<?php

namespace App\Domain\ERP\Http\Controllers;

use App\Domain\ERP\Http\Requests\BillOfMaterialStoreRequest;
use App\Domain\ERP\Http\Requests\BillOfMaterialUpdateRequest;
use App\Domain\ERP\Http\Resources\BillOfMaterialResource;
use App\Domain\ERP\Models\BillOfMaterial;
use App\Domain\ERP\Models\BillOfMaterialLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillOfMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $boms = BillOfMaterial::query()
            ->with(['item', 'lines.component'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('item_id'), fn ($query, $itemId) => $query->where('item_id', $itemId))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return BillOfMaterialResource::collection($boms)->response();
    }

    public function store(BillOfMaterialStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $data = $request->validated();

        $bom = DB::transaction(function () use ($tenantId, $data) {
            $bom = BillOfMaterial::create([
                'tenant_id' => $tenantId,
                'item_id' => $data['item_id'],
                'code' => $data['code'],
                'revision' => $data['revision'] ?? 'A',
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'metadata' => $data['metadata'] ?? null,
            ]);

            foreach ($data['components'] as $index => $component) {
                BillOfMaterialLine::create([
                    'tenant_id' => $tenantId,
                    'bom_id' => $bom->id,
                    'component_item_id' => $component['component_item_id'],
                    'quantity' => $component['quantity'],
                    'uom' => $component['uom'] ?? 'EA',
                    'sequence' => $component['sequence'] ?? ($index + 1),
                    'metadata' => $component['metadata'] ?? null,
                ]);
            }

            return $bom->load(['item', 'lines.component']);
        });

        return BillOfMaterialResource::make($bom)->response()->setStatusCode(201);
    }

    public function show(BillOfMaterial $bom): JsonResponse
    {
        $this->authorizeTenantResource($bom);

        return BillOfMaterialResource::make(
            $bom->load(['item', 'lines.component'])
        )->response();
    }

    public function update(BillOfMaterialUpdateRequest $request, BillOfMaterial $bom): JsonResponse
    {
        $this->authorizeTenantResource($bom);

        $data = $request->validated();
        $tenantId = $bom->tenant_id;

        $updatedBom = DB::transaction(function () use ($bom, $data, $tenantId) {
            $bom->update([
                ...$data,
                'revision' => $data['revision'] ?? $bom->revision,
            ]);

            if (isset($data['components'])) {
                foreach ($data['components'] as $component) {
                    if (($component['_action'] ?? null) === 'delete' && !empty($component['id'])) {
                        BillOfMaterialLine::query()
                            ->where('tenant_id', $tenantId)
                            ->where('id', $component['id'])
                            ->delete();
                        continue;
                    }

                    BillOfMaterialLine::updateOrCreate(
                        [
                            'id' => $component['id'] ?? null,
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'bom_id' => $bom->id,
                            'component_item_id' => $component['component_item_id'],
                            'quantity' => $component['quantity'],
                            'uom' => $component['uom'] ?? 'EA',
                            'sequence' => $component['sequence'] ?? 1,
                            'metadata' => $component['metadata'] ?? null,
                        ]
                    );
                }
            }

            return $bom->load(['item', 'lines.component']);
        });

        return BillOfMaterialResource::make($updatedBom)->response();
    }

    public function destroy(BillOfMaterial $bom): JsonResponse
    {
        $this->authorizeTenantResource($bom);

        $bom->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(BillOfMaterial $bom): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        abort_if($bom->tenant_id !== $tenantId, 404);
    }
}
