<?php

namespace App\Domain\Procurement\Http\Controllers;

use App\Domain\Procurement\Http\Requests\TenderStoreRequest;
use App\Domain\Procurement\Http\Requests\TenderUpdateRequest;
use App\Domain\Procurement\Http\Resources\TenderResource;
use App\Domain\Procurement\Models\Tender;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $tenders = Tender::query()
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('opening_date')
            ->paginate($request->integer('per_page', 20));

        return TenderResource::collection($tenders)->response();
    }

    public function store(TenderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $tender = Tender::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return TenderResource::make($tender)->response()->setStatusCode(201);
    }

    public function show(Tender $tender): JsonResponse
    {
        $this->authorizeTenantResource($tender);
        $tender->load('responses.vendor');

        return TenderResource::make($tender)->response();
    }

    public function update(TenderUpdateRequest $request, Tender $tender): JsonResponse
    {
        $this->authorizeTenantResource($tender);

        $tender->update($request->validated());

        return TenderResource::make($tender)->response();
    }

    public function destroy(Tender $tender): JsonResponse
    {
        $this->authorizeTenantResource($tender);
        $tender->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Tender $tender): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($tender->tenant_id !== $tenantId, 404);
    }
}
