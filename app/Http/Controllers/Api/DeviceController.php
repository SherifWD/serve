<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    use EnforcesTenantAccess;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $devices = $this->branchScoped($request, Device::query())
            ->with('branch.restaurant')
            ->latest()
            ->paginate(50);

        return response()->json($devices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'uuid' => 'required|string|max:255|unique:devices,uuid',
            'branch_id' => 'required|integer|exists:branches,id',
            'payment_provider' => 'nullable|string|max:100',
            'printer_profile' => 'nullable|string|max:100',
            'printer_paper_width_mm' => 'nullable|integer|min:40|max:120',
            'printer_endpoint' => 'nullable|string|max:255',
            'capabilities' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);
        $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);

        $device = Device::query()->create($data);

        return response()->json(['data' => $device->load('branch.restaurant')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $device = $this->branchScoped($request, Device::query())
            ->with('branch.restaurant')
            ->findOrFail($id);

        return response()->json(['data' => $device]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $device = $this->branchScoped($request, Device::query())->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:50',
            'uuid' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('devices', 'uuid')->ignore($device->id)],
            'branch_id' => 'sometimes|required|integer|exists:branches,id',
            'payment_provider' => 'nullable|string|max:100',
            'printer_profile' => 'nullable|string|max:100',
            'printer_paper_width_mm' => 'nullable|integer|min:40|max:120',
            'printer_endpoint' => 'nullable|string|max:255',
            'capabilities' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);
        if (array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $this->branchIdForWrite($request, (int) $data['branch_id']);
        }

        $device->update($data);

        return response()->json(['data' => $device->fresh('branch.restaurant')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $device = $this->branchScoped($request, Device::query())->findOrFail($id);
        $device->delete();

        return response()->json(['message' => 'Device deleted']);
    }
}
