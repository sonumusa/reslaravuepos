<?php

namespace App\Http\Controllers;

use App\Models\MenuModifier;
use Illuminate\Http\Request;

class ModifierController extends Controller
{
    public function index(Request $request)
    {
        try {
            $modifiers = MenuModifier::all();
            
            return response()->json([
                'success' => true,
                'data' => $modifiers
            ]);
        } catch (\Exception $e) {
            // Return empty array if table doesn't exist or other error
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function show($id)
    {
        $modifier = MenuModifier::findOrFail($id);
        return response()->json(['success' => true, 'data' => $modifier]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'group_name' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $modifier = MenuModifier::create($request->all());
        return response()->json(['success' => true, 'data' => $modifier], 201);
    }

    public function update(Request $request, $id)
    {
        $modifier = MenuModifier::findOrFail($id);
        $modifier->update($request->all());
        return response()->json(['success' => true, 'data' => $modifier]);
    }

    public function destroy($id)
    {
        MenuModifier::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function groups()
    {
        try {
            $groups = MenuModifier::select('group_name')->distinct()->pluck('group_name');
            return response()->json(['success' => true, 'data' => $groups]);
        } catch (\Exception $e) {
            return response()->json(['success' => true, 'data' => []]);
        }
    }
}