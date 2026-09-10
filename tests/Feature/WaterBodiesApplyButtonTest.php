<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Reservoir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterBodiesApplyButtonTest extends TestCase
{
    use RefreshDatabase;

    private District $district;
    private Reservoir $openReservoir;
    private Reservoir $closedReservoir;
    private User $citizen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->district = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->openReservoir = Reservoir::query()->create([
            'name' => 'Warsak Dam',
            'district_id' => $this->district->id,
            'water_body_type' => 'dam',
            'trout_type' => 'non_trout',
            'is_open_for_licensing' => true,
            'is_active' => true,
        ]);

        $this->closedReservoir = Reservoir::query()->create([
            'name' => 'Restricted Reservoir',
            'district_id' => $this->district->id,
            'water_body_type' => 'dam',
            'trout_type' => 'non_trout',
            'is_open_for_licensing' => false,
            'is_active' => true,
        ]);

        $this->citizen = User::factory()->create([
            'name' => 'Citizen User',
            'email' => 'citizen@test.com',
            'user_type' => User::TYPE_CITIZEN,
            'is_active' => true,
        ]);

        $this->citizen->citizenProfile()->create([
            'full_name' => 'Citizen User',
            'father_name' => 'Father',
            'cnic' => '17301-1234567-1',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Peshawar',
            'residence_district_id' => $this->district->id,
            'province' => 'Khyber Pakhtunkhwa',
            'emergency_contact' => '03001234567',
            'photo_path' => 'profiles/test.jpg',
        ]);
    }

    public function test_catalogue_page_displays_apply_button_for_open_water_bodies(): void
    {
        $response = $this->actingAs($this->citizen)->get('/water-bodies');

        $response->assertStatus(200);
        $response->assertSee('Warsak Dam');
        $response->assertSee(route('citizen.applications.create', $this->openReservoir));
        $response->assertSee('Apply');
        $response->assertSee('Details');
    }

    public function test_catalogue_page_displays_not_open_for_closed_water_bodies(): void
    {
        $response = $this->actingAs($this->citizen)->get('/water-bodies');

        $response->assertStatus(200);
        $response->assertSee('Restricted Reservoir');
        $response->assertSee('Not Open');
    }

    public function test_guest_user_sees_apply_button_linking_to_application_or_login(): void
    {
        $response = $this->get('/water-bodies');

        $response->assertStatus(200);
        $response->assertSee(route('citizen.applications.create', $this->openReservoir));
        $response->assertSee('Apply');
    }

    public function test_api_reservoirs_includes_allows_e_licence_attribute(): void
    {
        $response = $this->getJson('/api/v1/reservoirs');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $this->openReservoir->id,
            'allows_e_licence' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $this->closedReservoir->id,
            'allows_e_licence' => false,
        ]);
    }
}
