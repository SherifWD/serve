<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        return response()->json($query->with('items.product','branch','table','employee')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'table_id' => 'required|integer|exists:tables,id',
            'status' => 'required|string',
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'order_items.*.price' => 'required|numeric|min:0',
        ]);

        $order = Order::create([
            'branch_id' => $data['branch_id'],
            'table_id' => $data['table_id'],
            'status' => $data['status'],
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

        return response()->json($order->load('items.product'), 201);
    }

    public function show($id)
    {
        return Order::with('items.product')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $data = $request->validate([
            'status' => 'string',
        ]);
        $order->update($data);
        return response()->json($order);
    }

    public function destroy($id)
    {
        Order::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
