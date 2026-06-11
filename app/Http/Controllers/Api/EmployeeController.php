<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = $this->branchScoped($request, Employee::query());
        return response()->json($query->with('branch.restaurant:id,name,kind,currency_code')->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'branch_id' => 'required|integer|exists:branches,id',
            'position' => 'required|string',
            'base_salary' => 'required|numeric',
            'hired_at' => 'required|date',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        $employee = Employee::create($data);
        return response()->json($employee, 201);
    }

    public function show(Request $request, $id)
    {
        return $this->branchScoped($request, Employee::with('branch.restaurant:id,name,kind,currency_code'))->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $employee = $this->branchScoped($request, Employee::query())->findOrFail($id);
        $data = $request->validate([
            'name' => 'string',
            'branch_id' => 'integer|exists:branches,id',
            'position' => 'string',
            'base_salary' => 'numeric',
            'hired_at' => 'date',
        ]);
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        }
        $employee->update($data);
        return response()->json($employee);
    }

    public function destroy(Request $request, $id)
    {
        $employee = $this->branchScoped($request, Employee::query())->findOrFail($id);
        $employee->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function performance(Request $request, $id)
{
    $employee = $this->branchScoped($request, Employee::query())->findOrFail($id);

    // You can filter, order, or limit as needed
    $performances = $employee->performances()
        ->orderBy('recorded_at', 'desc')
        ->get();

    return response()->json($performances);
}

}
