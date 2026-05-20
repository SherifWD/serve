<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\PaymentProviderConfig;
use App\Support\HardwareValidation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentProviderController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $providers = $this->restaurantScoped(
            $request,
            PaymentProviderConfig::query()->with('branch')
        )
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->latest('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json($providers);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $restaurantId = $this->restaurantIdForWrite($request, $data['restaurant_id'] ?? null);
        $branchId = $data['branch_id'] ?? null;

        if ($branchId) {
            $this->ensureBranchAccess($request, (int) $branchId);
            abort_unless(
                (int) Branch::query()->whereKey($branchId)->value('restaurant_id') === (int) $restaurantId,
                403,
                'Payment provider branch must belong to the selected restaurant.'
            );
        }

        $provider = PaymentProviderConfig::query()->create([
            ...$data,
            'restaurant_id' => $restaurantId,
            'branch_id' => $branchId,
        ]);

        return response()->json(['data' => $provider->fresh('branch')], 201);
    }

    public function show(Request $request, PaymentProviderConfig $paymentProvider)
    {
        $provider = $this->restaurantScoped($request, PaymentProviderConfig::query()->with('branch'))
            ->findOrFail($paymentProvider->id);

        return response()->json(['data' => $provider]);
    }

    public function update(Request $request, PaymentProviderConfig $paymentProvider)
    {
        $provider = $this->restaurantScoped($request, PaymentProviderConfig::query())
            ->findOrFail($paymentProvider->id);
        $data = $this->validated($request, true);

        if (array_key_exists('branch_id', $data) && $data['branch_id']) {
            $this->ensureBranchAccess($request, (int) $data['branch_id']);
        }

        $provider->update($data);

        return response()->json(['data' => $provider->fresh('branch')]);
    }

    public function destroy(Request $request, PaymentProviderConfig $paymentProvider)
    {
        $provider = $this->restaurantScoped($request, PaymentProviderConfig::query())
            ->findOrFail($paymentProvider->id);
        $provider->delete();

        return response()->json(['message' => 'Payment provider deleted']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        $data = $request->validate([
            'restaurant_id' => 'nullable|integer|exists:restaurants,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'provider' => [$required, 'string', 'max:100'],
            'display_name' => [$required, 'string', 'max:255'],
            'mode' => ['nullable', Rule::in(['manual', 'terminal', 'online', 'gateway'])],
            'is_active' => 'nullable|boolean',
            'credentials' => 'nullable|array',
            'terminal_config' => 'nullable|array',
            'supported_methods' => 'nullable|array',
            'supported_methods.*' => ['string', Rule::in(HardwareValidation::PAYMENT_METHODS)],
            'webhook_secret' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $mode = $data['mode'] ?? null;
        if (in_array($mode, ['terminal', 'online', 'gateway'], true) && empty($data['supported_methods'])) {
            throw ValidationException::withMessages([
                'supported_methods' => 'Production payment providers must list supported methods.',
            ]);
        }

        if (($mode === 'gateway' || $mode === 'online') && empty($data['credentials']) && !$partial) {
            throw ValidationException::withMessages([
                'credentials' => 'Gateway and online payment providers require credentials.',
            ]);
        }

        return $data;
    }
}
