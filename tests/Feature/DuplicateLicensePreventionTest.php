<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CitizenProfile;
use App\Models\District;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DuplicateLicensePreventionTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private Reservoir $reservoir;
    private LicenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->reservoir = Reservoir::query()->create([
            'name' => 'Kabal Stream',
            'district_id' => $district->id,
            'water_body_type' => 'river',
            'trout_type' => 'trout',
            'is_active' => true,
            'is_open_for_licensing' => true,
        ]);

        $this->category = LicenseCategory::query()->create([
            'code' => 'TROUT-01',
            'name' => 'Trout Angling Permit',
            'duration_type' => 'daily',
            'duration_days' => 1,
            'fee_amount' => 1000,
            'currency' => 'PKR',
            'expiry_rule' => 'from_start',
            'is_active' => true,
        ]);

        $this->citizen = User::factory()->create([
            'user_type' => User::TYPE_CITIZEN,
            'mobile' => '03001234567',
            'is_active' => true,
        ]);

        CitizenProfile::query()->create([
            'user_id' => $this->citizen->id,
            'full_name' => 'Test Citizen',
            'father_name' => 'Test Father',
            'cnic' => '12345-6789012-3',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Test Address',
            'residence_district_id' => $district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03007654321',
        ]);
    }

    public function test_citizen_cannot_open_create_page_when_holding_valid_license(): void
    {
        $application = Application::query()->create([
            'application_no' => 'APP-2026-00001',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->toDateString(),
            'fee_amount_snapshot' => 1000,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        License::query()->create([
            'license_no' => 'LIC-2026-00001',
            'application_id' => $application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => Str::random(40),
        ]);

        $response = $this->actingAs($this->citizen)
            ->get(route('citizen.applications.create', $this->reservoir));

        $response->assertRedirect(route('catalogue.show', $this->reservoir));
        $response->assertSessionHasErrors(['licensing']);
    }

    public function test_citizen_cannot_submit_application_web_when_holding_valid_license(): void
    {
        $application = Application::query()->create([
            'application_no' => 'APP-2026-00002',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->toDateString(),
            'fee_amount_snapshot' => 1000,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        License::query()->create([
            'license_no' => 'LIC-2026-00002',
            'application_id' => $application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => Str::random(40),
        ]);

        $response = $this->actingAs($this->citizen)
            ->post(route('citizen.applications.store', $this->reservoir), [
                'category_id' => $this->category->id,
                'fishing_start_date' => now()->toDateString(),
                'payment_method' => '1bill',
                'accept_terms' => '1',
            ]);

        $response->assertSessionHasErrors(['reservoir']);
    }

    public function test_api_blocks_application_when_holding_valid_license(): void
    {
        $application = Application::query()->create([
            'application_no' => 'APP-2026-00003',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->toDateString(),
            'fee_amount_snapshot' => 1000,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        License::query()->create([
            'license_no' => 'LIC-2026-00003',
            'application_id' => $application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => Str::random(40),
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->postJson('/api/v1/licenses/applications', [
                'reservoir_id' => $this->reservoir->id,
                'category_id' => $this->category->id,
                'fishing_start_date' => now()->toDateString(),
                'payment_method' => '1bill',
                'accept_terms' => true,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'code' => 'ACTIVE_LICENSE_EXISTS',
        ]);
    }

    public function test_citizen_can_apply_when_previous_license_is_expired(): void
    {
        $application = Application::query()->create([
            'application_no' => 'APP-2026-00004',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->subDays(10)->toDateString(),
            'fishing_end_date' => now()->subDays(9)->toDateString(),
            'fee_amount_snapshot' => 1000,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        // License expired yesterday
        License::query()->create([
            'license_no' => 'LIC-2026-00004',
            'application_id' => $application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'expiry_date' => now()->subDay()->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => Str::random(40),
        ]);

        $response = $this->actingAs($this->citizen, 'sanctum')
            ->postJson('/api/v1/licenses/applications', [
                'reservoir_id' => $this->reservoir->id,
                'category_id' => $this->category->id,
                'fishing_start_date' => now()->toDateString(),
                'payment_method' => '1bill',
                'accept_terms' => true,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Application submitted');
    }
}
