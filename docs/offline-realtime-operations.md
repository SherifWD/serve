# Offline LAN And Realtime Operations

Janova can keep the waiter, kitchen, and cashier day running when the public internet is down only if the branch has a local LAN hub.

## Required Branch Hub

Run Laravel, the database, and Reverb on one machine inside the branch network. All tablets, phones, cashier devices, and kitchen screens must point to that LAN IP, not a cloud URL.

Example for a branch server at `192.168.1.10`:

```env
APP_URL=http://192.168.1.10:8000
API_BASE_URL=http://192.168.1.10:8000/api
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
REVERB_APP_ID=pos
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=192.168.1.10
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

Start the branch services:

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work
php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=192.168.1.10
```

Build or run Flutter devices with:

```bash
flutter run -t lib/main_waiter.dart --dart-define=API_BASE_URL=http://192.168.1.10:8000/api
flutter run -t lib/main_kitchen.dart --dart-define=API_BASE_URL=http://192.168.1.10:8000/api
flutter run -t lib/main_cashier.dart --dart-define=API_BASE_URL=http://192.168.1.10:8000/api
```

## What Works During Internet Outage

When the branch hub is still powered and reachable over local Wi-Fi or cable:

- Waiters can open tables, add items, send tickets to KDS, return items, and send orders to cashier.
- Kitchen can move items through queued, preparing, ready, served, and returned states.
- Cashier can settle orders, split payments, and generate receipts.
- All changes are written to the branch database immediately.
- Reverb broadcasts branch events on `private-branch.{branch_id}` so other devices can update without manual refresh.

## Realtime Events

The mobile sync endpoint returns the channel and event names at:

```http
GET /api/mobile/sync/state?surface=waiter
```

Current branch events:

- `order.created`
- `order.updated`
- `order.sent_to_kds`
- `kds.item_status_updated`
- `order.ready_for_cashier`
- `order.sent_to_cashier`
- `order.payment_updated`
- `order.paid`
- `order.item_updated`

Flutter staff screens also auto-poll as a fallback:

- Waiter floor: every 5 seconds
- Kitchen board: every 3 seconds
- Cashier queue: every 4 seconds

## Central Sync After Internet Returns

The current backend supports idempotent mobile mutations with `X-Client-Mutation-Id`, which prevents duplicate payment/order mutations during retries. For multi-branch cloud sync, add a separate upstream replication process from the branch hub to the central server. That process must use the same client mutation IDs and server timestamps to avoid duplicating payments, order items, receipts, or inventory movements.

Do not try to make multiple disconnected tablets independently write full restaurant state while the internet is off. For a live multi-user restaurant day, all branch devices need one shared LAN hub as the source of truth.
