<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $query = Table::query();

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        $tables = $query->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    public function floors()
    {
        $floors = Table::select('floor')->distinct()->pluck('floor');
        return response()->json(['success' => true, 'data' => $floors]);
    }

    public function show($id)
    {
        $table = Table::findOrFail($id);
        return response()->json(['success' => true, 'data' => $table]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'floor' => 'required|string',
            'seats' => 'required|integer|min:1',
        ]);

        $data = $request->all();
        $data['status'] = 'available';
        $data['is_active'] = true;
        $data['position_x'] = $data['position_x'] ?? 0;
        $data['position_y'] = $data['position_y'] ?? 0;

        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }

        $table = Table::create($data);
        return response()->json(['success' => true, 'data' => $table], 201);
    }

    public function update(Request $request, $id)
    {
        $table = Table::findOrFail($id);
        $table->update($request->all());
        return response()->json(['success' => true, 'data' => $table]);
    }

    public function updateStatus(Request $request, $id)
    {
        $table = Table::findOrFail($id);
        $table->status = $request->status;
        $table->save();
        return response()->json(['success' => true, 'data' => $table]);
    }

    public function destroy($id)
    {
        Table::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function transfer(Request $request)
    {
        return response()->json(['success' => true]);
    }

    public function merge(Request $request)
    {
        return response()->json(['success' => true]);
    }
}