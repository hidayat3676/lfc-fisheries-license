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

class OfficerCnicSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $citizenUser;
    private CitizenProfile $profile;

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
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);

        $district = District::query()->create([
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
            'residence_district_id' => $district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03001234567',
        ]);
    }

    public function test_citizen_profile_find_by_cnic_model_method(): void
    {
        // Exact formatted match
        $found = CitizenProfile::findByCnic('17301-1234567-1');
        $this->assertNotNull($found);
        $this->assertEquals($this->profile->id, $found->id);

        // Unformatted digits match
        $foundUnformatted = CitizenProfile::findByCnic('1730112345671');
        $this->assertNotNull($foundUnformatted);
        $this->assertEquals($this->profile->id, $foundUnformatted->id);

        // User model findByCnic
        $userFound = User::findByCnic('17301-1234567-1');
        $this->assertNotNull($userFound);
        $this->assertEquals($this->citizenUser->id, $userFound->id);
    }

    public function test_staff_officer_can_search_record_by_cnic_via_get_and_post(): void
    {
        // GET request
        $responseGet = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/officer/search-cnic?cnic=17301-1234567-1');

        $responseGet->assertStatus(200);
        $responseGet->assertJsonPath('status', 'success');
        $responseGet->assertJsonPath('data.profile.cnic', '17301-1234567-1');
        $responseGet->assertJsonPath('data.profile.full_name', 'Muhammad Ali');
        $responseGet->assertJsonPath('data.user.email', 'citizen@example.com');

        // POST request unformatted
        $responsePost = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/search-cnic', [
                'cnic' => '1730112345671',
            ]);

        $responsePost->assertStatus(200);
        $responsePost->assertJsonPath('status', 'success');
        $responsePost->assertJsonPath('data.profile.cnic', '17301-1234567-1');
    }

    public function test_search_by_cnic_returns_404_when_record_not_found(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/officer/search-cnic?cnic=99999-9999999-9');

        $response->assertStatus(404);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'No record found for the provided CNIC.');
    }

    public function test_non_staff_user_cannot_access_cnic_search_api(): void
    {
        $response = $this->actingAs($this->citizenUser)
            ->getJson('/api/v1/officer/search-cnic?cnic=17301-1234567-1');

        $response->assertStatus(403);
    }
}
