# Restaurant Suite Flutter Apps

This Flutter project now supports both a shared multi-role suite and dedicated role entrypoints.

## Prerequisites

- Flutter SDK available at `/Applications/Flutter/flutter/bin/flutter`
- Laravel API reachable at `http://127.0.0.1:8000/api`, or override with `--dart-define=API_BASE_URL=...`

## Install

```bash
/Applications/Flutter/flutter/bin/flutter pub get
```

## Run The Multi-Role Suite

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart
```

## Run Dedicated Apps

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_customer.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_cashier.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_kitchen.dart
/Applications/Flutter/flutter/bin/flutter run -t lib/main_owner.dart
```

## Override API Base URL

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main_waiter.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```
