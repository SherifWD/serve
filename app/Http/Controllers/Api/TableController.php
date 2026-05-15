<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Table;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1'
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $table = Table::create($data);
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
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1'
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $table->update($data);
        return response()->json($table->load('branch'));
    }

    // Delete a table
    public function destroy(Request $request, $id)
    {
        $table = $this->branchScoped($request, Table::query())->findOrFail($id);
        $table->delete();
        return response()->json(['message' => 'Table deleted']);
    }
}
