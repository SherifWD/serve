<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\EtaReceiptSubmission;
use App\Models\Receipt;
use App\Services\Fiscal\EtaReceiptSubmissionService;
use Illuminate\Http\Request;

class EtaSubmissionController extends Controller
{
    use EnforcesTenantAccess;

    public function store(Request $request, Receipt $receipt, EtaReceiptSubmissionService $service)
    {
        $data = $request->validate([
            'submit_now' => 'nullable|boolean',
        ]);

        $scopedReceipt = $this->branchRelationScoped($request, Receipt::query(), 'order.branch')
            ->with(['order.branch.restaurant', 'order.customer', 'order.payments'])
            ->findOrFail($receipt->id);

        $submission = $service->queue($scopedReceipt);

        if ($data['submit_now'] ?? false) {
            $submission = $service->submit($submission);
        }

        return response()->json([
            'data' => $submission->fresh(),
        ], 201);
    }

    public function show(Request $request, EtaReceiptSubmission $etaReceiptSubmission)
    {
        $submission = $this->branchScoped($request, EtaReceiptSubmission::query())
            ->with('receipt')
            ->findOrFail($etaReceiptSubmission->id);

        return response()->json([
            'data' => $submission,
        ]);
    }
}
