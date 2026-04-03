#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Product;
use App\Models\Modifier;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

define('SCENARIO_ROOT', dirname(__DIR__, 2));

require SCENARIO_ROOT.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require SCENARIO_ROOT.'/bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var HttpKernel $httpKernel */
$httpKernel = $app->make(HttpKernel::class);

$options = getopt('', ['fresh']);

$outputDir = SCENARIO_ROOT.'/docs/generated';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

if (array_key_exists('fresh', $options)) {
    Artisan::call('migrate:fresh', [
        '--seed' => true,
        '--force' => true,
    ]);

    file_put_contents(
        $outputDir.'/golden-first-day-migrate.log',
        Artisan::output()
    );
}

function api(
    HttpKernel $kernel,
    string $method,
    string $uri,
    array $payload = [],
    ?string $token = null
): array {
    app('auth')->forgetGuards();

    $server = [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_HOST' => 'localhost',
        'SERVER_NAME' => 'localhost',
        'SERVER_PORT' => '8000',
        'HTTPS' => 'off',
    ];

    if ($token) {
        $server['HTTP_AUTHORIZATION'] = "Bearer {$token}";
    }

    $request = Request::create(
        $uri,
        strtoupper($method),
        [],
        [],
        [],
        $server,
        empty($payload) ? null : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );

    $response = $kernel->handle($request);
    $content = $response->getContent();
    $decoded = null;

    if (is_string($content) && $content !== '') {
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = ['raw' => $content];
        }
    } else {
        $decoded = [];
    }

    $kernel->terminate($request, $response);
    app('auth')->forgetGuards();

    $result = [
        'method' => strtoupper($method),
        'uri' => $uri,
        'status' => $response->getStatusCode(),
        'body' => $decoded,
    ];

    if ($result['status'] >= 400) {
        throw new RuntimeException(sprintf(
            "%s %s failed with %d: %s",
            $result['method'],
            $result['uri'],
            $result['status'],
            json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ));
    }

    return $result;
}

function body(array $response): array
{
    return $response['body'] ?? [];
}

function firstDataItem(array $response): array
{
    $data = body($response)['data'] ?? [];

    if (!is_array($data) || $data === []) {
        return [];
    }

    if (array_is_list($data)) {
        return (array) ($data[0] ?? []);
    }

    return $data;
}

$steps = [];
$stepNumber = 1;

$recordStep = function (
    string $surface,
    string $page,
    string $actorRole,
    string $actorName,
    string $action,
    string $result,
    array $context = []
) use (&$steps, &$stepNumber): void {
    $steps[] = array_merge([
        'step' => $stepNumber++,
        'surface' => $surface,
        'page' => $page,
        'actor_role' => $actorRole,
        'actor_name' => $actorName,
        'action' => $action,
        'result' => $result,
    ], $context);
};

$restaurantBlueprint = [
    'name' => 'Harbor Ember Kitchen',
    'kind' => 'restaurant',
    'owner' => [
        'name' => 'Rana Soliman',
        'email' => 'owner.harbor-ember-kitchen@example.com',
        'password' => 'password',
    ],
    'stakeholder' => [
        'name' => 'Hossam Gaber',
        'email' => 'stakeholder.harbor-ember-kitchen@example.com',
        'password' => 'password',
    ],
    'branches' => [
        [
            'name' => 'Downtown Flagship',
            'location' => 'Downtown Cairo',
            'staff' => [
                ['name' => 'Dina Farouk', 'role' => 'supervisor', 'types' => [], 'position' => 'Branch Supervisor'],
                ['name' => 'Karim Adel', 'role' => 'staff', 'types' => ['waiter'], 'position' => 'Waiter'],
                ['name' => 'Salma Nasser', 'role' => 'staff', 'types' => ['cashier'], 'position' => 'Cashier'],
                ['name' => 'Tamer Wael', 'role' => 'staff', 'types' => ['kitchen'], 'position' => 'Kitchen Lead'],
            ],
            'tables' => [
                ['name' => 'T1 Garden', 'seats' => 2],
                ['name' => 'T2 Window', 'seats' => 2],
                ['name' => 'T3 Family', 'seats' => 4],
                ['name' => 'T4 Lounge', 'seats' => 4],
            ],
        ],
        [
            'name' => 'New Cairo Studio',
            'location' => 'New Cairo',
            'staff' => [
                ['name' => 'Nour Hassan', 'role' => 'staff', 'types' => ['waiter'], 'position' => 'Waiter'],
                ['name' => 'Ahmed Samy', 'role' => 'staff', 'types' => ['cashier'], 'position' => 'Cashier'],
                ['name' => 'Mina Wagdy', 'role' => 'staff', 'types' => ['kitchen'], 'position' => 'Kitchen Operator'],
            ],
            'tables' => [
                ['name' => 'N1 Patio', 'seats' => 2],
                ['name' => 'N2 Corner', 'seats' => 4],
            ],
        ],
        [
            'name' => 'Maadi Terrace',
            'location' => 'Maadi',
            'staff' => [
                ['name' => 'Mariam Fares', 'role' => 'staff', 'types' => ['waiter'], 'position' => 'Waiter'],
                ['name' => 'Omar Lotfy', 'role' => 'staff', 'types' => ['cashier'], 'position' => 'Cashier'],
                ['name' => 'Lina Atef', 'role' => 'staff', 'types' => ['kitchen'], 'position' => 'Kitchen Operator'],
            ],
            'tables' => [
                ['name' => 'M1 Terrace', 'seats' => 2],
                ['name' => 'M2 Family', 'seats' => 4],
            ],
        ],
    ],
];

$catalogBlueprint = [
    'Breakfast & Brunch' => [
        ['name' => 'Cedar Chicken Bowl', 'price' => 198, 'stock' => 16, 'min_stock' => 8, 'image' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Harissa Penne', 'price' => 214, 'stock' => 12, 'min_stock' => 6, 'image' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=1200&q=80'],
    ],
    'Signature Drinks' => [
        ['name' => 'Spanish Latte', 'price' => 92, 'stock' => 30, 'min_stock' => 12, 'image' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80'],
        ['name' => 'Citrus Sparkling Cooler', 'price' => 84, 'stock' => 24, 'min_stock' => 10, 'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1200&q=80'],
    ],
    'Desserts' => [
        ['name' => 'Sea Salt Brownie', 'price' => 74, 'stock' => 6, 'min_stock' => 8, 'image' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=1200&q=80'],
    ],
];

$scenario = [
    'title' => 'Golden First Day Scenario',
    'generated_at' => now()->toIso8601String(),
    'environment' => [
        'db_connection' => config('database.default'),
        'app_env' => config('app.env'),
    ],
    'restaurant' => null,
    'accounts' => [],
    'branches' => [],
    'products' => [],
    'tables' => [],
    'orders' => [],
    'dashboard_summary' => [],
    'customer_portal' => [],
    'steps' => [],
    'captures' => [],
];

$adminLogin = api($httpKernel, 'POST', '/api/login', [
    'email' => 'admin@restaurant-suite.com',
    'password' => 'password',
]);

$adminToken = body($adminLogin)['token'];
$adminUser = body($adminLogin)['user'];
$recordStep(
    'Web Dashboard',
    'Login',
    'Platform Admin',
    $adminUser['name'] ?? 'Restaurant Suite Admin',
    'Signed into the platform dashboard with the seeded admin account.',
    'Admin session token was issued successfully and cross-restaurant control is available.',
    ['api' => $adminLogin]
);

$restaurantResponse = api($httpKernel, 'POST', '/api/restaurants', [
    'name' => $restaurantBlueprint['name'],
    'kind' => $restaurantBlueprint['kind'],
], $adminToken);
$restaurant = body($restaurantResponse);
$restaurantId = (int) $restaurant['id'];

$scenario['restaurant'] = [
    'id' => $restaurantId,
    'name' => $restaurant['name'],
    'kind' => $restaurant['kind'],
];

$recordStep(
    'Web Dashboard',
    'Restaurants',
    'Platform Admin',
    $adminUser['name'] ?? 'Restaurant Suite Admin',
    sprintf('Opened Restaurants, clicked Add Restaurant, entered `%s`, selected `%s`, and clicked Save.', $restaurant['name'], $restaurant['kind']),
    'The new restaurant was created and became available for branch, owner, menu, and employee setup.',
    ['api' => $restaurantResponse]
);

$branches = [];
foreach ($restaurantBlueprint['branches'] as $branchBlueprint) {
    $response = api($httpKernel, 'POST', '/api/branches', [
        'restaurant_id' => $restaurantId,
        'name' => $branchBlueprint['name'],
        'location' => $branchBlueprint['location'],
    ], $adminToken);

    $branch = body($response);
    $branch['staff_blueprint'] = $branchBlueprint['staff'];
    $branch['tables_blueprint'] = $branchBlueprint['tables'];
    $branches[] = $branch;
}

$scenario['branches'] = array_map(fn (array $branch) => [
    'id' => $branch['id'],
    'name' => $branch['name'],
    'location' => $branch['location'],
], $branches);

$recordStep(
    'Web Dashboard',
    'Branches',
    'Platform Admin',
    $adminUser['name'] ?? 'Restaurant Suite Admin',
    'Opened Branches, clicked Add Branch three times, and created Downtown Flagship, New Cairo Studio, and Maadi Terrace for the new restaurant.',
    'All three operational branches were created and linked to Harbor Ember Kitchen.',
    ['branches' => $scenario['branches']]
);

$createUser = function (array $payload) use ($httpKernel, $adminToken): array {
    return body(api($httpKernel, 'POST', '/api/users', $payload, $adminToken));
};

$ownerPayload = [
    'name' => $restaurantBlueprint['owner']['name'],
    'email' => $restaurantBlueprint['owner']['email'],
    'password' => $restaurantBlueprint['owner']['password'],
    'role' => 'owner',
    'restaurant_id' => $restaurantId,
    'types' => ['owner'],
];
$ownerCreate = $createUser($ownerPayload);

$stakeholderPayload = [
    'name' => $restaurantBlueprint['stakeholder']['name'],
    'email' => $restaurantBlueprint['stakeholder']['email'],
    'password' => $restaurantBlueprint['stakeholder']['password'],
    'role' => 'owner',
    'restaurant_id' => $restaurantId,
    'types' => ['owner'],
];
$stakeholderCreate = $createUser($stakeholderPayload);

$scenario['accounts']['owner'] = [
    'name' => $ownerPayload['name'],
    'email' => $ownerPayload['email'],
    'password' => $ownerPayload['password'],
];
$scenario['accounts']['stakeholder'] = [
    'name' => $stakeholderPayload['name'],
    'email' => $stakeholderPayload['email'],
    'password' => $stakeholderPayload['password'],
];

$staffAccounts = [];
foreach ($branches as $branchIndex => $branch) {
    $branchSlug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]+/', '', $branch['name'])));

    foreach ($branch['staff_blueprint'] as $staffIndex => $staff) {
        $emailPrefix = strtolower(str_replace(' ', '.', $staff['name']));
        $email = preg_replace('/[^a-z0-9.]+/', '', $emailPrefix).".{$branchSlug}@example.com";

        $payload = [
            'name' => $staff['name'],
            'email' => $email,
            'password' => 'password',
            'role' => $staff['role'],
            'branch_id' => $branch['id'],
            'types' => $staff['types'],
        ];

        $created = $createUser($payload);

        $staffAccounts[] = [
            'branch_id' => $branch['id'],
            'branch_name' => $branch['name'],
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'role' => $payload['role'],
            'types' => $payload['types'],
            'user' => $created['user'] ?? null,
            'position' => $staff['position'],
        ];
    }
}

$scenario['accounts']['staff'] = array_map(fn (array $account) => [
    'branch_name' => $account['branch_name'],
    'name' => $account['name'],
    'email' => $account['email'],
    'password' => $account['password'],
    'role' => $account['role'],
    'types' => $account['types'],
], $staffAccounts);

$recordStep(
    'Web Dashboard',
    'Users',
    'Platform Admin',
    $adminUser['name'] ?? 'Restaurant Suite Admin',
    'Opened Users and provisioned the owner, stakeholder, supervisor, waiter, cashier, and kitchen users for the new restaurant.',
    'The restaurant now has authenticated accounts for admin control, owner oversight, and branch operations on the waiter, cashier, kitchen, and owner apps.',
    ['accounts' => $scenario['accounts']]
);

$ownerLogin = api($httpKernel, 'POST', '/api/login', [
    'email' => $ownerPayload['email'],
    'password' => $ownerPayload['password'],
]);
$ownerToken = body($ownerLogin)['token'];
$ownerUser = body($ownerLogin)['user'];

$recordStep(
    'Web Dashboard',
    'Login',
    'Owner',
    $ownerPayload['name'],
    'Signed into the owner dashboard for Harbor Ember Kitchen.',
    'Owner-scoped session is active and restricted to the new restaurant.',
    ['api' => $ownerLogin]
);

$branchArtifacts = [];

foreach ($branches as $branch) {
    $branchArtifacts[$branch['id']] = [
        'categories' => [],
        'products' => [],
        'tables' => [],
        'menu' => null,
    ];

    foreach (array_keys($catalogBlueprint) as $categoryName) {
        $categoryResponse = api($httpKernel, 'POST', '/api/categories', [
            'name' => $categoryName,
            'branch_id' => $branch['id'],
        ], $ownerToken);

        $category = body($categoryResponse);
        $branchArtifacts[$branch['id']]['categories'][$categoryName] = $category;
    }

    foreach ($branch['tables_blueprint'] as $tableBlueprint) {
        $tableResponse = api($httpKernel, 'POST', '/api/tables', [
            'branch_id' => $branch['id'],
            'name' => $tableBlueprint['name'],
            'seats' => $tableBlueprint['seats'],
        ], $ownerToken);

        $table = body($tableResponse);
        $branchArtifacts[$branch['id']]['tables'][$table['name']] = $table;
        $scenario['tables'][] = [
            'id' => $table['id'],
            'branch_name' => $branch['name'],
            'name' => $table['name'],
            'seats' => $table['seats'],
        ];
    }

    foreach ($catalogBlueprint as $categoryName => $products) {
        foreach ($products as $productBlueprint) {
            $productResponse = api($httpKernel, 'POST', '/api/products', [
                'name' => $productBlueprint['name'],
                'category_id' => $branchArtifacts[$branch['id']]['categories'][$categoryName]['id'],
                'branch_id' => $branch['id'],
                'price' => $productBlueprint['price'],
                'is_available' => true,
                'min_stock' => $productBlueprint['min_stock'],
            ], $ownerToken);

            $product = body($productResponse);
            Product::query()->find($product['id'])?->update([
                'image' => $productBlueprint['image'],
                'stock' => $productBlueprint['stock'],
            ]);

            $branchArtifacts[$branch['id']]['products'][$product['name']] = $product + [
                'image' => $productBlueprint['image'],
                'stock' => $productBlueprint['stock'],
                'min_stock' => $productBlueprint['min_stock'],
            ];

            $scenario['products'][] = [
                'id' => $product['id'],
                'branch_name' => $branch['name'],
                'category_name' => $categoryName,
                'name' => $product['name'],
                'price' => $productBlueprint['price'],
                'stock' => $productBlueprint['stock'],
                'min_stock' => $productBlueprint['min_stock'],
            ];
        }
    }

    $menuResponse = api($httpKernel, 'POST', '/api/menus', [
        'name' => $branch['name'].' All Day Menu',
        'branch_id' => $branch['id'],
        'categories' => array_values(array_map(
            fn (array $category) => $category['id'],
            $branchArtifacts[$branch['id']]['categories']
        )),
    ], $ownerToken);

    $branchArtifacts[$branch['id']]['menu'] = body($menuResponse)['data'] ?? null;

    foreach ($staffAccounts as $staffAccount) {
        if ((int) $staffAccount['branch_id'] !== (int) $branch['id']) {
            continue;
        }

        api($httpKernel, 'POST', '/api/employees', [
            'name' => $staffAccount['name'],
            'position' => $staffAccount['position'],
            'base_salary' => $staffAccount['position'] === 'Branch Supervisor' ? 12500 : 7800,
            'branch_id' => $branch['id'],
            'hired_at' => '2026-04-03',
        ], $ownerToken);
    }

    $inventoryTargets = [
        ['product' => 'Spanish Latte', 'quantity' => 28, 'min_stock' => 12],
        ['product' => 'Cedar Chicken Bowl', 'quantity' => 7, 'min_stock' => 10],
        ['product' => 'Sea Salt Brownie', 'quantity' => 3, 'min_stock' => 5],
    ];

    foreach ($inventoryTargets as $target) {
        $inventoryProduct = $branchArtifacts[$branch['id']]['products'][$target['product']];

        api($httpKernel, 'POST', '/api/inventory-items', [
            'branch_id' => $branch['id'],
            'product_id' => $inventoryProduct['id'],
            'unit' => 'pcs',
            'quantity' => $target['quantity'],
            'min_stock' => $target['min_stock'],
        ], $ownerToken);
    }
}

$recordStep(
    'Web Dashboard',
    'Catalog & Tables',
    'Owner',
    $ownerPayload['name'],
    'Opened Categories, Products, Menus, Tables, Inventory, and Employees to create the launch catalog, dining room map, team records, and low-stock monitoring for each branch.',
    'Each branch now has a working menu, sellable products, dine-in tables, employee records, and inventory thresholds for the first trading day.',
    [
        'branch_catalog' => array_map(fn (array $artifact) => [
            'category_count' => count($artifact['categories']),
            'product_count' => count($artifact['products']),
            'table_count' => count($artifact['tables']),
            'menu_name' => $artifact['menu']['name'] ?? null,
        ], $branchArtifacts),
    ]
);

$findStaff = function (string $branchName, string $type) use ($staffAccounts): array {
    foreach ($staffAccounts as $staffAccount) {
        if ($staffAccount['branch_name'] === $branchName && in_array($type, $staffAccount['types'], true)) {
            return $staffAccount;
        }
    }

    throw new RuntimeException("Could not find {$type} user for {$branchName}");
};

$loginStaff = function (array $account, string $type) use ($httpKernel): array {
    $response = api($httpKernel, 'POST', '/api/login', [
        'email' => $account['email'],
        'password' => $account['password'],
        'type' => $type,
    ]);

    return [
        'token' => body($response)['token'],
        'user' => body($response)['user'],
        'response' => $response,
    ];
};

$downtownWaiter = $loginStaff($findStaff('Downtown Flagship', 'waiter'), 'waiter');
$downtownKitchen = $loginStaff($findStaff('Downtown Flagship', 'kitchen'), 'kitchen');
$downtownCashier = $loginStaff($findStaff('Downtown Flagship', 'cashier'), 'cashier');

$newCairoWaiter = $loginStaff($findStaff('New Cairo Studio', 'waiter'), 'waiter');
$newCairoKitchen = $loginStaff($findStaff('New Cairo Studio', 'kitchen'), 'kitchen');
$newCairoCashier = $loginStaff($findStaff('New Cairo Studio', 'cashier'), 'cashier');

$maadiWaiter = $loginStaff($findStaff('Maadi Terrace', 'waiter'), 'waiter');
$maadiKitchen = $loginStaff($findStaff('Maadi Terrace', 'kitchen'), 'kitchen');
$maadiCashier = $loginStaff($findStaff('Maadi Terrace', 'cashier'), 'cashier');

$customerLogin = api($httpKernel, 'POST', '/api/customer/auth/login', [
    'name' => 'Mona Sami',
    'phone' => '01017770001',
    'email' => 'mona.sami@example.com',
]);
$customerToken = body($customerLogin)['token'];
$modifierIds = [
    'oat_milk' => (int) Modifier::query()->where('name', 'Oat Milk')->value('id'),
    'extra_shot' => (int) Modifier::query()->where('name', 'Extra Shot')->value('id'),
    'no_ice' => (int) Modifier::query()->where('name', 'No Ice')->value('id'),
];

$customerHome = api($httpKernel, 'GET', '/api/customer/home', [], $customerToken);
$recordStep(
    'Customer App',
    'Home',
    'Customer',
    'Mona Sami',
    'Logged into the customer app with a new phone number and opened the home screen.',
    'The new restaurant appears in the marketplace listing, ready for discovery before the first dine-in order is placed.',
    ['api' => $customerHome]
);

$downtownArtifacts = $branchArtifacts[$branches[0]['id']];

$firstOrder = api($httpKernel, 'POST', '/api/mobile/orders', [
    'table_id' => $downtownArtifacts['tables']['T1 Garden']['id'],
    'customer_name' => 'Mona Sami',
    'customer_phone' => '01017770001',
    'customer_email' => 'mona.sami@example.com',
    'items' => [
        [
            'product_id' => $downtownArtifacts['products']['Cedar Chicken Bowl']['id'],
            'quantity' => 2,
            'note' => 'One without onion on the pass.',
        ],
        [
            'product_id' => $downtownArtifacts['products']['Spanish Latte']['id'],
            'quantity' => 2,
            'modifiers' => [
                ['modifier_id' => $modifierIds['oat_milk']],
                ['modifier_id' => $modifierIds['extra_shot']],
            ],
            'note' => 'Less sweet for the second cup.',
        ],
        [
            'product_id' => $downtownArtifacts['products']['Citrus Sparkling Cooler']['id'],
            'quantity' => 1,
            'modifiers' => [
                ['modifier_id' => $modifierIds['no_ice']],
            ],
        ],
    ],
], $downtownWaiter['token']);

$firstOrderBody = body($firstOrder)['order'];
$firstOrderId = (int) $firstOrderBody['id'];
$firstOrderItems = collect($firstOrderBody['items'])->keyBy('product.name');

$recordStep(
    'Waiter App',
    'Table T1 Garden',
    'Waiter',
    'Karim Adel',
    'Opened T1 Garden, attached Mona Sami to the table, added full meals and drinks with modifiers, then saved the dine-in order.',
    sprintf('Order #%d was created on Downtown Flagship and the table became occupied.', $firstOrderId),
    ['api' => $firstOrder]
);

$dessertAdd = api($httpKernel, 'POST', '/api/mobile/orders', [
    'table_id' => $downtownArtifacts['tables']['T1 Garden']['id'],
    'items' => [
        [
            'product_id' => $downtownArtifacts['products']['Sea Salt Brownie']['id'],
            'quantity' => 1,
            'note' => 'Send after mains.',
        ],
    ],
], $downtownWaiter['token']);

$dessertOrder = body($dessertAdd)['order'];
$dessertItem = collect($dessertOrder['items'])->firstWhere('product.name', 'Sea Salt Brownie');

api($httpKernel, 'POST', "/api/mobile/orders/{$firstOrderId}/send-to-kds", [], $downtownWaiter['token']);
$recordStep(
    'Waiter App',
    'Order Composer',
    'Waiter',
    'Karim Adel',
    'Added dessert to the same open table and sent the full ticket to the kitchen display.',
    'Kitchen now has a queued dine-in ticket with mains, drinks, and dessert for Mona Sami.',
    ['order_id' => $firstOrderId]
);

$latteItem = $firstOrderItems['Spanish Latte'];
api($httpKernel, 'PATCH', "/api/mobile/order-items/{$latteItem['id']}/refund-change", [
    'action' => 'change',
    'quantity' => 1,
    'note' => 'Guest kept one latte only.',
], $downtownWaiter['token']);

api($httpKernel, 'PATCH', "/api/mobile/order-items/{$dessertItem['id']}/refund-change", [
    'action' => 'refund',
    'note' => 'Dessert returned before service.',
], $downtownWaiter['token']);

$recordStep(
    'Waiter App',
    'Order Adjustments',
    'Waiter',
    'Karim Adel',
    'Reduced the latte quantity after the guests changed their mind, then refunded the brownie that the table no longer wanted.',
    'The live order total was recalculated and the refunded dessert dropped out of active service.',
    ['changed_item_id' => $latteItem['id'], 'refunded_item_id' => $dessertItem['id']]
);

$kdsOrders = api($httpKernel, 'GET', '/api/mobile/kds/orders', [], $downtownKitchen['token']);
$firstKdsOrder = collect(body($kdsOrders)['data'])->firstWhere('id', $firstOrderId);
foreach ($firstKdsOrder['items'] as $item) {
    api($httpKernel, 'PATCH', "/api/mobile/kds/order-items/{$item['id']}", [
        'status' => 'preparing',
    ], $downtownKitchen['token']);
    api($httpKernel, 'PATCH', "/api/mobile/kds/order-items/{$item['id']}", [
        'status' => 'ready',
    ], $downtownKitchen['token']);
}

$recordStep(
    'Kitchen App',
    'Downtown KDS',
    'Kitchen',
    'Tamer Wael',
    'Opened the KDS board, advanced the active items from queued to preparing, then to ready.',
    'All non-refunded lines for Mona’s order became ready for cashier handoff.',
    ['api' => $kdsOrders]
);

api($httpKernel, 'PATCH', "/api/mobile/orders/{$firstOrderId}/send-to-cashier", [], $downtownWaiter['token']);
$firstPaid = api($httpKernel, 'POST', "/api/mobile/orders/{$firstOrderId}/pay", [
    'payments' => [
        ['method' => 'card', 'amount' => (float) Order::query()->findOrFail($firstOrderId)->total],
    ],
], $downtownCashier['token']);

$recordStep(
    'Cashier App',
    'Settlement Queue',
    'Cashier',
    'Salma Nasser',
    'Pulled Mona’s ready order into cashier, confirmed the final total, and completed payment by card.',
    'The order closed as paid, the table reopened, and Mona earned loyalty points for the completed visit.',
    ['api' => $firstPaid]
);

$drinksOnlyOrder = api($httpKernel, 'POST', '/api/mobile/orders', [
    'table_id' => $downtownArtifacts['tables']['T2 Window']['id'],
    'customer_name' => 'Omar Tarek',
    'customer_phone' => '01018880002',
    'items' => [
        [
            'product_id' => $downtownArtifacts['products']['Citrus Sparkling Cooler']['id'],
            'quantity' => 2,
            'modifiers' => [['modifier_id' => $modifierIds['no_ice']]],
        ],
        [
            'product_id' => $downtownArtifacts['products']['Spanish Latte']['id'],
            'quantity' => 1,
        ],
    ],
], $downtownWaiter['token']);
$drinksOnlyOrderId = (int) body($drinksOnlyOrder)['order']['id'];
api($httpKernel, 'POST', "/api/mobile/orders/{$drinksOnlyOrderId}/send-to-kds", [], $downtownWaiter['token']);
$downtownKdsForSecond = api($httpKernel, 'GET', '/api/mobile/kds/orders', [], $downtownKitchen['token']);
$secondKdsOrder = collect(body($downtownKdsForSecond)['data'])->firstWhere('id', $drinksOnlyOrderId);
foreach ($secondKdsOrder['items'] as $item) {
    api($httpKernel, 'PATCH', "/api/mobile/kds/order-items/{$item['id']}", [
        'status' => 'ready',
    ], $downtownKitchen['token']);
}
api($httpKernel, 'PATCH', "/api/mobile/orders/{$drinksOnlyOrderId}/send-to-cashier", [], $downtownWaiter['token']);
api($httpKernel, 'POST', "/api/mobile/orders/{$drinksOnlyOrderId}/pay", [
    'payments' => [
        ['method' => 'cash', 'amount' => (float) Order::query()->findOrFail($drinksOnlyOrderId)->total],
    ],
], $downtownCashier['token']);

$recordStep(
    'Waiter + Kitchen + Cashier',
    'T2 Window',
    'Service Team',
    'Karim Adel / Tamer Wael / Salma Nasser',
    'Processed a drinks-only dine-in visit from order creation through kitchen readiness and cash settlement.',
    'The first branch now has both card and cash sales on the same trading day.',
    ['order_id' => $drinksOnlyOrderId]
);

$familyOrder = api($httpKernel, 'POST', '/api/mobile/orders', [
    'table_id' => $downtownArtifacts['tables']['T3 Family']['id'],
    'customer_name' => 'Laila Hassan',
    'customer_phone' => '01019990003',
    'items' => [
        [
            'product_id' => $downtownArtifacts['products']['Harissa Penne']['id'],
            'quantity' => 2,
        ],
        [
            'product_id' => $downtownArtifacts['products']['Spanish Latte']['id'],
            'quantity' => 2,
        ],
    ],
], $downtownWaiter['token']);
$familyOrderId = (int) body($familyOrder)['order']['id'];
api($httpKernel, 'POST', "/api/mobile/orders/{$familyOrderId}/send-to-kds", [], $downtownWaiter['token']);
api($httpKernel, 'PATCH', "/api/mobile/tables/{$downtownArtifacts['tables']['T3 Family']['id']}/move", [
    'to_table_id' => $downtownArtifacts['tables']['T4 Lounge']['id'],
], $downtownWaiter['token']);

$downtownTablesAfterMove = api($httpKernel, 'GET', '/api/mobile/tables', [], $downtownWaiter['token']);
$movedTable = collect(body($downtownTablesAfterMove)['data'])->firstWhere('id', $downtownArtifacts['tables']['T4 Lounge']['id']);
$movedOrder = collect($movedTable['orders'])->first();
$movedOrderId = (int) $movedOrder['id'];
$movedDrinkItem = collect($movedOrder['items'])->firstWhere('product.name', 'Spanish Latte');
api($httpKernel, 'PATCH', "/api/mobile/order-items/{$movedDrinkItem['id']}/refund-change", [
    'action' => 'change',
    'quantity' => 1,
    'note' => 'Family kept one latte after moving tables.',
], $downtownWaiter['token']);

$downtownKdsAfterMove = api($httpKernel, 'GET', '/api/mobile/kds/orders', [], $downtownKitchen['token']);
$movedKdsOrder = collect(body($downtownKdsAfterMove)['data'])->firstWhere('id', $movedOrderId);
foreach ($movedKdsOrder['items'] as $item) {
    api($httpKernel, 'PATCH', "/api/mobile/kds/order-items/{$item['id']}", [
        'status' => 'ready',
    ], $downtownKitchen['token']);
}
api($httpKernel, 'PATCH', "/api/mobile/orders/{$movedOrderId}/send-to-cashier", [], $downtownWaiter['token']);
$movedTotal = (float) Order::query()->findOrFail($movedOrderId)->total;
api($httpKernel, 'POST', "/api/mobile/orders/{$movedOrderId}/pay", [
    'payments' => [
        ['method' => 'card', 'amount' => round($movedTotal * 0.6, 2)],
        ['method' => 'cash', 'amount' => round($movedTotal - round($movedTotal * 0.6, 2), 2)],
    ],
], $downtownCashier['token']);

$recordStep(
    'Waiter + Kitchen + Cashier',
    'T3 Family to T4 Lounge',
    'Service Team',
    'Karim Adel / Tamer Wael / Salma Nasser',
    'Moved a family table mid-service from T3 Family to T4 Lounge, reduced a drink line, completed kitchen production, and settled the final bill with split payment.',
    'The system preserved the audit trail of the table move while still allowing the visit to finish and pay successfully.',
    ['source_order_id' => $familyOrderId, 'target_order_id' => $movedOrderId]
);

$simpleBranchFlow = function (
    string $customerName,
    string $customerPhone,
    array $waiter,
    array $kitchen,
    array $cashier,
    array $artifacts
) use ($httpKernel): array {
    $order = api($httpKernel, 'POST', '/api/mobile/orders', [
        'table_id' => array_values($artifacts['tables'])[0]['id'],
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'items' => [
            [
                'product_id' => $artifacts['products']['Spanish Latte']['id'],
                'quantity' => 2,
            ],
            [
                'product_id' => $artifacts['products']['Citrus Sparkling Cooler']['id'],
                'quantity' => 1,
            ],
        ],
    ], $waiter['token']);

    $orderId = (int) body($order)['order']['id'];
    api($httpKernel, 'POST', "/api/mobile/orders/{$orderId}/send-to-kds", [], $waiter['token']);
    $kdsOrders = api($httpKernel, 'GET', '/api/mobile/kds/orders', [], $kitchen['token']);
    $ticket = collect(body($kdsOrders)['data'])->firstWhere('id', $orderId);
    foreach ($ticket['items'] as $item) {
        api($httpKernel, 'PATCH', "/api/mobile/kds/order-items/{$item['id']}", ['status' => 'ready'], $kitchen['token']);
    }
    api($httpKernel, 'PATCH', "/api/mobile/orders/{$orderId}/send-to-cashier", [], $waiter['token']);
    api($httpKernel, 'POST', "/api/mobile/orders/{$orderId}/pay", [
        'payments' => [
            ['method' => 'card', 'amount' => (float) Order::query()->findOrFail($orderId)->total],
        ],
    ], $cashier['token']);

    return ['order_id' => $orderId];
};

$newCairoFlow = $simpleBranchFlow(
    'Nada Wael',
    '01016660004',
    $newCairoWaiter,
    $newCairoKitchen,
    $newCairoCashier,
    $branchArtifacts[$branches[1]['id']]
);
$maadiFlow = $simpleBranchFlow(
    'Sara Gamal',
    '01015550005',
    $maadiWaiter,
    $maadiKitchen,
    $maadiCashier,
    $branchArtifacts[$branches[2]['id']]
);

$recordStep(
    'Multi-Branch Operations',
    'New Cairo Studio + Maadi Terrace',
    'Branch Teams',
    'Parallel service crews',
    'Processed light paid service in the second and third branches so the owner can review branch comparison, payment mix, and cross-branch performance on the first day.',
    'The restaurant now has paid activity across all branches for the end-of-day owner review.',
    ['orders' => [$newCairoFlow['order_id'], $maadiFlow['order_id']]]
);

$customerOrders = api($httpKernel, 'GET', '/api/customer/orders', [], $customerToken);
$customerLoyalty = api($httpKernel, 'GET', '/api/customer/loyalty', [], $customerToken);
$scenario['customer_portal'] = [
    'home' => body($customerHome),
    'orders' => body($customerOrders),
    'loyalty' => body($customerLoyalty),
];

$recordStep(
    'Customer App',
    'Orders & Rewards',
    'Customer',
    'Mona Sami',
    'Reopened the customer app after payment to check visit history and loyalty.',
    'The first dine-in visit now appears in order history with updated loyalty points and the correct restaurant and branch names.',
    [
        'orders_count' => body($customerOrders)['meta']['total'] ?? null,
        'loyalty_count' => body($customerLoyalty)['meta']['total'] ?? null,
    ]
);

$ownerDashboard = api($httpKernel, 'GET', '/api/dashboard/summary', [], $ownerToken);
$scenario['dashboard_summary'] = body($ownerDashboard);

$recordStep(
    'Web Dashboard',
    'Owner Dashboard',
    'Owner',
    $ownerPayload['name'],
    'Opened the live dashboard at close of day to review revenue, orders, payment mix, branch performance, low stock, and recent orders.',
    'The dashboard reflects the completed first day across three branches, including paid revenue, mixed tenders, loyalty activity, and low-stock warnings.',
    ['api' => $ownerDashboard]
);

$scenario['orders'] = [
    'mona_visit' => ['order_id' => $firstOrderId],
    'drinks_only' => ['order_id' => $drinksOnlyOrderId],
    'family_move' => ['source_order_id' => $familyOrderId, 'target_order_id' => $movedOrderId],
    'new_cairo' => $newCairoFlow,
    'maadi' => $maadiFlow,
];

$scenario['captures'] = [
    [
        'file' => 'admin-restaurants.png',
        'route' => '/#/restaurants',
        'user' => 'admin',
        'token' => $adminToken,
        'user_json' => json_encode($adminUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'admin-branches.png',
        'route' => '/#/branches',
        'user' => 'admin',
        'token' => $adminToken,
        'user_json' => json_encode($adminUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'admin-users.png',
        'route' => '/#/users',
        'user' => 'admin',
        'token' => $adminToken,
        'user_json' => json_encode($adminUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-products.png',
        'route' => '/#/products',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-menus.png',
        'route' => '/#/menus',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-tables.png',
        'route' => '/#/tables',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-employees.png',
        'route' => '/#/employees',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-orders.png',
        'route' => '/#/orders',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
    [
        'file' => 'owner-dashboard-end-of-day.png',
        'route' => '/#/dashboard',
        'user' => 'owner',
        'token' => $ownerToken,
        'user_json' => json_encode($ownerUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ],
];

$scenario['steps'] = $steps;

file_put_contents(
    $outputDir.'/golden-first-day-scenario.json',
    json_encode($scenario, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

echo "Scenario written to {$outputDir}/golden-first-day-scenario.json\n";
