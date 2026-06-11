<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchOperationSettingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\Customer\CustomerAuthController;
use App\Http\Controllers\Api\Customer\CustomerPortalController;
use App\Http\Controllers\Api\DataExportController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EtaSubmissionController;
use App\Http\Controllers\Api\FiscalProfileController;
use App\Http\Controllers\Api\FiscalReceiptController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\InventoryOperationController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\MarketingInquiryController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\Mobile\CouponController;
use App\Http\Controllers\Api\Mobile\KDSController;
use App\Http\Controllers\Api\Mobile\MobileProductController;
use App\Http\Controllers\Api\Mobile\MobileSyncController;
use App\Http\Controllers\Api\Mobile\ModifierController;
use App\Http\Controllers\Api\Mobile\OrderMobileController;
use App\Http\Controllers\Api\Mobile\TableMobileController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OwnerDashboardController;
use App\Http\Controllers\Api\PaymentAttemptController;
use App\Http\Controllers\Api\PaymentProviderController;
use App\Http\Controllers\Api\PrintJobController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\RestaurantController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\EnsureApiPermission;
use App\Http\Middleware\EnsureClientMutationIdempotency;
use Illuminate\Support\Facades\Route;

Route::get('/health', [SupportController::class, 'health']);
Route::middleware(['auth:sanctum', EnsureApiPermission::class])->group(function () {
    Route::apiResource('restaurants', RestaurantController::class);
    Route::apiResource('branches', BranchController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('menus', MenuController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('customers', CustomerController::class)->only(['index', 'show']);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('inventory-items', InventoryItemController::class);
    Route::apiResource('inventory-transactions', InventoryTransactionController::class);
    Route::prefix('inventory-operations')->group(function () {
        Route::post('purchase-receipts', [InventoryOperationController::class, 'receivePurchase']);
        Route::post('transfers', [InventoryOperationController::class, 'transfer']);
        Route::post('stock-counts', [InventoryOperationController::class, 'stockCount']);
        Route::post('adjustments', [InventoryOperationController::class, 'adjust']);
        Route::post('wastage', [InventoryOperationController::class, 'wastage']);
    });
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('devices', DeviceController::class);
    Route::get('branch-operation-settings', [BranchOperationSettingController::class, 'index']);
    Route::post('branch-operation-settings/{branch}/discover-devices', [BranchOperationSettingController::class, 'discover']);
    Route::put('branch-operation-settings/{branch}', [BranchOperationSettingController::class, 'update']);
    Route::get('payment-providers', [PaymentProviderController::class, 'index']);
    Route::post('payment-providers', [PaymentProviderController::class, 'store']);
    Route::get('payment-providers/{paymentProvider}', [PaymentProviderController::class, 'show']);
    Route::patch('payment-providers/{paymentProvider}', [PaymentProviderController::class, 'update']);
    Route::delete('payment-providers/{paymentProvider}', [PaymentProviderController::class, 'destroy']);
    Route::get('print-jobs', [PrintJobController::class, 'index']);
    Route::post('print-jobs', [PrintJobController::class, 'store']);
    Route::get('print-jobs/{printJob}', [PrintJobController::class, 'show']);
    Route::patch('print-jobs/{printJob}', [PrintJobController::class, 'update']);
    Route::apiResource('marketing-inquiries', MarketingInquiryController::class)
        ->only(['index', 'show', 'update'])
        ->parameters(['marketing-inquiries' => 'marketingInquiry']);
    Route::apiResource('tables', TableController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);
    Route::get('fiscal-profiles/effective', [FiscalProfileController::class, 'effective']);
    Route::apiResource('fiscal-profiles', FiscalProfileController::class);
    Route::get('receipts/{receipt}/fiscal-export', [FiscalReceiptController::class, 'show']);
    Route::post('receipts/{receipt}/eta-submissions', [EtaSubmissionController::class, 'store']);
    Route::get('eta-submissions/{etaReceiptSubmission}', [EtaSubmissionController::class, 'show']);
    Route::get('data-exports/{dataset}', [DataExportController::class, 'show']);
    Route::prefix('billing')->group(function () {
        Route::get('plans', [BillingController::class, 'plans']);
        Route::get('subscription', [BillingController::class, 'subscription']);
        Route::post('subscription', [BillingController::class, 'subscribe']);
        Route::get('invoices', [BillingController::class, 'invoices']);
        Route::post('invoices/{invoice}/mark-paid', [BillingController::class, 'markInvoicePaid']);
    });
    Route::get('support/ops', [SupportController::class, 'ops']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/support/context', [SupportController::class, 'context']);
});
Route::middleware('auth:sanctum')->post('/onboarding/restaurants', [OnboardingController::class, 'storeRestaurant']);
Route::middleware(['auth:sanctum', EnsureApiPermission::class])->get('/dashboard/summary', [OwnerDashboardController::class, 'summary']);
Route::middleware(['auth:sanctum', EnsureApiPermission::class])->get('/dashboard/receipt', [OwnerDashboardController::class, 'receipt']);
Route::middleware(['auth:sanctum', EnsureApiPermission::class])->group(function () {
    Route::get('employees/{id}/performance', [EmployeeController::class, 'performance']);
    Route::apiResource('ingredients', IngredientController::class);
    Route::post('ingredients/update-stock', [IngredientController::class, 'updateStock']);
    Route::apiResource('recipes', RecipeController::class);
});

Route::prefix('customer')->group(function () {
    Route::post('auth/request-otp', [CustomerAuthController::class, 'requestOtp']);
    Route::post('auth/verify-otp', [CustomerAuthController::class, 'verifyOtp']);
    Route::post('auth/login', [CustomerAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [CustomerAuthController::class, 'logout']);
        Route::get('me', [CustomerAuthController::class, 'me']);
        Route::get('home', [CustomerPortalController::class, 'home']);
        Route::get('restaurants', [CustomerPortalController::class, 'restaurants']);
        Route::get('restaurants/{restaurant}', [CustomerPortalController::class, 'restaurant']);
        Route::get('orders', [CustomerPortalController::class, 'orders']);
        Route::post('orders', [CustomerPortalController::class, 'checkout']);
        Route::get('orders/{order}', [CustomerPortalController::class, 'order']);
        Route::get('loyalty', [CustomerPortalController::class, 'loyalty']);
    });
});

// Mobile
// routes/api.php
Route::prefix('mobile')->middleware(['auth:sanctum', EnsureClientMutationIdempotency::class])->group(function () {
    Route::get('sync/state', [MobileSyncController::class, 'state']);
    Route::post('device-heartbeat', [MobileSyncController::class, 'heartbeat']);
    Route::post('payment-attempts', [PaymentAttemptController::class, 'store']);
    Route::patch('payment-attempts/{paymentAttempt}', [PaymentAttemptController::class, 'update']);
    Route::post('print-jobs', [PrintJobController::class, 'store']);
    Route::post('print-jobs/claim', [PrintJobController::class, 'claim']);
    Route::patch('print-jobs/{printJob}', [PrintJobController::class, 'update']);

    Route::get('tables', [TableMobileController::class, 'index']);
    Route::get('tables/{id}', [TableMobileController::class, 'show']);

    Route::get('orders', [OrderMobileController::class, 'index']);
    Route::get('orders/{id}', [OrderMobileController::class, 'show']);
    Route::get('products', [MobileProductController::class, 'index']);
    Route::post('orders', [OrderMobileController::class, 'store']);
    Route::patch('tables/{fromTable}/move', [TableMobileController::class, 'moveTable']);
    // Route::patch('orders/{order}/send-to-cashier', [TableMobileController::class, 'sendToCashier']);
    Route::put('orders/{order}', [OrderMobileController::class, 'update']);
    Route::patch('orders/{order}/reopen', [TableMobileController::class, 'reopenOrder']);
    Route::post('/coupons/verify', [CouponController::class, 'verify']);
    Route::post('/orders/{id}/pay', [OrderMobileController::class, 'pay']);
    Route::get('/orders/{id}/receipt', [OrderMobileController::class, 'receipt']);
    Route::patch('order-items/{id}/refund-change', [TableMobileController::class, 'refundOrChangeItem']);
    Route::delete('order-items/{id}', [TableMobileController::class, 'removeUnsentItem']);
    Route::get('order-items/{id}/history', [TableMobileController::class, 'itemHistory']);
    // routes/api.php
    Route::get('/modifiers/available', [ModifierController::class, 'availableForWaiter']);

    Route::post('orders/{order}/send-to-kds', [OrderMobileController::class, 'sendToKDS']);
    Route::patch('kds/order-items/{item}', [KDSController::class, 'setOrderItemStatus']); // keep
    Route::get('kds/orders', [KDSController::class, 'getActiveOrders']); // should filter by kds_status

    // Cashier (fix path!)
    Route::post('orders/send-to-cashier', [OrderMobileController::class, 'batchSendToCashier']);
    Route::patch('orders/{order}/send-to-cashier', [OrderMobileController::class, 'sendToCashier']); // single

    Route::prefix('kds')->group(function () {
        Route::get('/orders', [KDSController::class, 'getActiveOrders']);
        Route::patch('/orders/{order}', [KDSController::class, 'setOrderStatus']);
        Route::patch('/order-items/{item}', [KDSController::class, 'setOrderItemStatus']);
    });
});
