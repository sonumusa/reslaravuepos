<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // Mock data for now, or real data if models exist
        $stats = [
            'todaySales' => 15000,
            'todayOrders' => 45,
            'salesChange' => 12,
            'ordersChange' => 5,
            'averageOrder' => 333,
            'avgChange' => 2,
            'praPending' => 0
        ];

        $topItems = MenuItem::limit(5)->get(); // Assuming MenuItem model exists
        $recentOrders = Order::latest()->limit(5)->get(); // Assuming Order model exists

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'topItems' => $topItems,
                'recentOrders' => $recentOrders,
                'activeSessions' => [],
                'salesChart' => ['labels' => [], 'values' => []],
                'orderTypes' => ['labels' => [], 'values' => []]
            ]
        ]);
    }
}
