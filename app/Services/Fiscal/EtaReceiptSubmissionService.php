<?php

namespace App\Services\Fiscal;

use App\Models\EtaReceiptSubmission;
use App\Models\Receipt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class EtaReceiptSubmissionService
{
    public function __construct(private readonly EtaReceiptMapper $mapper)
    {
    }

    public function queue(Receipt $receipt): EtaReceiptSubmission
    {
        $receipt->loadMissing('order.branch');
        $mapped = $this->mapper->map($receipt);

        if (!$mapped['eta_ready']) {
            throw ValidationException::withMessages([
                'receipt' => $mapped['warnings'],
            ]);
        }

        return EtaReceiptSubmission::create([
            'receipt_id' => $receipt->id,
            'restaurant_id' => $receipt->order->branch->restaurant_id,
            'branch_id' => $receipt->order->branch_id,
            'status' => 'queued',
            'payload' => $mapped['submission'],
        ]);
    }

    public function submit(EtaReceiptSubmission $submission): EtaReceiptSubmission
    {
        if (!config('services.eta.enabled')) {
            $submission->forceFill([
                'status' => 'queued',
                'error_message' => 'ETA direct submission is disabled; receipt remains queued for export/signing.',
            ])->save();

            return $submission;
        }

        $endpoint = rtrim((string) config('services.eta.base_url'), '/').'/api/v1/receiptsubmissions';
        $token = config('services.eta.access_token');

        if (blank($token)) {
            $submission->forceFill([
                'status' => 'queued',
                'error_message' => 'ETA access token is not configured; receipt remains queued.',
            ])->save();

            return $submission;
        }

        $response = Http::withToken((string) $token)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $submission->payload);

        $body = $response->json() ?? [];
        $submission->forceFill([
            'status' => $response->successful() ? 'submitted' : 'failed',
            'eta_submission_uuid' => $body['submissionUUID'] ?? null,
            'eta_request_id' => $response->header('request-id') ?? $response->header('x-request-id'),
            'eta_response' => $body,
            'attempted_at' => now(),
            'submitted_at' => $response->successful() ? now() : null,
            'error_message' => $response->successful() ? null : ($body['message'] ?? $response->body()),
            'attempts' => $submission->attempts + 1,
        ])->save();

        return $submission;
    }
}
