<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login with email/password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'sometimes|string',
        ]);

        $user = User::with('branch')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke old tokens (optional - for single device login)
        // $user->tokens()->delete();

        $token = $user->createToken($request->device_name ?? 'pos_terminal')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
                'branch' => $user->branch,
                'permissions' => $this->getUserPermissions($user),
            ]
        ]);
    }

    /**
     * Login with PIN
     */
    public function loginPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string',
            'device_name' => 'sometimes|string',
        ]);

        $user = User::with('branch')->where('pin', $request->pin)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'pin' => ['Invalid PIN.'],
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'pos_terminal')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'PIN login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
                'branch' => $user->branch,
                'permissions' => $this->getUserPermissions($user),
            ]
        ]);
    }

    /**
     * Logout current session
     */
    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout all sessions
     */
    public function logoutAll(Request $request)
    {
        // Revoke all tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices'
        ]);
    }

    /**
     * Get current user (matches frontend /auth/user call)
     */
    public function user(Request $request)
    {
        $user = $request->user()->load('branch');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'branch' => $user->branch,
                'permissions' => $this->getUserPermissions($user),
            ]
        ]);
    }

    /**
     * Get current user (alias for user)
     */
    public function me(Request $request)
    {
        return $this->user($request);
    }

    /**
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|max:20',
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Change PIN
     */
    public function changePin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required',
            'new_pin' => 'required|digits:4',
        ]);

        $user = $request->user();

        if ($user->pin !== $request->current_pin) {
            throw ValidationException::withMessages([
                'current_pin' => ['Current PIN is incorrect.'],
            ]);
        }

        $user->update(['pin' => $request->new_pin]);

        return response()->json([
            'success' => true,
            'message' => 'PIN changed successfully'
        ]);
    }

    /**
     * Get user permissions based on role
     */
    private function getUserPermissions(User $user): array
    {
        $rolePermissions = [
            'superadmin' => ['*'],
            'admin' => [
                'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
                'menu.view', 'menu.create', 'menu.edit', 'menu.delete',
                'customers.view', 'customers.create', 'customers.edit',
                'inventory.view', 'inventory.create', 'inventory.edit',
                'expenses.view', 'expenses.create', 'expenses.edit',
                'reports.view', 'staff.view', 'staff.create', 'staff.edit',
                'settings.view',
            ],
            'cashier' => [
                'orders.view', 'orders.create', 'orders.edit',
                'customers.view', 'customers.create',
                'payments.process',
            ],
            'waiter' => [
                'orders.view', 'orders.create', 'orders.edit',
                'tables.view', 'tables.update',
            ],
            'kitchen' => [
                'orders.view', 'kds.view', 'kds.update',
            ],
        ];

        return $rolePermissions[$user->role] ?? [];
    }
}