<?php

namespace App\Domain\CRM\Http\Controllers;

use App\Domain\CRM\Http\Requests\ContactStoreRequest;
use App\Domain\CRM\Http\Requests\ContactUpdateRequest;
use App\Domain\CRM\Http\Resources\ContactResource;
use App\Domain\CRM\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $contacts = Contact::query()
            ->with('account')
            ->where('tenant_id', $tenantId)
            ->when($request->query('account_id'), fn ($q, $accountId) => $q->where('account_id', $accountId))
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 20));

        return ContactResource::collection($contacts)->response();
    }

    public function store(ContactStoreRequest $request): JsonResponse
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;

        $contact = Contact::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return ContactResource::make($contact->load('account'))->response()->setStatusCode(201);
    }

    public function show(Contact $contact): JsonResponse
    {
        $this->authorizeTenantResource($contact);

        return ContactResource::make($contact->load('account'))->response();
    }

    public function update(ContactUpdateRequest $request, Contact $contact): JsonResponse
    {
        $this->authorizeTenantResource($contact);

        $contact->update($request->validated());

        return ContactResource::make($contact->load('account'))->response();
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorizeTenantResource($contact);
        $contact->delete();

        return response()->json(['status' => 'deleted']);
    }

    protected function authorizeTenantResource(Contact $contact): void
    {
        $tenantId = app('tenant.context')->ensureTenant()->id;
        abort_if($contact->tenant_id !== $tenantId, 404);
    }
}

