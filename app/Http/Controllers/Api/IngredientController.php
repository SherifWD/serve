<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientBranch;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        // List all ingredients with branch stocks and related recipes
        $ingredients = Ingredient::with([
            'ingredientBranches.branch:id,name',
            'recipes:id,description'
        ])->get();

        return response()->json($ingredients);
    }

    public function show($id)
    {
        // Show one ingredient, all branch stocks, and related recipes
        $ingredient = Ingredient::with([
            'ingredientBranches.branch:id,name',
            'recipes:id,description'
        ])->findOrFail($id);
        return response()->json($ingredient);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string',
            'unit'   => 'required|string',
            'stock'  => 'nullable|numeric|min:0',
            'ingredient_branches'   => 'array',
            'ingredient_branches.*.branch_id' => 'required|integer|exists:branches,id',
            'ingredient_branches.*.stock'     => 'required|numeric|min:0',
            'recipe_ingredients'    => 'array',
            'recipe_ingredients.*.recipe_id' => 'required|integer|exists:recipes,id',
            'recipe_ingredients.*.quantity'  => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $ingredient = Ingredient::create([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'stock' => $data['stock'] ?? 0
            ]);

            // Sync ingredient_branches
            if (!empty($data['ingredient_branches'])) {
                foreach ($data['ingredient_branches'] as $ib) {
                    IngredientBranch::updateOrCreate([
                        'ingredient_id' => $ingredient->id,
                        'branch_id'     => $ib['branch_id'],
                    ], [
                        'stock' => $ib['stock']
                    ]);
                }
            }

            // Sync recipe_ingredients
            if (!empty($data['recipe_ingredients'])) {
                foreach ($data['recipe_ingredients'] as $ri) {
                    RecipeIngredient::updateOrCreate([
                        'ingredient_id' => $ingredient->id,
                        'recipe_id'     => $ri['recipe_id']
                    ], [
                        'quantity' => $ri['quantity']
                    ]);
                }
            }
            DB::commit();

            return response()->json($ingredient->load(['ingredientBranches.branch', 'recipes:id,description']), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::findOrFail($id);

        $data = $request->validate([
            'name'   => 'required|string',
            'unit'   => 'required|string',
            'stock'  => 'nullable|numeric|min:0',
            'ingredient_branches'   => 'array',
            'ingredient_branches.*.branch_id' => 'required|integer|exists:branches,id',
            'ingredient_branches.*.stock'     => 'required|numeric|min:0',
            'recipe_ingredients'    => 'array',
            'recipe_ingredients.*.recipe_id' => 'required|integer|exists:recipes,id',
            'recipe_ingredients.*.quantity'  => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $ingredient->update([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'stock' => $data['stock'] ?? 0
            ]);

            // Sync ingredient_branches
            // Remove old and add current
            IngredientBranch::where('ingredient_id', $ingredient->id)->delete();
            if (!empty($data['ingredient_branches'])) {
                foreach ($data['ingredient_branches'] as $ib) {
                    IngredientBranch::create([
                        'ingredient_id' => $ingredient->id,
                        'branch_id'     => $ib['branch_id'],
                        'stock'         => $ib['stock']
                    ]);
                }
            }

            // Sync recipe_ingredients
            RecipeIngredient::where('ingredient_id', $ingredient->id)->delete();
            if (!empty($data['recipe_ingredients'])) {
                foreach ($data['recipe_ingredients'] as $ri) {
                    RecipeIngredient::create([
                        'ingredient_id' => $ingredient->id,
                        'recipe_id'     => $ri['recipe_id'],
                        'quantity'      => $ri['quantity']
                    ]);
                }
            }
            DB::commit();

            return response()->json($ingredient->load(['ingredientBranches.branch', 'recipes:id,description']));
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();
        return response()->json(['message' => 'Deleted.']);
    }
public function updateStock(Request $request)
{
    $data = $request->validate([
        'ingredient_id' => 'required|exists:ingredients,id',
        'branch_id' => 'required|exists:branches,id',
        'stock' => 'required|numeric|min:0',
    ]);

    $ingredient = Ingredient::findOrFail($data['ingredient_id']);

    // Calculate the total stock if this branch is updated
    $otherBranchesTotal = IngredientBranch::where('ingredient_id', $ingredient->id)
        ->where('branch_id', '!=', $data['branch_id'])
        ->sum('stock');

    $totalAfter = $otherBranchesTotal + $data['stock'];

    if ($ingredient->stock !== null && $totalAfter > $ingredient->stock) {
        return response()->json([
            'message' => "Total stock assigned to branches ({$totalAfter}) cannot exceed the global stock ({$ingredient->stock})."
        ], 422);
    }

    $ib = IngredientBranch::updateOrCreate(
        [
            'ingredient_id' => $data['ingredient_id'],
            'branch_id' => $data['branch_id']
        ],
        ['stock' => $data['stock']]
    );

    return response()->json($ib->load('ingredient', 'branch'));
}

}
