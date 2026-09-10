<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CitizenProfile;
use App\Models\District;
use App\Models\LicenseCategory;
use App\Models\Office;
use App\Models\Reservoir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficerOfficeApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $restrictedStaffUser;
    private User $citizenUser;
    private District $districtA;
    private District $districtB;
    private Office $officeA;
    private Office $officeB;
    private Reservoir $reservoirA;
    private Reservoir $reservoirB;
    private LicenseCategory $category;
    private Application $appA;
    private Application $appB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffUser = User::factory()->create([
            'name' => 'Super Staff Officer',
            'email' => 'superstaff@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        $this->restrictedStaffUser = User::factory()->create([
            'name' => 'Restricted Staff Officer',
            'email' => 'restrictedstaff@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => false,
            'is_active' => true,
        ]);

        $this->citizenUser = User::factory()->create([
            'name' => 'Citizen Applicant',
            'email' => 'citizen@example.com',
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);

        $this->districtA = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->districtB = District::query()->create([
            'name' => 'Swabi',
            'code' => 'SWB',
            'is_active' => true,
        ]);

        $this->officeA = Office::query()->create([
            'district_id' => $this->districtA->id,
            'name' => 'Peshawar Fisheries Office',
            'is_active' => true,
        ]);

        $this->officeB = Office::query()->create([
            'district_id' => $this->districtB->id,
            'name' => 'Swabi Fisheries Office',
            'is_active' => true,
        ]);

        // Attach restricted staff user only to officeA and districtA
        $this->restrictedStaffUser->districts()->attach($this->districtA->id);
        $this->restrictedStaffUser->offices()->attach($this->officeA->id);

        $this->reservoirA = Reservoir::query()->create([
            'district_id' => $this->districtA->id,
            'office_id' => $this->officeA->id,
            'name' => 'Peshawar Canal',
            'water_body_type' => 'canal',
            'is_active' => true,
            'is_open_for_licensing' => true,
        ]);

        $this->reservoirB = Reservoir::query()->create([
            'district_id' => $this->districtB->id,
            'office_id' => $this->officeB->id,
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

        $this->appA = Application::query()->create([
            'application_no' => 'APP-OFFICE-001',
            'user_id' => $this->citizenUser->id,
            'applied_by' => $this->staffUser->id,
            'reservoir_id' => $this->reservoirA->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addDay()->toDateString(),
            'fee_amount_snapshot' => 500,
            'currency' => 'PKR',
            'status' => Application::STATUS_SUBMITTED,
            'payment_method' => 'counter_cash',
        ]);

        $this->appB = Application::query()->create([
            'application_no' => 'APP-OFFICE-002',
            'user_id' => $this->citizenUser->id,
            'applied_by' => $this->staffUser->id,
            'reservoir_id' => $this->reservoirB->id,
            'category_id' => $this->category->id,
            'fishing_start_date' => now()->toDateString(),
            'fishing_end_date' => now()->addDay()->toDateString(),
            'fee_amount_snapshot' => 500,
            'currency' => 'PKR',
            'status' => Application::STATUS_UNDER_REVIEW,
            'payment_method' => '1bill',
        ]);
    }

    public function test_staff_can_get_all_accessible_offices_applications(): void
    {
        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/applications');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 2);
    }

    public function test_restricted_staff_gets_only_their_office_applications(): void
    {
        $response = $this->actingAs($this->restrictedStaffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/applications');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.application_no', 'APP-OFFICE-001');
    }

    public function test_staff_can_filter_office_applications_by_office_id(): void
    {
        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/applications?office_id='.$this->officeB->id);

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.application_no', 'APP-OFFICE-002');
    }

    public function test_staff_can_get_single_office_applications(): void
    {
        $response = $this->actingAs($this->staffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/'.$this->officeA->id.'/applications');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.application_no', 'APP-OFFICE-001');
    }

    public function test_restricted_staff_cannot_access_other_office_applications(): void
    {
        // Try accessing via query parameter
        $response1 = $this->actingAs($this->restrictedStaffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/applications?office_id='.$this->officeB->id);

        $response1->assertStatus(403);
        $response1->assertJsonPath('message', 'Office is outside your assigned scope.');

        // Try accessing via path parameter
        $response2 = $this->actingAs($this->restrictedStaffUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/'.$this->officeB->id.'/applications');

        $response2->assertStatus(403);
        $response2->assertJsonPath('message', 'Office is outside your assigned scope.');
    }

    public function test_citizen_cannot_get_office_applications(): void
    {
        $response = $this->actingAs($this->citizenUser, 'sanctum')
            ->getJson('/api/v1/officer/offices/applications');

        $response->assertStatus(403);
    }
}
