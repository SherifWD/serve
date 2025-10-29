<?php

namespace App\Domain\Visitor\Http\Controllers;

use App\Domain\Visitor\Http\Requests\VisitorEntryStoreRequest;
use App\Domain\Visitor\Http\Requests\VisitorEntryUpdateRequest;
use App\Domain\Visitor\Http\Resources\VisitorEntryResource;
use App\Domain\Visitor\Models\Visitor;
use App\Domain\Visitor\Models\VisitorEntry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $entries = VisitorEntry::query()
            ->with('visitor')
            ->where('tenant_id', $tenantId)
            ->when($request->query('visitor_id'), fn ($query, $visitorId) => $query->where('visitor_id', $visitorId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('scheduled_start')
            ->paginate($request->integer('per_page', 20));

        return VisitorEntryResource::collection($entries)->response();
    }

    public function store(VisitorEntryStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $visitor = Visitor::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['visitor_id'])
            ->firstOrFail();

        $entry = VisitorEntry::create([
            ...$data,
            'tenant_id' => $tenantId,
        ]);

        return VisitorEntryResource::make($entry->load('visitor'))->response()->setStatusCode(201);
    }

    public function show(VisitorEntry $visitorEntry): JsonResponse
    {
        $this->authorizeTenantResource($visitorEntry);

        return VisitorEntryResource::make($visitorEntry->load('visitor'))->response();
    }

    public function update(VisitorEntryUpdateRequest $request, VisitorEntry $visitorEntry): JsonResponse
    {
        $this->authorizeTenantResource($visitorEntry);
        $data = $request->validated();

        if (isset($data['visitor_id'])) {
            $tenantId = app('tenant.context')->ensureTenant()->id;
            Visitor::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['visitor_id'])
                ->firstOrFail();
        }

        $visitorEntry->update($data);

        return VisitorEntryResource::make($visitorEntry->load('visitor'))->response();
    }

    public function destroy(VisitorEntry $visitorEntry): JsonResponse
    {
        $this->authorizeTenantResource($visitorEntry);
        $visitorEntry->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(VisitorEntry $entry): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($entry->tenant_id !== $tenantId, 404);
    }
}
