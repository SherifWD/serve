<?php

namespace App\Domain\Procurement\Http\Controllers;

use App\Domain\Procurement\Http\Requests\TenderResponseStoreRequest;
use App\Domain\Procurement\Http\Requests\TenderResponseUpdateRequest;
use App\Domain\Procurement\Http\Resources\TenderResponseResource;
use App\Domain\Procurement\Models\Tender;
use App\Domain\Procurement\Models\TenderResponse;
use App\Domain\Procurement\Models\Vendor;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderResponseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $responses = TenderResponse::query()
            ->with(['tender', 'vendor'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('tender_id'), fn ($query, $tenderId) => $query->where('tender_id', $tenderId))
            ->orderByDesc('response_date')
            ->paginate($request->integer('per_page', 20));

        return TenderResponseResource::collection($responses)->response();
    }

    public function store(TenderResponseStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        Tender::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $data['tender_id'])
            ->firstOrFail();

        if (!empty($data['vendor_id'])) {
            Vendor::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['vendor_id'])
                ->firstOrFail();
        }

        $response = TenderResponse::create([
            ...$data,
            'tenant_id' => $tenantId,
        ])->load(['tender', 'vendor']);

        return TenderResponseResource::make($response)->response()->setStatusCode(201);
    }

    public function show(TenderResponse $tenderResponse): JsonResponse
    {
        $this->authorizeTenantResource($tenderResponse);

        return TenderResponseResource::make($tenderResponse->load(['tender', 'vendor']))->response();
    }

    public function update(TenderResponseUpdateRequest $request, TenderResponse $tenderResponse): JsonResponse
    {
        $this->authorizeTenantResource($tenderResponse);
        $data = $request->validated();

        if (isset($data['tender_id'])) {
            $tenantId = app('tenant.context')->ensureTenant()->id;
            Tender::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['tender_id'])
                ->firstOrFail();
        }

        if (array_key_exists('vendor_id', $data) && $data['vendor_id']) {
            $tenantId = app('tenant.context')->ensureTenant()->id;
            Vendor::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $data['vendor_id'])
                ->firstOrFail();
        }

        $tenderResponse->update($data);

        return TenderResponseResource::make($tenderResponse->load(['tender', 'vendor']))->response();
    }

    public function destroy(TenderResponse $tenderResponse): JsonResponse
    {
        $this->authorizeTenantResource($tenderResponse);
        $tenderResponse->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(TenderResponse $response): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($response->tenant_id !== $tenantId, 404);
    }
}
