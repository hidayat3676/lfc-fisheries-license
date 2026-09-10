<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\District;
use App\Models\License;
use App\Models\LicenseCategory;
use App\Models\Office;
use App\Models\Reservoir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExecutiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $executiveUser;
    private User $staffUser;
    private User $citizenUser;
    private District $district;
    private Reservoir $reservoir;
    private LicenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executiveUser = User::factory()->create([
            'name' => 'Executive Officer',
            'email' => 'exec@example.com',
            'user_type' => User::TYPE_EXECUTIVE,
            'is_active' => true,
        ]);

        $this->staffUser = User::factory()->create([
            'name' => 'Staff Admin',
            'email' => 'admin@example.com',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => true,
        ]);

        $this->citizenUser = User::factory()->create([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);

        $this->district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        Office::query()->create([
            'district_id' => $this->district->id,
            'name' => 'Peshawar Central Office',
            'code' => 'PCO',
            'is_active' => true,
        ]);

        $this->reservoir = Reservoir::query()->create([
            'district_id' => $this->district->id,
            'name' => 'Kabal River',
            'is_active' => true,
        ]);

        $this->category = LicenseCategory::query()->create([
            'name' => 'Angling',
            'code' => 'ANG',
            'duration_type' => 'monthly',
            'fee_amount' => 1500,
            'validity_days' => 30,
            'is_active' => true,
        ]);

        // Approved application (Revenue = 1500)
        $approvedApp = Application::query()->create([
            'application_no' => 'APP-1001',
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addDays(30)->toDateString(),
            'fee_amount_snapshot' => 1500.00,
            'currency' => 'PKR',
            'status' => Application::STATUS_APPROVED,
        ]);

        License::query()->create([
            'license_no' => 'LIC-1001',
            'application_id' => $approvedApp->id,
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'issue_date' => now()->toDateString(),
            'expiry_date' => now()->addDays(30)->toDateString(),
            'status' => License::STATUS_ACTIVE,
            'qr_token' => 'QR1001TokenHashStr',
        ]);

        // Pending application
        Application::query()->create([
            'application_no' => 'APP-1002',
            'user_id' => $this->citizenUser->id,
            'reservoir_id' => $this->reservoir->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addDays(30)->toDateString(),
            'fee_amount_snapshot' => 1500.00,
            'currency' => 'PKR',
            'status' => Application::STATUS_SUBMITTED,
        ]);
    }

    public function test_executive_user_can_access_dashboard_metrics(): void
    {
        $response = $this->actingAs($this->executiveUser)
            ->getJson('/api/v1/executive/dashboard');

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        // All Time Metrics Assertions
        $response->assertJsonPath('metrics.all_time.all_time_licenses_issued_count', 1);
        $response->assertJsonPath('metrics.all_time.pending_licenses_count', 1);
        $response->assertJsonPath('metrics.all_time.all_districts_offices_count', 1);
        $response->assertJsonPath('metrics.all_time.districts_count', 1);
        $response->assertJsonPath('metrics.all_time.all_time_revenue', 1500);

        // District Breakdown Assertion
        $response->assertJsonPath('metrics.district_breakdown.0.district_name', 'Peshawar');
        $response->assertJsonPath('metrics.district_breakdown.0.total_revenue', 1500);
    }

    public function test_executive_dashboard_supports_period_filtering(): void
    {
        // Monthly filter
        $responseMonthly = $this->actingAs($this->executiveUser)
            ->getJson('/api/v1/executive/dashboard?period=monthly');

        $responseMonthly->assertStatus(200);
        $responseMonthly->assertJsonPath('filter.period', 'monthly');
        $responseMonthly->assertJsonPath('metrics.filtered.licenses_issued_count', 1);
        $responseMonthly->assertJsonPath('metrics.filtered.revenue_generated', 1500);

        // Custom date range filter
        $responseCustom = $this->actingAs($this->executiveUser)
            ->getJson('/api/v1/executive/dashboard?date_from='.now()->subDays(2)->toDateString().'&date_to='.now()->addDays(2)->toDateString());

        $responseCustom->assertStatus(200);
        $responseCustom->assertJsonPath('metrics.filtered.licenses_issued_count', 1);
    }

    public function test_non_executive_user_is_forbidden(): void
    {
        // Admin staff user attempt
        $responseStaff = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/executive/dashboard');

        $responseStaff->assertStatus(403);

        // Citizen user attempt
        $responseCitizen = $this->actingAs($this->citizenUser)
            ->getJson('/api/v1/executive/dashboard');

        $responseCitizen->assertStatus(403);
    }
}
