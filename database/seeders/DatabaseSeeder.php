<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\Salary;
use App\Models\SalaryPayment;
use App\Models\EmployeePerformance;
use App\Models\Category;
use App\Models\Product;
use App\Models\Menu;
use App\Models\Table;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Discount;
use App\Models\Tax;
use App\Models\MenuModifier;
use App\Models\SalesReport;
use App\Models\InventoryReport;
use App\Models\BranchPerformance;
use App\Models\ProductPerformance;
use App\Models\FinancialSummary;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Transaction;
use App\Platform\Modules\Models\Module as PlatformModule;
use App\Platform\Modules\Models\ModuleFeature;
use App\Platform\Tenancy\Models\SubscriptionDiscount;
use App\Platform\Tenancy\Models\SubscriptionPlan as TenancySubscriptionPlan;
use App\Platform\Tenancy\Models\Tenant;
use App\Platform\Tenancy\Models\TenantSubscription;
use App\Models\CashRegister;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Feedback;
use App\Models\Notification;
use App\Models\Alert;
use App\Models\AlertSubscription;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\ApiToken;
use App\Models\WebhookLog;
use App\Domain\ERP\Models\BillOfMaterial;
use App\Domain\ERP\Models\BillOfMaterialLine;
use App\Domain\ERP\Models\CostCenter as ErpCostCenter;
use App\Domain\ERP\Models\Department as ErpDepartment;
use App\Domain\ERP\Models\Item as ErpItem;
use App\Domain\ERP\Models\ItemCategory;
use App\Domain\ERP\Models\Site;
use App\Domain\MES\Models\ProductionEvent;
use App\Domain\MES\Models\ProductionLine as MesProductionLine;
use App\Domain\MES\Models\WorkOrder as MesWorkOrder;
use App\Domain\PLM\Models\DesignDocument;
use App\Domain\PLM\Models\EngineeringChange;
use App\Domain\PLM\Models\ProductDesign;
use App\Domain\SCM\Models\DemandPlan as ScmDemandPlan;
use App\Domain\SCM\Models\InboundShipment as ScmInboundShipment;
use App\Domain\SCM\Models\PurchaseOrder as ScmPurchaseOrder;
use App\Domain\SCM\Models\PurchaseOrderLine as ScmPurchaseOrderLine;
use App\Domain\SCM\Models\Supplier as ScmSupplier;
use App\Domain\WMS\Models\InventoryLot as WmsInventoryLot;
use App\Domain\WMS\Models\StorageBin as WmsStorageBin;
use App\Domain\WMS\Models\TransferOrder as WmsTransferOrder;
use App\Domain\WMS\Models\Warehouse as WmsWarehouse;
use App\Domain\QMS\Models\Audit as QmsAudit;
use App\Domain\QMS\Models\CapaAction as QmsCapaAction;
use App\Domain\QMS\Models\Inspection as QmsInspection;
use App\Domain\QMS\Models\InspectionPlan as QmsInspectionPlan;
use App\Domain\QMS\Models\NonConformity as QmsNonConformity;
use App\Domain\HRMS\Models\AttendanceRecord as HrmsAttendanceRecord;
use App\Domain\HRMS\Models\EmploymentContract as HrmsEmploymentContract;
use App\Domain\HRMS\Models\LeaveRequest as HrmsLeaveRequest;
use App\Domain\HRMS\Models\PayrollEntry as HrmsPayrollEntry;
use App\Domain\HRMS\Models\PayrollRun as HrmsPayrollRun;
use App\Domain\HRMS\Models\TrainingAssignment as HrmsTrainingAssignment;
use App\Domain\HRMS\Models\TrainingSession as HrmsTrainingSession;
use App\Domain\HRMS\Models\Worker as HrmsWorker;
use App\Domain\CMMS\Models\Asset as CmmsAsset;
use App\Domain\CMMS\Models\MaintenanceLog as CmmsMaintenanceLog;
use App\Domain\CMMS\Models\MaintenancePlan as CmmsMaintenancePlan;
use App\Domain\CMMS\Models\MaintenanceWorkOrder as CmmsMaintenanceWorkOrder;
use App\Domain\CMMS\Models\SparePart as CmmsSparePart;
use App\Domain\Finance\Models\LedgerAccount as FinanceLedgerAccount;
use App\Domain\Finance\Models\JournalEntry as FinanceJournalEntry;
use App\Domain\Finance\Models\JournalEntryLine as FinanceJournalEntryLine;
use App\Domain\Finance\Models\AccountPayable as FinanceAccountPayable;
use App\Domain\Finance\Models\AccountReceivable as FinanceAccountReceivable;
use App\Domain\CRM\Models\Account as CrmAccount;
use App\Domain\CRM\Models\Contact as CrmContact;
use App\Domain\CRM\Models\Lead as CrmLead;
use App\Domain\CRM\Models\Opportunity as CrmOpportunity;
use App\Domain\CRM\Models\ServiceCase as CrmServiceCase;
use App\Domain\BI\Models\Kpi as BiKpi;
use App\Domain\BI\Models\Dashboard as BiDashboard;
use App\Domain\BI\Models\DashboardWidget as BiDashboardWidget;
use App\Domain\BI\Models\DataSnapshot as BiDataSnapshot;
use App\Domain\HSE\Models\Incident as HseIncident;
use App\Domain\HSE\Models\Audit as HseAudit;
use App\Domain\HSE\Models\Action as HseAction;
use App\Domain\HSE\Models\TrainingRecord as HseTrainingRecord;
use App\Domain\DMS\Models\Document as DmsDocument;
use App\Domain\DMS\Models\DocumentFolder as DmsDocumentFolder;
use App\Domain\DMS\Models\DocumentVersion as DmsDocumentVersion;
use App\Domain\Visitor\Models\Visitor as VisitorVisitor;
use App\Domain\Visitor\Models\VisitorEntry as VisitorEntry;
use App\Domain\IoT\Models\Device as IotDevice;
use App\Domain\IoT\Models\Sensor as IotSensor;
use App\Domain\IoT\Models\Reading as IotReading;
use App\Domain\Procurement\Models\Vendor as ProcurementVendor;
use App\Domain\Procurement\Models\Tender as ProcurementTender;
use App\Domain\Procurement\Models\TenderResponse as ProcurementTenderResponse;
use App\Domain\Procurement\Models\PurchaseRequest as ProcurementPurchaseRequest;
use App\Domain\Commerce\Models\Customer as CommerceCustomer;
use App\Domain\Commerce\Models\Order as CommerceOrder;
use App\Domain\Commerce\Models\OrderItem as CommerceOrderItem;
use App\Domain\Budgeting\Models\CostCenter as BudgetingCostCenter;
use App\Domain\Budgeting\Models\Budget as BudgetingBudget;
use App\Domain\Budgeting\Models\Actual as BudgetingActual;
use App\Domain\Projects\Models\Project as ProjectsProject;
use App\Domain\Projects\Models\ProjectTask as ProjectsProjectTask;
use App\Domain\Projects\Models\ChangeRequest as ProjectsChangeRequest;
use App\Domain\Projects\Models\ChangeApproval as ProjectsChangeApproval;
use App\Domain\Communication\Models\Announcement as CommunicationAnnouncement;
use App\Domain\Communication\Models\WorkflowRequest as CommunicationWorkflowRequest;
use App\Domain\Communication\Models\WorkflowAction as CommunicationWorkflowAction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $moduleDefinitions = [
            ['key' => 'erp', 'name' => 'Enterprise Resource Planning', 'category' => 'Core Platform', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Central backbone integrating finance, HR, production, inventory, procurement, and more.'],
            ['key' => 'mes', 'name' => 'Manufacturing Execution System', 'category' => 'Operations', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Real-time monitoring and control of production lines with shop-floor visibility.'],
            ['key' => 'plm', 'name' => 'Product Lifecycle Management', 'category' => 'Engineering', 'is_core' => false, 'has_mobile_app' => false, 'description' => 'Manage product design, engineering changes, and release documentation.'],
            ['key' => 'scm', 'name' => 'Supply Chain Management', 'category' => 'Operations', 'is_core' => true, 'has_mobile_app' => false, 'description' => 'Plan procurement, supplier orders, logistics, and demand balancing.'],
            ['key' => 'wms', 'name' => 'Warehouse Management System', 'category' => 'Operations', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Track materials and finished goods with bin, barcode, and RFID tools.'],
            ['key' => 'qms', 'name' => 'Quality Management System', 'category' => 'Compliance', 'is_core' => false, 'has_mobile_app' => true, 'description' => 'Quality inspections, audits, non-conformance, CAPA, and ISO compliance.'],
            ['key' => 'hrms', 'name' => 'Human Resource Management', 'category' => 'People', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Recruitment, attendance, payroll, training, and employee self-service.'],
            ['key' => 'cmms', 'name' => 'Maintenance Management', 'category' => 'Operations', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Asset maintenance, work orders, preventive schedules, and technician apps.'],
            ['key' => 'finance', 'name' => 'Finance & Accounting', 'category' => 'Core Platform', 'is_core' => true, 'has_mobile_app' => false, 'description' => 'General ledger, AP/AR, cost accounting, and financial controls.'],
            ['key' => 'crm', 'name' => 'Customer Relationship Management', 'category' => 'Growth', 'is_core' => false, 'has_mobile_app' => true, 'description' => 'Manage leads, customer orders, and after-sales service with mobile sales tools.'],
            ['key' => 'bi', 'name' => 'Business Intelligence', 'category' => 'Analytics', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'KPIs, dashboards, and predictive analytics across the factory footprint.'],
            ['key' => 'hse', 'name' => 'Health, Safety & Environment', 'category' => 'Compliance', 'is_core' => false, 'has_mobile_app' => true, 'description' => 'Safety incident tracking, audits, training, and compliance workflows.'],
            ['key' => 'dms', 'name' => 'Document Management System', 'category' => 'Core Platform', 'is_core' => true, 'has_mobile_app' => false, 'description' => 'Centralised storage for documents, blueprints, ISO forms, and versioning.'],
            ['key' => 'visitor', 'name' => 'Visitor & Access Control', 'category' => 'Operations', 'is_core' => false, 'has_mobile_app' => true, 'description' => 'Gate entry, ID cards, visitor logs, and kiosk integrations.'],
            ['key' => 'iot', 'name' => 'IoT & SCADA Integration', 'category' => 'Operations', 'is_core' => false, 'has_mobile_app' => false, 'description' => 'Collect machine telemetry, OEE, and environmental data from sensors.'],
            ['key' => 'procurement', 'name' => 'Procurement & Vendor Portal', 'category' => 'Operations', 'is_core' => true, 'has_mobile_app' => false, 'description' => 'Supplier onboarding, tenders, purchase orders, and vendor collaboration.'],
            ['key' => 'commerce', 'name' => 'E-Commerce & Order Portal', 'category' => 'Growth', 'is_core' => false, 'has_mobile_app' => true, 'description' => 'B2B/B2C ordering experience integrated with ERP and fulfilment.'],
            ['key' => 'budgeting', 'name' => 'Budgeting & Cost Control', 'category' => 'Finance', 'is_core' => false, 'has_mobile_app' => false, 'description' => 'Budget creation, cost centres, variance tracking, and financial analytics.'],
            ['key' => 'projects', 'name' => 'Project & Engineering Change', 'category' => 'Engineering', 'is_core' => false, 'has_mobile_app' => false, 'description' => 'Project tracking, R&D workflows, and structured change approvals.'],
            ['key' => 'workflows', 'name' => 'Internal Communication & Workflow', 'category' => 'Core Platform', 'is_core' => true, 'has_mobile_app' => true, 'description' => 'Announcements, approvals, requests, and collaboration channels.'],
        ];

        foreach ($moduleDefinitions as $definition) {
            PlatformModule::updateOrCreate(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'is_core' => $definition['is_core'],
                    'has_mobile_app' => $definition['has_mobile_app'],
                    'description' => $definition['description'],
                ]
            );
        }

        $defaultPlan = TenancySubscriptionPlan::updateOrCreate(
            ['slug' => 'factory-enterprise-suite'],
            [
                'name' => 'Factory Enterprise Suite',
                'billing_cycle' => 'monthly',
                'price_cents' => 0,
                'currency' => 'USD',
                'is_active' => true,
            ]
        );

        $modules = PlatformModule::whereIn('key', array_column($moduleDefinitions, 'key'))->get()->keyBy('key');

        $defaultPlan->modules()->sync(
            $modules->mapWithKeys(fn ($module) => [
                $module->id => [
                    'seat_limit' => null,
                    'is_optional' => !$module->is_core,
                ],
            ])->toArray()
        );

        $moduleFeatureDefinitions = [
            'budgeting' => [
                [
                    'key' => 'cost_planning',
                    'name' => 'Cost Planning',
                    'category' => 'financial',
                    'is_default' => true,
                    'description' => 'Enable creation and approval workflow for cost centre budgets.',
                ],
                [
                    'key' => 'variance_analysis',
                    'name' => 'Variance Analysis',
                    'category' => 'analytics',
                    'is_default' => true,
                    'description' => 'Track variances against approved budgets with drill-downs.',
                ],
                [
                    'key' => 'forecast_scenarios',
                    'name' => 'Forecast Scenarios',
                    'category' => 'planning',
                    'is_default' => false,
                    'description' => 'Allow multi-scenario what-if forecasts for financial planning.',
                ],
            ],
            'projects' => [
                [
                    'key' => 'task_board',
                    'name' => 'Task Board',
                    'category' => 'execution',
                    'is_default' => true,
                    'description' => 'Kanban-style project task planning and status tracking.',
                ],
                [
                    'key' => 'change_control',
                    'name' => 'Change Control',
                    'category' => 'governance',
                    'is_default' => true,
                    'description' => 'Formalise engineering change requests with approvals.',
                ],
                [
                    'key' => 'risk_register',
                    'name' => 'Risk Register',
                    'category' => 'governance',
                    'is_default' => false,
                    'description' => 'Log and mitigate project risks with owners and due dates.',
                ],
            ],
            'workflows' => [
                [
                    'key' => 'announcements',
                    'name' => 'Announcements',
                    'category' => 'communication',
                    'is_default' => true,
                    'description' => 'Publish plant-wide announcements with scheduling.',
                ],
                [
                    'key' => 'approval_requests',
                    'name' => 'Approval Requests',
                    'category' => 'workflow',
                    'is_default' => true,
                    'description' => 'Route internal approval workflows with SLA tracking.',
                ],
                [
                    'key' => 'broadcast_alerts',
                    'name' => 'Broadcast Alerts',
                    'category' => 'communication',
                    'is_default' => false,
                    'description' => 'Escalate urgent alerts to targeted groups via push and email.',
                ],
            ],
            'bi' => [
                [
                    'key' => 'executive_dashboard',
                    'name' => 'Executive Dashboard',
                    'category' => 'dashboards',
                    'is_default' => true,
                    'description' => 'Summarise KPIs for executive leadership with real-time widgets.',
                ],
                [
                    'key' => 'trend_insights',
                    'name' => 'Trend Insights',
                    'category' => 'analytics',
                    'is_default' => true,
                    'description' => 'Visual trend analysis over time with comparison benchmarks.',
                ],
                [
                    'key' => 'predictive_forecast',
                    'name' => 'Predictive Forecast',
                    'category' => 'analytics',
                    'is_default' => false,
                    'description' => 'AI-assisted forecasting for throughput and downtime indicators.',
                ],
            ],
        ];

        foreach ($moduleFeatureDefinitions as $moduleKey => $features) {
            $module = $modules->get($moduleKey);
            if (!$module) {
                continue;
            }

            foreach ($features as $feature) {
                ModuleFeature::updateOrCreate(
                    ['module_id' => $module->id, 'key' => $feature['key']],
                    [
                        'name' => $feature['name'],
                        'category' => $feature['category'] ?? null,
                        'is_default' => $feature['is_default'] ?? false,
                        'description' => $feature['description'] ?? null,
                        'metadata' => $feature['metadata'] ?? null,
                    ]
                );
            }
        }

        $modules = PlatformModule::with('features')->whereIn('key', array_column($moduleDefinitions, 'key'))->get()->keyBy('key');

        // Create Roles
        $roles = [
            'owner' => Role::firstOrCreate(
                ['tenant_id' => null, 'key' => 'owner'],
                [
                    'name' => 'owner',
                    'display_name' => 'Owner',
                    'description' => 'Factory owner / administrator',
                    'guard_name' => 'web',
                    'is_default' => false,
                ]
            ),
            'supervisor' => Role::firstOrCreate(
                ['tenant_id' => null, 'key' => 'supervisor'],
                [
                    'name' => 'supervisor',
                    'display_name' => 'Supervisor',
                    'description' => 'Supervise departmental operations',
                    'guard_name' => 'web',
                    'is_default' => false,
                ]
            ),
            'staff' => Role::firstOrCreate(
                ['tenant_id' => null, 'key' => 'staff'],
                [
                    'name' => 'staff',
                    'display_name' => 'Staff',
                    'description' => 'General staff role',
                    'guard_name' => 'web',
                    'is_default' => true,
                ]
            ),
            'employee' => Role::firstOrCreate(
                ['tenant_id' => null, 'key' => 'employee'],
                [
                    'name' => 'employee',
                    'display_name' => 'Employee',
                    'description' => 'Employee self-service role',
                    'guard_name' => 'web',
                    'is_default' => false,
                ]
            ),
        ];

        // Create Owner
        $owner = User::factory()->create([
            'name' => 'Main Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'owner'
        ]);

        $tenant = Tenant::firstOrCreate(
            ['code' => 'FACTORY-HQ'],
            [
                'name' => 'Factory HQ',
                'industry' => 'Manufacturing',
                'status' => 'active',
                'billing_email' => 'owner@example.com',
                'activated_at' => now(),
                'settings' => ['primary_currency' => 'USD'],
            ]
        );

        $tenant->users()->syncWithoutDetaching([
            $owner->id => [
                'is_primary' => true,
                'status' => 'active',
                'invited_at' => now(),
                'accepted_at' => now(),
            ],
        ]);

        $owner->roles()->sync([
            $roles['owner']->id => [
                'tenant_id' => $tenant->id,
                'assigned_at' => now(),
            ],
        ]);

        $tenantSubscription = TenantSubscription::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $defaultPlan->id,
            ],
            [
                'status' => 'active',
                'starts_at' => now()->subDays(7),
                'trial_ends_at' => now()->addDays(21),
            ]
        );

        $tenantModulePayload = $modules->mapWithKeys(fn ($module) => [
            $module->id => [
                'status' => $module->is_core ? 'active' : 'pending',
                'activated_at' => $module->is_core ? now() : null,
                'seat_limit' => null,
            ],
        ])->toArray();

        $tenant->modules()->sync($tenantModulePayload);

        $defaultFeaturePayload = [];
        foreach ($modules as $module) {
            $module->features
                ->where('is_default', true)
                ->each(function (ModuleFeature $feature) use (&$defaultFeaturePayload) {
                    $defaultFeaturePayload[$feature->id] = [
                        'status' => 'enabled',
                        'settings' => null,
                    ];
                });
        }

        if (!empty($defaultFeaturePayload)) {
            $tenant->moduleFeatures()->syncWithoutDetaching($defaultFeaturePayload);
        }

        SubscriptionDiscount::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Launch Bundle Discount',
            ],
            [
                'type' => 'percent',
                'value' => 15.0,
                'stackable' => false,
                'reason' => 'Introductory offer for bundled domain access.',
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addMonths(3),
            ]
        );

        // Create Employee
        // $employee = User::factory()->create([
        //     'name' => 'Main Employee',
        //     'email' => 'employee@example.com',
        //     'password' => Hash::make('password'),
        //     'role' => 'employee'
        // ]);
        // $employee->roles()->sync([$roles['employee']->id]);

        // Create Branches
        $branches = collect([
    Branch::factory()->create(['name' => 'Downtown Branch', 'location' => 'Downtown']),
    Branch::factory()->create(['name' => 'Mall Branch', 'location' => 'Mall Center']),
        ]);


        // Create Supervisors and Staff per Branch
        foreach ($branches as $branch) {
            $supervisor = User::factory()->create([
                'name' => $branch->name . ' Supervisor',
                'email' => 'supervisor_' . Str::slug($branch->name) . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'supervisor',
                'branch_id' => $branch->id,
            ]);
            $supervisor->roles()->sync([
                $roles['supervisor']->id => [
                    'tenant_id' => $tenant->id,
                    'assigned_at' => now(),
                ],
            ]);

            $staff = User::factory(2)->create([
                'role' => 'staff',
                'branch_id' => $branch->id,
            ]);
            foreach ($staff as $s) {
                $s->roles()->sync([
                    $roles['staff']->id => [
                        'tenant_id' => $tenant->id,
                        'assigned_at' => now(),
                    ],
                ]);
            }

            // Tables
            Table::factory()->count(4)->create(['branch_id' => $branch->id]);
            // Devices
            Device::factory()->count(2)->create(['branch_id' => $branch->id]);
            // Employees
            Employee::factory()->count(5)->create(['branch_id' => $branch->id]);
        }

        // Categories and Products
        $categories = collect([
    Category::factory()->create(['name' => 'Burgers']),
    Category::factory()->create(['name' => 'Drinks']),
]);

        $products = collect();
        foreach ($categories as $category) {
            $products = $products->merge(Product::factory()->count(3)->create([
                'category_id' => $category->id,
                'branch_id' => $branches->first()->id,
            ]));
        }

        // Menus
        $menu = Menu::factory()->create(['name' => 'Main Menu', 'branch_id' => $branches->first()->id]);
        $menu->combos()->create(['name' => 'Combo 1', 'price' => 120]);

        // Ingredients & Recipes
        $ingredients = Ingredient::factory()->count(5)->create();
        $recipe = Recipe::factory()->create(['product_id' => $products->first()->id]);
        foreach ($ingredients as $ing) {
            RecipeIngredient::factory()->create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ing->id,
                'quantity' => rand(1, 10),
            ]);
        }

        // Inventory Items & Transactions
        $inventoryItems = InventoryItem::factory()->count(5)->create(['branch_id' => $branches->first()->id]);
        foreach ($inventoryItems as $item) {
            InventoryTransaction::factory()->count(2)->create(['inventory_item_id' => $item->id]);
        }

        // Supplier & PurchaseOrder
        $supplier = Supplier::factory()->create(['name' => 'Super Supplier']);
        PurchaseOrder::factory()->count(2)->create([
            'branch_id' => $branches->first()->id,
            'supplier_id' => $supplier->id,
        ]);

        // Orders & POS
        $order = Order::factory()->create([
            'branch_id' => $branches->first()->id,
            'table_id' => Table::first()->id,
            'order_type' => "dine-in",
            'subtotal' => "500",
            'tax' => "12.5",
            'discount' => '5',
        ]);
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_id' => $products->first()->id,
            
        ]);
        // Payment::factory()->create(['order_id' => $order->id]);

        // // Reports
        // SalesReport::factory()->create(['branch_id' => $branches->first()->id]);
        // InventoryReport::factory()->create(['branch_id' => $branches->first()->id]);
        // BranchPerformance::factory()->create(['branch_id' => $branches->first()->id]);
        // ProductPerformance::factory()->create(['product_id' => $products->first()->id]);
        // FinancialSummary::factory()->create(['branch_id' => $branches->first()->id]);

        // // Other modules (quick seed)
        // Expense::factory()->count(2)->create(['branch_id' => $branches->first()->id]);
        // Income::factory()->count(2)->create(['branch_id' => $branches->first()->id]);
        // CashRegister::factory()->create(['branch_id' => $branches->first()->id]);
        // BankAccount::factory()->create();
        // Customer::factory()->create();
        // LoyaltyTransaction::factory()->create();
        // Feedback::factory()->create();
        // Notification::factory()->create();
        // Alert::factory()->create();
        // AlertSubscription::factory()->create(['user_id' => $owner->id]);
        // Setting::factory()->create(['key' => 'currency', 'value' => 'USD']);
        // ActivityLog::factory()->create(['user_id' => $owner->id]);
        // AuditLog::factory()->create(['user_id' => $owner->id]);
        // ApiToken::factory()->create(['user_id' => $owner->id]);
        // WebhookLog::factory()->create();

        // ERP sample data
        $hqSite = Site::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'HQ'],
            [
                'name' => 'HQ Manufacturing Campus',
                'status' => 'active',
                'timezone' => 'America/New_York',
                'address' => [
                    'line1' => '123 Industry Way',
                    'city' => 'Factoryville',
                    'state' => 'NY',
                    'country' => 'USA',
                ],
            ]
        );

        $productionDepartment = ErpDepartment::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'PROD'],
            [
                'site_id' => $hqSite->id,
                'name' => 'Production',
                'status' => 'active',
            ]
        );

        ErpCostCenter::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'CC-100'],
            [
                'department_id' => $productionDepartment->id,
                'name' => 'Assembly Line Cost Center',
                'status' => 'active',
            ]
        );

        $itemCategory = ItemCategory::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'FG'],
            ['name' => 'Finished Goods', 'description' => 'Finished products leaving the factory']
        );

        $componentCategory = ItemCategory::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'RM'],
            ['name' => 'Raw Materials', 'description' => 'Purchased raw materials']
        );

        $finishedGood = ErpItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'FG-100'],
            [
                'category_id' => $itemCategory->id,
                'sku' => 'FG100',
                'name' => 'Industrial Pump Assembly',
                'type' => 'manufactured',
                'uom' => 'EA',
                'status' => 'active',
                'standard_cost' => 250.00,
                'list_price' => 480.00,
            ]
        );

        $component = ErpItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'RM-200'],
            [
                'category_id' => $componentCategory->id,
                'sku' => 'RM200',
                'name' => 'Pump Housing',
                'type' => 'purchased',
                'uom' => 'EA',
                'status' => 'active',
                'standard_cost' => 120.00,
                'list_price' => 0,
            ]
        );

        $bom = BillOfMaterial::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'BOM-FG-100', 'revision' => 'A'],
            [
                'item_id' => $finishedGood->id,
                'status' => 'active',
                'effective_from' => now()->subMonth(),
            ]
        );

        BillOfMaterialLine::where('bom_id', $bom->id)->delete();

        BillOfMaterialLine::create([
            'tenant_id' => $tenant->id,
            'bom_id' => $bom->id,
            'component_item_id' => $component->id,
            'quantity' => 1,
            'uom' => 'EA',
            'sequence' => 1,
        ]);

        // MES sample data
        $productionLine = MesProductionLine::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'LINE-1'],
            [
                'site_id' => $hqSite->id,
                'name' => 'Assembly Line 1',
                'status' => 'active',
            ]
        );

        $workOrder = MesWorkOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'WO-1001'],
            [
                'item_id' => $finishedGood->id,
                'production_line_id' => $productionLine->id,
                'status' => 'released',
                'quantity' => 100,
                'quantity_completed' => 25,
                'planned_start_at' => now()->startOfDay(),
                'planned_end_at' => now()->addDays(5),
            ]
        );

        ProductionEvent::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'work_order_id' => $workOrder->id,
                'event_type' => 'status_changed',
                'event_timestamp' => $workOrder->planned_start_at ?? now(),
            ],
            [
                'payload' => ['from' => 'planned', 'to' => 'released'],
            ]
        );

        // PLM sample data
        $productDesign = ProductDesign::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'PD-100'],
            [
                'item_id' => $finishedGood->id,
                'name' => 'Industrial Pump Design',
                'version' => '1.0',
                'lifecycle_state' => 'released',
                'description' => 'Design package for the industrial pump assembly.',
            ]
        );

        $engineeringChange = EngineeringChange::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ECR-500'],
            [
                'product_design_id' => $productDesign->id,
                'title' => 'Upgrade Pump Housing Material',
                'status' => 'submitted',
                'effectivity_date' => now()->addWeeks(2),
                'requested_by' => $owner->id,
            ]
        );

        DesignDocument::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'product_design_id' => $productDesign->id,
                'file_path' => 'designs/pump/specification-v1.pdf',
            ],
            [
                'document_type' => 'specification',
                'file_name' => 'Pump Specification v1.pdf',
                'version' => '1.0',
            ]
        );

        // SCM sample data
        $scmSupplier = ScmSupplier::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SUP-001'],
            [
                'name' => 'Precision Metals Ltd.',
                'contact_name' => 'Maria Steele',
                'email' => 'maria@precisionmetals.com',
                'phone' => '+1-555-321-9876',
                'status' => 'active',
                'address' => [
                    'line1' => '22 Supply Park',
                    'city' => 'Logistic City',
                    'state' => 'PA',
                    'country' => 'USA',
                ],
            ]
        );

        $scmPurchaseOrder = ScmPurchaseOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'po_number' => 'PO-24001'],
            [
                'supplier_id' => $scmSupplier->id,
                'status' => 'approved',
                'order_date' => now()->subDays(5)->toDateString(),
                'expected_date' => now()->addDays(7)->toDateString(),
                'metadata' => ['notes' => 'Expedite for upcoming production run'],
            ]
        );

        ScmPurchaseOrderLine::query()->where('purchase_order_id', $scmPurchaseOrder->id)->delete();

        $lineTotal = 25 * 80;
        ScmPurchaseOrderLine::create([
            'tenant_id' => $tenant->id,
            'purchase_order_id' => $scmPurchaseOrder->id,
            'item_id' => $component->id,
            'description' => 'Pump Housing Casting',
            'quantity' => 25,
            'uom' => 'EA',
            'unit_price' => 80,
            'line_total' => $lineTotal,
        ]);

        $scmPurchaseOrder->update([
            'subtotal' => $lineTotal,
            'tax_total' => round($lineTotal * 0.07, 2),
            'grand_total' => round($lineTotal * 1.07, 2),
        ]);

        ScmInboundShipment::updateOrCreate(
            ['tenant_id' => $tenant->id, 'purchase_order_id' => $scmPurchaseOrder->id, 'reference' => 'ASN-24001'],
            [
                'status' => 'pending',
                'arrival_date' => now()->addDays(5)->toDateString(),
            ]
        );

        ScmDemandPlan::updateOrCreate(
            ['tenant_id' => $tenant->id, 'item_id' => $finishedGood->id, 'period' => now()->format('Y-m')],
            [
                'forecast_quantity' => 320,
                'planning_strategy' => 'make-to-stock',
                'assumptions' => ['seasonality' => 'high', 'notes' => 'Holiday demand spike'],
            ]
        );

        // WMS sample data
        $warehouse = WmsWarehouse::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'WH-HQ'],
            [
                'name' => 'HQ Central Warehouse',
                'status' => 'active',
                'address' => [
                    'line1' => '400 Warehouse Blvd',
                    'city' => 'Factoryville',
                    'state' => 'NY',
                    'country' => 'USA',
                ],
            ]
        );

        $binA = WmsStorageBin::updateOrCreate(
            ['tenant_id' => $tenant->id, 'warehouse_id' => $warehouse->id, 'code' => 'A-01'],
            ['zone' => 'A', 'status' => 'active']
        );

        $binB = WmsStorageBin::updateOrCreate(
            ['tenant_id' => $tenant->id, 'warehouse_id' => $warehouse->id, 'code' => 'B-05'],
            ['zone' => 'B', 'status' => 'active']
        );

        WmsInventoryLot::updateOrCreate(
            ['tenant_id' => $tenant->id, 'storage_bin_id' => $binA->id, 'item_id' => $component->id, 'lot_number' => 'LOT-2401'],
            [
                'warehouse_id' => $warehouse->id,
                'quantity' => 50,
                'uom' => 'EA',
                'received_at' => now()->subDays(2)->toDateString(),
            ]
        );

        WmsTransferOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'TO-9001'],
            [
                'source_bin_id' => $binA->id,
                'destination_bin_id' => $binB->id,
                'item_id' => $finishedGood->id,
                'quantity' => 10,
                'status' => 'in_transit',
                'requested_at' => now()->toDateTimeString(),
            ]
        );

        // QMS sample data
        $inspectionPlan = QmsInspectionPlan::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'INSP-FG'],
            [
                'name' => 'Finished Goods Final Inspection',
                'inspection_type' => 'final',
                'status' => 'active',
                'checklist' => [
                    ['step' => 'Visual Inspection', 'criteria' => 'Surface free from defects'],
                    ['step' => 'Functional Test', 'criteria' => 'Meet pump performance spec'],
                ],
            ]
        );

        $inspection = QmsInspection::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'INSP-2025-10-001'],
            [
                'inspection_plan_id' => $inspectionPlan->id,
                'item_id' => $finishedGood->id,
                'status' => 'in_progress',
                'results' => ['visual' => 'pass'],
                'inspected_at' => now()->toDateTimeString(),
                'inspected_by' => $owner->id,
            ]
        );

        $nonConformity = QmsNonConformity::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'NC-2401'],
            [
                'inspection_id' => $inspection->id,
                'severity' => 'medium',
                'status' => 'open',
                'description' => 'Surface scratches observed on 2 units',
            ]
        );

        QmsCapaAction::updateOrCreate(
            ['tenant_id' => $tenant->id, 'non_conformity_id' => $nonConformity->id, 'description' => 'Adjust polishing process'],
            [
                'action_type' => 'corrective',
                'status' => 'in_progress',
                'due_at' => now()->addWeek(),
            ]
        );

        QmsAudit::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'AUD-INT-2025-01'],
            [
                'name' => 'Internal QMS Audit',
                'audit_type' => 'internal',
                'scheduled_date' => now()->addMonth()->toDateString(),
                'status' => 'scheduled',
            ]
        );

        // HRMS sample data
        $worker = HrmsWorker::updateOrCreate(
            ['tenant_id' => $tenant->id, 'employee_number' => 'EMP-1001'],
            [
                'first_name' => 'Alicia',
                'last_name' => 'Turner',
                'email' => 'alicia.turner@factorysuite.test',
                'employment_status' => 'active',
                'hire_date' => now()->subYear()->toDateString(),
            ]
        );

        HrmsEmploymentContract::updateOrCreate(
            ['tenant_id' => $tenant->id, 'worker_id' => $worker->id],
            [
                'contract_type' => 'permanent',
                'start_date' => now()->subYear()->toDateString(),
                'salary' => 65000,
                'currency' => 'USD',
            ]
        );

        HrmsAttendanceRecord::updateOrCreate(
            ['tenant_id' => $tenant->id, 'worker_id' => $worker->id, 'attendance_date' => now()->toDateString()],
            [
                'check_in_at' => now()->setTime(8, 0)->toDateTimeString(),
                'check_out_at' => now()->setTime(17, 0)->toDateTimeString(),
                'status' => 'present',
            ]
        );

        $payrollRun = HrmsPayrollRun::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'PR-2025-10'],
            [
                'period' => now()->format('Y-m'),
                'status' => 'processing',
                'pay_date' => now()->addDays(3)->toDateString(),
            ]
        );

        HrmsPayrollEntry::updateOrCreate(
            ['tenant_id' => $tenant->id, 'payroll_run_id' => $payrollRun->id, 'worker_id' => $worker->id],
            [
                'gross_amount' => 5200,
                'net_amount' => 4150,
                'breakdown' => ['basic' => 4500, 'bonus' => 700, 'tax' => -950],
            ]
        );

        $payrollTotals = HrmsPayrollEntry::query()
            ->where('tenant_id', $tenant->id)
            ->where('payroll_run_id', $payrollRun->id)
            ->selectRaw('SUM(gross_amount) as gross_total, SUM(net_amount) as net_total')
            ->first();

        $payrollRun->update([
            'gross_total' => (float) ($payrollTotals->gross_total ?? 0),
            'net_total' => (float) ($payrollTotals->net_total ?? 0),
        ]);

        HrmsLeaveRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'worker_id' => $worker->id, 'start_date' => now()->addDays(10)->toDateString()],
            [
                'leave_type' => 'vacation',
                'end_date' => now()->addDays(12)->toDateString(),
                'status' => 'approved',
                'reason' => 'Family event',
            ]
        );

        $trainingSession = HrmsTrainingSession::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Lean Manufacturing Workshop'],
            [
                'scheduled_date' => now()->addDays(14)->toDateString(),
                'status' => 'scheduled',
            ]
        );

        HrmsTrainingAssignment::updateOrCreate(
            ['tenant_id' => $tenant->id, 'training_session_id' => $trainingSession->id, 'worker_id' => $worker->id],
            ['status' => 'assigned']
        );

        // CMMS sample data
        $cmmsAsset = CmmsAsset::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'EQUIP-PMP-01'],
            [
                'name' => 'Primary Process Pump',
                'asset_type' => 'pump',
                'status' => 'active',
                'location' => [
                    'area' => 'Blending Hall',
                    'line' => 'Line 3',
                    'coordinates' => 'Aisle 2 / Slot 5',
                ],
                'commissioned_at' => '2023-01-15',
                'metadata' => [
                    'manufacturer' => 'FactoryFlow',
                    'model' => 'FF-PMP-100',
                ],
            ]
        );

        $cmmsSparePart = CmmsSparePart::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SP-IMPELLER-01'],
            [
                'name' => 'Impeller Assembly',
                'uom' => 'EA',
                'quantity_on_hand' => 8,
                'reorder_level' => 3,
                'metadata' => ['asset_code' => $cmmsAsset->code],
            ]
        );

        $maintenancePlan = CmmsMaintenancePlan::updateOrCreate(
            ['tenant_id' => $tenant->id, 'asset_id' => $cmmsAsset->id, 'name' => 'Quarterly Pump PM'],
            [
                'frequency' => 'quarterly',
                'interval_days' => 90,
                'tasks' => [
                    ['step' => 'Inspect seals', 'expected_result' => 'No leaks observed'],
                    ['step' => 'Check vibration', 'expected_result' => '< 6 mm/s'],
                    ['step' => 'Lubricate bearings', 'expected_result' => 'Fresh grease applied'],
                ],
                'status' => 'active',
            ]
        );

        $workOrder = CmmsMaintenanceWorkOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'MWO-2025-01'],
            [
                'asset_id' => $cmmsAsset->id,
                'maintenance_plan_id' => $maintenancePlan->id,
                'status' => 'scheduled',
                'priority' => 'medium',
                'description' => 'Quarterly preventive maintenance inspection for process pump.',
                'scheduled_date' => '2025-11-02',
                'metadata' => [
                    'parts_required' => [$cmmsSparePart->code],
                    'safety' => 'Lockout/tagout before service.',
                ],
            ]
        );

        CmmsMaintenanceLog::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'work_order_id' => $workOrder->id,
                'logged_at' => '2025-10-20 09:00:00',
            ],
            [
                'notes' => 'Maintenance scheduled and checklist confirmed.',
                'logged_by' => $owner->id,
            ]
        );

        // Finance sample data
        $cashAccount = FinanceLedgerAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => '1000'],
            [
                'name' => 'Cash on Hand',
                'account_type' => 'asset',
                'parent_code' => null,
                'is_active' => true,
                'metadata' => ['currency' => 'USD'],
            ]
        );

        $maintenanceExpenseAccount = FinanceLedgerAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => '6100'],
            [
                'name' => 'Maintenance Expense',
                'account_type' => 'expense',
                'parent_code' => null,
                'is_active' => true,
            ]
        );

        $journalEntry = FinanceJournalEntry::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'JE-2025-0101'],
            [
                'entry_date' => '2025-10-15',
                'description' => 'Record preventive maintenance expense',
                'status' => 'posted',
            ]
        );

        FinanceJournalEntryLine::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'journal_entry_id' => $journalEntry->id,
                'ledger_account_id' => $maintenanceExpenseAccount->id,
            ],
            [
                'debit' => 250,
                'credit' => 0,
                'memo' => 'Maintenance service vendor invoice',
            ]
        );

        FinanceJournalEntryLine::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'journal_entry_id' => $journalEntry->id,
                'ledger_account_id' => $cashAccount->id,
            ],
            [
                'debit' => 0,
                'credit' => 250,
                'memo' => 'Cash payment for maintenance',
            ]
        );

        FinanceAccountPayable::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'AP-INV-2025-010'],
            [
                'vendor_name' => 'Industrial Oils LLC',
                'invoice_date' => '2025-10-10',
                'due_date' => '2025-11-09',
                'amount' => 1200,
                'status' => 'open',
            ]
        );

        FinanceAccountReceivable::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'AR-INV-2025-044'],
            [
                'customer_name' => 'Acme Retail Partners',
                'invoice_date' => '2025-10-05',
                'due_date' => '2025-11-04',
                'amount' => 5400,
                'status' => 'open',
            ]
        );

        // CRM sample data
        $crmAccount = CrmAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Acme Manufacturing'],
            [
                'industry' => 'Automotive',
                'status' => 'active',
                'address' => [
                    'line1' => '200 Industrial Way',
                    'city' => 'Factoryville',
                    'state' => 'NY',
                ],
            ]
        );

        $crmContact = CrmContact::updateOrCreate(
            ['tenant_id' => $tenant->id, 'account_id' => $crmAccount->id, 'email' => 'laura.wells@acme-mfg.test'],
            [
                'first_name' => 'Laura',
                'last_name' => 'Wells',
                'phone' => '+1-555-302-8841',
                'position' => 'Operations Manager',
            ]
        );

        CrmLead::updateOrCreate(
            ['tenant_id' => $tenant->id, 'company_name' => 'Delta Steel Works'],
            [
                'contact_name' => 'Mark Rivera',
                'email' => 'mark.rivera@deltasteel.test',
                'phone' => '+1-555-741-2290',
                'status' => 'qualified',
                'source' => 'Trade Show',
            ]
        );

        $crmOpportunity = CrmOpportunity::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Acme Service Agreement'],
            [
                'account_id' => $crmAccount->id,
                'stage' => 'proposal',
                'amount' => 18500,
                'close_date' => '2025-11-15',
            ]
        );

        CrmServiceCase::updateOrCreate(
            ['tenant_id' => $tenant->id, 'case_number' => 'CASE-2025-1001'],
            [
                'account_id' => $crmAccount->id,
                'title' => 'Post-installation calibration support',
                'status' => 'working',
                'priority' => 'high',
                'description' => 'Assist with line calibration after commissioning new equipment.',
            ]
        );

        // BI sample data
        $uptimeKpi = BiKpi::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'KPI-OEE-UPTIME'],
            [
                'name' => 'Overall Equipment Uptime',
                'category' => 'Operations',
                'unit' => 'percentage',
                'config' => ['target' => 92],
            ]
        );

        $defaultDashboard = BiDashboard::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Factory Executive Overview'],
            [
                'description' => 'Cross-domain KPIs for executive review.',
                'layout' => [
                    ['i' => 'uptime', 'x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
                ],
                'is_default' => true,
            ]
        );

        BiDashboardWidget::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'dashboard_id' => $defaultDashboard->id,
                'position' => 1,
            ],
            [
                'kpi_id' => $uptimeKpi->id,
                'type' => 'metric',
                'options' => ['comparison' => 'previous_period'],
            ]
        );

        BiDataSnapshot::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'kpi_id' => $uptimeKpi->id,
                'snapshot_date' => '2025-10-15',
            ],
            [
                'value' => 91.5,
                'metadata' => ['source' => 'MES'],
            ]
        );

        // HSE sample data
        $hseIncident = HseIncident::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'INC-2025-014'],
            [
                'title' => 'Minor chemical spill near blending station',
                'incident_date' => '2025-10-08',
                'severity' => 'medium',
                'status' => 'investigating',
                'description' => 'Operator reported a small spill during drum changeover. Spill contained immediately.',
            ]
        );

        $hseAudit = HseAudit::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'HSE-AUD-2025-02'],
            [
                'name' => 'Quarterly Safety Walkthrough',
                'scheduled_date' => '2025-11-05',
                'status' => 'scheduled',
                'findings' => ['focus_areas' => ['Emergency exits', 'Chemical storage']],
            ]
        );

        HseAction::updateOrCreate(
            ['tenant_id' => $tenant->id, 'incident_id' => $hseIncident->id, 'description' => 'Update spill response checklist for new solvent.'],
            [
                'action_type' => 'corrective',
                'status' => 'in_progress',
                'due_date' => '2025-10-25',
            ]
        );

        HseAction::updateOrCreate(
            ['tenant_id' => $tenant->id, 'audit_id' => $hseAudit->id, 'description' => 'Verify safety signage visibility on Line 3.'],
            [
                'action_type' => 'preventive',
                'status' => 'open',
                'due_date' => '2025-11-10',
            ]
        );

        HseTrainingRecord::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Confined Space Refresher'],
            [
                'session_date' => '2025-10-12',
                'trainer' => 'Patricia Singh',
                'attendees' => [
                    ['name' => 'Alicia Turner', 'employee_number' => 'EMP-1001'],
                    ['name' => 'Jonah Blake', 'employee_number' => 'EMP-1004'],
                ],
                'metadata' => ['location' => 'Safety Training Room'],
            ]
        );

        // DMS sample data
        $dmsFolder = DmsDocumentFolder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'DOC-QMS'],
            [
                'name' => 'Quality Manuals',
                'description' => 'Primary repository for audited quality documentation.',
                'metadata' => ['owner' => 'Quality Office'],
            ]
        );

        $dmsDocument = DmsDocument::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'PROC-QA-001'],
            [
                'folder_id' => $dmsFolder->id,
                'title' => 'Quality Assurance Master Procedure',
                'document_type' => 'procedure',
                'status' => 'approved',
                'latest_version_number' => 1,
                'tags' => ['quality', 'iso9001'],
                'description' => 'Defines quality management policies and escalation protocols.',
            ]
        );

        DmsDocumentVersion::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'document_id' => $dmsDocument->id,
                'version_number' => 1,
            ],
            [
                'file_path' => 'documents/procedures/PROC-QA-001-v1.pdf',
                'checksum' => 'sha256:b5a4d2',
                'notes' => 'Initial release aligned to ISO 9001:2025.',
            ]
        );

        // Visitor & access sample data
        $visitorRecord = VisitorVisitor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'full_name' => 'Michael Chen'],
            [
                'company' => 'Industrial Solutions Ltd.',
                'email' => 'michael.chen@industrialsolutions.test',
                'phone' => '+1-555-872-3344',
                'status' => 'invited',
                'metadata' => ['purpose' => 'Vendor audit'],
            ]
        );

        VisitorEntry::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'visitor_id' => $visitorRecord->id,
                'scheduled_start' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
            ],
            [
                'host_name' => 'Alicia Turner',
                'host_department' => 'Quality',
                'purpose' => 'Supplier qualification audit',
                'status' => 'scheduled',
                'badge_number' => 'VIS-204',
            ]
        );

        // IoT sample data
        $iotDevice = IotDevice::updateOrCreate(
            ['tenant_id' => $tenant->id, 'device_key' => 'OEE-LINE-03'],
            [
                'name' => 'Line 3 OEE Gateway',
                'device_type' => 'gateway',
                'status' => 'active',
                'location' => ['line' => '3', 'area' => 'Assembly'],
                'installed_at' => '2025-01-10',
                'last_heartbeat_at' => now()->subMinutes(5)->toDateTimeString(),
            ]
        );

        $iotSensor = IotSensor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'device_id' => $iotDevice->id, 'tag' => 'LINE3_TEMP'],
            [
                'name' => 'Line 3 Temperature',
                'unit' => 'C',
                'data_type' => 'float',
                'threshold_min' => 18,
                'threshold_max' => 34,
            ]
        );

        IotReading::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'sensor_id' => $iotSensor->id,
                'recorded_at' => now()->subMinutes(2)->toDateTimeString(),
            ],
            [
                'value' => 27.6,
                'quality' => 'good',
                'metadata' => ['source' => 'edge-collector'],
            ]
        );

        // Procurement sample data
        $procurementVendor = ProcurementVendor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'VEND-ACM'],
            [
                'name' => 'Acme Industrial Parts',
                'category' => 'Mechanical Components',
                'status' => 'active',
                'email' => 'sales@acmeindustrial.test',
                'phone' => '+1-555-420-7788',
                'address' => ['line1' => '560 Supplier Park', 'city' => 'Factoryville'],
            ]
        );

        $procurementTender = ProcurementTender::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'TDR-2025-004'],
            [
                'title' => 'Line 3 Conveyor Upgrade',
                'status' => 'open',
                'opening_date' => now()->subWeek()->toDateString(),
                'closing_date' => now()->addWeek()->toDateString(),
                'description' => 'Upgrade conveyor drive assemblies for Line 3.',
            ]
        );

        ProcurementTenderResponse::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'tender_id' => $procurementTender->id,
                'vendor_id' => $procurementVendor->id,
            ],
            [
                'response_date' => now()->toDateString(),
                'status' => 'submitted',
                'amount' => 48250,
                'notes' => 'Includes installation and commissioning support.',
            ]
        );

        ProcurementPurchaseRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'PR-2025-218'],
            [
                'requester_name' => 'Jonah Blake',
                'department' => 'Maintenance',
                'status' => 'pending_approval',
                'needed_by' => now()->addDays(21)->toDateString(),
                'total_amount' => 12500,
                'justification' => 'Spare actuators for high-usage packaging robots.',
            ]
        );

        // Commerce sample data
        $commerceCustomer = CommerceCustomer::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'B2B-HORIZON'],
            [
                'name' => 'Horizon Retail Group',
                'email' => 'procurement@horizonretail.test',
                'phone' => '+1-555-930-2210',
                'status' => 'active',
                'billing_address' => ['line1' => '99 Market St', 'city' => 'New Delta'],
                'shipping_address' => ['line1' => '120 Logistics Ave', 'city' => 'New Delta'],
            ]
        );

        $commerceOrder = CommerceOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'order_number' => 'EC-2025-10021'],
            [
                'customer_id' => $commerceCustomer->id,
                'channel' => 'web',
                'status' => 'fulfilled',
                'subtotal' => 8600,
                'discount' => 300,
                'shipping_fee' => 150,
                'tax' => 500,
                'total' => 8950,
                'currency' => 'USD',
                'placed_at' => now()->subDays(5)->setTime(11, 30)->toDateTimeString(),
                'fulfilled_at' => now()->subDays(2)->setTime(16, 0)->toDateTimeString(),
                'metadata' => ['priority' => 'high'],
            ]
        );

        CommerceOrderItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'order_id' => $commerceOrder->id, 'sku' => 'FG-AXLE-1000'],
            [
                'name' => 'Heavy Duty Axle Assembly',
                'quantity' => 20,
                'unit_price' => 320,
                'line_total' => 6400,
            ]
        );

        CommerceOrderItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'order_id' => $commerceOrder->id, 'sku' => 'FG-GEAR-225'],
            [
                'name' => 'Precision Gear Set',
                'quantity' => 10,
                'unit_price' => 220,
                'line_total' => 2200,
            ]
        );

        // Budgeting sample data
        $costCenter = BudgetingCostCenter::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'MFG-PROD'],
            [
                'name' => 'Manufacturing - Production',
                'department' => 'Manufacturing',
                'manager_name' => 'Alicia Turner',
                'status' => 'active',
                'metadata' => ['location' => 'Plant 1'],
            ]
        );

        $budgetCurrent = BudgetingBudget::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cost_center_id' => $costCenter->id,
                'period' => 'FY25-Q4',
            ],
            [
                'fiscal_year' => 'FY25',
                'status' => 'approved',
                'planned_amount' => 250000,
                'approved_amount' => 240000,
                'forecast_amount' => 245000,
                'currency' => 'USD',
                'assumptions' => ['shift_pattern' => '3-shift', 'energy_price' => 'locked'],
            ]
        );

        BudgetingBudget::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cost_center_id' => $costCenter->id,
                'period' => 'FY26-Q1',
            ],
            [
                'fiscal_year' => 'FY26',
                'status' => 'draft',
                'planned_amount' => 265000,
                'currency' => 'USD',
                'assumptions' => ['new_line' => 'Line 4 automation'],
            ]
        );

        BudgetingActual::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cost_center_id' => $costCenter->id,
                'period' => 'FY25-Q3',
                'source_reference' => 'GL-2025-09',
            ],
            [
                'fiscal_year' => 'FY25',
                'actual_amount' => 238750,
                'currency' => 'USD',
                'notes' => 'Includes overtime for maintenance shutdown recovery.',
            ]
        );

        BudgetingActual::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cost_center_id' => $costCenter->id,
                'period' => 'FY25-Q4',
                'source_reference' => 'GL-2025-10',
            ],
            [
                'fiscal_year' => 'FY25',
                'actual_amount' => 61250,
                'currency' => 'USD',
                'notes' => 'Month-to-date performance through October 15.',
            ]
        );

        // Projects sample data
        $project = ProjectsProject::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'PRJ-LINE4-AUTO'],
            [
                'name' => 'Line 4 Automation Upgrade',
                'status' => 'active',
                'stage' => 'execution',
                'description' => 'Deploy collaborative robots and vision inspection on Line 4.',
                'start_date' => now()->subMonth()->toDateString(),
                'due_date' => now()->addMonths(4)->toDateString(),
                'budget_amount' => 750000,
                'metadata' => ['sponsor' => 'Operations VP'],
            ]
        );

        ProjectsProjectTask::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'title' => 'Install robot pedestals',
            ],
            [
                'status' => 'in_progress',
                'priority' => 'high',
                'start_date' => now()->subWeeks(2)->toDateString(),
                'due_date' => now()->addWeek()->toDateString(),
                'progress' => 55,
                'metadata' => ['vendor' => 'RoboWorks Integrators'],
            ]
        );

        $visionTask = ProjectsProjectTask::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'title' => 'Configure vision inspection recipe',
            ],
            [
                'status' => 'not_started',
                'priority' => 'medium',
                'depends_on_task_id' => null,
                'metadata' => ['owner' => 'Quality Engineering'],
            ]
        );

        ProjectsProjectTask::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'project_id' => $project->id,
                'title' => 'Run integrated FAT',
            ],
            [
                'status' => 'not_started',
                'priority' => 'medium',
                'depends_on_task_id' => $visionTask->id,
                'metadata' => ['location' => 'Line 4'],
            ]
        );

        $changeRequest = ProjectsChangeRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'ECR-2025-021'],
            [
                'project_id' => $project->id,
                'title' => 'Add palletizing cell safety scanner',
                'change_type' => 'scope',
                'status' => 'in_review',
                'requested_by' => $project->owner_id,
                'requested_at' => now()->subDays(3)->toDateString(),
                'risk_level' => 'medium',
                'impact_summary' => 'Adds $32k hardware cost but reduces safety risk.',
            ]
        );

        ProjectsChangeApproval::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'change_request_id' => $changeRequest->id,
                'role' => 'Engineering Lead',
            ],
            [
                'status' => 'approved',
                'comments' => 'Supports compliance with latest safety assessment.',
                'acted_at' => now()->subDay()->toDateTimeString(),
            ]
        );

        ProjectsChangeApproval::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'change_request_id' => $changeRequest->id,
                'role' => 'Finance Controller',
            ],
            [
                'status' => 'pending',
                'comments' => null,
            ]
        );

        // Communication & workflow sample data
        CommunicationAnnouncement::updateOrCreate(
            ['tenant_id' => $tenant->id, 'title' => 'Annual Maintenance Shutdown'],
            [
                'body' => 'Plant-wide shutdown scheduled for Dec 12-14 for preventive maintenance.',
                'priority' => 'high',
                'status' => 'scheduled',
                'publish_at' => now()->addWeeks(6)->setTime(7, 30)->toDateTimeString(),
                'audiences' => ['All Employees', 'Maintenance'],
                'attachments' => [
                    ['name' => 'Shutdown Checklist', 'path' => 'communication/announcements/shutdown-checklist.pdf'],
                ],
            ]
        );

        $workflowRequest = CommunicationWorkflowRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'reference' => 'WRK-REQ-4587'],
            [
                'request_type' => 'Access Request',
                'title' => 'Temporary MES access for auditors',
                'description' => 'Grant read-only MES access to ISO audit team from Nov 4-6.',
                'status' => 'in_progress',
                'priority' => 'high',
                'requester_id' => $project->owner_id,
                'requested_at' => now()->toDateString(),
                'due_at' => now()->addDays(5)->toDateString(),
                'payload' => ['systems' => ['MES', 'SCADA'], 'access_level' => 'read-only'],
            ]
        );

        CommunicationWorkflowAction::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'workflow_request_id' => $workflowRequest->id,
                'action' => 'approve',
            ],
            [
                'status' => 'completed',
                'comments' => 'Approved for duration of audit window.',
                'acted_at' => now()->setTime(10, 15)->toDateTimeString(),
            ]
        );

        CommunicationWorkflowAction::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'workflow_request_id' => $workflowRequest->id,
                'action' => 'assign',
            ],
            [
                'status' => 'recorded',
                'comments' => 'IT Security to provision accounts.',
                'metadata' => ['assignee_role' => 'IT Security'],
            ]
        );

        // Add more if needed...
    }
}
