<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;

class RecipeController extends Controller
{
    // GET /api/recipes
    public function index()
    {
        // Eager load ingredients if desired
        return Recipe::with([
            'ingredients' => function($q) {
                $q->select('ingredients.id', 'name', 'unit');
            }
        ])->get(['id', 'description']);
    }

    // GET /api/recipes/{id}
    public function show($id)
    {
        $recipe = Recipe::with(['ingredients' => function($q) {
            $q->select('ingredients.id', 'name', 'unit');
        }])->findOrFail($id);

        return response()->json($recipe);
    }

    // POST /api/recipes
    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string',
            'ingredients' => 'array', // optional
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
        ]);
        $recipe = Recipe::create([
            'description' => $data['description'],
        ]);

        // Attach ingredients if any
        if (!empty($data['ingredients'])) {
            foreach ($data['ingredients'] as $ingredient) {
                $recipe->ingredients()->attach($ingredient['ingredient_id'], [
                    'quantity' => $ingredient['quantity'],
                ]);
            }
        }

        return response()->json($recipe->load('ingredients'), 201);
    }

    // PUT /api/recipes/{id}
    public function update(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);
        $data = $request->validate([
            'description' => 'sometimes|required|string',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if (isset($data['description'])) {
            $recipe->description = $data['description'];
            $recipe->save();
        }

        // Update ingredients pivot
        if (isset($data['ingredients'])) {
            $syncData = [];
            foreach ($data['ingredients'] as $ing) {
                $syncData[$ing['ingredient_id']] = ['quantity' => $ing['quantity']];
            }
            $recipe->ingredients()->sync($syncData);
        }

        return response()->json($recipe->load('ingredients'));
    }

    // DELETE /api/recipes/{id}
    public function destroy($id)
    {
        $recipe = Recipe::findOrFail($id);
        $recipe->ingredients()->detach();
        $recipe->delete();

        return response()->json(['success' => true]);
    }
}
