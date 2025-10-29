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
use App\Domain\ERP\Http\Controllers\BillOfMaterialController;
use App\Domain\ERP\Http\Controllers\DepartmentController;
use App\Domain\ERP\Http\Controllers\ItemController as ErpItemController;
use App\Domain\ERP\Http\Controllers\SiteController;
use App\Domain\MES\Http\Controllers\ProductionEventController;
use App\Domain\MES\Http\Controllers\ProductionLineController;
use App\Domain\MES\Http\Controllers\WorkOrderController;
use App\Domain\SCM\Http\Controllers\DemandPlanController;
use App\Domain\SCM\Http\Controllers\InboundShipmentController;
use App\Domain\SCM\Http\Controllers\PurchaseOrderController;
use App\Domain\SCM\Http\Controllers\SupplierController as ScmSupplierController;
use App\Domain\WMS\Http\Controllers\InventoryLotController;
use App\Domain\WMS\Http\Controllers\StorageBinController;
use App\Domain\WMS\Http\Controllers\TransferOrderController;
use App\Domain\WMS\Http\Controllers\WarehouseController;
use App\Domain\QMS\Http\Controllers\AuditController;
use App\Domain\QMS\Http\Controllers\CapaActionController;
use App\Domain\QMS\Http\Controllers\InspectionController;
use App\Domain\QMS\Http\Controllers\InspectionPlanController;
use App\Domain\QMS\Http\Controllers\NonConformityController;
use App\Domain\HRMS\Http\Controllers\AttendanceRecordController;
use App\Domain\HRMS\Http\Controllers\EmploymentContractController;
use App\Domain\HRMS\Http\Controllers\LeaveRequestController;
use App\Domain\HRMS\Http\Controllers\PayrollRunController;
use App\Domain\HRMS\Http\Controllers\TrainingAssignmentController;
use App\Domain\HRMS\Http\Controllers\TrainingSessionController;
use App\Domain\HRMS\Http\Controllers\WorkerController;
use App\Domain\PLM\Http\Controllers\DesignDocumentController;
use App\Domain\PLM\Http\Controllers\EngineeringChangeController;
use App\Domain\PLM\Http\Controllers\ProductDesignController;
use App\Domain\CMMS\Http\Controllers\AssetController as CmmsAssetController;
use App\Domain\CMMS\Http\Controllers\MaintenanceLogController as CmmsMaintenanceLogController;
use App\Domain\CMMS\Http\Controllers\MaintenancePlanController as CmmsMaintenancePlanController;
use App\Domain\CMMS\Http\Controllers\MaintenanceWorkOrderController as CmmsMaintenanceWorkOrderController;
use App\Domain\CMMS\Http\Controllers\SparePartController as CmmsSparePartController;
use App\Domain\Finance\Http\Controllers\AccountPayableController as FinanceAccountPayableController;
use App\Domain\Finance\Http\Controllers\AccountReceivableController as FinanceAccountReceivableController;
use App\Domain\Finance\Http\Controllers\JournalEntryController as FinanceJournalEntryController;
use App\Domain\Finance\Http\Controllers\LedgerAccountController as FinanceLedgerAccountController;
use App\Domain\CRM\Http\Controllers\AccountController as CrmAccountController;
use App\Domain\CRM\Http\Controllers\ContactController as CrmContactController;
use App\Domain\CRM\Http\Controllers\LeadController as CrmLeadController;
use App\Domain\CRM\Http\Controllers\OpportunityController as CrmOpportunityController;
use App\Domain\CRM\Http\Controllers\ServiceCaseController as CrmServiceCaseController;
use App\Domain\BI\Http\Controllers\DashboardController as BiDashboardController;
use App\Domain\BI\Http\Controllers\DashboardWidgetController as BiDashboardWidgetController;
use App\Domain\BI\Http\Controllers\DataSnapshotController as BiDataSnapshotController;
use App\Domain\BI\Http\Controllers\KpiController as BiKpiController;
use App\Domain\BI\Http\Controllers\ReportController as BiReportController;
use App\Domain\HSE\Http\Controllers\ActionController as HseActionController;
use App\Domain\HSE\Http\Controllers\AuditController as HseAuditController;
use App\Domain\HSE\Http\Controllers\IncidentController as HseIncidentController;
use App\Domain\HSE\Http\Controllers\TrainingRecordController as HseTrainingRecordController;
use App\Domain\DMS\Http\Controllers\DocumentController as DmsDocumentController;
use App\Domain\DMS\Http\Controllers\DocumentFolderController as DmsDocumentFolderController;
use App\Domain\DMS\Http\Controllers\DocumentVersionController as DmsDocumentVersionController;
use App\Domain\Visitor\Http\Controllers\VisitorController;
use App\Domain\Visitor\Http\Controllers\VisitorEntryController;
use App\Domain\IoT\Http\Controllers\DeviceController as IoTDeviceController;
use App\Domain\IoT\Http\Controllers\ReadingController as IoTReadingController;
use App\Domain\IoT\Http\Controllers\SensorController as IoTSensorController;
use App\Domain\Procurement\Http\Controllers\PurchaseRequestController;
use App\Domain\Procurement\Http\Controllers\TenderController;
use App\Domain\Procurement\Http\Controllers\TenderResponseController;
use App\Domain\Procurement\Http\Controllers\VendorController;
use App\Domain\Commerce\Http\Controllers\CustomerController as CommerceCustomerController;
use App\Domain\Commerce\Http\Controllers\OrderController as CommerceOrderController;
use App\Domain\Budgeting\Http\Controllers\CostCenterController as BudgetingCostCenterController;
use App\Domain\Budgeting\Http\Controllers\BudgetController as BudgetingBudgetController;
use App\Domain\Budgeting\Http\Controllers\ActualController as BudgetingActualController;
use App\Domain\Budgeting\Http\Controllers\ReportController as BudgetingReportController;
use App\Domain\Projects\Http\Controllers\ProjectController as ProjectsProjectController;
use App\Domain\Projects\Http\Controllers\ProjectTaskController;
use App\Domain\Projects\Http\Controllers\ChangeRequestController as ProjectsChangeRequestController;
use App\Domain\Projects\Http\Controllers\ChangeApprovalController as ProjectsChangeApprovalController;
use App\Domain\Projects\Http\Controllers\ReportController as ProjectsReportController;
use App\Domain\Communication\Http\Controllers\AnnouncementController;
use App\Domain\Communication\Http\Controllers\WorkflowActionController;
use App\Domain\Communication\Http\Controllers\WorkflowRequestController;
use App\Domain\Communication\Http\Controllers\ReportController as CommunicationReportController;
use App\Platform\Tenancy\Http\Controllers\TenantController as PlatformTenantController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:sanctum', 'tenant.access'])->group(function () {
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
Route::middleware(['auth:sanctum', 'tenant.access'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
Route::middleware(['auth:sanctum', 'tenant.access'])->get('/dashboard/summary', [OwnerDashboardController::class, 'summary']);
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
Route::prefix('mobile')->middleware(['auth:sanctum', 'tenant.access'])->group(function () {
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

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('erp')->group(function () {
    Route::apiResource('sites', SiteController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('items', ErpItemController::class);
    Route::apiResource('boms', BillOfMaterialController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('mes')->group(function () {
    Route::apiResource('production-lines', ProductionLineController::class);
    Route::apiResource('work-orders', WorkOrderController::class);
    Route::get('events', [ProductionEventController::class, 'index']);
    Route::post('events', [ProductionEventController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('plm')->group(function () {
    Route::apiResource('product-designs', ProductDesignController::class);
    Route::apiResource('engineering-changes', EngineeringChangeController::class);
    Route::apiResource('design-documents', DesignDocumentController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('scm')->group(function () {
    Route::apiResource('suppliers', ScmSupplierController::class);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::apiResource('inbound-shipments', InboundShipmentController::class);
    Route::apiResource('demand-plans', DemandPlanController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('wms')->group(function () {
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('storage-bins', StorageBinController::class);
    Route::apiResource('inventory-lots', InventoryLotController::class);
    Route::apiResource('transfer-orders', TransferOrderController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('qms')->group(function () {
    Route::apiResource('inspection-plans', InspectionPlanController::class);
    Route::apiResource('inspections', InspectionController::class);
    Route::apiResource('non-conformities', NonConformityController::class);
    Route::apiResource('capa-actions', CapaActionController::class);
    Route::apiResource('audits', AuditController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('hrms')->group(function () {
    Route::apiResource('workers', WorkerController::class);
    Route::apiResource('employment-contracts', EmploymentContractController::class);
    Route::apiResource('attendance-records', AttendanceRecordController::class);
    Route::apiResource('payroll-runs', PayrollRunController::class);
    Route::apiResource('leave-requests', LeaveRequestController::class);
    Route::apiResource('training-sessions', TrainingSessionController::class);
    Route::apiResource('training-assignments', TrainingAssignmentController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('cmms')->group(function () {
    Route::apiResource('assets', CmmsAssetController::class);
    Route::apiResource('maintenance-plans', CmmsMaintenancePlanController::class);
    Route::apiResource('work-orders', CmmsMaintenanceWorkOrderController::class);
    Route::apiResource('maintenance-logs', CmmsMaintenanceLogController::class);
    Route::apiResource('spare-parts', CmmsSparePartController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('finance')->group(function () {
    Route::apiResource('ledger-accounts', FinanceLedgerAccountController::class);
    Route::apiResource('journal-entries', FinanceJournalEntryController::class);
    Route::apiResource('accounts-payable', FinanceAccountPayableController::class);
    Route::apiResource('accounts-receivable', FinanceAccountReceivableController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('crm')->group(function () {
    Route::apiResource('accounts', CrmAccountController::class);
    Route::apiResource('contacts', CrmContactController::class);
    Route::apiResource('leads', CrmLeadController::class);
    Route::apiResource('opportunities', CrmOpportunityController::class);
    Route::apiResource('service-cases', CrmServiceCaseController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('bi')->group(function () {
    Route::get('reports/summary', [BiReportController::class, 'summary']);
    Route::get('reports/export', [BiReportController::class, 'export']);
    Route::apiResource('kpis', BiKpiController::class);
    Route::apiResource('dashboards', BiDashboardController::class);
    Route::apiResource('dashboard-widgets', BiDashboardWidgetController::class);
    Route::apiResource('data-snapshots', BiDataSnapshotController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('hse')->group(function () {
    Route::apiResource('incidents', HseIncidentController::class);
    Route::apiResource('audits', HseAuditController::class);
    Route::apiResource('actions', HseActionController::class);
    Route::apiResource('training-records', HseTrainingRecordController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('dms')->group(function () {
    Route::apiResource('folders', DmsDocumentFolderController::class);
    Route::apiResource('documents', DmsDocumentController::class);
    Route::apiResource('document-versions', DmsDocumentVersionController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('visitor')->group(function () {
    Route::apiResource('visitors', VisitorController::class);
    Route::apiResource('entries', VisitorEntryController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('iot')->group(function () {
    Route::apiResource('devices', IoTDeviceController::class);
    Route::apiResource('sensors', IoTSensorController::class);
    Route::apiResource('readings', IoTReadingController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('procurement')->group(function () {
    Route::apiResource('vendors', VendorController::class);
    Route::apiResource('tenders', TenderController::class);
    Route::apiResource('tender-responses', TenderResponseController::class);
    Route::apiResource('purchase-requests', PurchaseRequestController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('commerce')->group(function () {
    Route::apiResource('customers', CommerceCustomerController::class);
    Route::apiResource('orders', CommerceOrderController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('budgeting')->group(function () {
    Route::get('reports/summary', [BudgetingReportController::class, 'summary']);
    Route::get('reports/export', [BudgetingReportController::class, 'export']);
    Route::apiResource('cost-centers', BudgetingCostCenterController::class);
    Route::apiResource('budgets', BudgetingBudgetController::class);
    Route::apiResource('actuals', BudgetingActualController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->group(function () {
    Route::get('projects/reports/summary', [ProjectsReportController::class, 'summary']);
    Route::get('projects/reports/export', [ProjectsReportController::class, 'export']);
    Route::apiResource('projects', ProjectsProjectController::class);
    Route::apiResource('project-tasks', ProjectTaskController::class);
    Route::apiResource('project-change-requests', ProjectsChangeRequestController::class);
    Route::apiResource('project-change-approvals', ProjectsChangeApprovalController::class);
});

Route::middleware(['auth:sanctum', 'tenant.access'])->prefix('communication')->group(function () {
    Route::get('reports/summary', [CommunicationReportController::class, 'summary']);
    Route::get('reports/export', [CommunicationReportController::class, 'export']);
    Route::apiResource('announcements', AnnouncementController::class);
    Route::apiResource('workflows', WorkflowRequestController::class);
    Route::apiResource('workflow-actions', WorkflowActionController::class);
});

Route::middleware(['auth:sanctum'])->prefix('platform')->group(function () {
    Route::get('modules', [PlatformTenantController::class, 'modules']);
    Route::get('tenants', [PlatformTenantController::class, 'index']);
    Route::post('tenants', [PlatformTenantController::class, 'store']);
    Route::put('tenants/{tenant}', [PlatformTenantController::class, 'update']);
    Route::get('tenants/{tenant}/users', [PlatformTenantController::class, 'users']);
    Route::post('tenants/{tenant}/users', [PlatformTenantController::class, 'storeUser']);
    Route::put('tenants/{tenant}/users/{user}', [PlatformTenantController::class, 'updateUser']);
    Route::put('tenants/{tenant}/modules/{module}', [PlatformTenantController::class, 'updateModule']);
    Route::put('tenants/{tenant}/modules/{module}/features/{feature}', [PlatformTenantController::class, 'updateModuleFeature']);
});
