<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupModulePermission;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Module $usersModule;
    private Module $applicationsModule;
    private Module $violationsModule;
    private Module $groupsModule;

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

        $this->usersModule = Module::query()->create([
            'key' => 'users',
            'name' => 'User Management',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->applicationsModule = Module::query()->create([
            'key' => 'applications',
            'name' => 'Licence Applications',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->violationsModule = Module::query()->create([
            'key' => 'violations',
            'name' => 'Violations',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $this->groupsModule = Module::query()->create([
            'key' => 'groups',
            'name' => 'Groups & Roles',
            'sort_order' => 4,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_view_groups_index_and_create_page(): void
    {
        $group = Group::query()->create([
            'name' => 'Test Inspectors',
            'description' => 'Field officers group',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.groups.index'));
        $response->assertStatus(200);
        $response->assertSee('Groups &amp; Roles', false);
        $response->assertSee('Test Inspectors');

        $createResponse = $this->actingAs($this->superAdmin)->get(route('admin.groups.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Add new group');
        $createResponse->assertSee('Module Permissions Matrix');
    }

    public function test_store_group_with_module_permissions(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.groups.store'), [
            'name' => 'Application Reviewers',
            'description' => 'Can view and approve applications',
            'is_active' => '1',
            'permissions' => [
                $this->applicationsModule->id => [
                    'view' => '1',
                    'approve' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.groups.index'));
        $this->assertDatabaseHas('groups', [
            'name' => 'Application Reviewers',
            'is_active' => true,
        ]);

        $group = Group::query()->where('name', 'Application Reviewers')->firstOrFail();
        $this->assertDatabaseHas('group_module_permissions', [
            'group_id' => $group->id,
            'module_id' => $this->applicationsModule->id,
            'can_view' => true,
            'can_approve' => true,
            'can_delete' => false,
        ]);
    }

    public function test_update_group_details_and_permissions(): void
    {
        $group = Group::query()->create([
            'name' => 'Old Name',
            'description' => 'Old description',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.groups.update', $group), [
            'name' => 'Updated Group Name',
            'description' => 'Updated description',
            'is_active' => '0',
            'permissions' => [
                $this->violationsModule->id => [
                    'view' => '1',
                    'create' => '1',
                    'status' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.groups.index'));
        $group->refresh();
        $this->assertEquals('Updated Group Name', $group->name);
        $this->assertFalse($group->is_active);

        $this->assertDatabaseHas('group_module_permissions', [
            'group_id' => $group->id,
            'module_id' => $this->violationsModule->id,
            'can_view' => true,
            'can_create' => true,
            'can_status' => true,
        ]);
    }

    public function test_delete_group(): void
    {
        $group = Group::query()->create([
            'name' => 'To Delete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.groups.destroy', $group));
        $response->assertRedirect(route('admin.groups.index'));
        $this->assertDatabaseMissing('groups', ['name' => 'To Delete']);
    }

    public function test_assigning_single_group_grants_permissions_to_user(): void
    {
        $group = Group::query()->create([
            'name' => 'Application Reviewers',
            'is_active' => true,
        ]);

        GroupModulePermission::query()->create([
            'group_id' => $group->id,
            'module_id' => $this->applicationsModule->id,
            'can_view' => true,
            'can_approve' => true,
        ]);

        $staffUser = User::factory()->create([
            'name' => 'Officer One',
            'email' => 'officer1@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        // Before assigning group: should not have permission
        $this->assertFalse($staffUser->hasModuleAction('applications', 'view'));
        $this->assertFalse($staffUser->hasModuleAction('applications', 'approve'));

        // Assign group
        $staffUser->groups()->attach($group->id);

        // After assigning group: should have granted permissions
        $this->assertTrue($staffUser->hasModuleAction('applications', 'view'));
        $this->assertTrue($staffUser->hasModuleAction('applications', 'approve'));
        $this->assertFalse($staffUser->hasModuleAction('applications', 'delete'));
        $this->assertFalse($staffUser->hasModuleAction('violations', 'view'));
    }

    public function test_assigning_multiple_groups_combines_permissions_for_user(): void
    {
        $groupApp = Group::query()->create([
            'name' => 'Application Managers',
            'is_active' => true,
        ]);
        GroupModulePermission::query()->create([
            'group_id' => $groupApp->id,
            'module_id' => $this->applicationsModule->id,
            'can_view' => true,
            'can_approve' => true,
        ]);

        $groupVio = Group::query()->create([
            'name' => 'Violation Handlers',
            'is_active' => true,
        ]);
        GroupModulePermission::query()->create([
            'group_id' => $groupVio->id,
            'module_id' => $this->violationsModule->id,
            'can_view' => true,
            'can_status' => true,
        ]);

        $staffUser = User::factory()->create([
            'name' => 'Multi Role Officer',
            'email' => 'multirole@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        // Assign both groups
        $staffUser->groups()->attach([$groupApp->id, $groupVio->id]);

        // Should have permissions from BOTH groups
        $this->assertTrue($staffUser->hasModuleAction('applications', 'view'));
        $this->assertTrue($staffUser->hasModuleAction('applications', 'approve'));
        $this->assertTrue($staffUser->hasModuleAction('violations', 'view'));
        $this->assertTrue($staffUser->hasModuleAction('violations', 'status'));

        // Should not have unassigned permissions
        $this->assertFalse($staffUser->hasModuleAction('users', 'view'));
        $this->assertFalse($staffUser->hasModuleAction('violations', 'delete'));
    }

    public function test_inactive_group_does_not_grant_permissions(): void
    {
        $inactiveGroup = Group::query()->create([
            'name' => 'Suspended Role',
            'is_active' => false,
        ]);
        GroupModulePermission::query()->create([
            'group_id' => $inactiveGroup->id,
            'module_id' => $this->applicationsModule->id,
            'can_view' => true,
            'can_approve' => true,
        ]);

        $staffUser = User::factory()->create([
            'name' => 'Inactive Group User',
            'email' => 'inactivegroup@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        $staffUser->groups()->attach($inactiveGroup->id);

        $this->assertFalse($staffUser->hasModuleAction('applications', 'view'));
        $this->assertFalse($staffUser->hasModuleAction('applications', 'approve'));
    }

    public function test_staff_without_group_permission_is_forbidden(): void
    {
        $staffUser = User::factory()->create([
            'name' => 'Restricted Staff',
            'email' => 'restricted@example.com',
            'user_type' => User::TYPE_ADMIN,
            'all_districts' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($staffUser)->get(route('admin.groups.index'));
        $response->assertStatus(403);
    }

    public function test_staff_user_store_and_update_with_groups(): void
    {
        $group1 = Group::query()->create(['name' => 'Group One', 'is_active' => true]);
        $group2 = Group::query()->create(['name' => 'Group Two', 'is_active' => true]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.store'), [
            'name' => 'New Staff With Groups',
            'email' => 'staffwithgroups@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => '1',
            'all_districts' => '1',
            'groups' => [$group1->id, $group2->id],
        ]);

        $response->assertRedirect();
        $user = User::query()->where('email', 'staffwithgroups@example.com')->firstOrFail();
        $this->assertCount(2, $user->groups);
        $this->assertTrue($user->groups->contains($group1));
        $this->assertTrue($user->groups->contains($group2));

        // Update user to only have group1
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('admin.users.update', $user), [
            'name' => 'New Staff With Groups Updated',
            'email' => 'staffwithgroups@example.com',
            'user_type' => User::TYPE_ADMIN,
            'is_active' => '1',
            'all_districts' => '1',
            'groups' => [$group1->id],
        ]);

        $updateResponse->assertRedirect();
        $user->refresh();
        $this->assertCount(1, $user->groups);
        $this->assertTrue($user->groups->contains($group1));
        $this->assertFalse($user->groups->contains($group2));
    }
}
