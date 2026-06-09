# POS Winning Readiness Steps - 2026-06-09

This note translates the competitive gap analysis into product work. The priority is to win merchants who are frustrated by payment lock-in, fake restaurant workflows, weak support, and poor data portability.

## Implemented Now

### 1. Counter and takeaway orders no longer need fake tables

Staff mobile order creation now supports `branch_id` without `table_id` for `takeaway` and `delivery` orders.

Example request:

```json
{
  "branch_id": 1,
  "order_type": "takeaway",
  "customer_name": "Counter Guest",
  "items": [
    {
      "product_id": 10,
      "quantity": 1,
      "note": "No table counter sale"
    }
  ]
}
```

Why this matters:

- Drinks-only shops can run cashier-only sales without occupying a fake table.
- Takeaway cafes and pickup restaurants get a cleaner operational model.
- The scenario test now proves cashier takeaway, payment, receipt PDF, print queue, print claim, and printed status with no table.

### 2. No-table orders can be corrected and reopened safely

Item correction, item history, and reopen authorization now use `order.branch_id` instead of assuming every order has a table.

Why this matters:

- Counter/takeaway orders are not second-class records after creation.
- Staff can handle real service corrections without a table dependency.

### 3. Merchant data export is now available

Added a secured CSV export endpoint:

```text
GET /api/data-exports/{dataset}
```

Supported datasets:

- `products`
- `customers`
- `orders`
- `payments`
- `receipts`
- `inventory-items`

Supported filters:

- `branch_id`
- `restaurant_id`
- `from_date`
- `to_date`

Examples:

```text
GET /api/data-exports/products?branch_id=1
GET /api/data-exports/orders?from_date=2026-06-01&to_date=2026-06-09
GET /api/data-exports/receipts?restaurant_id=1
```

Why this matters:

- Directly answers the Sleft-style buyer question: "Can I export my data?"
- Gives sales a credible anti-lock-in proof point.
- Helps onboarding, migration, audits, accountant review, and cancellation trust.

## Verified

```bash
php artisan test tests/Feature/PosProductionScenarioTest.php
php artisan test tests/Feature/MobileApiRegressionTest.php --filter='cashier_can_create_no_table|owner_can_export_products'
```

Results:

- Production scenario test passed with 176 assertions.
- New cashier no-table takeaway test passed.
- New owner products CSV export test passed.

## Next Best Modifications To Win More Clients

### P0 - Do before broad competitive marketing

1. Add real payment processor adapters and terminal capture.
   - Build payment provider adapters for the target launch market.
   - Add settlement reconciliation and provider reference auditing.
   - Add effective-rate and monthly processing-cost reports.

2. Add payment-cost calculator for sales.
   - Input current processor rate, monthly volume, average ticket, POS fee, hardware lease, and add-ons.
   - Show monthly and yearly savings against Toast/Square/Clover-style packages.

3. Certify hardware.
   - Publish supported receipt printers, kitchen printers, cash drawers, tablets, and terminal devices.
   - Add a setup wizard and device health checks.
   - Run real rush-hour print tests, not only API print-job tests.

4. Add import tooling.
   - CSV import for products, categories, modifiers, customers, and inventory.
   - Import preview, validation errors, and rollback.
   - Templates for Toast, Square, Clover, Foodics, and spreadsheet migrations.

5. Tighten receipt and print UX.
   - Separate bill preview, paid receipt, fiscal receipt, kitchen ticket, and reprint actions.
   - Default cashier flow to paid receipt after payment.
   - Add print retry and failed-printer escalation.

6. Publish commercial trust pages.
   - Pricing page.
   - Hardware ownership policy.
   - Cancellation/data ownership policy.
   - Support SLA and outage process.

### P1 - Do after first pilots

1. True offline mode for mobile.
   - Local order queue.
   - Local print queue.
   - Sync replay.
   - Conflict handling.
   - Offline receipt numbering rules.

2. Restaurant cash controls.
   - Cash drawer open/close.
   - Shift close.
   - Tip/service charge/tip-out.
   - Cash variance reporting.

3. Online ordering upgrades.
   - Online payment.
   - Pickup time slots.
   - Customer notifications.
   - Delivery zones.
   - Kitchen throttling.

4. Loyalty and marketing.
   - Gift cards.
   - Campaigns.
   - Coupon targeting.
   - Customer retention reports.

5. Accounting and migration integrations.
   - Exports for accounting.
   - Webhooks.
   - Public API documentation.

## Marketing Position After These Changes

Safe claim now:

> Restaurant POS for cafes, takeaway, and dining rooms with waiter, KDS, cashier, paid receipt, print queue, owner reporting, fiscal path, and CSV data export.

Claim after P0 completion:

> Switch from locked-in POS systems with guided migration, certified hardware, supported payment processors, transparent pricing, and exportable data.
