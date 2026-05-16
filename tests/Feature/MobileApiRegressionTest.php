<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BillingInvoice;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ClientMutation;
use App\Models\Customer;
use App\Models\CustomerOtpCode;
use App\Models\EtaReceiptSubmission;
use App\Models\Employee;
use App\Models\FiscalProfile;
use App\Models\Device;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentProviderConfig;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\Recipe;
use App\Models\RestaurantSubscription;
use App\Models\StockTransfer;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_mobile_order_show_returns_an_order_payload(): void
    {
        $order = Order::query()
            ->with('table')
            ->whereHas('items')
            ->firstOrFail();

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'waiter'));

        $this->getJson("/api/mobile/orders/{$order->id}")
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('data.id', $order->id)
                ->where('data.table.id', $order->table_id)
                ->missing('data.order')
            );
    }

    public function test_mobile_order_item_rejects_invalid_action_values(): void
    {
        $item = OrderItem::query()
            ->whereHas('order.table')
            ->with('order.table')
            ->firstOrFail();

        Sanctum::actingAs($this->staffUserForBranch((int) $item->order->branch_id, 'waiter'));

        $this->patchJson("/api/mobile/order-items/{$item->id}/refund-change", [
            'action' => 'mystery',
            'note' => 'invalid transition',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }

    public function test_send_to_kds_rejects_order_with_only_refunded_items(): void
    {
        $order = Order::query()
            ->with(['items', 'table'])
            ->whereHas('table')
            ->whereHas('items')
            ->firstOrFail();

        $order->forceFill([
            'status' => 'pending',
            'kds_sent_at' => null,
        ])->save();
        $order->items()->update([
            'status' => 'refunded',
            'kds_status' => 'pending',
            'kds_sent_at' => null,
        ]);

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'waiter'));

        $this->postJson("/api/mobile/orders/{$order->id}/send-to-kds")
            ->assertUnprocessable()
            ->assertJsonPath('error', 'No active items are waiting to be sent to kitchen.');

        $this->assertNull($order->fresh()->kds_sent_at);
        $this->assertSame(
            ['pending'],
            $order->items()->pluck('kds_status')->unique()->values()->all(),
        );
    }

    public function test_cross_branch_cashier_cannot_pay_a_foreign_order(): void
    {
        $order = Order::query()->whereHas('items')->firstOrFail();
        $foreignCashier = User::query()
            ->where('branch_id', '!=', $order->branch_id)
            ->where('email', 'like', 'cashier%@example.com')
            ->firstOrFail();

        Sanctum::actingAs($foreignCashier);

        $this->postJson("/api/mobile/orders/{$order->id}/pay", [
            'payments' => [
                ['method' => 'cash', 'amount' => 25],
            ],
        ])->assertForbidden();
    }

    public function test_cross_branch_cashier_cannot_download_a_foreign_receipt(): void
    {
        $order = Order::query()->whereHas('items')->firstOrFail();
        $foreignCashier = User::query()
            ->where('branch_id', '!=', $order->branch_id)
            ->where('email', 'like', 'cashier%@example.com')
            ->firstOrFail();

        Sanctum::actingAs($foreignCashier);

        $this->get("/api/mobile/orders/{$order->id}/receipt")
            ->assertForbidden();
    }

    public function test_cross_branch_users_cannot_read_foreign_item_history(): void
    {
        $item = OrderItem::query()
            ->whereHas('order.table')
            ->with('order.table')
            ->firstOrFail();

        $foreignCashier = User::query()
            ->where('branch_id', '!=', $item->order->branch_id)
            ->where('email', 'like', 'cashier%@example.com')
            ->firstOrFail();

        Sanctum::actingAs($foreignCashier);

        $this->getJson("/api/mobile/order-items/{$item->id}/history")
            ->assertForbidden();
    }

    public function test_ingredients_and_employee_performance_require_authentication(): void
    {
        $employee = Employee::query()->firstOrFail();

        $this->getJson('/api/ingredients')->assertUnauthorized();
        $this->getJson("/api/employees/{$employee->id}/performance")->assertUnauthorized();
    }

    public function test_waiter_can_add_items_after_order_was_sent_to_cashier(): void
    {
        $order = Order::query()
            ->with(['items.product', 'table'])
            ->whereHas('items')
            ->firstOrFail();
        $order->forceFill([
            'status' => 'cashier',
            'payment_status' => 'unpaid',
        ])->save();
        $order->table->update(['status' => 'cashier']);

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'waiter'));

        $product = $order->items->first()->product ?? Product::query()->firstOrFail();

        $response = $this->postJson('/api/mobile/orders', [
            'table_id' => $order->table_id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('order.id', $order->id)
            ->assertJsonPath('order.status', 'pending');

        $this->assertGreaterThan(
            $order->items->count(),
            $order->fresh('items')->items->count(),
        );
    }

    public function test_cashier_can_pay_selected_item_without_closing_table(): void
    {
        $order = Order::query()
            ->with(['items', 'table'])
            ->has('items', '>=', 2)
            ->firstOrFail();
        $item = $order->items->first();
        $order->forceFill([
            'status' => 'cashier',
            'payment_status' => 'unpaid',
        ])->save();
        $order->table->update(['status' => 'cashier']);

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $this->postJson("/api/mobile/orders/{$order->id}/pay", [
            'item_ids' => [$item->id],
            'payments' => [
                ['method' => 'cash', 'amount' => (float) $item->total],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('order.payment_status', 'partial')
            ->assertJsonPath('order.status', 'cashier');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'scope' => 'items',
        ]);
        $this->assertSame('cashier', $order->table->fresh()->status);
    }

    public function test_cashier_can_generate_receipt_for_selected_paid_item(): void
    {
        $order = Order::query()
            ->with(['items', 'table'])
            ->has('items', '>=', 2)
            ->firstOrFail();
        $item = $order->items->first();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount' => (float) $item->total,
            'item_ids' => [$item->id],
            'scope' => 'items',
        ]);

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $this->get("/api/mobile/orders/{$order->id}/receipt?payment_id={$payment->id}")
            ->assertOk();

        $receipt = Receipt::query()->latest('id')->firstOrFail();
        $content = json_decode((string) $receipt->content, true);

        $this->assertSame([$item->id], $content['item_ids']);
        $this->assertEqualsWithDelta((float) $item->total, (float) $content['total'], 0.001);

        $receiptCount = Receipt::query()->count();

        $this->get("/api/mobile/orders/{$order->id}/receipt?scope=last")
            ->assertOk();

        $this->assertSame($receiptCount, Receipt::query()->count());
    }

    public function test_full_receipt_marks_paid_items_as_zero_due(): void
    {
        $order = Order::query()
            ->with(['items', 'table'])
            ->whereHas('items', fn ($query) => $query->whereNotIn('status', ['refunded', 'canceled', 'cancelled']), '>=', 2)
            ->firstOrFail();
        $activeItems = $order->items
            ->reject(fn ($item) => in_array($item->status, ['refunded', 'canceled', 'cancelled'], true))
            ->values();
        $paidItem = $activeItems->first();
        $unpaidItem = $activeItems->skip(1)->first();

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount' => (float) $paidItem->total,
            'item_ids' => [$paidItem->id],
            'scope' => 'items',
        ]);

        $order->forceFill([
            'status' => 'cashier',
            'payment_status' => 'partial',
        ])->save();

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $this->get("/api/mobile/orders/{$order->id}/receipt")
            ->assertOk();

        $receipt = Receipt::query()->latest('id')->firstOrFail();
        $content = json_decode((string) $receipt->content, true);
        $lines = collect($content['lines']);
        $paidLine = $lines->firstWhere('id', $paidItem->id);
        $unpaidLine = $lines->firstWhere('id', $unpaidItem->id);

        $this->assertSame('paid', $paidLine['payment_status']);
        $this->assertEqualsWithDelta(0.0, (float) $paidLine['display_total'], 0.001);
        $this->assertEqualsWithDelta((float) $unpaidItem->total, (float) $unpaidLine['display_total'], 0.001);

        $expectedReceiptTotal = round(
            (float) $activeItems
                ->reject(fn ($item) => (int) $item->id === (int) $paidItem->id)
                ->sum('total'),
            2
        );

        $this->assertEqualsWithDelta($expectedReceiptTotal, (float) $content['total'], 0.001);
    }

    public function test_waiter_can_return_item_to_kitchen(): void
    {
        $item = OrderItem::query()
            ->with('order.table')
            ->whereHas('order.table')
            ->firstOrFail();
        $item->forceFill([
            'status' => 'ready',
            'kds_status' => 'ready',
            'kds_sent_at' => now(),
        ])->save();

        Sanctum::actingAs($this->staffUserForBranch((int) $item->order->branch_id, 'waiter'));

        $this->patchJson("/api/mobile/order-items/{$item->id}/refund-change", [
            'action' => 'return',
            'note' => 'Returned for remake',
        ])->assertOk();

        $item->refresh();

        $this->assertSame('returned', $item->status);
        $this->assertSame('returned', $item->kds_status);
    }

    public function test_waiter_cannot_return_item_before_kitchen_finishes_it(): void
    {
        $item = OrderItem::query()
            ->with('order.table')
            ->whereHas('order.table')
            ->firstOrFail();
        $item->forceFill([
            'status' => 'pending',
            'kds_status' => 'pending',
            'kds_sent_at' => null,
        ])->save();

        Sanctum::actingAs($this->staffUserForBranch((int) $item->order->branch_id, 'waiter'));

        $this->patchJson("/api/mobile/order-items/{$item->id}/refund-change", [
            'action' => 'return',
            'note' => 'Returned too early',
        ])->assertUnprocessable()
            ->assertJsonPath('error', 'Only ready or served items can be returned to kitchen.');

        $item->refresh();

        $this->assertSame('pending', $item->status);
        $this->assertSame('pending', $item->kds_status);
    }

    public function test_owner_summary_includes_date_range_and_branch_stock_label(): void
    {
        $branch = Branch::query()->firstOrFail();

        InventoryItem::create([
            'branch_id' => $branch->id,
            'name' => 'Water',
            'unit' => 'bottle',
            'quantity' => 1,
            'min_stock' => 5,
        ]);

        Sanctum::actingAs(User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail());

        $response = $this->getJson('/api/dashboard/summary?preset=month')
            ->assertOk()
            ->assertJsonPath('date_range.preset', 'month');

        $matchingAlert = collect($response->json('low_stock_items'))
            ->firstWhere('name', 'Water');

        $this->assertSame($branch->name, $matchingAlert['branch_name'] ?? null);
    }

    public function test_owner_summary_can_be_filtered_to_an_accessible_branch(): void
    {
        $branch = Branch::query()->firstOrFail();

        Sanctum::actingAs(User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail());

        $response = $this->getJson("/api/dashboard/summary?preset=month&branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('selected_branch_id', $branch->id);

        $this->assertContains(
            $branch->id,
            collect($response->json('branch_options'))->pluck('id')->all(),
        );

        $this->assertTrue(
            collect($response->json('branch_performance'))
                ->every(fn ($row) => (int) $row['id'] === (int) $branch->id),
        );
    }

    public function test_owner_summary_includes_branch_drilldown_and_active_staff(): void
    {
        $branch = Branch::query()
            ->whereHas('employees.user')
            ->whereHas('orders')
            ->firstOrFail();
        $employee = Employee::query()
            ->with('user')
            ->where('branch_id', $branch->id)
            ->whereNotNull('user_id')
            ->firstOrFail();

        DB::table('attendance')->insert([
            'employee_id' => $employee->id,
            'date' => Carbon::today()->toDateString(),
            'check_in' => '09:00:00',
            'check_out' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('staff_shifts')->insert([
            'user_id' => $employee->user_id,
            'shift_start' => Carbon::now()->subHour(),
            'shift_end' => null,
            'is_closed' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Sanctum::actingAs(User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail());

        $response = $this->getJson("/api/dashboard/summary?preset=month&branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('branch_details.0.id', $branch->id);

        $this->assertTrue(
            collect($response->json('active_employees'))
                ->contains(fn ($row) => (int) $row['id'] === (int) $employee->id),
        );

        $detail = collect($response->json('branch_details'))
            ->firstWhere('id', $branch->id);

        $this->assertNotNull($detail);
        $this->assertNotEmpty($detail['employees']);
        $this->assertNotEmpty($detail['tables']);
        $this->assertNotEmpty($detail['orders']);
        $this->assertArrayHasKey('returned_orders_count', $detail);
        $this->assertArrayHasKey('kitchen_shift', $detail);

        $firstOrder = $detail['orders'][0];
        $this->assertArrayHasKey('waiter_name', $firstOrder);
        $this->assertArrayHasKey('cashier_name', $firstOrder);
        $this->assertArrayHasKey('table_name', $firstOrder);
        $this->assertArrayHasKey('items', $firstOrder);
    }

    public function test_owner_can_download_date_range_receipt(): void
    {
        Sanctum::actingAs(User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail());

        $this->get('/api/dashboard/receipt?preset=month')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_health_endpoint_reports_database_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('database', 'ok')
            ->assertJsonStructure([
                'queue_connection',
                'cache_store',
                'broadcast_connection',
                'environment',
            ]);
    }

    public function test_platform_admin_can_onboard_restaurant_branch_owner_and_settings(): void
    {
        $admin = User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail();

        Sanctum::actingAs($admin);

        $this->postJson('/api/onboarding/restaurants', [
            'restaurant' => [
                'name' => 'Phase One Cafe',
                'kind' => 'cafe',
            ],
            'branches' => [
                [
                    'name' => 'Phase One Zamalek',
                    'location' => 'Zamalek',
                ],
            ],
            'owner' => [
                'name' => 'Phase Owner',
                'email' => 'phase-owner@example.com',
                'password' => 'secret123',
            ],
            'settings' => [
                'currency' => 'EGP',
                'vat_rate' => 0.14,
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('restaurant.name', 'Phase One Cafe')
            ->assertJsonPath('branches.0.name', 'Phase One Zamalek')
            ->assertJsonPath('owner.email', 'phase-owner@example.com');

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Phase One Cafe',
            'kind' => 'cafe',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Phase One Zamalek',
            'location' => 'Zamalek',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'phase-owner@example.com',
            'role' => 'owner',
        ]);

        $owner = User::query()->where('email', 'phase-owner@example.com')->firstOrFail();
        $this->assertTrue($owner->roles()->where('name', 'owner')->exists());
        $this->assertTrue($owner->types()->where('name', 'owner')->exists());
        $this->assertDatabaseHas('settings', [
            'key' => "restaurant.{$owner->restaurant_id}.currency",
            'value' => 'EGP',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => "restaurant.{$owner->restaurant_id}.vat_rate",
            'value' => '0.14',
        ]);
        $this->assertDatabaseHas('fiscal_profiles', [
            'restaurant_id' => $owner->restaurant_id,
            'branch_id' => null,
            'currency_code' => 'EGP',
            'vat_rate' => 0.14,
            'price_includes_vat' => true,
        ]);
        $this->assertTrue(
            AuditLog::query()
                ->where('user_id', $admin->id)
                ->get()
                ->contains(fn (AuditLog $log) => ($log->changes['path'] ?? null) === 'api/onboarding/restaurants'),
            'Onboarding mutation was not captured in the API audit log.'
        );
    }

    public function test_dashboard_can_create_multiple_tables_with_optional_seats(): void
    {
        $admin = User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail();
        $branch = Branch::query()
            ->whereNotNull('restaurant_id')
            ->firstOrFail();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tables', [
            'restaurant_id' => $branch->restaurant_id,
            'branch_id' => $branch->id,
            'tables' => [
                ['name' => 'Regression Patio 1'],
                ['name' => 'Regression Patio 2', 'seats' => 4],
            ],
        ])->assertCreated()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseHas('tables', [
            'branch_id' => $branch->id,
            'name' => 'Regression Patio 1',
            'seats' => null,
        ]);
        $this->assertDatabaseHas('tables', [
            'branch_id' => $branch->id,
            'name' => 'Regression Patio 2',
            'seats' => 4,
        ]);

        $tableId = collect($response->json('data'))->firstWhere('name', 'Regression Patio 1')['id'];

        $this->putJson("/api/tables/{$tableId}", [
            'restaurant_id' => $branch->restaurant_id,
            'branch_id' => $branch->id,
            'name' => 'Regression Patio 1A',
            'seats' => null,
        ])->assertOk();

        $this->assertDatabaseHas('tables', [
            'id' => $tableId,
            'name' => 'Regression Patio 1A',
            'seats' => null,
        ]);
    }

    public function test_dashboard_catalog_can_reuse_categories_and_assign_existing_recipe(): void
    {
        $admin = User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail();
        $branch = Branch::query()
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $category = Category::query()
            ->whereNotNull('branch_id')
            ->where('branch_id', '!=', $branch->id)
            ->firstOrFail();
        $recipe = Recipe::query()
            ->where('branch_id', $branch->id)
            ->first() ?: Recipe::create([
                'branch_id' => $branch->id,
                'description' => 'Regression reusable recipe',
            ]);

        Sanctum::actingAs($admin);

        $categoriesResponse = $this->getJson('/api/categories')
            ->assertOk();
        $categoryNames = collect($categoriesResponse->json('data'))
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)));

        $this->assertSame(
            $categoryNames->count(),
            $categoryNames->unique()->count(),
            'Category API returned duplicate category names.'
        );

        $this->postJson('/api/menus', [
            'name' => 'Regression Shared Category Menu',
            'branch_id' => $branch->id,
            'categories' => [$category->id],
        ])->assertCreated()
            ->assertJsonPath('data.categories.0.id', $category->id);

        $this->postJson('/api/products', [
            'name' => 'Regression Recipe Product',
            'category_id' => $category->id,
            'branch_id' => $branch->id,
            'price' => 55,
            'is_available' => true,
            'recipe_id' => $recipe->id,
        ])->assertCreated();

        $product = Product::query()
            ->where('name', 'Regression Recipe Product')
            ->with('recipe')
            ->firstOrFail();

        $this->assertSame((int) $category->id, (int) $product->category_id);
        $this->assertSame(0, (int) $product->min_stock);
        $this->assertNotNull($product->recipe);
        $this->assertSame($recipe->description, $product->recipe->description);
    }

    public function test_restaurant_owner_cannot_onboard_restaurants(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $this->postJson('/api/onboarding/restaurants', [
            'restaurant' => [
                'name' => 'Blocked Cafe',
                'kind' => 'cafe',
            ],
            'branches' => [
                ['name' => 'Blocked Branch'],
            ],
            'owner' => [
                'name' => 'Blocked Owner',
                'email' => 'blocked-owner@example.com',
            ],
        ])->assertForbidden();
    }

    public function test_backup_command_creates_database_snapshot_manifest(): void
    {
        $backupDir = storage_path('framework/testing/backups');
        File::deleteDirectory($backupDir);

        $this->artisan('pos:backup', [
            '--path' => $backupDir,
            '--keep' => 1,
        ])->assertExitCode(0);

        $manifests = collect(File::files($backupDir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.manifest.json'))
            ->values();

        $this->assertCount(1, $manifests);

        $manifest = json_decode(File::get($manifests->first()->getPathname()), true);

        $this->assertSame('sqlite', $manifest['driver']);
        $this->assertArrayHasKey('users', $manifest['tables']);
        $this->assertFileExists($manifest['archive']);

        File::deleteDirectory($backupDir);
    }

    public function test_branch_vat_configuration_recalculates_tax_without_increasing_inclusive_prices(): void
    {
        $order = Order::query()
            ->whereHas('items')
            ->with('items')
            ->firstOrFail();

        FiscalProfile::query()->updateOrCreate(
            [
                'restaurant_id' => $order->branch->restaurant_id,
                'branch_id' => $order->branch_id,
            ],
            [
                'display_name' => 'Inclusive VAT test profile',
                'currency_code' => 'EGP',
                'vat_rate' => 0.14,
                'price_includes_vat' => true,
                'eta_seller_rin' => '200173707',
                'eta_seller_name' => 'VAT Test Restaurant',
                'eta_branch_code' => 'B001',
                'eta_device_serial_number' => 'POS-001',
                'eta_activity_code' => '5610',
                'address_country' => 'EG',
                'address_governate' => 'Cairo',
                'address_region_city' => 'Cairo',
                'address_street' => 'Test Street',
                'address_building_number' => '1',
            ],
        );

        $expectedGross = round((float) $order->items->sum('total'), 2);

        $order = \App\Services\Orders\RecalculateOrder::run($order);

        $this->assertEqualsWithDelta($expectedGross, (float) $order->total, 0.001);
        $this->assertGreaterThan(0, (float) $order->tax);
        $this->assertEqualsWithDelta($expectedGross, (float) $order->subtotal + (float) $order->tax, 0.001);
    }

    public function test_owner_can_manage_effective_fiscal_profile_for_accessible_branch(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $branch = Branch::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->firstOrFail();

        FiscalProfile::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->where('branch_id', $branch->id)
            ->delete();

        Sanctum::actingAs($owner);

        $profileId = $this->postJson('/api/fiscal-profiles', [
            'display_name' => 'Owner VAT profile',
            'branch_id' => $branch->id,
            'currency_code' => 'EGP',
            'vat_rate' => 0.12,
            'price_includes_vat' => false,
            'eta_seller_rin' => '200173707',
            'eta_seller_name' => 'Owner Managed Restaurant',
            'eta_branch_code' => 'B010',
            'eta_device_serial_number' => 'POS-B010',
            'eta_activity_code' => '5610',
            'address_country' => 'EG',
            'address_governate' => 'Cairo',
            'address_region_city' => 'Nasr City',
            'address_street' => 'Abbas El Akkad',
            'address_building_number' => '10',
        ])
            ->assertCreated()
            ->assertJsonPath('data.vat_rate', '0.1200')
            ->json('data.id');

        $this->getJson("/api/fiscal-profiles/effective?branch_id={$branch->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.price_includes_vat', false);
    }

    public function test_receipt_fiscal_export_maps_to_eta_coffee_restaurant_payload(): void
    {
        $order = Order::query()
            ->with(['items', 'branch'])
            ->whereHas('items')
            ->firstOrFail();

        FiscalProfile::query()->updateOrCreate(
            [
                'restaurant_id' => $order->branch->restaurant_id,
                'branch_id' => $order->branch_id,
            ],
            [
                'display_name' => 'ETA ready profile',
                'currency_code' => 'EGP',
                'vat_rate' => 0.14,
                'price_includes_vat' => true,
                'eta_seller_rin' => '200173707',
                'eta_seller_name' => 'ETA Ready Restaurant',
                'eta_branch_code' => 'B001',
                'eta_device_serial_number' => 'POS-ETA-001',
                'eta_activity_code' => '5610',
                'address_country' => 'EG',
                'address_governate' => 'Cairo',
                'address_region_city' => 'Heliopolis',
                'address_street' => 'Fiscal Street',
                'address_building_number' => '12',
            ],
        );

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $this->get("/api/mobile/orders/{$order->id}/receipt")
            ->assertOk();

        $receipt = Receipt::query()->where('order_id', $order->id)->latest('id')->firstOrFail();

        $response = $this->getJson("/api/receipts/{$receipt->id}/fiscal-export")
            ->assertOk()
            ->assertJsonPath('eta_ready', true)
            ->assertJsonPath('submission.receipts.0.documentType.receiptType', 'SC')
            ->assertJsonPath('submission.receipts.0.documentType.typeVersion', '1.2')
            ->assertJsonPath('submission.receipts.0.seller.rin', '200173707')
            ->assertJsonPath('submission.receipts.0.seller.branchCode', 'B001')
            ->assertJsonPath('submission.receipts.0.paymentMethod', 'C');

        $payload = $response->json('submission.receipts.0');
        $this->assertNotEmpty($payload['header']['uuid']);
        $this->assertNotEmpty($payload['itemData']);
        $this->assertSame('T1', $payload['itemData'][0]['taxableItems'][0]['taxType']);
        $this->assertGreaterThan(0, (float) $payload['taxTotals'][0]['amount']);
    }

    public function test_customer_login_requires_otp_verification_before_token_issue(): void
    {
        $response = $this->postJson('/api/customer/auth/request-otp', [
            'name' => 'Phase Four Guest',
            'phone' => '01019990000',
        ])
            ->assertStatus(202)
            ->assertJsonPath('otp_required', true)
            ->assertJsonPath('verification.channel', 'sms')
            ->assertJsonPath('customer.phone', '01019990000');

        $code = $response->json('debug_otp_code');
        $this->assertNotEmpty($code);
        $this->assertDatabaseHas('customer_otp_codes', [
            'destination' => '01019990000',
            'purpose' => 'login',
        ]);

        $verify = $this->postJson('/api/customer/auth/verify-otp', [
            'phone' => '01019990000',
            'code' => $code,
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'customer'])
            ->assertJsonPath('customer.phone', '01019990000');

        $this->assertNotEmpty($verify->json('token'));
        $customer = Customer::query()->where('phone', '01019990000')->firstOrFail();
        $this->assertNotNull($customer->phone_verified_at);
        $this->assertTrue(
            CustomerOtpCode::query()
                ->where('customer_id', $customer->id)
                ->whereNotNull('consumed_at')
                ->exists()
        );
    }

    public function test_customer_otp_rejects_invalid_code_and_tracks_attempts(): void
    {
        $this->postJson('/api/customer/auth/request-otp', [
            'name' => 'Invalid Code Guest',
            'phone' => '01018880000',
        ])->assertStatus(202);

        $otp = CustomerOtpCode::query()
            ->where('destination', '01018880000')
            ->latest('id')
            ->firstOrFail();

        $this->postJson('/api/customer/auth/verify-otp', [
            'phone' => '01018880000',
            'code' => '000000',
        ])->assertUnprocessable();

        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_owner_can_select_subscription_plan_and_view_billing_context(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $plan = SubscriptionPlan::query()
            ->where('slug', 'starter-pos')
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $this->getJson('/api/billing/plans')
            ->assertOk()
            ->assertJsonPath('data.0.currency', 'EGP');

        $this->postJson('/api/billing/subscription', [
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_email' => 'billing.phase4@example.com',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan.slug', 'starter-pos')
            ->assertJsonPath('invoice.status', 'open');

        $this->assertDatabaseHas('restaurant_subscriptions', [
            'restaurant_id' => $owner->restaurant_id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $this->assertTrue(
            RestaurantSubscription::query()
                ->where('restaurant_id', $owner->restaurant_id)
                ->where('status', 'active')
                ->exists()
        );
        $this->assertTrue(
            BillingInvoice::query()
                ->where('restaurant_id', $owner->restaurant_id)
                ->where('status', 'open')
                ->exists()
        );

        $this->getJson('/api/support/context')
            ->assertOk()
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.plan.slug', 'starter-pos');

        $this->getJson('/api/billing/invoices')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->has('data')
                ->etc()
            );
    }

    public function test_eta_receipt_submission_is_queued_from_ready_fiscal_receipt(): void
    {
        $order = Order::query()
            ->with(['items', 'branch'])
            ->whereHas('items')
            ->firstOrFail();

        FiscalProfile::query()->updateOrCreate(
            [
                'restaurant_id' => $order->branch->restaurant_id,
                'branch_id' => $order->branch_id,
            ],
            [
                'display_name' => 'ETA queue ready profile',
                'currency_code' => 'EGP',
                'vat_rate' => 0.14,
                'price_includes_vat' => true,
                'eta_seller_rin' => '200173707',
                'eta_seller_name' => 'ETA Queue Restaurant',
                'eta_branch_code' => 'B001',
                'eta_device_serial_number' => 'POS-ETA-QUEUE-001',
                'eta_activity_code' => '5610',
                'address_country' => 'EG',
                'address_governate' => 'Cairo',
                'address_region_city' => 'Heliopolis',
                'address_street' => 'Fiscal Street',
                'address_building_number' => '12',
            ],
        );

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $this->get("/api/mobile/orders/{$order->id}/receipt")
            ->assertOk();

        $receipt = Receipt::query()->where('order_id', $order->id)->latest('id')->firstOrFail();

        $response = $this->postJson("/api/receipts/{$receipt->id}/eta-submissions")
            ->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.payload.receipts.0.documentType.receiptType', 'SC');

        $this->assertDatabaseHas('eta_receipt_submissions', [
            'receipt_id' => $receipt->id,
            'restaurant_id' => $order->branch->restaurant_id,
            'branch_id' => $order->branch_id,
            'status' => 'queued',
        ]);
        $this->assertNotEmpty(EtaReceiptSubmission::query()->findOrFail($response->json('data.id'))->payload['receipts'][0]['header']['uuid']);
    }

    public function test_inventory_operations_cover_receiving_transfer_count_adjustment_and_wastage(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $branches = Branch::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->orderBy('id')
            ->take(2)
            ->get();
        $this->assertCount(2, $branches);

        $sourceBranch = $branches[0];
        $targetBranch = $branches[1];
        $item = InventoryItem::query()
            ->where('branch_id', $sourceBranch->id)
            ->firstOrFail();
        $initialQuantity = (float) $item->quantity;

        Sanctum::actingAs($owner);

        $this->postJson('/api/inventory-operations/purchase-receipts', [
            'branch_id' => $sourceBranch->id,
            'reference_code' => 'PO-PHASE3-001',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 5,
                    'unit_cost' => 20,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.reference_code', 'PO-PHASE3-001')
            ->assertJsonPath('data.status', 'received');

        $item->refresh();
        $this->assertEqualsWithDelta($initialQuantity + 5, (float) $item->quantity, 0.001);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'type' => 'in',
            'source_type' => PurchaseOrder::class,
            'reference_code' => 'PO-PHASE3-001',
        ]);

        $this->postJson('/api/inventory-operations/transfers', [
            'from_branch_id' => $sourceBranch->id,
            'to_branch_id' => $targetBranch->id,
            'reference_code' => 'TR-PHASE3-001',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 2,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.reference_code', 'TR-PHASE3-001')
            ->assertJsonPath('data.status', 'completed');

        $item->refresh();
        $targetItem = InventoryItem::query()
            ->where('branch_id', $targetBranch->id)
            ->where('name', $item->name)
            ->where('unit', $item->unit)
            ->firstOrFail();
        $this->assertEqualsWithDelta($initialQuantity + 3, (float) $item->quantity, 0.001);
        $this->assertGreaterThanOrEqual(2, (float) $targetItem->quantity);
        $this->assertDatabaseHas('stock_transfers', [
            'reference_code' => 'TR-PHASE3-001',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'type' => 'out',
            'source_type' => StockTransfer::class,
            'reference_code' => 'TR-PHASE3-001',
        ]);

        $stockCountResponse = $this->postJson('/api/inventory-operations/stock-counts', [
            'branch_id' => $sourceBranch->id,
            'reference_code' => 'CNT-PHASE3-001',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'counted_quantity' => 11,
                    'reason' => 'Night count',
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.0.operation', 'stock_count');

        $this->assertEqualsWithDelta(11, (float) $stockCountResponse->json('data.0.after_quantity'), 0.001);

        $item->refresh();
        $this->assertEqualsWithDelta(11, (float) $item->quantity, 0.001);

        $this->postJson('/api/inventory-operations/wastage', [
            'branch_id' => $sourceBranch->id,
            'inventory_item_id' => $item->id,
            'quantity' => 1.5,
            'reason' => 'Spillage',
            'reference_code' => 'WST-PHASE3-001',
        ])
            ->assertCreated()
            ->assertJsonPath('data.operation', 'waste');

        $item->refresh();
        $this->assertEqualsWithDelta(9.5, (float) $item->quantity, 0.001);
        $this->assertTrue(
            InventoryTransaction::query()
                ->where('inventory_item_id', $item->id)
                ->where('reference_code', 'WST-PHASE3-001')
                ->where('type', 'out')
                ->exists()
        );
    }

    public function test_inventory_transfer_cannot_cross_restaurant_tenants(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $sourceBranch = Branch::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->firstOrFail();
        $foreignBranch = Branch::query()
            ->where('restaurant_id', '!=', $owner->restaurant_id)
            ->firstOrFail();
        $item = InventoryItem::query()
            ->where('branch_id', $sourceBranch->id)
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $this->postJson('/api/inventory-operations/transfers', [
            'from_branch_id' => $sourceBranch->id,
            'to_branch_id' => $foreignBranch->id,
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertForbidden();
    }

    public function test_mobile_sync_state_and_device_heartbeat_support_polling_and_hardware_profiles(): void
    {
        $branch = Branch::query()->whereHas('tables')->firstOrFail();
        $staff = $this->staffUserForBranch((int) $branch->id, 'waiter');

        Sanctum::actingAs($staff);

        $this->getJson('/api/mobile/sync/state?surface=waiter')
            ->assertOk()
            ->assertJsonPath('branch_id', $branch->id)
            ->assertJsonPath('polling.mode', config('broadcasting.default') === 'reverb' ? 'broadcast-preferred' : 'polling')
            ->assertJsonPath('offline.client_mutation_ids', true)
            ->assertJsonStructure([
                'server_time',
                'polling' => ['tables_seconds', 'orders_seconds', 'kds_seconds', 'retry_backoff_seconds'],
                'data' => ['tables', 'orders', 'products', 'inventory_alerts', 'devices'],
            ]);

        $this->postJson('/api/mobile/device-heartbeat', [
            'uuid' => 'phase3-waiter-terminal',
            'name' => 'Waiter Terminal 1',
            'type' => 'POS',
            'payment_provider' => 'manual',
            'printer_profile' => 'escpos-network',
            'printer_paper_width_mm' => 80,
            'printer_endpoint' => 'tcp://192.168.1.20:9100',
            'capabilities' => [
                'cash_drawer' => true,
                'qr_printing' => true,
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.uuid', 'phase3-waiter-terminal')
            ->assertJsonPath('data.printer_profile', 'escpos-network')
            ->assertJsonPath('data.capabilities.cash_drawer', true);

        $device = Device::query()->where('uuid', 'phase3-waiter-terminal')->firstOrFail();
        $this->assertSame($branch->id, $device->branch_id);
        $this->assertNotNull($device->last_seen_at);
    }

    public function test_mobile_client_mutation_ids_replay_payment_without_double_charging(): void
    {
        $order = Order::query()
            ->whereHas('items')
            ->whereDoesntHave('payments')
            ->firstOrFail();
        $order->forceFill([
            'status' => 'cashier',
            'payment_status' => 'unpaid',
        ])->save();

        Sanctum::actingAs($this->staffUserForBranch((int) $order->branch_id, 'cashier'));

        $payload = [
            'payments' => [
                ['method' => 'cash', 'amount' => 10],
            ],
        ];
        $mutationId = 'phase5-pay-'.$order->id;

        $this->withHeader('X-Client-Mutation-Id', $mutationId)
            ->postJson("/api/mobile/orders/{$order->id}/pay", $payload)
            ->assertOk()
            ->assertHeader('X-Client-Mutation-Id', $mutationId);

        $this->assertSame(1, Payment::query()->where('client_mutation_id', $mutationId)->count());
        $this->assertDatabaseHas('client_mutations', [
            'client_mutation_id' => $mutationId,
            'status' => 'succeeded',
        ]);

        $this->withHeader('X-Client-Mutation-Id', $mutationId)
            ->postJson("/api/mobile/orders/{$order->id}/pay", $payload)
            ->assertOk()
            ->assertHeader('X-Client-Mutation-Replayed', 'true');

        $this->assertSame(1, Payment::query()->where('client_mutation_id', $mutationId)->count());
        $this->assertSame(1, ClientMutation::query()->where('client_mutation_id', $mutationId)->count());
    }

    public function test_phase_five_payment_provider_attempt_print_job_and_ops_snapshot(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();
        $branch = Branch::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->whereHas('orders.items')
            ->firstOrFail();
        $order = Order::query()
            ->where('branch_id', $branch->id)
            ->whereHas('items')
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $this->postJson('/api/payment-providers', [
            'restaurant_id' => $owner->restaurant_id,
            'branch_id' => $branch->id,
            'provider' => 'manual-terminal',
            'display_name' => 'Counter terminal',
            'mode' => 'terminal',
            'supported_methods' => ['card', 'wallet'],
            'terminal_config' => [
                'capture_strategy' => 'cashier_confirms_terminal_result',
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.provider', 'manual-terminal')
            ->assertJsonPath('data.mode', 'terminal');

        $this->assertTrue(
            PaymentProviderConfig::query()
                ->where('restaurant_id', $owner->restaurant_id)
                ->where('branch_id', $branch->id)
                ->where('provider', 'manual-terminal')
                ->exists()
        );

        Sanctum::actingAs($this->staffUserForBranch((int) $branch->id, 'cashier'));

        $this->postJson('/api/mobile/device-heartbeat', [
            'uuid' => 'phase5-counter-terminal',
            'name' => 'Counter terminal',
            'type' => 'POS',
            'payment_provider' => 'manual-terminal',
            'printer_profile' => 'escpos-network',
            'printer_endpoint' => 'tcp://192.168.1.30:9100',
            'capabilities' => [
                'card_terminal' => true,
                'receipt_printer' => true,
            ],
        ])->assertOk();

        $attempt = $this->withHeader('X-Client-Mutation-Id', 'phase5-payment-attempt-'.$order->id)
            ->postJson('/api/mobile/payment-attempts', [
                'order_id' => $order->id,
                'device_uuid' => 'phase5-counter-terminal',
                'provider' => 'manual-terminal',
                'method' => 'card',
                'amount' => 25,
                'provider_reference' => 'TERM-APPROVED-001',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.provider_reference', 'TERM-APPROVED-001')
            ->json('data.id');

        $this->assertTrue(PaymentAttempt::query()->whereKey($attempt)->where('status', 'approved')->exists());

        $receipt = Receipt::query()->create([
            'order_id' => $order->id,
            'receipt_number' => 'PHASE5-'.$order->id,
            'content' => json_encode([
                'order_id' => $order->id,
                'scope' => 'full',
                'item_ids' => $order->items()->pluck('id')->all(),
                'lines' => [],
                'total' => (float) $order->total,
                'created_at' => now()->toISOString(),
            ]),
        ]);

        $printJobId = $this->withHeader('X-Client-Mutation-Id', 'phase5-print-'.$receipt->id)
            ->postJson('/api/mobile/print-jobs', [
                'receipt_id' => $receipt->id,
                'device_uuid' => 'phase5-counter-terminal',
                'type' => 'receipt',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.printer_profile', 'escpos-network')
            ->json('data.id');

        $this->withHeader('X-Client-Mutation-Id', 'phase5-claim-'.$receipt->id)
            ->postJson('/api/mobile/print-jobs/claim', [
            'device_uuid' => 'phase5-counter-terminal',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $printJobId)
            ->assertJsonPath('data.status', 'printing');

        $this->withHeader('X-Client-Mutation-Id', 'phase5-print-status-'.$receipt->id)
            ->patchJson("/api/mobile/print-jobs/{$printJobId}", [
            'status' => 'printed',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'printed');

        $this->assertTrue(PrintJob::query()->whereKey($printJobId)->whereNotNull('printed_at')->exists());

        Sanctum::actingAs($owner);

        $ops = $this->getJson('/api/support/ops')
            ->assertOk()
            ->assertJsonPath('payments.approved', 1)
            ->assertJsonPath('print_jobs.queued', 0);
        $this->assertGreaterThanOrEqual(1, (int) $ops->json('devices.active'));
    }

    public function test_restaurant_owner_cannot_read_or_write_foreign_products(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();

        $foreignProduct = Product::query()
            ->whereHas('branch', fn ($query) => $query->where('restaurant_id', '!=', $owner->restaurant_id))
            ->firstOrFail();

        $foreignCategory = Category::query()
            ->where('branch_id', $foreignProduct->branch_id)
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/products?branch_id={$foreignProduct->branch_id}")
            ->assertOk();

        $this->assertFalse(
            collect($response->json('data'))->contains(fn ($row) => (int) $row['id'] === (int) $foreignProduct->id),
            'Foreign product leaked into the owner-scoped product listing.'
        );

        $this->getJson("/api/products/{$foreignProduct->id}")
            ->assertNotFound();

        $this->postJson('/api/products', [
            'name' => 'Foreign Branch Latte',
            'category_id' => $foreignCategory->id,
            'branch_id' => $foreignProduct->branch_id,
            'price' => 75,
            'is_available' => true,
            'min_stock' => 1,
        ])->assertForbidden();
    }

    public function test_restaurant_owner_cannot_read_or_write_foreign_suppliers(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();

        $foreignSupplier = Supplier::query()
            ->whereNotNull('restaurant_id')
            ->where('restaurant_id', '!=', $owner->restaurant_id)
            ->firstOrFail();

        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/suppliers')
            ->assertOk();

        $this->assertFalse(
            collect($response->json())->contains(fn ($row) => (int) $row['id'] === (int) $foreignSupplier->id),
            'Foreign supplier leaked into the owner-scoped supplier listing.'
        );

        $this->getJson("/api/suppliers/{$foreignSupplier->id}")
            ->assertNotFound();

        $this->postJson('/api/suppliers', [
            'name' => 'Foreign Supplier',
            'email' => 'foreign-supplier@example.com',
            'restaurant_id' => $foreignSupplier->restaurant_id,
        ])->assertForbidden();
    }

    public function test_staff_without_manage_permission_cannot_mutate_products(): void
    {
        $product = Product::query()->firstOrFail();
        $category = Category::query()
            ->where('branch_id', $product->branch_id)
            ->firstOrFail();

        Sanctum::actingAs($this->staffUserForBranch((int) $product->branch_id, 'cashier'));

        $this->postJson('/api/products', [
            'name' => 'Permission Check Product',
            'category_id' => $category->id,
            'branch_id' => $product->branch_id,
            'price' => 45,
            'is_available' => true,
            'min_stock' => 1,
        ])->assertForbidden();
    }

    private function staffUserForBranch(int $branchId, string $prefix): User
    {
        return User::query()
            ->where('branch_id', $branchId)
            ->where('email', 'like', "{$prefix}%@example.com")
            ->firstOrFail();
    }
}
