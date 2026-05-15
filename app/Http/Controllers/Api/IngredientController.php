<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Ingredient;
use App\Models\IngredientBranch;
use App\Models\RecipeIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        // List all ingredients with branch stocks and related recipes
        $ingredients = $this->ingredientQuery($request)->with([
            'ingredientBranches' => fn ($query) => $this->scopeIngredientBranches($request, $query),
            'ingredientBranches.branch:id,name',
            'recipes:id,description'
        ])->get();

        return response()->json($ingredients);
    }

    public function show(Request $request, $id)
    {
        // Show one ingredient, all branch stocks, and related recipes
        $ingredient = $this->ingredientQuery($request)->with([
            'ingredientBranches' => fn ($query) => $this->scopeIngredientBranches($request, $query),
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

        foreach ($data['ingredient_branches'] ?? [] as $ib) {
            $this->ensureBranchAccess($request, (int) $ib['branch_id']);
        }

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
        $ingredient = $this->ingredientQuery($request)->findOrFail($id);

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

        foreach ($data['ingredient_branches'] ?? [] as $ib) {
            $this->ensureBranchAccess($request, (int) $ib['branch_id']);
        }

        DB::beginTransaction();
        try {
            $ingredient->update([
                'name' => $data['name'],
                'unit' => $data['unit'],
                'stock' => $data['stock'] ?? 0
            ]);

            // Sync ingredient_branches
            // Remove old and add current
            $deleteBranches = IngredientBranch::where('ingredient_id', $ingredient->id);
            $branchIds = $this->accessibleBranchIds($request);
            if ($branchIds !== null) {
                $deleteBranches->whereIn('branch_id', $branchIds);
            }
            $deleteBranches->delete();
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
    ]);
    $this->ensureBranchAccess($request, (int) $data['branch_id']);

    $ingredient = $this->ingredientQuery($request)->findOrFail($data['ingredient_id']);

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
