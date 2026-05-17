<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use EnforcesTenantAccess;

    // Display a listing of products
    public function index(Request $request)
    {
        // Optionally: filter by branch, pagination
        $query = $this->branchScoped($request, Product::query());

        return response()->json($query->with('category', 'recipe.ingredients.recipeIngredients', 'branch')->latest()->paginate(20));
    }

    // Store a new product along with a recipe and ingredients
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'price' => 'required|numeric',
            'is_available' => 'boolean',
            'min_stock' => 'nullable|integer|min:0',
            'recipe_id' => 'nullable|integer|exists:recipes,id',
            'image' => 'nullable|image|max:2048',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $this->ensureCategoryCanBeUsed($request, (int) $data['category_id']);
        $recipeId = $data['recipe_id'] ?? null;
        unset($data['recipe_id']);
        $data['min_stock'] = $data['min_stock'] ?? 0;

        // Generate unique SKU
        do {
            $sku = strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());
        $data['sku'] = $sku;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('product_images', 'public');
            $data['image'] = $path;
        }
        $product = Product::create($data);

        if ($request->has('recipe_id')) {
            $this->assignExistingRecipe($request, $product, $recipeId);
        }

        // If the product includes a recipe, store it along with ingredients
        if (! $request->has('recipe_id') && $request->has('recipe')) {
            // Create the recipe associated with the product
            $recipe = Recipe::create([
                'product_id' => $product->id,
                'branch_id' => $product->branch_id,
                'description' => $request->recipe['description'],
            ]);

            // Loop through ingredients and add them
            foreach ($request->recipe['ingredients'] as $ingredientData) {
                $ingredient = Ingredient::firstOrCreate([
                    'name' => $ingredientData['name'],
                    'unit' => $ingredientData['unit'],
                ]);

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $ingredientData['quantity'],
                ]);
            }
        }

        return response()->json($product->fresh(['category', 'recipe.ingredients', 'branch']), 201);
    }

    // Display the specified product along with its recipe and ingredients
    public function show(Request $request, $id)
    {
        $product = $this->branchScoped($request, Product::with(['category', 'recipe.ingredients']))->findOrFail($id);

        return response()->json($product);
    }

    // Update an existing product and its recipe and ingredients
    public function update(Request $request, $id)
    {
        // Validate product data
        $product = $this->branchScoped($request, Product::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'string',
            'category_id' => 'integer|exists:categories,id',
            'branch_id' => 'integer|exists:branches,id',
            'price' => 'numeric',
            'is_available' => 'boolean',
            'min_stock' => 'nullable|integer|min:0',
            'recipe_id' => 'nullable|integer|exists:recipes,id',
            'image' => 'nullable|image|max:2048',
        ]);
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        }
        if (array_key_exists('category_id', $data)) {
            $this->ensureCategoryCanBeUsed($request, (int) $data['category_id']);
        }
        if (array_key_exists('is_available', $data)) {
            $data['is_available'] = (int) $request->boolean('is_available');
        }
        if (array_key_exists('min_stock', $data) && $data['min_stock'] === null) {
            $data['min_stock'] = 0;
        }
        $recipeId = $data['recipe_id'] ?? null;
        unset($data['recipe_id']);
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old if exists
            $oldImage = $product->getRawOriginal('image');
            if ($oldImage && ! Str::startsWith($oldImage, ['http://', 'https://'])) {
                Storage::disk('public')->delete($oldImage);
            }
            $path = $request->file('image')->store('product_images', 'public');
            $data['image'] = $path;
        }

        $product->update($data);
        $product->refresh();

        if ($request->has('recipe_id')) {
            $this->assignExistingRecipe($request, $product, $recipeId);
        }

        // If the request includes a recipe, update the recipe and its ingredients
        if (! $request->has('recipe_id') && $request->has('recipe')) {
            // Update or create the recipe
            $recipe = Recipe::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'branch_id' => $product->branch_id,
                    'description' => $request->recipe['description'],
                ]
            );

            // Delete old ingredients and add new ones
            $recipe->ingredients()->delete();  // Remove all old ingredients

            // Add new ingredients
            foreach ($request->recipe['ingredients'] as $ingredientData) {
                $ingredient = Ingredient::firstOrCreate([
                    'name' => $ingredientData['name'],
                    'unit' => $ingredientData['unit'],
                ]);

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity' => $ingredientData['quantity'],
                ]);
            }
        }

        return response()->json($product->fresh(['category', 'recipe.ingredients', 'branch']));
    }

    // Remove a product from the database
    public function destroy(Request $request, $id)
    {
        $product = $this->branchScoped($request, Product::query())->findOrFail($id);

        // If the product has a recipe, delete the associated recipe and ingredients
        if ($product->recipe) {
            $product->recipe->ingredients()->delete();
            $product->recipe->delete();
        }

        // Delete the product
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
        DB::transaction(function () use ($request, $product, $recipeId): void {
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
                abort_unless(
                    (int) $recipe->branch_id === (int) $product->branch_id,
                    422,
                    'Product recipe must belong to the selected branch.'
                );
            }

            Recipe::query()
                ->where('product_id', $product->id)
                ->whereKeyNot($recipe->id)
                ->update(['product_id' => null]);

            if (! $recipe->product_id || (int) $recipe->product_id === (int) $product->id) {
                $recipe->update([
                    'product_id' => $product->id,
                    'branch_id' => $product->branch_id,
                ]);

                return;
            }

            $copy = Recipe::create([
                'product_id' => $product->id,
                'branch_id' => $product->branch_id,
                'description' => $recipe->description,
            ]);

            foreach ($recipe->ingredients as $ingredient) {
                $copy->ingredients()->attach($ingredient->id, [
                    'quantity' => $ingredient->pivot->quantity,
                ]);
            }
        });
    }
}
