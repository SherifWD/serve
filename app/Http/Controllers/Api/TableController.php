<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Branch;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    use EnforcesTenantAccess;

    // List all tables (with branch name)
   public function index(Request $request) {
    $tables = $this->branchScoped($request, Table::query())->with('branch')->withCount(['orders' => function($q) {
        $q->where('status', '!=', 'closed'); // Or whatever means "active"
    }])->get();

    // If you want a computed status:
    foreach ($tables as $table) {
        $table->status = $table->orders_count > 0 ? 'occupied' : 'open';
    }

    return response()->json(['data' => $tables]);
}



    // Store a new table
    public function store(Request $request)
    {
        if ($request->has('tables')) {
            $data = $request->validate([
                'restaurant_id' => 'nullable|integer|exists:restaurants,id',
                'branch_id' => 'required|exists:branches,id',
                'tables' => 'required|array|min:1',
                'tables.*.name' => 'required|string|max:255',
                'tables.*.seats' => 'nullable|integer|min:1',
            ]);

            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
            $this->ensureBranchMatchesRestaurant($data['restaurant_id'] ?? null, (int) $data['branch_id']);

            $tables = DB::transaction(function () use ($data) {
                $createdTables = collect($data['tables'])
                    ->map(fn (array $tableData) => Table::create([
                        'branch_id' => $data['branch_id'],
                        'name' => $tableData['name'],
                        'seats' => $tableData['seats'] ?? null,
                    ]))
                    ->values();

                return Table::with('branch')
                    ->whereKey($createdTables->pluck('id')->all())
                    ->get();
            });

            return response()->json(['data' => $tables], 201);
        }

        $data = $request->validate([
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'seats' => 'nullable|integer|min:1',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $this->ensureBranchMatchesRestaurant($data['restaurant_id'] ?? null, (int) $data['branch_id']);
        $table = Table::create([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'seats' => $data['seats'] ?? null,
        ]);
        return response()->json($table->load('branch'), 201);
    }

    // Show a table
    public function show(Request $request, $id)
{
    // Eager load orders and their items/products for this table only!
    $table = $this->branchScoped($request, Table::with(['orders.items.product']))->findOrFail($id);

    // Optionally, format the data to match your frontend expectations
    return response()->json([
        'data' => $table
    ]);
}

    // Update a table
    public function update(Request $request, $id)
    {
        $table = $this->branchScoped($request, Table::query())->findOrFail($id);
        $data = $request->validate([
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'seats' => 'nullable|integer|min:1',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $this->ensureBranchMatchesRestaurant($data['restaurant_id'] ?? null, (int) $data['branch_id']);
        $table->update([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'seats' => $data['seats'] ?? null,
        ]);
        return response()->json($table->load('branch'));
    }

    // Delete a table
    public function destroy(Request $request, $id)
    {
        $table = $this->branchScoped($request, Table::query())->findOrFail($id);
        $table->delete();
        return response()->json(['message' => 'Table deleted']);
    }

    private function ensureBranchMatchesRestaurant(?int $restaurantId, int $branchId): void
    {
        if ($restaurantId === null) {
            return;
        }

        abort_unless(
            Branch::query()
                ->whereKey($branchId)
                ->where('restaurant_id', $restaurantId)
                ->exists(),
            422,
            'Selected branch must belong to the selected restaurant.'
        );
    }
}
