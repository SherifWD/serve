<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;

class RecipeController extends Controller
{
    use EnforcesTenantAccess;

    // GET /api/recipes
    public function index(Request $request)
    {
        // Eager load ingredients if desired
        return $this->branchScoped($request, Recipe::query())->with([
            'branch:id,name,restaurant_id',
            'branch.restaurant:id,name,kind',
            'ingredients' => function($q) {
                $q->select('ingredients.id', 'name', 'unit');
            }
        ])->get(['id', 'description', 'branch_id']);
    }

    // GET /api/recipes/{id}
    public function show(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::with([
            'branch:id,name,restaurant_id',
            'branch.restaurant:id,name,kind',
            'ingredients' => function($q) {
                $q->select('ingredients.id', 'name', 'unit');
            },
        ]))->findOrFail($id);

        return response()->json($recipe);
    }

    // POST /api/recipes
    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'ingredients' => 'array', // optional
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
        ]);
        $data['branch_id'] = $this->defaultBranchIdForWrite($request, $data['branch_id'] ?? null);
        $recipe = Recipe::create([
            'description' => $data['description'],
            'branch_id' => $data['branch_id'],
        ]);

        // Attach ingredients if any
        if (!empty($data['ingredients'])) {
            foreach ($data['ingredients'] as $ingredient) {
                $recipe->ingredients()->attach($ingredient['ingredient_id'], [
                    'quantity' => $ingredient['quantity'],
                ]);
            }
        }

        return response()->json($recipe->load(['branch.restaurant:id,name,kind', 'ingredients']), 201);
    }

    // PUT /api/recipes/{id}
    public function update(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::query())->findOrFail($id);
        $data = $request->validate([
            'description' => 'sometimes|required|string',
            'branch_id' => 'sometimes|required|integer|exists:branches,id',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
        ]);
        if (array_key_exists('branch_id', $data)) {
            $recipe->branch_id = $this->branchIdForWrite($request, (int) $data['branch_id']);
        }

        if (isset($data['description'])) {
            $recipe->description = $data['description'];
        }
        $recipe->save();

        // Update ingredients pivot
        if (isset($data['ingredients'])) {
            $syncData = [];
            foreach ($data['ingredients'] as $ing) {
                $syncData[$ing['ingredient_id']] = ['quantity' => $ing['quantity']];
            }
            $recipe->ingredients()->sync($syncData);
        }

        return response()->json($recipe->load(['branch.restaurant:id,name,kind', 'ingredients']));
    }

    // DELETE /api/recipes/{id}
    public function destroy(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::query())->findOrFail($id);
        $recipe->ingredients()->detach();
        $recipe->delete();

        return response()->json(['success' => true]);
    }
}
