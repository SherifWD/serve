<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Owner Receipt {{ $report['start_date'] }} - {{ $report['end_date'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 8px; }
        .muted { color: #6b7280; }
        .summary { margin: 14px 0; padding: 10px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f9fafb; }
        .right { text-align: right; }
        .order-block { margin-top: 18px; page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>Owner Date Range Receipt</h1>
    <div class="muted">Period: {{ $report['start_date'] }} to {{ $report['end_date'] }}</div>
    <div class="summary">
        <strong>Total sales:</strong> {{ number_format($report['total_sales'], 2) }}<br>
        <strong>Paid amount recorded:</strong> {{ number_format($report['paid_amount'], 2) }}<br>
        <strong>Paid orders:</strong> {{ $report['order_count'] }}
    </div>

    <h2>Product Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="right">Qty</th>
                <th class="right">Returned/Refunded</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['product_summary'] as $product)
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td class="right">{{ $product['quantity'] }}</td>
                    <td class="right">{{ $product['returned'] }}</td>
                    <td class="right">{{ number_format($product['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No paid product sales in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2 style="margin-top: 20px;">Order Details</h2>
    <table>
        <thead>
            <tr>
                <th>Order</th>
                <th>Branch / Table</th>
                <th>Customer</th>
                <th>Item</th>
                <th>Status</th>
                <th>Notes</th>
                <th class="right">Qty</th>
                <th class="right">Line Total</th>
                <th class="right">Order Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                @foreach($order->items as $item)
                    <tr>
                        <td>#{{ $order->id }}<br><span class="muted">{{ $order->order_date ?? optional($order->created_at)->format('Y-m-d') }}</span></td>
                        <td>{{ $order->branch->name ?? '-' }}<br><span class="muted">{{ $order->table->name ?? '-' }}</span></td>
                        <td>{{ $order->customer->name ?? 'Guest' }}</td>
                        <td>{{ $item->product->name ?? 'Item' }}</td>
                        <td>{{ $item->status }} / {{ $item->kds_status ?? '-' }}</td>
                        <td>{{ $item->item_note ?? $item->change_note ?? '-' }}</td>
                        <td class="right">{{ $item->quantity }}</td>
                        <td class="right">{{ number_format((float) $item->total, 2) }}</td>
                        <td class="right">{{ number_format((float) $order->total, 2) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="9">No paid orders in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
