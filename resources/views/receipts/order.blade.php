<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        @page { margin: 10mm 6mm; }
        * { box-sizing: border-box; }
        body {
            color: #2f3437;
            font-family: DejaVu Sans Mono, DejaVu Sans, monospace;
            font-size: 11px;
            line-height: 1.35;
            margin: 0;
        }
        .receipt {
            margin: 0 auto;
            width: 68mm;
        }
        .center { text-align: center; }
        .merchant-logo {
            display: block;
            height: 48px;
            margin: 0 auto 6px;
            object-fit: contain;
            width: 48px;
        }
        .solution-logo {
            display: block;
            height: 34px;
            margin: 0 auto;
            object-fit: contain;
            width: 128px;
        }
        .merchant-mark,
        .solution-mark {
            border: 2px solid #2f3437;
            border-radius: 50%;
            display: inline-block;
            font-weight: 800;
            height: 42px;
            line-height: 38px;
            margin-bottom: 6px;
            text-align: center;
            width: 42px;
        }
        .merchant-name {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }
        .muted { color: #686f73; }
        .rule {
            border-top: 1px dashed #9aa0a4;
            margin: 10px 0;
        }
        .sale-title {
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 6px;
            text-align: center;
        }
        .row,
        .line-item,
        .total-row,
        .signature-row {
            display: table;
            table-layout: fixed;
            width: 100%;
        }
        .row span,
        .line-item span,
        .total-row span,
        .signature-row span {
            display: table-cell;
            vertical-align: top;
        }
        .row span:last-child,
        .line-item span:last-child,
        .total-row span:last-child {
            text-align: right;
        }
        .item-name { width: 72%; }
        .amount { width: 28%; }
        .line-item { margin: 3px 0; }
        .total-row {
            font-size: 12px;
            font-weight: 800;
            margin-top: 3px;
        }
        .signature-row { margin: 12px 0; }
        .signature-label { width: 22%; }
        .signature-line {
            border-bottom: 1px solid #9aa0a4;
            height: 16px;
            width: 78%;
        }
        .footer {
            margin-top: 22px;
            text-align: center;
        }
        .solution-mark {
            border-radius: 8px;
            font-size: 9px;
            height: 32px;
            letter-spacing: 0;
            line-height: 28px;
            width: 78px;
        }
        .copy {
            font-size: 12px;
            font-weight: 800;
            margin-top: 12px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
@php
    $lines = $receiptLines ?? $receiptItems ?? $order->items;
    $lineTotal = (float) ($receiptTotal ?? $order->total);
    $discount = $scope === 'full' ? (float) $order->discount : 0.0;
    $tax = $scope === 'full' ? (float) $order->tax : 0.0;
    $summaryTotal = max($lineTotal - $discount + $tax, 0);
    $paid = (float) ($receiptPaid ?? $order->payments->sum('amount'));
    $balance = (float) ($receiptBalance ?? max(((float) $order->total) - $paid, 0));
    $restaurant = $order->branch?->restaurant;
    $restaurantName = $restaurant?->name ?? 'Restaurant';
    $logoUrl = $restaurant?->logo_url;
    $logoIsExternal = $logoUrl && (
        str_starts_with($logoUrl, 'http://') ||
        str_starts_with($logoUrl, 'https://') ||
        str_starts_with($logoUrl, 'data:')
    );
    $logoSource = $logoUrl
        ? ($logoIsExternal ? $logoUrl : public_path(ltrim($logoUrl, '/')))
        : null;
    $logoAvailable = $logoSource && ($logoIsExternal || file_exists($logoSource));
    $solutionLogoSource = public_path('images/janova-serve-pos-logo.svg');
    $initials = collect(explode(' ', $restaurantName))
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->filter()
        ->take(2)
        ->join('');
    $timestamp = $receipt?->created_at
        ? \Illuminate\Support\Carbon::parse($receipt->created_at)
        : now();
    $paymentMethodSource = isset($payment) && $payment
        ? collect([$payment->method])
        : $order->payments->pluck('method');
    $paymentMethods = $paymentMethodSource
        ->filter()
        ->unique()
        ->map(fn ($method) => strtoupper((string) $method))
        ->join(' / ');
    $formatMoney = fn ($amount) => number_format((float) $amount, 2).' '.$currency;
@endphp

<div class="receipt">
    <div class="center">
        @if($logoAvailable)
            <img class="merchant-logo" src="{{ $logoSource }}" alt="{{ $restaurantName }}">
        @else
            <div class="merchant-mark">{{ $initials ?: 'J' }}</div>
        @endif
        <div class="merchant-name">{{ $restaurantName }}</div>
        <div>{{ $order->branch?->name }}</div>
        @if($order->branch?->location)
            <div class="muted">{{ $order->branch->location }}</div>
        @endif
    </div>

    <div class="rule"></div>

    <div class="sale-title">SALE</div>
    <div class="row">
        <span>{{ $timestamp->format('m/d/Y') }}</span>
        <span>{{ $timestamp->format('h:i A') }}</span>
    </div>
    <div class="row">
        <span>Receipt #</span>
        <span>{{ $receipt->receipt_number ?? '-' }}</span>
    </div>
    <div class="row">
        <span>Order #</span>
        <span>{{ $order->id }}</span>
    </div>
    <div class="row">
        <span>Table</span>
        <span>{{ $order->table->name ?? strtoupper((string) $order->order_type) }}</span>
    </div>
    <div class="row">
        <span>Cashier</span>
        <span>{{ $order->employee->name ?? '-' }}</span>
    </div>
    @if($order->customer)
        <div class="row">
            <span>Customer</span>
            <span>{{ $order->customer->name }}</span>
        </div>
    @endif
    @if($paymentMethods)
        <div class="row">
            <span>Payment</span>
            <span>{{ $paymentMethods }}</span>
        </div>
    @endif

    <div class="rule"></div>

    @foreach($lines as $line)
        @php
            $name = data_get($line, 'name', data_get($line, 'product.name', 'Item'));
            $quantity = (int) data_get($line, 'quantity', 0);
            $total = (float) data_get($line, 'display_total', data_get($line, 'total', 0));
        @endphp
        <div class="line-item">
            <span class="item-name">{{ $quantity }} {{ $name }}</span>
            <span class="amount">{{ $formatMoney($total) }}</span>
        </div>
    @endforeach

    <div class="rule"></div>

    <div class="row">
        <span>SUBTOTAL</span>
        <span>{{ $formatMoney($lineTotal) }}</span>
    </div>
    @if($discount > 0)
        <div class="row">
            <span>DISCOUNT</span>
            <span>-{{ $formatMoney($discount) }}</span>
        </div>
    @endif
    @if($tax > 0)
        <div class="row">
            <span>TAX</span>
            <span>{{ $formatMoney($tax) }}</span>
        </div>
    @endif
    <div class="total-row">
        <span>TOTAL</span>
        <span>{{ $formatMoney($summaryTotal) }}</span>
    </div>
    <div class="row">
        <span>PAID</span>
        <span>{{ $formatMoney($paid) }}</span>
    </div>
    <div class="row">
        <span>BALANCE</span>
        <span>{{ $formatMoney($balance) }}</span>
    </div>

    <div class="signature-row">
        <span class="signature-label">TIP</span>
        <span class="signature-line"></span>
    </div>
    <div class="signature-row">
        <span class="signature-label">TOTAL</span>
        <span class="signature-line"></span>
    </div>

    <div class="footer">
        <div class="copy">Approved<br>Thank You<br>Customer Copy</div>
        <div class="rule"></div>
        @if(file_exists($solutionLogoSource))
            <img class="solution-logo" src="{{ $solutionLogoSource }}" alt="Janova Serve POS">
        @else
            <div class="solution-mark">Janova Serve POS</div>
        @endif
    </div>
</div>
</body>
</html>
