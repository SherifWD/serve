<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = $this->branchScoped($request, Order::query());
        return response()->json($query->with('items.product','branch','table','customer','employee')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'table_id' => 'nullable|integer|exists:tables,id',
            'status' => 'required|string',
            'order_type' => 'nullable|in:dine-in,takeaway,delivery',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_email' => 'nullable|email|max:255',
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $orderType = $data['order_type'] ?? (! empty($data['table_id']) ? 'dine-in' : 'takeaway');

        if ($orderType === 'dine-in' && empty($data['table_id'])) {
            abort(422, 'A table is required for dine-in orders.');
        }

        $table = null;
        if (! empty($data['table_id'])) {
            $table = Table::query()->findOrFail($data['table_id']);
            abort_unless((int) $table->branch_id === (int) $data['branch_id'], 422, 'Order table must belong to the selected branch.');
        }

        $productIds = collect($data['order_items'])->pluck('product_id')->unique()->values();
        $productCount = Product::query()
            ->whereIn('id', $productIds)
            ->where('branch_id', $data['branch_id'])
            ->count();
        abort_unless($productCount === $productIds->count(), 422, 'Order products must belong to the selected branch.');

        $customer = $this->resolveCustomer($data);

        $order = Order::create([
            'branch_id' => $data['branch_id'],
            'table_id' => $table?->id,
            'customer_id' => $customer?->id,
            'status' => $data['status'],
            'order_type' => $orderType,
            'total' => collect($data['order_items'])->sum(fn($i) => $i['quantity'] * $i['price']),
            'order_date' => now(),
        ]);

        foreach ($data['order_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'total' => $item['quantity'] * $item['price'],
            ]);
        }

        return response()->json($order->load('items.product', 'customer', 'branch', 'table'), 201);
    }

    public function show(Request $request, $id)
    {
        return $this->branchScoped($request, Order::with('items.product'))->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $order = $this->branchScoped($request, Order::query())->findOrFail($id);
        $data = $request->validate([
            'status' => 'string',
        ]);
        $order->update($data);
        return response()->json($order);
    }

    public function destroy(Request $request, $id)
    {
        $order = $this->branchScoped($request, Order::query())->findOrFail($id);
        $order->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function resolveCustomer(array $data): ?Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::query()->find($data['customer_id']);
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
            return Customer::query()->create([
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
