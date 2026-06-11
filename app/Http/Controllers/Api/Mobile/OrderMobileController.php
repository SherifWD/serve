<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Events\BranchOrderUpdated;
use App\Events\OrderReadyForCashier;
use App\Events\OrderSentToKDS;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CategoryAnswer;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\LoyaltyTransaction;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Table;
use App\Services\Inventory\ProductStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderMobileController extends Controller
{
    private const ACTIVE_ORDER_STATUSES = ['pending', 'open', 'running', 'cashier'];

    private const NON_ACTIVE_ITEM_STATUSES = ['canceled', 'cancelled', 'refunded'];

    private const CASHIER_READY_ITEM_STATUSES = ['ready', 'served'];

    private function orderQueryForUser(Request $request)
    {
        $query = Order::query();
        $user = $request->user();

        if ($user?->branch_id) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($user?->restaurant_id) {
            $query->whereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
        }

        return $query;
    }

    private function ensureBranchAccessible(Request $request, int $branchId): ?JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->branch_id) {
            return (int) $user->branch_id === $branchId
                ? null
                : response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($user->restaurant_id) {
            $allowed = Branch::query()
                ->whereKey($branchId)
                ->where('restaurant_id', $user->restaurant_id)
                ->exists();

            return $allowed
                ? null
                : response()->json(['error' => 'Unauthorized'], 403);
        }

        return null;
    }

    private function ensureOrderAccessible(Request $request, Order $order): ?JsonResponse
    {
        return $this->ensureBranchAccessible($request, (int) $order->branch_id);
    }

    private function activeOrderConstraint($query)
    {
        return $query->whereIn('status', self::ACTIVE_ORDER_STATUSES)
            ->where(function ($paymentQuery) {
                $paymentQuery->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            });
    }

    private function resolveOrderLocation(Request $request, array $data): array
    {
        $orderType = $data['order_type'] ?? (! empty($data['table_id']) ? 'dine-in' : 'takeaway');

        if ($orderType === 'dine-in' && empty($data['table_id'])) {
            throw ValidationException::withMessages([
                'table_id' => 'A table is required for dine-in orders.',
            ]);
        }

        if (! empty($data['table_id'])) {
            $table = Table::with('branch')->findOrFail($data['table_id']);

            if (! empty($data['branch_id']) && (int) $data['branch_id'] !== (int) $table->branch_id) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Branch must match the selected table.',
                ]);
            }

            return [$table->branch, $table, $orderType];
        }

        $branchId = $data['branch_id'] ?? $request->user()?->branch_id;

        if (! $branchId && $request->user()?->restaurant_id) {
            $branchId = Branch::query()
                ->where('restaurant_id', $request->user()->restaurant_id)
                ->orderBy('id')
                ->value('id');
        }

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch is required for takeaway or delivery orders without a table.',
            ]);
        }

        return [Branch::query()->findOrFail($branchId), null, $orderType];
    }

    private function appendItemRuntimeState(Order $order): void
    {
        $order->loadMissing('payments');

        $order->items->each(function ($item) use ($order) {
            $item->setRelation('order', $order);
            $item->append(['item_note', 'change_note', 'paid_amount', 'payment_status']);
        });
    }

    private function employeeIdForUser(Request $request, int $branchId): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($branchId) {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->orderByRaw('branch_id = ? desc', [$branchId])
            ->value('id');
    }

    private function broadcastOrderChange(Order $order, string $event = 'order.updated'): void
    {
        event(new BranchOrderUpdated($order->fresh([
            'table',
            'customer',
            'items.product',
            'items.modifiers.modifier',
            'payments',
        ]) ?? $order, $event));
    }

    private function orderItemsReadyForCashier(Order $order): bool
    {
        $activeItems = $order->items->filter(function ($item) {
            $status = $item->kds_status ?? $item->status;

            return ! in_array($status, self::NON_ACTIVE_ITEM_STATUSES, true);
        });

        return $activeItems->isNotEmpty() && $activeItems->every(function ($item) {
            $status = $item->kds_status ?? $item->status;

            return in_array($status, self::CASHIER_READY_ITEM_STATUSES, true);
        });
    }

    private function parseItemIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function index(Request $request)
    {
        $query = $this->orderQueryForUser($request);

        if ($request->has('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        if ($request->filled('status')) {
            $statuses = collect(explode(',', (string) $request->input('status')))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values();

            if ($statuses->isNotEmpty()) {
                $query->whereIn('status', $statuses);
            }
        }

        $orders = $query->with([
            'branch.restaurant',
            'table',
            'customer',
            'employee.user',
            'items.product',
            'items.modifiers.modifier',
            'payments',
        ])
            ->latest('id')
            ->get();

        $orders->each(fn ($order) => $this->appendItemRuntimeState($order));

        return response()->json(['data' => $orders]);
    }

    public function sendToCashier(Request $request, Order $order)
    {
        if ($authResponse = $this->ensureOrderAccessible($request, $order)) {
            return $authResponse;
        }

        $order->load('items');
        $allDone = $this->orderItemsReadyForCashier($order);

        if (! $allDone) {
            return response()->json(['error' => 'Items are not finished in KDS'], 422);
        }

        $order->status = 'cashier'; // <— important
        $order->save();

        if ($order->table && $order->order_type === 'dine-in') {
            $order->table->update(['status' => 'cashier']);
        }

        event(new OrderReadyForCashier($order->fresh(['table', 'customer', 'employee.user', 'items.product', 'payments'])));
        $this->broadcastOrderChange($order, 'order.sent_to_cashier');

        return response()->json(['ok' => true]);
    }

    public function batchSendToCashier(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = $this->orderQueryForUser($request)
            ->with('items')
            ->whereIn('id', $data['order_ids'])
            ->get()
            ->keyBy('id');

        if ($orders->count() !== count(array_unique($data['order_ids']))) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $ok = [];
        $failed = [];
        foreach ($data['order_ids'] as $id) {
            $order = $orders->get($id);
            $allDone = $this->orderItemsReadyForCashier($order);

            if ($allDone) {
                $order->status = 'cashier'; // <— important
                $order->save();
                if ($order->table && $order->order_type === 'dine-in') {
                    $order->table->update(['status' => 'cashier']);
                }
                $ok[] = $id;
                event(new OrderReadyForCashier($order->fresh(['table', 'customer', 'employee.user', 'items.product', 'payments'])));
                $this->broadcastOrderChange($order, 'order.sent_to_cashier');
            } else {
                $failed[] = $id;
            }
        }

        return response()->json(['ok' => $ok, 'failed' => $failed]);
    }

    public function show(Request $request, $id)
    {
        $order = $this->orderQueryForUser($request)
            ->with([
                'branch.restaurant',
                'table',
                'customer',
                'employee.user',
                'items.product',
                'items.answers.choice.question',
                'items.modifiers.modifier',
                'payments',
            ])
            ->findOrFail($id);

        $this->appendItemRuntimeState($order);

        return response()->json(['data' => $order]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'table_id' => 'nullable|integer|exists:tables,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'order_type' => 'nullable|in:dine-in,takeaway,delivery',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.answers' => 'nullable|array',
            'items.*.answers.*.choice_id' => 'required|integer|exists:category_choices,id',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'items.*.modifiers' => 'nullable|array',
            'items.*.modifiers.*.modifier_id' => 'nullable|exists:modifiers,id',
            'items.*.modifiers.*.raw_modifier' => 'nullable|string|max:255',
            'items.*.note' => 'nullable|string|max:255', // NEW
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent',
            'coupon_code' => 'nullable|string|max:50',
            'send_to_cashier' => 'nullable|boolean',
        ]);

        [$branch, $table, $orderType] = $this->resolveOrderLocation($request, $data);
        if ($authResponse = $this->ensureBranchAccessible($request, (int) $branch->id)) {
            return $authResponse;
        }
        $restaurantId = $branch->restaurant_id;

        DB::beginTransaction();
        try {
            $stockService = app(ProductStockService::class);
            $customer = $this->resolveCustomer($data);

            $openOrder = $table
                ? Order::where('table_id', $table->id)
                    ->where(fn ($query) => $this->activeOrderConstraint($query))
                    ->lockForUpdate()
                    ->first()
                : null;

            $order = $openOrder ?: Order::create([
                'branch_id' => $branch->id,
                'table_id' => $table?->id,
                'customer_id' => $customer?->id,
                'employee_id' => $this->employeeIdForUser($request, (int) $branch->id),
                'order_type' => $orderType,
                'status' => 'pending',
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'order_date' => now(),
            ]);

            if ($openOrder && ! $order->customer_id && $customer) {
                $order->customer_id = $customer->id;
                $order->save();
            }

            $orderTotal = $order->total;
            $branchId = $branch->id;

            // Helper: sum modifier price for modifier_ids
            $sumModifierPrice = function (array $mods, Product $product) use ($restaurantId): float {
                if (empty($mods)) {
                    return 0.0;
                }
                $ids = array_values(array_filter(array_map(fn ($m) => $m['modifier_id'] ?? null, $mods)));
                if (empty($ids)) {
                    return 0.0;
                }
                $rows = Modifier::query()
                    ->whereIn('id', $ids)
                    ->where('is_active', true)
                    ->where(function ($query) use ($restaurantId) {
                        $query->whereNull('restaurant_id');

                        if ($restaurantId) {
                            $query->orWhere('restaurant_id', $restaurantId);
                        }
                    })
                    ->where(function ($query) use ($product) {
                        $query->whereNull('category_id')
                            ->orWhere('category_id', $product->category_id);
                    })
                    ->get(['id', 'price']);
                if ($rows->count() !== count(array_unique($ids))) {
                    throw ValidationException::withMessages([
                        'items' => ['Selected modifier is not available for this product category.'],
                    ]);
                }
                $byId = $rows->keyBy('id');
                $sum = 0.0;
                foreach ($ids as $id) {
                    $p = $byId[$id]->price ?? 0;
                    $sum += (float) $p;
                }

                return $sum;
            };

            // (Optional) Validate required questions if your data has 'required'
            $questionsByCategory = []; // if you can provide, otherwise skip checking

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->whereKey($item['product_id'])
                    ->where('branch_id', $branchId)
                    ->where('is_available', true)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ['Selected product is not available for this branch.'],
                    ]);
                }

                // --- compute base + modifiers ---
                $baseUnit = (float) $product->price;
                $mods = $item['modifiers'] ?? [];
                $modsUnit = $sumModifierPrice($mods, $product); // price per 1 unit of product
                $unitPrice = $baseUnit + $modsUnit;

                $qty = (int) $item['quantity'];
                $itemTotal = $unitPrice * $qty;

                // item-level discount
                $itemDiscount = (float) ($item['discount'] ?? 0);
                $itemDiscountType = $item['discount_type'] ?? 'fixed';
                if ($itemDiscount > 0) {
                    $itemTotal -= ($itemDiscountType === 'percent')
                        ? ($itemTotal * $itemDiscount / 100.0)
                        : $itemDiscount;
                }
                $itemTotal = max(0, round($itemTotal, 2));

                $stockService->consume($product, (int) $branchId, $qty);

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $unitPrice, // store unit price INCLUDING modifier price
                    'total' => $itemTotal,
                    'discount' => $itemDiscount,
                    'discount_type' => $itemDiscountType,
                    'change_note' => $item['note'] ?? null, // use as item note on create
                    'item_note' => $item['note'] ?? null, // use as item note on create
                ]);
                $orderTotal += $itemTotal;

                // answers
                if (! empty($item['answers'])) {
                    foreach ($item['answers'] as $answer) {
                        CategoryAnswer::create([
                            'order_item_id' => $orderItem->id,
                            'choice_id' => $answer['choice_id'],
                            'image' => $answer['image'] ?? null,
                        ]);
                    }
                }

                // modifiers (link rows)
                if (! empty($mods) && is_array($mods)) {
                    foreach ($mods as $mod) {
                        if (! empty($mod['modifier_id']) || ! empty($mod['raw_modifier'])) {
                            \App\Models\OrderItemModifier::create([
                                'order_item_id' => $orderItem->id,
                                'modifier_id' => $mod['modifier_id'] ?? null,
                                'raw_modifier' => $mod['raw_modifier'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // order-level discount
            $orderDiscount = (float) ($data['discount'] ?? 0);
            $orderDiscountType = $data['discount_type'] ?? 'fixed';
            if ($orderDiscount > 0) {
                $orderTotal -= ($orderDiscountType === 'percent')
                    ? ($orderTotal * $orderDiscount / 100.0)
                    : $orderDiscount;
            }

            $order->discount = $orderDiscount;
            $order->discount_type = $orderDiscountType;
            $order->coupon_code = $data['coupon_code'] ?? null;
            $order->total = max($orderTotal, 0);
            if ($openOrder && $order->status === 'cashier') {
                $order->status = 'pending';
            }
            $order->save();

            if ($table) {
                $table->status = \App\Enums\TableStatus::OCCUPIED;
                $table->save();
            }

            // recompute unified subtotal/tax/total (keeps fields consistent)
            $order = \App\Services\Orders\RecalculateOrder::run($order);

            $paidAmount = (float) $order->payments()->sum('amount');
            if ($paidAmount > 0) {
                $order->payment_status = $paidAmount >= (float) $order->total ? 'paid' : 'partial';
                if ($order->payment_status !== 'paid' && $order->status === 'paid') {
                    $order->status = 'pending';
                }
                $order->save();
            }

            if ($request->boolean('send_to_cashier') && $order->payment_status !== 'paid') {
                $order->status = 'cashier';
                $order->save();
            }

            DB::commit();

            $order->load(['branch.restaurant', 'table', 'customer', 'employee.user', 'items.product', 'items.answers.choice.question', 'items.modifiers.modifier', 'payments']); // eager
            $this->appendItemRuntimeState($order);
            $this->broadcastOrderChange($order, $openOrder ? 'order.updated' : 'order.created');

            return response()->json(['order' => $order], $openOrder ? 200 : 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Order creation failed.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendToKDS(Request $request, Order $order)
    {
        if ($authResponse = $this->ensureOrderAccessible($request, $order)) {
            return $authResponse;
        }

        $pendingActiveItemIds = $order->items()
            ->whereNotIn('status', self::NON_ACTIVE_ITEM_STATUSES)
            ->where(function ($query) {
                $query->whereNull('kds_status')
                    ->orWhere('kds_status', 'pending');
            })
            ->pluck('id');

        if ($pendingActiveItemIds->isEmpty()) {
            return response()->json([
                'error' => 'No active items are waiting to be sent to kitchen.',
            ], 422);
        }

        // Mark only active items that have not been sent before.
        $updated = $order->items()
            ->whereIn('id', $pendingActiveItemIds)
            ->update([
                'kds_status' => 'queued',
                'kds_sent_at' => now(),
            ]);

        if ($updated > 0 && ! $order->kds_sent_at) {
            $order->kds_sent_at = now();
            $order->save();
        }

        if ($updated > 0 && $order->table && $order->order_type === 'dine-in') {
            $order->table->update(['status' => 'occupied']);
        }

        if ($updated > 0) {
            event(new OrderSentToKDS($order->fresh(['table', 'customer', 'employee.user', 'items.product', 'items.modifiers.modifier'])));
            $this->broadcastOrderChange($order, 'order.sent_to_kds');
        }

        return response()->json(['ok' => true, 'order_id' => $order->id]);
    }

    public function pay(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:30',
            'email_receipt' => 'nullable|email',
            'client_mutation_id' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:100',
            'provider_reference' => 'nullable|string|max:255',
            'payment_attempt_id' => 'nullable|integer|exists:payment_attempts,id',
            'payments' => 'nullable|array|min:1',
            'payments.*.method' => 'required_with:payments|in:cash,card,wallet',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.provider' => 'nullable|string|max:100',
            'payments.*.provider_reference' => 'nullable|string|max:255',
            'payments.*.payment_attempt_id' => 'nullable|integer|exists:payment_attempts,id',
            'item_ids' => 'nullable|array|min:1',
            'item_ids.*' => 'integer|exists:order_items,id',
        ]);

        if (empty($data['payments']) && (! isset($data['amount']) || empty($data['payment_method']))) {
            return response()->json(['error' => 'Provide either a single payment or a payments split array.'], 422);
        }

        $order = Order::with(['table', 'customer', 'payments', 'items'])->findOrFail($id);

        if ($authResponse = $this->ensureOrderAccessible($request, $order)) {
            return $authResponse;
        }

        $paymentItemIds = collect($data['item_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($paymentItemIds->isNotEmpty()) {
            $selectedItems = $order->items
                ->whereIn('id', $paymentItemIds->all())
                ->reject(fn ($item) => in_array($item->status, self::NON_ACTIVE_ITEM_STATUSES, true));

            if ($selectedItems->count() !== $paymentItemIds->count()) {
                return response()->json(['error' => 'Selected payment items must belong to this active order.'], 422);
            }
        }

        $clientMutationId = $request->header('X-Client-Mutation-Id') ?: ($data['client_mutation_id'] ?? null);

        DB::transaction(function () use ($data, $order, $paymentItemIds, $clientMutationId) {
            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $record = Payment::create([
                        'order_id' => $order->id,
                        'payment_attempt_id' => $payment['payment_attempt_id'] ?? null,
                        'method' => $payment['method'],
                        'provider' => $payment['provider'] ?? null,
                        'provider_reference' => $payment['provider_reference'] ?? null,
                        'client_mutation_id' => $clientMutationId,
                        'amount' => $payment['amount'],
                        'item_ids' => $paymentItemIds->isEmpty() ? null : $paymentItemIds->all(),
                        'scope' => $paymentItemIds->isEmpty() ? 'order' : 'items',
                    ]);
                    $this->markPaymentAttemptCaptured($payment['payment_attempt_id'] ?? null, $record);
                }
                $order->payment_method = count($data['payments']) > 1 ? 'mixed' : $data['payments'][0]['method'];
            } else {
                $record = Payment::create([
                    'order_id' => $order->id,
                    'payment_attempt_id' => $data['payment_attempt_id'] ?? null,
                    'method' => $data['payment_method'],
                    'provider' => $data['provider'] ?? null,
                    'provider_reference' => $data['provider_reference'] ?? null,
                    'client_mutation_id' => $clientMutationId,
                    'amount' => $data['amount'],
                    'item_ids' => $paymentItemIds->isEmpty() ? null : $paymentItemIds->all(),
                    'scope' => $paymentItemIds->isEmpty() ? 'order' : 'items',
                ]);
                $this->markPaymentAttemptCaptured($data['payment_attempt_id'] ?? null, $record);
                $order->payment_method = $data['payment_method'];
            }

            $paidAmount = (float) $order->payments()->sum('amount');
            if ($paidAmount >= (float) $order->total) {
                $order->payment_status = 'paid';
                $order->status = 'paid';
                $order->paid_at = now();

                if ($order->table && $order->order_type === 'dine-in') {
                    $order->table->update(['status' => \App\Enums\TableStatus::OPEN]);
                }

                if ($order->customer && ! $order->customer->loyaltyTransactions()->where('order_id', $order->id)->where('type', 'earn')->exists()) {
                    $earnedPoints = max(1, (int) floor(((float) $order->total) / 10));

                    LoyaltyTransaction::create([
                        'customer_id' => $order->customer->id,
                        'order_id' => $order->id,
                        'points' => $earnedPoints,
                        'type' => 'earn',
                    ]);

                    $order->customer->increment('loyalty_points', $earnedPoints);
                }
            } else {
                $order->payment_status = $paidAmount > 0 ? 'partial' : 'unpaid';
                $order->status = 'cashier';

                if ($order->table && $order->order_type === 'dine-in') {
                    $order->table->update(['status' => 'cashier']);
                }
            }

            $order->save();
        });

        $order->refresh()->load(['branch.restaurant', 'customer', 'items.product', 'items.modifiers.modifier', 'payments']);
        $this->appendItemRuntimeState($order);
        $this->broadcastOrderChange(
            $order,
            $order->status === 'paid' ? 'order.paid' : 'order.payment_updated',
        );

        // Optionally send email receipt
        if (! empty($data['email_receipt'])) {
            // see below for mailing
        }

        return response()->json(['order' => $order]);
    }

    private function markPaymentAttemptCaptured(?int $attemptId, Payment $payment): void
    {
        if (! $attemptId) {
            return;
        }

        PaymentAttempt::query()
            ->whereKey($attemptId)
            ->where('order_id', $payment->order_id)
            ->update([
                'payment_id' => $payment->id,
                'status' => 'captured',
                'captured_at' => now(),
            ]);
    }

    public function receipt(Request $request, $id)
    {
        $order = Order::with(['branch.restaurant', 'customer', 'employee', 'items.product', 'table', 'payments'])->findOrFail($id);

        if ($authResponse = $this->ensureOrderAccessible($request, $order)) {
            return $authResponse;
        }

        $this->appendItemRuntimeState($order);

        $scope = $request->input('scope', 'full');
        if (! in_array($scope, ['full', 'paid', 'unprinted', 'last'], true)) {
            return response()->json(['error' => 'Invalid receipt scope.'], 422);
        }

        $itemIds = $this->parseItemIds($request->input('item_ids'));
        $payment = null;
        $existingReceipt = null;

        if ($scope === 'last' || $request->boolean('reprint')) {
            $existingReceipt = Receipt::query()
                ->where('order_id', $order->id)
                ->latest('id')
                ->first();

            if (! $existingReceipt) {
                return response()->json(['error' => 'No previous receipt found for this order.'], 404);
            }

            $content = json_decode((string) $existingReceipt->content, true);
            $itemIds = $this->parseItemIds($content['item_ids'] ?? null);
            $scope = $content['scope'] ?? 'full';
        }

        if ($request->filled('payment_id')) {
            $payment = $order->payments->firstWhere('id', (int) $request->input('payment_id'));
            if (! $payment) {
                return response()->json(['error' => 'Payment does not belong to this order.'], 422);
            }

            $paymentItemIds = $this->parseItemIds($payment->item_ids ?? null);
            if ($paymentItemIds) {
                $itemIds = $paymentItemIds;
                $scope = 'paid';
            }
        }

        if ($scope === 'paid' && ! $itemIds) {
            $itemIds = $order->payments
                ->flatMap(fn ($payment) => $this->parseItemIds($payment->item_ids ?? null))
                ->unique()
                ->values()
                ->all();

            if (! $itemIds && (float) $order->payments->sum('amount') >= (float) $order->total) {
                $itemIds = $order->items->pluck('id')->all();
            }
        }

        if ($scope === 'unprinted') {
            $printedItemIds = Receipt::query()
                ->where('order_id', $order->id)
                ->pluck('content')
                ->flatMap(function ($content) {
                    $decoded = json_decode((string) $content, true);

                    return is_array($decoded) ? ($decoded['item_ids'] ?? []) : [];
                })
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $itemIds = $order->items
                ->pluck('id')
                ->diff($printedItemIds)
                ->values()
                ->all();
        }

        $receiptItems = $order->items
            ->reject(fn ($item) => in_array($item->status, self::NON_ACTIVE_ITEM_STATUSES, true))
            ->when($itemIds, fn ($items) => $items->whereIn('id', $itemIds))
            ->values();

        if ($receiptItems->isEmpty()) {
            return response()->json(['error' => 'No receiptable items found for this scope.'], 422);
        }

        $receiptLines = $this->buildReceiptLines($order, $receiptItems, $scope, $payment);
        $receiptTotal = round((float) $receiptLines->sum('display_total'), 2);
        $receiptPaid = round((float) ($payment?->amount ?? $order->payments->sum('amount')), 2);
        $receiptBalance = round(max((float) $order->total - $receiptPaid, 0), 2);
        $currency = Setting::query()->where('key', 'currency')->value('value') ?: 'USD';
        $receipt = $existingReceipt ?? Receipt::create([
            'order_id' => $order->id,
            'receipt_number' => $this->nextReceiptNumber($order),
            'content' => json_encode([
                'order_id' => $order->id,
                'scope' => $scope,
                'payment_id' => $payment?->id,
                'item_ids' => $receiptItems->pluck('id')->values()->all(),
                'lines' => $receiptLines->values()->all(),
                'total' => $receiptTotal,
                'created_at' => now()->toISOString(),
            ]),
        ]);

        $pdf = \PDF::loadView('receipts.order', compact('order', 'receipt', 'receiptItems', 'receiptLines', 'receiptTotal', 'receiptPaid', 'receiptBalance', 'currency', 'scope', 'payment'));

        return $pdf->download("receipt_{$receipt->receipt_number}.pdf");
    }

    private function nextReceiptNumber(Order $order): string
    {
        do {
            $number = 'RCPT-'.$order->id.'-'.Str::upper(Str::random(6));
        } while (Receipt::where('receipt_number', $number)->exists());

        return $number;
    }

    private function buildReceiptLines(Order $order, $receiptItems, string $scope, ?Payment $payment = null)
    {
        $itemsById = $order->items->keyBy('id');

        return $receiptItems->map(function (OrderItem $item) use ($itemsById, $scope, $payment) {
            $quantity = max((int) ($item->quantity ?? 0), 1);
            $itemTotal = round((float) ($item->total ?? 0), 2);
            $paidAmount = round((float) $item->paid_amount, 2);
            $remainingTotal = round(max($itemTotal - $paidAmount, 0), 2);
            $displayTotal = $itemTotal;

            if (in_array($scope, ['full', 'unprinted'], true)) {
                $displayTotal = $remainingTotal;
            } elseif ($payment) {
                $paymentAmount = $this->paymentAmountForItem($payment, $item, $itemsById);
                $displayTotal = $paymentAmount > 0
                    ? $paymentAmount
                    : ($remainingTotal > 0 ? $remainingTotal : $itemTotal);
            } elseif ($remainingTotal > 0) {
                $displayTotal = $remainingTotal;
            }

            $displayTotal = round($displayTotal, 2);

            return [
                'id' => (int) $item->id,
                'name' => $item->product->name ?? '',
                'quantity' => $quantity,
                'price' => round((float) ($item->price ?? 0), 2),
                'display_price' => round($displayTotal / $quantity, 2),
                'total' => $itemTotal,
                'display_total' => $displayTotal,
                'paid_amount' => $paidAmount,
                'remaining_total' => $remainingTotal,
                'payment_status' => $item->payment_status,
            ];
        });
    }

    private function paymentAmountForItem(Payment $payment, OrderItem $item, $itemsById): float
    {
        $itemIds = $this->parseItemIds($payment->item_ids ?? null);
        if (! $itemIds || ! in_array((int) $item->id, $itemIds, true)) {
            return 0.0;
        }

        $itemTotal = (float) ($item->total ?? 0);
        if (count($itemIds) === 1) {
            return round(min((float) $payment->amount, $itemTotal), 2);
        }

        $selectedTotal = collect($itemIds)
            ->map(fn ($id) => $itemsById->get($id))
            ->filter()
            ->reject(fn ($selectedItem) => in_array($selectedItem->status, self::NON_ACTIVE_ITEM_STATUSES, true))
            ->sum(fn ($selectedItem) => (float) ($selectedItem->total ?? 0));

        if ($selectedTotal <= 0) {
            return 0.0;
        }

        return round((float) $payment->amount * ($itemTotal / $selectedTotal), 2);
    }

    private function resolveCustomer(array $data): ?Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::find($data['customer_id']);
        }

        if (empty($data['customer_phone']) && empty($data['customer_email'])) {
            return null;
        }

        $customer = Customer::query()
            ->when(
                ! empty($data['customer_phone']),
                fn ($query) => $query->where('phone', $data['customer_phone'])
            )
            ->when(
                empty($data['customer_phone']) && ! empty($data['customer_email']),
                fn ($query) => $query->where('email', $data['customer_email'])
            )
            ->first();

        if (! $customer) {
            return Customer::create([
                'name' => $data['customer_name'] ?? 'Walk-in Customer',
                'phone' => $data['customer_phone'] ?? null,
                'email' => $data['customer_email'] ?? null,
            ]);
        }

        $customer->fill(array_filter([
            'name' => $data['customer_name'] ?? null,
            'phone' => $data['customer_phone'] ?? null,
            'email' => $data['customer_email'] ?? null,
        ], fn ($value) => filled($value)))->save();

        return $customer;
    }
}
