<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Module;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private District $districtSwat;
    private District $districtPeshawar;
    private Office $officeSwat;
    private Office $officePeshawar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'user_type' => User::TYPE_SUPER_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        $this->districtSwat = District::query()->create([
            'name' => 'Swat',
            'code' => 'SWT',
            'is_active' => true,
        ]);

        $this->districtPeshawar = District::query()->create([
            'name' => 'Peshawar',
            'code' => 'PEW',
            'is_active' => true,
        ]);

        $this->officeSwat = Office::query()->create([
            'district_id' => $this->districtSwat->id,
            'name' => 'Swat District Fisheries Office',
            'is_active' => true,
        ]);

        $this->officePeshawar = Office::query()->create([
            'district_id' => $this->districtPeshawar->id,
            'name' => 'Peshawar Central Office',
            'is_active' => true,
        ]);

        Module::query()->create([
            'key' => 'users',
            'name' => 'User Management',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_user_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.users.create'));

        $response->assertStatus(200);
        $response->assertSee('Assigned Offices');
        $response->assertSee('Select All Offices');
        $response->assertSee('toggleSelectAllDistrictOffices');
        $response->assertSee('Swat District Fisheries Office');
        $response->assertSee('Peshawar Central Office');
    }

    public function test_store_staff_user_with_assigned_district_and_offices(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Officer Swat',
                'email' => 'swat.officer@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'user_type' => User::TYPE_ADMIN,
                'is_active' => '1',
                'all_districts' => '0',
                'districts' => [$this->districtSwat->id],
                'offices' => [$this->officeSwat->id],
            ]);

        $response->assertRedirect();

        $newUser = User::query()->where('email', 'swat.officer@example.com')->firstOrFail();
        $this->assertCount(1, $newUser->districts);
        $this->assertEquals($this->districtSwat->id, $newUser->districts->first()->id);

        $this->assertCount(1, $newUser->offices);
        $this->assertEquals($this->officeSwat->id, $newUser->offices->first()->id);
    }

    public function test_store_executive_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Executive Officer',
                'email' => 'executive@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'user_type' => User::TYPE_EXECUTIVE,
                'is_active' => '1',
                'all_districts' => '1',
            ]);

        $response->assertRedirect();

        $newUser = User::query()->where('email', 'executive@example.com')->firstOrFail();
        $this->assertTrue($newUser->isExecutive());
        $this->assertTrue($newUser->isAdminStaff());
        $this->assertEquals(User::TYPE_EXECUTIVE, $newUser->user_type);
    }

    public function test_store_user_with_cnic_and_profile_fields(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Profile Officer',
                'email' => 'profile.officer@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'user_type' => User::TYPE_ADMIN,
                'is_active' => '1',
                'all_districts' => '1',
                'cnic' => '17301-9999999-1',
                'father_name' => 'Officer Father',
                'dob' => '1990-01-01',
                'gender' => 'male',
                'address' => 'Peshawar Office Address',
                'emergency_contact' => '03001234567',
                'designation_label' => 'Inspector Fisheries',
            ]);

        $response->assertRedirect();

        $newUser = User::query()->where('email', 'profile.officer@example.com')->firstOrFail();
        $profile = $newUser->adminProfile;
        $this->assertNotNull($profile);
        $this->assertEquals('17301-9999999-1', $profile->cnic);
        $this->assertEquals('Officer Father', $profile->father_name);
        $this->assertEquals('1990-01-01', $profile->dob->format('Y-m-d'));
        $this->assertEquals('male', $profile->gender);
        $this->assertEquals('Peshawar Office Address', $profile->address);
        $this->assertEquals('03001234567', $profile->emergency_contact);
        $this->assertEquals('Inspector Fisheries', $profile->designation_label);
    }
}
