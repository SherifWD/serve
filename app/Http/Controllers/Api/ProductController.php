<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\Inventory\CatalogInventorySync;
use App\Support\KdsStation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $products = $this->branchScoped($request, Product::query())
            ->with('category', 'recipe.ingredients.recipeIngredients', 'branch')
            ->latest()
            ->get();

        return response()->json(['data' => $this->groupForDashboard($products)->values()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'kds_station' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'price' => 'required|numeric',
            'is_available' => 'boolean',
            'min_stock' => 'nullable|integer|min:0',
            'recipe_id' => 'nullable|integer|exists:recipes,id',
            'recipe.name' => 'nullable|string|max:255',
            'recipe.category' => 'nullable|string|max:255',
            'recipe.description' => 'nullable|string',
            'recipe.ingredients' => 'nullable|array',
            'recipe.ingredients.*.name' => 'required_with:recipe.ingredients|string',
            'recipe.ingredients.*.unit' => 'required_with:recipe.ingredients|string',
            'recipe.ingredients.*.quantity' => 'required_with:recipe.ingredients|numeric|min:0.01',
            'image' => 'nullable|image|max:2048',
        ]);

        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
        );
        $this->ensureCategoryCanBeUsed($request, (int) $data['category_id']);
        $category = Category::query()->findOrFail((int) $data['category_id']);

        $recipeId = $data['recipe_id'] ?? null;
        unset($data['recipe_id'], $data['branch_ids'], $data['branch_id'], $data['recipe']);
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['is_available'] = array_key_exists('is_available', $data) ? (int) $request->boolean('is_available') : 1;
        $data['kds_station'] = KdsStation::normalize($data['kds_station'] ?? null);
        $data['branch_group_id'] = (string) Str::uuid();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('product_images', 'public');
        }

        $mapCategoryToBranch = count($branchIds) > 1;
        $products = DB::transaction(function () use ($request, $data, $branchIds, $recipeId, $category, $mapCategoryToBranch) {
            $created = collect();

            foreach ($branchIds as $branchId) {
                $product = Product::create(array_merge($data, [
                    'branch_id' => $branchId,
                    'category_id' => $mapCategoryToBranch
                        ? $this->categoryIdForBranch($category, (int) $branchId)
                        : (int) $category->id,
                    'sku' => $this->uniqueSku(),
                ]));

                if ($request->has('recipe_id')) {
                    $this->assignExistingRecipe($request, $product, $recipeId);
                } elseif ($request->has('recipe')) {
                    $this->syncInlineRecipe($request, $product);
                }

                app(CatalogInventorySync::class)->syncProduct($product);
                $created->push($product);
            }

            return $created;
        });

        return response()->json($this->withBranchGroupMetadata($products->first()->fresh(['category', 'recipe.ingredients', 'branch']), $request), 201);
    }

    public function show(Request $request, $id)
    {
        $product = $this->branchScoped($request, Product::with(['category', 'recipe.ingredients', 'branch']))->findOrFail($id);

        return response()->json($this->withBranchGroupMetadata($product, $request));
    }

    public function update(Request $request, $id)
    {
        $product = $this->branchScoped($request, Product::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'string',
            'category_id' => 'integer|exists:categories,id',
            'kds_station' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'price' => 'numeric',
            'is_available' => 'boolean',
            'min_stock' => 'nullable|integer|min:0',
            'recipe_id' => 'nullable|integer|exists:recipes,id',
            'recipe.name' => 'nullable|string|max:255',
            'recipe.category' => 'nullable|string|max:255',
            'recipe.description' => 'nullable|string',
            'recipe.ingredients' => 'nullable|array',
            'recipe.ingredients.*.name' => 'required_with:recipe.ingredients|string',
            'recipe.ingredients.*.unit' => 'required_with:recipe.ingredients|string',
            'recipe.ingredients.*.quantity' => 'required_with:recipe.ingredients|numeric|min:0.01',
            'image' => 'nullable|image|max:2048',
        ]);

        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : (int) $product->branch_id,
        );

        if (array_key_exists('category_id', $data)) {
            $this->ensureCategoryCanBeUsed($request, (int) $data['category_id']);
        }
        $category = array_key_exists('category_id', $data)
            ? Category::query()->findOrFail((int) $data['category_id'])
            : $product->category;
        if (array_key_exists('is_available', $data)) {
            $data['is_available'] = (int) $request->boolean('is_available');
        }
        if (array_key_exists('min_stock', $data) && $data['min_stock'] === null) {
            $data['min_stock'] = 0;
        }
        if (array_key_exists('kds_station', $data)) {
            $data['kds_station'] = KdsStation::normalize($data['kds_station']);
        }

        $recipeId = $data['recipe_id'] ?? null;
        unset($data['recipe_id'], $data['branch_ids'], $data['branch_id'], $data['recipe']);

        if ($request->hasFile('image')) {
            $oldImage = $product->getRawOriginal('image');
            if ($oldImage && ! Str::startsWith($oldImage, ['http://', 'https://'])) {
                Storage::disk('public')->delete($oldImage);
            }
            $data['image'] = $request->file('image')->store('product_images', 'public');
        }

        $groupId = $product->branch_group_id ?: (string) Str::uuid();

        $mapCategoryToBranch = count($branchIds) > 1;
        DB::transaction(function () use ($request, $product, $data, $branchIds, $recipeId, $groupId, $category, $mapCategoryToBranch) {
            if (! $product->branch_group_id) {
                $product->branch_group_id = $groupId;
                $product->save();
            }

            foreach ($branchIds as $index => $branchId) {
                $target = Product::query()
                    ->where('branch_group_id', $groupId)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $target) {
                    $target = $index === 0 ? $product : $product->replicate(['sku']);
                    $target->branch_id = $branchId;
                    $target->branch_group_id = $groupId;
                    if (! $target->exists) {
                        $target->sku = $this->uniqueSku();
                    }
                }

                $targetData = $data;
                if ($category) {
                    $targetData['category_id'] = $this->categoryIdForBranch($category, (int) $branchId);
                    if (! $mapCategoryToBranch) {
                        $targetData['category_id'] = (int) $category->id;
                    }
                }

                $target->fill($targetData);
                if (! $target->sku) {
                    $target->sku = $this->uniqueSku();
                }
                $target->branch_group_id = $groupId;
                $target->branch_id = $branchId;
                $target->save();
                app(CatalogInventorySync::class)->syncProduct($target);

                if ($request->has('recipe_id')) {
                    $this->assignExistingRecipe($request, $target, $recipeId);
                } elseif ($request->has('recipe')) {
                    $this->syncInlineRecipe($request, $target);
                }
            }
        });

        return response()->json($this->withBranchGroupMetadata($product->fresh(['category', 'recipe.ingredients', 'branch']), $request));
    }

    public function destroy(Request $request, $id)
    {
        $product = $this->branchScoped($request, Product::query())->findOrFail($id);

        if ($product->recipe) {
            $product->recipe->ingredients()->detach();
            $product->recipe->delete();
        }

        $product->delete();

        return response()->json(['message' => 'Product and associated recipe deleted successfully']);
    }

    private function ensureCategoryCanBeUsed(Request $request, int $categoryId): void
    {
        $query = Category::query()->whereKey($categoryId);
        $user = $request->user();

        if (! $user?->isPlatformAdmin()) {
            if ($user?->branch_id) {
                $query->where(function ($scope) use ($user) {
                    $scope->whereNull('branch_id')
                        ->orWhere('branch_id', $user->branch_id);
                });
            } elseif ($user?->restaurant_id) {
                $query->where(function ($scope) use ($user) {
                    $scope->whereNull('branch_id')
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        abort_unless($query->exists(), 422, 'Product category must be available to the selected restaurant.');
    }

    private function assignExistingRecipe(Request $request, Product $product, ?int $recipeId): void
    {
        $product->loadMissing('recipe');

        if ($recipeId === null) {
            Recipe::query()
                ->where('product_id', $product->id)
                ->update(['product_id' => null]);

            return;
        }

        $recipe = Recipe::query()
            ->with('ingredients')
            ->findOrFail($recipeId);

        if ($recipe->branch_id !== null) {
            $this->ensureBranchAccess($request, (int) $recipe->branch_id);
        }

        Recipe::query()
            ->where('product_id', $product->id)
            ->whereKeyNot($recipe->id)
            ->update(['product_id' => null]);

        if ((int) $recipe->branch_id === (int) $product->branch_id && (! $recipe->product_id || (int) $recipe->product_id === (int) $product->id)) {
            $recipe->update([
                'product_id' => $product->id,
                'branch_id' => $product->branch_id,
                'branch_group_id' => $recipe->branch_group_id ?: $product->branch_group_id,
            ]);

            return;
        }

        $copy = Recipe::create([
            'product_id' => $product->id,
            'branch_id' => $product->branch_id,
            'branch_group_id' => $recipe->branch_group_id ?: $product->branch_group_id,
            'name' => $recipe->name,
            'category' => $recipe->category,
            'description' => $recipe->description,
        ]);

        foreach ($recipe->ingredients as $ingredient) {
            $copy->ingredients()->attach($ingredient->id, [
                'quantity' => $ingredient->pivot->quantity,
            ]);
        }
    }

    private function syncInlineRecipe(Request $request, Product $product): void
    {
        $ingredients = $request->input('recipe.ingredients', []);
        $seen = [];

        foreach ($ingredients as $ingredientData) {
            $key = mb_strtolower(trim((string) ($ingredientData['name'] ?? '')));
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'recipe.ingredients' => 'Recipe ingredients cannot contain duplicates.',
                ]);
            }
            $seen[$key] = true;
        }

        $recipe = Recipe::updateOrCreate(
            ['product_id' => $product->id],
            [
                'branch_id' => $product->branch_id,
                'branch_group_id' => $product->branch_group_id,
                'name' => $this->normalizedRecipeName($request, $product),
                'category' => $this->normalizedOptionalString($request->input('recipe.category')),
                'description' => $request->input('recipe.description'),
            ]
        );

        $recipe->ingredients()->detach();

        foreach ($ingredients as $ingredientData) {
            $name = trim((string) ($ingredientData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $ingredient = Ingredient::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            $ingredient ??= Ingredient::create([
                'name' => $name,
                'unit' => $ingredientData['unit'],
                'stock' => 0,
            ]);

            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => $ingredientData['quantity'],
            ]);
        }
    }

    private function normalizedRecipeName(Request $request, Product $product): string
    {
        return $this->normalizedOptionalString($request->input('recipe.name'))
            ?: "{$product->name} recipe";
    }

    private function normalizedOptionalString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function uniqueSku(): string
    {
        do {
            $sku = strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function categoryIdForBranch(Category $category, int $branchId): int
    {
        if ($category->branch_id === null || (int) $category->branch_id === $branchId) {
            return (int) $category->id;
        }

        $groupId = $category->branch_group_id;
        if (! $groupId) {
            $groupId = (string) Str::uuid();
            $category->branch_group_id = $groupId;
            $category->save();
        }

        $sibling = Category::query()
            ->where('branch_id', $branchId)
            ->where(function ($query) use ($category, $groupId) {
                $query->where('branch_group_id', $groupId)
                    ->orWhereRaw('LOWER(name) = ?', [mb_strtolower(trim($category->name))]);
            })
            ->first();

        if ($sibling) {
            if (! $sibling->branch_group_id) {
                $sibling->branch_group_id = $groupId;
                $sibling->save();
            }

            return (int) $sibling->id;
        }

        $copy = Category::create([
            'name' => $category->name,
            'branch_id' => $branchId,
            'kds_station' => $category->kds_station,
            'branch_group_id' => $groupId,
        ]);

        return (int) $copy->id;
    }

    private function groupForDashboard(Collection $products): Collection
    {
        return $products
            ->groupBy(fn (Product $product) => $product->branch_group_id ?: 'product:'.$product->id)
            ->map(function (Collection $group) {
                $product = $group->first();
                $branches = $group->pluck('branch')->filter()->unique('id')->values();

                $product->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
                $product->setRelation('branches', $branches);

                return $product;
            });
    }

    private function withBranchGroupMetadata(Product $product, Request $request): Product
    {
        if (! $product->branch_group_id) {
            $branches = collect([$product->branch])->filter()->values();
            $product->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->all());
            $product->setRelation('branches', $branches);

            return $product;
        }

        $siblings = Product::query()
            ->where('branch_group_id', $product->branch_group_id)
            ->with('branch')
            ->when($this->accessibleBranchIds($request) !== null, function ($query) use ($request) {
                $query->whereIn('branch_id', $this->accessibleBranchIds($request));
            })
            ->get();
        $branches = $siblings->pluck('branch')->filter()->unique('id')->values();

        $product->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
        $product->setRelation('branches', $branches);

        return $product;
    }
}
