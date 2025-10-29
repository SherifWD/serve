<?php

namespace App\Domain\IoT\Http\Controllers;

use App\Domain\IoT\Http\Requests\SensorStoreRequest;
use App\Domain\IoT\Http\Requests\SensorUpdateRequest;
use App\Domain\IoT\Http\Resources\SensorResource;
use App\Domain\IoT\Models\Device;
use App\Domain\IoT\Models\Sensor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $sensors = Sensor::query()
            ->with('device')
            ->where('tenant_id', $tenantId)
            ->when($request->query('device_id'), fn ($query, $deviceId) => $query->where('device_id', $deviceId))
            ->when($request->query('tag'), fn ($query, $tag) => $query->where('tag', $tag))
            ->orderBy('tag')
            ->paginate($request->integer('per_page', 20));

        return SensorResource::collection($sensors)->response();
    }

    public function store(SensorStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        Device::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['device_id'])
            ->firstOrFail();

        $sensor = Sensor::create([
            ...$data,
            'tenant_id' => $tenantId,
        ]);

        return SensorResource::make($sensor->load('device'))->response()->setStatusCode(201);
    }

    public function show(Sensor $sensor): JsonResponse
    {
        $this->authorizeTenantResource($sensor);
        $sensor->load(['device', 'readings']);

        return SensorResource::make($sensor)->response();
    }

    public function update(SensorUpdateRequest $request, Sensor $sensor): JsonResponse
    {
        $this->authorizeTenantResource($sensor);
        $data = $request->validated();

        if (isset($data['device_id'])) {
            $tenantId = app('tenant.context')->ensureTenant()->id;
            Device::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['device_id'])
                ->firstOrFail();
        }

        $sensor->update($data);

        return SensorResource::make($sensor->load('device'))->response();
    }

    public function destroy(Sensor $sensor): JsonResponse
    {
        $this->authorizeTenantResource($sensor);
        $sensor->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Sensor $sensor): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($sensor->tenant_id !== $tenantId, 404);
    }
}
