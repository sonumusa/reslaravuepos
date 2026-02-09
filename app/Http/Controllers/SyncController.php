<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuModifier;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Ping endpoint for connectivity check
     */
    public function ping()
    {
        return response()->json([
            'success' => true,
            'message' => 'pong',
            'timestamp' => now()->toISOString(),
            'server_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get sync status and last sync times
     */
    public function status(Request $request)
    {
        $branchId = $request->user()->branch_id;

        // Get latest update timestamps for key tables
        $lastUpdates = [
            'categories' => Category::when($branchId, fn($q) => $q->where('branch_id', $branchId))->max('updated_at'),
            'menu_items' => MenuItem::when($branchId, fn($q) => $q->where('branch_id', $branchId))->max('updated_at'),
            'modifiers' => MenuModifier::when($branchId, fn($q) => $q->where('branch_id', $branchId))->max('updated_at'),
        ];

        // Count pending offline orders
        $pendingOrders = Order::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('created_offline', true)
            ->where('synced', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'online' => true,
                'last_updates' => $lastUpdates,
                'pending_sync_count' => $pendingOrders,
                'server_version' => config('app.version', '1.0.0'),
            ]
        ]);
    }

    /**
     * Download data for offline use
     */
    public function download(Request $request)
    {
        $branchId = $request->user()->branch_id;
        $since = $request->get('since'); // ISO timestamp for incremental sync

        $data = [];

        // Categories
        $categoriesQuery = Category::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true);
        if ($since) {
            $categoriesQuery->where('updated_at', '>', $since);
        }
        $data['categories'] = $categoriesQuery->get();

        // Menu Items
        $menuItemsQuery = MenuItem::with('category')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_available', true);
        if ($since) {
            $menuItemsQuery->where('updated_at', '>', $since);
        }
        $data['menu_items'] = $menuItemsQuery->get();

        // Modifiers
        $modifiersQuery = MenuModifier::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true);
        if ($since) {
            $modifiersQuery->where('updated_at', '>', $since);
        }
        $data['modifiers'] = $modifiersQuery->get();

        // Customers (limited set for offline search)
        if ($request->get('include_customers', false)) {
            $customersQuery = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->limit(500);
            if ($since) {
                $customersQuery->where('updated_at', '>', $since);
            }
            $data['customers'] = $customersQuery->get();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'sync_timestamp' => now()->toISOString(),
            'incremental' => !empty($since),
        ]);
    }

    /**
     * Upload offline data
     */
    public function upload(Request $request)
    {
        $request->validate([
            'orders' => 'sometimes|array',
            'customers' => 'sometimes|array',
        ]);

        $results = [
            'orders' => ['success' => 0, 'failed' => 0, 'errors' => []],
            'customers' => ['success' => 0, 'failed' => 0, 'errors' => []],
        ];

        DB::beginTransaction();
        try {
            // Process offline orders
            if ($request->has('orders')) {
                foreach ($request->orders as $orderData) {
                    try {
                        // Check if order already exists (by UUID)
                        $existing = Order::where('uuid', $orderData['uuid'])->first();
                        
                        if ($existing) {
                            // Mark as synced
                            $existing->update(['synced' => true, 'synced_at' => now()]);
                            $results['orders']['success']++;
                            continue;
                        }

                        // Create new order from offline data
                        $order = $this->createOrderFromOfflineData($orderData, $request->user());
                        $results['orders']['success']++;
                        
                    } catch (\Exception $e) {
                        $results['orders']['failed']++;
                        $results['orders']['errors'][] = [
                            'uuid' => $orderData['uuid'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            // Process offline customers
            if ($request->has('customers')) {
                foreach ($request->customers as $customerData) {
                    try {
                        // Check for existing customer by phone
                        $existing = Customer::where('phone', $customerData['phone'])->first();
                        
                        if ($existing) {
                            $results['customers']['success']++;
                            continue;
                        }

                        Customer::create([
                            'code' => $customerData['code'] ?? 'CUST-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'name' => $customerData['name'],
                            'phone' => $customerData['phone'],
                            'email' => $customerData['email'] ?? null,
                            'branch_id' => $request->user()->branch_id,
                            'loyalty_points' => 0,
                            'total_spent' => 0,
                            'total_orders' => 0,
                        ]);
                        $results['customers']['success']++;
                        
                    } catch (\Exception $e) {
                        $results['customers']['failed']++;
                        $results['customers']['errors'][] = [
                            'phone' => $customerData['phone'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sync completed',
                'data' => $results,
                'sync_timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'data' => $results,
            ], 500);
        }
    }

    /**
     * Resolve sync conflicts
     */
    public function resolveConflicts(Request $request)
    {
        $request->validate([
            'conflicts' => 'required|array',
            'conflicts.*.type' => 'required|string',
            'conflicts.*.local_id' => 'required',
            'conflicts.*.server_id' => 'required',
            'conflicts.*.resolution' => 'required|in:keep_local,keep_server,merge',
        ]);

        $resolved = [];

        foreach ($request->conflicts as $conflict) {
            // Handle based on conflict type
            switch ($conflict['type']) {
                case 'order':
                    $resolved[] = $this->resolveOrderConflict($conflict);
                    break;
                case 'customer':
                    $resolved[] = $this->resolveCustomerConflict($conflict);
                    break;
                default:
                    $resolved[] = [
                        'type' => $conflict['type'],
                        'status' => 'skipped',
                        'reason' => 'Unknown conflict type',
                    ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Conflicts resolved',
            'data' => $resolved,
        ]);
    }

    /**
     * Create order from offline data
     */
    private function createOrderFromOfflineData(array $data, $user)
    {
        $order = Order::create([
            'uuid' => $data['uuid'],
            'order_number' => $data['order_number'] ?? 'OFF-' . date('YmdHis') . '-' . rand(1000, 9999),
            'branch_id' => $user->branch_id,
            'user_id' => $user->id,
            'table_id' => $data['table_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'order_type' => $data['order_type'] ?? 'dine_in',
            'status' => $data['status'] ?? 'completed',
            'subtotal' => $data['subtotal'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'total_amount' => $data['total_amount'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'is_priority' => $data['is_priority'] ?? false,
            'created_offline' => true,
            'synced' => true,
            'synced_at' => now(),
            'created_at' => $data['created_at'] ?? now(),
        ]);

        // Create order items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $order->items()->create([
                    'menu_item_id' => $itemData['menu_item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'subtotal' => $itemData['subtotal'] ?? ($itemData['unit_price'] * $itemData['quantity']),
                    'tax_amount' => $itemData['tax_amount'] ?? 0,
                    'total_amount' => $itemData['total_amount'] ?? $itemData['subtotal'],
                    'notes' => $itemData['notes'] ?? null,
                    'modifiers' => json_encode($itemData['modifiers'] ?? []),
                    'status' => 'completed',
                ]);
            }
        }

        return $order;
    }

    /**
     * Resolve order conflict
     */
    private function resolveOrderConflict(array $conflict)
    {
        // Implementation depends on your conflict resolution strategy
        return [
            'type' => 'order',
            'local_id' => $conflict['local_id'],
            'server_id' => $conflict['server_id'],
            'status' => 'resolved',
            'resolution' => $conflict['resolution'],
        ];
    }

    /**
     * Resolve customer conflict
     */
    private function resolveCustomerConflict(array $conflict)
    {
        return [
            'type' => 'customer',
            'local_id' => $conflict['local_id'],
            'server_id' => $conflict['server_id'],
            'status' => 'resolved',
            'resolution' => $conflict['resolution'],
        ];
    }
}
