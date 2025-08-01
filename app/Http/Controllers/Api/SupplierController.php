<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // Get a list of all suppliers
    public function index()
    {
        $suppliers = Supplier::all();
        return response()->json($suppliers);
    }

    // Store a new supplier
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
        ]);

        $supplier = Supplier::create($data);
        return response()->json($supplier, 201);
    }

    // Update an existing supplier
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($data);
        return response()->json($supplier);
    }

    // Delete a supplier
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
