<?php

namespace App\Domain\IoT\Http\Controllers;

use App\Domain\IoT\Http\Requests\DeviceStoreRequest;
use App\Domain\IoT\Http\Requests\DeviceUpdateRequest;
use App\Domain\IoT\Http\Resources\DeviceResource;
use App\Domain\IoT\Models\Device;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $devices = Device::query()
            ->where('tenant_id', $tenantId)
            ->with($request->boolean('with_sensors') ? ['sensors'] : [])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('device_key', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return DeviceResource::collection($devices)->response();
    }

    public function store(DeviceStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $device = Device::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return DeviceResource::make($device)->response()->setStatusCode(201);
    }

    public function show(Device $device): JsonResponse
    {
        $this->authorizeTenantResource($device);
        $device->load('sensors');

        return DeviceResource::make($device)->response();
    }

    public function update(DeviceUpdateRequest $request, Device $device): JsonResponse
    {
        $this->authorizeTenantResource($device);

        $device->update($request->validated());

        return DeviceResource::make($device)->response();
    }

    public function destroy(Device $device): JsonResponse
    {
        $this->authorizeTenantResource($device);
        $device->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Device $device): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($device->tenant_id !== $tenantId, 404);
    }
}
