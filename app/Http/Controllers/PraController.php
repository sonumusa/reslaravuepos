<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PraLog;
use App\Services\PraService;
use App\Jobs\SubmitInvoiceToPra;
use Illuminate\Http\Request;

class PraController extends Controller
{
    protected $praService;

    public function __construct(PraService $praService)
    {
        $this->praService = $praService;
    }

    /**
     * Get PRA integration status
     */
    public function status(Request $request)
    {
        $branchId = $request->user()->branch_id;

        // Count invoices by PRA status
        $query = Invoice::where('status', 'paid');
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $statusCounts = [
            'pending' => (clone $query)->where('pra_status', 'pending')->count(),
            'queued' => (clone $query)->where('pra_status', 'queued')->count(),
            'submitted' => (clone $query)->where('pra_status', 'submitted')->count(),
            'success' => (clone $query)->where('pra_status', 'success')->count(),
            'failed' => (clone $query)->where('pra_status', 'failed')->count(),
            'not_required' => (clone $query)->where('pra_status', 'not_required')->count(),
        ];

        // Recent failures
        $recentFailures = PraLog::with('invoice')
            ->where('status', 'failed')
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('invoice', fn($q) => $q->where('branch_id', $branchId));
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => config('pra.enabled', true),
                'test_mode' => config('pra.test_mode', true),
                'status_counts' => $statusCounts,
                'recent_failures' => $recentFailures,
            ]
        ]);
    }

    /**
     * Get pending PRA submissions
     */
    public function pending(Request $request)
    {
        $query = Invoice::with(['customer', 'order'])
            ->where('status', 'paid')
            ->whereIn('pra_status', ['pending', 'failed', 'queued']);

        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $invoices = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Get PRA submission logs
     */
    public function logs(Request $request)
    {
        $query = PraLog::with(['invoice.customer']);

        // Filter by invoice
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by branch
        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where('branch_id', $request->user()->branch_id);
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Test PRA connection
     */
    public function testConnection()
    {
        try {
            // In test mode, just return success
            if (config('pra.test_mode', true)) {
                return response()->json([
                    'success' => true,
                    'message' => 'PRA test connection successful (test mode)',
                    'data' => [
                        'test_mode' => true,
                        'api_url' => config('pra.api_url'),
                    ]
                ]);
            }

            // Real connection test
            $response = \Illuminate\Support\Facades\Http::withToken(config('pra.api_token'))
                ->timeout(10)
                ->get(config('pra.api_url') . '/ping');

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'PRA connection successful' : 'PRA connection failed',
                'data' => [
                    'status_code' => $response->status(),
                    'response' => $response->json(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PRA connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit single invoice to PRA
     */
    public function submit($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        // Check if invoice is paid
        if ($invoice->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Can only submit paid invoices to PRA'
            ], 422);
        }

        // Check if already successfully submitted
        if ($invoice->pra_status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already submitted successfully to PRA'
            ], 422);
        }

        try {
            $result = $this->praService->submitInvoice($invoice);

            return response()->json([
                'success' => $result['success'] ?? false,
                'message' => $result['success'] ? 'Invoice submitted to PRA' : 'PRA submission failed',
                'data' => [
                    'invoice' => $invoice->fresh(),
                    'pra_response' => $result,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PRA submission failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed PRA submission
     */
    public function retry($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        // Only retry failed submissions
        if (!in_array($invoice->pra_status, ['failed', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Can only retry failed or pending submissions'
            ], 422);
        }

        // Check retry limit
        if ($invoice->pra_retry_count >= config('pra.retry_attempts', 3)) {
            return response()->json([
                'success' => false,
                'message' => 'Maximum retry attempts reached'
            ], 422);
        }

        // Increment retry count
        $invoice->increment('pra_retry_count');
        $invoice->update(['pra_status' => 'queued']);

        // Dispatch job
        SubmitInvoiceToPra::dispatch($invoice);

        return response()->json([
            'success' => true,
            'message' => 'PRA submission queued for retry',
            'data' => $invoice->fresh()
        ]);
    }

    /**
     * Retry all failed PRA submissions
     */
    public function retryAll(Request $request)
    {
        $query = Invoice::where('status', 'paid')
            ->where('pra_status', 'failed')
            ->where('pra_retry_count', '<', config('pra.retry_attempts', 3));

        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $invoices = $query->get();
        $count = 0;

        foreach ($invoices as $invoice) {
            $invoice->increment('pra_retry_count');
            $invoice->update(['pra_status' => 'queued']);
            SubmitInvoiceToPra::dispatch($invoice);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} invoices queued for PRA submission",
            'data' => ['count' => $count]
        ]);
    }

    /**
     * Update PRA settings
     */
    public function updateSettings(Request $request)
    {
        // This would typically update settings in database or .env
        // For now, return current settings

        $request->validate([
            'enabled' => 'sometimes|boolean',
            'test_mode' => 'sometimes|boolean',
        ]);

        // In a real implementation, you'd save these to database
        // For now, just acknowledge the request

        return response()->json([
            'success' => true,
            'message' => 'PRA settings updated',
            'data' => [
                'enabled' => $request->enabled ?? config('pra.enabled'),
                'test_mode' => $request->test_mode ?? config('pra.test_mode'),
                'note' => 'Settings are stored in .env - manual update required for persistence'
            ]
        ]);
    }
}
