<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\IngredientBranch;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\PrintJob;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosProductionScenarioTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_named_production_scenarios_generate_receipts_and_complete_print_jobs(): void
    {
        $cafe = $this->branchForKind('cafe');
        $restaurant = $this->branchForKind('restaurant');

        $outcomes = [
            $this->runStaffScenario('small_cafe_dine_in', $cafe, 'dine-in', true),
            $this->runStaffScenario('big_cafe_split_tender', $cafe, 'dine-in', true, splitTender: true),
            $this->runStaffScenario('drinks_only_cashier_takeaway', $cafe, 'takeaway', false, creatorPrefix: 'cashier', useTable: false),
            $this->runStaffScenario('takeaway_cafe_with_barista_kds', $cafe, 'takeaway', true, creatorPrefix: 'cashier'),
            $this->runStaffScenario('small_restaurant_dine_in', $restaurant, 'dine-in', true),
            $this->runStaffScenario('big_restaurant_table_move', $restaurant, 'dine-in', true, moveTable: true, splitTender: true),
            $this->runCustomerTakeawayScenario('takeaway_restaurant_customer_pickup', $restaurant),
        ];

        $this->assertCount(7, $outcomes);

        foreach ($outcomes as $outcome) {
            $this->assertSame('paid', $outcome['order_status'], $outcome['scenario']);
            $this->assertSame('paid', $outcome['payment_status'], $outcome['scenario']);
            $this->assertSame('printed', $outcome['print_status'], $outcome['scenario']);
            $this->assertGreaterThan(0, $outcome['receipt_total'], $outcome['scenario']);
        }

        $dineInOutcomes = collect($outcomes)->where('order_type', 'dine-in');
        $this->assertTrue($dineInOutcomes->every(fn (array $outcome) => $outcome['table_status_after_payment'] === 'open'));

        $cashierOnly = collect($outcomes)->firstWhere('scenario', 'drinks_only_cashier_takeaway');
        $this->assertNull($cashierOnly['table_status_after_payment']);

        $customerTakeaway = collect($outcomes)->firstWhere('scenario', 'takeaway_restaurant_customer_pickup');
        $this->assertNull($customerTakeaway['table_status_after_payment']);
    }

    private function runStaffScenario(
        string $scenario,
        Branch $branch,
        string $orderType,
        bool $useKitchen,
        bool $moveTable = false,
        bool $splitTender = false,
        string $creatorPrefix = 'waiter',
        bool $useTable = true,
    ): array {
        $table = $useTable
            ? Table::query()->create([
                'branch_id' => $branch->id,
                'name' => "Scenario {$scenario}",
                'status' => 'open',
                'seats' => 4,
            ])
            : null;
        $product = $this->availableProduct($branch);
        $quantity = $splitTender ? 2 : 1;
        $this->ensureProductStockAvailable($product, (int) $branch->id, 1000);

        Sanctum::actingAs($this->staffUserForBranch((int) $branch->id, $creatorPrefix));

        $payload = [
            'branch_id' => $branch->id,
            'order_type' => $orderType,
            'customer_name' => "Scenario {$scenario}",
            'customer_phone' => '010'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'note' => "Scenario {$scenario}",
                ],
            ],
        ];

        if ($table) {
            $payload['table_id'] = $table->id;
        }

        $response = $this->postJson('/api/mobile/orders', $payload)->assertStatus(201);

        $order = Order::query()->with(['items', 'table'])->findOrFail($response->json('order.id'));

        if ($moveTable) {
            $target = Table::query()->create([
                'branch_id' => $branch->id,
                'name' => "Scenario moved {$scenario}",
                'status' => 'open',
                'seats' => 6,
            ]);

            Sanctum::actingAs($this->staffUserForBranch((int) $branch->id, 'waiter'));
            $this->patchJson("/api/mobile/tables/{$table->id}/move", [
                'to_table_id' => $target->id,
            ])->assertOk();

            $order = Order::query()
                ->with(['items', 'table'])
                ->where('table_id', $target->id)
                ->whereIn('status', ['pending', 'open', 'running'])
                ->latest('id')
                ->firstOrFail();
        }

        if ($useKitchen) {
            $this->sendThroughKitchenToCashier($order);
        }

        return $this->payReceiptAndPrint($scenario, $order->fresh(['items', 'table']), $splitTender);
    }

    private function runCustomerTakeawayScenario(string $scenario, Branch $branch): array
    {
        $customer = Customer::query()->create([
            'name' => 'Scenario Customer',
            'phone' => '011'.str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
            'phone_verified_at' => now(),
        ]);
        $product = $this->availableProduct($branch);
        $this->ensureProductStockAvailable($product, (int) $branch->id, 1000);

        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/customer/orders', [
            'branch_id' => $branch->id,
            'order_type' => 'takeaway',
            'payment_method' => 'pay_at_counter',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'note' => "Scenario {$scenario}",
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Order placed for branch confirmation.');

        $order = Order::query()->with(['items', 'table'])->findOrFail($response->json('data.id'));
        $this->assertNull($order->table_id);

        $this->sendThroughKitchenToCashier($order);

        return $this->payReceiptAndPrint($scenario, $order->fresh(['items', 'table']), false);
    }

    private function sendThroughKitchenToCashier(Order $order): void
    {
        $branchId = (int) $order->branch_id;

        Sanctum::actingAs($this->staffUserForBranch($branchId, 'waiter'));
        $this->postJson("/api/mobile/orders/{$order->id}/send-to-kds")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $order->refresh()->load('items');

        Sanctum::actingAs($this->staffUserForBranch($branchId, 'kitchen'));
        foreach ($order->items as $item) {
            $this->patchJson("/api/mobile/kds/order-items/{$item->id}", [
                'status' => 'ready',
            ])
                ->assertOk()
                ->assertJsonPath('item.kds_status', 'ready');
        }

        Sanctum::actingAs($this->staffUserForBranch($branchId, 'waiter'));
        $this->patchJson("/api/mobile/orders/{$order->id}/send-to-cashier")
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    private function payReceiptAndPrint(string $scenario, Order $order, bool $splitTender): array
    {
        $branchId = (int) $order->branch_id;
        $cashier = $this->staffUserForBranch($branchId, 'cashier');
        $deviceUuid = "scenario-printer-{$order->id}";

        Sanctum::actingAs($cashier);
        $this->postJson('/api/mobile/device-heartbeat', [
            'uuid' => $deviceUuid,
            'name' => "Scenario printer {$order->id}",
            'type' => 'Receipt Printer',
            'printer_profile' => 'escpos-network',
            'printer_endpoint' => 'tcp://192.168.10.'.$order->id.':9100',
            'capabilities' => [
                'receipt_printer' => true,
            ],
        ])->assertOk();

        $total = round((float) $order->fresh()->total, 2);
        $payments = $splitTender
            ? [
                ['method' => 'cash', 'amount' => round($total / 2, 2)],
                ['method' => 'card', 'amount' => round($total - round($total / 2, 2), 2)],
            ]
            : [
                ['method' => 'cash', 'amount' => $total],
            ];

        $this->postJson("/api/mobile/orders/{$order->id}/pay", [
            'payments' => $payments,
        ])
            ->assertOk()
            ->assertJsonPath('order.status', 'paid')
            ->assertJsonPath('order.payment_status', 'paid');

        $this->get("/api/mobile/orders/{$order->id}/receipt?scope=paid")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $receipt = Receipt::query()->where('order_id', $order->id)->latest('id')->firstOrFail();
        $receiptContent = json_decode((string) $receipt->content, true);

        $printJobId = $this->postJson('/api/mobile/print-jobs', [
            'receipt_id' => $receipt->id,
            'device_uuid' => $deviceUuid,
            'type' => 'receipt',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->json('data.id');

        $this->postJson('/api/mobile/print-jobs/claim', [
            'device_uuid' => $deviceUuid,
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $printJobId)
            ->assertJsonPath('data.status', 'printing');

        $this->patchJson("/api/mobile/print-jobs/{$printJobId}", [
            'status' => 'printed',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'printed');

        $printJob = PrintJob::query()->findOrFail($printJobId);
        $order->refresh()->load('table');

        return [
            'scenario' => $scenario,
            'order_id' => $order->id,
            'order_type' => $order->order_type,
            'order_status' => $order->status,
            'payment_status' => $order->payment_status,
            'receipt_number' => $receipt->receipt_number,
            'receipt_total' => (float) ($receiptContent['total'] ?? 0),
            'print_job_id' => $printJob->id,
            'print_status' => $printJob->status,
            'table_status_after_payment' => $order->table?->status,
        ];
    }

    private function branchForKind(string $kind): Branch
    {
        return Branch::query()
            ->whereHas('restaurant', fn ($query) => $query->where('kind', $kind))
            ->whereHas('products', fn ($query) => $query->where('is_available', true))
            ->whereHas('users', fn ($query) => $query->where('email', 'like', 'waiter%@example.com'))
            ->whereHas('users', fn ($query) => $query->where('email', 'like', 'cashier%@example.com'))
            ->whereHas('users', fn ($query) => $query->where('email', 'like', 'kitchen%@example.com'))
            ->firstOr(function () {
                return Branch::query()
                    ->whereHas('products', fn ($query) => $query->where('is_available', true))
                    ->whereHas('users', fn ($query) => $query->where('email', 'like', 'waiter%@example.com'))
                    ->whereHas('users', fn ($query) => $query->where('email', 'like', 'cashier%@example.com'))
                    ->whereHas('users', fn ($query) => $query->where('email', 'like', 'kitchen%@example.com'))
                    ->firstOrFail();
            });
    }

    private function availableProduct(Branch $branch): Product
    {
        return Product::query()
            ->where('branch_id', $branch->id)
            ->where('is_available', true)
            ->firstOrFail();
    }

    private function staffUserForBranch(int $branchId, string $prefix): User
    {
        return User::query()
            ->where('branch_id', $branchId)
            ->where('email', 'like', "{$prefix}%@example.com")
            ->firstOrFail();
    }

    private function ensureProductStockAvailable(Product $product, int $branchId, float $quantity): void
    {
        $product->loadMissing('recipe.ingredients');

        if ($product->recipe && $product->recipe->ingredients->isNotEmpty()) {
            foreach ($product->recipe->ingredients as $ingredient) {
                IngredientBranch::query()->updateOrCreate(
                    ['ingredient_id' => $ingredient->id, 'branch_id' => $branchId],
                    ['stock' => $quantity],
                );

                InventoryItem::query()->updateOrCreate(
                    ['branch_id' => $branchId, 'ingredient_id' => $ingredient->id],
                    [
                        'name' => $ingredient->name,
                        'unit' => $ingredient->unit ?? 'unit',
                        'quantity' => $quantity,
                        'min_stock' => 1,
                    ],
                );
            }

            return;
        }

        $product->stock = max((int) $product->stock, (int) $quantity);
        $product->save();

        InventoryItem::query()->updateOrCreate(
            ['branch_id' => $branchId, 'product_id' => $product->id],
            [
                'name' => $product->name,
                'unit' => 'each',
                'quantity' => $quantity,
                'min_stock' => 1,
            ],
        );
    }
}
