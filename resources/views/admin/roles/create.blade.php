{{--
    Role Creation View
    Creates new role with permissions assignment
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Create Role</h1>
            <p class="text-muted mb-0">Define a new role with specific permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-plus-circle me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Please correct the following errors:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.roles.store') }}" method="POST" id="roleForm">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label text-light">
                                        <i class="fas fa-tag me-1"></i>Role Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control bg-dark border-secondary text-light"
                                           id="name" name="name" value="{{ old('name') }}" required
                                           placeholder="e.g., Content Manager">
                                    <div class="form-text text-muted">
                                        Unique name for this role (3-50 characters)
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slug" class="form-label text-light">
                                        <i class="fas fa-link me-1"></i>Role Slug
                                    </label>
                                    <input type="text" class="form-control bg-dark border-secondary text-light"
                                           id="slug" name="slug" value="{{ old('slug') }}" readonly
                                           placeholder="Auto-generated from name">
                                    <div class="form-text text-muted">
                                        URL-friendly identifier (auto-generated)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label text-light">
                                        <i class="fas fa-layer-group me-1"></i>Role Level <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select bg-dark border-secondary text-light"
                                            id="level" name="level" required>
                                        <option value="">Select Role Level</option>
                                        <option value="1" {{ old('level') == '1' ? 'selected' : '' }}>Level 1 - Basic User</option>
                                        <option value="2" {{ old('level') == '2' ? 'selected' : '' }}>Level 2 - Advanced User</option>
                                        <option value="3" {{ old('level') == '3' ? 'selected' : '' }}>Level 3 - Supervisor</option>
                                        <option value="4" {{ old('level') == '4' ? 'selected' : '' }}>Level 4 - Manager</option>
                                        <option value="5" {{ old('level') == '5' ? 'selected' : '' }}>Level 5 - Administrator</option>
                                    </select>
                                    <div class="form-text text-muted">
                                        Higher levels can manage lower level roles
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label text-light">
                                        <i class="fas fa-toggle-on me-1"></i>Status
                                    </label>
                                    <select class="form-select bg-dark border-secondary text-light"
                                            id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <div class="form-text text-muted">
                                        Inactive roles cannot be assigned to users
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label text-light">
                                <i class="fas fa-file-alt me-1"></i>Description
                            </label>
                            <textarea class="form-control bg-dark border-secondary text-light"
                                      id="description" name="description" rows="3"
                                      placeholder="Describe the purpose and responsibilities of this role">{{ old('description') }}</textarea>
                            <div class="form-text text-muted">
                                Optional description to clarify role purpose (max 500 characters)
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-shield-alt me-2"></i>Assign Permissions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="selectAllPermissions">
                            <label class="form-check-label text-light" for="selectAllPermissions">
                                <strong>Select All Permissions</strong>
                            </label>
                        </div>
                        <hr class="border-secondary">
                    </div>

                    @if(isset($permissions) && $permissions->count() > 0)
                        @php
                            $groupedPermissions = $permissions->groupBy(function($permission) {
                                return explode('.', $permission->name)[0];
                            });
                        @endphp

                        @foreach($groupedPermissions as $group => $groupPermissions)
                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-folder me-1"></i>{{ ucfirst($group) }}
                                    <span class="badge bg-secondary ms-2">{{ $groupPermissions->count() }}</span>
                                </h6>
                                @foreach($groupPermissions as $permission)
                                    <div class="form-check ms-3 mb-1">
                                        <input type="checkbox" class="form-check-input permission-checkbox"
                                               id="permission_{{ $permission->id }}"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label text-light small"
                                               for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                            @if($permission->description)
                                                <i class="fas fa-info-circle text-muted ms-1"
                                                   data-bs-toggle="tooltip"
                                                   title="{{ $permission->description }}"></i>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <p>No permissions available.<br>Please create permissions first.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-dark border-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Once created, you can assign this role to users and modify permissions as needed.
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary me-2" onclick="history.back()">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" form="roleForm">
                                <i class="fas fa-save me-2"></i>Create Role
                            </button>
                        </div>
                    </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for enhanced functionality --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    nameInput.addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
    });

    // Select/Deselect all permissions
    const selectAllCheckbox = document.getElementById('selectAllPermissions');
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');

    selectAllCheckbox.addEventListener('change', function() {
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Update select all checkbox when individual permissions change
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const totalCheckboxes = permissionCheckboxes.length;
            const checkedCheckboxes = document.querySelectorAll('.permission-checkbox:checked').length;

            selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
            selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes;
        });
    });

    // Form validation
    document.getElementById('roleForm').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const level = document.getElementById('level').value;

        if (!name) {
            e.preventDefault();
            alert('Please enter a role name');
            document.getElementById('name').focus();
            return;
        }

        if (!level) {
            e.preventDefault();
            alert('Please select a role level');
            document.getElementById('level').focus();
            return;
        }
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
