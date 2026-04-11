# Restaurant Suite Deployment Guide

Updated: April 11, 2026

This guide covers the Laravel API, owner dashboard, staff Flutter app (`factory_app`), and customer Flutter app (`customer_app`).

## 1. Components

- Laravel API and web backend: repository root.
- Owner/admin dashboard: `pos-dashboard`.
- Staff Flutter suite and role entrypoints: `factory_app`.
- Standalone customer Flutter app: `customer_app`.
- Production API base URL expected by Flutter: `https://api.example.com/api` including `/api`.

## 2. Local Development

From the repository root:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for your local database. For quick local work you can use SQLite:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
BROADCAST_DRIVER=log
BROADCAST_CONNECTION=log
```

Create the SQLite file if needed, then migrate:

```bash
touch database/database.sqlite
php artisan migrate
```

For demo data only:

```bash
php artisan db:seed
```

Do not run the demo seeder on a real production restaurant database unless you intentionally want demo venues, users, orders, and test data.

Start the backend:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

In another terminal, start the queue worker:

```bash
php artisan queue:listen --tries=1
```

Run the root Vite assets if you are editing Laravel-served frontend assets:

```bash
npm run dev
```

Run the Vue owner/admin dashboard:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/pos-dashboard
npm install
VITE_API_BASE_URL=http://127.0.0.1:8000/api npm run dev -- --host 127.0.0.1 --port 5173
```

Run the staff Flutter app:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app
/Applications/Flutter/flutter/bin/flutter pub get
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

Role-specific staff app entrypoints:

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_cashier.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_kitchen.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
/Applications/Flutter/flutter/bin/flutter run -t lib/main_owner.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

Run the customer Flutter app:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app
/Applications/Flutter/flutter/bin/flutter pub get
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

## 3. Production Server Prerequisites

Recommended server baseline:

- Ubuntu 22.04 or 24.04 LTS.
- Nginx or Apache.
- PHP 8.2 or newer with common Laravel extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- Composer 2.
- Node.js 18 or newer and npm.
- MySQL 8 or MariaDB 10.6 or newer.
- Supervisor for queue workers.
- SSL certificate, usually from Let's Encrypt.

Do not point a public web server at the repository root. The Laravel API domain must use the `public/` directory as its document root.

Example production paths:

```text
/var/www/restaurant-suite/current
/var/www/restaurant-suite-dashboard
/var/www/restaurant-suite-flutter/staff
/var/www/restaurant-suite-flutter/customer
```

## 4. Laravel API Deployment

Clone or upload the backend:

```bash
sudo mkdir -p /var/www/restaurant-suite
sudo chown -R $USER:www-data /var/www/restaurant-suite
cd /var/www/restaurant-suite
git clone <your-repo-url> current
cd current
```

Install production dependencies:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Create the production `.env`:

```bash
cp .env.example .env
php artisan key:generate --force
```

Minimum production `.env` shape:

```dotenv
APP_NAME="Restaurant Suite"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurant_suite
DB_USERNAME=restaurant_suite
DB_PASSWORD=replace_with_strong_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

BROADCAST_DRIVER=log
BROADCAST_CONNECTION=log

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=replace_me
MAIL_PASSWORD=replace_me
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Use `BROADCAST_DRIVER=log` and `BROADCAST_CONNECTION=log` until a real broadcast connection is configured in `config/broadcasting.php`. The repo contains broadcast events, but the current broadcasting config does not define a Reverb connection yet.

Run migrations:

```bash
php artisan migrate --force
```

Create a storage symlink if you serve uploaded public assets:

```bash
php artisan storage:link
```

Set permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rw storage bootstrap/cache
```

Optimize for production:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## 5. Web Server

Nginx example:

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/restaurant-suite/current/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Apache example:

```apache
<VirtualHost *:80>
    ServerName api.example.com
    DocumentRoot /var/www/restaurant-suite/current/public

    <Directory /var/www/restaurant-suite/current/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/restaurant-suite-error.log
    CustomLog ${APACHE_LOG_DIR}/restaurant-suite-access.log combined
</VirtualHost>
```

Enable SSL after the virtual host works:

```bash
sudo certbot --nginx -d api.example.com
```

or:

```bash
sudo certbot --apache -d api.example.com
```

## 6. Queue Worker And Scheduler

Create a Supervisor program for Laravel queues:

```ini
[program:restaurant-suite-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/restaurant-suite/current/artisan queue:work database --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/restaurant-suite/current/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Load it:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start restaurant-suite-queue:*
```

Add Laravel scheduler cron:

```bash
* * * * * cd /var/www/restaurant-suite/current && php artisan schedule:run >> /dev/null 2>&1
```

Restart workers after each deployment:

```bash
php artisan queue:restart
```

## 7. Owner/Admin Dashboard Production Build

The dashboard uses `VITE_API_BASE_URL`. Always set it explicitly for production.

```bash
cd /var/www/restaurant-suite/current/pos-dashboard
npm ci
VITE_API_BASE_URL=https://api.example.com/api VITE_DEPLOY_TARGET=prod npm run build
```

Deploy the generated files from `pos-dashboard/dist/` to your dashboard static host:

```bash
sudo mkdir -p /var/www/restaurant-suite-dashboard
sudo rsync -a --delete dist/ /var/www/restaurant-suite-dashboard/
```

If the dashboard is hosted under a subpath such as `https://admin.example.com/dashboard/`, build with:

```bash
VITE_API_BASE_URL=https://api.example.com/api VITE_APP_BASE=/dashboard/ VITE_DEPLOY_TARGET=prod npm run build
```

If it is hosted at the domain root, omit `VITE_APP_BASE` or set it to `/`.

## 8. Flutter Production Builds

Set these variables first:

```bash
export FLUTTER=/Applications/Flutter/flutter/bin/flutter
export API_BASE_URL=https://api.example.com/api
```

On CI or Linux build machines, replace `FLUTTER` with the installed Flutter binary path:

```bash
export FLUTTER=flutter
```

### factory_app Web Builds

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app
$FLUTTER clean
$FLUTTER pub get

$FLUTTER build web --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_suite
$FLUTTER build web --release --target=lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_waiter
$FLUTTER build web --release --target=lib/main_cashier.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_cashier
$FLUTTER build web --release --target=lib/main_kitchen.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_kitchen
$FLUTTER build web --release --target=lib/main_owner.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_owner
```

Deploy each generated folder to the intended static path, for example:

```text
build/web_suite
build/web_waiter
build/web_cashier
build/web_kitchen
build/web_owner
```

### factory_app Android Builds

Use App Bundle for Play Store or APK for direct device installation:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app
mkdir -p ../build-artifacts/factory_app

$FLUTTER build appbundle --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/factory_app/suite-release.aab

$FLUTTER build appbundle --release --target=lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/factory_app/waiter-release.aab

$FLUTTER build appbundle --release --target=lib/main_cashier.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/factory_app/cashier-release.aab

$FLUTTER build appbundle --release --target=lib/main_kitchen.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/factory_app/kitchen-release.aab

$FLUTTER build appbundle --release --target=lib/main_owner.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/factory_app/owner-release.aab
```

APK example:

```bash
$FLUTTER build apk --release --target=lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/flutter-apk/app-release.apk ../build-artifacts/factory_app/waiter-release.apk
```

Important: these Dart entrypoints change which screen boots first. They do not create separate Android package names or iOS bundle identifiers by themselves. If you want separate app store listings for waiter, cashier, kitchen, and owner apps, add Android product flavors and iOS schemes/bundle IDs before release.

### factory_app iOS Builds

Run on macOS with Xcode and signing configured:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app
$FLUTTER pub get
$FLUTTER build ios --release --target=lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL
$FLUTTER build ipa --release --target=lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL
```

Repeat with `lib/main_cashier.dart`, `lib/main_kitchen.dart`, `lib/main_owner.dart`, or `lib/main.dart` as needed.

### customer_app Web Build

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app
$FLUTTER clean
$FLUTTER pub get
$FLUTTER build web --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL --output=build/web_customer
```

### customer_app Android Builds

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app
mkdir -p ../build-artifacts/customer_app

$FLUTTER build appbundle --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/bundle/release/app-release.aab ../build-artifacts/customer_app/customer-release.aab

$FLUTTER build apk --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
cp build/app/outputs/flutter-apk/app-release.apk ../build-artifacts/customer_app/customer-release.apk
```

### customer_app iOS Builds

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app
$FLUTTER pub get
$FLUTTER build ios --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
$FLUTTER build ipa --release --target=lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
```

## 9. Standard Update Deployment

For an existing production server:

```bash
cd /var/www/restaurant-suite/current
php artisan down
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
php artisan up
```

Rebuild and redeploy `pos-dashboard`, `factory_app`, and `customer_app` whenever API URLs, UI code, or Flutter code changes.

## 10. Production Smoke Checks

After deploy:

```bash
cd /var/www/restaurant-suite/current
php artisan about
php artisan migrate:status
php artisan queue:failed
```

Then verify:

- API domain loads over HTTPS.
- Dashboard login reaches `https://api.example.com/api`.
- Waiter can create an order and send items to KDS.
- KDS can move an item through queued, preparing, ready, served, returned.
- Cashier can take full payment and selected-item payment.
- Receipt PDF downloads.
- Customer app can log in and read paid order history.

## 11. Production Notes

- Keep `.env` out of git.
- Keep `vendor/`, `node_modules/`, Flutter `build/`, and `.dart_tool/` out of git.
- Use `php artisan migrate --force` for production migrations.
- Run queue workers under Supervisor, not an interactive terminal.
- Use HTTPS for the API URL embedded into Flutter builds.
- Use a real SMTP provider before sending production email.
- Add backups for the database and uploaded storage before onboarding restaurants.
- Add separate Android/iOS flavors if each staff role must ship as a different store app.
