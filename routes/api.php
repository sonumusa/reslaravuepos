<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ModifierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\PosSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;

// ═══════════════════════════════════════════════════════
// PUBLIC ROUTES
// ═══════════════════════════════════════════════════════
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/login-pin', [AuthController::class, 'loginPin']);

// ═══════════════════════════════════════════════════════
// PROTECTED ROUTES
// ═══════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {
    
    // ─────────────────────────────────────────────────────
    // AUTH
    // ─────────────────────────────────────────────────────
    Route::get('auth/user', [AuthController::class, 'user']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('auth/change-pin', [AuthController::class, 'changePin']);

    // ─────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────
    Route::get('admin/dashboard', [AdminController::class, 'dashboard']);

    // ─────────────────────────────────────────────────────
    // CATEGORIES
    // ─────────────────────────────────────────────────────
    Route::apiResource('categories', CategoryController::class);

    // ─────────────────────────────────────────────────────
    // INVENTORY
    // ─────────────────────────────────────────────────────
    Route::apiResource('inventory', InventoryController::class);
    Route::post('inventory/{id}/adjust', [InventoryController::class, 'adjust']);
    
    // ─────────────────────────────────────────────────────
    // EXPENSES
    // ─────────────────────────────────────────────────────
    Route::get('expenses/categories', [ExpenseController::class, 'categories']);
    Route::get('expenses/summary', [ExpenseController::class, 'summary']);
    Route::apiResource('expenses', ExpenseController::class);
    
    // ─────────────────────────────────────────────────────
    // USERS/STAFF
    // ─────────────────────────────────────────────────────
    Route::apiResource('users', UserController::class);
    
    // ─────────────────────────────────────────────────────
    // REPORTS
    // ─────────────────────────────────────────────────────
    Route::get('reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('reports/hourly-sales', [ReportController::class, 'hourlySales']);
    Route::get('reports/items-sold', [ReportController::class, 'itemsSold']);
    Route::get('reports/payment-methods', [ReportController::class, 'paymentMethods']);
    Route::get('reports/staff-performance', [ReportController::class, 'staffPerformance']);

    // ─────────────────────────────────────────────────────
    // MENU ITEMS
    // ─────────────────────────────────────────────────────
    Route::apiResource('menu-items', MenuController::class);
    Route::post('menu-items/{menuItem}/toggle-availability', [MenuController::class, 'toggleAvailability']);
    Route::post('menu-items/barcode', [MenuController::class, 'findByBarcode']);
    Route::post('menu-items/bulk-update-prices', [MenuController::class, 'bulkUpdatePrices']);

    // ─────────────────────────────────────────────────────
    // MODIFIERS
    // ─────────────────────────────────────────────────────
    Route::apiResource('modifiers', ModifierController::class);
    Route::get('modifiers/groups', [ModifierController::class, 'groups']);

    // ─────────────────────────────────────────────────────
    // DAILY SPECIALS
    // ─────────────────────────────────────────────────────
    Route::get('daily-specials', [MenuController::class, 'dailySpecials']);
    Route::post('daily-specials', [MenuController::class, 'createDailySpecial']);
    Route::put('daily-specials/{id}', [MenuController::class, 'updateDailySpecial']);
    Route::delete('daily-specials/{id}', [MenuController::class, 'deleteDailySpecial']);

    // ─────────────────────────────────────────────────────
    // ORDERS - Main CRUD
    // ─────────────────────────────────────────────────────
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/completed', [OrderController::class, 'completed']);
    Route::get('orders/held', [OrderController::class, 'held']);
    Route::get('orders/table/{tableId}', [OrderController::class, 'byTable']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}', [OrderController::class, 'update']);
    Route::delete('orders/{order}', [OrderController::class, 'destroy']);

    // ─────────────────────────────────────────────────────
    // ORDERS - Items Management
    // ─────────────────────────────────────────────────────
    Route::post('orders/{order}/items', [OrderController::class, 'addItem']);
    Route::put('orders/{order}/items/{item}', [OrderController::class, 'updateItem']);
    Route::delete('orders/{order}/items/{item}', [OrderController::class, 'removeItem']);

    // ─────────────────────────────────────────────────────
    // ORDERS - Actions
    // ─────────────────────────────────────────────────────
    Route::post('orders/{order}/send-kitchen', [OrderController::class, 'sendToKitchen']);
    Route::post('orders/{order}/hold', [OrderController::class, 'hold']);
    Route::post('orders/{order}/resume', [OrderController::class, 'resume']);
    Route::post('orders/{order}/ready', [OrderController::class, 'ready']);
    Route::post('orders/{order}/complete', [OrderController::class, 'complete']);
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('orders/{order}/void', [OrderController::class, 'void']);

    // ─────────────────────────────────────────────────────
    // TABLES
    // ─────────────────────────────────────────────────────
    Route::apiResource('tables', TableController::class);
    Route::get('tables/floors', [TableController::class, 'floors']);
    Route::put('tables/{table}/status', [TableController::class, 'updateStatus']);
    Route::post('tables/transfer', [TableController::class, 'transfer']);
    Route::post('tables/merge', [TableController::class, 'merge']);

    // ─────────────────────────────────────────────────────
    // BRANCHES
    // ─────────────────────────────────────────────────────
    Route::apiResource('branches', BranchController::class);
    Route::get('branches/{branch}/stats', [BranchController::class, 'stats']);

    // ─────────────────────────────────────────────────────
    // POS SESSIONS
    // ─────────────────────────────────────────────────────
    Route::get('pos-sessions', [PosSessionController::class, 'index']);
    Route::get('pos-sessions/current', [PosSessionController::class, 'current']);
    Route::get('pos-sessions/{session}', [PosSessionController::class, 'show']);
    Route::get('pos-sessions/{session}/summary', [PosSessionController::class, 'summary']);
    Route::post('pos-sessions/open', [PosSessionController::class, 'open']);
    Route::post('pos-sessions/close', [PosSessionController::class, 'close']);
    Route::post('pos-sessions/suspend', [PosSessionController::class, 'suspend']);
    Route::post('pos-sessions/resume', [PosSessionController::class, 'resume']);
    Route::post('pos-sessions/cash-drop', [PosSessionController::class, 'cashDrop']);
    Route::get('pos-sessions/x-report', [PosSessionController::class, 'xReport']);
    Route::get('pos-sessions/z-report', [PosSessionController::class, 'zReport']);

    // ─────────────────────────────────────────────────────
    // CUSTOMERS
    // ─────────────────────────────────────────────────────
    Route::get('customers/search', [CustomerController::class, 'search']);
    Route::get('customers/walkin', [CustomerController::class, 'walkin']);
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/{customer}/orders', [CustomerController::class, 'orders']);
    Route::post('customers/{customer}/redeem-points', [CustomerController::class, 'redeemPoints']);
});