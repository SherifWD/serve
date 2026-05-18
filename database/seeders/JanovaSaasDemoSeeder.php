<?php

namespace Database\Seeders;

use App\Models\BillingInvoice;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\CategoryChoice;
use App\Models\CategoryQuestion;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeePerformance;
use App\Models\FiscalProfile;
use App\Models\Ingredient;
use App\Models\IngredientBranch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Menu;
use App\Models\Modifier;
use App\Models\PaymentProviderConfig;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Restaurant;
use App\Models\RestaurantSubscription;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Type;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\Concerns\UsesOnlineProductImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class JanovaSaasDemoSeeder extends Seeder
{
    use UsesOnlineProductImages;

    private const JANOVA_LOGO_URL = 'images/janova-logo.svg';

    private array $roleIds = [];

    private array $typeIds = [];

    private Collection $ingredients;

    public function run(): void
    {
        $this->ingredients = collect();

        DB::transaction(function (): void {
            $this->seedReferenceData();

            $restaurant = Restaurant::query()->updateOrCreate(
                ['name' => 'Janova Restaurant'],
                [
                    'kind' => 'restaurant',
                    'logo_url' => self::JANOVA_LOGO_URL,
                ],
            );

            $branches = collect($this->branchBlueprints())
                ->map(function (array $branchData) use ($restaurant): array {
                    $branch = Branch::query()->updateOrCreate(
                        ['restaurant_id' => $restaurant->id, 'name' => $branchData['name']],
                        ['location' => $branchData['location']],
                    );

                    return ['model' => $branch, 'data' => $branchData];
                });

            $this->seedSuppliers($restaurant);

            foreach ($branches as $branchContext) {
                $this->seedSaasState($restaurant, $branchContext['model'], $branchContext['data']);
                $this->seedStaff($restaurant, $branchContext['model'], $branchContext['data']);
                $this->seedTablesAndDevices($restaurant, $branchContext['model'], $branchContext['data']);
                $this->seedCatalog($restaurant, $branchContext['model'], $branchContext['data']);
            }

            $this->syncSeededProductImages();
        });
    }

    private function seedReferenceData(): void
    {
        foreach (['admin', 'owner', 'supervisor', 'staff', 'employee'] as $roleName) {
            $role = Role::query()->firstOrCreate(['name' => $roleName]);
            $this->roleIds[$roleName] = $role->id;
        }

        foreach (['owner', 'waiter', 'cashier', 'kitchen'] as $typeName) {
            $type = Type::query()->firstOrCreate(['name' => $typeName]);
            $this->typeIds[$typeName] = $type->id;
        }

        $permissionNames = [
            'platform.restaurants.manage',
            'platform.branches.manage',
            'platform.users.manage',
            'platform.roles.manage',
            'dashboard.view',
            'branches.view',
            'branches.manage',
            'users.view',
            'users.manage',
            'roles.view',
            'orders.view',
            'orders.manage',
            'customers.view',
            'customers.manage',
            'cashier.manage',
            'kds.manage',
            'tables.view',
            'tables.manage',
            'menu.view',
            'menu.manage',
            'categories.view',
            'categories.manage',
            'products.view',
            'products.manage',
            'inventory.view',
            'inventory.manage',
            'suppliers.view',
            'suppliers.manage',
            'ingredients.view',
            'ingredients.manage',
            'recipes.view',
            'recipes.manage',
            'employees.view',
            'employees.manage',
            'settings.view',
            'settings.manage',
        ];

        foreach ($permissionNames as $permissionName) {
            Permission::query()->firstOrCreate(['name' => $permissionName]);
        }

        $rolePermissions = [
            'admin' => $permissionNames,
            'owner' => array_values(array_filter($permissionNames, fn ($name) => ! str_starts_with($name, 'platform.'))),
            'supervisor' => [
                'dashboard.view',
                'branches.view',
                'users.view',
                'roles.view',
                'orders.view',
                'orders.manage',
                'cashier.manage',
                'kds.manage',
                'tables.view',
                'tables.manage',
                'menu.view',
                'categories.view',
                'products.view',
                'inventory.view',
                'suppliers.view',
                'ingredients.view',
                'recipes.view',
                'employees.view',
            ],
            'staff' => [
                'orders.view',
                'orders.manage',
                'cashier.manage',
                'kds.manage',
                'tables.view',
                'tables.manage',
                'menu.view',
                'categories.view',
                'products.view',
                'ingredients.view',
                'inventory.view',
            ],
            'employee' => ['dashboard.view'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $permissionIds = Permission::query()->whereIn('name', $permissions)->pluck('id')->all();
            Role::query()->find($this->roleIds[$roleName])?->permissions()->sync($permissionIds);
        }

        Setting::query()->updateOrCreate(['key' => 'currency'], ['value' => 'USD']);
        Setting::query()->updateOrCreate(['key' => 'vat_rate'], ['value' => '0.14']);
    }

    private function branchBlueprints(): array
    {
        return [
            [
                'name' => 'Alexandria',
                'location' => 'Alexandria Corniche',
                'slug' => 'alexandria',
                'code' => 'ALX',
                'governate' => 'Alexandria',
                'city' => 'Alexandria',
                'street' => 'Corniche Road',
                'building' => '12',
                'opening_balance' => 5000,
            ],
            [
                'name' => 'New Cairo',
                'location' => 'New Cairo, Cairo',
                'slug' => 'new-cairo',
                'code' => 'NCA',
                'governate' => 'Cairo',
                'city' => 'New Cairo',
                'street' => 'Road 90',
                'building' => '24',
                'opening_balance' => 4500,
            ],
        ];
    }

    private function seedSaasState(Restaurant $restaurant, Branch $branch, array $branchData): void
    {
        $plan = SubscriptionPlan::query()->updateOrCreate(
            ['slug' => 'janova-growth'],
            [
                'name' => 'Janova Growth Restaurant',
                'billing_period' => 'monthly',
                'currency' => 'USD',
                'price' => 2999,
                'max_branches' => 3,
                'max_users' => 25,
                'max_devices' => 12,
                'features' => [
                    'waiter_app',
                    'cashier_app',
                    'kds',
                    'advanced_inventory',
                    'branch_reporting',
                    'fiscal_exports',
                ],
                'is_active' => true,
            ],
        );

        $subscription = RestaurantSubscription::query()->updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'subscription_plan_id' => $plan->id],
            [
                'status' => 'active',
                'trial_ends_at' => now()->subDays(1),
                'current_period_starts_at' => now()->startOfMonth(),
                'current_period_ends_at' => now()->endOfMonth(),
                'next_invoice_at' => now()->addMonth()->startOfMonth(),
                'billing_email' => 'billing@janova.example.com',
                'metadata' => ['seed' => 'janova-saas-demo'],
            ],
        );

        BillingInvoice::query()->updateOrCreate(
            ['invoice_number' => 'JANOVA-'.now()->format('Ym').'-001'],
            [
                'restaurant_id' => $restaurant->id,
                'restaurant_subscription_id' => $subscription->id,
                'status' => 'paid',
                'currency' => 'USD',
                'subtotal' => 2999,
                'tax' => 419.86,
                'total' => 3418.86,
                'due_date' => now()->startOfMonth()->addDays(7)->toDateString(),
                'paid_at' => now()->subDays(2),
                'line_items' => [
                    [
                        'description' => 'Janova Growth Restaurant monthly subscription',
                        'quantity' => 1,
                        'unit_amount' => 2999,
                        'total' => 2999,
                    ],
                ],
                'metadata' => ['seed' => 'janova-saas-demo'],
            ],
        );

        FiscalProfile::query()->updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'branch_id' => $branch->id],
            [
                'display_name' => "Janova {$branch->name} VAT profile",
                'is_default' => $branchData['code'] === 'ALX',
                'currency_code' => 'USD',
                'vat_rate' => 0.14,
                'price_includes_vat' => true,
                'vat_tax_type' => 'T1',
                'vat_subtype' => 'V009',
                'buyer_id_threshold' => 150000,
                'default_payment_method_code' => 'C',
                'eta_receipt_type' => 'SC',
                'eta_type_version' => '1.2',
                'eta_seller_rin' => '200173707',
                'eta_seller_name' => $restaurant->name,
                'eta_branch_code' => $branchData['code'],
                'eta_device_serial_number' => "JANOVA-{$branchData['code']}-POS-01",
                'eta_activity_code' => '5610',
                'address_country' => 'EG',
                'address_governate' => $branchData['governate'],
                'address_region_city' => $branchData['city'],
                'address_street' => $branchData['street'],
                'address_building_number' => $branchData['building'],
            ],
        );

        PaymentProviderConfig::query()->updateOrCreate(
            ['restaurant_id' => $restaurant->id, 'branch_id' => $branch->id, 'provider' => 'manual'],
            [
                'display_name' => 'Janova Manual POS',
                'mode' => 'manual',
                'is_active' => true,
                'supported_methods' => ['cash', 'card'],
                'terminal_config' => ['receipt_printer' => "JANOVA-{$branchData['code']}-PRINTER"],
                'metadata' => ['seed' => 'janova-saas-demo'],
            ],
        );
    }

    private function seedStaff(Restaurant $restaurant, Branch $branch, array $branchData): void
    {
        $slug = $branchData['slug'];

        $owner = $this->upsertUser(
            restaurant: $restaurant,
            branch: null,
            email: 'owner@janova.example.com',
            name: 'Janova Owner',
            role: 'owner',
            type: 'owner',
        );

        $this->upsertUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "supervisor.{$slug}@janova.example.com",
            name: "Janova {$branch->name} Supervisor",
            role: 'supervisor',
            type: null,
            position: 'Branch Supervisor',
            salary: 15500,
        );

        foreach ([
            1,
            2,
        ] as $index) {
            $this->upsertUser(
                restaurant: $restaurant,
                branch: $branch,
                email: "waiter{$index}.{$slug}@janova.example.com",
                name: "Janova {$branch->name} Waiter {$index}",
                role: 'staff',
                type: 'waiter',
                position: 'Waiter',
                salary: 8500,
            );
        }

        $this->upsertUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "cashier.{$slug}@janova.example.com",
            name: "Janova {$branch->name} Cashier",
            role: 'staff',
            type: 'cashier',
            position: 'Cashier',
            salary: 9200,
        );

        foreach ([
            ['email' => "kitchen1.{$slug}@janova.example.com", 'name' => "Janova {$branch->name} Kitchen Lead", 'position' => 'Kitchen Lead', 'salary' => 11800],
            ['email' => "kitchen2.{$slug}@janova.example.com", 'name' => "Janova {$branch->name} Line Cook", 'position' => 'Line Cook', 'salary' => 9200],
        ] as $staff) {
            $this->upsertUser(
                restaurant: $restaurant,
                branch: $branch,
                email: $staff['email'],
                name: $staff['name'],
                role: 'staff',
                type: 'kitchen',
                position: $staff['position'],
                salary: $staff['salary'],
            );
        }

        Employee::query()->firstOrCreate(
            ['user_id' => $owner->id],
            [
                'branch_id' => $branch->id,
                'name' => $owner->name,
                'position' => 'Restaurant Owner',
                'base_salary' => 0,
                'hired_at' => CarbonImmutable::now()->subYear()->toDateString(),
            ],
        );
    }

    private function upsertUser(
        Restaurant $restaurant,
        ?Branch $branch,
        string $email,
        string $name,
        string $role,
        ?string $type,
        ?string $position = null,
        float $salary = 0,
    ): User {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make('password');
        $user->role = $role;
        $user->restaurant_id = $restaurant->id;
        $user->branch_id = $branch?->id;
        $user->save();

        $user->roles()->sync([$this->roleIds[$role]]);
        if ($type !== null) {
            $user->types()->sync([$this->typeIds[$type]]);
        }

        if ($branch && $position) {
            $employee = Employee::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $branch->id,
                    'name' => $user->name,
                    'position' => $position,
                    'base_salary' => $salary,
                    'hired_at' => CarbonImmutable::now()->subMonths(8)->toDateString(),
                ],
            );

            EmployeePerformance::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'metric' => 'orders_handled'],
                ['value' => 95 + ($employee->id % 40), 'recorded_at' => now()->subDay()->toDateString()],
            );
            EmployeePerformance::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'metric' => 'guest_rating'],
                ['value' => 4.4 + (($employee->id % 4) * 0.1), 'recorded_at' => now()->subDay()->toDateString()],
            );
        }

        return $user;
    }

    private function seedTablesAndDevices(Restaurant $restaurant, Branch $branch, array $branchData): void
    {
        foreach ([
            ['name' => 'A1 Window', 'seats' => 2],
            ['name' => 'A2 Window', 'seats' => 2],
            ['name' => 'B1 Family', 'seats' => 4],
            ['name' => 'B2 Family', 'seats' => 4],
            ['name' => 'C1 Terrace', 'seats' => 6],
            ['name' => 'C2 Terrace', 'seats' => 6],
            ['name' => 'D1 Private', 'seats' => 8],
            ['name' => 'Bar 1', 'seats' => 2],
        ] as $table) {
            Table::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'name' => $table['name']],
                ['seats' => $table['seats'], 'status' => 'open'],
            );
        }

        $code = Str::lower($branchData['code']);
        foreach ([
            ['uuid' => "janova-{$code}-pos-01", 'name' => "Janova {$branch->name} POS", 'type' => 'POS'],
            ['uuid' => "janova-{$code}-waiter-01", 'name' => "Janova {$branch->name} Waiter Tablet 1", 'type' => 'Tablet'],
            ['uuid' => "janova-{$code}-kds-01", 'name' => "Janova {$branch->name} Kitchen Display", 'type' => 'KDS'],
            [
                'uuid' => "janova-{$code}-receipt-printer-01",
                'name' => "Janova {$branch->name} Receipt Printer",
                'type' => 'Receipt Printer',
                'printer_profile' => 'epson-thermal',
                'printer_paper_width_mm' => 80,
                'printer_endpoint' => "usb://janova-{$code}-receipt-printer",
                'capabilities' => ['receipt_printer' => true],
            ],
            [
                'uuid' => "janova-{$code}-cash-drawer-01",
                'name' => "Janova {$branch->name} Cash Drawer",
                'type' => 'Cash Drawer',
                'printer_endpoint' => "usb://janova-{$code}-cash-drawer",
                'capabilities' => ['cash_drawer' => true],
            ],
        ] as $device) {
            Device::query()->updateOrCreate(
                ['uuid' => $device['uuid']],
                [
                    'name' => $device['name'],
                    'branch_id' => $branch->id,
                    'type' => $device['type'],
                    'printer_profile' => $device['printer_profile'] ?? null,
                    'printer_paper_width_mm' => $device['printer_paper_width_mm'] ?? null,
                    'printer_endpoint' => $device['printer_endpoint'] ?? null,
                    'capabilities' => $device['capabilities'] ?? null,
                    'is_active' => true,
                    'last_seen_at' => now(),
                ],
            );
        }

        CashRegister::query()->updateOrCreate(
            ['branch_id' => $branch->id],
            ['opening_balance' => $branchData['opening_balance'], 'closing_balance' => null, 'is_open' => true],
        );
    }

    private function seedSuppliers(Restaurant $restaurant): void
    {
        foreach ([
            ['name' => 'Alexandria Fresh Seafood', 'contact_person' => 'Mina Farouk', 'phone' => '01010002001', 'email' => 'seafood@janova.example.com'],
            ['name' => 'Janova Produce Market', 'contact_person' => 'Salma Adel', 'phone' => '01010002002', 'email' => 'produce@janova.example.com'],
            ['name' => 'Mediterranean Dry Goods', 'contact_person' => 'Omar Nabil', 'phone' => '01010002003', 'email' => 'drygoods@janova.example.com'],
        ] as $supplier) {
            Supplier::query()->updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'email' => $supplier['email']],
                $supplier,
            );
        }
    }

    private function seedCatalog(Restaurant $restaurant, Branch $branch, array $branchData): void
    {
        $menu = Menu::query()->updateOrCreate(
            ['branch_id' => $branch->id, 'name' => "Janova {$branch->name} Main Menu"],
            ['category_id' => null],
        );

        $categoryIds = [];
        $usedIngredientNames = collect();

        foreach ($this->catalog() as $categoryData) {
            $category = Category::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'name' => $categoryData['name']],
                [],
            );
            $categoryIds[] = $category->id;

            $this->syncQuestion($category, $categoryData['question'], $categoryData['choices']);
            $this->syncModifiers($restaurant, $category, $categoryData['modifiers']);

            foreach ($categoryData['products'] as $productData) {
                $product = Product::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'name' => $productData['name']],
                    [
                        'category_id' => $category->id,
                        'price' => $productData['price'],
                        'is_available' => true,
                        'image' => $this->productImageUrl($productData['name']),
                        'sku' => "JANOVA-{$branchData['code']}-".Str::upper(Str::slug($productData['name'], '-')),
                        'min_stock' => 10,
                        'stock' => $productData['stock'] ?? 90,
                    ],
                );

                $product->category_id = $category->id;
                $product->price = $productData['price'];
                $product->is_available = true;
                $product->image = $this->productImageUrl($productData['name']);
                $product->stock = $productData['stock'] ?? 90;
                $product->save();

                $recipe = Recipe::query()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'branch_id' => $branch->id,
                        'description' => $productData['recipe'],
                    ],
                );

                $sync = [];
                foreach ($productData['ingredients'] as $ingredientName => $quantity) {
                    $ingredient = $this->ingredient($ingredientName, $this->unitForIngredient($ingredientName));
                    $sync[$ingredient->id] = ['quantity' => $quantity];
                    $usedIngredientNames->push($ingredientName);
                }
                $recipe->ingredients()->sync($sync);

                InventoryItem::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    [
                        'ingredient_id' => null,
                        'name' => $product->name,
                        'unit' => 'each',
                        'quantity' => $product->stock,
                        'min_stock' => 10,
                    ],
                );
            }
        }

        $menu->categories()->sync($categoryIds);
        $this->seedIngredientInventory($branch, $usedIngredientNames->unique()->values());
    }

    private function syncQuestion(Category $category, string $questionText, array $choices): void
    {
        $question = CategoryQuestion::query()->updateOrCreate(
            ['category_id' => $category->id, 'question' => $questionText],
            ['image' => null],
        );

        foreach ($choices as $choiceText) {
            CategoryChoice::query()->updateOrCreate(
                ['question_id' => $question->id, 'choice' => $choiceText],
                ['image' => null],
            );
        }
    }

    private function syncModifiers(Restaurant $restaurant, Category $category, array $modifiers): void
    {
        foreach ($modifiers as $modifier) {
            Modifier::query()->updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $modifier['name'],
                ],
                [
                    'price' => $modifier['price'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedIngredientInventory(Branch $branch, Collection $ingredientNames): void
    {
        foreach ($ingredientNames as $index => $ingredientName) {
            $ingredient = $this->ingredient($ingredientName, $this->unitForIngredient($ingredientName));
            $quantity = 450 + ($index * 35);
            $minStock = 40 + ($index % 5) * 5;

            $ingredient->stock = max((float) $ingredient->stock, $quantity);
            $ingredient->min_stock = $minStock;
            $ingredient->save();

            IngredientBranch::query()->updateOrCreate(
                ['ingredient_id' => $ingredient->id, 'branch_id' => $branch->id],
                ['stock' => $quantity],
            );

            $inventoryItem = InventoryItem::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'ingredient_id' => $ingredient->id],
                [
                    'product_id' => null,
                    'name' => $ingredient->name,
                    'unit' => $ingredient->unit,
                    'quantity' => $quantity,
                    'min_stock' => $minStock,
                ],
            );

            InventoryTransaction::query()->updateOrCreate(
                ['inventory_item_id' => $inventoryItem->id, 'type' => 'in', 'reason' => 'Janova opening stock'],
                ['quantity' => $quantity, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function ingredient(string $name, string $unit): Ingredient
    {
        if ($this->ingredients->has($name)) {
            return $this->ingredients->get($name);
        }

        $ingredient = Ingredient::query()->firstOrCreate(
            ['name' => $name],
            ['unit' => $unit, 'stock' => 0, 'min_stock' => 25],
        );

        if (! $ingredient->unit) {
            $ingredient->unit = $unit;
            $ingredient->save();
        }

        $this->ingredients->put($name, $ingredient);

        return $ingredient;
    }

    private function unitForIngredient(string $name): string
    {
        return [
            'Sea Bass Fillet' => 'g',
            'Shrimp' => 'g',
            'Calamari' => 'g',
            'Chicken' => 'g',
            'Beef' => 'g',
            'Pasta' => 'g',
            'Rice' => 'g',
            'Tomato' => 'g',
            'Cucumber' => 'g',
            'Lettuce' => 'g',
            'Potato' => 'g',
            'Lemon' => 'g',
            'Mint' => 'g',
            'Parsley' => 'g',
            'Garlic' => 'g',
            'Olive Oil' => 'ml',
            'Tahini' => 'g',
            'Bread' => 'pcs',
            'Butter' => 'g',
            'Flour' => 'g',
            'Sugar' => 'g',
            'Milk' => 'ml',
            'Chocolate' => 'g',
            'Water' => 'ml',
            'Orange' => 'g',
            'Coffee Beans' => 'g',
        ][$name] ?? 'g';
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'Starters',
                'question' => 'Choose your dip',
                'choices' => ['Tahini', 'Garlic Yogurt', 'Spicy Harissa'],
                'modifiers' => [
                    ['name' => 'Extra Pita', 'price' => 12],
                    ['name' => 'Extra Dip', 'price' => 10],
                ],
                'products' => [
                    ['name' => 'Alexandria Mezze', 'price' => 125, 'stock' => 95, 'recipe' => 'Janova mezze plate with tahini, greens, and warm bread.', 'ingredients' => ['Tahini' => 45, 'Tomato' => 60, 'Cucumber' => 50, 'Bread' => 1]],
                    ['name' => 'Calamari Rings', 'price' => 165, 'stock' => 80, 'recipe' => 'Crisp calamari with lemon garlic dip.', 'ingredients' => ['Calamari' => 160, 'Flour' => 35, 'Lemon' => 20, 'Garlic' => 8]],
                    ['name' => 'Lentil Sea Soup', 'price' => 85, 'stock' => 100, 'recipe' => 'Coastal lentil-style soup base with parsley and lemon.', 'ingredients' => ['Water' => 280, 'Parsley' => 8, 'Lemon' => 12, 'Olive Oil' => 10]],
                ],
            ],
            [
                'name' => 'Seafood',
                'question' => 'Choose cooking style',
                'choices' => ['Grilled', 'Pan Seared', 'Spicy'],
                'modifiers' => [
                    ['name' => 'Extra Shrimp', 'price' => 65],
                    ['name' => 'Lemon Butter Sauce', 'price' => 22],
                ],
                'products' => [
                    ['name' => 'Mediterranean Sea Bass', 'price' => 285, 'stock' => 70, 'recipe' => 'Sea bass with lemon, parsley, and olive oil.', 'ingredients' => ['Sea Bass Fillet' => 220, 'Lemon' => 28, 'Parsley' => 10, 'Olive Oil' => 18]],
                    ['name' => 'Shrimp Sayadeya', 'price' => 245, 'stock' => 75, 'recipe' => 'Shrimp with rice, tomato, garlic, and coastal spices.', 'ingredients' => ['Shrimp' => 180, 'Rice' => 120, 'Tomato' => 55, 'Garlic' => 9]],
                    ['name' => 'Seafood Pasta', 'price' => 235, 'stock' => 85, 'recipe' => 'Creamy pasta with shrimp and calamari.', 'ingredients' => ['Pasta' => 160, 'Shrimp' => 90, 'Calamari' => 80, 'Milk' => 80]],
                ],
            ],
            [
                'name' => 'Grill',
                'question' => 'Choose your doneness',
                'choices' => ['Medium', 'Medium Well', 'Well Done'],
                'modifiers' => [
                    ['name' => 'Add Rice', 'price' => 28],
                    ['name' => 'Add Fries', 'price' => 32],
                ],
                'products' => [
                    ['name' => 'Janova Kofta', 'price' => 210, 'stock' => 90, 'recipe' => 'Beef kofta with rice and grilled tomato.', 'ingredients' => ['Beef' => 190, 'Rice' => 110, 'Tomato' => 45, 'Parsley' => 8]],
                    ['name' => 'Charcoal Chicken', 'price' => 195, 'stock' => 95, 'recipe' => 'Charcoal chicken with garlic potatoes.', 'ingredients' => ['Chicken' => 220, 'Potato' => 160, 'Garlic' => 10, 'Olive Oil' => 16]],
                    ['name' => 'Beef Tenderloin Plate', 'price' => 340, 'stock' => 55, 'recipe' => 'Tenderloin with lemon butter and greens.', 'ingredients' => ['Beef' => 230, 'Butter' => 18, 'Lemon' => 12, 'Lettuce' => 35]],
                ],
            ],
            [
                'name' => 'Salads',
                'question' => 'Choose dressing',
                'choices' => ['Lemon Olive Oil', 'Tahini', 'No Dressing'],
                'modifiers' => [
                    ['name' => 'Extra Chicken', 'price' => 55],
                    ['name' => 'Extra Bread', 'price' => 10],
                ],
                'products' => [
                    ['name' => 'Greek Coast Salad', 'price' => 115, 'stock' => 100, 'recipe' => 'Crisp salad with tomato, cucumber, lettuce, and olive oil.', 'ingredients' => ['Tomato' => 80, 'Cucumber' => 80, 'Lettuce' => 60, 'Olive Oil' => 15]],
                    ['name' => 'Chicken Fattoush', 'price' => 145, 'stock' => 85, 'recipe' => 'Chicken salad with crunchy bread and lemon dressing.', 'ingredients' => ['Chicken' => 90, 'Lettuce' => 70, 'Bread' => 1, 'Lemon' => 16]],
                ],
            ],
            [
                'name' => 'Desserts',
                'question' => 'Serving style',
                'choices' => ['Warm', 'Chilled', 'To Go'],
                'modifiers' => [
                    ['name' => 'Extra Chocolate', 'price' => 18],
                    ['name' => 'Extra Cream', 'price' => 16],
                ],
                'products' => [
                    ['name' => 'Chocolate Fondant', 'price' => 105, 'stock' => 80, 'recipe' => 'Warm chocolate fondant with butter and flour.', 'ingredients' => ['Chocolate' => 45, 'Butter' => 28, 'Flour' => 35, 'Sugar' => 22]],
                    ['name' => 'Milk Pudding', 'price' => 78, 'stock' => 90, 'recipe' => 'Chilled milk pudding with sugar and citrus.', 'ingredients' => ['Milk' => 180, 'Sugar' => 20, 'Orange' => 20]],
                ],
            ],
            [
                'name' => 'Beverages',
                'question' => 'Choose ice level',
                'choices' => ['No Ice', 'Light Ice', 'Regular Ice'],
                'modifiers' => [
                    ['name' => 'Extra Mint', 'price' => 8],
                    ['name' => 'Extra Shot', 'price' => 24],
                ],
                'products' => [
                    ['name' => 'Fresh Lemon Mint', 'price' => 65, 'stock' => 120, 'recipe' => 'Fresh lemon mint drink.', 'ingredients' => ['Lemon' => 35, 'Mint' => 15, 'Water' => 260, 'Sugar' => 12]],
                    ['name' => 'Orange Juice', 'price' => 70, 'stock' => 120, 'recipe' => 'Fresh orange juice.', 'ingredients' => ['Orange' => 220, 'Water' => 40]],
                    ['name' => 'Janova Espresso', 'price' => 55, 'stock' => 100, 'recipe' => 'Single-origin espresso shot.', 'ingredients' => ['Coffee Beans' => 18, 'Water' => 40]],
                ],
            ],
        ];
    }
}
