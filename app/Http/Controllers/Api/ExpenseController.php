<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = $this->branchScoped($request, Expense::query())
            ->with('branch.restaurant:id,name,kind,currency_code')
            ->latest('expense_date')
            ->latest('id');

        if ($request->filled('start_date')) {
            $query->whereDate('expense_date', '>=', $request->date('start_date')->toDateString());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('expense_date', '<=', $request->date('end_date')->toDateString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        return response()->json($query->paginate((int) $request->input('per_page', 20)));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);

        $expense = Expense::query()->create($data);

        return response()->json([
            'data' => $expense->load('branch.restaurant:id,name,kind,currency_code'),
        ], 201);
    }

    public function show(Request $request, Expense $expense)
    {
        $this->ensureBranchAccess($request, $expense->branch_id);

        return response()->json([
            'data' => $expense->load('branch.restaurant:id,name,kind,currency_code'),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->ensureBranchAccess($request, $expense->branch_id);

        $data = $this->validatedPayload($request);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);

        $expense->update($data);

        return response()->json([
            'data' => $expense->fresh('branch.restaurant:id,name,kind,currency_code'),
        ]);
    }

    public function destroy(Request $request, Expense $expense)
    {
        $this->ensureBranchAccess($request, $expense->branch_id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted']);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0|max:99999999.99',
            'description' => 'nullable|string|max:1000',
            'expense_date' => 'required|date',
        ]);
    }
}
