# Janova Restaurant And Cafe Suite Feature Documentation

Updated: 2026-05-20

This document is written for marketing preparation. It describes the main restaurant/cafe features that are implemented or ready to demonstrate after the current blocker fixes. Keep claims practical until live pilot proof is collected.

## Product Positioning

Janova is a restaurant and cafe operating suite for owners who need a tailored system across branch management, dine-in service, kitchen workflow, cashier checkout, customer ordering, inventory, and owner visibility.

Recommended market positioning:

- Controlled beta or custom SaaS deployment.
- Strong fit for cafes, casual dining, and local multi-branch operators.
- Built for customization, local workflow control, and white-label ownership.

Avoid positioning as a full Foodics replacement until payments, printer/terminal certifications, marketplace integrations, advanced loyalty, and production support operations are proven.

## Main Modules

| Module | Marketing-Friendly Description |
|---|---|
| Owner/Admin Dashboard | Central workspace for managing restaurants, branches, users, roles, menus, products, orders, tables, customers, suppliers, recipes, and inventory. |
| Staff Mobile Suite | Role-based Flutter app surfaces for owner, waiter, kitchen, cashier, and customer-facing operations. |
| Table Service | Waiters can open table orders, add items, handle modifiers, send tickets to kitchen, move tables, and manage changes/refunds through controlled workflows. |
| Kitchen Display Workflow | Kitchen staff can track active order items and move them through preparation states. |
| Cashier And Receipts | Cashier flows support payment capture, partial item payments, receipt generation, refunds, and settlement-oriented operations. |
| Customer Ordering | Customers can browse restaurants and menus, authenticate, view loyalty/order history, and place pickup/takeaway orders. |
| Inventory And Recipes | Branches can receive purchases, transfer stock, count stock, adjust quantities, record wastage, and connect recipes to ingredient usage. |
| SaaS Administration | Platform-level onboarding, plans, invoices, restaurant scoping, permissions, and audit-friendly foundations are present. |
| Hardware And Payments Foundation | Payment provider, terminal, printer, device heartbeat, and print job settings include defensive validation before production use. |
| Fiscal And Compliance Foundation | Receipt and fiscal profile foundations exist, including ETA-style payload preparation. |

## Restaurant/Cafe Feature Details

### Owner And Manager Features

- Multi-restaurant and multi-branch administration.
- Branch-level dashboard visibility.
- User and role management.
- Menu, category, product, and pricing management.
- Table and floor-operation management.
- Supplier, ingredient, recipe, and inventory management.
- Customer directory and order history foundations.
- Dashboard metrics for revenue, orders, product performance, and low stock.
- Period receipt/PDF export support.

### Waiter Features

- Open dine-in table orders.
- Add items, quantities, notes, and modifiers.
- Send active items to kitchen.
- Protect kitchen-started items from unsafe quantity changes.
- Return served items to kitchen with reason.
- Refund eligible returned items.
- Move guests from one table to another.
- Send ready orders to cashier.

### Kitchen Features

- View active kitchen items.
- Track item states such as queued, preparing, ready, served, and returned.
- Keep refunded items out of active kitchen work.
- Support kitchen accountability through item-level status history.

### Cashier Features

- Receive waiter-sent orders.
- Record payments.
- Support selected-item/partial payment workflows.
- Generate receipts.
- Support refund and return workflows.
- Prepare for printer, cash drawer, and terminal integrations through validated device settings.

### Customer Features

- Customer login and guest access surfaces.
- Restaurant and menu browsing.
- Order history.
- Loyalty visibility foundations.
- Pickup/takeaway checkout proof flow.
- Branch-aware ordering when the same menu item exists in multiple branches.

### Inventory Features

- Purchase receipt creation.
- Supplier-linked receiving flow.
- Branch-to-branch stock transfer.
- Stock count entry.
- Manual stock adjustments.
- Wastage recording.
- Product/recipe stock checks during ordering.
- Low-stock visibility.

### SaaS And Administration Features

- Restaurant onboarding foundations.
- Subscription plan and invoice foundations.
- Tenant-scoped API behavior.
- Role and permission controls.
- Audit-friendly operational records.
- Deployment guide and production hardening checklist.

### Production Safety Features

- Payment methods are limited to known supported values.
- Card/wallet attempts require active provider configuration.
- Terminal payment mode requires an active registered device.
- Printer profile and endpoint fields are validated.
- Unsafe printer endpoints are rejected.
- Mobile device heartbeat rejects invalid hardware configuration.

## Best Demo Story For Sales

Use one complete restaurant day:

1. Owner creates branches, staff, tables, menu items, recipes, and inventory.
2. Waiter opens a table, adds items/modifiers, and sends them to KDS.
3. Kitchen prepares and marks items ready/served.
4. Customer requests a change or return and the system enforces the correct workflow.
5. Cashier settles the bill and prints/generates receipt.
6. Owner checks sales, recent orders, low stock, and period receipt.
7. Manager receives new stock and records wastage.
8. Customer places a pickup order from the customer app.

## Claims That Are Safe To Make Now

- Role-based restaurant operations suite.
- Dine-in POS workflow foundation.
- Waiter, kitchen, cashier, owner, and customer app surfaces.
- Inventory operations and recipe-aware stock control foundations.
- Customer pickup/takeaway ordering proof flow.
- Branch and restaurant scoping foundations.
- Payment, printer, and terminal validation foundations.
- Suitable for controlled pilot deployments.

## Claims To Avoid Until Field-Proven

- Fully certified payment processing.
- Universal printer and terminal compatibility.
- Complete delivery management platform.
- Advanced loyalty/campaign automation.
- Enterprise BI parity with mature POS platforms.
- Marketplace/app ecosystem parity.
- Foodics replacement.

## Marketing Readiness Statement

Janova is ready to be introduced as a controlled beta or custom SaaS solution for selected restaurants and cafes. It should be sold with implementation support, clear pilot scope, and a signed validation plan covering real staff workflows, printers, payment terminals, backups, and support response.

