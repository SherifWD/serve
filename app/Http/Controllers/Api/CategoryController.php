<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Modifier;
use App\Support\KdsStation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $categories = $this->categoryQueryForUser($request)
            ->with($this->categoryRelations())
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $this->groupForDashboard($categories)->values()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'kds_station' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:category_questions,id',
            'questions.*.question' => 'nullable|string|max:255',
            'questions.*.image' => 'nullable|string|max:2048',
            'questions.*.choices' => 'nullable|array',
            'questions.*.choices.*.id' => 'nullable|integer|exists:category_choices,id',
            'questions.*.choices.*.choice' => 'nullable|string|max:255',
            'questions.*.choices.*.image' => 'nullable|string|max:2048',
            'modifiers' => 'nullable|array',
            'modifiers.*.id' => 'nullable|integer|exists:modifiers,id',
            'modifiers.*.name' => 'nullable|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
        ]);
        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
        );
        $questions = $data['questions'] ?? null;
        $modifiers = $data['modifiers'] ?? null;
        unset($data['questions'], $data['modifiers'], $data['branch_ids'], $data['branch_id']);

        $data['name'] = trim($data['name']);
        $data['kds_station'] = KdsStation::normalize($data['kds_station'] ?? null);
        $groupId = (string) Str::uuid();

        $categories = DB::transaction(function () use ($data, $branchIds, $groupId, $questions, $modifiers) {
            $saved = collect();

            foreach ($branchIds as $branchId) {
                $category = Category::query()
                    ->where('branch_id', $branchId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
                    ->first();

                $category ??= new Category();
                $category->fill($data);
                $category->branch_id = $branchId;
                $category->branch_group_id = $category->branch_group_id ?: $groupId;
                $category->save();

                if ($questions !== null) {
                    $this->syncQuestions($category, $questions);
                }
                if ($modifiers !== null) {
                    $this->syncModifiers($category, $modifiers);
                }

                $saved->push($category);
            }

            return $saved;
        });

        return response()->json($this->withBranchGroupMetadata($categories->first()->load($this->categoryRelations()), $request), 201);
    }

    public function show(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)
            ->with($this->categoryRelations())
            ->findOrFail($id);

        return $this->withBranchGroupMetadata($category, $request);
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)->findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:255',
            'kds_station' => 'nullable|string|max:40',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:category_questions,id',
            'questions.*.question' => 'nullable|string|max:255',
            'questions.*.image' => 'nullable|string|max:2048',
            'questions.*.choices' => 'nullable|array',
            'questions.*.choices.*.id' => 'nullable|integer|exists:category_choices,id',
            'questions.*.choices.*.choice' => 'nullable|string|max:255',
            'questions.*.choices.*.image' => 'nullable|string|max:2048',
            'modifiers' => 'nullable|array',
            'modifiers.*.id' => 'nullable|integer|exists:modifiers,id',
            'modifiers.*.name' => 'nullable|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
        ]);
        $questions = $data['questions'] ?? null;
        $modifiers = $data['modifiers'] ?? null;
        $branchIds = $this->branchIdsForWrite(
            $request,
            $data['branch_ids'] ?? null,
            isset($data['branch_id']) ? (int) $data['branch_id'] : (int) $category->branch_id,
        );
        unset($data['questions'], $data['modifiers'], $data['branch_ids'], $data['branch_id']);

        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
        }
        if (array_key_exists('kds_station', $data)) {
            $data['kds_station'] = KdsStation::normalize($data['kds_station']);
        }

        $groupId = $category->branch_group_id ?: (string) Str::uuid();

        DB::transaction(function () use ($category, $data, $branchIds, $groupId, $questions, $modifiers) {
            if (! $category->branch_group_id) {
                $category->branch_group_id = $groupId;
                $category->save();
            }

            foreach ($branchIds as $index => $branchId) {
                $target = Category::query()
                    ->where('branch_group_id', $groupId)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $target) {
                    $target = $index === 0 ? $category : new Category();
                    $target->branch_id = $branchId;
                    $target->branch_group_id = $groupId;
                }

                $target->fill($data);
                $target->branch_id = $branchId;
                $target->branch_group_id = $groupId;
                $target->save();

                if ($questions !== null) {
                    $this->syncQuestions($target, $questions);
                }
                if ($modifiers !== null) {
                    $this->syncModifiers($target, $modifiers);
                }
            }
        });

        return response()->json($this->withBranchGroupMetadata($category->fresh()->load($this->categoryRelations()), $request));
    }

    public function destroy(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function categoryQueryForUser(Request $request)
    {
        $query = Category::query();
        $user = $request->user();

        if ($user?->isPlatformAdmin()) {
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->integer('branch_id'));
            } elseif ($request->filled('restaurant_id')) {
                $restaurantId = $request->integer('restaurant_id');
                $query->where(function ($scope) use ($restaurantId) {
                    $scope->whereNull('branch_id')
                        ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $restaurantId));
                });
            }

            return $query;
        }

        if ($user?->branch_id) {
            return $query->where(function ($scope) use ($user) {
                $scope->whereNull('branch_id')
                    ->orWhere('branch_id', $user->branch_id);
            });
        }

        if ($user?->restaurant_id) {
            return $query->where(function ($scope) use ($user) {
                $scope->whereNull('branch_id')
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('restaurant_id', $user->restaurant_id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function categoryRelations(): array
    {
        return [
            'branch.restaurant:id,name,kind',
            'questions' => fn ($query) => $query->orderBy('id'),
            'questions.choices' => fn ($query) => $query->orderBy('id'),
            'modifiers' => fn ($query) => $query->select('id', 'name', 'price', 'restaurant_id', 'category_id', 'is_active')
                ->where('is_active', true)
                ->orderBy('name'),
        ];
    }

    private function syncQuestions(Category $category, array $questions): void
    {
        $keptQuestionIds = [];

        foreach ($questions as $questionData) {
            $questionText = trim((string) ($questionData['question'] ?? ''));
            $choices = $questionData['choices'] ?? [];

            if ($questionText === '') {
                continue;
            }

            $question = null;
            if (!empty($questionData['id'])) {
                $question = $category->questions()->whereKey((int) $questionData['id'])->first();
            }

            $question ??= $category->questions()->make();
            $question->question = $questionText;
            if (array_key_exists('image', $questionData)) {
                $question->image = $questionData['image'] ?: null;
            }
            $question->save();

            $keptQuestionIds[] = $question->id;
            $this->syncChoices($question, is_array($choices) ? $choices : []);
        }

        $deleteQuery = $category->questions();
        if ($keptQuestionIds) {
            $deleteQuery->whereNotIn('id', $keptQuestionIds);
        }
        $deleteQuery->delete();
    }

    private function syncChoices($question, array $choices): void
    {
        $keptChoiceIds = [];

        foreach ($choices as $choiceData) {
            $choiceText = trim((string) ($choiceData['choice'] ?? ''));
            if ($choiceText === '') {
                continue;
            }

            $choice = null;
            if (!empty($choiceData['id'])) {
                $choice = $question->choices()->whereKey((int) $choiceData['id'])->first();
            }

            $choice ??= $question->choices()->make();
            $choice->choice = $choiceText;
            if (array_key_exists('image', $choiceData)) {
                $choice->image = $choiceData['image'] ?: null;
            }
            $choice->save();

            $keptChoiceIds[] = $choice->id;
        }

        $deleteQuery = $question->choices();
        if ($keptChoiceIds) {
            $deleteQuery->whereNotIn('id', $keptChoiceIds);
        }
        $deleteQuery->delete();
    }

    private function syncModifiers(Category $category, array $modifiers): void
    {
        $restaurantId = $this->restaurantIdForCategory($category);
        $keptModifierIds = [];

        foreach ($modifiers as $modifierData) {
            $name = trim((string) ($modifierData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $modifier = null;
            if (!empty($modifierData['id'])) {
                $modifier = Modifier::query()
                    ->whereKey((int) $modifierData['id'])
                    ->where('category_id', $category->id)
                    ->first();
            }

            $modifier ??= new Modifier();
            $modifier->name = $name;
            $modifier->price = (float) ($modifierData['price'] ?? 0);
            $modifier->restaurant_id = $restaurantId;
            $modifier->category_id = $category->id;
            $modifier->is_active = true;
            $modifier->save();

            $keptModifierIds[] = $modifier->id;
        }

        $inactiveQuery = Modifier::query()
            ->where('category_id', $category->id)
            ->where('is_active', true);

        if ($keptModifierIds) {
            $inactiveQuery->whereNotIn('id', $keptModifierIds);
        }

        $inactiveQuery->update(['is_active' => false]);
    }

    private function groupForDashboard(Collection $categories): Collection
    {
        return $categories
            ->groupBy(fn (Category $category) => $category->branch_group_id ?: mb_strtolower(trim($category->name)))
            ->map(function (Collection $group) {
                $category = $group->first();
                $branches = $group->pluck('branch')->filter()->unique('id')->values();

                $category->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
                $category->setRelation('branches', $branches);

                return $category;
            });
    }

    private function withBranchGroupMetadata(Category $category, Request $request): Category
    {
        $siblingsQuery = Category::query()->with('branch.restaurant:id,name,kind');
        $branchIds = $this->accessibleBranchIds($request);

        if ($branchIds !== null) {
            $siblingsQuery->whereIn('branch_id', $branchIds);
        }

        if ($category->branch_group_id) {
            $siblingsQuery->where('branch_group_id', $category->branch_group_id);
        } else {
            $siblingsQuery->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($category->name))]);
        }

        $siblings = $siblingsQuery->get();

        $branches = $siblings->pluck('branch')->filter()->unique('id')->values();
        $category->setAttribute('branch_ids', $branches->pluck('id')->map(fn ($id) => (int) $id)->values()->all());
        $category->setRelation('branches', $branches);

        return $category;
    }

    private function restaurantIdForCategory(Category $category): ?int
    {
        if (!$category->branch_id) {
            return null;
        }

        return Branch::query()
            ->whereKey($category->branch_id)
            ->value('restaurant_id');
    }
}
