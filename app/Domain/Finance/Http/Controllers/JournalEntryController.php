<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\JournalEntryStoreRequest;
use App\Domain\Finance\Http\Requests\JournalEntryUpdateRequest;
use App\Domain\Finance\Http\Resources\JournalEntryResource;
use App\Domain\Finance\Models\JournalEntry;
use App\Domain\Finance\Models\JournalEntryLine;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $entries = JournalEntry::query()
            ->with('lines.ledgerAccount')
            ->where('tenant_id', $tenantId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('entry_date')
            ->paginate($request->integer('per_page', 20));

        return JournalEntryResource::collection($entries)->response();
    }

    public function store(JournalEntryStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        $data = $request->validated();

        $entry = DB::transaction(function () use ($tenantId, $data) {
            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'reference' => $data['reference'],
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'draft',
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::create([
                    'tenant_id' => $tenantId,
                    'journal_entry_id' => $entry->id,
                    'ledger_account_id' => $line['ledger_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $entry->load('lines.ledgerAccount');
        });

        return JournalEntryResource::make($entry)->response()->setStatusCode(201);
    }

    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeTenantResource($journalEntry);

        return JournalEntryResource::make($journalEntry->load('lines.ledgerAccount'))->response();
    }

    public function update(JournalEntryUpdateRequest $request, JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeTenantResource($journalEntry);
        $data = $request->validated();
        $tenantId = $journalEntry->tenant_id;

        $journalEntry = DB::transaction(function () use ($journalEntry, $data, $tenantId) {
            $journalEntry->update($data);

            if (isset($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    if (($line['_action'] ?? null) === 'delete' && !empty($line['id'])) {
                        JournalEntryLine::query()
                            ->where('tenant_id', $tenantId)
                            ->where('id', $line['id'])
                            ->delete();
                        continue;
                    }

                    JournalEntryLine::updateOrCreate(
                        [
                            'id' => $line['id'] ?? null,
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'tenant_id' => $tenantId,
                            'journal_entry_id' => $journalEntry->id,
                            'ledger_account_id' => $line['ledger_account_id'],
                            'debit' => $line['debit'] ?? 0,
                            'credit' => $line['credit'] ?? 0,
                            'memo' => $line['memo'] ?? null,
                        ]
                    );
                }
            }

            return $journalEntry->load('lines.ledgerAccount');
        });

        return JournalEntryResource::make($journalEntry)->response();
    }

    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $this->authorizeTenantResource($journalEntry);
        $journalEntry->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(JournalEntry $journalEntry): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($journalEntry->tenant_id !== $tenantId, 404);
    }
}

