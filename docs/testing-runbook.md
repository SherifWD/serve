# Restaurant Suite Testing Runbook

Updated: April 1, 2026

## 1. Backend Prerequisites

- PHP 8.2+
- Composer
- MySQL or MariaDB
- Node.js 18+ for Laravel helper tooling
- Node.js 18+ for `pos-dashboard` after the Vite pin in this repo
- npm

## 2. Backend Setup

From the project root:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set these values in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-backend-domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

`QUEUE_CONNECTION=sync` is acceptable for staging if you do not want to manage a worker yet.

## 3. Run Migrations And Seed Test Data

```bash
php artisan migrate --force
php artisan db:seed --force
```

This repository now seeds the full restaurant and cafe demo estate through [RestaurantSuiteDemoSeeder.php](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/database/seeders/RestaurantSuiteDemoSeeder.php).

## 4. Default Test Accounts

All seeded staff and owner passwords are:

```text
password
```

Account patterns:

- Platform admin: `admin@restaurant-suite.com`
- Owner: `owner.<restaurant-slug>@example.com`
- Stakeholder: `stakeholder.<restaurant-slug>@example.com`
- Supervisor: `supervisor.<restaurant-slug>.<branch-slug>@example.com`
- Waiter: `waiter1.<restaurant-slug>.<branch-slug>@example.com`
- Cashier: `cashier1.<restaurant-slug>.<branch-slug>@example.com`
- Kitchen: `kitchen1.<restaurant-slug>.<branch-slug>@example.com`

Examples:

- `admin@restaurant-suite.com`
- `owner.nile-flame-grill@example.com`
- `stakeholder.bean-harbor-cafe@example.com`
- `waiter1.nile-flame-grill.downtown@example.com`
- `cashier1.bean-harbor-cafe.maadi@example.com`
- `kitchen1.cairo-kebab-district.zayed@example.com`

Known seeded customers are documented in [seed-data-reference.md](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/docs/seed-data-reference.md). Customers can still be created ad hoc by the app when logging in with a new name and phone.

## 5. Local Backend Smoke Checks

```bash
php artisan test
php artisan route:list --path=customer
php artisan route:list --path=mobile
```

## 6. Owner Dashboard

The dashboard is in `pos-dashboard` and now supports a configurable API base URL.

Local development:

```bash
cd pos-dashboard
npm install
VITE_API_BASE_URL=http://127.0.0.1:8000/api npm run dev
```

Production build:

```bash
cd pos-dashboard
npm install
VITE_API_BASE_URL=https://your-backend-domain/api npm run build
```

Upload `pos-dashboard/dist` to your frontend hosting if you want the dashboard online.

Dashboard routes now include:

- `#/dashboard`
- `#/restaurants`
- `#/branches`
- `#/users`
- `#/roles`
- `#/orders`
- `#/tables`
- `#/products`
- `#/categories`
- `#/menus`
- `#/inventory`
- `#/suppliers`
- `#/ingredients`
- `#/recipe`
- `#/settings`

Important for Hostinger shared hosting:

- Do not run `npm run dev` on the server as your deployed dashboard.
- `vite dev` is a local development server, not a production web server.
- Build the dashboard once with `npm run build`, then serve the generated static files from `pos-dashboard/dist`.
- If your Hostinger environment is still on Node 18, the dashboard now uses a Node 18 compatible Vite line.
- If your Laravel app lives in a subfolder such as `/serveu/serve/public/`, build the dashboard with a matching base and upload it under `public/dashboard/`:

```bash
cd pos-dashboard
npm install
VITE_API_BASE_URL=https://ambernoak.co.uk/serveu/serve/public/api \
VITE_APP_BASE=/serveu/serve/public/dashboard/ \
npm run build
```

- After uploading the contents of `dist/` into `public/dashboard/`, the Laravel root route will redirect `/` to `/dashboard/` automatically when that built file exists.

## 7. Flutter Setup

From `factory_app`:

```bash
/Applications/Flutter/flutter/bin/flutter pub get
/Applications/Flutter/flutter/bin/flutter analyze --no-fatal-infos
/Applications/Flutter/flutter/bin/flutter test test/goldens/workspaces_golden_test.dart
```

## 8. Run Apps During Testing

Multi-role suite:

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
```

Dedicated apps:

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_customer.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_cashier.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_kitchen.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_owner.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
```

## 9. Build APKs

Example debug APK for field testing:

```bash
/Applications/Flutter/flutter/bin/flutter build apk --debug -t lib/main_waiter.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
```

Example release APK:

```bash
/Applications/Flutter/flutter/bin/flutter build apk --release -t lib/main_waiter.dart --dart-define=API_BASE_URL=https://your-backend-domain/api
```

APK output:

- `factory_app/build/app/outputs/flutter-apk/app-debug.apk`
- `factory_app/build/app/outputs/flutter-apk/app-release.apk`

Repeat with the other entrypoints to produce separate waiter, cashier, kitchen, owner, and customer APKs.

## 10. Suggested Full Manual Test Cycle

1. Log in to the owner dashboard with a seeded owner account such as `owner.nile-flame-grill@example.com`.
2. Log in to the platform dashboard with `admin@restaurant-suite.com` if you want to test cross-restaurant management.
3. Verify restaurants, branches, users, and roles pages load on the dashboard.
4. Install waiter APK on a phone.
5. Install kitchen APK on a tablet.
6. Install cashier APK on another phone or tablet.
7. Install customer APK on a second phone.
8. Customer logs in with a new phone number.
9. Waiter opens a table and attaches the customer.
10. Waiter adds items, modifiers, and notes.
11. Waiter sends items to kitchen.
12. Kitchen moves items from queued to preparing to ready.
13. Waiter sends the order to cashier.
14. Cashier settles with one tender, then repeat another order with split tenders.
15. Owner dashboard verifies recent orders, revenue, branch performance, loyalty members, cashier queue, and KDS backlog.
16. Repeat with change quantity, refund item, reopen paid order, and move table scenarios.

## 11. Hostinger Notes

- If you deploy only the backend on Hostinger, point both Flutter and the dashboard to `https://your-backend-domain/api`.
- If you deploy the dashboard on Hostinger shared hosting, publish the contents of `pos-dashboard/dist` instead of trying to keep `npm run dev` running.
- Make sure Sanctum tokens are preserved and HTTPS is enabled.
- If you use shared hosting and cannot keep a queue worker alive, keep `QUEUE_CONNECTION=sync` for staging.
- Run `php artisan optimize:clear` after environment changes.

## 12. Useful Commands

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```
