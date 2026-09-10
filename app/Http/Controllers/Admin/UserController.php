<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffUserRequest;
use App\Http\Requests\Admin\UpdateStaffUserRequest;
use App\Models\District;
use App\Models\Module;
use App\Models\Office;
use App\Models\User;
use App\Models\UserModulePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->whereIn('user_type', [User::TYPE_SUPER_ADMIN, User::TYPE_ADMIN, User::TYPE_EXECUTIVE])
            ->with(['adminProfile', 'districts', 'offices', 'modulePermissions.module'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->orderByRaw("CASE user_type WHEN 'super_admin' THEN 0 WHEN 'executive' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User([
                'user_type' => User::TYPE_ADMIN,
                'is_active' => true,
                'all_districts' => false,
            ]),
            'districts' => District::query()->where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::query()->where('is_active', true)->with('district')->orderBy('name')->get(),
            'modules' => Module::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'actions' => config('rflms.actions'),
            'selectedDistrictIds' => [],
            'selectedOfficeIds' => [],
            'permissionMap' => [],
            'permissionTemplates' => $this->permissionTemplatesPayload(),
            'mode' => 'create',
        ]);
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'] ?? null,
                'password' => $data['password'],
                'user_type' => $data['user_type'],
                'all_districts' => $data['user_type'] === User::TYPE_SUPER_ADMIN
                    ? true
                    : (bool) ($data['all_districts'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'email_verified_at' => now(),
            ]);

            $user->adminProfile()->create([
                'full_name' => $data['name'],
                'father_name' => $data['father_name'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'dob' => $data['dob'] ?? null,
                'gender' => $data['gender'] ?? null,
                'designation_label' => $data['designation_label'] ?? null,
                'district_id' => $data['district_id'] ?? ($data['districts'][0] ?? null),
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
            ]);

            $this->syncAccess($user, $data);

            return $user;
        });

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Staff user created.');
    }

    public function edit(User $user): View
    {
        $this->ensureStaffUser($user);

        $user->load(['adminProfile', 'districts', 'offices', 'modulePermissions']);

        $permissionMap = [];
        foreach ($user->modulePermissions as $perm) {
            $permissionMap[$perm->module_id] = [
                'view' => $perm->can_view,
                'create' => $perm->can_create,
                'edit' => $perm->can_edit,
                'delete' => $perm->can_delete,
                'status' => $perm->can_status,
                'approve' => $perm->can_approve,
            ];
        }

        return view('admin.users.form', [
            'user' => $user,
            'districts' => District::query()->where('is_active', true)->orderBy('name')->get(),
            'offices' => Office::query()->where('is_active', true)->with('district')->orderBy('name')->get(),
            'modules' => Module::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'actions' => config('rflms.actions'),
            'selectedDistrictIds' => $user->districts->pluck('id')->all(),
            'selectedOfficeIds' => $user->offices->pluck('id')->all(),
            'permissionMap' => $permissionMap,
            'permissionTemplates' => $this->permissionTemplatesPayload(),
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateStaffUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureStaffUser($user);
        $data = $request->validated();

        if ($user->id === $request->user()->id && empty($data['is_active'])) {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        if ($user->isSuperAdmin() && $data['user_type'] !== User::TYPE_SUPER_ADMIN) {
            $otherSupers = User::query()
                ->where('user_type', User::TYPE_SUPER_ADMIN)
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->count();
            if ($otherSupers === 0) {
                return back()->withErrors(['user_type' => 'Cannot demote the only active Super Admin.']);
            }
        }

        DB::transaction(function () use ($user, $data) {
            $payload = [
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'mobile' => $data['mobile'] ?? null,
                'user_type' => $data['user_type'],
                'all_districts' => $data['user_type'] === User::TYPE_SUPER_ADMIN
                    ? true
                    : (bool) ($data['all_districts'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? false),
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            $user->adminProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $data['name'],
                    'father_name' => $data['father_name'] ?? null,
                    'cnic' => $data['cnic'] ?? null,
                    'dob' => $data['dob'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'designation_label' => $data['designation_label'] ?? null,
                    'district_id' => $data['district_id'] ?? ($data['districts'][0] ?? null),
                    'mobile' => $data['mobile'] ?? null,
                    'address' => $data['address'] ?? null,
                    'emergency_contact' => $data['emergency_contact'] ?? null,
                ]
            );

            $this->syncAccess($user, $data);
        });

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Staff user updated.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncAccess(User $user, array $data): void
    {
        if ($user->user_type === User::TYPE_SUPER_ADMIN) {
            $user->districts()->sync([]);
            $user->offices()->sync([]);
            $user->modulePermissions()->delete();

            return;
        }

        $districtIds = ! empty($data['all_districts'])
            ? []
            : array_map('intval', $data['districts'] ?? []);
        $user->districts()->sync($districtIds);

        $officeIds = array_map('intval', $data['offices'] ?? []);
        $user->offices()->sync($officeIds);

        $user->modulePermissions()->delete();
        $permissions = $data['permissions'] ?? [];

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

            UserModulePermission::query()->create([
                'user_id' => $user->id,
                'module_id' => $moduleId,
                ...$flags,
            ]);
        }
    }

    private function ensureStaffUser(User $user): void
    {
        if (! $user->isAdminStaff()) {
            abort(404);
        }
    }

    /**
     * @return array{templates: array<string, mixed>, moduleIds: array<string, int>}
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
