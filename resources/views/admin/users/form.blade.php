@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add staff user' : 'Edit staff user').' — '.config('app.name'))

@section('content')
@php
    $isEdit = $mode === 'edit';
    $actionLabels = [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'status' => 'Status',
        'approve' => 'Approve',
    ];

    $officesPayload = $offices->map(fn ($o) => [
        'id' => (int) $o->id,
        'name' => $o->name,
        'district_id' => (int) $o->district_id,
        'district_name' => $o->district?->name,
    ])->values()->all();

    $districtsPayload = $districts->map(fn ($d) => [
        'id' => (int) $d->id,
        'name' => $d->name,
    ])->values()->all();
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $isEdit ? 'Edit staff user' : 'Add staff user' }}</h1>
        <p class="text-secondary mb-0">Assign districts and module actions</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<form method="POST"
      action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}"
      x-data="staffUserForm({
          userType: '{{ old('user_type', $user->user_type ?? 'admin') }}',
          allDistricts: {{ old('all_districts', $user->all_districts) ? 'true' : 'false' }},
          selectedDistrictIds: @js(array_map('intval', old('districts', $selectedDistrictIds ?? []))),
          selectedOfficeIds: @js(array_map('intval', old('offices', $selectedOfficeIds ?? []))),
          offices: @js($officesPayload),
          districts: @js($districtsPayload),
          templates: @js($permissionTemplates['templates'] ?? []),
          moduleIds: @js($permissionTemplates['moduleIds'] ?? []),
          actions: @js($permissionTemplates['actions'] ?? [])
      })">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="bg-white rounded-4 shadow-sm p-4">
                <h2 class="h6 fw-bold mb-3">Account</h2>

                <div class="mb-3">
                    <label class="form-label" for="name">Full name</label>
                    <input id="name" name="name" type="text" class="form-control" required
                           value="{{ old('name', $user->name) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="form-control" required
                           value="{{ old('email', $user->email) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="mobile">Mobile</label>
                    <input id="mobile" name="mobile" type="text" class="form-control"
                           value="{{ old('mobile', $user->mobile ?? $user->adminProfile?->mobile) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="cnic">CNIC</label>
                    <input id="cnic" name="cnic" type="text" class="form-control"
                           placeholder="e.g. 12345-1234567-1"
                           value="{{ old('cnic', $user->adminProfile?->cnic) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="father_name">Father's Name</label>
                    <input id="father_name" name="father_name" type="text" class="form-control"
                           value="{{ old('father_name', $user->adminProfile?->father_name) }}">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="dob">Date of Birth</label>
                        <input id="dob" name="dob" type="date" class="form-control"
                               value="{{ old('dob', optional($user->adminProfile?->dob)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', $user->adminProfile?->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $user->adminProfile?->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $user->adminProfile?->gender) === 'other')>Other</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="address">Address</label>
                    <input id="address" name="address" type="text" class="form-control"
                           value="{{ old('address', $user->adminProfile?->address) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="emergency_contact">Emergency Contact</label>
                    <input id="emergency_contact" name="emergency_contact" type="text" class="form-control"
                           value="{{ old('emergency_contact', $user->adminProfile?->emergency_contact) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="designation_label">Designation</label>
                    <input id="designation_label" name="designation_label" type="text" class="form-control"
                           placeholder="e.g. District Officer Fisheries Swabi"
                           value="{{ old('designation_label', $user->adminProfile?->designation_label) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="district_id">Assigned Primary District (Profile)</label>
                    <select id="district_id" name="district_id" class="form-select">
                        <option value="">Select District</option>
                        @foreach ($districts as $d)
                            <option value="{{ $d->id }}" @selected(old('district_id', $user->adminProfile?->district_id) == $d->id)>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="user_type">Account type</label>
                    <select id="user_type" name="user_type" class="form-select" x-model="userType"
                        @if (! auth()->user()->isSuperAdmin()) disabled @endif>
                        <option value="admin">Admin (staff)</option>
                        <option value="executive">Executive</option>
                        @if (auth()->user()->isSuperAdmin())
                            <option value="super_admin">Super Admin</option>
                        @endif
                    </select>
                    @unless (auth()->user()->isSuperAdmin())
                        <input type="hidden" name="user_type" :value="userType">
                    @endunless
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password {{ $isEdit ? '(optional)' : '' }}</label>
                    <input id="password" name="password" type="password" class="form-control" @unless($isEdit) required @endunless minlength="8">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" @unless($isEdit) required @endunless minlength="8">
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $user->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3" x-show="!isSuper">
                <h2 class="h6 fw-bold mb-2">Permission template</h2>
                <p class="small text-secondary mb-3">Apply a preset, then adjust districts / checkboxes if needed.</p>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <template x-for="(tpl, key) in templates" :key="key">
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                @click="applyTemplate(key)"
                                x-text="tpl.label"></button>
                    </template>
                </div>
                <p class="small text-secondary mb-0" x-text="templateHint"></p>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4 mb-3" x-show="!isSuper">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">District scope</h2>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="all_districts"
                               name="all_districts" value="1" x-model="allDistricts"
                               @checked(old('all_districts', $user->all_districts))>
                        <label class="form-check-label" for="all_districts">All districts</label>
                    </div>
                </div>

                <div class="row g-2" x-show="!allDistricts" style="max-height: 260px; overflow:auto;">
                    @foreach ($districts as $district)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="districts[]"
                                       id="district_{{ $district->id }}" value="{{ $district->id }}"
                                       x-model.number="selectedDistricts"
                                       @change="onDistrictChange()">
                                <label class="form-check-label" for="district_{{ $district->id }}">{{ $district->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="small text-secondary mb-0" x-show="allDistricts">This user can access data for every district.</p>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4 mb-3" x-show="!isSuper">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Assigned Offices</h2>
                        <span class="small text-secondary" x-text="officeSummaryText"></span>
                    </div>
                    <div x-show="visibleDistrictGroups.length > 0">
                        <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" @click="toggleSelectAllGlobalOffices()">
                            <i class="bi" :class="areAllGlobalOfficesSelected ? 'bi-dash-square me-1' : 'bi-check-all me-1'"></i>
                            <span x-text="areAllGlobalOfficesSelected ? 'Deselect All Offices' : 'Select All Offices'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="visibleDistrictGroups.length === 0 && !allDistricts" class="text-center py-4 text-muted border rounded-3 bg-light">
                    <i class="bi bi-building-exclamation fs-3 d-block mb-1 text-secondary"></i>
                    <span class="small fw-medium">Please select a district above to view and assign its offices.</span>
                </div>

                <!-- Per-District Sections -->
                <div class="d-flex flex-column gap-3" x-show="visibleDistrictGroups.length > 0" style="max-height: 420px; overflow:auto;">
                    <template x-for="group in visibleDistrictGroups" :key="group.id">
                        <div class="border rounded-3 p-3 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-rflms text-white fw-bold px-2 py-1" x-text="group.name + ' District'"></span>
                                    <span class="small text-muted" x-text="'(' + group.selectedCount + '/' + group.totalCount + ' selected)'"></span>
                                </div>
                                <button type="button" class="btn btn-xs btn-outline-rflms py-1 px-2 text-nowrap small"
                                        @click="toggleSelectAllDistrictOffices(group.id)">
                                    <i class="bi me-1" :class="group.allSelected ? 'bi-dash-square' : 'bi-check-all'"></i>
                                    <span x-text="group.allSelected ? 'Deselect ' + group.name + ' Offices' : 'Select All ' + group.name + ' Offices'"></span>
                                </button>
                            </div>

                            <div class="row g-2">
                                <template x-for="office in group.offices" :key="office.id">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="offices[]"
                                                   :id="'office_' + office.id" :value="office.id"
                                                   x-model.number="selectedOffices">
                                            <label class="form-check-label small fw-medium" :for="'office_' + office.id" x-text="office.name"></label>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4 mb-3" x-show="isSuper">
                <h2 class="h6 fw-bold mb-2">Super Admin access</h2>
                <p class="small text-secondary mb-0">Unrestricted: all districts, all modules, all actions. Permission matrix is not required.</p>
            </div>

            <div class="bg-white rounded-4 shadow-sm p-4" x-show="!isSuper">
                <h2 class="h6 fw-bold mb-3">Module permissions</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Module</th>
                                @foreach ($actions as $action)
                                    <th class="text-center small">{{ $actionLabels[$action] ?? $action }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module)
                                @php
                                    $row = old('permissions.'.$module->id, $permissionMap[$module->id] ?? []);
                                @endphp
                                <tr>
                                    <td class="small fw-semibold">{{ $module->name }}</td>
                                    @foreach ($actions as $action)
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input perm-box"
                                                   data-module-id="{{ $module->id }}"
                                                   data-action="{{ $action }}"
                                                   name="permissions[{{ $module->id }}][{{ $action }}]"
                                                   value="1"
                                                   @checked(! empty($row[$action]))>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-rflms">{{ $isEdit ? 'Save changes' : 'Create user' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function staffUserForm(cfg) {
    return {
        userType: cfg.userType,
        allDistricts: cfg.allDistricts,
        selectedDistricts: (cfg.selectedDistrictIds || []).map(Number),
        selectedOffices: (cfg.selectedOfficeIds || []).map(Number),
        allOffices: cfg.offices || [],
        districtsList: cfg.districts || [],
        templates: cfg.templates || {},
        moduleIds: cfg.moduleIds || {},
        actions: cfg.actions || [],
        templateHint: 'No template applied yet.',

        get isSuper() {
            return this.userType === 'super_admin';
        },

        get visibleDistrictGroups() {
            let targetDistricts = [];
            if (this.allDistricts) {
                const districtIdsWithOffices = new Set(this.allOffices.map(o => Number(o.district_id)));
                targetDistricts = this.districtsList.filter(d => districtIdsWithOffices.has(Number(d.id)));
            } else if (this.selectedDistricts && this.selectedDistricts.length > 0) {
                const selectedSet = new Set(this.selectedDistricts.map(Number));
                targetDistricts = this.districtsList.filter(d => selectedSet.has(Number(d.id)));
            }

            const selectedOfficesSet = new Set(this.selectedOffices.map(Number));

            return targetDistricts.map(district => {
                const dId = Number(district.id);
                const districtOffices = this.allOffices.filter(o => Number(o.district_id) === dId);
                const allSelected = districtOffices.length > 0 && districtOffices.every(o => selectedOfficesSet.has(Number(o.id)));
                const selectedCount = districtOffices.filter(o => selectedOfficesSet.has(Number(o.id))).length;

                return {
                    id: dId,
                    name: district.name,
                    offices: districtOffices,
                    allSelected: allSelected,
                    selectedCount: selectedCount,
                    totalCount: districtOffices.length
                };
            }).filter(group => group.offices.length > 0);
        },

        get allVisibleOffices() {
            return this.visibleDistrictGroups.flatMap(g => g.offices);
        },

        get areAllGlobalOfficesSelected() {
            const visible = this.allVisibleOffices;
            if (visible.length === 0) return false;
            const selectedSet = new Set(this.selectedOffices.map(Number));
            return visible.every(o => selectedSet.has(Number(o.id)));
        },

        get officeSummaryText() {
            const groupsCount = this.visibleDistrictGroups.length;
            const allVisible = this.allVisibleOffices;
            const selectedSet = new Set(this.selectedOffices.map(Number));
            const selectedCount = allVisible.filter(o => selectedSet.has(Number(o.id))).length;

            if (this.allDistricts) {
                return `Showing ${groupsCount} district section(s) (${selectedCount}/${allVisible.length} offices assigned)`;
            }
            if (groupsCount === 0) {
                return 'Select a district above to view offices';
            }
            return `Showing ${groupsCount} district section(s) (${selectedCount}/${allVisible.length} offices assigned)`;
        },

        toggleSelectAllGlobalOffices() {
            const visibleIds = this.allVisibleOffices.map(o => Number(o.id));
            if (visibleIds.length === 0) return;

            if (this.areAllGlobalOfficesSelected) {
                const toRemove = new Set(visibleIds);
                this.selectedOffices = this.selectedOffices.filter(id => !toRemove.has(Number(id)));
            } else {
                const set = new Set(this.selectedOffices.map(Number));
                visibleIds.forEach(id => set.add(id));
                this.selectedOffices = Array.from(set);
            }
        },

        toggleSelectAllDistrictOffices(districtId) {
            const dId = Number(districtId);
            const districtOffices = this.allOffices.filter(o => Number(o.district_id) === dId);
            const officeIds = districtOffices.map(o => Number(o.id));
            if (officeIds.length === 0) return;

            const selectedSet = new Set(this.selectedOffices.map(Number));
            const allSelected = officeIds.every(id => selectedSet.has(id));

            if (allSelected) {
                const toRemove = new Set(officeIds);
                this.selectedOffices = this.selectedOffices.filter(id => !toRemove.has(Number(id)));
            } else {
                officeIds.forEach(id => selectedSet.add(id));
                this.selectedOffices = Array.from(selectedSet);
            }
        },

        onDistrictChange() {
            if (!this.allDistricts) {
                const visibleOfficeIds = new Set(this.allVisibleOffices.map(o => Number(o.id)));
                this.selectedOffices = this.selectedOffices.filter(id => visibleOfficeIds.has(Number(id)));
            }
        },

        applyTemplate(key) {
            const tpl = this.templates[key];
            if (!tpl) return;
            this.allDistricts = !!tpl.all_districts;
            document.querySelectorAll('.perm-box').forEach((el) => { el.checked = false; });
            Object.entries(tpl.modules || {}).forEach(([moduleKey, actions]) => {
                const id = this.moduleIds[moduleKey];
                if (!id) return;
                (actions || []).forEach((action) => {
                    const box = document.querySelector(`.perm-box[data-module-id="${id}"][data-action="${action}"]`);
                    if (box) box.checked = true;
                });
            });
            this.templateHint = tpl.label + ' — ' + (tpl.description || '');
        }
    };
}
</script>
@endpush
