<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /**
     * Get all menu items
     */
    public function index(Request $request)
    {
        $query = MenuItem::with(['category'])->where('is_available', true);

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Get single menu item
     */
    public function show($id)
    {
        $item = MenuItem::with(['category'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Create menu item
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|unique:menu_items',
            'barcode' => 'nullable|string',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();
        
        // Generate SKU if not provided
        if (!isset($data['sku'])) {
            $data['sku'] = 'ITEM-' . strtoupper(Str::random(8));
        }

        // Set branch if user has one
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $item = MenuItem::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Menu item created successfully',
            'data' => $item->load('category')
        ], 201);
    }

    /**
     * Update menu item
     */
    public function update(Request $request, $id)
    {
        $item = MenuItem::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'sku' => 'sometimes|string|unique:menu_items,sku,' . $id,
            'barcode' => 'nullable|string',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $item->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu item updated successfully',
            'data' => $item->fresh(['category'])
        ]);
    }

    /**
     * Delete menu item
     */
    public function destroy($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully'
        ]);
    }

    /**
     * Toggle availability
     */
    public function toggleAvailability($id)
    {
        $item = MenuItem::findOrFail($id);
        $item->is_available = !$item->is_available;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Availability updated',
            'data' => $item
        ]);
    }

    /**
     * Find by barcode
     */
    public function findByBarcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $item = MenuItem::where('barcode', $request->barcode)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * Bulk update prices
     */
    public function bulkUpdatePrices(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        foreach ($request->items as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->update(['price' => $itemData['price']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prices updated successfully'
        ]);
    }

    /**
     * Get daily specials
     */
    public function dailySpecials(Request $request)
    {
        // For now return empty - implement DailySpecial model if needed
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Create daily special
     */
    public function createDailySpecial(Request $request)
    {
        // Implement when DailySpecial model is ready
        return response()->json([
            'success' => true,
            'message' => 'Feature coming soon',
            'data' => []
        ]);
    }

    /**
     * Update daily special
     */
    public function updateDailySpecial(Request $request, $id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Feature coming soon'
        ]);
    }

    /**
     * Delete daily special
     */
    public function deleteDailySpecial($id)
    {
        return response()->json([
            'success' => true,
            'message' => 'Feature coming soon'
        ]);
    }
}