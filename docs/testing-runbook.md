# Restaurant Suite Testing Runbook

Updated: April 1, 2026

## 1. Backend Prerequisites

- PHP 8.2+
- Composer
- MySQL or MariaDB
- Node.js 20+
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

This repository now seeds stable testing users and role types.

## 4. Default Test Accounts

- Owner: `owner@example.com` / `password`
- Downtown waiter: `waiter_downtown_branch@example.com` / `password`
- Downtown cashier: `cashier_downtown_branch@example.com` / `password`
- Downtown kitchen: `kitchen_downtown_branch@example.com` / `password`
- Mall waiter: `waiter_mall_branch@example.com` / `password`
- Mall cashier: `cashier_mall_branch@example.com` / `password`
- Mall kitchen: `kitchen_mall_branch@example.com` / `password`

Customer accounts are created by the app automatically when you log in with a name and phone.

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

1. Log in to the owner dashboard with `owner@example.com`.
2. Verify branches, tables, products, and users load.
3. Install waiter APK on a phone.
4. Install kitchen APK on a tablet.
5. Install cashier APK on another phone or tablet.
6. Install customer APK on a second phone.
7. Customer logs in with a new phone number.
8. Waiter opens a table and attaches the customer.
9. Waiter adds items, modifiers, and notes.
10. Waiter sends items to kitchen.
11. Kitchen moves items from queued to preparing to ready.
12. Waiter sends the order to cashier.
13. Cashier settles with one tender, then repeat another order with split tenders.
14. Owner dashboard verifies recent orders, revenue, branch performance, loyalty members, cashier queue, and KDS backlog.
15. Repeat with change quantity, refund item, reopen paid order, and move table scenarios.

## 11. Hostinger Notes

- If you deploy only the backend on Hostinger, point both Flutter and the dashboard to `https://your-backend-domain/api`.
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
