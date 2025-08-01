<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Display a listing of products
    public function index(Request $request)
    {
        // Optionally: filter by branch, pagination
        $query = Product::query();
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);

        return response()->json($query->with('category','recipe.ingredients.recipeIngredients','branch')->latest()->paginate(20));
    }

    // Store a new product along with a recipe and ingredients
    public function store(Request $request)
{
    $data = $request->validate([
        'name'        => 'required|string',
        'category_id' => 'required|integer|exists:categories,id',
        'branch_id'   => 'required|integer|exists:branches,id',
        'price'       => 'required|numeric',
        'is_available'=> 'boolean',
        'min_stock'   => 'required|integer|min:0',
        'image'       => 'nullable|image|max:2048',
    ]);

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

    // If the product includes a recipe, store it along with ingredients
    if ($request->has('recipe')) {
        // Create the recipe associated with the product
        $recipe = Recipe::create([
            'product_id' => $product->id,
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

    return response()->json($product, 201);
}


    // Display the specified product along with its recipe and ingredients
    public function show($id)
{
    $product = Product::with(['category', 'recipe.ingredients'])->findOrFail($id);
    return response()->json($product);
}



    // Update an existing product and its recipe and ingredients
    public function update(Request $request, $id)
{
    // Validate product data
    $product = Product::findOrFail($id);
    $data = $request->validate([
        'name'        => 'string',
        'category_id' => 'integer|exists:categories,id',
        'branch_id'   => 'integer|exists:branches,id',
        'price'       => 'numeric',
        'is_available'=> 'boolean',
        'min_stock'   => 'integer|min:0',
        'image'       => 'nullable|image|max:2048',
    ]);
$data['is_available'] = (int) $request->boolean('is_available');
    // Handle image upload
    if ($request->hasFile('image')) {
        // Delete old if exists
        if ($product->image) Storage::disk('public')->delete($product->image);
        $path = $request->file('image')->store('product_images', 'public');
        $data['image'] = $path;
    }

    $product->update($data);
    // If the request includes a recipe, update the recipe and its ingredients
    if ($request->has('recipe')) {
        // Update or create the recipe
        $recipe = Recipe::updateOrCreate(
            ['product_id' => $product->id],
            ['description' => $request->recipe['description']]
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

    return response()->json($product);
}


    // Remove a product from the database
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // If the product has a recipe, delete the associated recipe and ingredients
        if ($product->recipe) {
            $product->recipe->ingredients()->delete();
            $product->recipe->delete();
        }

        // Delete the product
        $product->delete();

        return response()->json(['message' => 'Product and associated recipe deleted successfully']);
    }
}
