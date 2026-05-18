<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = DatabaseSeeder::class;

    public function test_platform_admin_can_read_customers_without_purchases(): void
    {
        $admin = User::query()->where('role', 'admin')->firstOrFail();
        $customer = Customer::query()->create([
            'name' => 'No Order Guest',
            'email' => 'no-order-guest@example.com',
            'phone' => '01015550000',
            'loyalty_points' => 0,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/customers?search=No%20Order%20Guest')
            ->assertOk();

        $match = collect($response->json('data'))->firstWhere('id', $customer->id);

        $this->assertNotNull($match);
        $this->assertSame(0, $match['purchases_count']);
        $this->assertSame([], $match['orders']);
    }

    public function test_owner_customer_directory_is_scoped_and_purchase_filters_are_applied(): void
    {
        $owner = User::query()
            ->where('role', 'owner')
            ->whereNotNull('restaurant_id')
            ->firstOrFail();

        $branch = Branch::query()
            ->where('restaurant_id', $owner->restaurant_id)
            ->firstOrFail();

        $foreignRestaurant = Restaurant::query()->create([
            'name' => 'Foreign Test Cafe',
            'kind' => 'cafe',
        ]);
        $foreignBranch = Branch::query()->create([
            'restaurant_id' => $foreignRestaurant->id,
            'name' => 'Foreign Test Branch',
            'location' => 'Outside owner scope',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Scoped Diner',
            'email' => 'scoped-diner@example.com',
            'phone' => '01016660000',
            'loyalty_points' => 4,
        ]);

        $foreignCustomer = Customer::query()->create([
            'name' => 'Scoped Foreign Diner',
            'email' => 'scoped-foreign-diner@example.com',
            'phone' => '01017770000',
            'loyalty_points' => 0,
        ]);

        Order::query()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'order_type' => 'dine-in',
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 130,
            'tax' => 20,
            'discount' => 0,
            'total' => 150,
            'order_date' => '2026-05-10',
        ]);

        Order::query()->create([
            'branch_id' => $foreignBranch->id,
            'customer_id' => $foreignCustomer->id,
            'order_type' => 'dine-in',
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 870,
            'tax' => 129,
            'discount' => 0,
            'total' => 999,
            'order_date' => '2026-05-10',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson(
            "/api/customers?search=Scoped&branch_id={$branch->id}&min_bill=100&max_bill=200&from_date=2026-05-01&to_date=2026-05-31&payment_status=paid"
        )->assertOk();

        $payload = collect($response->json('data'));
        $match = $payload->firstWhere('id', $customer->id);

        $this->assertNotNull($match);
        $this->assertNull($payload->firstWhere('id', $foreignCustomer->id));
        $this->assertSame(1, $match['purchases_count']);
        $this->assertEquals(150.0, $match['total_spent']);
        $this->assertSame([$branch->id], collect($match['orders'])->pluck('branch_id')->unique()->values()->all());
    }
}
