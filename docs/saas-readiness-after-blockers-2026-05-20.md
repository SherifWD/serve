# Janova SaaS Readiness Retest After Main Blockers

Retest date: 2026-05-20

This is a post-fix assessment of the main blockers identified before SaaS marketing. It updates the earlier Foodics benchmark without rewriting the original audit trail.

## Executive Decision

Janova is now credible for a controlled beta or custom SaaS pilot with selected restaurants and cafes.

It is still not ready to be marketed as a full Foodics replacement. The remaining gap is no longer basic workflow coverage; it is live production proof: real payment acquiring, real terminals, real printers, monitored deployment, backup restore, and support operations.

Recommended positioning today:

> Controlled beta / custom restaurant and cafe operating suite with strong dine-in, inventory, staff workflow, customer ordering, and owner dashboard foundations.

Do not position it yet as:

> Mature marketplace-scale POS, payments, loyalty, BI, and online ordering platform equivalent to Foodics.

## Validation Run

Current workspace validation:

- `php artisan test`: 58 tests passed, 384 assertions.
- `npm run build` in `pos-dashboard`: passed; Vite reported only the existing large chunk warning.
- `/Applications/Flutter/flutter/bin/flutter analyze --no-fatal-infos` in `factory_app`: passed.
- `/Applications/Flutter/flutter/bin/flutter test` in `factory_app`: passed, 17 tests.
- `/Applications/Flutter/flutter/bin/flutter analyze --no-fatal-infos` in `customer_app`: passed.
- `/Applications/Flutter/flutter/bin/flutter test` in `customer_app`: passed, 1 test.

This remains software validation in the local workspace. It is not a substitute for on-site UAT with real printers, terminals, kitchen screens, unstable Wi-Fi, rush-hour pressure, and end-of-day cash/card reconciliation.

## Blocker Closure Status

| Blocker | Current Result | Evidence |
|---|---|---|
| Fix Flutter mobile test failures | Closed | Staff and customer Flutter analyze/test now pass. |
| Complete dashboard UI for inventory operations | Closed | Dashboard now exposes purchase receipt, transfer, stock count, manual adjustment, and wastage forms. |
| Prove customer ordering/checkout if selling online ordering | Closed for pickup/takeaway proof | Authenticated customer checkout API and staff customer workspace checkout action are implemented and tested. |
| Add production payment, printer, and terminal field validation | Closed for defensive validation | Payment provider, payment attempt, print job, branch operation, mobile heartbeat, and device endpoints validate production hardware/payment fields. |
| Harden production config, monitoring, CI, backups, and support process | Documented, operational proof still needed | Deployment guide now includes production hardening and marketing release gates. |
| Position as controlled beta/custom solution | Still required | Product is improved, but not yet broad public SaaS. |

## Retested Scorecard

Scores are out of 10 for SaaS market readiness.

| Aspect | Previous | Current | Reason |
|---|---:|---:|---|
| Core restaurant workflow | 7.0 | 7.4 | Backend flow remains strong and mobile tests now pass. |
| Waiter/KDS/cashier operations | 6.5 | 7.0 | Role app test coverage is green; still needs live on-site rush-hour UAT. |
| Inventory and recipes | 7.0 | 7.8 | Dashboard now exposes the main stock operation workflows. |
| Dashboard and reporting | 6.0 | 6.4 | Inventory operations improve usability; BI depth remains below Foodics. |
| CRM, loyalty, marketing | 4.5 | 4.8 | Customer and loyalty foundations exist, but campaigns/gift cards/segmentation remain gaps. |
| Online ordering | 4.0 | 5.8 | Customer pickup/takeaway checkout is now implemented; delivery, live payment, and order tracking still need proof. |
| SaaS tenancy and billing | 6.0 | 6.2 | Tenant foundations remain good; production subscription billing lifecycle is not complete. |
| Security and access control | 6.5 | 6.8 | Hardware/payment validation and green tests improve confidence; formal security review still needed. |
| Compliance and fiscal | 5.5 | 5.6 | ETA payload foundation remains; live submission/certification is not proven. |
| Integrations and ecosystem | 4.0 | 4.3 | Payment/print abstractions are safer; marketplace/accounting/delivery ecosystem remains limited. |
| Mobile app release quality | 4.0 | 7.0 | Staff and customer tests pass after fixes. |
| Deployment and operations | 4.5 | 5.5 | Hardening checklist exists; monitoring, CI, backups, and restore need operational evidence. |
| Commercial polish | 4.5 | 5.8 | Marketing feature documentation and blocker closure help; packaging/support still immature. |

Overall readiness score: 6.3 / 10 for public SaaS, 7.2 / 10 for controlled beta/custom pilots.

## Updated 20 Real-Life Restaurant/Cafe Scenario Results

| # | Scenario | Current Result | Readiness Notes |
|---:|---|---|---|
| 1 | Platform admin onboards a restaurant, branches, owner, plans, and fiscal profile. | Pass | Backend coverage exists; production onboarding checklist still needed. |
| 2 | Owner logs in and sees only own restaurant data. | Pass | Tenant-scoped dashboard tests pass. |
| 3 | Owner filters by branch/month and downloads period receipt PDF. | Pass | Backend tests cover dashboard and PDF behavior. |
| 4 | Manager creates tables and staff roles. | Pass | Existing dashboard/API modules support the flow. |
| 5 | Waiter opens a table and adds menu items. | Pass | Mobile tests now pass; field UX still needs staff validation. |
| 6 | Waiter adds modifiers and notes. | Pass | API validation and order tests cover the core behavior. |
| 7 | Waiter tries to sell unavailable stock. | Pass | API blocks insufficient stock for products/recipes. |
| 8 | Waiter sends active items to KDS. | Pass | Mobile/API coverage is green. |
| 9 | Kitchen moves items through preparing, ready, served, and returned. | Pass with field risk | API flow exists; real kitchen screen and printer testing still required. |
| 10 | Waiter changes item quantity before kitchen lock. | Pass | Protected after KDS workflow begins. |
| 11 | Waiter refunds returned item with reason. | Pass | Regression coverage exists. |
| 12 | Waiter moves guests between tables. | Pass | Audit-style source/target order behavior should be explained in training. |
| 13 | Cashier takes full payment. | Pass | Payment endpoint works; live processor proof still required. |
| 14 | Cashier takes selected-item/partial payment. | Pass | Regression coverage exists. |
| 15 | Receipt PDF reflects paid and unpaid items correctly. | Pass | Receipt tests pass. |
| 16 | Customer logs in, views restaurants/menu/orders/loyalty. | Pass | Customer app test now passes. |
| 17 | Customer places pickup/takeaway order. | Pass for proof flow | New checkout API and mobile action are implemented and tested. |
| 18 | Branch receives purchase stock. | Pass | Dashboard stock operation UI posts to the backend endpoint. |
| 19 | Branch transfers, counts, adjusts, and records wastage. | Pass | Dashboard exposes all main inventory operation forms. |
| 20 | Unsafe payment, terminal, or printer config is entered. | Pass | API rejects unsupported methods, missing providers, unsafe endpoints, and unsafe heartbeat data. |

## Remaining Go-To-Market Risks

- Live acquiring/payment terminal certification is not proven.
- Printer compatibility is not field-tested across common restaurant devices.
- CI, monitoring, alerting, backup restore, and support process are documented but not shown running in production.
- Customer ordering supports a proof pickup/takeaway flow, but delivery dispatch, online payment settlement, branch prep screens, notifications, and cancellation/refund policies need more product work.
- Foodics still has stronger public packaging around marketplace integrations, gift cards, loyalty campaigns, advanced BI, online ordering ecosystem, and payment services.

## Marketing Recommendation

Start marketing only to controlled pilot customers where expectations can be managed and implementation/support can be hands-on.

Best early targets:

- Single-branch cafes.
- Two-to-five branch local restaurants.
- Restaurants that need custom workflows, owner control, local fiscal adaptation, or white-label deployment.
- Operators willing to run a structured pilot before full rollout.

Avoid these claims until proven:

- "Foodics replacement"
- "Production-certified payment platform"
- "Works with every printer and terminal"
- "Marketplace-integrated POS"
- "Enterprise BI platform"

## Required Proof Before Broad SaaS Launch

1. Run two real pilots: one cafe and one dine-in restaurant.
2. Complete one full operating day with waiter, kitchen, cashier, owner, customer ordering, inventory receipt, and close-of-day settlement.
3. Test at least one supported receipt printer, one kitchen printer, and one payment terminal on the customer network.
4. Run backup restore using current production data shape.
5. Put CI and monitoring in place and store the latest passing run evidence.
6. Create support scripts for onboarding, staff training, incident handling, and migration from spreadsheets/old POS.
