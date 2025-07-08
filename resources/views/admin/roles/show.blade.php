{{--
    Role Show View
    Displays detailed role information with permissions and users
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Role Details: ' . $role->name)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Role Details</h1>
            <p class="text-muted mb-0">View role information, permissions, and assigned users</p>
        </div>
        <div>
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-2"></i>Edit Role
            </a>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Roles
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        {{-- Role Information Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-info-circle me-2"></i>Role Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $role->name }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Display Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $role->display_name ?? 'Not set' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Description:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $role->description ?? 'No description' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Status:</strong>
                        </div>
                        <div class="col-sm-8">
                            @if($role->is_active)
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i>Active
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>Inactive
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">System Role:</strong>
                        </div>
                        <div class="col-sm-8">
                            @if($role->is_system)
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-shield-alt me-1"></i>System Role
                                </span>
                            @else
                                <span class="badge bg-info">
                                    <i class="fas fa-user me-1"></i>Custom Role
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Created:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $role->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Updated:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $role->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#toggleStatusModal">
                            @if($role->is_active)
                                <i class="fas fa-pause me-2"></i>Deactivate Role
                            @else
                                <i class="fas fa-play me-2"></i>Activate Role
                            @endif
                        </button>

                        @if(!$role->is_system)
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal">
                                <i class="fas fa-trash me-2"></i>Delete Role
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-key me-2"></i>Permissions
                        <span class="badge bg-primary ms-2">{{ $role->permissions->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#managePermissionsModal">
                        <i class="fas fa-edit me-1"></i>Manage
                    </button>
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        <div class="permissions-list" style="max-height: 300px; overflow-y: auto;">
                            @foreach($role->permissions->groupBy('category') as $category => $permissions)
                                <div class="mb-3">
                                    <h6 class="text-warning mb-2">
                                        <i class="fas fa-folder me-1"></i>{{ ucfirst($category) }}
                                    </h6>
                                    @foreach($permissions as $permission)
                                        <div class="d-flex justify-content-between align-items-center py-1">
                                            <span class="text-muted small">{{ $permission->display_name ?? $permission->name }}</span>
                                            <span class="badge bg-secondary small">{{ $permission->name }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                            <p class="mb-0">No permissions assigned to this role</p>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#managePermissionsModal">
                                <i class="fas fa-plus me-1"></i>Assign Permissions
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Users Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-users me-2"></i>Assigned Users
                        <span class="badge bg-primary ms-2">{{ $role->users->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
                        <i class="fas fa-edit me-1"></i>Manage
                    </button>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="users-list" style="max-height: 300px; overflow-y: auto;">
                            @foreach($role->users as $user)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <div>
                                        <div class="text-light">{{ $user->first_name }} {{ $user->last_name }}</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    <div>
                                        @if($user->is_active)
                                            <span class="badge bg-success small">Active</span>
                                        @else
                                            <span class="badge bg-danger small">Inactive</span>
                                        @endif
                                        @if(!$role->is_system)
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-1"
                                                    onclick="removeUserFromRole('{{ $user->id }}', '{{ $user->first_name }} {{ $user->last_name }}')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <p class="mb-0">No users assigned to this role</p>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#manageUsersModal">
                                <i class="fas fa-plus me-1"></i>Assign Users
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toggle Status Modal --}}
<div class="modal fade" id="toggleStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">
                    @if($role->is_active)
                        Deactivate Role
                    @else
                        Activate Role
                    @endif
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    @if($role->is_active)
                        Are you sure you want to deactivate the role "{{ $role->name }}"?
                        Users with this role will lose their permissions until the role is reactivated.
                    @else
                        Are you sure you want to activate the role "{{ $role->name }}"?
                        Users with this role will regain their permissions.
                    @endif
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.roles.toggle-status', $role) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn {{ $role->is_active ? 'btn-warning' : 'btn-success' }}">
                        @if($role->is_active)
                            <i class="fas fa-pause me-2"></i>Deactivate
                        @else
                            <i class="fas fa-play me-2"></i>Activate
                        @endif
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Delete Role Modal --}}
@if(!$role->is_system)
<div class="modal fade" id="deleteRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Delete Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p class="text-muted">
                    Are you sure you want to permanently delete the role "{{ $role->name }}"?
                    This will remove all permissions and user assignments for this role.
                </p>
                <p class="text-warning">
                    <i class="fas fa-info-circle me-1"></i>
                    {{ $role->users->count() }} user(s) will be affected by this deletion.
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Manage Permissions Modal --}}
<div class="modal fade" id="managePermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Manage Permissions</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.roles.assign-permissions', $role) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        @foreach($allPermissions->groupBy('category') as $category => $permissions)
                            <div class="col-md-6 mb-4">
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-folder me-2"></i>{{ ucfirst($category) }}
                                </h6>
                                @foreach($permissions as $permission)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                               value="{{ $permission->id }}" id="permission_{{ $permission->id }}"
                                               @if($role->permissions->contains($permission->id)) checked @endif>
                                        <label class="form-check-label text-light" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name ?? $permission->name }}
                                            <small class="text-muted d-block">{{ $permission->description }}</small>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Manage Users Modal --}}
<div class="modal fade" id="manageUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Manage Users</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.roles.assign-users', $role) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <input type="text" class="form-control bg-dark text-light border-secondary"
                               placeholder="Search users..." id="userSearch">
                    </div>
                    <div class="user-list" style="max-height: 400px; overflow-y: auto;">
                        @foreach($allUsers as $user)
                            <div class="form-check mb-2 user-item" data-user-name="{{ strtolower($user->first_name . ' ' . $user->last_name . ' ' . $user->email) }}">
                                <input class="form-check-input" type="checkbox" name="users[]"
                                       value="{{ $user->id }}" id="user_{{ $user->id }}"
                                       @if($role->users->contains($user->id)) checked @endif>
                                <label class="form-check-label text-light d-flex justify-content-between align-items-center" for="user_{{ $user->id }}">
                                    <div>
                                        <div>{{ $user->first_name }} {{ $user->last_name }}</div>
                                        <small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    <div>
                                        @if($user->is_active)
                                            <span class="badge bg-success small">Active</span>
                                        @else
                                            <span class="badge bg-danger small">Inactive</span>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Users
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// User search functionality
document.getElementById('userSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const userItems = document.querySelectorAll('.user-item');

    userItems.forEach(function(item) {
        const userName = item.getAttribute('data-user-name');
        if (userName.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Remove user from role function
function removeUserFromRole(userId, userName) {
    if (confirm(`Are you sure you want to remove ${userName} from this role?`)) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('admin.roles.remove-user', ['role' => $role->id, 'user' => '__USER_ID__']) }}`.replace('__USER_ID__', userId);

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
