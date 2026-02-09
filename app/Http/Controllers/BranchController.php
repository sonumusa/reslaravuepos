<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Get all branches
     */
    public function index(Request $request)
    {
        $query = Branch::withCount(['users', 'orders', 'tables']);

        // Only superadmin can see all branches
        if ($request->user()->role !== 'superadmin') {
            $query->where('id', $request->user()->branch_id);
        }

        // Filter active only
        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
        }

        $branches = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Get single branch
     */
    public function show($id)
    {
        $branch = Branch::with(['users', 'terminals', 'tables'])
            ->withCount(['orders', 'invoices'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    /**
     * Create new branch
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:branches',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $branch = Branch::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'address' => $request->address,
            'city' => $request->city,
            'phone' => $request->phone,
            'email' => $request->email,
            'gst_rate' => $request->gst_rate ?? 16,
            'is_active' => $request->is_active ?? true,
            'settings' => $request->settings ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'data' => $branch
        ], 201);
    }

    /**
     * Update branch
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:10|unique:branches,code,' . $id,
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $branch->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => $branch->fresh()
        ]);
    }

    /**
     * Delete branch
     */
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Check for dependencies
        if ($branch->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch with active users'
            ], 422);
        }

        if ($branch->orders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete branch with orders. Consider deactivating instead.'
            ], 422);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully'
        ]);
    }

    /**
     * Get branch statistics
     */
    public function stats($id)
    {
        $branch = Branch::findOrFail($id);
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Today's stats
        $todaySales = Invoice::where('branch_id', $id)
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('total_amount');

        $todayOrders = Order::where('branch_id', $id)
            ->whereDate('created_at', $today)
            ->count();

        // This month stats
        $monthSales = Invoice::where('branch_id', $id)
            ->where('created_at', '>=', $thisMonth)
            ->where('status', 'paid')
            ->sum('total_amount');

        $monthOrders = Order::where('branch_id', $id)
            ->where('created_at', '>=', $thisMonth)
            ->count();

        // Average order value
        $avgOrderValue = $todayOrders > 0 ? $todaySales / $todayOrders : 0;

        // Payment method breakdown
        $paymentMethods = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.branch_id', $id)
            ->whereDate('payments.created_at', $today)
            ->selectRaw('payments.method, SUM(payments.amount) as total')
            ->groupBy('payments.method')
            ->get();

        // Top selling items today
        $topItems = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->where('orders.branch_id', $id)
            ->whereDate('orders.created_at', $today)
            ->selectRaw('menu_items.name, SUM(order_items.quantity) as qty, SUM(order_items.total_amount) as total')
            ->groupBy('menu_items.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'sales' => $todaySales,
                    'orders' => $todayOrders,
                    'avg_order' => round($avgOrderValue, 2),
                ],
                'month' => [
                    'sales' => $monthSales,
                    'orders' => $monthOrders,
                ],
                'payment_methods' => $paymentMethods,
                'top_items' => $topItems,
            ]
        ]);
    }
}
