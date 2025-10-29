<?php

namespace App\Domain\Commerce\Http\Controllers;

use App\Domain\Commerce\Http\Requests\OrderStoreRequest;
use App\Domain\Commerce\Http\Requests\OrderUpdateRequest;
use App\Domain\Commerce\Http\Resources\OrderResource;
use App\Domain\Commerce\Models\Customer;
use App\Domain\Commerce\Models\Order;
use App\Domain\Commerce\Models\OrderItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('placed_at')
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders)->response();
    }

    public function store(OrderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();
        $itemsData = collect($data['items'] ?? []);
        unset($data['items']);

        if (!empty($data['customer_id'])) {
            Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['customer_id'])
                ->firstOrFail();
        }

        $order = DB::transaction(function () use ($tenantId, $data, $itemsData) {
            $normalizedItems = $itemsData->map(function (array $item): array {
                $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 1;
                $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;
                $lineTotal = isset($item['line_total']) ? (float) $item['line_total'] : $quantity * $unitPrice;

                return [
                    'sku' => $item['sku'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'metadata' => $item['metadata'] ?? null,
                ];
            });

            $computedSubtotal = $normalizedItems->sum('line_total');

            $subtotal = $data['subtotal'] ?? $computedSubtotal;
            $discount = $data['discount'] ?? 0;
            $shippingFee = $data['shipping_fee'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $total = $data['total'] ?? ($subtotal - $discount + $shippingFee + $tax);

            $order = Order::create([
                'tenant_id' => $tenantId,
                'order_number' => $data['order_number'],
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'total' => $total,
                'currency' => $data['currency'] ?? 'USD',
                'placed_at' => $data['placed_at'] ?? now(),
                'fulfilled_at' => $data['fulfilled_at'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($normalizedItems as $item) {
                OrderItem::create([
                    ...$item,
                    'tenant_id' => $tenantId,
                    'order_id' => $order->id,
                ]);
            }

            return $order->load(['customer', 'items']);
        });

        return OrderResource::make($order)->response()->setStatusCode(201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorizeTenantResource($order);

        return OrderResource::make($order->load(['customer', 'items']))->response();
    }

    public function update(OrderUpdateRequest $request, Order $order): JsonResponse
    {
        $this->authorizeTenantResource($order);
        $data = $request->validated();
        $itemsData = collect($data['items'] ?? []);
        unset($data['items']);

        $tenantId = app('tenant.context')->ensureTenant()->id;

        if (array_key_exists('customer_id', $data) && !empty($data['customer_id'])) {
            Customer::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['customer_id'])
                ->firstOrFail();
        }

        $order = DB::transaction(function () use ($order, $tenantId, $data, $itemsData) {
            if ($itemsData->isNotEmpty()) {
                foreach ($itemsData as $item) {
                    if (($item['_action'] ?? null) === 'delete' && !empty($item['id'])) {
                        OrderItem::query()
                            ->where('tenant_id', $tenantId)
                            ->where('order_id', $order->id)
                            ->where('id', $item['id'])
                            ->delete();
                        continue;
                    }

                    $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 1;
                    $unitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;
                    $lineTotal = isset($item['line_total']) ? (float) $item['line_total'] : $quantity * $unitPrice;

                    $payload = [
                        'sku' => $item['sku'] ?? null,
                        'name' => $item['name'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'metadata' => $item['metadata'] ?? null,
                    ];

                    if (!empty($item['id'])) {
                        $existing = OrderItem::query()
                            ->where('tenant_id', $tenantId)
                            ->where('order_id', $order->id)
                            ->where('id', $item['id'])
                            ->firstOrFail();
                        $existing->update($payload);
                    } else {
                        OrderItem::create([
                            ...$payload,
                            'tenant_id' => $tenantId,
                            'order_id' => $order->id,
                        ]);
                    }
                }
            }

            $currentItems = $order->items()->get();
            $computedSubtotal = $currentItems->sum('line_total');

            $subtotal = $data['subtotal'] ?? $computedSubtotal;
            $discount = $data['discount'] ?? $order->discount;
            $shippingFee = $data['shipping_fee'] ?? $order->shipping_fee;
            $tax = $data['tax'] ?? $order->tax;
            $total = $data['total'] ?? ($subtotal - $discount + $shippingFee + $tax);

            $order->update([
                'order_number' => $data['order_number'] ?? $order->order_number,
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'channel' => $data['channel'] ?? $order->channel,
                'status' => $data['status'] ?? $order->status,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'tax' => $tax,
                'total' => $total,
                'currency' => $data['currency'] ?? $order->currency,
                'placed_at' => $data['placed_at'] ?? $order->placed_at,
                'fulfilled_at' => $data['fulfilled_at'] ?? $order->fulfilled_at,
                'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $order->metadata,
            ]);

            return $order->load(['customer', 'items']);
        });

        return OrderResource::make($order)->response();
    }

    public function destroy(Order $order): JsonResponse
    {
        $this->authorizeTenantResource($order);
        $order->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Order $order): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($order->tenant_id !== $tenantId, 404);
    }
}
