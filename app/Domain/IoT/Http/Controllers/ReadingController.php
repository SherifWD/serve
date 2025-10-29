<?php

namespace App\Domain\IoT\Http\Controllers;

use App\Domain\IoT\Http\Requests\ReadingStoreRequest;
use App\Domain\IoT\Http\Requests\ReadingUpdateRequest;
use App\Domain\IoT\Http\Resources\ReadingResource;
use App\Domain\IoT\Models\Reading;
use App\Domain\IoT\Models\Sensor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $readings = Reading::query()
            ->with('sensor')
            ->where('tenant_id', $tenantId)
            ->when($request->query('sensor_id'), fn ($query, $sensorId) => $query->where('sensor_id', $sensorId))
            ->when($request->query('from'), fn ($query, $from) => $query->where('recorded_at', '>=', $from))
            ->when($request->query('to'), fn ($query, $to) => $query->where('recorded_at', '<=', $to))
            ->orderByDesc('recorded_at')
            ->paginate($request->integer('per_page', 50));

        return ReadingResource::collection($readings)->response();
    }

    public function store(ReadingStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        Sensor::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['sensor_id'])
            ->firstOrFail();

        $reading = Reading::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load('sensor');

        return ReadingResource::make($reading)->response()->setStatusCode(201);
    }

    public function show(Reading $reading): JsonResponse
    {
        $this->authorizeTenantResource($reading);

        return ReadingResource::make($reading->load('sensor'))->response();
    }

    public function update(ReadingUpdateRequest $request, Reading $reading): JsonResponse
    {
        $this->authorizeTenantResource($reading);
        $data = $request->validated();

        if (isset($data['sensor_id'])) {
            $tenantId = app('tenant.context')->ensureTenant()->id;
            Sensor::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['sensor_id'])
                ->firstOrFail();
        }

        $reading->update($data);

        return ReadingResource::make($reading->load('sensor'))->response();
    }

    public function destroy(Reading $reading): JsonResponse
    {
        $this->authorizeTenantResource($reading);
        $reading->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Reading $reading): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($reading->tenant_id !== $tenantId, 404);
    }
}
