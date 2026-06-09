<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    use EnforcesTenantAccess;

    private const DATASETS = [
        'products',
        'customers',
        'orders',
        'payments',
        'receipts',
        'inventory-items',
    ];

    public function show(Request $request, string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, self::DATASETS, true), 404, 'Unsupported export dataset.');

        $filters = $request->validate([
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        [$headers, $rows] = $this->dataset($request, $dataset, $filters);
        $filename = 'janova-'.$dataset.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn ($value) => $this->csvValue($value), $row));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function dataset(Request $request, string $dataset, array $filters): array
    {
        return match ($dataset) {
            'products' => $this->products($request, $filters),
            'customers' => $this->customers($request, $filters),
            'orders' => $this->orders($request, $filters),
            'payments' => $this->payments($request, $filters),
            'receipts' => $this->receipts($request, $filters),
            'inventory-items' => $this->inventoryItems($request, $filters),
        };
    }

    private function products(Request $request, array $filters): array
    {
        $query = $this->branchScoped(
            $request,
            Product::query()->with(['branch.restaurant', 'category'])
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'restaurant_id',
            'restaurant_name',
            'branch_id',
            'branch_name',
            'category_id',
            'category_name',
            'sku',
            'name',
            'price',
            'stock',
            'min_stock',
            'is_available',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('branch_id')
            ->orderBy('name')
            ->cursor()
            ->map(fn (Product $product) => [
                $product->id,
                $product->branch?->restaurant_id,
                $product->branch?->restaurant?->name,
                $product->branch_id,
                $product->branch?->name,
                $product->category_id,
                $product->category?->name,
                $product->sku,
                $product->name,
                $product->price,
                $product->stock,
                $product->min_stock,
                $product->is_available,
                $product->created_at,
                $product->updated_at,
            ])];
    }

    private function customers(Request $request, array $filters): array
    {
        $query = $this->branchRelationScoped(
            $request,
            Customer::query()
                ->withCount('orders')
                ->withMax('orders', 'created_at'),
            'orders.branch'
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'name',
            'email',
            'phone',
            'loyalty_points',
            'orders_count',
            'last_order_at',
            'phone_verified_at',
            'email_verified_at',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('name')
            ->cursor()
            ->map(fn (Customer $customer) => [
                $customer->id,
                $customer->name,
                $customer->email,
                $customer->phone,
                $customer->loyalty_points,
                $customer->orders_count,
                $customer->orders_max_created_at,
                $customer->phone_verified_at,
                $customer->email_verified_at,
                $customer->created_at,
                $customer->updated_at,
            ])];
    }

    private function orders(Request $request, array $filters): array
    {
        $query = $this->branchScoped(
            $request,
            Order::query()->with(['branch.restaurant', 'table', 'customer', 'payments'])
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'restaurant_id',
            'restaurant_name',
            'branch_id',
            'branch_name',
            'table_id',
            'table_name',
            'customer_id',
            'customer_name',
            'customer_phone',
            'order_type',
            'status',
            'payment_status',
            'payment_method',
            'subtotal',
            'tax',
            'discount',
            'discount_type',
            'coupon_code',
            'total',
            'paid_amount',
            'paid_at',
            'order_date',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('created_at')
            ->cursor()
            ->map(fn (Order $order) => [
                $order->id,
                $order->branch?->restaurant_id,
                $order->branch?->restaurant?->name,
                $order->branch_id,
                $order->branch?->name,
                $order->table_id,
                $order->table?->name,
                $order->customer_id,
                $order->customer?->name,
                $order->customer?->phone,
                $order->order_type,
                $order->status,
                $order->payment_status,
                $order->payment_method,
                $order->subtotal,
                $order->tax,
                $order->discount,
                $order->discount_type,
                $order->coupon_code,
                $order->total,
                $order->payments->sum('amount'),
                $order->paid_at,
                $order->order_date,
                $order->created_at,
                $order->updated_at,
            ])];
    }

    private function payments(Request $request, array $filters): array
    {
        $query = $this->branchRelationScoped(
            $request,
            Payment::query()->with(['order.branch.restaurant', 'order.customer']),
            'order.branch'
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'order_id',
            'restaurant_id',
            'branch_id',
            'customer_id',
            'customer_name',
            'method',
            'provider',
            'provider_reference',
            'scope',
            'item_ids',
            'amount',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('created_at')
            ->cursor()
            ->map(fn (Payment $payment) => [
                $payment->id,
                $payment->order_id,
                $payment->order?->branch?->restaurant_id,
                $payment->order?->branch_id,
                $payment->order?->customer_id,
                $payment->order?->customer?->name,
                $payment->method,
                $payment->provider,
                $payment->provider_reference,
                $payment->scope,
                $payment->item_ids,
                $payment->amount,
                $payment->created_at,
                $payment->updated_at,
            ])];
    }

    private function receipts(Request $request, array $filters): array
    {
        $query = $this->branchRelationScoped(
            $request,
            Receipt::query()->with(['order.branch.restaurant', 'order.customer']),
            'order.branch'
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'receipt_number',
            'order_id',
            'restaurant_id',
            'branch_id',
            'customer_id',
            'scope',
            'payment_id',
            'total',
            'item_ids',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('created_at')
            ->cursor()
            ->map(function (Receipt $receipt) {
                $content = json_decode((string) $receipt->content, true);
                $content = is_array($content) ? $content : [];

                return [
                    $receipt->id,
                    $receipt->receipt_number,
                    $receipt->order_id,
                    $receipt->order?->branch?->restaurant_id,
                    $receipt->order?->branch_id,
                    $receipt->order?->customer_id,
                    $content['scope'] ?? null,
                    $content['payment_id'] ?? null,
                    $content['total'] ?? null,
                    $content['item_ids'] ?? null,
                    $receipt->created_at,
                    $receipt->updated_at,
                ];
            })];
    }

    private function inventoryItems(Request $request, array $filters): array
    {
        $query = $this->branchScoped(
            $request,
            InventoryItem::query()->with(['branch.restaurant', 'product', 'ingredient'])
        );

        $this->applyDateFilters($query, $filters);

        return [[
            'id',
            'restaurant_id',
            'restaurant_name',
            'branch_id',
            'branch_name',
            'product_id',
            'product_name',
            'ingredient_id',
            'ingredient_name',
            'name',
            'unit',
            'quantity',
            'min_stock',
            'created_at',
            'updated_at',
        ], $query
            ->orderBy('branch_id')
            ->orderBy('name')
            ->cursor()
            ->map(fn (InventoryItem $item) => [
                $item->id,
                $item->branch?->restaurant_id,
                $item->branch?->restaurant?->name,
                $item->branch_id,
                $item->branch?->name,
                $item->product_id,
                $item->product?->name,
                $item->ingredient_id,
                $item->ingredient?->name,
                $item->name,
                $item->unit,
                $item->quantity,
                $item->min_stock,
                $item->created_at,
                $item->updated_at,
            ])];
    }

    private function applyDateFilters(Builder $query, array $filters, string $column = 'created_at'): void
    {
        if (! empty($filters['from_date'])) {
            $query->whereDate($column, '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate($column, '<=', $filters['to_date']);
        }
    }

    private function csvValue(mixed $value): string|int|float|null
    {
        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value)) {
            return (string) json_encode($value);
        }

        return $value;
    }
}
