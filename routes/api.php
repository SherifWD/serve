<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\Mobile\CouponController;
use App\Http\Controllers\Api\Mobile\KDSController;
use App\Http\Controllers\Api\Mobile\OrderMobileController;
use App\Http\Controllers\Api\Mobile\TableMobileController;
use App\Http\Controllers\Api\Mobile\MobileProductController;
use App\Http\Controllers\Api\Mobile\ModifierController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OwnerDashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('menus', MenuController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::apiResource('inventory-transactions', InventoryTransactionController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('devices', DeviceController::class);
    Route::apiResource('tables', TableController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
});
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
Route::middleware('auth:sanctum')->get('/dashboard/summary', [OwnerDashboardController::class, 'summary']);
Route::get('employees/{id}/performance', [EmployeeController::class, 'performance']);
Route::get('ingredients', [IngredientController::class, 'index']);
Route::post('ingredients', [IngredientController::class, 'store']);
Route::get('ingredients/{id}', [IngredientController::class, 'show']);
Route::put('ingredients/{id}', [IngredientController::class, 'update']);
Route::delete('ingredients/{id}', [IngredientController::class, 'destroy']);
Route::post('ingredients/update-stock', [IngredientController::class, 'updateStock']);
Route::apiResource('recipes', RecipeController::class);




//Mobile
// routes/api.php
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    Route::get('tables', [TableMobileController::class, 'index']);
    Route::get('tables/{id}', [TableMobileController::class, 'show']);

    Route::get('orders', [OrderMobileController::class, 'index']);
    Route::get('orders/{id}', [OrderMobileController::class, 'show']);
Route::get('products', [MobileProductController::class, 'index']);
Route::post('orders', [OrderMobileController::class, 'store']);
Route::patch('tables/{fromTable}/move', [TableMobileController::class, 'moveTable']);
Route::patch('{order}/send-to-cashier', [TableMobileController::class, 'sendToCashier']);
Route::put('orders/{order}', [OrderMobileController::class, 'update']);
Route::patch('orders/{order}/reopen', [TableMobileController::class, 'reopenOrder']);
Route::post('/coupons/verify', [CouponController::class, 'verify']);
Route::post('/orders/{id}/pay', [OrderMobileController::class, 'pay']);
Route::get('/orders/{id}/receipt', [OrderMobileController::class, 'receipt']);
Route::patch('order-items/{id}/refund-change', [TableMobileController::class, 'refundOrChangeItem']);
Route::get('order-items/{id}/history', [TableMobileController::class, 'itemHistory']);
// routes/api.php
Route::get('/modifiers/available', [ModifierController::class, 'availableForWaiter']);





Route::prefix('kds')->group(function () {
Route::get('/orders', [KDSController::class, 'getActiveOrders']);
    Route::patch('/orders/{order}', [KDSController::class, 'setOrderStatus']);
    Route::patch('/order-items/{item}', [KDSController::class, 'setOrderItemStatus']);
});
});
