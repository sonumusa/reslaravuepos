<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $inventory = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    public function show($id)
    {
        $item = InventoryItem::findOrFail($id);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'sku' => 'required|string|unique:inventory_items',
            'unit' => 'required|string',
            'stock_quantity' => 'required|numeric|min:0',
            'min_stock_level' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $item = InventoryItem::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string',
            'sku' => 'sometimes|string|unique:inventory_items,sku,' . $id,
            'stock_quantity' => 'sometimes|numeric|min:0',
            'min_stock_level' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        $item->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated',
            'data' => $item
        ]);
    }

    public function destroy($id)
    {
        InventoryItem::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Inventory item deleted']);
    }

    public function adjust(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric',
            'type' => 'required|in:add,subtract,set',
            'reason' => 'nullable|string',
        ]);

        $item = InventoryItem::findOrFail($id);

        switch ($request->type) {
            case 'add':
                $item->stock_quantity += $request->quantity;
                break;
            case 'subtract':
                $item->stock_quantity -= $request->quantity;
                break;
            case 'set':
                $item->stock_quantity = $request->quantity;
                break;
        }

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted',
            'data' => $item
        ]);
    }
}