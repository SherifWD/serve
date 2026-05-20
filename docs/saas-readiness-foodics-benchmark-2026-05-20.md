# Janova Suite vs Foodics SaaS Readiness Benchmark

Research date: 2026-05-20

## Scope And Method

This report compares the current repository at `/Applications/XAMPP/xamppfiles/htdocs/pos_restaurant` against Foodics' public product and pricing pages available on 2026-05-20.

Local validation performed:

- `php artisan test`: 55 tests passed, 356 assertions.
- `npm run build` in `pos-dashboard`: production build passed.
- `php artisan route:list --path=api`: 164 API routes detected.
- `flutter analyze --no-fatal-infos` in `factory_app`: failed due test double override errors.
- `flutter test` in `factory_app`: failed due the override errors plus one waiter UI expectation failure.
- `flutter analyze --no-fatal-infos` in `customer_app`: passed.
- `flutter test` in `customer_app`: failed due expected app title mismatch.

This is software and workflow testing, not physical in-restaurant field testing.

## Foodics Public Baseline

Foodics publicly positions itself as a cloud POS and restaurant management system with POS, Pay at Table, Customer Display, Foodics Pay, Self Ordering, Waiter App, Online, Kitchen Display Screen, Accounting, One, and Business Intelligence.

Foodics pricing page currently lists:

- QSR and Cafes Starter Bundle: 423/mo monthly, 392/mo billed annually.
- QSR and Cafes Basic Bundle: 801/mo monthly, 742/mo billed annually.
- QSR and Cafes Advanced Bundle: 1224/mo monthly, 1133/mo billed annually.
- Cloud Kitchen Basic Bundle: 612/mo monthly, 567/mo billed annually.
- Cloud Kitchen Advanced Bundle: 936/mo monthly, 867/mo billed annually.
- Dine In Basic Bundle: 1183/mo monthly, 1096/mo billed annually.

Foodics also says a specialist follows up with a customized quote based on required hardware and software, so local Egypt pricing should be confirmed with sales.

Foodics RMS public features include CRM, menu engineering, real-time reporting, inventory, supplier purchasing and transfers, role authority, promotions, loyalty, coupons, gift cards, table management, coursing, multiple payment methods, order splitting, and internal approvals.

Foodics Online public features include online web store, mobile application, order status, easy login, smart ordering, branch management, order management, delivery management, menu management, employee management, payment management, marketing tools, customer management, reporting, and product support.

Foodics marketplace publicly advertises dozens of apps across accounting, analytics, delivery management, digital menu, ERP, food aggregators, inventory, loyalty, online ordering, table payments, and reservation categories.

Sources:

- https://www.foodics.com/
- https://www.foodics.com/pricing/
- https://www.foodics.com/rms-features/
- https://www.foodics.com/online-features/
- https://www.foodics.com/marketplace/
- https://www.foodics.com/business-intelligence/
- https://help.foodics.com/hc/en-us/articles/17190003861276-What-is-Foodics-AI
- https://help.foodics.com/hc/en-us/articles/7494382541084-Foodics-API-Adapter

## Current Janova Capability Snapshot

Verified capabilities in this repository:

- Laravel 12 API backend with 164 API routes.
- Sanctum authentication for dashboard, mobile staff, and customer portal.
- Permission middleware and role-scoped dashboard routing.
- Restaurant, branch, user, role, table, category, menu, product, supplier, ingredient, recipe, inventory, customer, order, payment, receipt, billing, fiscal profile, device, print job, and payment provider modules.
- Vue owner/admin dashboard with pages for dashboard, restaurants, branches, users, roles, orders, tables, products, categories, menus, inventory, suppliers, ingredients, recipes, customers, employees, and settings.
- Flutter multi-role apps for owner, waiter, kitchen, cashier, and customer surfaces.
- Mobile API support for table listing, order creation, modifiers, coupons, send to KDS, KDS status changes, send to cashier, payments, partial item payments, receipts, returns, refunds, table move, reopen order, sync state, device heartbeat, payment attempts, and print jobs.
- SaaS onboarding, subscription plan selection, invoice creation, support context, tenant scoping, and audit logging.
- ETA receipt payload export and queued submission model.
- Backup command snapshot manifest.

## Side By Side Assessment

| Area | Janova Current State | Foodics Public State | Winner |
|---|---|---|---|
| Core POS | Strong backend flow for table orders, items, modifiers, KDS handoff, cashier handoff, payment, receipt. Flutter tests currently failing. | Mature cloud POS product. | Foodics |
| Waiter App | Exists in Flutter and APIs, but mobile tests fail. | Public Waiter App product. | Foodics |
| KDS | Backend APIs and regression tests exist. | Public KDS product. | Foodics, due maturity |
| Cashier And Payments | Multiple tender arrays, partial item payments, payment attempts, device configs. No certified live processor evidence. | Foodics Pay, customer display, pay at table, multiple payment methods. | Foodics |
| Table Management | Table status, move table, reopen order, cashier state. | Public table management, pay at table, order splitting, coursing. | Foodics |
| Inventory | Backend supports purchase receipts, transfer, stock counts, adjustment, wastage. Dashboard stock operations tab still says coming soon. | Inventory, supplier management, purchasing, transfers, warehouse. | Foodics |
| CRM And Loyalty | Customer directory, OTP, loyalty earning, customer portal. No full campaign engine. | CRM, loyalty, coupons, gift cards, push, segmentation, feedback. | Foodics |
| Online Ordering | Customer portal lists restaurants/menu/orders/loyalty. No complete customer checkout flow verified. | Online web/app creation, order tracking, delivery, payment, marketing. | Foodics |
| Reporting | Owner dashboard, branch drilldown, payment mix, low stock, PDF period receipt. | Real-time reporting, BI dashboards, AI analytics. | Foodics |
| SaaS Layer | Onboarding, billing plans, invoices, tenant tests. Still not production billing/subscription automation. | Established subscription product and support organization. | Foodics |
| Integrations | ETA payload, payment provider configs, device/print jobs. No public marketplace/API program. | Marketplace, API adapter, delivery/accounting/BI ecosystem. | Foodics |
| Local Compliance | VAT recalculation and ETA receipt mapping tested. Not proven live-certified. | Foodics Pay is publicly positioned under SAMA supervision and fintech license. | Foodics |
| Customization | Source-owned, faster to tailor. | Vendor roadmap and marketplace model. | Janova |
| Ownership And White Label | Full control possible. | Foodics brand/platform. | Janova |

## Ratings

Scores are out of 10 for SaaS market readiness, not raw code volume.

| Aspect | Score | Reason |
|---|---:|---|
| Core restaurant workflow | 7.0 | Backend flow is real and tested; mobile polish blocks launch confidence. |
| Waiter/KDS/cashier operations | 6.5 | APIs are solid, but role apps need green tests and field UX validation. |
| Inventory and recipes | 7.0 | Backend operations are strong; dashboard UI does not expose the full workflow yet. |
| Dashboard and reporting | 6.0 | Useful owner KPIs and branch detail; not BI-grade. |
| CRM, loyalty, marketing | 4.5 | Customer/OTP/loyalty foundations exist; campaigns, segmentation, feedback, gift cards are missing. |
| Online ordering | 4.0 | Customer portal exists; complete order/payment/delivery journey is not proven. |
| SaaS tenancy and billing | 6.0 | Onboarding, plans, invoices, scoping tests pass; payment collection and lifecycle automation are immature. |
| Security and access control | 6.5 | Tenant scoping and permissions are tested; needs production hardening and security review. |
| Compliance and fiscal | 5.5 | ETA payload mapping exists; live submission/certification readiness is not proven. |
| Integrations and ecosystem | 4.0 | Internal device/payment/print abstractions exist; no marketplace, aggregator, accounting ecosystem. |
| Mobile app release quality | 4.0 | Customer analyze passes, but factory analyze/tests and customer test fail. |
| Deployment and operations | 4.5 | Build/test scripts exist; production debug/config/monitoring/CI evidence is incomplete. |
| Commercial polish | 4.5 | Strong technical foundation, but not yet packaged like a buyable SaaS. |

Overall readiness score: 5.6 / 10.

Marketing readiness:

- Controlled pilot with 1 to 3 known restaurants: yes, after fixing mobile tests and doing on-site UAT.
- Public SaaS launch against Foodics: not yet.
- Best current positioning: custom, white-label, locally adaptable restaurant operations suite, not a Foodics replacement.

## 20 Real Life Restaurant/Cafe Scenarios

| # | Scenario | Result | Evidence / Risk |
|---:|---|---|---|
| 1 | Platform admin onboards a new restaurant, branch, owner, and fiscal settings. | Pass | Backend test passes. |
| 2 | Owner logs in and sees scoped dashboard data for own restaurant. | Pass | Dashboard summary branch scoping tests pass. |
| 3 | Owner filters dashboard by branch/month and downloads period PDF. | Pass | Backend tests pass. |
| 4 | Manager creates tables with optional seat counts. | Pass | Backend table test passes. |
| 5 | Waiter opens a table and adds items. | Pass API, UI risk | API tests pass; waiter Flutter tests have a failure. |
| 6 | Waiter adds modifiers/category choices to an item. | Pass | Backend validation test passes. |
| 7 | Waiter tries unavailable finished-product stock. | Pass | API correctly blocks order. |
| 8 | Waiter tries unavailable recipe ingredient stock. | Pass | API correctly blocks order. |
| 9 | Waiter sends only refunded items to kitchen. | Pass | API correctly rejects. |
| 10 | Kitchen sees active orders and item states. | Partial | API exists; needs stronger end-to-end UI field test. |
| 11 | Kitchen marks item preparing/ready/served. | Partial | Controller supports it; full positive scenario not independently proven in current test output. |
| 12 | Waiter returns served item to kitchen with reason. | Pass | Backend test passes. |
| 13 | Waiter attempts return without reason. | Pass | API correctly rejects. |
| 14 | Waiter attempts quantity change after kitchen workflow starts. | Pass | API correctly rejects. |
| 15 | Waiter refunds item before kitchen return. | Pass | API correctly rejects. |
| 16 | Waiter refunds item after kitchen return. | Pass | Backend test passes. |
| 17 | Cashier takes partial payment for selected items without closing table. | Pass | Backend test passes. |
| 18 | Cashier generates selected-item receipt and full receipt shows paid items as zero due. | Pass | Backend PDF/receipt tests pass. |
| 19 | Customer requests OTP, verifies login, sees restaurants/menu/orders/loyalty. | Pass API, UI risk | API tests pass; customer Flutter wrapper test fails. |
| 20 | Branch receives stock, transfers to another branch, counts stock, records wastage. | Pass API, UI gap | Backend tests pass; dashboard adjustment UI says coming soon. |

## Launch Blockers

1. Fix Flutter test double method signatures for `fetchCustomerRestaurantDetail(branchId: ...)`.
2. Fix waiter UI action test at `factory_app/test/waiter_order_sheet_test.dart:100`.
3. Fix customer wrapper title expectation or actual title in `customer_app/test/widget_test.dart`.
4. Expose inventory receiving, transfer, count, adjustment, and wastage in the dashboard UI.
5. Complete customer checkout/online ordering and delivery/pickup flow if selling to cafes beyond dine-in POS.
6. Complete payment processor integrations, terminal certification path, settlement reporting, and failed payment recovery.
7. Add split bill, pay at table, customer display, gift cards, advanced loyalty, promotions, and campaign segmentation if competing directly with Foodics.
8. Harden production environment: disable debug, verify `.env`, HTTPS, backups, queue strategy, observability, error reporting, CI, and rollback plan.
9. Run on-site UAT with real waiter, cashier, kitchen, owner, printer, network outage, and rush-hour workflows.
10. Create commercial packaging: pricing page, demo tenant, onboarding checklist, SLA, support process, help docs, and data migration plan.

## Recommendation

Do not market Janova today as a Foodics-equivalent SaaS. Market it as a controlled beta or custom pilot solution for restaurants that need ownership, localization, custom workflows, and direct feature tailoring.

The fastest credible path to market is:

1. Fix mobile test failures.
2. Run a 2-week pilot in one restaurant and one cafe.
3. Use Janova for dine-in POS, KDS, cashier, receipts, inventory operations, and owner reporting.
4. Keep online ordering, delivery, loyalty campaigns, gift cards, and advanced BI as roadmap or paid custom modules until fully proven.

Target readiness after the fixes and pilot: 7.0 to 7.5 / 10 for controlled SaaS sales to small restaurants and cafes.
