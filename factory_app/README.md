# Janova Suite Staff App

This Flutter project is the staff operations app. It supports a shared staff suite shell and dedicated role entrypoints for branch devices.

## Prerequisites

- Flutter SDK available at `/Applications/Flutter/flutter/bin/flutter`
- Laravel API reachable at `http://127.0.0.1:8000/api`, or override with `--dart-define=API_BASE_URL=...`

## Install

```bash
/Applications/Flutter/flutter/bin/flutter pub get
```

## Run The Staff Suite

```bash
/Applications/Flutter/flutter/bin/flutter run --flavor suite -t lib/main.dart
```

## Run Dedicated Staff Apps

```bash
/Applications/Flutter/flutter/bin/flutter run --flavor waiter -t lib/main_waiter.dart
/Applications/Flutter/flutter/bin/flutter run --flavor cashier -t lib/main_cashier.dart
/Applications/Flutter/flutter/bin/flutter run --flavor kitchen -t lib/main_kitchen.dart
/Applications/Flutter/flutter/bin/flutter run --flavor owner -t lib/main_owner.dart
```

## Customer App

The customer experience can run as this factory app's `customer` flavor, or from the separate Flutter project at [customer_app](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app).

```bash
/Applications/Flutter/flutter/bin/flutter run --flavor customer -t lib/main_customer.dart
```

## Override API Base URL

```bash
/Applications/Flutter/flutter/bin/flutter run --flavor waiter -t lib/main_waiter.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

## Android Product Flavors

The Android app uses product flavors so each role can be installed on the same device:

| Flavor | Entrypoint | Application ID | Launcher Label |
| --- | --- | --- | --- |
| `suite` | `lib/main.dart` | `com.janova.serve.suite` | Janova Serve |
| `cashier` | `lib/main_cashier.dart` | `com.janova.serve.cashier` | Janova Cashier |
| `waiter` | `lib/main_waiter.dart` | `com.janova.serve.waiter` | Janova Waiter |
| `kitchen` | `lib/main_kitchen.dart` | `com.janova.serve.kitchen` | Janova Kitchen |
| `owner` | `lib/main_owner.dart` | `com.janova.serve.owner` | Janova Owner |
| `customer` | `lib/main_customer.dart` | `com.janova.serve.customer` | Janova Customer |

Build release APKs:

```bash
API_BASE_URL=https://YOUR_DOMAIN/api

/Applications/Flutter/flutter/bin/flutter build apk --release --flavor suite -t lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
/Applications/Flutter/flutter/bin/flutter build apk --release --flavor cashier -t lib/main_cashier.dart --dart-define=API_BASE_URL=$API_BASE_URL
/Applications/Flutter/flutter/bin/flutter build apk --release --flavor waiter -t lib/main_waiter.dart --dart-define=API_BASE_URL=$API_BASE_URL
/Applications/Flutter/flutter/bin/flutter build apk --release --flavor kitchen -t lib/main_kitchen.dart --dart-define=API_BASE_URL=$API_BASE_URL
/Applications/Flutter/flutter/bin/flutter build apk --release --flavor owner -t lib/main_owner.dart --dart-define=API_BASE_URL=$API_BASE_URL
/Applications/Flutter/flutter/bin/flutter build apk --release --flavor customer -t lib/main_customer.dart --dart-define=API_BASE_URL=$API_BASE_URL
```

Run on a connected Android device against production:

```bash
API_BASE_URL=https://YOUR_DOMAIN/api

/Applications/Flutter/flutter/bin/flutter devices
/Applications/Flutter/flutter/bin/flutter run --release -d <DEVICE_ID> --flavor cashier -t lib/main_cashier.dart --dart-define=API_BASE_URL=$API_BASE_URL
```
