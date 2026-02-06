<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailySales(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        $sales = Invoice::whereDate('created_at', $date)
            ->where('status', 'paid')
            ->sum('total_amount');

        $orders = Order::whereDate('created_at', $date)->count();

        $payments = Payment::whereDate('created_at', $date)
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'total_sales' => $sales,
                'total_orders' => $orders,
                'payments_by_method' => $payments,
            ]
        ]);
    }

    public function hourlySales(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        $hourlySales = Invoice::whereDate('created_at', $date)
            ->where('status', 'paid')
            ->selectRaw('HOUR(created_at) as hour, SUM(total_amount) as sales, COUNT(*) as orders')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $hourlySales
        ]);
    }

    public function itemsSold(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $items = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->selectRaw('menu_items.name, SUM(order_items.quantity) as total_quantity, SUM(order_items.total_amount) as total_sales')
            ->groupBy('menu_items.name')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    public function paymentMethods(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $payments = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    public function staffPerformance(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $waiterPerformance = Order::whereBetween('created_at', [$startDate, $endDate])
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as orders_count, SUM(orders.total_amount) as total_sales')
            ->groupBy('users.name')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $waiterPerformance
        ]);
    }
}