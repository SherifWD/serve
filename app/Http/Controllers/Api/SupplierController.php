<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use EnforcesTenantAccess;

    // Get a list of all suppliers
    public function index(Request $request)
    {
        $suppliers = $this->restaurantScoped($request, Supplier::query())->get();
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
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
        ]);
        $data['restaurant_id'] = $this->restaurantIdForWrite($request, $data['restaurant_id'] ?? null);

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

        $supplier = $this->restaurantScoped($request, Supplier::query())->findOrFail($id);
        $supplier->update($data);
        return response()->json($supplier);
    }

    public function show(Request $request, $id)
    {
        $supplier = $this->restaurantScoped($request, Supplier::query())->findOrFail($id);

        return response()->json($supplier);
    }

    // Delete a supplier
    public function destroy(Request $request, $id)
    {
        $supplier = $this->restaurantScoped($request, Supplier::query())->findOrFail($id);
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
