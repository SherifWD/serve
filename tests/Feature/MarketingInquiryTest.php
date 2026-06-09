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
