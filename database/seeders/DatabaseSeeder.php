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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $roles = [
            'owner' => Role::firstOrCreate(['name' => 'owner']),
            'supervisor' => Role::firstOrCreate(['name' => 'supervisor']),
            'staff' => Role::firstOrCreate(['name' => 'staff']),
            'employee' => Role::firstOrCreate(['name' => 'employee']),
        ];

        // Create Owner
        $owner = User::factory()->create([
            'name' => 'Main Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role' => 'owner'
        ]);
        $owner->roles()->sync([$roles['owner']->id]);
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
            $supervisor->roles()->sync([$roles['supervisor']->id]);

            $staff = User::factory(2)->create([
                'role' => 'staff',
                'branch_id' => $branch->id,
            ]);
            foreach ($staff as $s) {
                $s->roles()->sync([$roles['staff']->id]);
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

        // Add more if needed...
    }
}
