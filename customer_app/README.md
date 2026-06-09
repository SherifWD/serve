# Restaurant Customer App

This Flutter project is the standalone customer-facing application for Janova Suite. It reuses the shared package from [factory_app](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app) but boots directly into the customer experience.

Android package ID: `com.janova.serve.guest`

Launcher label: `Janova Guest`

## Install

```bash
/Applications/Flutter/flutter/bin/flutter pub get
```

## Run

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart
```

Run on a connected Android device against production:

```bash
API_BASE_URL=https://YOUR_DOMAIN/api
/Applications/Flutter/flutter/bin/flutter devices
/Applications/Flutter/flutter/bin/flutter run --release -d <DEVICE_ID> -t lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
```

## Override API Base URL

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

## Build Production APK

```bash
API_BASE_URL=https://YOUR_DOMAIN/api
/Applications/Flutter/flutter/bin/flutter build apk --release -t lib/main.dart --dart-define=API_BASE_URL=$API_BASE_URL
```
