<?php

namespace App\Domain\HRMS\Http\Controllers;

use App\Domain\HRMS\Http\Requests\AttendanceRecordStoreRequest;
use App\Domain\HRMS\Http\Requests\AttendanceRecordUpdateRequest;
use App\Domain\HRMS\Http\Resources\AttendanceRecordResource;
use App\Domain\HRMS\Models\AttendanceRecord;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $records = AttendanceRecord::query()
            ->with('worker')
            ->where('tenant_id', $tenantId)
            ->when($request->query('worker_id'), fn ($q, $workerId) => $q->where('worker_id', $workerId))
            ->when($request->query('date'), fn ($q, $date) => $q->where('attendance_date', $date))
            ->orderByDesc('attendance_date')
            ->paginate($request->integer('per_page', 20));

        return AttendanceRecordResource::collection($records)->response();
    }

    public function store(AttendanceRecordStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $record = AttendanceRecord::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return AttendanceRecordResource::make($record->load('worker'))->response()->setStatusCode(201);
    }

    public function show(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorizeTenantResource($attendanceRecord);

        return AttendanceRecordResource::make($attendanceRecord->load('worker'))->response();
    }

    public function update(AttendanceRecordUpdateRequest $request, AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorizeTenantResource($attendanceRecord);

        $attendanceRecord->update($request->validated());

        return AttendanceRecordResource::make($attendanceRecord->load('worker'))->response();
    }

    public function destroy(AttendanceRecord $attendanceRecord): JsonResponse
    {
        $this->authorizeTenantResource($attendanceRecord);
        $attendanceRecord->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(AttendanceRecord $attendanceRecord): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($attendanceRecord->tenant_id !== $tenantId, 404);
    }
}

