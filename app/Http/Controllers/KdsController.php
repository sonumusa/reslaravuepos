<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    /**
     * Get all orders for Kitchen Display System
     */
    public function index(Request $request)
    {
        $query = Order::with(['items.menuItem', 'table', 'waiter'])
            ->whereIn('status', ['in_progress', 'preparing', 'sent_to_kitchen', 'ready'])
            ->whereNotNull('sent_to_kitchen_at');

        // Filter by branch
        if ($request->user()->role !== 'superadmin' && $request->user()->branch_id) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        // Order by priority and time
        $orders = $query->orderByDesc('is_priority')
            ->orderBy('sent_to_kitchen_at')
            ->get();

        // Group by status for easier KDS display
        $grouped = [
            'new' => $orders->where('status', 'sent_to_kitchen')->values(),
            'preparing' => $orders->whereIn('status', ['in_progress', 'preparing'])->values(),
            'ready' => $orders->where('status', 'ready')->values(),
        ];

        return response()->json([
            'success' => true,
            'data' => $orders,
            'grouped' => $grouped,
        ]);
    }

    /**
     * Get KDS statistics
     */
    public function stats(Request $request)
    {
        $branchId = $request->user()->branch_id;

        // Today's stats
        $today = now()->startOfDay();
        
        $ordersQuery = Order::whereDate('created_at', $today);
        if ($branchId) {
            $ordersQuery->where('branch_id', $branchId);
        }

        $totalOrders = $ordersQuery->count();
        $completedOrders = (clone $ordersQuery)->whereIn('status', ['completed', 'paid'])->count();
        $pendingOrders = (clone $ordersQuery)->whereIn('status', ['sent_to_kitchen', 'in_progress', 'preparing'])->count();
        $readyOrders = (clone $ordersQuery)->where('status', 'ready')->count();

        // Average prep time (in minutes)
        $avgPrepTime = Order::whereDate('created_at', $today)
            ->whereNotNull('sent_to_kitchen_at')
            ->whereNotNull('ready_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, sent_to_kitchen_at, ready_at)) as avg_time')
            ->value('avg_time');

        return response()->json([
            'success' => true,
            'data' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'pending_orders' => $pendingOrders,
                'ready_orders' => $readyOrders,
                'average_prep_time' => round($avgPrepTime ?? 0, 1),
            ]
        ]);
    }

    /**
     * Acknowledge order (kitchen has seen it)
     */
    public function acknowledge($orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->status === 'sent_to_kitchen') {
            $order->update([
                'status' => 'in_progress',
                'acknowledged_at' => now(),
            ]);

            $order->items()->where('status', 'sent')->update(['status' => 'preparing']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order acknowledged',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Start preparing order
     */
    public function startPreparing($orderId)
    {
        $order = Order::findOrFail($orderId);

        $order->update([
            'status' => 'preparing',
            'started_preparing_at' => now(),
        ]);

        $order->items()->whereIn('status', ['pending', 'sent'])->update(['status' => 'preparing']);

        return response()->json([
            'success' => true,
            'message' => 'Started preparing order',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Mark single item as ready
     */
    public function itemReady($orderId, $itemId)
    {
        $order = Order::findOrFail($orderId);
        $item = OrderItem::where('order_id', $orderId)->findOrFail($itemId);

        $item->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        // Check if all items are ready
        $pendingItems = $order->items()->where('status', '!=', 'ready')->count();

        if ($pendingItems === 0) {
            $order->update([
                'status' => 'ready',
                'ready_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item marked as ready',
            'data' => $order->fresh(['items.menuItem']),
            'all_ready' => $pendingItems === 0,
        ]);
    }

    /**
     * Bump order (mark entire order as ready)
     */
    public function bump($orderId)
    {
        $order = Order::findOrFail($orderId);

        $order->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        $order->items()->update([
            'status' => 'ready',
            'ready_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order bumped to ready',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }

    /**
     * Recall order (bring back to preparing)
     */
    public function recall($orderId)
    {
        $order = Order::findOrFail($orderId);

        if ($order->status === 'ready') {
            $order->update([
                'status' => 'preparing',
                'ready_at' => null,
            ]);

            $order->items()->where('status', 'ready')->update([
                'status' => 'preparing',
                'ready_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order recalled',
            'data' => $order->fresh(['items.menuItem'])
        ]);
    }
}
