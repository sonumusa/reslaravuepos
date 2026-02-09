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

        $waiterPerformance = Order::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as orders_count, SUM(orders.total_amount) as total_sales')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $waiterPerformance
        ]);
    }

    public function cashierSummary(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $cashierSummary = Payment::whereBetween('payments.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->join('users', 'payments.received_by', '=', 'users.id')
            ->selectRaw('users.name, users.id, 
                COUNT(*) as transaction_count,
                SUM(CASE WHEN payments.amount > 0 THEN payments.amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN payments.amount < 0 THEN ABS(payments.amount) ELSE 0 END) as total_refunds,
                SUM(payments.tip) as total_tips')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_collected')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cashierSummary
        ]);
    }

    public function waiterSummary(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $waiterSummary = Order::whereBetween('orders.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('users.role', 'waiter')
            ->selectRaw('users.name, users.id,
                COUNT(*) as orders_count,
                SUM(orders.total_amount) as total_sales,
                AVG(orders.total_amount) as avg_order_value,
                SUM(CASE WHEN orders.status = "completed" OR orders.status = "paid" THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN orders.status = "cancelled" OR orders.status = "void" THEN 1 ELSE 0 END) as cancelled_orders')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $waiterSummary
        ]);
    }

    public function taxSummary(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $taxSummary = Invoice::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'paid')
            ->selectRaw('
                DATE(created_at) as date,
                SUM(subtotal) as gross_sales,
                SUM(discount_amount) as total_discounts,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as net_sales,
                COUNT(*) as invoice_count,
                AVG(tax_rate) as avg_tax_rate')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totals = Invoice::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'paid')
            ->selectRaw('
                SUM(subtotal) as gross_sales,
                SUM(discount_amount) as total_discounts,
                SUM(tax_amount) as total_tax,
                SUM(total_amount) as net_sales,
                COUNT(*) as invoice_count')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'daily' => $taxSummary,
                'totals' => $totals,
            ]
        ]);
    }

    public function praStatus(Request $request)
    {
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $praStatus = Invoice::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'paid')
            ->selectRaw('pra_status, COUNT(*) as count, SUM(total_amount) as total_amount')
            ->groupBy('pra_status')
            ->get();

        $failedInvoices = Invoice::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'paid')
            ->where('pra_status', 'failed')
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $praStatus,
                'failed_invoices' => $failedInvoices,
            ]
        ]);
    }
}