<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get all orders with relationships
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'table', 'waiter', 'items.menuItem'])
            ->withCount('items');

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

        // Filter by branch (if user is not superadmin)
        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'table_id' => 'nullable|exists:tables,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
            'items.*.modifiers' => 'nullable|array',
            'notes' => 'nullable|string',
            'is_priority' => 'boolean',
        ]);

        // Generate order number
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;
        
        foreach ($request->items as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            $itemTotal = $menuItem->price * $item['quantity'];
            $subtotal += $itemTotal;
            $taxAmount += $itemTotal * ($menuItem->tax_rate / 100);
        }

        $order = Order::create([
            'uuid' => Str::uuid(),
            'order_number' => $orderNumber,
            'branch_id' => $request->user()->branch_id,
            'user_id' => $request->user()->id,
            'pos_session_id' => $request->pos_session_id,
            'table_id' => $request->table_id,
            'customer_id' => $request->customer_id,
            'order_type' => $request->order_type,
            'status' => 'open',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $subtotal + $taxAmount,
            'notes' => $request->notes,
            'is_priority' => $request->is_priority ?? false,
        ]);

        // Create order items
        foreach ($request->items as $item) {
            $menuItem = MenuItem::find($item['menu_item_id']);
            $itemSubtotal = $menuItem->price * $item['quantity'];
            $itemTax = $itemSubtotal * ($menuItem->tax_rate / 100);

            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem->price,
                'subtotal' => $itemSubtotal,
                'tax_amount' => $itemTax,
                'discount_amount' => 0,
                'total_amount' => $itemSubtotal + $itemTax,
                'notes' => $item['notes'] ?? null,
                'modifiers' => json_encode($item['modifiers'] ?? []),
                'status' => 'pending',
            ]);
        }

        // Update table status if dine-in
        if ($order->table_id) {
            $order->table->update(['status' => 'occupied']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order->load(['items.menuItem', 'customer', 'table'])
        ], 201);
    }

    /**
     * Get single order
     */
    public function show($id)
    {
        $order = Order::with(['customer', 'table', 'waiter', 'items.menuItem', 'invoice'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }

    /**
     * Update order
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'sometimes|in:open,in_progress,ready,served,completed,cancelled',
            'notes' => 'sometimes|string',
            'is_priority' => 'sometimes|boolean',
            'customer_id' => 'sometimes|exists:customers,id',
        ]);

        $order->update($request->only([
            'status', 'notes', 'is_priority', 'customer_id'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $order->fresh(['items.menuItem', 'customer', 'table'])
        ]);
    }

    /**
     * Delete order
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        // Only allow deletion of open/draft orders
        if (!in_array($order->status, ['open', 'draft', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete order with status: ' . $order->status
            ], 422);
        }

        // Free up table
        if ($order->table_id) {
            $order->table->update(['status' => 'available']);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Add item to existing order
     */
    public function addItem(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);

        $request->validate([
            'menu_item_id' => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'modifiers' => 'nullable|array',
        ]);

        $menuItem = MenuItem::findOrFail($request->menu_item_id);
        $itemSubtotal = $menuItem->price * $request->quantity;
        $itemTax = $itemSubtotal * ($menuItem->tax_rate / 100);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'menu_item_id' => $menuItem->id,
            'quantity' => $request->quantity,
            'unit_price' => $menuItem->price,
            'subtotal' => $itemSubtotal,
            'tax_amount' => $itemTax,
            'discount_amount' => 0,
            'total_amount' => $itemSubtotal + $itemTax,
            'notes' => $request->notes,
            'modifiers' => json_encode($request->modifiers ?? []),
            'status' => 'pending',
        ]);

        // Recalculate order totals
        $this->recalculateOrderTotals($order);

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Update order item
     */
    public function updateItem(Request $request, $orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)->findOrFail($itemId);

        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'notes' => 'sometimes|string',
        ]);

        if ($request->has('quantity')) {
            $itemSubtotal = $item->unit_price * $request->quantity;
            $itemTax = $itemSubtotal * ($item->menuItem->tax_rate / 100);

            $item->update([
                'quantity' => $request->quantity,
                'subtotal' => $itemSubtotal,
                'tax_amount' => $itemTax,
                'total_amount' => $itemSubtotal + $itemTax,
            ]);
        }

        if ($request->has('notes')) {
            $item->update(['notes' => $request->notes]);
        }

        $this->recalculateOrderTotals($order);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Remove item from order
     */
    public function removeItem($orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)->findOrFail($itemId);

        $item->delete();
        $this->recalculateOrderTotals($order);

        return response()->json([
            'success' => true,
            'message' => 'Item removed successfully',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Send order to kitchen
     */
    public function sendToKitchen($id)
    {
        $order = Order::findOrFail($id);
        
        $order->update([
            'status' => 'in_progress',
            'sent_to_kitchen_at' => now(),
        ]);

        // Update all pending items
        $order->items()->where('status', 'pending')->update(['status' => 'preparing']);

        return response()->json([
            'success' => true,
            'message' => 'Order sent to kitchen',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Hold order
     */
    public function hold($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'held']);

        return response()->json([
            'success' => true,
            'message' => 'Order put on hold',
            'data' => $order
        ]);
    }

    /**
     * Resume held order
     */
    public function resume($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'open']);

        return response()->json([
            'success' => true,
            'message' => 'Order resumed',
            'data' => $order
        ]);
    }

    /**
     * Mark order as ready
     */
    public function ready($id)
    {
        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        // Mark all items as ready
        $order->items()->update(['status' => 'ready']);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as ready',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Mark order as served
     */
    public function served($id)
    {
        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        // Mark all items as served
        $order->items()->update(['status' => 'served']);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as served',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Complete order
     */
    public function complete($id)
    {
        $order = Order::findOrFail($id);
        
        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Free up table
        if ($order->table_id) {
            $order->table->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order completed',
            'data' => $order
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $order->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->reason,
        ]);

        // Free up table
        if ($order->table_id) {
            $order->table->update(['status' => 'available']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled',
            'data' => $order
        ]);
    }

    /**
     * Void order (requires manager approval)
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

        $order = Order::findOrFail($id);
        $order->update([
            'status' => 'voided',
            'void_reason' => $request->reason,
            'voided_by' => $manager->id,
            'voided_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order voided',
            'data' => $order
        ]);
    }

    /**
     * Get completed orders
     */
    public function completed(Request $request)
    {
        $orders = Order::with(['customer', 'items'])
            ->where('status', 'completed')
            ->when($request->user()->branch_id, function($q) use ($request) {
                $q->where('branch_id', $request->user()->branch_id);
            })
            ->orderBy('completed_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get held orders
     */
    public function held(Request $request)
    {
        $orders = Order::with(['customer', 'items', 'table'])
            ->where('status', 'held')
            ->when($request->user()->branch_id, function($q) use ($request) {
                $q->where('branch_id', $request->user()->branch_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get orders by table
     */
    public function byTable($tableId)
    {
        $orders = Order::with(['items.menuItem', 'customer'])
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['completed', 'cancelled', 'voided'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Process payment for order (creates invoice and processes payment)
     */
    public function pay(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,card,mobile,split,credit,other',
            'amount_tendered' => 'nullable|numeric|min:0',
            'tip' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $order = Order::with('items.menuItem')->findOrFail($id);

        // Check if order can be paid
        if (!in_array($order->status, ['open', 'ready', 'served', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be paid in current status: ' . $order->status
            ], 422);
        }

        // Check if invoice already exists
        if ($order->invoice && $order->invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order has already been paid'
            ], 422);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = $order->items->sum('subtotal');
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

            // Create or update invoice
            $invoice = $order->invoice;
            if (!$invoice) {
                $branchCode = $request->user()->branch->code ?? 'BR01';
                $invoiceNumber = $branchCode . '-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
                
                $invoice = \App\Models\Invoice::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'branch_id' => $request->user()->branch_id,
                    'order_id' => $order->id,
                    'customer_id' => $request->customer_id ?? $order->customer_id,
                    'cashier_id' => $request->user()->id,
                    'pos_session_id' => $request->pos_session_id,
                    'invoice_number' => $invoiceNumber,
                    'local_invoice_number' => 'INV-' . date('YmdHis') . '-' . rand(1000, 9999),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'discount_type' => $request->discount_type,
                    'discount_value' => $request->discount_value,
                    'discount_reason' => $request->discount_reason,
                    'tax_amount' => $taxAmount,
                    'tax_rate' => $taxRate,
                    'service_charge' => 0,
                    'tip_amount' => $request->tip ?? 0,
                    'total_amount' => $totalAmount,
                    'status' => 'pending',
                    'pra_status' => config('pra.enabled', true) ? 'pending' : 'not_required',
                ]);

                // Create invoice items
                foreach ($order->items as $orderItem) {
                    \App\Models\InvoiceItem::create([
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
            }

            // Calculate change for cash
            $change = 0;
            if ($request->payment_method === 'cash' && $request->amount_tendered) {
                $change = max(0, $request->amount_tendered - $totalAmount);
            }

            // Create payment record
            $payment = \App\Models\Payment::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'invoice_id' => $invoice->id,
                'pos_session_id' => $request->pos_session_id,
                'received_by' => $request->user()->id,
                'payment_number' => 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999),
                'method' => $request->payment_method,
                'amount' => $totalAmount,
                'tip' => $request->tip ?? 0,
                'tendered' => $request->amount_tendered,
                'change' => $change,
                'status' => 'completed',
            ]);

            // Update invoice
            $invoice->update([
                'paid_amount' => $totalAmount,
                'change_amount' => $change,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Update order
            $order->update([
                'status' => 'paid',
                'completed_at' => now(),
            ]);

            // Free up table
            if ($order->table_id) {
                $order->table->update(['status' => 'available']);
            }

            // Update customer stats
            if ($order->customer_id && $order->customer) {
                $order->customer->increment('total_spent', $totalAmount);
                $order->customer->increment('total_orders');
                // Add loyalty points (1 point per 100 spent)
                $loyaltyPoints = floor($totalAmount / 100);
                if ($loyaltyPoints > 0) {
                    $order->customer->increment('loyalty_points', $loyaltyPoints);
                }
            }

            // Queue PRA submission
            if (config('pra.enabled', true)) {
                \App\Jobs\SubmitInvoiceToPra::dispatch($invoice);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => [
                    'order' => $order->fresh(['items.menuItem', 'customer', 'table']),
                    'invoice' => $invoice->fresh(['payments']),
                    'payment' => $payment,
                    'change' => $change,
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate order totals
     */
    private function recalculateOrderTotals(Order $order)
    {
        $items = $order->items()->get();
        
        $subtotal = $items->sum('subtotal');
        $taxAmount = $items->sum('tax_amount');
        $discountAmount = $items->sum('discount_amount');

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $subtotal + $taxAmount - $discountAmount,
        ]);
    }
}