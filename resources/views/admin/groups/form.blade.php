@extends('layouts.admin')

@section('title', ($mode === 'create' ? 'Add group' : 'Edit group') . ' — ' . config('app.name'))

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
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $isEdit ? 'Edit group' : 'Add new group' }}</h1>
        <p class="text-secondary mb-0">Configure group details and define module permissions</p>
    </div>
    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to groups
    </a>
</div>

<form method="POST"
      action="{{ $isEdit ? route('admin.groups.update', $group) : route('admin.groups.store') }}"
      x-data="groupForm({
          templates: @js($permissionTemplates['templates'] ?? []),
          moduleIds: @js($permissionTemplates['moduleIds'] ?? []),
          actions: @js($permissionTemplates['actions'] ?? [])
      })">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <!-- Left Column: Details -->
        <div class="col-lg-4">
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <h2 class="h6 fw-bold mb-3">Group Details</h2>

                <div class="mb-3">
                    <label class="form-label" for="name">Group Name <span class="text-danger">*</span></label>
                    <input id="name" name="name" type="text" class="form-control" required
                           placeholder="e.g. Field Warden / Regional Inspector"
                           value="{{ old('name', $group->name) }}">
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Brief summary of duties and access level...">{{ old('description', $group->description) }}</textarea>
                    @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                           @checked(old('is_active', $group->is_active ?? true))>
                    <label class="form-check-label fw-medium" for="is_active">Active Group</label>
                    <div class="small text-secondary">Inactive groups will not grant permissions to assigned users.</div>
                </div>
            </div>

            <!-- Permission Template Presets -->
            <div class="bg-white rounded-4 shadow-sm p-4 mb-3">
                <h2 class="h6 fw-bold mb-2">Preset Templates</h2>
                <p class="small text-secondary mb-3">Quickly pre-fill the permissions matrix using predefined operational roles:</p>
                <div class="d-flex flex-column gap-2">
                    <template x-for="(tmpl, key) in templates" :key="key">
                        <button type="button" class="btn btn-sm btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2"
                                @click="applyTemplate(key)">
                            <div>
                                <div class="fw-semibold text-dark" x-text="tmpl.label"></div>
                                <div class="small text-muted" style="font-size:0.75rem;" x-text="tmpl.description"></div>
                            </div>
                            <i class="bi bi-arrow-right-short fs-5 text-secondary"></i>
                        </button>
                    </template>
                </div>
            </div>

            @if ($isEdit && $group->users->isNotEmpty())
                <div class="bg-white rounded-4 shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 fw-bold mb-0">Assigned Users</h2>
                        <span class="badge bg-light text-dark border">{{ $group->users->count() }}</span>
                    </div>
                    <ul class="list-unstyled mb-0 small" style="max-height: 240px; overflow-y: auto;">
                        @foreach ($group->users as $member)
                            <li class="py-1.5 border-bottom d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium text-dark">{{ $member->name }}</span>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ $member->email }}</div>
                                </div>
                                <a href="{{ route('admin.users.edit', $member) }}" class="btn btn-xs btn-outline-secondary py-0 px-1 text-decoration-none">
                                    Edit
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Right Column: Module Permissions Matrix -->
        <div class="col-lg-8">
            <div class="bg-white rounded-4 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h6 fw-bold mb-0">Module Permissions Matrix</h2>
                        <span class="small text-secondary">Specify allowed capabilities across system areas for this group</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="toggleSelectAll(true)">
                            <i class="bi bi-check-all me-1"></i> Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleSelectAll(false)">
                            <i class="bi bi-x me-1"></i> Clear All
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 32%;">Module</th>
                                @foreach ($actions as $action)
                                    <th class="text-center small py-2">{{ $actionLabels[$action] ?? ucfirst($action) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module)
                                @php
                                    $row = old('permissions.' . $module->id, $permissionMap[$module->id] ?? []);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold small text-dark">{{ $module->name }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">key: <code>{{ $module->key }}</code></div>
                                    </td>
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

                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-rflms px-4">
                        <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Save Changes' : 'Create Group' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function groupForm(cfg) {
    return {
        templates: cfg.templates || {},
        moduleIds: cfg.moduleIds || {},
        actions: cfg.actions || [],

        toggleSelectAll(state) {
            document.querySelectorAll('.perm-box').forEach(cb => {
                cb.checked = state;
            });
        },

        applyTemplate(templateKey) {
            const template = this.templates[templateKey];
            if (!template || !template.modules) return;

            // First uncheck all
            this.toggleSelectAll(false);

            // Apply modules from template
            Object.entries(template.modules).forEach(([modKey, actions]) => {
                const moduleId = this.moduleIds[modKey];
                if (!moduleId) return;

                actions.forEach(action => {
                    const cb = document.querySelector(`.perm-box[data-module-id="${moduleId}"][data-action="${action}"]`);
                    if (cb) cb.checked = true;
                });
            });
        }
    };
}
</script>
@endpush
