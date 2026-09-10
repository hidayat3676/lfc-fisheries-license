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

class LicenseVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private User $officer;
    private License $license;

    protected function setUp(): void
    {
        parent::setUp();

        $district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $reservoir = Reservoir::query()->create([
            'name' => 'Kabal Stream',
            'district_id' => $district->id,
            'water_body_type' => 'river',
            'trout_type' => 'trout',
            'is_active' => true,
            'is_open_for_licensing' => true,
        ]);

        $category = LicenseCategory::query()->create([
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
            'name' => 'Hidayat Khan',
            'user_type' => User::TYPE_CITIZEN,
            'mobile' => '03001234567',
            'is_active' => true,
        ]);

        $this->officer = User::factory()->create([
            'name' => 'Officer Ahmad',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => true,
        ]);

        CitizenProfile::query()->create([
            'user_id' => $this->citizen->id,
            'full_name' => 'Hidayat Ullah Khan',
            'father_name' => 'Muhammad Khan',
            'cnic' => '17301-1234567-1',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Peshawar City',
            'residence_district_id' => $district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03007654321',
        ]);

        $application = Application::query()->create([
            'application_no' => 'APP-2026-99999',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $reservoir->id,
            'category_id' => $category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->toDateString(),
            'fee_amount_snapshot' => 1000,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        $this->license = License::query()->create([
            'license_no' => 'LIC-2026-99999',
            'application_id' => $application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $reservoir->id,
            'category_id' => $category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => 'qr_token_test_12345',
        ]);
    }

    public function test_verify_page_by_license_id_shows_full_name(): void
    {
        $response = $this->get('/verify/licence/' . $this->license->id);

        $response->assertStatus(200);
        $response->assertSee('Hidayat Ullah Khan');
        $response->assertSee('S/O Muhammad Khan');
    }

    public function test_verify_page_by_qr_token_shows_full_name(): void
    {
        $response = $this->get('/verify/licence/' . $this->license->qr_token);

        $response->assertStatus(200);
        $response->assertSee('Hidayat Ullah Khan');
    }

    public function test_verify_page_by_license_number_shows_full_name(): void
    {
        $response = $this->get('/verify/licence/' . $this->license->license_no);

        $response->assertStatus(200);
        $response->assertSee('Hidayat Ullah Khan');
    }

    public function test_api_verify_by_license_id_returns_full_name_and_dates(): void
    {
        $response = $this->actingAs($this->officer, 'sanctum')
            ->getJson('/api/v1/officer/licenses/verify/' . $this->license->id);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'license_no' => 'LIC-2026-99999',
            'holder' => 'Hidayat Ullah Khan',
            'full_name' => 'Hidayat Ullah Khan',
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->toDateString(),
        ]);
    }

    public function test_api_verify_nonexistent_license_returns_404_json_error(): void
    {
        $response = $this->actingAs($this->officer, 'sanctum')
            ->getJson('/api/v1/officer/licenses/verify/NONEXISTENT-999');

        $response->assertStatus(404);
        $response->assertJson([
            'status' => 'error',
            'message' => 'No license found for the given license number.',
        ]);
    }
}
