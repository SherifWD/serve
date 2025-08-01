<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { font-size: 18px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px;}
        th, td { border-bottom: 1px solid #ddd; padding: 4px 6px; }
    </style>
</head>
<body>
    <div class="header">Order #{{ $order->id }} - Table: {{ $order->table->name ?? '-' }}</div>
    <div>Date: {{ $order->order_date }}</div>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product->name ?? '' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $item->price }}</td>
                <td>{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:8px;">
        Discount: {{ $order->discount }} {{ $order->discount_type }}
        <br>
        Coupon: {{ $order->coupon_code ?? '-' }}
        <br>
        <b>Total: {{ $order->total }}</b>
    </div>
</body>
</html>
