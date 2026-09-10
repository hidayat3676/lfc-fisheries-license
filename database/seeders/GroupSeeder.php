<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupModulePermission;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure all configured modules exist in modules table
        foreach (config('rflms.modules', []) as $i => $mod) {
            Module::query()->updateOrCreate(
                ['key' => $mod['key']],
                [
                    'name' => $mod['name'],
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]
            );
        }

        $moduleMap = Module::query()->where('is_active', true)->pluck('id', 'key')->all();

        // 2. Default Groups and their permission templates
        $templates = config('rflms.permission_templates', []);

        foreach ($templates as $key => $template) {
            $group = Group::query()->updateOrCreate(
                ['name' => $template['label']],
                [
                    'description' => $template['description'] ?? null,
                    'is_active' => true,
                ]
            );

            // Populate permissions
            foreach ($template['modules'] ?? [] as $moduleKey => $actions) {
                $moduleId = $moduleMap[$moduleKey] ?? null;
                if (! $moduleId) {
                    continue;
                }

                $flags = [
                    'can_view' => in_array('view', $actions, true),
                    'can_create' => in_array('create', $actions, true),
                    'can_edit' => in_array('edit', $actions, true),
                    'can_delete' => in_array('delete', $actions, true),
                    'can_status' => in_array('status', $actions, true),
                    'can_approve' => in_array('approve', $actions, true),
                ];

                if (! $flags['can_view'] && in_array(true, $flags, true)) {
                    $flags['can_view'] = true;
                }

                GroupModulePermission::query()->updateOrCreate(
                    [
                        'group_id' => $group->id,
                        'module_id' => $moduleId,
                    ],
                    $flags
                );
            }
        }

        // 3. Assign DG group to existing non-superadmin staff users who don't have any groups yet
        $dgGroup = Group::query()->where('name', 'DG / Provincial')->first();
        if ($dgGroup) {
            $staffUsers = User::query()
                ->whereIn('user_type', [User::TYPE_ADMIN, User::TYPE_EXECUTIVE])
                ->whereDoesntHave('groups')
                ->get();

            foreach ($staffUsers as $user) {
                $user->groups()->syncWithoutDetaching([$dgGroup->id]);
            }
        }
    }
}
