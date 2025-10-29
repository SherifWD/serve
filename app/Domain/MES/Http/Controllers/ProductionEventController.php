<?php

namespace App\Domain\MES\Http\Controllers;

use App\Domain\MES\Http\Requests\ProductionEventStoreRequest;
use App\Domain\MES\Http\Resources\ProductionEventResource;
use App\Domain\MES\Models\ProductionEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $events = ProductionEvent::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('work_order_id'), fn ($query, $workOrderId) => $query->where('work_order_id', $workOrderId))
            ->orderByDesc('event_timestamp')
            ->paginate($request->integer('per_page', 50));

        return ProductionEventResource::collection($events)->response();
    }

    public function store(ProductionEventStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $event = ProductionEvent::create([
            'tenant_id' => $tenantId,
            'work_order_id' => $data['work_order_id'],
            'machine_id' => $data['machine_id'] ?? null,
            'recorded_by' => $request->user()?->id,
            'event_type' => $data['event_type'],
            'event_timestamp' => $data['event_timestamp'],
            'payload' => $data['payload'] ?? null,
        ]);

        return ProductionEventResource::make($event)->response()->setStatusCode(201);
    }
}

