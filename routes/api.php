<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderController;

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/kitchen/queue-stream', [KitchenController::class, 'streamQueue']);

// Menu routes (public)
Route::get('/menu/categories', [MenuController::class, 'getCategories']);
Route::get('/menu/categories/{id}/foods', [MenuController::class, 'getCategoryFoods']);
Route::get('/menu/foods', [MenuController::class, 'getFoods']);
Route::get('/menu/foods/{id}', [MenuController::class, 'getFoodDetails']);
Route::get('/menu', [MenuController::class, 'getAllMenus']);

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Menu routes
    Route::post('/menu/foods', [MenuController::class, 'createFood']);
    Route::post('/menu/foods/{id}', [MenuController::class, 'updateFood']);
    Route::put('/menu/foods/{id}', [MenuController::class, 'updateFood']);
    Route::delete('/menu/foods/{id}', [MenuController::class, 'deleteFood']);
    Route::post('/menu/categories', [MenuController::class, 'createCategory']);
    Route::put('/menu/categories/{id}', [MenuController::class, 'updateCategory']);
    Route::delete('/menu/categories/{id}', [MenuController::class, 'deleteCategory']);
    Route::get('/menu/recipes', [MenuController::class, 'getRecipes']);
    Route::post('/menu/recipes', [MenuController::class, 'createRecipe']);

    // Inventory routes
    Route::get('/inventory', [InventoryController::class, 'getInventory']);
    Route::get('/inventory/low-stock', [InventoryController::class, 'getLowStockItems']);
    Route::get('/inventory/{id}', [InventoryController::class, 'getIngredientDetails']);
    Route::post('/inventory/{id}/stock', [InventoryController::class, 'updateStock']);
    Route::get('/inventory/{id}/logs', [InventoryController::class, 'getInventoryLogs']);
    Route::post('/inventory/ingredients', [InventoryController::class, 'createIngredient']);
    Route::put('/inventory/ingredients/{id}', [InventoryController::class, 'updateIngredient']);
    Route::delete('/inventory/ingredients/{id}', [InventoryController::class, 'deleteIngredient']);

    // Kitchen routes
    Route::get('/kitchen/queue', [KitchenController::class, 'getQueue']);
    Route::get('/kitchen/queue/{id}', [KitchenController::class, 'getOrderDetails']);
    Route::put('/kitchen/orders/{id}/status', [KitchenController::class, 'updateOrderStatus']);
    Route::get('/kitchen/ready-orders', [KitchenController::class, 'getReadyOrders']);
    Route::put('/kitchen/orders/{id}/complete', [KitchenController::class, 'completeOrder']);
    Route::post('/kitchen/orders/{id}/print', [KitchenController::class, 'printOrder']);

    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/stats/summary', [OrderController::class, 'getStats']);
    Route::get('/orders/table/{tableId}', [OrderController::class, 'getByTable']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/request-payment', [OrderController::class, 'requestPayment']);
    Route::delete('/orders/{id}', [OrderController::class, 'cancel']);

    // Employee routes
    Route::get('/employees', [EmployeeController::class, 'getEmployees']);
    Route::get('/employees/{id}', [EmployeeController::class, 'getEmployeeDetails']);
    Route::post('/employees', [EmployeeController::class, 'createEmployee']);
    Route::put('/employees/{id}', [EmployeeController::class, 'updateEmployee']);
    Route::get('/employees/{id}/kpi', [EmployeeController::class, 'getKPIData']);
    Route::put('/employees/{id}/status', [EmployeeController::class, 'updateEmployeeStatus']);
    Route::get('/departments', [EmployeeController::class, 'getDepartments']);
    Route::get('/departments/{id}/employees', [EmployeeController::class, 'getEmployeesByDepartment']);

    // Payment routes
    Route::get('/payments', [PaymentController::class, 'getPayments']);
    Route::post('/payments', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{id}', [PaymentController::class, 'getPaymentDetails']);
    Route::get('/orders/{id}/payments', [PaymentController::class, 'getOrderPayments']);
    Route::post('/orders/{id}/invoice', [PaymentController::class, 'generateInvoice']);
    Route::get('/invoices', [PaymentController::class, 'getInvoices']);
    Route::get('/invoices/current', [PaymentController::class, 'getCurrentBills']);
    Route::get('/invoices/{id}', [PaymentController::class, 'getInvoice']);
    Route::post('/orders/{id}/coupon', [PaymentController::class, 'applyCoupon']);
    Route::post('/coupons/validate', [PaymentController::class, 'validateCoupon']);

    // Analytics routes
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboardData']);
    Route::get('/analytics/revenue', [AnalyticsController::class, 'getRevenueData']);
    Route::get('/analytics/top-dishes', [AnalyticsController::class, 'getTopDishes']);
    Route::get('/analytics/orders', [AnalyticsController::class, 'getOrderAnalytics']);
    Route::get('/analytics/inventory', [AnalyticsController::class, 'getInventoryAnalytics']);
    Route::get('/analytics/employees', [AnalyticsController::class, 'getEmployeePerformance']);
    Route::post('/analytics/reports', [AnalyticsController::class, 'getSalesReport']);
    Route::get('/analytics/payment-breakdown', [AnalyticsController::class, 'getPaymentMethodBreakdown']);
    Route::get('/analytics/customers', [AnalyticsController::class, 'getCustomerMetrics']);

    // Table routes
    Route::get('/tables', [TableController::class, 'getAllTables']);
    Route::post('/tables', [TableController::class, 'createTable']);
    Route::get('/tables/{id}', [TableController::class, 'getTableDetails']);
    Route::put('/tables/{id}', [TableController::class, 'updateTable']);
    Route::delete('/tables/{id}', [TableController::class, 'deleteTable']);
    Route::put('/tables/{id}/status', [TableController::class, 'updateTableStatus']);
    Route::post('/tables/{id}/assign', [TableController::class, 'assignTable']);
    Route::post('/tables/{id}/release', [TableController::class, 'releaseTable']);
    Route::post('/reservations', [TableController::class, 'createReservation']);
    Route::get('/reservations', [TableController::class, 'getReservations']);
    Route::post('/tables/merge', [TableController::class, 'mergeTables']);
    Route::post('/tables/merge/{id}/unmerge', [TableController::class, 'unmergeTables']);
});
