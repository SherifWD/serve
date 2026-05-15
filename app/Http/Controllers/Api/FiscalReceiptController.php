<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\Fiscal\EtaReceiptMapper;
use Illuminate\Http\Request;

class FiscalReceiptController extends Controller
{
    use EnforcesTenantAccess;

    public function show(Request $request, Receipt $receipt, EtaReceiptMapper $mapper)
    {
        $scopedReceipt = $this->branchRelationScoped($request, Receipt::query(), 'order.branch')
            ->with(['order.branch.restaurant', 'order.customer', 'order.table', 'order.payments'])
            ->findOrFail($receipt->id);

        $payload = $mapper->map($scopedReceipt);

        return response()->json($payload);
    }
}
