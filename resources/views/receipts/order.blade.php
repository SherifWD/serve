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
    @php
        $lines = $receiptLines ?? $receiptItems ?? $order->items;
        $lineTotal = $receiptTotal ?? $order->total;
    @endphp
    <div class="header">Order #{{ $order->id }} - Table: {{ $order->table->name ?? '-' }}</div>
    @isset($receipt)
        <div>Receipt: {{ $receipt->receipt_number }}</div>
    @endisset
    <div>Date: {{ $order->order_date }}</div>
    <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($lines as $line)
            @php
                $name = data_get($line, 'name', data_get($line, 'product.name', ''));
                $quantity = data_get($line, 'quantity', 0);
                $price = (float) data_get($line, 'display_price', data_get($line, 'price', 0));
                $total = (float) data_get($line, 'display_total', data_get($line, 'total', 0));
                $status = data_get($line, 'payment_status');
            @endphp
            <tr>
                <td>{{ $name }}</td>
                <td>{{ $quantity }}</td>
                <td>{{ number_format($price, 2) }}</td>
                <td>{{ number_format($total, 2) }}</td>
                <td>{{ $status ? ucfirst($status) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:8px;">
        Discount: {{ $order->discount }} {{ $order->discount_type }}
        <br>
        Coupon: {{ $order->coupon_code ?? '-' }}
        <br>
        <b>Total: {{ number_format((float) $lineTotal, 2) }}</b>
    </div>
</body>
</html>
