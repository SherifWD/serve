<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Support\IngredientUnits;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $recipes = $this->branchScoped($request, Recipe::query())->with([
            'branch:id,name,restaurant_id',
            'branch.restaurant:id,name,kind',
            'ingredients' => function ($query) {
                $query->select('ingredients.id', 'name', 'unit');
            },
        ])->get(['id', 'description', 'branch_id', 'branch_group_id']);

        return response()->json($this->groupForDashboard($recipes)->values());
    }

    public function show(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::with([
            'branch:id,name,restaurant_id',
            'branch.restaurant:id,name,kind',
            'ingredients' => function ($query) {
                $query->select('ingredients.id', 'name', 'unit');
            },
        ]))->findOrFail($id);

        return response()->json($this->withBranchGroupMetadata($recipe, $request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id|distinct',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.unit' => 'nullable|string|max:50',
        ]);

        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
        );
        $ingredients = $this->normalizedIngredientRows($data['ingredients'] ?? []);
        $groupId = (string) Str::uuid();

        $recipes = DB::transaction(function () use ($data, $branchIds, $ingredients, $groupId) {
            $saved = collect();

            foreach ($branchIds as $branchId) {
                $recipe = Recipe::create([
                    'description' => $data['description'],
                    'branch_id' => $branchId,
                    'branch_group_id' => $groupId,
                ]);

                $this->syncIngredients($recipe, $ingredients);
                $saved->push($recipe);
            }

            return $saved;
        });

        return response()->json($this->withBranchGroupMetadata($recipes->first()->load(['branch.restaurant:id,name,kind', 'ingredients']), $request), 201);
    }

    public function update(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::query())->findOrFail($id);
        $data = $request->validate([
            'description' => 'sometimes|required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id|distinct',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.unit' => 'nullable|string|max:50',
        ]);

        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : (int) $recipe->branch_id,
        );
        $ingredients = array_key_exists('ingredients', $data)
            ? $this->normalizedIngredientRows($data['ingredients'] ?? [])
            : null;
        $groupId = $recipe->branch_group_id ?: (string) Str::uuid();

        DB::transaction(function () use ($recipe, $data, $branchIds, $ingredients, $groupId) {
            if (! $recipe->branch_group_id) {
                $recipe->branch_group_id = $groupId;
                $recipe->save();
            }

            foreach ($branchIds as $index => $branchId) {
                $target = Recipe::query()
                    ->where('branch_group_id', $groupId)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $target) {
                    $target = $index === 0 ? $recipe : $recipe->replicate();
                    $target->branch_id = $branchId;
                    $target->branch_group_id = $groupId;
                    if (! $target->exists) {
                        $target->product_id = null;
                    }
                }

                if (isset($data['description'])) {
                    $target->description = $data['description'];
                }
                $target->branch_id = $branchId;
                $target->branch_group_id = $groupId;
                $target->save();

                if ($ingredients !== null) {
                    $this->syncIngredients($target, $ingredients);
                }
            }
        });

        return response()->json($this->withBranchGroupMetadata($recipe->fresh(['branch.restaurant:id,name,kind', 'ingredients']), $request));
    }

    public function destroy(Request $request, $id)
    {
        $recipe = $this->branchScoped($request, Recipe::query())->findOrFail($id);
        $recipe->ingredients()->detach();
        $recipe->delete();

        return response()->json(['success' => true]);
    }

    private function normalizedIngredientRows(array $rows): array
    {
        if (! $rows) {
            return [];
        }

        $ingredients = Ingredient::query()
            ->whereIn('id', collect($rows)->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all())
            ->get()
            ->keyBy('id');

        return collect($rows)
            ->mapWithKeys(function (array $row) use ($ingredients) {
                $ingredient = $ingredients[(int) $row['ingredient_id']];
                $unit = $row['unit'] ?? $ingredient->unit;

                return [
                    (int) $ingredient->id => [
                        'quantity' => IngredientUnits::toMinimumUnit((float) $row['quantity'], $unit, $ingredient->unit),
                    ],
                ];
            })
            ->all();
    }

    private function syncIngredients(Recipe $recipe, array $ingredients): void
    {
        $recipe->ingredients()->sync($ingredients);
    }

    private function groupForDashboard(Collection $recipes): Collection
    {
        return $recipes
            ->groupBy(fn (Recipe $recipe) => $recipe->branch_group_id ?: 'recipe:'.$recipe->id)
            ->map(function (Collection $group) {
                $recipe = $group->first();
                $branches = $group->pluck('branch')->filter()->unique('id')->values();

                $recipe->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
                $recipe->setRelation('branches', $branches);

                return $recipe;
            });
    }

    private function withBranchGroupMetadata(Recipe $recipe, Request $request): Recipe
    {
        if (! $recipe->branch_group_id) {
            $branches = collect([$recipe->branch])->filter()->values();
            $recipe->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->all());
            $recipe->setRelation('branches', $branches);

            return $recipe;
        }

        $branchIds = $this->accessibleBranchIds($request);
        $siblings = Recipe::query()
            ->where('branch_group_id', $recipe->branch_group_id)
            ->with('branch.restaurant:id,name,kind')
            ->when($branchIds !== null, fn ($query) => $query->whereIn('branch_id', $branchIds))
            ->get();
        $branches = $siblings->pluck('branch')->filter()->unique('id')->values();

        $recipe->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
        $recipe->setRelation('branches', $branches);

        return $recipe;
    }
}
