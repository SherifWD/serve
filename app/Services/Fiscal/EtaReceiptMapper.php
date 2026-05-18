<?php

namespace App\Services\Fiscal;

use App\Models\FiscalProfile;
use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EtaReceiptMapper
{
    public function map(Receipt $receipt): array
    {
        $receipt->loadMissing([
            'order.branch.restaurant',
            'order.customer',
            'order.table',
            'order.payments',
        ]);

        $order = $receipt->order;
        $content = json_decode((string) $receipt->content, true) ?: [];
        $lines = collect($content['lines'] ?? []);
        $profile = FiscalProfile::effectiveForBranch((int) $order->branch_id, (int) $order->branch?->restaurant_id);
        $warnings = $this->warnings($profile);
        $itemData = $this->itemData($lines, $profile);
        $taxTotal = round($itemData->sum(fn (array $line) => collect($line['taxableItems'] ?? [])->sum('amount')), 5);
        $totalSales = round($itemData->sum('totalSale'), 5);
        $netAmount = round($itemData->sum('netSale'), 5);
        $totalAmount = round($itemData->sum('total'), 5);
        $issuedAt = $receipt->created_at ?: $order->paid_at ?: $order->created_at ?: now();
        $receiptNumber = (string) $receipt->receipt_number;

        $document = [
            'header' => [
                'dateTimeIssued' => Carbon::parse($issuedAt)->utc()->format('Y-m-d\TH:i:s\Z'),
                'receiptNumber' => $receiptNumber,
                'uuid' => $this->uuidForReceipt($receipt, $profile),
                'previousUUID' => '',
                'currency' => strtoupper((string) ($profile->currency_code ?: 'USD')),
                'orderdeliveryMode' => $this->deliveryMode($order->order_type ?? null),
            ],
            'documentType' => [
                'receiptType' => $profile->eta_receipt_type ?: 'SC',
                'typeVersion' => $profile->eta_type_version ?: '1.2',
            ],
            'seller' => [
                'rin' => (string) ($profile->eta_seller_rin ?? ''),
                'companyTradeName' => (string) ($profile->eta_seller_name ?? $order->branch?->restaurant?->name ?? ''),
                'branchCode' => (string) ($profile->eta_branch_code ?? $order->branch_id),
                'branchAddress' => $this->branchAddress($profile),
                'deviceSerialNumber' => (string) ($profile->eta_device_serial_number ?? ''),
                'activityCode' => (string) ($profile->eta_activity_code ?? ''),
            ],
            'buyer' => $this->buyer($order->customer, $totalAmount, (float) $profile->buyer_id_threshold),
            'itemData' => $itemData->values()->all(),
            'totalSales' => $totalSales,
            'totalCommercialDiscount' => 0.0,
            'totalItemsDiscount' => 0.0,
            'netAmount' => $netAmount,
            'feesAmount' => 0.0,
            'totalAmount' => $totalAmount,
            'taxTotals' => $taxTotal > 0 ? [[
                'taxType' => $profile->vat_tax_type ?: 'T1',
                'amount' => $taxTotal,
            ]] : [],
            'paymentMethod' => $this->paymentMethod($order->payment_method, $profile->default_payment_method_code),
            'adjustment' => 0.0,
        ];

        return [
            'eta_ready' => empty($warnings),
            'warnings' => $warnings,
            'profile' => [
                'id' => $profile->exists ? $profile->id : null,
                'display_name' => $profile->display_name,
                'branch_id' => $profile->branch_id,
                'restaurant_id' => $profile->restaurant_id,
                'vat_rate' => (float) $profile->vat_rate,
                'price_includes_vat' => (bool) $profile->price_includes_vat,
                'currency_code' => $profile->currency_code,
            ],
            'submission' => [
                'receipts' => [$document],
                'signatures' => [],
            ],
            'receipt' => [
                'id' => $receipt->id,
                'receipt_number' => $receiptNumber,
                'order_id' => $order->id,
            ],
        ];
    }

    private function itemData(Collection $lines, FiscalProfile $profile): Collection
    {
        $vatRate = max((float) $profile->vat_rate, 0.0);
        $taxRate = round($vatRate * 100, 4);

        return $lines->values()->map(function (array $line, int $index) use ($profile, $vatRate, $taxRate): array {
            $quantity = max((float) ($line['quantity'] ?? 1), 0.00001);
            $grossTotal = round((float) ($line['display_total'] ?? $line['total'] ?? 0), 5);

            if ($profile->price_includes_vat && $vatRate > 0) {
                $netSale = round($grossTotal / (1 + $vatRate), 5);
                $taxAmount = round($grossTotal - $netSale, 5);
            } else {
                $netSale = round($grossTotal, 5);
                $taxAmount = round($netSale * $vatRate, 5);
                $grossTotal = round($netSale + $taxAmount, 5);
            }

            return [
                'internalCode' => (string) ($line['id'] ?? 'line-'.($index + 1)),
                'description' => Str::limit((string) ($line['name'] ?? 'Menu item'), 500, ''),
                'itemType' => 'EGS',
                'itemCode' => (string) ($line['eta_item_code'] ?? $line['sku'] ?? $line['id'] ?? 'EGS-'.$index),
                'unitType' => 'EA',
                'quantity' => $quantity,
                'unitPrice' => round($netSale / $quantity, 5),
                'netSale' => $netSale,
                'totalSale' => $netSale,
                'total' => $grossTotal,
                'taxableItems' => $taxAmount > 0 ? [[
                    'taxType' => $profile->vat_tax_type ?: 'T1',
                    'amount' => $taxAmount,
                    'subType' => $profile->vat_subtype ?: 'V009',
                    'rate' => $taxRate,
                ]] : [],
            ];
        });
    }

    private function warnings(FiscalProfile $profile): array
    {
        $required = [
            'eta_seller_rin' => 'Seller RIN is required before ETA submission.',
            'eta_seller_name' => 'Seller trade name is required before ETA submission.',
            'eta_branch_code' => 'ETA branch code is required before ETA submission.',
            'eta_device_serial_number' => 'POS serial number is required before ETA submission.',
            'eta_activity_code' => 'ETA activity code is required before ETA submission.',
            'address_governate' => 'Branch governorate is required before ETA submission.',
            'address_region_city' => 'Branch city/region is required before ETA submission.',
            'address_street' => 'Branch street is required before ETA submission.',
            'address_building_number' => 'Branch building number is required before ETA submission.',
        ];

        return collect($required)
            ->filter(fn (string $message, string $field) => blank($profile->{$field}))
            ->values()
            ->all();
    }

    private function branchAddress(FiscalProfile $profile): array
    {
        return array_filter([
            'country' => $profile->address_country ?: 'EG',
            'governate' => $profile->address_governate,
            'regionCity' => $profile->address_region_city,
            'street' => $profile->address_street,
            'buildingNumber' => $profile->address_building_number,
            'postalCode' => $profile->address_postal_code,
            'floor' => $profile->address_floor,
            'room' => $profile->address_room,
            'landmark' => $profile->address_landmark,
            'additionalInformation' => $profile->address_additional_information,
        ], fn ($value) => filled($value));
    }

    private function buyer($customer, float $totalAmount, float $threshold): array
    {
        return array_filter([
            'type' => 'P',
            'id' => $totalAmount >= $threshold ? (string) ($customer?->national_id ?? '') : null,
            'name' => $customer?->name,
            'mobileNumber' => $customer?->phone,
        ], fn ($value) => filled($value));
    }

    private function paymentMethod(?string $method, ?string $fallback): string
    {
        return match ($method) {
            'card' => 'V',
            'wallet' => 'O',
            'cash' => 'C',
            default => $fallback ?: 'C',
        };
    }

    private function deliveryMode(?string $orderType): string
    {
        return match ($orderType) {
            'delivery' => 'HD',
            'takeaway' => 'TO',
            default => 'FC',
        };
    }

    private function uuidForReceipt(Receipt $receipt, FiscalProfile $profile): string
    {
        return hash('sha256', implode('|', [
            $receipt->receipt_number,
            $receipt->order_id,
            $receipt->created_at?->toISOString(),
            $profile->eta_seller_rin,
            $profile->eta_branch_code,
        ]));
    }
}
