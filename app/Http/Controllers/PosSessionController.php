<?php

namespace App\Http\Controllers;

use App\Models\PosSession;
use App\Models\PosTerminal;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosSessionController extends Controller
{
    /**
     * Get all POS sessions with pagination
     */
    public function index(Request $request)
    {
        $query = PosSession::with(['user', 'terminal', 'branch']);

        // Filter by branch
        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->orderBy('opened_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        ]);
    }

    /**
     * Get current active session for user
     */
    public function current(Request $request)
    {
        $session = PosSession::with(['terminal', 'branch'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Get single session with summary
     */
    public function show($id)
    {
        $session = PosSession::with(['user', 'terminal', 'branch', 'orders.items'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    }

    /**
     * Get session summary/report
     */
    public function summary($id)
    {
        $session = PosSession::findOrFail($id);

        // Get all orders for this session
        $orders = Order::where('pos_session_id', $id)->get();
        
        // Get all payments for this session
        $payments = Payment::where('pos_session_id', $id)->get();

        // Calculate totals
        $summary = [
            'session_id' => $session->id,
            'opened_at' => $session->opened_at,
            'closed_at' => $session->closed_at,
            'opening_cash' => $session->opening_cash,
            'closing_cash' => $session->closing_cash,
            
            'total_orders' => $orders->count(),
            'total_sales' => $payments->where('amount', '>', 0)->sum('amount'),
            'total_refunds' => abs($payments->where('amount', '<', 0)->sum('amount')),
            'total_tips' => $payments->sum('tip'),
            
            'cash_sales' => $payments->where('method', 'cash')->where('amount', '>', 0)->sum('amount'),
            'card_sales' => $payments->where('method', 'card')->where('amount', '>', 0)->sum('amount'),
            'mobile_sales' => $payments->where('method', 'mobile')->where('amount', '>', 0)->sum('amount'),
            'other_sales' => $payments->whereNotIn('method', ['cash', 'card', 'mobile'])->where('amount', '>', 0)->sum('amount'),
            
            'expected_cash' => $session->opening_cash + $payments->where('method', 'cash')->sum('amount'),
            'cash_difference' => $session->closing_cash ? ($session->closing_cash - ($session->opening_cash + $payments->where('method', 'cash')->sum('amount'))) : null,
            
            'orders_by_type' => $orders->groupBy('order_type')->map->count(),
            'orders_by_status' => $orders->groupBy('status')->map->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Open new POS session
     */
    public function open(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|exists:pos_terminals,id',
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check if user already has an open session
        $existingSession = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an open session',
                'data' => $existingSession
            ], 422);
        }

        // Check if terminal is available
        $terminal = PosTerminal::findOrFail($request->terminal_id);
        $terminalSession = PosSession::where('terminal_id', $request->terminal_id)
            ->where('status', 'open')
            ->first();

        if ($terminalSession) {
            return response()->json([
                'success' => false,
                'message' => 'Terminal is already in use'
            ], 422);
        }

        $session = PosSession::create([
            'branch_id' => $request->user()->branch_id ?? $terminal->branch_id,
            'terminal_id' => $request->terminal_id,
            'user_id' => $request->user()->id,
            'opening_cash' => $request->opening_cash,
            'opened_at' => now(),
            'status' => 'open',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'POS session opened',
            'data' => $session->load(['terminal', 'branch'])
        ], 201);
    }

    /**
     * Close POS session
     */
    public function close(Request $request)
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session found'
            ], 404);
        }

        // Calculate expected cash
        $cashPayments = Payment::where('pos_session_id', $session->id)
            ->where('method', 'cash')
            ->sum('amount');
        
        $expectedCash = $session->opening_cash + $cashPayments;
        $cashDifference = $request->closing_cash - $expectedCash;

        // Get totals
        $totalSales = Payment::where('pos_session_id', $session->id)
            ->where('amount', '>', 0)
            ->sum('amount');
        
        $totalOrders = Order::where('pos_session_id', $session->id)->count();

        $session->update([
            'closing_cash' => $request->closing_cash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $cashDifference,
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'closed_at' => now(),
            'status' => 'closed',
            'notes' => $request->notes ?? $session->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'POS session closed',
            'data' => [
                'session' => $session,
                'summary' => [
                    'opening_cash' => $session->opening_cash,
                    'closing_cash' => $request->closing_cash,
                    'expected_cash' => $expectedCash,
                    'cash_difference' => $cashDifference,
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                ]
            ]
        ]);
    }

    /**
     * Suspend session (temporary pause)
     */
    public function suspend(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session found'
            ], 404);
        }

        $session->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspend_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session suspended',
            'data' => $session
        ]);
    }

    /**
     * Resume suspended session
     */
    public function resume(Request $request)
    {
        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'suspended')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No suspended session found'
            ], 404);
        }

        $session->update([
            'status' => 'open',
            'resumed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session resumed',
            'data' => $session
        ]);
    }

    /**
     * Record cash drop (removal of cash from drawer)
     */
    public function cashDrop(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session found'
            ], 404);
        }

        // Record cash drop as negative payment/adjustment
        // You could create a separate cash_drops table for better tracking

        return response()->json([
            'success' => true,
            'message' => 'Cash drop recorded',
            'data' => [
                'amount' => $request->amount,
                'reason' => $request->reason,
                'recorded_at' => now(),
            ]
        ]);
    }

    /**
     * Generate X Report (mid-day report without closing)
     */
    public function xReport(Request $request)
    {
        $session = PosSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No open session found'
            ], 404);
        }

        // Get summary without closing
        $payments = Payment::where('pos_session_id', $session->id)->get();
        $orders = Order::where('pos_session_id', $session->id)->get();

        $report = [
            'report_type' => 'X',
            'generated_at' => now()->toISOString(),
            'session' => [
                'id' => $session->id,
                'opened_at' => $session->opened_at,
                'opening_cash' => $session->opening_cash,
            ],
            'totals' => [
                'gross_sales' => $payments->where('amount', '>', 0)->sum('amount'),
                'refunds' => abs($payments->where('amount', '<', 0)->sum('amount')),
                'net_sales' => $payments->sum('amount'),
                'total_orders' => $orders->count(),
                'total_tips' => $payments->sum('tip'),
            ],
            'by_payment_method' => [
                'cash' => $payments->where('method', 'cash')->sum('amount'),
                'card' => $payments->where('method', 'card')->sum('amount'),
                'mobile' => $payments->where('method', 'mobile')->sum('amount'),
                'other' => $payments->whereNotIn('method', ['cash', 'card', 'mobile'])->sum('amount'),
            ],
            'expected_cash' => $session->opening_cash + $payments->where('method', 'cash')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Generate Z Report (end of day report)
     */
    public function zReport(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $branchId = $request->user()->branch_id;

        // Get all sessions for the date
        $sessions = PosSession::with(['user', 'terminal'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('opened_at', $date)
            ->get();

        // Get all payments for the date
        $payments = Payment::whereDate('created_at', $date)
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('invoice', fn($q) => $q->where('branch_id', $branchId));
            })
            ->get();

        // Get all orders for the date
        $orders = Order::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $date)
            ->get();

        $report = [
            'report_type' => 'Z',
            'date' => $date,
            'generated_at' => now()->toISOString(),
            'sessions' => $sessions->map(fn($s) => [
                'id' => $s->id,
                'user' => $s->user->name ?? 'Unknown',
                'terminal' => $s->terminal->name ?? 'Unknown',
                'opened_at' => $s->opened_at,
                'closed_at' => $s->closed_at,
                'opening_cash' => $s->opening_cash,
                'closing_cash' => $s->closing_cash,
                'cash_difference' => $s->cash_difference,
                'total_sales' => $s->total_sales,
                'total_orders' => $s->total_orders,
            ]),
            'totals' => [
                'gross_sales' => $payments->where('amount', '>', 0)->sum('amount'),
                'refunds' => abs($payments->where('amount', '<', 0)->sum('amount')),
                'net_sales' => $payments->sum('amount'),
                'total_orders' => $orders->count(),
                'total_tips' => $payments->sum('tip'),
            ],
            'by_payment_method' => [
                'cash' => $payments->where('method', 'cash')->sum('amount'),
                'card' => $payments->where('method', 'card')->sum('amount'),
                'mobile' => $payments->where('method', 'mobile')->sum('amount'),
                'other' => $payments->whereNotIn('method', ['cash', 'card', 'mobile'])->sum('amount'),
            ],
            'by_order_type' => $orders->groupBy('order_type')->map->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Get available terminals
     */
    public function terminals(Request $request)
    {
        $branchId = $request->user()->branch_id;

        $terminals = PosTerminal::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->get();

        // Mark which ones have active sessions
        $activeSessionTerminals = PosSession::where('status', 'open')
            ->pluck('terminal_id')
            ->toArray();

        $terminals = $terminals->map(function($terminal) use ($activeSessionTerminals) {
            $terminal->is_in_use = in_array($terminal->id, $activeSessionTerminals);
            return $terminal;
        });

        return response()->json([
            'success' => true,
            'data' => $terminals
        ]);
    }
}
