# Factory Ops Flutter App

Production-ready Flutter layout for a factory environment with RBAC, dashboards, charts, and modular feature folders. Uses Riverpod, GoRouter, Dio, and fl_chart with mock data.

## Run
```
flutter pub get
flutter run -d chrome   # or desired device
```

Use mock accounts on the login screen:
- owner@factory.com
- manager@factory.com
- employee@factory.com
All passwords: `password`.

### Mocked modules covered
- POS (orders, tables, KDS, coupons/modifiers), inventory & recipes, ERP, MES, PLM, SCM, WMS, QMS
- HR/HRMS (workers, contracts, attendance, payroll, leave, training), Employees, Attendance, Salaries
- CMMS, Finance, CRM, BI, HSE, DMS, Visitor, IoT, Procurement, Commerce, Budgeting, Projects, Communication, Platform/Tenancy
- Interactive demos include: tap any feature to open a mock action, POS quick actions, HR clock in/out, vacation requests, and module-specific quick action grids.

### API base URL
- Configure your backend URL via `--dart-define=API_BASE_URL=https://your-host/api` (defaults to `http://localhost/api`).
- Endpoints in each module call your REST routes (e.g., `mobile/orders`, `inventory-items`, `erp/items`, etc.) with auth token attached.
