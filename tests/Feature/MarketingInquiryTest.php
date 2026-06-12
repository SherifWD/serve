<?php

namespace Tests\Feature;

use App\Models\MarketingInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketingInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_survey_submission_is_stored(): void
    {
        $response = $this->post('/contact', $this->validPayload());

        $response
            ->assertRedirect('/contact')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('marketing_inquiries', [
            'full_name' => 'Mariam Nabil',
            'business_name' => 'Downtown Test Cafe',
            'email' => 'mariam@example.com',
            'phone' => '+201001112223',
            'status' => 'new',
            'consent_to_contact' => true,
        ]);

        $inquiry = MarketingInquiry::query()->firstOrFail();

        $this->assertSame(['dine-in', 'takeaway'], $inquiry->order_channels);
        $this->assertSame(['owner-dashboard', 'kds'], $inquiry->interest_areas);
    }

    public function test_admin_can_read_and_update_marketing_inquiries(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);
        $inquiry = MarketingInquiry::query()->create($this->validPayload());

        Sanctum::actingAs($admin);

        $this->getJson('/api/marketing-inquiries')
            ->assertOk()
            ->assertJsonPath('data.0.id', $inquiry->id)
            ->assertJsonPath('data.0.business_name', 'Downtown Test Cafe');

        $this->patchJson("/api/marketing-inquiries/{$inquiry->id}", [
            'status' => 'contacted',
            'admin_notes' => 'Call scheduled.',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'contacted')
            ->assertJsonPath('admin_notes', 'Call scheduled.');
    }

    public function test_standard_plan_checkout_is_stored_and_redirects_to_paymob(): void
    {
        config(['services.paymob.checkout_url' => 'https://paymob.example/checkout']);

        $response = $this->post('/checkout/standard', [
            'plan' => 'Cafe + KDS',
            'price' => 'USD 99/mo',
            'region' => 'mena',
            'full_name' => 'Youssef Owner',
            'business_name' => 'North Coast Cafe',
            'email' => 'checkout@example.com',
            'phone' => '+201009998887',
            'country' => 'Egypt',
            'contact_method' => 'whatsapp',
            'business_type' => 'big-cafe',
            'branch_count' => 1,
            'checkout_consent' => '1',
        ]);

        $response->assertRedirect();

        $location = $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://paymob.example/checkout?', $location);
        $this->assertSame('janova-serve-landing', $query['source']);
        $this->assertSame('Cafe + KDS', $query['plan']);
        $this->assertSame('USD 99/mo', $query['price']);
        $this->assertSame('mena', $query['region']);
        $this->assertArrayHasKey('lead_id', $query);
        $this->assertArrayNotHasKey('email', $query);
        $this->assertArrayNotHasKey('phone', $query);

        $this->assertDatabaseHas('marketing_inquiries', [
            'full_name' => 'Youssef Owner',
            'business_name' => 'North Coast Cafe',
            'email' => 'checkout@example.com',
            'business_type' => 'cafe',
            'status' => 'checkout_started',
            'budget_range' => 'USD 99/mo (mena)',
            'preferred_contact_method' => 'whatsapp',
            'consent_to_contact' => true,
        ]);
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Mariam Nabil',
            'business_name' => 'Downtown Test Cafe',
            'role' => 'Owner',
            'email' => 'mariam@example.com',
            'phone' => '+201001112223',
            'city' => 'Cairo',
            'website' => 'https://example.com',
            'business_type' => 'cafe',
            'branch_count' => 2,
            'staff_count' => 18,
            'current_system' => 'Manual sheets',
            'order_channels' => ['dine-in', 'takeaway'],
            'interest_areas' => ['owner-dashboard', 'kds'],
            'devices' => ['android', 'printer'],
            'timeline' => '1-3-months',
            'budget_range' => 'Pilot budget ready',
            'pain_points' => 'We need table flow, kitchen visibility, and better stock control.',
            'success_notes' => 'One branch pilot with staff training.',
            'preferred_contact_method' => 'whatsapp',
            'best_contact_time' => 'Afternoons',
            'consent_to_contact' => '1',
        ];
    }
}
