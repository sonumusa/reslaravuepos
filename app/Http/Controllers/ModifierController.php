<?php

namespace App\Http\Controllers;

use App\Models\MenuModifier;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    /**
     * Get all modifiers
     */
    public function index(Request $request)
    {
        $query = MenuModifier::query();

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by group
        if ($request->has('group_name')) {
            $query->where('group_name', $request->group_name);
        }

        $modifiers = $query->orderBy('group_name')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $modifiers
        ]);
    }

    /**
     * Get modifier groups
     */
    public function groups(Request $request)
    {
        $groups = MenuModifier::select('group_name')
            ->distinct()
            ->pluck('group_name');

        return response()->json([
            'success' => true,
            'data' => $groups
        ]);
    }

    /**
     * Get single modifier
     */
    public function show($id)
    {
        $modifier = MenuModifier::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $modifier
        ]);
    }

    /**
     * Create modifier
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'group_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        
        // Set branch
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $modifier = MenuModifier::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Modifier created successfully',
            'data' => $modifier
        ], 201);
    }

    /**
     * Update modifier
     */
    public function update(Request $request, $id)
    {
        $modifier = MenuModifier::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'group_name' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $modifier->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Modifier updated successfully',
            'data' => $modifier->fresh()
        ]);
    }

    /**
     * Delete modifier
     */
    public function destroy($id)
    {
        $modifier = MenuModifier::findOrFail($id);
        $modifier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Modifier deleted successfully'
        ]);
    }
}