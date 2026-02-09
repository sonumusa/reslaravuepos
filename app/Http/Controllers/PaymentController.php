<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Tip;
use App\Jobs\SubmitInvoiceToPra;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Get all payments with pagination
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice.customer', 'invoice.order']);

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by method
        if ($request->has('method')) {
            $query->where('method', $request->method);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ]);
    }

    /**
     * Get single payment
     */
    public function show($id)
    {
        $payment = Payment::with(['invoice.customer', 'invoice.order', 'tip'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Process payment
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'method' => 'required|in:cash,card,mobile,credit,other',
            'amount' => 'required|numeric|min:0',
            'tendered' => 'nullable|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'card_type' => 'nullable|string',
            'card_last_four' => 'nullable|string|size:4',
            'transaction_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        // Check if invoice is already fully paid
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already fully paid'
            ], 422);
        }

        // Calculate remaining amount
        $remainingAmount = $invoice->total_amount - $invoice->paid_amount;

        // Validate payment amount
        if ($request->amount > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds remaining balance'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate change for cash payments
            $change = 0;
            if ($request->method === 'cash' && $request->tendered) {
                $change = max(0, $request->tendered - $request->amount);
            }

            // Generate payment number
            $paymentNumber = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);

            $payment = Payment::create([
                'uuid' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'pos_session_id' => $request->pos_session_id,
                'received_by' => $request->user()->id,
                'payment_number' => $paymentNumber,
                'method' => $request->method,
                'amount' => $request->amount,
                'tendered' => $request->tendered,
                'change' => $change,
                'tip' => $request->tip ?? 0,
                'card_type' => $request->card_type,
                'card_last_four' => $request->card_last_four,
                'transaction_reference' => $request->transaction_reference,
                'status' => 'completed',
                'notes' => $request->notes,
            ]);

            // Create tip record if provided
            if ($request->tip && $request->tip > 0) {
                Tip::create([
                    'payment_id' => $payment->id,
                    'user_id' => $invoice->order->user_id ?? $request->user()->id,
                    'amount' => $request->tip,
                ]);
            }

            // Update invoice paid amount and status
            $newPaidAmount = $invoice->paid_amount + $request->amount;
            $newStatus = $newPaidAmount >= $invoice->total_amount ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'change_amount' => $change,
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now() : null,
            ]);

            // Update order status if fully paid
            if ($newStatus === 'paid' && $invoice->order) {
                $invoice->order->update(['status' => 'paid']);

                // Free up the table
                if ($invoice->order->table) {
                    $invoice->order->table->update(['status' => 'available']);
                }
            }

            // Queue PRA submission if invoice is fully paid
            if ($newStatus === 'paid' && config('pra.enabled', true)) {
                SubmitInvoiceToPra::dispatch($invoice->fresh());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'payment' => $payment,
                    'invoice' => $invoice->fresh(['payments']),
                    'change' => $change,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process split payment
     */
    public function split(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payments' => 'required|array|min:2',
            'payments.*.method' => 'required|in:cash,card,mobile,credit,other',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        // Validate total equals invoice total
        $totalAmount = collect($request->payments)->sum('amount');
        $remainingAmount = $invoice->total_amount - $invoice->paid_amount;

        if (abs($totalAmount - $remainingAmount) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Split payment total must equal remaining balance'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $createdPayments = [];

            foreach ($request->payments as $paymentData) {
                $paymentNumber = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);

                $payment = Payment::create([
                    'uuid' => Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'pos_session_id' => $request->pos_session_id,
                    'received_by' => $request->user()->id,
                    'payment_number' => $paymentNumber,
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'tendered' => $paymentData['tendered'] ?? null,
                    'change' => $paymentData['change'] ?? 0,
                    'card_type' => $paymentData['card_type'] ?? null,
                    'card_last_four' => $paymentData['card_last_four'] ?? null,
                    'transaction_reference' => $paymentData['transaction_reference'] ?? null,
                    'status' => 'completed',
                    'notes' => 'Split payment',
                ]);

                $createdPayments[] = $payment;
            }

            // Update invoice
            $invoice->update([
                'paid_amount' => $invoice->total_amount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Update order status
            if ($invoice->order) {
                $invoice->order->update(['status' => 'paid']);

                if ($invoice->order->table) {
                    $invoice->order->table->update(['status' => 'available']);
                }
            }

            // Queue PRA submission
            if (config('pra.enabled', true)) {
                SubmitInvoiceToPra::dispatch($invoice->fresh());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Split payment processed successfully',
                'data' => [
                    'payments' => $createdPayments,
                    'invoice' => $invoice->fresh(['payments']),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process split payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refund payment
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'manager_pin' => 'required|string',
        ]);

        // Verify manager PIN
        $manager = \App\Models\User::where('pin', $request->manager_pin)
            ->whereIn('role', ['admin', 'superadmin'])
            ->first();

        if (!$manager) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid manager PIN'
            ], 403);
        }

        $payment = Payment::with('invoice')->findOrFail($id);

        // Validate refund amount
        if ($request->amount > $payment->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed payment amount'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create refund payment record
            $refundPayment = Payment::create([
                'uuid' => Str::uuid(),
                'invoice_id' => $payment->invoice_id,
                'pos_session_id' => $request->pos_session_id,
                'received_by' => $request->user()->id,
                'payment_number' => 'REF-' . date('YmdHis') . '-' . rand(1000, 9999),
                'method' => $payment->method,
                'amount' => -$request->amount,
                'status' => 'completed',
                'notes' => 'Refund for ' . $payment->payment_number . ': ' . $request->reason,
            ]);

            // Update original payment status if full refund
            if ($request->amount >= $payment->amount) {
                $payment->update(['status' => 'refunded']);
            }

            // Update invoice
            $invoice = $payment->invoice;
            $newPaidAmount = max(0, $invoice->paid_amount - $request->amount);
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount <= 0 ? 'refunded' : 'partial',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => [
                    'refund' => $refundPayment,
                    'original_payment' => $payment->fresh(),
                    'invoice' => $invoice->fresh(['payments']),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: ' . $e->getMessage()
            ], 500);
        }
    }
}
