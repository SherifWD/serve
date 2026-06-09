<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientBranch;
use App\Models\InventoryItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\Inventory\CatalogInventorySync;
use App\Support\IngredientUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IngredientController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $ingredients = $this->ingredientQuery($request)->with([
            'ingredientBranches' => fn ($query) => $this->scopeIngredientBranches($request, $query),
            'ingredientBranches.branch:id,name',
            'recipes:id,name,description,category',
        ])->get();

        return response()->json($ingredients);
    }

    public function show(Request $request, $id)
    {
        $ingredient = $this->ingredientQuery($request)->with([
            'ingredientBranches' => fn ($query) => $this->scopeIngredientBranches($request, $query),
            'ingredientBranches.branch:id,name',
            'recipes:id,name,description,category',
        ])->findOrFail($id);

        return response()->json($ingredient);
    }

    public function store(Request $request)
    {
        $data = $this->validatedIngredientData($request);
        $name = trim($data['name']);
        $minimumUnit = IngredientUnits::minimumUnit($data['unit']);
        $stock = $this->convertQuantity((float) ($data['stock'] ?? 0), $data['stock_unit'] ?? $data['unit'], $minimumUnit);
        $minStock = $this->convertQuantity((float) ($data['min_stock'] ?? 0), $data['min_stock_unit'] ?? $minimumUnit, $minimumUnit);

        $this->ensureUniqueIngredientName($name);
        $branchStocks = $this->normalizedBranchStocks($request, $data, $minimumUnit, $stock);
        $recipeRows = $this->normalizedRecipeRows($request, $data, $minimumUnit);
        $this->ensureBranchStockFitsGlobal($stock, $branchStocks);

        $category = $this->normalizedOptionalString($data['category'] ?? null);

        $ingredient = DB::transaction(function () use ($request, $name, $category, $minimumUnit, $stock, $minStock, $branchStocks, $recipeRows) {
            $ingredient = Ingredient::create([
                'name' => $name,
                'category' => $category,
                'unit' => $minimumUnit,
                'stock' => $stock,
                'min_stock' => $minStock,
            ]);

            $this->syncBranchStocks($request, $ingredient, $branchStocks);
            $this->syncRecipeRows($request, $ingredient, $recipeRows);

            return $ingredient;
        });

        return response()->json($ingredient->load(['ingredientBranches.branch', 'recipes:id,name,description,category']), 201);
    }

    public function update(Request $request, $id)
    {
        $ingredient = $this->ingredientQuery($request)->findOrFail($id);
        $data = $this->validatedIngredientData($request);
        $name = trim($data['name']);
        $minimumUnit = IngredientUnits::minimumUnit($data['unit']);
        $stock = $this->convertQuantity((float) ($data['stock'] ?? 0), $data['stock_unit'] ?? $data['unit'], $minimumUnit);
        $minStock = $this->convertQuantity((float) ($data['min_stock'] ?? 0), $data['min_stock_unit'] ?? $minimumUnit, $minimumUnit);

        $category = $this->normalizedOptionalString($data['category'] ?? null);

        $this->ensureUniqueIngredientName($name, (int) $ingredient->id);
        $branchStocks = $this->normalizedBranchStocks($request, $data, $minimumUnit, $stock);
        $recipeRows = $this->normalizedRecipeRows($request, $data, $minimumUnit);
        $this->ensureBranchStockFitsGlobal($stock, $branchStocks);

        DB::transaction(function () use ($request, $ingredient, $name, $category, $minimumUnit, $stock, $minStock, $branchStocks, $recipeRows) {
            $ingredient->update([
                'name' => $name,
                'category' => $category,
                'unit' => $minimumUnit,
                'stock' => $stock,
                'min_stock' => $minStock,
            ]);

            $this->syncBranchStocks($request, $ingredient, $branchStocks);
            $this->syncRecipeRows($request, $ingredient, $recipeRows);
        });

        return response()->json($ingredient->load(['ingredientBranches.branch', 'recipes:id,name,description,category']));
    }

    public function destroy(Request $request, $id)
    {
        $ingredient = $this->ingredientQuery($request)->findOrFail($id);
        $ingredient->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function updateStock(Request $request)
    {
        $data = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'branch_id' => 'required|exists:branches,id',
            'stock' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);
        $this->ensureBranchAccess($request, (int) $data['branch_id']);

        $ingredient = $this->ingredientQuery($request)->findOrFail($data['ingredient_id']);
        $addition = $this->convertQuantity((float) $data['stock'], $data['unit'] ?? $ingredient->unit, $ingredient->unit);

        $branchStock = DB::transaction(function () use ($ingredient, $data, $addition) {
            $lockedIngredient = Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
            $currentBranchStock = IngredientBranch::query()
                ->where('ingredient_id', $lockedIngredient->id)
                ->where('branch_id', $data['branch_id'])
                ->lockForUpdate()
                ->first();

            $nextStock = round((float) ($currentBranchStock?->stock ?? 0) + $addition, 3);
            $branchStock = IngredientBranch::updateOrCreate([
                'ingredient_id' => $lockedIngredient->id,
                'branch_id' => $data['branch_id'],
            ], [
                'stock' => $nextStock,
            ]);

            $otherBranchesTotal = IngredientBranch::where('ingredient_id', $lockedIngredient->id)
                ->where('branch_id', '!=', $data['branch_id'])
                ->sum('stock');
            $totalAssigned = round((float) $otherBranchesTotal + $nextStock, 3);

            $lockedIngredient->stock = round(max((float) $lockedIngredient->stock + $addition, $totalAssigned), 3);
            $lockedIngredient->save();

            app(CatalogInventorySync::class)->syncIngredientBranch($lockedIngredient, (int) $data['branch_id'], $nextStock);

            return $branchStock;
        });

        return response()->json($branchStock->load('ingredient', 'branch'));
    }

    private function validatedIngredientData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'unit' => 'required|string|max:50',
            'stock' => 'nullable|numeric|min:0',
            'stock_unit' => 'nullable|string|max:50',
            'min_stock' => 'nullable|numeric|min:0',
            'min_stock_unit' => 'nullable|string|max:50',
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'distribute_equally' => 'boolean',
            'ingredient_branches' => 'array',
            'ingredient_branches.*.branch_id' => 'required|integer|exists:branches,id',
            'ingredient_branches.*.stock' => 'required|numeric|min:0',
            'ingredient_branches.*.unit' => 'nullable|string|max:50',
            'recipe_ingredients' => 'array',
            'recipe_ingredients.*.recipe_id' => 'required|integer|exists:recipes,id|distinct',
            'recipe_ingredients.*.quantity' => 'required|numeric|min:0',
            'recipe_ingredients.*.unit' => 'nullable|string|max:50',
        ]);
    }

    private function ensureUniqueIngredientName(string $name, ?int $ignoreId = null): void
    {
        $query = Ingredient::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Ingredient names must be unique.',
            ]);
        }
    }

    private function normalizedOptionalString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizedBranchStocks(Request $request, array $data, string $minimumUnit, float $stock): array
    {
        if (! empty($data['distribute_equally'])) {
            $branchIds = $this->restaurantBranchIdsForWrite(
                $request,
                $data['branch_ids'] ?? null,
                isset($data['restaurant_id']) ? (int) $data['restaurant_id'] : null,
            );

            if (! $branchIds) {
                throw ValidationException::withMessages([
                    'branch_ids' => 'At least one branch is required for equal distribution.',
                ]);
            }

            return $this->equalDistributionRows($branchIds, $stock);
        }

        $rows = [];
        foreach ($data['ingredient_branches'] ?? [] as $row) {
            $branchId = $this->branchIdForWrite($request, (int) $row['branch_id']);
            $rows[$branchId] = [
                'branch_id' => $branchId,
                'stock' => $this->convertQuantity((float) $row['stock'], $row['unit'] ?? $minimumUnit, $minimumUnit),
            ];
        }

        foreach ($data['branch_ids'] ?? [] as $branchId) {
            $branchId = $this->branchIdForWrite($request, (int) $branchId);
            $rows[$branchId] ??= [
                'branch_id' => $branchId,
                'stock' => 0,
            ];
        }

        return array_values($rows);
    }

    private function equalDistributionRows(array $branchIds, float $stock): array
    {
        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));
        $count = count($branchIds);
        if ($count === 0) {
            return [];
        }

        $perBranch = floor(($stock / $count) * 100) / 100;
        $rows = [];
        $assigned = 0.0;

        foreach ($branchIds as $index => $branchId) {
            $amount = $index === $count - 1
                ? round($stock - $assigned, 2)
                : round($perBranch, 2);
            $assigned += $amount;
            $rows[] = [
                'branch_id' => $branchId,
                'stock' => $amount,
            ];
        }

        return $rows;
    }

    private function normalizedRecipeRows(Request $request, array $data, string $minimumUnit): array
    {
        $recipeIds = collect($data['recipe_ingredients'] ?? [])
            ->pluck('recipe_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recipeIds->isNotEmpty()) {
            $allowedCount = $this->branchScoped($request, Recipe::query())
                ->whereIn('id', $recipeIds->all())
                ->count();

            if ($allowedCount !== $recipeIds->count()) {
                throw ValidationException::withMessages([
                    'recipe_ingredients' => 'Recipe assignments must stay inside the selected restaurant.',
                ]);
            }
        }

        $rows = [];
        foreach ($data['recipe_ingredients'] ?? [] as $row) {
            $recipeId = (int) $row['recipe_id'];
            $rows[$recipeId] = [
                'recipe_id' => $recipeId,
                'quantity' => $this->convertQuantity((float) $row['quantity'], $row['unit'] ?? $minimumUnit, $minimumUnit),
            ];
        }

        return array_values($rows);
    }

    private function syncBranchStocks(Request $request, Ingredient $ingredient, array $branchStocks): void
    {
        $inventorySync = app(CatalogInventorySync::class);
        $branchIds = collect($branchStocks)->pluck('branch_id')->map(fn ($id) => (int) $id)->all();
        $deleteQuery = IngredientBranch::query()->where('ingredient_id', $ingredient->id);
        $accessibleBranchIds = $this->accessibleBranchIds($request);

        if ($accessibleBranchIds !== null) {
            $deleteQuery->whereIn('branch_id', $accessibleBranchIds);
        }

        if ($branchIds) {
            $deleteQuery->whereNotIn('branch_id', $branchIds);
        }

        $deletedBranchIds = (clone $deleteQuery)
            ->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $deleteQuery->delete();
        if ($deletedBranchIds) {
            InventoryItem::query()
                ->where('ingredient_id', $ingredient->id)
                ->whereIn('branch_id', $deletedBranchIds)
                ->delete();
        }

        foreach ($branchStocks as $stock) {
            IngredientBranch::updateOrCreate([
                'ingredient_id' => $ingredient->id,
                'branch_id' => $stock['branch_id'],
            ], [
                'stock' => $stock['stock'],
            ]);

            $inventorySync->syncIngredientBranch($ingredient, (int) $stock['branch_id'], (float) $stock['stock']);
        }
    }

    private function syncRecipeRows(Request $request, Ingredient $ingredient, array $recipeRows): void
    {
        $accessibleRecipeIds = $this->branchScoped($request, Recipe::query())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($accessibleRecipeIds === []) {
            return;
        }

        RecipeIngredient::where('ingredient_id', $ingredient->id)
            ->whereIn('recipe_id', $accessibleRecipeIds)
            ->delete();

        foreach ($recipeRows as $row) {
            RecipeIngredient::create([
                'ingredient_id' => $ingredient->id,
                'recipe_id' => $row['recipe_id'],
                'quantity' => $row['quantity'],
            ]);
        }
    }

    private function ensureBranchStockFitsGlobal(float $stock, array $branchStocks): void
    {
        $branchTotal = collect($branchStocks)->sum(fn ($row) => (float) $row['stock']);

        if ($branchTotal > $stock) {
            throw ValidationException::withMessages([
                'ingredient_branches' => "Total branch stock ({$branchTotal}) cannot exceed the global stock ({$stock}).",
            ]);
        }
    }

    private function convertQuantity(float $quantity, string $fromUnit, string $minimumUnit): float
    {
        return IngredientUnits::toMinimumUnit($quantity, $fromUnit, $minimumUnit);
    }

    private function ingredientQuery(Request $request)
    {
        $query = Ingredient::query();
        $branchIds = $this->accessibleBranchIds($request);

        if ($branchIds !== null) {
            return $query->whereHas('ingredientBranches', fn ($stockQuery) => $stockQuery->whereIn('branch_id', $branchIds));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('ingredientBranches', fn ($stockQuery) => $stockQuery->where('branch_id', $request->integer('branch_id')));
        }

        return $query;
    }

    private function scopeIngredientBranches(Request $request, $query)
    {
        $branchIds = $this->accessibleBranchIds($request);

        if ($branchIds !== null) {
            return $query->whereIn('branch_id', $branchIds);
        }

        if ($request->filled('branch_id')) {
            return $query->where('branch_id', $request->integer('branch_id'));
        }

        return $query;
    }
}
