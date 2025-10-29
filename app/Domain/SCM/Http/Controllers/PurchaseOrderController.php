<?php

namespace App\Domain\SCM\Http\Controllers;

use App\Domain\SCM\Http\Requests\PurchaseOrderStoreRequest;
use App\Domain\SCM\Http\Requests\PurchaseOrderUpdateRequest;
use App\Domain\SCM\Http\Resources\PurchaseOrderResource;
use App\Domain\SCM\Models\PurchaseOrder;
use App\Domain\SCM\Models\PurchaseOrderLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier', 'lines'])
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('supplier_id'), fn ($q, $supplierId) => $q->where('supplier_id', $supplierId))
            ->orderByDesc('order_date')
            ->paginate($request->integer('per_page', 20));

        return PurchaseOrderResource::collection($purchaseOrders)->response();
    }

    public function store(PurchaseOrderStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $purchaseOrder = DB::transaction(function () use ($tenantId, $data) {
            $purchaseOrder = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $data['supplier_id'],
                'po_number' => $data['po_number'],
                'status' => $data['status'] ?? 'draft',
                'order_date' => $data['order_date'] ?? null,
                'expected_date' => $data['expected_date'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $subtotal = 0;
            foreach ($data['lines'] as $lineData) {
                $lineTotal = $lineData['quantity'] * $lineData['unit_price'];
                $subtotal += $lineTotal;

                PurchaseOrderLine::create([
                    'tenant_id' => $tenantId,
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id' => $lineData['item_id'] ?? null,
                    'description' => $lineData['description'] ?? null,
                    'quantity' => $lineData['quantity'],
                    'uom' => $lineData['uom'] ?? 'EA',
                    'unit_price' => $lineData['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            $purchaseOrder->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($subtotal * 0.1, 2), // placeholder 10% tax
                'grand_total' => round($subtotal * 1.1, 2),
            ]);

            return $purchaseOrder->load(['supplier', 'lines']);
        });

        return PurchaseOrderResource::make($purchaseOrder)->response()->setStatusCode(201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeTenantResource($purchaseOrder);

        return PurchaseOrderResource::make(
            $purchaseOrder->load(['supplier', 'lines', 'shipments'])
        )->response();
    }

    public function update(PurchaseOrderUpdateRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeTenantResource($purchaseOrder);
        $data = $request->validated();
        $tenantId = $purchaseOrder->tenant_id;

        $purchaseOrder = DB::transaction(function () use ($purchaseOrder, $data, $tenantId) {
            $purchaseOrder->update($data);

            if (isset($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    if (($lineData['_action'] ?? null) === 'delete' && !empty($lineData['id'])) {
                        PurchaseOrderLine::query()
                            ->where('tenant_id', $tenantId)
                            ->where('id', $lineData['id'])
                            ->delete();
                        continue;
                    }

                    $lineTotal = $lineData['quantity'] * $lineData['unit_price'];

                    PurchaseOrderLine::updateOrCreate(
                        [
                            'id' => $lineData['id'] ?? null,
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'purchase_order_id' => $purchaseOrder->id,
                            'item_id' => $lineData['item_id'] ?? null,
                            'description' => $lineData['description'] ?? null,
                            'quantity' => $lineData['quantity'],
                            'uom' => $lineData['uom'] ?? 'EA',
                            'unit_price' => $lineData['unit_price'],
                            'line_total' => $lineTotal,
                        ]
                    );
                }
            }

            $totals = PurchaseOrderLine::query()
                ->where('tenant_id', $tenantId)
                ->where('purchase_order_id', $purchaseOrder->id)
                ->selectRaw('SUM(line_total) as subtotal')
                ->first();

            $subtotal = (float) ($totals->subtotal ?? 0);
            $purchaseOrder->update([
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($subtotal * 0.1, 2),
                'grand_total' => round($subtotal * 1.1, 2),
            ]);

            return $purchaseOrder->load(['supplier', 'lines', 'shipments']);
        });

        return PurchaseOrderResource::make($purchaseOrder)->response();
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeTenantResource($purchaseOrder);
        $purchaseOrder->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(PurchaseOrder $purchaseOrder): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($purchaseOrder->tenant_id !== $tenantId, 404);
    }
}

