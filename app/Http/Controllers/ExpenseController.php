<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $expenses
        ]);
    }

    public function show($id)
    {
        $expense = Expense::with('category')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $expense]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'payment_method' => 'nullable|string',
        ]);

        $data = $request->all();
        if (!isset($data['branch_id']) && $request->user()->branch_id) {
            $data['branch_id'] = $request->user()->branch_id;
        }
        $data['created_by'] = $request->user()->id;

        $expense = Expense::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded',
            'data' => $expense->load('category')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $request->validate([
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
        ]);

        $expense->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Expense updated',
            'data' => $expense->load('category')
        ]);
    }

    public function destroy($id)
    {
        Expense::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Expense deleted']);
    }

    public function categories()
    {
        $categories = ExpenseCategory::all();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function summary(Request $request)
    {
        $query = Expense::query();

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('start_date')) {
            $query->whereDate('expense_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('expense_date', '<=', $request->end_date);
        }

        $total = $query->sum('amount');

        $byCategory = Expense::selectRaw('expense_category_id, SUM(amount) as total')
            ->groupBy('expense_category_id')
            ->with('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'by_category' => $byCategory
            ]
        ]);
    }
}