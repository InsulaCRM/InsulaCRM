@extends('layouts.app')

@section('title', __('Roles & Permissions'))
@section('page-title', __('Roles & Permissions'))

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ __('Roles & Permissions') }}</li>
@endsection

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">{{ __('Roles & Permissions') }}</h2>
                <div class="text-secondary mt-1">{{ __('Manage who can access what in your CRM. System roles provide defaults; custom roles let you fine-tune access.') }}</div>
            </div>
            <div class="col-auto ms-auto">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14" /><path d="M5 12l14 0" /></svg>
                    {{ __('Create Role') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Role Cards --}}
    <div class="row row-deck row-cards mb-4">
        @foreach($roles as $role)
        <div class="col-sm-6 col-lg-4">
            <div class="card {{ $selectedRole && $selectedRole->id === $role->id ? 'border-primary' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar avatar-sm rounded {{ $role->is_system ? 'bg-blue-lt text-blue' : 'bg-green-lt text-green' }} me-3">
                            @if($role->name === 'admin')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5" /><path d="M12 12l8 -4.5" /><path d="M12 12l0 9" /><path d="M12 12l-8 -4.5" /></svg>
                            @elseif($role->name === 'field_scout')
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 9l-2 4h4l-2 4" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /></svg>
                            @endif
                        </span>
                        <div class="flex-fill">
                            <div class="fw-bold">{{ __($role->display_name ?? ucwords(str_replace('_', ' ', $role->name))) }}</div>
                            <div class="text-secondary small">
                                @if($role->is_system)
                                    {{ __('System role') }}
                                @else
                                    {{ __('Custom role') }}
                                @endif
                            </div>
                        </div>
                        @if(!$role->is_system && $role->users->count() === 0)
                        <div class="dropdown">
                            <a href="#" class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown" aria-label="{{ __('Options') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <form method="POST" action="{{ route('settings.deleteRole', $role) }}" onsubmit="return confirm('{{ __('Delete this role? This cannot be undone.') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                        {{ __('Delete Role') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-auto">
                            <span class="text-secondary small">{{ __('Members') }}</span>
                        </div>
                        <div class="col-auto">
                            @if($role->users->count() > 0)
                                <div class="avatar-list avatar-list-stacked">
                                    @foreach($role->users->take(5) as $user)
                                    <span class="avatar avatar-xs rounded-circle" title="{{ $user->name }}" data-bs-toggle="tooltip">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    @endforeach
                                    @if($role->users->count() > 5)
                                    <span class="avatar avatar-xs rounded-circle">+{{ $role->users->count() - 5 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-secondary small">{{ __('No users') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        @php
                            $permCount = $role->name === 'admin' ? $permissions->count() : $role->permissions->count();
                            $permTotal = $permissions->count();
                            $permPct = $permTotal > 0 ? round(($permCount / $permTotal) * 100) : 0;
                        @endphp
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary small">{{ __('Permissions') }}</span>
                            <span class="text-secondary small">{{ $permCount }} / {{ $permTotal }}</span>
                        </div>
                        <div class="progress progress-sm">
                            <div class="progress-bar {{ $role->name === 'admin' ? 'bg-blue' : ($permPct > 60 ? 'bg-green' : ($permPct > 30 ? 'bg-yellow' : 'bg-orange')) }}" style="width: {{ $permPct }}%" role="progressbar" aria-valuenow="{{ $permPct }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $permPct }}% {{ __('permissions') }}"></div>
                        </div>
                    </div>
                    @if($role->name === 'admin')
                        <div class="text-secondary small">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-lock-open" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 11m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" /><path d="M12 16m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M8 11v-5a4 4 0 0 1 8 0" /></svg>
                            {{ __('Full access — cannot be restricted') }}
                        </div>
                    @else
                        <a href="{{ route('settings.roles', ['role' => $role->id]) }}" class="btn btn-outline-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                            {{ __('Edit Permissions') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Permission Editor for selected role --}}
    @if($selectedRole && $selectedRole->name !== 'admin')
    <div class="card" id="permission-editor">
        <div class="card-header">
            <h3 class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06" /><path d="M15 19l2 2l4 -4" /></svg>
                {{ __('Editing permissions for: :role', ['role' => __($selectedRole->display_name)]) }}
            </h3>
            <div class="card-actions">
                @if($selectedRole->is_system)
                    <span class="badge bg-blue-lt">{{ __('System Role') }}</span>
                @else
                    <span class="badge bg-green-lt">{{ __('Custom Role') }}</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.updateRolePermissions', $selectedRole) }}" id="permissionsForm">
                @csrf
                @method('PUT')

                @php
                    $groupDescriptions = [
                        'leads' => __('Control access to lead records, imports, and exports'),
                        'properties' => __('Control access to property records and submissions'),
                        'deals' => __('Control access to the deal pipeline and documents'),
                        'buyers' => __('Control access to the buyer database and matching'),
                        'calendar' => __('Control access to the calendar view'),
                        'reports' => __('Control access to reports and analytics'),
                        'sequences' => __('Control access to drip sequences'),
                        'lists' => __('Control access to lists, tags, and imports'),
                        'settings' => __('Control access to system settings and team management'),
                        'profile' => __('Control access to user profile settings'),
                    ];
                @endphp

                @foreach($permissionGroups as $group => $groupPermissions)
                <div class="permission-group mb-4 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <h4 class="mb-0">{{ __(ucwords($group)) }}</h4>
                            @if(isset($groupDescriptions[$group]))
                                <small class="text-secondary">{{ $groupDescriptions[$group] }}</small>
                            @endif
                        </div>
                        <div>
                            <a href="#" class="btn btn-ghost-secondary btn-sm toggle-group-btn" data-group="{{ $group }}">
                                {{ __('Toggle all') }}
                            </a>
                        </div>
                    </div>
                    <div class="row g-2">
                        @foreach($groupPermissions as $permission)
                        <div class="col-sm-6 col-lg-4 col-xl-3">
                            <label class="form-check form-switch ps-0">
                                <span class="form-check-label me-auto">{{ __($permission->display_name) }}</span>
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="form-check-input ms-2 perm-checkbox" data-group="{{ $group }}" {{ $selectedRole->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="text-secondary" id="permissionCount">
                        <span id="checkedCount">{{ $selectedRole->permissions->count() }}</span> / {{ $permissions->count() }} {{ __('permissions enabled') }}
                    </div>
                    <div>
                        <a href="{{ route('settings.roles') }}" class="btn btn-ghost-secondary me-2">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                            {{ __('Save Permissions') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @elseif(!$selectedRole)
    <div class="card bg-transparent border-0 shadow-none">
        <div class="card-body text-center py-5 text-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 12l2 2l4 -4" /><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /></svg>
            <p class="mb-0">{{ __('Select a role above to edit its permissions.') }}</p>
        </div>
    </div>
    @endif
</div>

{{-- Create Role Modal --}}
<div class="modal modal-blur fade" id="createRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('settings.createRole') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create Custom Role') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">{{ __('Role Name') }}</label>
                        <input type="text" name="display_name" class="form-control" placeholder="{{ __('e.g. Team Lead, Virtual Assistant') }}" required maxlength="100" autofocus>
                        <small class="form-hint">{{ __('Give the role a clear, descriptive name. A system key will be generated automatically.') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Role') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    // Toggle all checkboxes in a permission group
    document.querySelectorAll('.toggle-group-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var group = this.dataset.group;
            var boxes = document.querySelectorAll('.perm-checkbox[data-group="' + group + '"]');
            var allChecked = Array.from(boxes).every(function(cb) { return cb.checked; });
            boxes.forEach(function(cb) { cb.checked = !allChecked; });
            updateCount();
        });
    });

    // Live permission counter
    function updateCount() {
        var el = document.getElementById('checkedCount');
        if (el) {
            el.textContent = document.querySelectorAll('.perm-checkbox:checked').length;
        }
    }

    document.querySelectorAll('.perm-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateCount);
    });

    // Scroll to editor if editing
    var editor = document.getElementById('permission-editor');
    if (editor && window.location.search.indexOf('role=') > -1) {
        setTimeout(function() { editor.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 200);
    }

    // Show modal if validation failed on create
    @if($errors->has('display_name'))
    var modal = new bootstrap.Modal(document.getElementById('createRoleModal'));
    modal.show();
    @endif
})();
</script>
@endpush
