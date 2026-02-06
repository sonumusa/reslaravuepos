<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Only active if not admin
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        $categories = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Get single category
     */
    public function show($id)
    {
        $category = Category::with(['menuItems'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Create category
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->all();
        
        // Set branch
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        // Set sort order
        if (!isset($data['sort_order'])) {
            $maxOrder = Category::max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        }

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->fresh()
        ]);
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has items
        if ($category->menuItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with menu items'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->categories as $cat) {
            Category::where('id', $cat['id'])
                ->update(['sort_order' => $cat['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }
}