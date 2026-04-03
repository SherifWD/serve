# Restaurant Suite Staff App

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
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart
```

## Run Dedicated Staff Apps

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_cashier.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_kitchen.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_owner.dart
```

## Customer App

The customer experience now runs from the separate Flutter project at [customer_app](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/customer_app).

## Override API Base URL

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```
