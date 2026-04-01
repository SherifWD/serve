import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';

const requireFromCwd = createRequire(path.join(process.cwd(), 'render-operation-cards.cjs'));
const puppeteer = requireFromCwd('puppeteer-core');

const outputDir = process.argv[2] || '../docs/assets/flow-cards';

const steps = [
  {
    file: 'step-01-waiter-create-order.png',
    number: '01',
    actor: 'Waiter App',
    title: 'Seat Guests And Open A Dine-In Order',
    action: [
      'Guests sit at `T2 Window`.',
      'Waiter creates order `#4`.',
      'Items: `Flat White x2` with `Extra Shot + Oat Milk + No Sugar` and `Chicken Caesar Wrap x1` with `No Onions`.',
    ],
    state: [
      'Order status becomes `pending`.',
      'Table state becomes `occupied`.',
      'Item KDS states start as `pending`.',
    ],
    dashboard: [
      'Real dashboard shows order `#4` in Recent Orders and Orders list.',
      'KPIs do not change yet because the summary only counts orders where `orders.status = paid`.',
    ],
    note: 'Observed total after initial create: `451 EGP`.',
  },
  {
    file: 'step-02-waiter-add-dessert.png',
    number: '02',
    actor: 'Waiter App',
    title: 'Add More Items To The Same Open Table',
    action: [
      'Waiter sends a second `POST /api/mobile/orders` for the same table.',
      'The backend appends `San Sebastian Cheesecake x1` to the existing open order instead of creating a new one.',
    ],
    state: [
      'Order `#4` remains `pending`.',
      'Item `#9` is added with note `Birthday candle after mains`.',
    ],
    dashboard: [
      'Orders screen total increases for the same order.',
      'Recent Orders reflects the higher amount but KPIs remain unchanged.',
    ],
    note: 'Observed total after add-on item: `586 EGP`.',
  },
  {
    file: 'step-03-send-to-kds.png',
    number: '03',
    actor: 'Waiter App',
    title: 'Send The Order To Kitchen Display',
    action: [
      'Waiter sends `POST /api/mobile/orders/4/send-to-kds`.',
      'All unsent order items are queued for kitchen execution.',
    ],
    state: [
      'Order gets `kds_sent_at`.',
      'Items `#7`, `#8`, and `#9` move from `pending` to `queued` in `kds_status`.',
    ],
    dashboard: [
      'Owner dashboard shows no dedicated KDS status columns.',
      'Operationally, this change is visible through the KDS feed, not the current dashboard UI.',
    ],
    note: 'Observed KDS table: `T2 Window`, items queued with waiter notes preserved.',
  },
  {
    file: 'step-04-change-quantity.png',
    number: '04',
    actor: 'Waiter App',
    title: 'Change Quantity After The Order Was Sent',
    action: [
      'Guest decides to keep only one coffee.',
      'Waiter calls `PATCH /api/mobile/order-items/7/refund-change` with `action=change` and `quantity=1`.',
    ],
    state: [
      'Item `#7` quantity changes from `2` to `1`.',
      'Item status becomes `changed`.',
      'Order subtotal and total are recalculated.',
    ],
    dashboard: [
      'Orders screen total drops for order `#4`.',
      'The owner dashboard Recent Orders panel updates to the new lower amount.',
    ],
    note: 'Observed intermediate total after quantity change: `453 EGP` before dessert refund.',
  },
  {
    file: 'step-05-refund-dessert.png',
    number: '05',
    actor: 'Waiter App',
    title: 'Refund An Item That Should Not Be Served',
    action: [
      'Guests defer dessert.',
      'Waiter calls `PATCH /api/mobile/order-items/9/refund-change` with `action=refund`.',
    ],
    state: [
      'Item `#9` becomes `refunded` and its `kds_status` becomes `refunded`.',
      'Refunded amount is recorded on the item.',
      'Refunded dessert drops out of active KDS output.',
    ],
    dashboard: [
      'Orders screen total drops again.',
      'Dashboard KPIs still remain unchanged because the order is not yet `paid`.',
    ],
    note: 'Observed order total after change + refund: `318 EGP`.',
  },
  {
    file: 'step-06-kitchen-ready.png',
    number: '06',
    actor: 'Kitchen App',
    title: 'Mark The Remaining Items Ready',
    action: [
      'Kitchen marks item `#7` and item `#8` as `ready`.',
      'Refunded dessert is no longer part of the active production list.',
    ],
    state: [
      'Remaining live items are now `ready`.',
      'The order is eligible for cashier handoff.',
    ],
    dashboard: [
      'The current owner dashboard does not expose per-item KDS states.',
      'This is an operational transition with minimal dashboard visibility today.',
    ],
    note: 'Observed KDS feed for order `#4`: only Flat White and Chicken Caesar Wrap remain, both `ready`.',
  },
  {
    file: 'step-07-send-to-cashier.png',
    number: '07',
    actor: 'Waiter App',
    title: 'Move The Ready Order To Cashier',
    action: [
      'Waiter calls `PATCH /api/mobile/orders/4/send-to-cashier`.',
      'The controller validates that all non-refunded items are `ready` or `served`.',
    ],
    state: [
      'Order status changes from `pending` to `cashier`.',
      'Items keep their own item-level statuses and KDS history.',
    ],
    dashboard: [
      'Orders page starts showing `cashier` as the live status.',
      'Recent Orders panel also shows `cashier`.',
    ],
    note: 'Observed order `#4` status after cashier handoff: `cashier`.',
  },
  {
    file: 'step-08-pay-order.png',
    number: '08',
    actor: 'Cashier App',
    title: 'Collect Payment At Cashier',
    action: [
      'Cashier calls `POST /api/mobile/orders/4/pay` with `amount=318` and `payment_method=card`.',
      'Payment is recorded against the order.',
    ],
    state: [
      'Order `payment_status` becomes `paid`.',
      '`payment_method` and `paid_at` are stored.',
      'Order status remains `cashier` in the current implementation.',
    ],
    dashboard: [
      'Orders and Recent Orders still show `cashier` instead of `paid`.',
      'Revenue KPI remains unchanged because `OwnerDashboardController` counts only `orders.status = paid`.',
    ],
    note: 'Observed end state: `status=cashier`, `payment_status=paid`.',
  },
  {
    file: 'step-09-reopen-order.png',
    number: '09',
    actor: 'Cashier App',
    title: 'Reopen After Payment To Add A Missed Item',
    action: [
      'Cashier or supervisor calls `PATCH /api/mobile/orders/4/reopen`.',
      'Waiter adds `Sparkling Water x1` for the same table.',
    ],
    state: [
      'Order status returns to `pending`.',
      'New item `#10` is appended with `kds_status=pending`.',
      'Table remains `occupied`.',
    ],
    dashboard: [
      'Orders screen changes from `cashier` back to `pending`.',
      'Recent Orders amount increases again.',
    ],
    note: 'Observed reopened total: `353 EGP`.',
  },
  {
    file: 'step-10-move-table.png',
    number: '10',
    actor: 'Waiter App',
    title: 'Move An Open Table To Another Seat',
    action: [
      'A separate order is opened on `T1 Terrace`.',
      'Waiter calls `PATCH /api/mobile/tables/1/move` to move it to `T3 Family`.',
    ],
    state: [
      'Source order `#5` is closed for audit history.',
      'Target order `#6` is created on `T3 Family` and receives cloned items.',
      'Source table returns to `open`; target table becomes `occupied`.',
    ],
    dashboard: [
      'Orders list shows both records: closed source order and new pending target order.',
      'This is a clear example of operational traceability for owners.',
    ],
    note: 'Observed move result: `#5 closed`, `#6 pending`, both at `110 EGP`.',
  },
];

const escapeHtml = (value) =>
  value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');

const renderList = (items) =>
  items
    .map((item) => `<li>${escapeHtml(item)}</li>`)
    .join('');

const browser = await puppeteer.launch({
  executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  headless: 'new',
  args: ['--no-sandbox', '--disable-dev-shm-usage'],
  defaultViewport: { width: 1600, height: 900, deviceScaleFactor: 1 },
});

await fs.mkdir(outputDir, { recursive: true });

for (const step of steps) {
  const page = await browser.newPage();

  const html = `
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8" />
        <style>
          :root {
            --bg: #0f1315;
            --panel: #1e262b;
            --panel-soft: #252f35;
            --accent: #2a9d8f;
            --accent-soft: #b7dbcc;
            --ink: #eef3ee;
            --muted: #99a8a1;
            --warn: #f3c969;
          }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
              radial-gradient(circle at top right, rgba(42,157,143,0.18), transparent 32%),
              linear-gradient(180deg, #111618 0%, var(--bg) 100%);
            color: var(--ink);
          }
          .frame {
            width: 1600px;
            min-height: 900px;
            padding: 44px 48px;
          }
          .eyebrow {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
          }
          .step {
            font-size: 14px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent-soft);
          }
          .actor {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(42,157,143,0.15);
            color: var(--accent-soft);
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.04em;
          }
          h1 {
            margin: 0 0 12px;
            font-size: 44px;
            line-height: 1.08;
            max-width: 980px;
          }
          .subtitle {
            max-width: 1080px;
            font-size: 19px;
            line-height: 1.55;
            color: var(--muted);
            margin-bottom: 28px;
          }
          .grid {
            display: grid;
            grid-template-columns: 1.12fr 1fr 1fr;
            gap: 22px;
          }
          .panel {
            background: linear-gradient(180deg, rgba(30,38,43,0.96), rgba(25,33,37,0.96));
            border: 1px solid rgba(183,219,204,0.08);
            border-radius: 24px;
            padding: 24px 24px 22px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.24);
          }
          .panel h2 {
            margin: 0 0 14px;
            font-size: 15px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent);
          }
          ul {
            margin: 0;
            padding-left: 20px;
          }
          li {
            margin: 0 0 12px;
            font-size: 19px;
            line-height: 1.45;
          }
          code {
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            color: var(--ink);
            font-size: 0.9em;
          }
          .note {
            margin-top: 24px;
            padding: 18px 20px;
            border-left: 4px solid var(--warn);
            background: rgba(243,201,105,0.08);
            border-radius: 18px;
            font-size: 20px;
            line-height: 1.45;
          }
          .foot {
            margin-top: 18px;
            color: #789087;
            font-size: 14px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
          }
        </style>
      </head>
      <body>
        <div class="frame">
          <div class="eyebrow">
            <span class="step">Step ${step.number}</span>
            <span class="actor">${escapeHtml(step.actor)}</span>
          </div>
          <h1>${escapeHtml(step.title)}</h1>
          <div class="subtitle">
            Generated operations visual based on the implemented API flow in this repository.
            The waiter, kitchen, and cashier frontend code is not present here, so this image documents the observed system behavior rather than a literal UI screenshot.
          </div>

          <div class="grid">
            <section class="panel">
              <h2>Action</h2>
              <ul>${renderList(step.action)}</ul>
            </section>
            <section class="panel">
              <h2>Observed State</h2>
              <ul>${renderList(step.state)}</ul>
            </section>
            <section class="panel">
              <h2>Owner Dashboard Impact</h2>
              <ul>${renderList(step.dashboard)}</ul>
            </section>
          </div>

          <div class="note">${escapeHtml(step.note)}</div>
          <div class="foot">POS Restaurant Documentation Asset</div>
        </div>
      </body>
    </html>
  `;

  await page.setContent(html, { waitUntil: 'networkidle0' });
  await page.screenshot({
    path: path.join(outputDir, step.file),
    type: 'png',
  });
  await page.close();
}

await browser.close();
