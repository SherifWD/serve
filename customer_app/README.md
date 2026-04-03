# Restaurant Customer App

This Flutter project is the standalone customer-facing application for Restaurant Suite. It reuses the shared package from [factory_app](/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant/factory_app) but boots directly into the customer experience.

## Install

```bash
/Applications/Flutter/flutter/bin/flutter pub get
```

## Run

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart
```

## Override API Base URL

```bash
/Applications/Flutter/flutter/bin/flutter run -t lib/main.dart --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```
