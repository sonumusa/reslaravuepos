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
        $previousQuantity = $item->stock_quantity;

        switch ($request->type) {
            case 'add':
                $item->stock_quantity += $request->quantity;
                break;
            case 'subtract':
                $item->stock_quantity = max(0, $item->stock_quantity - $request->quantity);
                break;
            case 'set':
                $item->stock_quantity = max(0, $request->quantity);
                break;
        }

        $item->save();

        // Log the stock movement
        $this->logStockMovement($item, $previousQuantity, $request->type, $request->quantity, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Stock adjusted',
            'data' => $item
        ]);
    }

    /**
     * Deduct stock for order items (called when order is completed)
     */
    public function deductForOrder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'order_id' => 'required|exists:orders,id',
        ]);

        $deducted = [];
        $errors = [];

        foreach ($request->items as $item) {
            try {
                // Find inventory items linked to this menu item
                $inventoryItem = InventoryItem::where('menu_item_id', $item['menu_item_id'])->first();
                
                if ($inventoryItem && $inventoryItem->track_stock) {
                    $previousQuantity = $inventoryItem->stock_quantity;
                    $inventoryItem->stock_quantity = max(0, $inventoryItem->stock_quantity - $item['quantity']);
                    $inventoryItem->save();

                    $this->logStockMovement(
                        $inventoryItem, 
                        $previousQuantity, 
                        'sale', 
                        $item['quantity'], 
                        'Order #' . $request->order_id
                    );

                    $deducted[] = [
                        'inventory_id' => $inventoryItem->id,
                        'menu_item_id' => $item['menu_item_id'],
                        'quantity' => $item['quantity'],
                        'remaining' => $inventoryItem->stock_quantity,
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'menu_item_id' => $item['menu_item_id'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($errors) === 0 ? 'Stock deducted successfully' : 'Some items failed',
            'data' => [
                'deducted' => $deducted,
                'errors' => $errors,
            ]
        ]);
    }

    /**
     * Log stock movement
     */
    private function logStockMovement($item, $previousQuantity, $type, $quantity, $reason)
    {
        // Log to stock_movements table if it exists
        try {
            \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                'inventory_item_id' => $item->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousQuantity,
                'new_stock' => $item->stock_quantity,
                'reason' => $reason,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log table might not exist, that's okay
            \Illuminate\Support\Facades\Log::warning('Stock movement logging failed: ' . $e->getMessage());
        }
    }
}