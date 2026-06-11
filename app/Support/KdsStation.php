<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;

class KdsStation
{
    public const STATIONS = [
        'general',
        'barista',
        'grill',
        'cold',
        'dessert',
    ];

    public static function normalize(?string $value): ?string
    {
        $station = str($value ?? '')->trim()->lower()->replace([' ', '-'], '_')->toString();

        if ($station === '' || $station === 'all') {
            return null;
        }

        return in_array($station, self::STATIONS, true) ? $station : 'general';
    }

    public static function label(?string $station): string
    {
        return match (self::normalize($station) ?? 'general') {
            'barista' => 'Barista',
            'grill' => 'Grill',
            'cold' => 'Cold',
            'dessert' => 'Dessert',
            default => 'General',
        };
    }

    public static function forProduct(?Product $product): string
    {
        if (!$product) {
            return 'general';
        }

        $explicit = self::normalize($product->kds_station ?? null);
        if ($explicit) {
            return $explicit;
        }

        $category = $product->relationLoaded('category') ? $product->category : null;
        if ($category instanceof Category) {
            $categoryStation = self::normalize($category->kds_station ?? null);
            if ($categoryStation) {
                return $categoryStation;
            }
        }

        return self::inferFromNames($product->name ?? '', $category?->name ?? '');
    }

    public static function inferFromNames(string $productName, string $categoryName = ''): string
    {
        $text = str($categoryName.' '.$productName)->lower()->toString();

        if (preg_match('/coffee|espresso|latte|cappuccino|tea|juice|smoothie|drink|barista|beverage/', $text)) {
            return 'barista';
        }

        if (preg_match('/grill|burger|steak|kebab|bbq|broil|roast/', $text)) {
            return 'grill';
        }

        if (preg_match('/salad|cold|sushi|sandwich|mezze|dip/', $text)) {
            return 'cold';
        }

        if (preg_match('/dessert|cake|pastry|ice cream|sweet|pudding/', $text)) {
            return 'dessert';
        }

        return 'general';
    }
}
