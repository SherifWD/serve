<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class IngredientUnits
{
    /** @var array<string,array{dimension:string,base:string,factor:float}> */
    private const UNITS = [
        'mg' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 0.001],
        'milligram' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 0.001],
        'milligrams' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 0.001],
        'g' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1],
        'gram' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1],
        'grams' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1],
        'kg' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1000],
        'kilo' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1000],
        'kilos' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1000],
        'kilogram' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1000],
        'kilograms' => ['dimension' => 'weight', 'base' => 'g', 'factor' => 1000],

        'ml' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1],
        'milliliter' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1],
        'milliliters' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1],
        'millilitre' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1],
        'millilitres' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1],
        'l' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1000],
        'liter' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1000],
        'liters' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1000],
        'litre' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1000],
        'litres' => ['dimension' => 'volume', 'base' => 'ml', 'factor' => 1000],

        'pc' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
        'pcs' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
        'piece' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
        'pieces' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
        'unit' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
        'units' => ['dimension' => 'count', 'base' => 'pc', 'factor' => 1],
    ];

    public static function normalize(string $unit): string
    {
        return mb_strtolower(trim($unit));
    }

    public static function minimumUnit(string $unit): string
    {
        $normalized = self::normalize($unit);

        return self::UNITS[$normalized]['base'] ?? $normalized;
    }

    public static function toMinimumUnit(float $quantity, string $fromUnit, string $minimumUnit): float
    {
        $from = self::normalize($fromUnit);
        $to = self::normalize($minimumUnit);

        if ($from === $to) {
            return round($quantity, 3);
        }

        $fromMeta = self::UNITS[$from] ?? null;
        $toMeta = self::UNITS[$to] ?? null;

        if (! $fromMeta && ! $toMeta) {
            throw ValidationException::withMessages([
                'unit' => "Unit '{$fromUnit}' cannot be converted to '{$minimumUnit}'.",
            ]);
        }

        if (! $fromMeta || ! $toMeta || $fromMeta['dimension'] !== $toMeta['dimension']) {
            throw ValidationException::withMessages([
                'unit' => "Unit '{$fromUnit}' is not compatible with '{$minimumUnit}'.",
            ]);
        }

        $baseQuantity = $quantity * $fromMeta['factor'];

        return round($baseQuantity / $toMeta['factor'], 3);
    }
}
