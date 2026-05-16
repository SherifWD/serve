<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Modifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $categories = $this->categoryQueryForUser($request)
            ->with($this->categoryRelations())
            ->orderBy('name')
            ->get()
            ->unique(fn (Category $category) => mb_strtolower(trim($category->name)))
            ->values();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
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
        unset($data['questions'], $data['modifiers']);

        $data['name'] = trim($data['name']);
        if (!empty($data['branch_id'])) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        } else {
            $data['branch_id'] = null;
        }

        $user = $request->user();
        $existingQuery = $user?->isPlatformAdmin()
            ? Category::query()
            : $this->categoryQueryForUser($request);

        $existing = $existingQuery
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->first();

        if ($existing) {
            DB::transaction(function () use ($existing, $questions, $modifiers) {
                if ($questions !== null) {
                    $this->syncQuestions($existing, $questions);
                }
                if ($modifiers !== null) {
                    $this->syncModifiers($existing, $modifiers);
                }
            });

            return response()->json($existing->fresh()->load($this->categoryRelations()));
        }

        $category = DB::transaction(function () use ($data, $questions, $modifiers) {
            $category = Category::create($data);

            if ($questions !== null) {
                $this->syncQuestions($category, $questions);
            }
            if ($modifiers !== null) {
                $this->syncModifiers($category, $modifiers);
            }

            return $category;
        });

        return response()->json($category->load($this->categoryRelations()), 201);
    }

    public function show(Request $request, $id)
    {
        return $this->categoryQueryForUser($request)
            ->with($this->categoryRelations())
            ->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = $this->categoryQueryForUser($request)->findOrFail($id);
        $data = $request->validate([
            'name' => 'string|max:255',
            'branch_id' => 'nullable|integer|exists:branches,id',
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
        unset($data['questions'], $data['modifiers']);

        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            $duplicate = Category::query()
                ->whereKeyNot($category->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'name' => 'Category names must be unique.',
                ]);
            }
        }

        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $data['branch_id'] === null
                ? null
                : $this->branchIdForWrite($request, (int) $data['branch_id']);
        }

        DB::transaction(function () use ($category, $data, $questions, $modifiers) {
            $category->update($data);
            $category->refresh();

            if ($questions !== null) {
                $this->syncQuestions($category, $questions);
            }
            if ($modifiers !== null) {
                $this->syncModifiers($category, $modifiers);
            }
        });

        return response()->json($category->fresh()->load($this->categoryRelations()));
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
