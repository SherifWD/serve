<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $receiptCount = Receipt::query()->count();

        $this->get("/api/mobile/orders/{$order->id}/receipt?scope=last")
            ->assertOk();

        $this->assertSame($receiptCount, Receipt::query()->count());
    }

    public function test_waiter_can_return_item_to_kitchen(): void
    {
        $item = OrderItem::query()
            ->with('order.table')
            ->whereHas('order.table')
            ->firstOrFail();

        Sanctum::actingAs($this->staffUserForBranch((int) $item->order->branch_id, 'waiter'));

        $this->patchJson("/api/mobile/order-items/{$item->id}/refund-change", [
            'action' => 'return',
            'note' => 'Returned for remake',
        ])->assertOk();

        $item->refresh();

        $this->assertSame('returned', $item->status);
        $this->assertSame('returned', $item->kds_status);
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

    public function test_owner_can_download_date_range_receipt(): void
    {
        Sanctum::actingAs(User::query()->where('email', 'admin@restaurant-suite.com')->firstOrFail());

        $this->get('/api/dashboard/receipt?preset=month')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function staffUserForBranch(int $branchId, string $prefix): User
    {
        return User::query()
            ->where('branch_id', $branchId)
            ->where('email', 'like', "{$prefix}%@example.com")
            ->firstOrFail();
    }
}
