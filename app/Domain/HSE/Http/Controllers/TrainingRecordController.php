<?php

namespace App\Domain\HSE\Http\Controllers;

use App\Domain\HSE\Http\Requests\TrainingRecordStoreRequest;
use App\Domain\HSE\Http\Requests\TrainingRecordUpdateRequest;
use App\Domain\HSE\Http\Resources\TrainingRecordResource;
use App\Domain\HSE\Models\TrainingRecord;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $records = TrainingRecord::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('from_date'), fn ($query, $date) => $query->whereDate('session_date', '>=', $date))
            ->when($request->query('to_date'), fn ($query, $date) => $query->whereDate('session_date', '<=', $date))
            ->orderByDesc('session_date')
            ->paginate($request->integer('per_page', 20));

        return TrainingRecordResource::collection($records)->response();
    }

    public function store(TrainingRecordStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $record = TrainingRecord::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return TrainingRecordResource::make($record)->response()->setStatusCode(201);
    }

    public function show(TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorizeTenantResource($trainingRecord);

        return TrainingRecordResource::make($trainingRecord)->response();
    }

    public function update(TrainingRecordUpdateRequest $request, TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorizeTenantResource($trainingRecord);

        $trainingRecord->update($request->validated());

        return TrainingRecordResource::make($trainingRecord)->response();
    }

    public function destroy(TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorizeTenantResource($trainingRecord);
        $trainingRecord->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(TrainingRecord $trainingRecord): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($trainingRecord->tenant_id !== $tenantId, 404);
    }
}
