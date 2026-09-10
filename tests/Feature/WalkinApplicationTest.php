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
use Tests\TestCase;

class WalkinApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $citizenUser;
    private CitizenProfile $profile;
    private District $district;
    private Reservoir $reservoir;
    private LicenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'name' => 'Staff Officer',
            'email' => 'officer@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        $this->citizenUser = User::factory()->create([
            'name' => 'Citizen Applicant',
            'email' => 'citizen@example.com',
            'mobile' => '03001234567',
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);

        $this->district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->profile = CitizenProfile::query()->create([
            'user_id' => $this->citizenUser->id,
            'full_name' => 'Muhammad Ali',
            'father_name' => 'Khan Ali',
            'cnic' => '17301-1234567-1',
            'dob' => '1995-05-15',
            'gender' => 'male',
            'address' => 'Peshawar City',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03001234567',
        ]);

        $this->reservoir = Reservoir::query()->create([
            'district_id' => $this->district->id,
            'name' => 'Kundal Dam',
            'water_body_type' => 'dam',
            'is_active' => true,
            'is_open_for_licensing' => true,
        ]);

        $this->category = LicenseCategory::query()->create([
            'code' => 'DAILY',
            'name' => 'Daily Fishing Licence',
            'duration_type' => 'daily',
            'duration_days' => 1,
            'fee_amount' => 500,
            'currency' => 'PKR',
            'is_active' => true,
        ]);
    }

    public function test_staff_can_create_walkin_application_and_auto_approve_counter_cash(): void
    {
        $payload = [
            'cnic' => '17301-1234567-1',
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => 'counter_cash',
            'officer_remarks' => 'Walk-in cash payment collected.',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-application', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.application.user_id', $this->citizenUser->id);
        $response->assertJsonPath('data.application.applied_by.id', $this->staffUser->id);
        $response->assertJsonPath('data.application.applied_by_name', 'Staff Officer');
        $response->assertJsonPath('data.application.status', Application::STATUS_APPROVED);
        $response->assertJsonPath('data.license.status', License::STATUS_ACTIVE);

        $this->assertDatabaseHas('applications', [
            'user_id' => $this->citizenUser->id,
            'applied_by' => $this->staffUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'payment_method' => 'counter_cash',
            'status' => Application::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('licenses', [
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'status' => License::STATUS_ACTIVE,
        ]);
    }

    public function test_staff_can_create_walkin_application_by_unformatted_cnic(): void
    {
        $payload = [
            'cnic' => '1730112345671', // unformatted
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => '1bill',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-application', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.application.user_id', $this->citizenUser->id);
        $response->assertJsonPath('data.application.status', Application::STATUS_UNDER_REVIEW);
    }

    public function test_walkin_application_returns_404_when_cnic_not_registered(): void
    {
        $payload = [
            'cnic' => '99999-9999999-9',
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => 'counter_cash',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-application', $payload);

        $response->assertStatus(404);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'No user record found for the provided CNIC. Please register citizen first.');
    }

    public function test_walkin_application_prevents_duplicate_active_license(): void
    {
        $app = Application::query()->create([
            'application_no' => 'APP-2026-TEST',
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addDays(1)->toDateString(),
            'fee_amount_snapshot' => 500,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        // Issue active license
        License::query()->create([
            'application_id' => $app->id,
            'license_no' => 'LIC-2026-TEST1',
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(5)->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => 'TESTQRTOKEN123',
        ]);

        $payload = [
            'cnic' => '17301-1234567-1',
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => 'counter_cash',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-application', $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'ACTIVE_LICENSE_EXISTS');
    }

    public function test_non_staff_user_cannot_submit_walkin_application(): void
    {
        $payload = [
            'cnic' => '17301-1234567-1',
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => 'counter_cash',
        ];

        $response = $this->actingAs($this->citizenUser)
            ->postJson('/api/v1/officer/walkin-application', $payload);

        $response->assertStatus(403);
    }

    public function test_staff_can_get_list_of_their_applied_applications(): void
    {
        // Submit walkin application as staff
        $payload = [
            'cnic' => '17301-1234567-1',
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'payment_method' => 'counter_cash',
        ];

        $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-application', $payload)
            ->assertStatus(201);

        $response = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/officer/applications');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.applied_by.id', $this->staffUser->id);
        $response->assertJsonPath('data.0.applied_by_name', 'Staff Officer');
    }

    public function test_staff_can_approve_application_via_api(): void
    {
        $application = \App\Models\Application::query()->create([
            'application_no' => 'APP-2026-00099',
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'status' => \App\Models\Application::STATUS_UNDER_REVIEW,
            'payment_method' => 'counter_cash',
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addMonth()->toDateString(),
            'fee_amount_snapshot' => $this->category->fee_amount,
            'currency' => 'PKR',
        ]);

        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->postJson("/api/v1/officer/applications/{$application->id}/approve", [
                'officer_remarks' => 'Approved by staff via API.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.application.status', \App\Models\Application::STATUS_APPROVED);
        $response->assertJsonPath('data.application.officer_remarks', 'Approved by staff via API.');
        $this->assertNotNull($response->json('data.license.license_no'));

        $this->assertDatabaseHas('licenses', [
            'application_id' => $application->id,
            'issued_by' => $this->staffUser->id,
        ]);
    }
}
