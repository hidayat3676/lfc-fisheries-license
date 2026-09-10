<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalkinRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;
    private User $citizenUser;
    private District $district;

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
    }

    public function test_staff_officer_can_perform_walkin_citizen_registration(): void
    {
        $payload = [
            'full_name' => 'Walkin Citizen',
            'father_name' => 'Father Name',
            'email' => 'walkin@example.com',
            'mobile' => '03001234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cnic' => '17301-7654321-9',
            'dob' => '1992-08-20',
            'gender' => 'male',
            'address' => 'Peshawar Cantt',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03009876543',
        ];

        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-registration', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.user.email', 'walkin@example.com');
        $response->assertJsonPath('data.user.profile_complete', true);
        $response->assertJsonPath('data.profile.cnic', '17301-7654321-9');

        $newUser = User::query()->where('email', 'walkin@example.com')->firstOrFail();
        $this->assertNotNull($newUser->email_verified_at);
        $this->assertTrue($newUser->hasCompleteCitizenProfile());
    }

    public function test_walkin_registration_validates_required_fields(): void
    {
        $response = $this->actingAs($this->staffUser)
            ->postJson('/api/v1/officer/walkin-registration', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'full_name',
            'father_name',
            'email',
            'mobile',
            'password',
            'cnic',
            'dob',
            'gender',
            'address',
            'residence_district_id',
            'province',
            'emergency_contact',
        ]);
    }

    public function test_non_staff_user_cannot_perform_walkin_registration(): void
    {
        $response = $this->actingAs($this->citizenUser)
            ->postJson('/api/v1/officer/walkin-registration', []);

        $response->assertStatus(403);
    }
}
