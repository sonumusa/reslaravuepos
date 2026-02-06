<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('branch');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show($id)
    {
        $user = User::with('branch')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:superadmin,admin,cashier,waiter,kitchen',
            'branch_id' => 'required|exists:branches,id',
            'pin' => 'nullable|digits:4|unique:users',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user->load('branch')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:superadmin,admin,cashier,waiter,kitchen',
            'pin' => 'nullable|digits:4|unique:users,pin,' . $id,
        ]);

        $data = $request->except(['password']);
        
        if ($request->has('password') && $request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->fresh(['branch'])
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete superadmin user'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}