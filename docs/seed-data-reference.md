# Restaurant Suite Demo Seed Data

This project now uses a full demo seeder by default:

- [DatabaseSeeder.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/database/seeders/DatabaseSeeder.php)
- [RestaurantSuiteDemoSeeder.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/database/seeders/RestaurantSuiteDemoSeeder.php)

## Run It

Use this if you want to load the full demo dataset into the current database without dropping tables:

```bash
php artisan db:seed --class=RestaurantSuiteDemoSeeder
```

Use this if you want a completely clean demo database:

```bash
php artisan migrate:fresh --seed
```

The seeder stores a version marker in `settings` and will not duplicate the dataset when the same version has already been applied.

## What Gets Seeded

- 14 venues total
- 7 restaurants
- 7 cafes
- Venue kind persisted in the `restaurants.kind` column
- 1 to 3 branches per venue
- 2 owner-side users per venue: owner and stakeholder
- 1 platform admin account with full cross-restaurant access
- 1 supervisor per branch
- 2 waiters per branch
- 1 cashier per branch
- 2 kitchen users per branch
- Known customers with loyalty-ready history
- Tables, devices, cash registers, menus, categories, questions, choices, products, modifiers, recipes, stock, suppliers, purchase orders, payments, receipts, activity logs, alerts, and branch summaries

## Seeded Venues

- Nile Flame Grill
- Cedar Route Kitchen
- Alexandria Catch House
- Cairo Kebab District
- Olive Table Bistro
- Desert Ember Steakhouse
- Saffron Gate Restaurant
- Bean Harbor Cafe
- Lotus Brew Cafe
- Corniche Roast Lab
- Palm Lounge Cafe
- Midnight Mocha Bar
- Garden Cup Cafe
- Oat & Honey Bakery Cafe

Branch count is generated per venue as a deterministic random value from `1` to `3`, so every restaurant or cafe gets a stable branch count without hardcoding the same structure everywhere.

Possible branch areas are:

- Downtown Branch
- New Cairo Branch
- Maadi Branch
- Zayed Branch
- Heliopolis Branch
- Alexandria Branch

## User Login Pattern

All seeded users use the password:

```text
password
```

Owner-side accounts:

- `admin@restaurant-suite.com`
- `owner.<restaurant-slug>@example.com`
- `stakeholder.<restaurant-slug>@example.com`

Branch staff accounts:

- `supervisor.<restaurant-slug>.<branch-slug>@example.com`
- `waiter1.<restaurant-slug>.<branch-slug>@example.com`
- `waiter2.<restaurant-slug>.<branch-slug>@example.com`
- `cashier1.<restaurant-slug>.<branch-slug>@example.com`
- `kitchen1.<restaurant-slug>.<branch-slug>@example.com`
- `kitchen2.<restaurant-slug>.<branch-slug>@example.com`

Examples:

- `admin@restaurant-suite.com`
- `owner.nile-flame-grill@example.com`
- `stakeholder.bean-harbor-cafe@example.com`
- `waiter1.nile-flame-grill.downtown@example.com`
- `cashier1.bean-harbor-cafe.maadi@example.com`
- `kitchen1.cairo-kebab-district.zayed@example.com`

## Seeded Customers

Known customer phone numbers:

- `01010000001` Nour Adel
- `01010000002` Omar Tarek
- `01010000003` Laila Hassan
- `01010000004` Mina Samir
- `01010000005` Yara Mostafa
- `01010000006` Kareem Youssef
- `01010000007` Mariam Fathy
- `01010000008` Hussein Nabil
- `01010000009` Dina Sherif
- `01010000010` Salma Hany
- `01010000011` Mahmoud Reda
- `01010000012` Rana Khaled
- `01010000013` Ahmed Ehab
- `01010000014` Farah Ayman
- `01010000015` Karim Atef
- `01010000016` Nada Wael
- `01010000017` Amr Hossam
- `01010000018` Sara Gamal

These customers are used across seeded orders, loyalty transactions, and order history so the customer app has real restaurant, branch, and points data to display.

## Venue Type Persistence

A new migration adds a `kind` column to `restaurants`, and seeded venues are stored as either:

- `restaurant`
- `cafe`

The customer restaurant listing response now includes `kind`, so the customer app can split or filter venues cleanly without inferring from names.

## Scenario Types Seeded

Each branch is seeded with multiple operational scenarios so every app has something real to fetch:

- Open dine-in waiter order attached to a table
- Order sent to kitchen with live item states
- Ready-for-cashier order
- Fully paid cash order
- Fully paid split-tender order
- Partial refund order
- Historical paid orders for analytics and customer order history

This gives usable data for:

- Customer app: home, restaurants, order history, loyalty
- Waiter app: tables, active orders, menu, modifiers, change quantity, refund, table move
- Kitchen app: queued and in-progress tickets
- Cashier app: cashier queue and paid orders
- Owner app: summary cards, payment mix, branch ranking, loyalty members, alerts, and low stock items
- Web dashboard: platform admin restaurant management, branch control, user management, and role permission testing

## API Scope Fixes Included

The backend was updated so the multi-restaurant seeded dataset is scoped correctly:

- [MobileProductController.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/app/Http/Controllers/Api/Mobile/MobileProductController.php) now returns only branch-relevant menu data.
- [OrderMobileController.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/app/Http/Controllers/Api/Mobile/OrderMobileController.php) now scopes order lists to the authenticated branch or restaurant.
- [OwnerDashboardController.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/app/Http/Controllers/Api/OwnerDashboardController.php) now scopes employee count, loyalty members, and low-stock widgets to the owner context.

## Notes

- The Flutter app branding remains `restaurant_suite`.
- The workspace folder on disk is still `factory_app`; that is only a folder name and does not change the seeded data or app-facing product name.
