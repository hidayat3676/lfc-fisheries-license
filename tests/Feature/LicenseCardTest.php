<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CitizenProfile;
use App\Models\District;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Models\Reservoir;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseCardTest extends TestCase
{
    use RefreshDatabase;

    private User $citizen;
    private User $otherCitizen;
    private License $license;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $district = District::query()->create([
            'name' => 'Swat',
            'code' => 'SWT',
            'is_active' => true,
        ]);

        $reservoir = Reservoir::query()->create([
            'name' => 'Swat River Basin',
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
            'duration_days' => 7,
            'fee_amount' => 1500,
            'currency' => 'PKR',
            'expiry_rule' => 'from_start',
            'is_active' => true,
        ]);

        $this->citizen = User::factory()->create([
            'name' => 'Ahmad Khan',
            'user_type' => User::TYPE_CITIZEN,
            'mobile' => '03001234567',
            'is_active' => true,
        ]);

        CitizenProfile::query()->create([
            'user_id' => $this->citizen->id,
            'full_name' => 'Ahmad Khan',
            'father_name' => 'Usman Khan',
            'cnic' => '15302-1234567-1',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Mingora, Swat',
            'residence_district_id' => $district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03007654321',
        ]);

        $this->otherCitizen = User::factory()->create([
            'name' => 'Other Angler',
            'user_type' => User::TYPE_CITIZEN,
            'mobile' => '03007654321',
            'is_active' => true,
        ]);

        $startDate = Carbon::parse('2026-09-15');
        $endDate = Carbon::parse('2026-09-22');
        $issueDate = Carbon::parse('2026-09-10');

        $this->application = Application::query()->create([
            'application_no' => 'APP-2026-10001',
            'user_id' => $this->citizen->id,
            'reservoir_id' => $reservoir->id,
            'category_id' => $category->id,
            'fishing_start_date' => $startDate->toDateString(),
            'fishing_end_date' => $endDate->toDateString(),
            'fee_amount_snapshot' => 1500,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        $this->license = License::query()->create([
            'license_no' => 'LIC-2026-10001',
            'application_id' => $this->application->id,
            'user_id' => $this->citizen->id,
            'reservoir_id' => $reservoir->id,
            'category_id' => $category->id,
            'issue_date' => $issueDate->toDateString(),
            'expiry_date' => $endDate->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => 'qr_token_test_abc123',
        ]);
    }

    public function test_license_card_renders_front_side_with_fishing_start_date(): void
    {
        $response = $this->actingAs($this->citizen)
            ->get(route('citizen.licenses.card', $this->license));

        $response->assertStatus(200);
        $response->assertSee('FRONT SIDE');
        $response->assertSee('Issue Date:');
        $response->assertSee('10 Sep 2026');
        $response->assertSee('Fishing Start Date:');
        $response->assertSee('15 Sep 2026');
        $response->assertSee('Expiry Date:');
        $response->assertSee('22 Sep 2026');
    }

    public function test_license_model_provides_fishing_start_date_accessor(): void
    {
        $this->assertEquals('2026-09-15', $this->license->fishing_start_date?->toDateString());

        // Test fallback to issue date when license has no linked application
        $licenseWithoutApp = new License([
            'issue_date' => '2026-09-10',
            'expiry_date' => '2026-09-17',
        ]);
        $this->assertEquals('2026-09-10', $licenseWithoutApp->fishing_start_date?->toDateString());
    }

    public function test_other_citizen_cannot_view_license_card(): void
    {
        $response = $this->actingAs($this->otherCitizen)
            ->get(route('citizen.licenses.card', $this->license));

        $response->assertStatus(403);
    }
}
