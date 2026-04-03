<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Alert;
use App\Models\Branch;
use App\Models\BranchPerformance;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\CategoryAnswer;
use App\Models\CategoryChoice;
use App\Models\CategoryQuestion;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Employee;
use App\Models\EmployeePerformance;
use App\Models\Expense;
use App\Models\Feedback;
use App\Models\FinancialSummary;
use App\Models\Income;
use App\Models\Ingredient;
use App\Models\IngredientBranch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\LoyaltyTransaction;
use App\Models\Menu;
use App\Models\MenuModifier;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemHistory;
use App\Models\OrderItemModifier;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductPerformance;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\Recipe;
use App\Models\Refund;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\SalesReport;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Transaction;
use App\Models\Type;
use App\Models\User;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestaurantSuiteDemoSeeder extends Seeder
{
    private const SEED_MARKER_KEY = 'restaurant_suite_demo_seed_version';
    private const LEGACY_SEED_MARKER_VALUE = '2026-04-02-full-pos-scenarios';
    private const SEED_MARKER_VALUE = '2026-04-03-platform-admin-sync';

    private Generator $faker;

    /** @var array<string,int> */
    private array $roleIds = [];

    /** @var array<string,int> */
    private array $typeIds = [];

    /** @var array<string,\App\Models\Modifier> */
    private array $globalModifiers = [];

    /** @var array<string,\App\Models\Ingredient> */
    private array $ingredients = [];

    public function run(): void
    {
        $this->faker = fake('en_US');
        $this->faker->seed(20260402);

        $currentMarker = Setting::where('key', self::SEED_MARKER_KEY)->value('value');

        $this->seedReferenceData();
        $this->seedPlatformAdmin();
        $customers = $this->seedCustomers();

        $hasScenarioData = in_array($currentMarker, [
            self::LEGACY_SEED_MARKER_VALUE,
            self::SEED_MARKER_VALUE,
        ], true);

        if (!$hasScenarioData) {
            foreach ($this->venueBlueprints() as $venueBlueprint) {
                $this->seedVenue($venueBlueprint, $customers);
            }
        } else {
            $this->command?->info('RestaurantSuiteDemoSeeder synced reference data and platform admin. Existing demo scenarios were retained.');
        }

        Setting::updateOrCreate(
            ['key' => self::SEED_MARKER_KEY],
            ['value' => self::SEED_MARKER_VALUE],
        );
    }

    private function seedReferenceData(): void
    {
        foreach (['admin', 'owner', 'supervisor', 'staff', 'employee'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $this->roleIds[$roleName] = $role->id;
        }

        foreach (['owner', 'waiter', 'cashier', 'kitchen'] as $typeName) {
            $type = Type::firstOrCreate(['name' => $typeName]);
            $this->typeIds[$typeName] = $type->id;
        }

        Setting::updateOrCreate(['key' => 'currency'], ['value' => 'EGP']);
        Setting::updateOrCreate(['key' => 'vat_rate'], ['value' => '0.14']);
        Setting::updateOrCreate(['key' => 'loyalty_rule'], ['value' => '1 point per EGP 10']);

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
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $rolePermissions = [
            'admin' => $permissionNames,
            'owner' => [
                'dashboard.view',
                'branches.view',
                'branches.manage',
                'users.view',
                'users.manage',
                'roles.view',
                'orders.view',
                'orders.manage',
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
            ],
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
            'employee' => [
                'dashboard.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $names) {
            $role = Role::query()->find($this->roleIds[$roleName]);
            $permissionIds = Permission::query()->whereIn('name', $names)->pluck('id')->all();
            $role?->permissions()->sync($permissionIds);
        }

        foreach ([
            ['name' => 'No Ice', 'price' => 0],
            ['name' => 'Extra Cheese', 'price' => 18],
            ['name' => 'Extra Sauce', 'price' => 12],
            ['name' => 'Oat Milk', 'price' => 20],
            ['name' => 'Extra Shot', 'price' => 24],
            ['name' => 'Whipped Cream', 'price' => 15],
            ['name' => 'No Onion', 'price' => 0],
            ['name' => 'Takeaway Cutlery', 'price' => 4],
        ] as $modifierData) {
            $modifier = Modifier::firstOrCreate(
                ['name' => $modifierData['name'], 'restaurant_id' => null],
                ['price' => $modifierData['price']],
            );
            $this->globalModifiers[$modifier->name] = $modifier;
        }

        foreach ([
            ['name' => 'Espresso Beans', 'unit' => 'g'],
            ['name' => 'Milk', 'unit' => 'ml'],
            ['name' => 'Oat Milk', 'unit' => 'ml'],
            ['name' => 'Almond Milk', 'unit' => 'ml'],
            ['name' => 'Tea Leaves', 'unit' => 'g'],
            ['name' => 'Sugar', 'unit' => 'g'],
            ['name' => 'Butter', 'unit' => 'g'],
            ['name' => 'Flour', 'unit' => 'g'],
            ['name' => 'Cocoa Powder', 'unit' => 'g'],
            ['name' => 'Vanilla Syrup', 'unit' => 'ml'],
            ['name' => 'Bread', 'unit' => 'pcs'],
            ['name' => 'Turkey Slices', 'unit' => 'g'],
            ['name' => 'Tuna', 'unit' => 'g'],
            ['name' => 'Cheddar Cheese', 'unit' => 'g'],
            ['name' => 'Halloumi Cheese', 'unit' => 'g'],
            ['name' => 'Lettuce', 'unit' => 'g'],
            ['name' => 'Tomato', 'unit' => 'g'],
            ['name' => 'Potato', 'unit' => 'g'],
            ['name' => 'Rice', 'unit' => 'g'],
            ['name' => 'Chicken', 'unit' => 'g'],
            ['name' => 'Beef', 'unit' => 'g'],
            ['name' => 'Pasta', 'unit' => 'g'],
            ['name' => 'Lemon', 'unit' => 'g'],
            ['name' => 'Mint', 'unit' => 'g'],
            ['name' => 'Chocolate', 'unit' => 'g'],
            ['name' => 'Water', 'unit' => 'ml'],
        ] as $ingredientData) {
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $ingredientData['name']],
                [
                    'unit' => $ingredientData['unit'],
                    'stock' => 500,
                    'min_stock' => 25,
                ],
            );
            $this->ingredients[$ingredient->name] = $ingredient;
        }
    }

    private function seedPlatformAdmin(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@restaurant-suite.com'],
            [
                'name' => 'Restaurant Suite Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'restaurant_id' => null,
                'branch_id' => null,
            ],
        );

        $user->roles()->sync([$this->roleIds['admin']]);
        $user->types()->sync([]);
    }

    private function seedCustomers(): Collection
    {
        $customers = collect();

        $profiles = [
            ['name' => 'Nour Adel', 'phone' => '01010000001'],
            ['name' => 'Omar Tarek', 'phone' => '01010000002'],
            ['name' => 'Laila Hassan', 'phone' => '01010000003'],
            ['name' => 'Mina Samir', 'phone' => '01010000004'],
            ['name' => 'Yara Mostafa', 'phone' => '01010000005'],
            ['name' => 'Kareem Youssef', 'phone' => '01010000006'],
            ['name' => 'Mariam Fathy', 'phone' => '01010000007'],
            ['name' => 'Hussein Nabil', 'phone' => '01010000008'],
            ['name' => 'Dina Sherif', 'phone' => '01010000009'],
            ['name' => 'Salma Hany', 'phone' => '01010000010'],
            ['name' => 'Mahmoud Reda', 'phone' => '01010000011'],
            ['name' => 'Rana Khaled', 'phone' => '01010000012'],
            ['name' => 'Ahmed Ehab', 'phone' => '01010000013'],
            ['name' => 'Farah Ayman', 'phone' => '01010000014'],
            ['name' => 'Karim Atef', 'phone' => '01010000015'],
            ['name' => 'Nada Wael', 'phone' => '01010000016'],
            ['name' => 'Amr Hossam', 'phone' => '01010000017'],
            ['name' => 'Sara Gamal', 'phone' => '01010000018'],
        ];

        foreach ($profiles as $index => $profile) {
            $customer = Customer::updateOrCreate(
                ['phone' => $profile['phone']],
                [
                    'name' => $profile['name'],
                    'email' => 'customer'.($index + 1).'@example.com',
                    'loyalty_points' => 0,
                ],
            );

            $customers->push($customer);
        }

        return $customers;
    }

    private function seedVenue(array $venueBlueprint, Collection $customers): void
    {
        $restaurant = Restaurant::updateOrCreate(
            ['name' => $venueBlueprint['name']],
            ['kind' => $venueBlueprint['kind']],
        );
        $restaurantSlug = Str::slug($restaurant->name);

        $owners = [
            $this->upsertOwnerUser(
                restaurant: $restaurant,
                name: $restaurant->name.' Owner',
                email: "owner.{$restaurantSlug}@example.com",
            ),
            $this->upsertOwnerUser(
                restaurant: $restaurant,
                name: $restaurant->name.' Stakeholder',
                email: "stakeholder.{$restaurantSlug}@example.com",
            ),
        ];

        $restaurantModifiers = $this->seedRestaurantModifiers($restaurant, $venueBlueprint['kind']);
        $suppliers = $this->seedSuppliersForRestaurant($restaurant, $restaurantSlug);

        foreach ($this->branchBlueprintsFor($restaurant->name) as $branchIndex => $branchBlueprint) {
            $branch = Branch::firstOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => $branchBlueprint['name']],
                ['location' => $branchBlueprint['location']],
            );

            $branchContext = $this->seedBranchStaff(
                branch: $branch,
                restaurant: $restaurant,
                restaurantSlug: $restaurantSlug,
                branchSlug: $branchBlueprint['slug'],
            );

            $tables = $this->seedTablesForBranch($branch, $branchBlueprint['slug']);
            $this->seedDevicesForBranch($branch, $branchBlueprint['slug']);
            $catalog = $this->seedCatalogForBranch(
                restaurant: $restaurant,
                branch: $branch,
                kind: $venueBlueprint['kind'],
                restaurantSlug: $restaurantSlug,
                restaurantModifiers: $restaurantModifiers,
            );
            $this->seedInventoryForBranch($branch, $catalog['used_ingredients'], $suppliers, $branchIndex);
            $this->seedOperationsForBranch(
                restaurant: $restaurant,
                branch: $branch,
                kind: $venueBlueprint['kind'],
                tables: $tables,
                customers: $customers,
                branchContext: $branchContext,
                catalog: $catalog,
            );

            foreach ($owners as $ownerUser) {
                ActivityLog::create([
                    'user_id' => $ownerUser->id,
                    'action' => "Viewed {$branch->name} launch board",
                    'description' => "Seeded owner activity for {$branch->name}.",
                ]);
            }
        }

        Alert::firstOrCreate(
            ['message' => "{$restaurant->name} has demo low-stock alerts enabled."],
            ['type' => 'stock', 'resolved' => false],
        );
    }

    private function upsertOwnerUser(Restaurant $restaurant, string $name, string $email): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make('password');
        $user->role = 'owner';
        $user->restaurant_id = $restaurant->id;
        $user->branch_id = null;
        $user->save();

        $user->roles()->sync([$this->roleIds['owner']]);
        $user->types()->syncWithoutDetaching([$this->typeIds['owner']]);

        return $user;
    }

    private function seedRestaurantModifiers(Restaurant $restaurant, string $kind): Collection
    {
        $names = $kind === 'cafe'
            ? [
                ['name' => 'Caramel Syrup', 'price' => 18],
                ['name' => 'Hazelnut Syrup', 'price' => 18],
                ['name' => 'Decaf Shot', 'price' => 12],
                ['name' => 'Extra Pastry Box', 'price' => 8],
            ]
            : [
                ['name' => 'Add Fries', 'price' => 24],
                ['name' => 'Add Rice', 'price' => 20],
                ['name' => 'Extra Garlic Dip', 'price' => 10],
                ['name' => 'Family Bread Basket', 'price' => 22],
            ];

        return collect($names)->map(function (array $modifierData) use ($restaurant) {
            return Modifier::firstOrCreate(
                ['name' => $modifierData['name'], 'restaurant_id' => $restaurant->id],
                ['price' => $modifierData['price']],
            );
        });
    }

    private function seedSuppliersForRestaurant(Restaurant $restaurant, string $slug): Collection
    {
        return collect([
            ['name' => $restaurant->name.' Fresh Supply', 'contact_person' => 'Procurement Lead'],
            ['name' => $restaurant->name.' Beverage Hub', 'contact_person' => 'Beverage Vendor'],
        ])->map(function (array $supplierData, int $index) use ($slug) {
            return Supplier::firstOrCreate(
                ['email' => "supplier{$index}.{$slug}@example.com"],
                [
                    'name' => $supplierData['name'],
                    'phone' => '011'.str_pad((string) (9000000 + $index + abs(crc32($slug)) % 100000), 7, '0', STR_PAD_LEFT),
                    'contact_person' => $supplierData['contact_person'],
                ],
            );
        });
    }

    private function branchBlueprintsFor(string $restaurantName): array
    {
        $areas = [
            ['label' => 'Downtown', 'location' => 'Downtown Cairo'],
            ['label' => 'New Cairo', 'location' => 'New Cairo'],
            ['label' => 'Maadi', 'location' => 'Maadi'],
            ['label' => 'Zayed', 'location' => 'Sheikh Zayed'],
            ['label' => 'Heliopolis', 'location' => 'Heliopolis'],
            ['label' => 'Alexandria', 'location' => 'Alexandria Corniche'],
        ];

        $branchCount = 1 + (abs(crc32($restaurantName)) % 3);
        $offset = abs(crc32($restaurantName.'-offset')) % count($areas);

        $selected = [];
        for ($i = 0; $i < $branchCount; $i++) {
            $area = $areas[($offset + $i) % count($areas)];
            $selected[] = [
                'name' => $area['label'].' Branch',
                'location' => $area['location'],
                'slug' => Str::slug($area['label']),
            ];
        }

        return $selected;
    }

    private function seedBranchStaff(Branch $branch, Restaurant $restaurant, string $restaurantSlug, string $branchSlug): array
    {
        $supervisor = $this->upsertStaffUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "supervisor.{$restaurantSlug}.{$branchSlug}@example.com",
            name: "{$restaurant->name} {$branch->name} Supervisor",
            role: 'supervisor',
            type: null,
            position: 'Branch Supervisor',
        );

        $waiters = collect([1, 2])->map(fn (int $index) => $this->upsertStaffUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "waiter{$index}.{$restaurantSlug}.{$branchSlug}@example.com",
            name: "{$branch->name} Waiter {$index}",
            role: 'staff',
            type: 'waiter',
            position: 'Waiter',
        ));

        $cashiers = collect([1])->map(fn (int $index) => $this->upsertStaffUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "cashier{$index}.{$restaurantSlug}.{$branchSlug}@example.com",
            name: "{$branch->name} Cashier {$index}",
            role: 'staff',
            type: 'cashier',
            position: 'Cashier',
        ));

        $kitchen = collect([1, 2])->map(fn (int $index) => $this->upsertStaffUser(
            restaurant: $restaurant,
            branch: $branch,
            email: "kitchen{$index}.{$restaurantSlug}.{$branchSlug}@example.com",
            name: "{$branch->name} Kitchen {$index}",
            role: 'staff',
            type: 'kitchen',
            position: $index === 1 ? 'Kitchen Lead' : 'Line Cook',
        ));

        return [
            'supervisor' => $supervisor,
            'waiters' => $waiters,
            'cashiers' => $cashiers,
            'kitchen' => $kitchen,
        ];
    }

    private function upsertStaffUser(
        Restaurant $restaurant,
        Branch $branch,
        string $email,
        string $name,
        string $role,
        ?string $type,
        string $position,
    ): array {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make('password');
        $user->role = $role;
        $user->restaurant_id = $restaurant->id;
        $user->branch_id = $branch->id;
        $user->save();

        $user->roles()->sync([$this->roleIds[$role]]);
        if ($type !== null) {
            $user->types()->syncWithoutDetaching([$this->typeIds[$type]]);
        }

        $employee = Employee::firstOrCreate(
            ['user_id' => $user->id],
            [
                'branch_id' => $branch->id,
                'position' => $position,
                'hired_at' => CarbonImmutable::now()->subMonths(6)->toDateString(),
                'base_salary' => match ($position) {
                    'Branch Supervisor' => 13500,
                    'Kitchen Lead' => 10500,
                    'Cashier' => 8500,
                    default => 7800,
                },
            ],
        );

        if (!EmployeePerformance::where('employee_id', $employee->id)->exists()) {
            EmployeePerformance::create([
                'employee_id' => $employee->id,
                'metric' => 'orders_handled',
                'value' => 120 + ($employee->id % 45),
                'recorded_at' => CarbonImmutable::now()->subDays(2)->toDateString(),
            ]);
            EmployeePerformance::create([
                'employee_id' => $employee->id,
                'metric' => 'guest_rating',
                'value' => 4.2 + (($employee->id % 6) * 0.1),
                'recorded_at' => CarbonImmutable::now()->subDay()->toDateString(),
            ]);
        }

        return ['user' => $user, 'employee' => $employee];
    }

    private function seedTablesForBranch(Branch $branch, string $branchSlug): Collection
    {
        $tableNames = [
            'T1 Terrace',
            'T2 Window',
            'T3 Family',
            'T4 Lounge',
            'T5 Bar',
            'T6 Patio',
            'T7 Corner',
            'T8 Garden',
        ];

        return collect($tableNames)->map(function (string $tableName, int $index) use ($branch) {
            return Table::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $tableName],
                [
                    'seats' => match (true) {
                        $index < 2 => 2,
                        $index < 5 => 4,
                        default => 6,
                    },
                    'status' => 'open',
                ],
            );
        });
    }

    private function seedDevicesForBranch(Branch $branch, string $branchSlug): void
    {
        foreach ([
            ['name' => "{$branch->name} POS", 'type' => 'POS'],
            ['name' => "{$branch->name} Waiter Tablet", 'type' => 'Tablet'],
            ['name' => "{$branch->name} Kitchen Screen", 'type' => 'KDS'],
        ] as $index => $deviceData) {
            Device::firstOrCreate(
                ['uuid' => "{$branchSlug}-{$branch->id}-{$index}"],
                [
                    'name' => $deviceData['name'],
                    'branch_id' => $branch->id,
                    'type' => $deviceData['type'],
                ],
            );
        }

        CashRegister::firstOrCreate(
            ['branch_id' => $branch->id],
            [
                'opening_balance' => 3500,
                'closing_balance' => null,
                'is_open' => true,
            ],
        );
    }

    private function seedCatalogForBranch(
        Restaurant $restaurant,
        Branch $branch,
        string $kind,
        string $restaurantSlug,
        Collection $restaurantModifiers,
    ): array {
        $menu = Menu::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => $branch->name.' Main Menu'],
        );

        $productsByCategory = [];
        $choicesByCategory = [];
        $usedIngredients = collect();
        $allModifiers = collect($this->globalModifiers)->concat($restaurantModifiers)->values();

        foreach ($allModifiers as $modifier) {
            MenuModifier::firstOrCreate([
                'menu_id' => $menu->id,
                'modifier_id' => $modifier->id,
            ]);
        }

        foreach ($this->catalogBlueprintForKind($kind) as $categoryDefinition) {
            $category = Category::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $categoryDefinition['name']],
            );
            $menu->categories()->syncWithoutDetaching([$category->id]);

            $question = CategoryQuestion::firstOrCreate(
                ['category_id' => $category->id, 'question' => $categoryDefinition['question']],
                ['image' => null],
            );

            $choiceModels = collect();
            foreach ($categoryDefinition['choices'] as $choiceLabel) {
                $choice = CategoryChoice::where('question_id', $question->id)
                    ->where('choice', $choiceLabel)
                    ->first();

                if (!$choice) {
                    $choice = new CategoryChoice();
                    $choice->question_id = $question->id;
                    $choice->choice = $choiceLabel;
                    $choice->image = null;
                    $choice->save();
                }

                $choiceModels->push($choice);
            }

            $choicesByCategory[$category->name] = $choiceModels;
            $products = collect();

            foreach ($categoryDefinition['products'] as $productDefinition) {
                $product = Product::firstOrCreate(
                    ['branch_id' => $branch->id, 'name' => $productDefinition['name']],
                    [
                        'category_id' => $category->id,
                        'price' => $productDefinition['price'],
                        'is_available' => true,
                        'image' => null,
                        'sku' => "{$restaurantSlug}-{$branch->id}-".Str::slug($productDefinition['name']),
                        'min_stock' => 8,
                        'stock' => 85,
                    ],
                );

                $product->category_id = $category->id;
                $product->price = $productDefinition['price'];
                $product->is_available = true;
                $product->save();

                $recipe = Recipe::firstOrCreate(['product_id' => $product->id]);
                foreach ($productDefinition['ingredients'] as $ingredientName => $quantity) {
                    $ingredient = $this->ingredients[$ingredientName];
                    $recipe->ingredients()->syncWithoutDetaching([
                        $ingredient->id => ['quantity' => $quantity],
                    ]);
                    $usedIngredients->put($ingredientName, $ingredient);
                }

                $products->push($product);
            }

            $productsByCategory[$category->name] = $products;
        }

        return [
            'menu' => $menu,
            'products_by_category' => collect($productsByCategory),
            'choices_by_category' => collect($choicesByCategory),
            'restaurant_modifiers' => $restaurantModifiers->keyBy('name'),
            'global_modifiers' => collect($this->globalModifiers),
            'used_ingredients' => $usedIngredients->values(),
        ];
    }

    private function catalogBlueprintForKind(string $kind): array
    {
        if ($kind === 'cafe') {
            return [
                [
                    'name' => 'Coffee',
                    'question' => 'Choose your milk',
                    'choices' => ['Whole Milk', 'Oat Milk', 'Almond Milk'],
                    'products' => [
                        ['name' => 'Flat White', 'price' => 88, 'ingredients' => ['Espresso Beans' => 18, 'Milk' => 180]],
                        ['name' => 'Spanish Latte', 'price' => 96, 'ingredients' => ['Espresso Beans' => 18, 'Milk' => 190, 'Sugar' => 10]],
                        ['name' => 'Iced Latte', 'price' => 92, 'ingredients' => ['Espresso Beans' => 18, 'Milk' => 180, 'Water' => 60]],
                    ],
                ],
                [
                    'name' => 'Tea',
                    'question' => 'Choose your sugar level',
                    'choices' => ['No Sugar', 'Half Sugar', 'Regular'],
                    'products' => [
                        ['name' => 'English Breakfast', 'price' => 48, 'ingredients' => ['Tea Leaves' => 10, 'Water' => 250, 'Sugar' => 6]],
                        ['name' => 'Green Tea', 'price' => 52, 'ingredients' => ['Tea Leaves' => 9, 'Water' => 250]],
                        ['name' => 'Hibiscus Cooler', 'price' => 58, 'ingredients' => ['Tea Leaves' => 8, 'Sugar' => 10, 'Water' => 260]],
                    ],
                ],
                [
                    'name' => 'Pastries',
                    'question' => 'How should we pack it?',
                    'choices' => ['Serve Warm', 'Standard Pack', 'Gift Box'],
                    'products' => [
                        ['name' => 'Butter Croissant', 'price' => 44, 'ingredients' => ['Butter' => 22, 'Flour' => 90]],
                        ['name' => 'Cinnamon Roll', 'price' => 55, 'ingredients' => ['Butter' => 20, 'Flour' => 95, 'Sugar' => 16]],
                        ['name' => 'Chocolate Muffin', 'price' => 50, 'ingredients' => ['Butter' => 16, 'Flour' => 80, 'Chocolate' => 18]],
                    ],
                ],
                [
                    'name' => 'Sandwiches',
                    'question' => 'Choose your side',
                    'choices' => ['Fries', 'Salad', 'No Side'],
                    'products' => [
                        ['name' => 'Turkey Club', 'price' => 118, 'ingredients' => ['Bread' => 2, 'Turkey Slices' => 90, 'Cheddar Cheese' => 24, 'Lettuce' => 15, 'Tomato' => 20]],
                        ['name' => 'Halloumi Panini', 'price' => 124, 'ingredients' => ['Bread' => 2, 'Halloumi Cheese' => 80, 'Tomato' => 25]],
                        ['name' => 'Tuna Melt', 'price' => 128, 'ingredients' => ['Bread' => 2, 'Tuna' => 85, 'Cheddar Cheese' => 28]],
                    ],
                ],
                [
                    'name' => 'Desserts',
                    'question' => 'Serving style',
                    'choices' => ['Chilled', 'Warm', 'To Go'],
                    'products' => [
                        ['name' => 'San Sebastian Cheesecake', 'price' => 96, 'ingredients' => ['Butter' => 18, 'Sugar' => 24, 'Cheddar Cheese' => 40]],
                        ['name' => 'Tiramisu Cup', 'price' => 84, 'ingredients' => ['Milk' => 90, 'Chocolate' => 14, 'Sugar' => 18]],
                        ['name' => 'Lotus Cake Jar', 'price' => 89, 'ingredients' => ['Butter' => 12, 'Sugar' => 18, 'Chocolate' => 16]],
                    ],
                ],
            ];
        }

        return [
            [
                'name' => 'Starters',
                'question' => 'Choose your dip',
                'choices' => ['Tahini', 'Garlic Dip', 'Spicy Sauce'],
                'products' => [
                    ['name' => 'Halloumi Bites', 'price' => 94, 'ingredients' => ['Halloumi Cheese' => 90, 'Tomato' => 18]],
                    ['name' => 'Lentil Soup', 'price' => 62, 'ingredients' => ['Water' => 250, 'Lemon' => 12]],
                    ['name' => 'Mezze Plate', 'price' => 110, 'ingredients' => ['Tomato' => 35, 'Lettuce' => 30, 'Bread' => 1]],
                ],
            ],
            [
                'name' => 'Mains',
                'question' => 'Choose your side',
                'choices' => ['Fries', 'Rice', 'Salad'],
                'products' => [
                    ['name' => 'Chicken Shawarma Plate', 'price' => 176, 'ingredients' => ['Chicken' => 160, 'Rice' => 120, 'Tomato' => 20]],
                    ['name' => 'Mushroom Pasta', 'price' => 168, 'ingredients' => ['Pasta' => 150, 'Milk' => 70, 'Cheddar Cheese' => 22]],
                    ['name' => 'Baked Kofta', 'price' => 189, 'ingredients' => ['Beef' => 180, 'Tomato' => 25, 'Rice' => 90]],
                ],
            ],
            [
                'name' => 'Grill',
                'question' => 'Choose your doneness',
                'choices' => ['Medium Rare', 'Medium', 'Well Done'],
                'products' => [
                    ['name' => 'Angus Burger', 'price' => 182, 'ingredients' => ['Beef' => 170, 'Bread' => 1, 'Cheddar Cheese' => 26, 'Tomato' => 18]],
                    ['name' => 'Mixed Grill', 'price' => 238, 'ingredients' => ['Beef' => 140, 'Chicken' => 140, 'Rice' => 100]],
                    ['name' => 'BBQ Chicken Skillet', 'price' => 194, 'ingredients' => ['Chicken' => 180, 'Potato' => 130, 'Tomato' => 20]],
                ],
            ],
            [
                'name' => 'Drinks',
                'question' => 'Choose your ice level',
                'choices' => ['No Ice', 'Light Ice', 'Regular Ice'],
                'products' => [
                    ['name' => 'Fresh Lemon Mint', 'price' => 58, 'ingredients' => ['Lemon' => 20, 'Mint' => 12, 'Water' => 260, 'Sugar' => 10]],
                    ['name' => 'Sparkling Water', 'price' => 32, 'ingredients' => ['Water' => 330]],
                    ['name' => 'Berry Mojito', 'price' => 66, 'ingredients' => ['Water' => 280, 'Mint' => 10, 'Sugar' => 12]],
                ],
            ],
            [
                'name' => 'Desserts',
                'question' => 'Serving style',
                'choices' => ['Warm', 'Chilled', 'To Go'],
                'products' => [
                    ['name' => 'Om Ali', 'price' => 78, 'ingredients' => ['Milk' => 130, 'Sugar' => 18, 'Bread' => 1]],
                    ['name' => 'Brownie Skillet', 'price' => 88, 'ingredients' => ['Chocolate' => 22, 'Butter' => 18, 'Flour' => 70]],
                    ['name' => 'Cheesecake Slice', 'price' => 84, 'ingredients' => ['Cheddar Cheese' => 36, 'Sugar' => 20, 'Butter' => 10]],
                ],
            ],
        ];
    }

    private function seedInventoryForBranch(Branch $branch, Collection $ingredients, Collection $suppliers, int $branchIndex): void
    {
        foreach ($ingredients as $ingredientIndex => $ingredient) {
            $isLowStock = (($branch->id + $ingredientIndex) % 7) === 0;
            $quantity = $isLowStock ? 5 + ($ingredientIndex % 3) : 45 + (($branch->id + $ingredientIndex) % 75);
            $minStock = 12 + ($ingredientIndex % 5);

            IngredientBranch::updateOrCreate(
                ['ingredient_id' => $ingredient->id, 'branch_id' => $branch->id],
                ['stock' => $quantity],
            );

            $inventoryItem = InventoryItem::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $ingredient->name],
                [
                    'unit' => $ingredient->unit,
                    'quantity' => $quantity,
                    'min_stock' => $minStock,
                ],
            );
            $inventoryItem->quantity = $quantity;
            $inventoryItem->min_stock = $minStock;
            $inventoryItem->unit = $ingredient->unit;
            $inventoryItem->save();

            if (!InventoryTransaction::where('inventory_item_id', $inventoryItem->id)->exists()) {
                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type' => 'in',
                    'quantity' => $quantity + 20,
                    'reason' => 'Opening seeded stock delivery',
                ]);
                InventoryTransaction::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type' => 'out',
                    'quantity' => max(8, $quantity / 2),
                    'reason' => 'Seeded operational consumption',
                ]);
            }
        }

        if (!PurchaseOrder::where('branch_id', $branch->id)->exists()) {
            foreach ($suppliers as $index => $supplier) {
                PurchaseOrder::create([
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branch->id,
                    'total_cost' => 2800 + ($branchIndex * 450) + ($index * 320),
                    'status' => $index === 0 ? 'received' : 'pending',
                    'order_date' => CarbonImmutable::now()->subDays(4 + $index)->toDateString(),
                ]);
            }
        }
    }

    private function seedOperationsForBranch(
        Restaurant $restaurant,
        Branch $branch,
        string $kind,
        Collection $tables,
        Collection $customers,
        array $branchContext,
        array $catalog,
    ): void {
        if (Order::where('branch_id', $branch->id)->exists()) {
            return;
        }

        $branchCustomers = $customers->slice($branch->id % max(1, $customers->count() - 6), 6)->values();
        $waiter = $branchContext['waiters']->first();
        $cashier = $branchContext['cashiers']->first();
        $waiterUser = $waiter['user'];
        $waiterEmployee = $waiter['employee'];

        $openOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[0],
            customer: $branchCustomers[0],
            employee: $waiterEmployee,
            status: 'open',
            orderType: 'dine-in',
            paymentStatus: 'unpaid',
            paymentMethod: null,
            orderedAt: CarbonImmutable::now()->subMinutes(18),
            items: $this->scenarioItems($kind, 'waiter_open', $catalog),
            kdsSentAt: null,
            discount: 0,
            discountType: 'fixed',
            couponCode: null,
            payments: [],
        );
        $tables[0]->update(['status' => 'occupied']);
        $this->logOrderStatuses($openOrder, [$waiterUser->id => ['pending', 'open']]);

        $kdsOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[1],
            customer: $branchCustomers[1],
            employee: $waiterEmployee,
            status: 'open',
            orderType: 'dine-in',
            paymentStatus: 'unpaid',
            paymentMethod: null,
            orderedAt: CarbonImmutable::now()->subMinutes(12),
            items: $this->scenarioItems($kind, 'kds_live', $catalog),
            kdsSentAt: CarbonImmutable::now()->subMinutes(8),
            discount: 0,
            discountType: 'fixed',
            couponCode: null,
            payments: [],
        );
        $tables[1]->update(['status' => 'occupied']);
        $this->logOrderStatuses($kdsOrder, [$waiterUser->id => ['pending', 'open']]);

        $cashierOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[2],
            customer: $branchCustomers[2],
            employee: $waiterEmployee,
            status: 'cashier',
            orderType: 'dine-in',
            paymentStatus: 'unpaid',
            paymentMethod: null,
            orderedAt: CarbonImmutable::now()->subMinutes(24),
            items: $this->scenarioItems($kind, 'cashier_queue', $catalog),
            kdsSentAt: CarbonImmutable::now()->subMinutes(18),
            discount: 10,
            discountType: 'fixed',
            couponCode: 'CASHIER-DEMO',
            payments: [],
        );
        $tables[2]->update(['status' => 'cashier']);
        $this->logOrderStatuses($cashierOrder, [$waiterUser->id => ['pending', 'open'], $cashier['user']->id => ['cashier']]);

        $paidCashOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[3],
            customer: $branchCustomers[0],
            employee: $waiterEmployee,
            status: 'paid',
            orderType: 'dine-in',
            paymentStatus: 'paid',
            paymentMethod: 'cash',
            orderedAt: CarbonImmutable::now()->subHours(3),
            items: $this->scenarioItems($kind, 'paid_cash', $catalog),
            kdsSentAt: CarbonImmutable::now()->subHours(3)->addMinutes(6),
            discount: 0,
            discountType: 'fixed',
            couponCode: null,
            payments: [
                ['method' => 'cash', 'ratio' => 1],
            ],
        );
        $tables[3]->update(['status' => 'open']);
        $this->logOrderStatuses($paidCashOrder, [$waiterUser->id => ['pending', 'open', 'cashier'], $cashier['user']->id => ['paid']]);
        $this->createFeedbackForPaidOrder($paidCashOrder, $branchCustomers[0], 'Fast service and very clear bill breakdown.', 5);

        $paidSplitOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[4],
            customer: $branchCustomers[3],
            employee: $waiterEmployee,
            status: 'paid',
            orderType: 'dine-in',
            paymentStatus: 'paid',
            paymentMethod: 'split',
            orderedAt: CarbonImmutable::now()->subDays(1)->subHours(2),
            items: $this->scenarioItems($kind, 'paid_split', $catalog),
            kdsSentAt: CarbonImmutable::now()->subDays(1)->subHours(2)->addMinutes(10),
            discount: 12,
            discountType: 'fixed',
            couponCode: 'LOYALTY12',
            payments: [
                ['method' => 'cash', 'ratio' => 0.4],
                ['method' => 'card', 'ratio' => 0.6],
            ],
        );
        $tables[4]->update(['status' => 'open']);
        $this->logOrderStatuses($paidSplitOrder, [$waiterUser->id => ['pending', 'open', 'cashier'], $cashier['user']->id => ['paid']]);
        $this->createFeedbackForPaidOrder($paidSplitOrder, $branchCustomers[3], 'Split payment worked smoothly for the family table.', 5);

        $refundOrder = $this->createScenarioOrder(
            branch: $branch,
            table: $tables[5],
            customer: $branchCustomers[4],
            employee: $waiterEmployee,
            status: 'paid',
            orderType: 'dine-in',
            paymentStatus: 'paid',
            paymentMethod: 'card',
            orderedAt: CarbonImmutable::now()->subDays(2)->subHours(1),
            items: $this->scenarioItems($kind, 'refund_case', $catalog),
            kdsSentAt: CarbonImmutable::now()->subDays(2)->subHours(1)->addMinutes(8),
            discount: 0,
            discountType: 'fixed',
            couponCode: null,
            payments: [
                ['method' => 'card', 'ratio' => 1],
            ],
        );
        $tables[5]->update(['status' => 'open']);
        $this->addRefundScenario($refundOrder, $waiterUser);
        $this->logOrderStatuses($refundOrder, [$waiterUser->id => ['pending', 'open', 'cashier'], $cashier['user']->id => ['paid']]);

        foreach ([
            CarbonImmutable::now()->subDays(4),
            CarbonImmutable::now()->subDays(7),
            CarbonImmutable::now()->subDays(11),
        ] as $index => $historicalDate) {
            $customer = $branchCustomers[$index % $branchCustomers->count()];
            $historicalOrder = $this->createScenarioOrder(
                branch: $branch,
                table: null,
                customer: $customer,
                employee: $waiterEmployee,
                status: 'paid',
                orderType: $index % 2 === 0 ? 'delivery' : 'takeaway',
                paymentStatus: 'paid',
                paymentMethod: $index % 2 === 0 ? 'wallet' : 'card',
                orderedAt: $historicalDate,
                items: $this->scenarioItems($kind, 'history', $catalog, $index),
                kdsSentAt: $historicalDate->addMinutes(6),
                discount: $index === 1 ? 8 : 0,
                discountType: 'fixed',
                couponCode: $index === 1 ? 'TAKE8' : null,
                payments: [
                    ['method' => $index % 2 === 0 ? 'wallet' : 'card', 'ratio' => 1],
                ],
            );
            $this->logOrderStatuses($historicalOrder, [$waiterUser->id => ['pending', 'open', 'cashier'], $cashier['user']->id => ['paid']]);
        }

        $this->seedBranchSummaries($branch);
    }

    private function scenarioItems(string $kind, string $scenario, array $catalog, int $variant = 0): array
    {
        $products = $catalog['products_by_category'];
        $choices = $catalog['choices_by_category'];
        $globalModifiers = $catalog['global_modifiers'];
        $restaurantModifiers = $catalog['restaurant_modifiers'];

        if ($kind === 'cafe') {
            return match ($scenario) {
                'waiter_open' => [
                    $this->itemDefinition($products['Coffee'][0], 2, $choices['Coffee'][1] ?? null, [$globalModifiers['Oat Milk'], $globalModifiers['Extra Shot']], 'Guest asked for a stronger cup.'),
                    $this->itemDefinition($products['Pastries'][1], 1, $choices['Pastries'][0] ?? null, [$restaurantModifiers['Extra Pastry Box'] ?? null], 'Serve warm on the side.'),
                ],
                'kds_live' => [
                    $this->itemDefinition($products['Coffee'][2], 1, $choices['Coffee'][0] ?? null, [$globalModifiers['No Ice']], 'No ice, keep it cold from milk only.', 'pending', 'queued'),
                    $this->itemDefinition($products['Sandwiches'][0], 1, $choices['Sandwiches'][1] ?? null, [], 'Cut into two pieces.', 'preparing', 'preparing'),
                    $this->itemDefinition($products['Desserts'][0], 1, $choices['Desserts'][1] ?? null, [], null, 'prepared', 'ready'),
                ],
                'cashier_queue' => [
                    $this->itemDefinition($products['Coffee'][1], 2, $choices['Coffee'][2] ?? null, [$globalModifiers['Whipped Cream']], null, 'prepared', 'ready'),
                    $this->itemDefinition($products['Sandwiches'][2], 1, $choices['Sandwiches'][0] ?? null, [], null, 'prepared', 'ready'),
                ],
                'paid_cash' => [
                    $this->itemDefinition($products['Tea'][0], 2, $choices['Tea'][0] ?? null, [], null, 'served', 'served'),
                    $this->itemDefinition($products['Desserts'][1], 1, $choices['Desserts'][0] ?? null, [], null, 'served', 'served'),
                ],
                'paid_split' => [
                    $this->itemDefinition($products['Coffee'][0], 2, $choices['Coffee'][1] ?? null, [$globalModifiers['Extra Shot']], null, 'served', 'served'),
                    $this->itemDefinition($products['Pastries'][0], 2, $choices['Pastries'][2] ?? null, [], null, 'served', 'served'),
                    $this->itemDefinition($products['Sandwiches'][1], 1, $choices['Sandwiches'][0] ?? null, [], null, 'served', 'served'),
                ],
                'refund_case' => [
                    $this->itemDefinition($products['Coffee'][1], 2, $choices['Coffee'][0] ?? null, [$restaurantModifiers['Caramel Syrup'] ?? null], null, 'served', 'served'),
                    $this->itemDefinition($products['Desserts'][2], 1, $choices['Desserts'][2] ?? null, [], null, 'served', 'served'),
                ],
                default => [
                    $this->itemDefinition($products['Coffee'][$variant % 3], 1, $choices['Coffee'][0] ?? null, [], null, 'served', 'served'),
                    $this->itemDefinition($products['Pastries'][($variant + 1) % 3], 1, $choices['Pastries'][1] ?? null, [], null, 'served', 'served'),
                ],
            };
        }

        return match ($scenario) {
            'waiter_open' => [
                $this->itemDefinition($products['Mains'][0], 2, $choices['Mains'][0] ?? null, [$restaurantModifiers['Add Rice'] ?? null], 'One plate without onion.'),
                $this->itemDefinition($products['Drinks'][0], 2, $choices['Drinks'][0] ?? null, [$globalModifiers['No Ice']], 'No ice for both drinks.'),
            ],
            'kds_live' => [
                $this->itemDefinition($products['Grill'][1], 1, $choices['Grill'][1] ?? null, [$restaurantModifiers['Extra Garlic Dip'] ?? null], 'Serve sizzling.', 'pending', 'queued'),
                $this->itemDefinition($products['Starters'][0], 1, $choices['Starters'][2] ?? null, [], null, 'preparing', 'preparing'),
                $this->itemDefinition($products['Desserts'][1], 1, $choices['Desserts'][1] ?? null, [], null, 'prepared', 'ready'),
            ],
            'cashier_queue' => [
                $this->itemDefinition($products['Grill'][0], 2, $choices['Grill'][2] ?? null, [$globalModifiers['Extra Cheese']], null, 'prepared', 'ready'),
                $this->itemDefinition($products['Drinks'][2], 2, $choices['Drinks'][1] ?? null, [], null, 'prepared', 'ready'),
            ],
            'paid_cash' => [
                $this->itemDefinition($products['Starters'][1], 2, $choices['Starters'][1] ?? null, [], null, 'served', 'served'),
                $this->itemDefinition($products['Desserts'][0], 1, $choices['Desserts'][0] ?? null, [], null, 'served', 'served'),
            ],
            'paid_split' => [
                $this->itemDefinition($products['Mains'][1], 2, $choices['Mains'][2] ?? null, [$restaurantModifiers['Add Fries'] ?? null], null, 'served', 'served'),
                $this->itemDefinition($products['Grill'][2], 1, $choices['Grill'][1] ?? null, [], null, 'served', 'served'),
                $this->itemDefinition($products['Drinks'][0], 2, $choices['Drinks'][0] ?? null, [$globalModifiers['No Ice']], null, 'served', 'served'),
            ],
            'refund_case' => [
                $this->itemDefinition($products['Grill'][0], 2, $choices['Grill'][1] ?? null, [$globalModifiers['Extra Cheese']], null, 'served', 'served'),
                $this->itemDefinition($products['Desserts'][2], 1, $choices['Desserts'][1] ?? null, [], null, 'served', 'served'),
            ],
            default => [
                $this->itemDefinition($products['Mains'][$variant % 3], 1, $choices['Mains'][0] ?? null, [], null, 'served', 'served'),
                $this->itemDefinition($products['Drinks'][($variant + 1) % 3], 1, $choices['Drinks'][1] ?? null, [], null, 'served', 'served'),
            ],
        };
    }

    private function itemDefinition(
        Product $product,
        int $quantity,
        ?CategoryChoice $choice,
        array $modifiers,
        ?string $note,
        string $status = 'pending',
        string $kdsStatus = 'pending',
    ): array {
        return [
            'product' => $product,
            'quantity' => $quantity,
            'choice' => $choice,
            'modifiers' => array_values(array_filter($modifiers)),
            'note' => $note,
            'status' => $status,
            'kds_status' => $kdsStatus,
        ];
    }

    private function createScenarioOrder(
        Branch $branch,
        ?Table $table,
        Customer $customer,
        Employee $employee,
        string $status,
        string $orderType,
        string $paymentStatus,
        ?string $paymentMethod,
        CarbonImmutable $orderedAt,
        array $items,
        ?CarbonImmutable $kdsSentAt,
        float $discount,
        string $discountType,
        ?string $couponCode,
        array $payments,
    ): Order {
        $order = Order::create([
            'branch_id' => $branch->id,
            'table_id' => $table?->id,
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'order_type' => $orderType,
            'status' => $status,
            'subtotal' => 0,
            'tax' => 0,
            'discount' => $discount,
            'discount_type' => $discountType,
            'total' => 0,
            'coupon_code' => $couponCode,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? $orderedAt->addMinutes(28) : null,
            'order_date' => $orderedAt->toDateString(),
            'created_at' => $orderedAt,
            'updated_at' => $orderedAt,
            'kds_sent_at' => $kdsSentAt,
        ]);

        $subtotal = 0.0;
        foreach ($items as $itemDefinition) {
            $orderItem = $this->createOrderItem($order, $itemDefinition, $orderedAt, $kdsSentAt);
            if (!in_array($orderItem->status, ['refunded', 'cancelled'], true)) {
                $subtotal += (float) $orderItem->price * (int) $orderItem->quantity;
            }
        }

        $discountAmount = $discountType === 'percent'
            ? round($subtotal * ($discount / 100), 2)
            : min($discount, $subtotal);
        $tax = round(max($subtotal - $discountAmount, 0) * 0.14, 2);
        $total = round(max($subtotal - $discountAmount, 0) + $tax, 2);

        $order->subtotal = round($subtotal, 2);
        $order->tax = $tax;
        $order->total = $total;
        $order->save();

        if (!empty($payments)) {
            $remaining = $total;
            foreach ($payments as $index => $paymentDefinition) {
                $amount = $index === array_key_last($payments)
                    ? round($remaining, 2)
                    : round($total * (float) $paymentDefinition['ratio'], 2);
                $remaining -= $amount;

                Payment::create([
                    'order_id' => $order->id,
                    'method' => $paymentDefinition['method'],
                    'amount' => $amount,
                    'created_at' => $orderedAt->addMinutes(25 + $index),
                    'updated_at' => $orderedAt->addMinutes(25 + $index),
                ]);

                Transaction::create([
                    'order_id' => $order->id,
                    'type' => 'income',
                    'amount' => $amount,
                    'method' => $paymentDefinition['method'],
                    'created_at' => $orderedAt->addMinutes(25 + $index),
                    'updated_at' => $orderedAt->addMinutes(25 + $index),
                ]);
            }

            $earnedPoints = max(1, (int) floor($order->total / 10));
            $customer->increment('loyalty_points', $earnedPoints);
            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'points' => $earnedPoints,
                'type' => 'earn',
                'created_at' => $orderedAt->addMinutes(28),
                'updated_at' => $orderedAt->addMinutes(28),
            ]);

            Receipt::create([
                'order_id' => $order->id,
                'receipt_number' => 'RCPT-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'content' => json_encode([
                    'order_id' => $order->id,
                    'branch' => $branch->name,
                    'customer' => $customer->name,
                    'total' => $order->total,
                ]),
            ]);
        }

        return $order->fresh(['items.product', 'payments']);
    }

    private function createOrderItem(Order $order, array $itemDefinition, CarbonImmutable $orderedAt, ?CarbonImmutable $kdsSentAt): OrderItem
    {
        /** @var \App\Models\Product $product */
        $product = $itemDefinition['product'];
        $modifierUnit = collect($itemDefinition['modifiers'])->sum(fn (Modifier $modifier) => (float) $modifier->price);
        $unitPrice = (float) $product->price + $modifierUnit;
        $quantity = (int) $itemDefinition['quantity'];
        $total = round($unitPrice * $quantity, 2);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'price' => $unitPrice,
            'total' => $total,
            'discount' => 0,
            'discount_type' => 'fixed',
            'status' => $itemDefinition['status'],
            'kds_status' => $itemDefinition['kds_status'],
            'item_note' => $itemDefinition['note'],
            'change_note' => $itemDefinition['note'],
            'kds_sent_at' => $kdsSentAt,
            'created_at' => $orderedAt,
            'updated_at' => $orderedAt,
        ]);

        if ($itemDefinition['choice'] instanceof CategoryChoice) {
            $answer = new CategoryAnswer();
            $answer->order_item_id = $orderItem->id;
            $answer->choice_id = $itemDefinition['choice']->id;
            $answer->image = null;
            $answer->save();
        }

        foreach ($itemDefinition['modifiers'] as $modifier) {
            OrderItemModifier::create([
                'order_item_id' => $orderItem->id,
                'modifier_id' => $modifier->id,
                'raw_modifier' => null,
            ]);
        }

        $this->decrementBranchStock($product, $quantity, $order->branch_id);

        return $orderItem;
    }

    private function decrementBranchStock(Product $product, int $quantity, int $branchId): void
    {
        $product->loadMissing('recipe.ingredients');
        if ($product->recipe) {
            foreach ($product->recipe->ingredients as $ingredient) {
                $pivotQuantity = (float) $ingredient->pivot->quantity;
                $branchStock = IngredientBranch::where('ingredient_id', $ingredient->id)
                    ->where('branch_id', $branchId)
                    ->first();

                if ($branchStock) {
                    $branchStock->stock = max(0, (float) $branchStock->stock - ($pivotQuantity * $quantity));
                    $branchStock->save();
                }

                $inventoryItem = InventoryItem::where('branch_id', $branchId)
                    ->where('name', $ingredient->name)
                    ->first();

                if ($inventoryItem) {
                    $inventoryItem->quantity = max(0, (float) $inventoryItem->quantity - ($pivotQuantity * $quantity));
                    $inventoryItem->save();
                }
            }
        }

        $product->stock = max(0, (int) $product->stock - $quantity);
        $product->save();
    }

    private function addRefundScenario(Order $order, User $waiterUser): void
    {
        $sourceItem = $order->items()->first();
        if (!$sourceItem || $sourceItem->quantity < 2) {
            return;
        }

        $before = $sourceItem->toArray();
        $sourceItem->quantity = 1;
        $sourceItem->total = round((float) $sourceItem->price, 2);
        $sourceItem->change_note = 'Guest kept one portion and refunded one.';
        $sourceItem->save();

        $refundedItem = $sourceItem->replicate(['parent_item_id']);
        $refundedItem->parent_item_id = $sourceItem->id;
        $refundedItem->quantity = 1;
        $refundedItem->status = 'refunded';
        $refundedItem->kds_status = 'refunded';
        $refundedItem->total = round((float) $sourceItem->price, 2);
        $refundedItem->refunded_quantity = 1;
        $refundedItem->refunded_amount = round((float) $sourceItem->price, 2);
        $refundedItem->change_note = 'Customer changed their mind after serving.';
        $refundedItem->save();

        Refund::create([
            'order_item_id' => $refundedItem->id,
            'refunded_by' => $waiterUser->id,
            'refund_type' => 'partial',
            'amount' => $refundedItem->refunded_amount,
            'reason' => 'Customer requested partial refund after serving.',
        ]);

        OrderItemHistory::create([
            'order_item_id' => $refundedItem->id,
            'action' => 'refund',
            'snapshot_before' => json_encode($before),
            'snapshot_after' => json_encode($refundedItem->toArray()),
            'note' => 'Partial refund seeded for demo.',
            'user_id' => $waiterUser->id,
        ]);

        $order->subtotal = $order->items()->whereNotIn('status', ['refunded', 'cancelled'])->sum(\DB::raw('price * quantity'));
        $order->tax = round((float) $order->subtotal * 0.14, 2);
        $order->total = round((float) $order->subtotal + (float) $order->tax, 2);
        $order->save();
    }

    private function logOrderStatuses(Order $order, array $statusLogMap): void
    {
        foreach ($statusLogMap as $userId => $statuses) {
            foreach ($statuses as $status) {
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $status,
                    'user_id' => $userId,
                ]);
            }
        }
    }

    private function createFeedbackForPaidOrder(Order $order, Customer $customer, string $message, int $rating): void
    {
        Feedback::firstOrCreate(
            ['order_id' => $order->id],
            [
                'customer_id' => $customer->id,
                'message' => $message,
                'rating' => $rating,
            ],
        );
    }

    private function seedBranchSummaries(Branch $branch): void
    {
        $paidOrders = Order::where('branch_id', $branch->id)
            ->where('payment_status', 'paid')
            ->get();

        $sales = (float) $paidOrders->sum('total');
        $expenses = round($sales * 0.28, 2);
        $summaryDate = CarbonImmutable::now()->subDay()->toDateString();

        SalesReport::updateOrCreate(
            ['branch_id' => $branch->id, 'report_date' => $summaryDate],
            [
                'total_sales' => $sales,
                'total_tax' => (float) $paidOrders->sum('tax'),
            ],
        );

        BranchPerformance::updateOrCreate(
            ['branch_id' => $branch->id, 'report_date' => $summaryDate],
            [
                'revenue' => $sales,
                'expenses' => $expenses,
            ],
        );

        FinancialSummary::updateOrCreate(
            ['branch_id' => $branch->id, 'summary_date' => $summaryDate],
            [
                'sales' => $sales,
                'expenses' => $expenses,
            ],
        );

        Expense::updateOrCreate(
            ['branch_id' => $branch->id, 'expense_date' => $summaryDate, 'category' => 'Operations'],
            [
                'amount' => round($expenses * 0.55, 2),
                'description' => 'Utilities, cleaning, and delivery packaging.',
            ],
        );

        Income::updateOrCreate(
            ['branch_id' => $branch->id, 'income_date' => $summaryDate, 'source' => 'POS Sales'],
            [
                'amount' => $sales,
                'description' => 'Daily POS sales imported from seeded orders.',
            ],
        );

        foreach (
            $paidOrders->flatMap(fn (Order $order) => $order->items)->groupBy('product_id')->take(5)
            as $productId => $items
        ) {
            ProductPerformance::updateOrCreate(
                ['product_id' => $productId, 'report_date' => $summaryDate],
                [
                    'units_sold' => (int) $items->sum('quantity'),
                    'total_revenue' => (float) $items->sum('total'),
                ],
            );
        }
    }

    private function venueBlueprints(): array
    {
        return [
            ['name' => 'Nile Flame Grill', 'kind' => 'restaurant'],
            ['name' => 'Cedar Route Kitchen', 'kind' => 'restaurant'],
            ['name' => 'Alexandria Catch House', 'kind' => 'restaurant'],
            ['name' => 'Cairo Kebab District', 'kind' => 'restaurant'],
            ['name' => 'Olive Table Bistro', 'kind' => 'restaurant'],
            ['name' => 'Desert Ember Steakhouse', 'kind' => 'restaurant'],
            ['name' => 'Saffron Gate Restaurant', 'kind' => 'restaurant'],
            ['name' => 'Bean Harbor Cafe', 'kind' => 'cafe'],
            ['name' => 'Lotus Brew Cafe', 'kind' => 'cafe'],
            ['name' => 'Corniche Roast Lab', 'kind' => 'cafe'],
            ['name' => 'Palm Lounge Cafe', 'kind' => 'cafe'],
            ['name' => 'Midnight Mocha Bar', 'kind' => 'cafe'],
            ['name' => 'Garden Cup Cafe', 'kind' => 'cafe'],
            ['name' => 'Oat & Honey Bakery Cafe', 'kind' => 'cafe'],
        ];
    }
}
