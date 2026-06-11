<?php

namespace App\Support;

use App\Models\Branch;

class BranchOperationProfile
{
    public const MODES = [
        'drinks_only_cafe',
        'cafe_with_barista',
        'small_restaurant',
        'big_restaurant',
        'custom',
    ];

    public static function defaultsForMode(string $mode): array
    {
        return match ($mode) {
            'drinks_only_cafe' => [
                'uses_tables' => false,
                'cashier_first' => true,
                'kds_enabled' => false,
                'waiter_table_ownership' => false,
                'show_waiter_names' => false,
                'table_transfer' => false,
                'split_bills' => false,
                'multi_kds_stations' => false,
                'station_count' => 1,
                'customer_ordering' => true,
            ],
            'cafe_with_barista' => [
                'uses_tables' => false,
                'cashier_first' => true,
                'kds_enabled' => true,
                'waiter_table_ownership' => false,
                'show_waiter_names' => false,
                'table_transfer' => false,
                'split_bills' => true,
                'multi_kds_stations' => false,
                'station_count' => 1,
                'customer_ordering' => true,
            ],
            'big_restaurant' => [
                'uses_tables' => true,
                'cashier_first' => false,
                'kds_enabled' => true,
                'waiter_table_ownership' => true,
                'show_waiter_names' => true,
                'table_transfer' => true,
                'split_bills' => true,
                'multi_kds_stations' => true,
                'station_count' => 3,
                'customer_ordering' => true,
            ],
            'custom' => [
                'uses_tables' => true,
                'cashier_first' => false,
                'kds_enabled' => true,
                'waiter_table_ownership' => true,
                'show_waiter_names' => true,
                'table_transfer' => true,
                'split_bills' => true,
                'multi_kds_stations' => false,
                'station_count' => 1,
                'customer_ordering' => true,
            ],
            default => [
                'uses_tables' => true,
                'cashier_first' => false,
                'kds_enabled' => true,
                'waiter_table_ownership' => true,
                'show_waiter_names' => false,
                'table_transfer' => true,
                'split_bills' => true,
                'multi_kds_stations' => false,
                'station_count' => 1,
                'customer_ordering' => true,
            ],
        };
    }

    public static function defaultModeForBranch(Branch $branch): string
    {
        return $branch->restaurant?->kind === 'cafe'
            ? 'cafe_with_barista'
            : 'small_restaurant';
    }

    public static function labelForMode(string $mode): string
    {
        return match ($mode) {
            'drinks_only_cafe' => 'Drinks-only cafe',
            'cafe_with_barista' => 'Cafe with barista',
            'small_restaurant' => 'Small restaurant',
            'big_restaurant' => 'Big restaurant',
            'custom' => 'Custom operation',
            default => 'Small restaurant',
        };
    }

    public static function forBranch(Branch $branch): array
    {
        $mode = $branch->operation_mode ?: self::defaultModeForBranch($branch);
        if (! in_array($mode, self::MODES, true)) {
            $mode = self::defaultModeForBranch($branch);
        }

        $stored = is_array($branch->operation_features)
            ? $branch->operation_features
            : [];
        $features = array_merge(self::defaultsForMode($mode), $stored);
        $stationCount = max(1, min(8, (int) ($features['station_count'] ?? 1)));

        return [
            'mode' => $mode,
            'label' => $branch->operation_label ?: self::labelForMode($mode),
            'features' => [
                'uses_tables' => (bool) ($features['uses_tables'] ?? true),
                'cashier_first' => (bool) ($features['cashier_first'] ?? false),
                'kds_enabled' => (bool) ($features['kds_enabled'] ?? true),
                'waiter_table_ownership' => (bool) ($features['waiter_table_ownership'] ?? true),
                'show_waiter_names' => (bool) ($features['show_waiter_names'] ?? false),
                'table_transfer' => (bool) ($features['table_transfer'] ?? true),
                'split_bills' => (bool) ($features['split_bills'] ?? true),
                'multi_kds_stations' => (bool) ($features['multi_kds_stations'] ?? false),
                'station_count' => $stationCount,
                'customer_ordering' => (bool) ($features['customer_ordering'] ?? true),
            ],
        ];
    }
}
