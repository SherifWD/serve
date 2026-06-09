<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    public function isAvailable(Product $product, int $branchId, int $quantity = 1): bool
    {
        return $this->availability($product, $branchId, $quantity, false)['available'];
    }

    public function consume(Product $product, int $branchId, int $quantity): void
    {
        $availability = $this->availability($product, $branchId, $quantity, true);

        if (!$availability['available']) {
            throw ValidationException::withMessages([
                'items' => $availability['messages'],
            ]);
        }

        foreach ($availability['sources'] as $source) {
            $this->adjustSource($source, -1);
        }
    }

    public function restore(Product $product, int $branchId, int $quantity): void
    {
        $availability = $this->availability($product, $branchId, $quantity, true);

        foreach ($availability['sources'] as $source) {
            $this->adjustSource($source, 1);
        }
    }

    private function availability(Product $product, int $branchId, int $quantity, bool $lock): array
    {
        $quantity = max(1, $quantity);
        $product->loadMissing('recipe.ingredients');

        $sources = [];
        $messages = [];
        $hasRecipeIngredients = $product->recipe && $product->recipe->ingredients->isNotEmpty();

        if ($hasRecipeIngredients) {
            foreach ($product->recipe->ingredients as $ingredient) {
                $required = (float) $ingredient->pivot->quantity * $quantity;
                $ingredientSources = $this->ingredientSources($ingredient, $branchId, $required, $lock);
                $sources = array_merge($sources, $ingredientSources);
            }
        } else {
            $productInventory = $this->productInventoryItem($product, $branchId, $lock);
            if ($productInventory) {
                $sources[] = [
                    'type' => 'inventory_item',
                    'model' => $productInventory,
                    'label' => $product->name,
                    'required' => (float) $quantity,
                    'available' => (float) $productInventory->quantity,
                ];
            }
        }

        if (!$hasRecipeIngredients && Schema::hasColumn('products', 'stock')) {
            $trackedProduct = $lock
                ? Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail()
                : $product;

            $sources[] = [
                'type' => 'product_stock',
                'model' => $trackedProduct,
                'label' => $product->name,
                'required' => (float) $quantity,
                'available' => (float) ($trackedProduct->stock ?? 0),
            ];
        }

        foreach ($sources as $source) {
            if ($source['available'] < $source['required']) {
                $messages[] = sprintf(
                    '%s has %.3f available but %.3f is required.',
                    $source['label'],
                    $source['available'],
                    $source['required'],
                );
            }
        }

        return [
            'available' => $messages === [],
            'messages' => $messages,
            'sources' => $sources,
        ];
    }

    private function ingredientSources(Ingredient $ingredient, int $branchId, float $required, bool $lock): array
    {
        $sources = [];
        $branchStockQuery = DB::table('ingredient_branches')
            ->where('ingredient_id', $ingredient->id)
            ->where('branch_id', $branchId);

        if ($lock) {
            $branchStockQuery->lockForUpdate();
        }

        $branchStock = $branchStockQuery->first();
        if ($branchStock) {
            $sources[] = [
                'type' => 'ingredient_branch',
                'id' => $branchStock->id,
                'label' => $ingredient->name,
                'required' => $required,
                'available' => (float) $branchStock->stock,
            ];
        }

        $inventoryItem = $this->ingredientInventoryItem($ingredient, $branchId, $lock);
        if ($inventoryItem) {
            $sources[] = [
                'type' => 'inventory_item',
                'model' => $inventoryItem,
                'label' => $ingredient->name,
                'required' => $required,
                'available' => (float) $inventoryItem->quantity,
            ];
        }

        if (!$branchStock && !$inventoryItem) {
            $trackedIngredient = $lock
                ? Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail()
                : $ingredient;

            $sources[] = [
                'type' => 'ingredient_stock',
                'model' => $trackedIngredient,
                'label' => $ingredient->name,
                'required' => $required,
                'available' => (float) ($trackedIngredient->stock ?? 0),
            ];
        }

        return $sources;
    }

    private function productInventoryItem(Product $product, int $branchId, bool $lock): ?InventoryItem
    {
        $query = InventoryItem::query()
            ->where('branch_id', $branchId)
            ->where(function ($scope) use ($product) {
                $scope->where('product_id', $product->id)
                    ->orWhere(function ($nameScope) use ($product) {
                        $nameScope->whereNull('product_id')
                            ->whereNull('ingredient_id')
                            ->where('name', $product->name);
                    });
            })
            ->orderByRaw('CASE WHEN product_id = ? THEN 0 ELSE 1 END', [$product->id]);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function ingredientInventoryItem(Ingredient $ingredient, int $branchId, bool $lock): ?InventoryItem
    {
        $query = InventoryItem::query()
            ->where('branch_id', $branchId)
            ->where(function ($scope) use ($ingredient) {
                $scope->where('ingredient_id', $ingredient->id)
                    ->orWhere(function ($nameScope) use ($ingredient) {
                        $nameScope->whereNull('ingredient_id')
                            ->whereNull('product_id')
                            ->where('name', $ingredient->name);
                    });
            })
            ->orderByRaw('CASE WHEN ingredient_id = ? THEN 0 ELSE 1 END', [$ingredient->id]);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function adjustSource(array $source, int $direction): void
    {
        $quantity = $source['required'] * $direction;

        if ($source['type'] === 'ingredient_branch') {
            $after = max(0, $source['available'] + $quantity);

            DB::table('ingredient_branches')
                ->where('id', $source['id'])
                ->update([
                    'stock' => round($after, 3),
                    'updated_at' => now(),
                ]);

            return;
        }

        $model = $source['model'];

        if ($source['type'] === 'inventory_item') {
            $model->quantity = round(max(0, (float) $model->quantity + $quantity), 3);
        } elseif ($source['type'] === 'product_stock') {
            $model->stock = max(0, (int) $model->stock + (int) $quantity);
        } elseif ($source['type'] === 'ingredient_stock') {
            $model->stock = round(max(0, (float) $model->stock + $quantity), 3);
        }

        $model->save();
    }
}
