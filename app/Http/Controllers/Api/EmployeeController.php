<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        return response()->json($query->with('branch')->latest()->paginate(20));
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
        $employee = Employee::create($data);
        return response()->json($employee, 201);
    }

    public function show($id)
    {
        return Employee::with('branch')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $data = $request->validate([
            'name' => 'string',
            'branch_id' => 'integer|exists:branches,id',
            'position' => 'string',
            'base_salary' => 'numeric',
            'hired_at' => 'date',
        ]);
        $employee->update($data);
        return response()->json($employee);
    }

    public function destroy($id)
    {
        Employee::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
    public function performance($id)
{
    $employee = Employee::findOrFail($id);

    // You can filter, order, or limit as needed
    $performances = $employee->performances()
        ->orderBy('recorded_at', 'desc')
        ->get();

    return response()->json($performances);
}

}
