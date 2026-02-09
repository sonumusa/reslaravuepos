<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Payment;
use App\Jobs\SubmitInvoiceToPra;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Get all invoices with pagination
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'order', 'cashier', 'payments']);

        // Filter by branch
        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by PRA status
        if ($request->has('pra_status')) {
            $query->where('pra_status', $request->pra_status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $invoices->items(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        ]);
    }

    /**
     * Get invoices pending PRA submission
     */
    public function praPending(Request $request)
    {
        $query = Invoice::with(['customer', 'order'])
            ->whereIn('pra_status', ['pending', 'failed', 'queued'])
            ->where('status', 'paid');

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
     * Get single invoice
     */
    public function show($id)
    {
        $invoice = Invoice::with(['customer', 'order.items.menuItem', 'items.menuItem', 'payments', 'praLogs'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }

    /**
     * Create invoice from order
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $order = Order::with('items.menuItem')->findOrFail($request->order_id);

        // Check if invoice already exists
        if ($order->invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already exists for this order'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = $order->items->sum(function($item) {
                return $item->quantity * $item->unit_price;
            });

            $taxRate = $request->user()->branch->gst_rate ?? 16;
            $taxAmount = round($subtotal * ($taxRate / 100), 2);

            // Calculate discount
            $discountAmount = 0;
            if ($request->discount_type && $request->discount_value) {
                if ($request->discount_type === 'percentage') {
                    $discountAmount = round($subtotal * ($request->discount_value / 100), 2);
                } else {
                    $discountAmount = $request->discount_value;
                }
            }

            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Generate invoice number
            $branchCode = $request->user()->branch->code ?? 'BR01';
            $invoiceNumber = $branchCode . '-' . date('Ymd') . '-' . strtoupper(Str::random(4));
            $localInvoiceNumber = 'INV-' . date('Ymd-His') . '-' . rand(1000, 9999);

            $invoice = Invoice::create([
                'uuid' => Str::uuid(),
                'branch_id' => $request->user()->branch_id,
                'order_id' => $order->id,
                'customer_id' => $request->customer_id ?? $order->customer_id,
                'cashier_id' => $request->user()->id,
                'pos_session_id' => $request->pos_session_id,
                'invoice_number' => $invoiceNumber,
                'local_invoice_number' => $localInvoiceNumber,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'discount_reason' => $request->discount_reason,
                'tax_amount' => $taxAmount,
                'tax_rate' => $taxRate,
                'service_charge' => 0,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'pra_status' => config('pra.enabled', true) ? 'pending' : 'not_required',
                'notes' => $request->notes,
            ]);

            // Create invoice items from order items
            foreach ($order->items as $orderItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'menu_item_id' => $orderItem->menu_item_id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'subtotal' => $orderItem->subtotal,
                    'tax_amount' => $orderItem->tax_amount,
                    'discount_amount' => $orderItem->discount_amount ?? 0,
                    'total_amount' => $orderItem->total_amount,
                    'notes' => $orderItem->notes,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice->load(['items.menuItem', 'customer'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        // Only allow updates on draft/pending invoices
        if (!in_array($invoice->status, ['draft', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update invoice with status: ' . $invoice->status
            ], 422);
        }

        $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'notes' => 'sometimes|string',
        ]);

        $invoice->update($request->only(['customer_id', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => $invoice->fresh(['items.menuItem', 'customer'])
        ]);
    }

    /**
     * Get invoice receipt data
     */
    public function receipt($id)
    {
        $invoice = Invoice::with(['customer', 'order.table', 'items.menuItem', 'payments', 'cashier', 'branch'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => $invoice,
                'branch' => $invoice->branch,
                'printed_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Void invoice
     */
    public function void(Request $request, $id)
    {
        $request->validate([
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

        $invoice = Invoice::findOrFail($id);

        // Don't allow voiding already voided invoices
        if ($invoice->status === 'void') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already voided'
            ], 422);
        }

        $invoice->update([
            'status' => 'void',
            'void_reason' => $request->reason,
            'voided_by' => $manager->id,
            'voided_at' => now(),
        ]);

        // Update related order status
        if ($invoice->order) {
            $invoice->order->update(['status' => 'voided']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice voided successfully',
            'data' => $invoice
        ]);
    }

    /**
     * Refund invoice
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

        $invoice = Invoice::findOrFail($id);

        // Only allow refunds on paid invoices
        if ($invoice->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Can only refund paid invoices'
            ], 422);
        }

        // Validate refund amount
        if ($request->amount > $invoice->paid_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed paid amount'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create refund payment record
            Payment::create([
                'uuid' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'pos_session_id' => $request->pos_session_id,
                'received_by' => $request->user()->id,
                'payment_number' => 'REF-' . date('YmdHis') . '-' . rand(1000, 9999),
                'method' => 'cash',
                'amount' => -$request->amount,
                'status' => 'completed',
                'notes' => 'Refund: ' . $request->reason,
            ]);

            // Update invoice
            $newPaidAmount = $invoice->paid_amount - $request->amount;
            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount <= 0 ? 'refunded' : 'partial',
                'refund_reason' => $request->reason,
                'refunded_by' => $manager->id,
                'refunded_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'data' => $invoice->fresh(['payments'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete invoice (soft delete)
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Only allow deletion of draft invoices
        if ($invoice->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Can only delete draft invoices'
            ], 422);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully'
        ]);
    }
}
