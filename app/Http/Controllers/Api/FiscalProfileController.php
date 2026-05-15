<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesTenantAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\FiscalProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FiscalProfileController extends Controller
{
    use EnforcesTenantAccess;

    public function index(Request $request)
    {
        $query = $this->restaurantScoped($request, FiscalProfile::query())
            ->with(['restaurant:id,name,kind', 'branch:id,name,restaurant_id'])
            ->latest('id');

        if ($request->filled('branch_id')) {
            $this->ensureBranchAccess($request, $request->integer('branch_id'));
            $query->where('branch_id', $request->integer('branch_id'));
        } elseif ($request->user()?->branch_id) {
            $query->where(function ($scoped) use ($request) {
                $scoped
                    ->whereNull('branch_id')
                    ->orWhere('branch_id', $request->user()->branch_id);
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['restaurant_id'] = $this->restaurantIdForWrite($request, $data['restaurant_id'] ?? null);
        $this->validateBranchAssignment($request, $data);
        $this->assertProfileSlotAvailable($data['restaurant_id'], $data['branch_id'] ?? null);

        $profile = FiscalProfile::query()->create($data);

        return response()->json(['data' => $profile->load(['restaurant:id,name,kind', 'branch:id,name,restaurant_id'])], 201);
    }

    public function show(Request $request, FiscalProfile $fiscalProfile)
    {
        $profile = $this->restaurantScoped($request, FiscalProfile::query())
            ->with(['restaurant:id,name,kind', 'branch:id,name,restaurant_id'])
            ->findOrFail($fiscalProfile->id);

        return response()->json(['data' => $profile]);
    }

    public function update(Request $request, FiscalProfile $fiscalProfile)
    {
        $profile = $this->restaurantScoped($request, FiscalProfile::query())->findOrFail($fiscalProfile->id);
        $data = $this->validated($request, partial: true);

        if (array_key_exists('restaurant_id', $data)) {
            $data['restaurant_id'] = $this->restaurantIdForWrite($request, $data['restaurant_id']);
        } else {
            $data['restaurant_id'] = $profile->restaurant_id;
        }

        if (!array_key_exists('branch_id', $data)) {
            $data['branch_id'] = $profile->branch_id;
        }

        $this->validateBranchAssignment($request, $data);
        $this->assertProfileSlotAvailable($data['restaurant_id'], $data['branch_id'] ?? null, $profile->id);

        $profile->update($data);

        return response()->json(['data' => $profile->fresh(['restaurant:id,name,kind', 'branch:id,name,restaurant_id'])]);
    }

    public function destroy(Request $request, FiscalProfile $fiscalProfile)
    {
        $profile = $this->restaurantScoped($request, FiscalProfile::query())->findOrFail($fiscalProfile->id);
        $profile->delete();

        return response()->json(['message' => 'Fiscal profile deleted']);
    }

    public function effective(Request $request)
    {
        $branchId = $request->filled('branch_id') ? $request->integer('branch_id') : $request->user()?->branch_id;
        if ($branchId) {
            $this->ensureBranchAccess($request, $branchId);
        }

        $restaurantId = $request->filled('restaurant_id')
            ? $this->restaurantIdForWrite($request, $request->integer('restaurant_id'))
            : $request->user()?->restaurant_id;

        $profile = FiscalProfile::effectiveForBranch($branchId, $restaurantId);

        return response()->json(['data' => $profile]);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return $request->validate([
            'restaurant_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'integer', 'exists:restaurants,id'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'display_name' => [$required, 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'vat_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'price_includes_vat' => ['sometimes', 'boolean'],
            'vat_tax_type' => ['sometimes', 'string', 'max:30'],
            'vat_subtype' => ['sometimes', 'string', 'max:50'],
            'buyer_id_threshold' => ['sometimes', 'numeric', 'min:0'],
            'default_payment_method_code' => ['sometimes', 'string', 'max:10'],
            'eta_receipt_type' => ['sometimes', Rule::in(['SC'])],
            'eta_type_version' => ['sometimes', Rule::in(['1.2'])],
            'eta_seller_rin' => ['nullable', 'string', 'max:30'],
            'eta_seller_name' => ['nullable', 'string', 'max:200'],
            'eta_branch_code' => ['nullable', 'string', 'max:50'],
            'eta_device_serial_number' => ['nullable', 'string', 'max:100'],
            'eta_activity_code' => ['nullable', 'string', 'max:10'],
            'address_country' => ['sometimes', 'string', 'size:2'],
            'address_governate' => ['nullable', 'string', 'max:100'],
            'address_region_city' => ['nullable', 'string', 'max:100'],
            'address_street' => ['nullable', 'string', 'max:200'],
            'address_building_number' => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:30'],
            'address_floor' => ['nullable', 'string', 'max:100'],
            'address_room' => ['nullable', 'string', 'max:100'],
            'address_landmark' => ['nullable', 'string', 'max:500'],
            'address_additional_information' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validateBranchAssignment(Request $request, array $data): void
    {
        if (empty($data['branch_id'])) {
            return;
        }

        $this->ensureBranchAccess($request, (int) $data['branch_id']);

        $branchRestaurantId = Branch::query()->whereKey($data['branch_id'])->value('restaurant_id');
        abort_unless((int) $branchRestaurantId === (int) $data['restaurant_id'], 422, 'Fiscal profile branch must belong to the selected restaurant.');
    }

    private function assertProfileSlotAvailable(int $restaurantId, ?int $branchId, ?int $ignoreId = null): void
    {
        $exists = FiscalProfile::query()
            ->where('restaurant_id', $restaurantId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId), fn ($query) => $query->whereNull('branch_id'))
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        abort_if($exists, 422, 'A fiscal profile already exists for this restaurant/branch scope.');
    }
}
