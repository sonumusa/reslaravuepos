<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\PosSession;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        // Today's stats
        $todaySales = Invoice::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('total_amount');

        $todayOrders = Order::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $today)
            ->count();

        // Yesterday's stats for comparison
        $yesterdaySales = Invoice::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $yesterday)
            ->where('status', 'paid')
            ->sum('total_amount');

        $yesterdayOrders = Order::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $yesterday)
            ->count();

        // Calculate changes
        $salesChange = $yesterdaySales > 0 
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1) 
            : ($todaySales > 0 ? 100 : 0);

        $ordersChange = $yesterdayOrders > 0 
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1) 
            : ($todayOrders > 0 ? 100 : 0);

        $averageOrder = $todayOrders > 0 ? round($todaySales / $todayOrders, 2) : 0;
        $yesterdayAvg = $yesterdayOrders > 0 ? $yesterdaySales / $yesterdayOrders : 0;
        $avgChange = $yesterdayAvg > 0 
            ? round((($averageOrder - $yesterdayAvg) / $yesterdayAvg) * 100, 1) 
            : 0;

        // PRA pending count
        $praPending = Invoice::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereIn('pra_status', ['pending', 'failed', 'queued'])
            ->where('status', 'paid')
            ->count();

        $stats = [
            'todaySales' => $todaySales,
            'todayOrders' => $todayOrders,
            'salesChange' => $salesChange,
            'ordersChange' => $ordersChange,
            'averageOrder' => $averageOrder,
            'avgChange' => $avgChange,
            'praPending' => $praPending
        ];

        // Top selling items today
        $topItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
            ->whereDate('orders.created_at', $today)
            ->selectRaw('menu_items.id, menu_items.name, menu_items.price, SUM(order_items.quantity) as qty, SUM(order_items.total_amount) as total')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.price')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        // Recent orders
        $recentOrders = Order::with(['customer', 'table', 'waiter'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get();

        // Active POS sessions
        $activeSessions = PosSession::with(['user', 'terminal'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', 'open')
            ->get();

        // Sales chart data (last 7 days)
        $salesChart = $this->getSalesChartData($branchId, 7);

        // Order types distribution
        $orderTypes = Order::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', $today)
            ->selectRaw('order_type, COUNT(*) as count')
            ->groupBy('order_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'topItems' => $topItems,
                'recentOrders' => $recentOrders,
                'activeSessions' => $activeSessions,
                'salesChart' => $salesChart,
                'orderTypes' => [
                    'labels' => $orderTypes->pluck('order_type')->toArray(),
                    'values' => $orderTypes->pluck('count')->toArray()
                ]
            ]
        ]);
    }

    /**
     * Get sales chart data for last N days
     */
    private function getSalesChartData($branchId, $days = 7)
    {
        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');
            
            $sales = Invoice::when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', $date)
                ->where('status', 'paid')
                ->sum('total_amount');
            
            $values[] = (float) $sales;
        }

        return [
            'labels' => $labels,
            'values' => $values
        ];
    }
}
