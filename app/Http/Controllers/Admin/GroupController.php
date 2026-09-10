<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Requests\Admin\UpdateGroupRequest;
use App\Models\Group;
use App\Models\GroupModulePermission;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(Request $request): View
    {
        $groups = Group::query()
            ->withCount('users')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'ilike', $term)
                        ->orWhere('description', 'ilike', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('admin.groups.form', [
            'group' => new Group(['is_active' => true]),
            'modules' => Module::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'actions' => config('rflms.actions', []),
            'permissionMap' => [],
            'permissionTemplates' => $this->permissionTemplatesPayload(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $group = Group::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncPermissions($group, $data['permissions'] ?? []);
        });

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Group created successfully.');
    }

    public function edit(Group $group): View
    {
        $group->load(['modulePermissions', 'users.adminProfile']);

        $permissionMap = [];
        foreach ($group->modulePermissions as $perm) {
            $permissionMap[$perm->module_id] = [
                'view' => $perm->can_view,
                'create' => $perm->can_create,
                'edit' => $perm->can_edit,
                'delete' => $perm->can_delete,
                'status' => $perm->can_status,
                'approve' => $perm->can_approve,
            ];
        }

        return view('admin.groups.form', [
            'group' => $group,
            'modules' => Module::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'actions' => config('rflms.actions', []),
            'permissionMap' => $permissionMap,
            'permissionTemplates' => $this->permissionTemplatesPayload(),
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($group, $data) {
            $group->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $this->syncPermissions($group, $data['permissions'] ?? []);
        });

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Group updated successfully.');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $group->delete();

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Group deleted.');
    }

    /**
     * @param array<int|string, array<string, mixed>> $permissions
     */
    private function syncPermissions(Group $group, array $permissions): void
    {
        $group->modulePermissions()->delete();

        foreach ($permissions as $moduleId => $actions) {
            $moduleId = (int) $moduleId;
            if ($moduleId < 1 || ! is_array($actions)) {
                continue;
            }

            $flags = [
                'can_view' => ! empty($actions['view']),
                'can_create' => ! empty($actions['create']),
                'can_edit' => ! empty($actions['edit']),
                'can_delete' => ! empty($actions['delete']),
                'can_status' => ! empty($actions['status']),
                'can_approve' => ! empty($actions['approve']),
            ];

            if (! in_array(true, $flags, true)) {
                continue;
            }

            // Viewing is implied if any other action is granted
            if (! $flags['can_view']) {
                $flags['can_view'] = true;
            }

            GroupModulePermission::query()->create([
                'group_id' => $group->id,
                'module_id' => $moduleId,
                ...$flags,
            ]);
        }
    }

    /**
     * @return array{templates: array<string, mixed>, moduleIds: array<string, int>, actions: list<string>}
     */
    private function permissionTemplatesPayload(): array
    {
        $moduleIds = Module::query()
            ->where('is_active', true)
            ->pluck('id', 'key')
            ->all();

        return [
            'templates' => config('rflms.permission_templates', []),
            'moduleIds' => $moduleIds,
            'actions' => config('rflms.actions', []),
        ];
    }
}
