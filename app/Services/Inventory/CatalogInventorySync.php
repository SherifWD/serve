<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\Product;

class CatalogInventorySync
{
    public function syncIngredientBranch(Ingredient $ingredient, int $branchId, float $quantity): InventoryItem
    {
        return InventoryItem::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'ingredient_id' => $ingredient->id,
            ],
            [
                'name' => $ingredient->name,
                'unit' => $ingredient->unit,
                'quantity' => round($quantity, 3),
                'min_stock' => (float) ($ingredient->min_stock ?? 0),
                'product_id' => null,
            ],
        );
    }

    public function syncProduct(Product $product): InventoryItem
    {
        return InventoryItem::query()->updateOrCreate(
            [
                'branch_id' => $product->branch_id,
                'product_id' => $product->id,
            ],
            [
                'name' => $product->name,
                'unit' => 'unit',
                'quantity' => (float) ($product->stock ?? 0),
                'min_stock' => (float) ($product->min_stock ?? 0),
                'ingredient_id' => null,
            ],
        );
    }
}
