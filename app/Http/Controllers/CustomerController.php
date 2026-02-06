<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    /**
     * Get all customers
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Exclude walk-in customer
        $query->where('is_walkin', false);

        $customers = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        ]);
    }

    /**
     * Search customers
     */
    public function search(Request $request)
    {
        $query = $request->get('q') ?? $request->get('phone') ?? '';
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $customers = Customer::where('is_walkin', false)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Get walk-in customer
     */
    public function walkin(Request $request)
    {
        $branchId = $request->user()->branch_id;

        $walkin = Customer::where('is_walkin', true)
            ->where('branch_id', $branchId)
            ->first();

        if (!$walkin) {
            // Create walk-in customer if doesn't exist
            $walkin = Customer::create([
                'code' => 'WALKIN',
                'name' => 'Walk-in Customer',
                'branch_id' => $branchId,
                'is_walkin' => true,
                'loyalty_points' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $walkin
        ]);
    }

    /**
     * Get single customer
     */
    public function show($id)
    {
        $customer = Customer::with(['orders', 'invoices'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Create customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers',
            'email' => 'nullable|email|unique:customers',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'birthday' => 'nullable|date',
        ]);

        $data = $request->all();
        
        // Generate customer code
        $data['code'] = 'CUST-' . strtoupper(Str::random(6));
        
        // Set branch
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        // Initialize loyalty points
        $data['loyalty_points'] = 0;
        $data['total_spent'] = 0;
        $data['total_orders'] = 0;
        $data['is_walkin'] = false;

        $customer = Customer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    /**
     * Update customer
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $id,
            'email' => 'nullable|email|unique:customers,email,' . $id,
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'birthday' => 'nullable|date',
        ]);

        $customer->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer->fresh()
        ]);
    }

    /**
     * Delete customer
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        // Don't allow deleting walk-in
        if ($customer->is_walkin) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete walk-in customer'
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Get customer orders
     */
    public function orders($id)
    {
        $customer = Customer::findOrFail($id);
        $orders = $customer->orders()
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        if ($customer->loyalty_points < $request->points) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient loyalty points'
            ], 422);
        }

        $customer->loyalty_points -= $request->points;
        $customer->save();

        // TODO: Create loyalty transaction record

        return response()->json([
            'success' => true,
            'message' => 'Points redeemed successfully',
            'data' => $customer
        ]);
    }
}