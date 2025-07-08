{{--
    Permission Show View
    Displays detailed permission information with usage statistics
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Permission Details: ' . $permission->display_name)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Permission Details</h1>
            <p class="text-muted mb-0">View permission information and usage statistics</p>
        </div>
        <div>
            <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-2"></i>Edit Permission
            </a>
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Permissions
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
        {{-- Permission Information Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-key me-2"></i>Permission Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted font-monospace">{{ $permission->name }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Display Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $permission->display_name }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Description:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $permission->description ?? 'No description' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Module:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="badge bg-info">{{ ucfirst($permission->module) }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Category:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ ucfirst(str_replace('_', ' ', $permission->category)) }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Action:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="badge bg-secondary">{{ ucfirst($permission->action) }}</span>
                        </div>
                    </div>

                    @if($permission->resource)
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong class="text-light">Resource:</strong>
                            </div>
                            <div class="col-sm-8">
                                <span class="text-muted">{{ $permission->resource }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Status:</strong>
                        </div>
                        <div class="col-sm-8">
                            @if($permission->is_active)
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
                            <strong class="text-light">Type:</strong>
                        </div>
                        <div class="col-sm-8">
                            @if($permission->is_system)
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-shield-alt me-1"></i>System
                                </span>
                            @else
                                <span class="badge bg-info">
                                    <i class="fas fa-user me-1"></i>Custom
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Priority:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $permission->priority }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Slug:</strong>
                        </div>
                        <div class="col-sm-8">
                            <small class="text-muted font-monospace">{{ $permission->slug }}</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Created:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $permission->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-light">Updated:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="text-muted">{{ $permission->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-grid gap-2 mt-4">
                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#toggleStatusModal">
                            @if($permission->is_active)
                                <i class="fas fa-pause me-2"></i>Deactivate Permission
                            @else
                                <i class="fas fa-play me-2"></i>Activate Permission
                            @endif
                        </button>

                        @if(!$permission->is_system && $roleCount === 0)
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePermissionModal">
                                <i class="fas fa-trash me-2"></i>Delete Permission
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Assigned Roles Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-user-shield me-2"></i>Assigned Roles
                        <span class="badge bg-primary ms-2">{{ $roleCount }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if($permission->roles->count() > 0)
                        <div class="roles-list" style="max-height: 400px; overflow-y: auto;">
                            @foreach($permission->roles as $role)
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                    <div>
                                        <div class="text-light">{{ $role->display_name ?? $role->name }}</div>
                                        <small class="text-muted">{{ $role->description ?? 'No description' }}</small>
                                    </div>
                                    <div>
                                        @if($role->is_active)
                                            <span class="badge bg-success small">Active</span>
                                        @else
                                            <span class="badge bg-danger small">Inactive</span>
                                        @endif
                                        @if($role->is_system)
                                            <span class="badge bg-warning text-dark small">System</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-user-shield fa-2x mb-2"></i>
                            <p class="mb-0">No roles assigned</p>
                            <small>This permission is not currently assigned to any roles</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Advanced Settings Card --}}
        <div class="col-lg-4 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-cog me-2"></i>Advanced Settings
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Usage Statistics --}}
                    <div class="mb-4">
                        <h6 class="text-warning mb-2">Usage Statistics</h6>
                        <div class="row mb-2">
                            <div class="col-6"><span class="text-light">Total Roles:</span></div>
                            <div class="col-6"><span class="text-muted">{{ $roleCount }}</span></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><span class="text-light">Active Roles:</span></div>
                            <div class="col-6"><span class="text-muted">{{ $activeRoleCount }}</span></div>
                        </div>
                    </div>

                    {{-- Conditions --}}
                    @if($permission->conditions)
                        <div class="mb-3">
                            <h6 class="text-warning mb-2">Conditions</h6>
                            <pre class="text-muted small bg-secondary p-2 rounded" style="font-size: 11px;">{{ json_encode($permission->conditions, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    {{-- Settings --}}
                    @if($permission->settings)
                        <div class="mb-3">
                            <h6 class="text-warning mb-2">Settings</h6>
                            <pre class="text-muted small bg-secondary p-2 rounded" style="font-size: 11px;">{{ json_encode($permission->settings, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif

                    {{-- Administrative Notes --}}
                    @if($permission->notes)
                        <div class="mb-3">
                            <h6 class="text-warning mb-2">Administrative Notes</h6>
                            <div class="text-muted small">{{ $permission->notes }}</div>
                        </div>
                    @endif

                    {{-- Guard and ID --}}
                    <div class="mb-3">
                        <h6 class="text-warning mb-2">Technical Details</h6>
                        <div class="row mb-1">
                            <div class="col-5"><small class="text-light">Guard:</small></div>
                            <div class="col-7"><small class="text-muted">{{ $permission->guard_name ?? 'web' }}</small></div>
                        </div>
                        <div class="row mb-1">
                            <div class="col-5"><small class="text-light">UUID:</small></div>
                            <div class="col-7"><small class="text-muted font-monospace">{{ $permission->id }}</small></div>
                        </div>
                    </div>
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
                    @if($permission->is_active)
                        Deactivate Permission
                    @else
                        Activate Permission
                    @endif
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    @if($permission->is_active)
                        Are you sure you want to deactivate the permission "{{ $permission->display_name }}"?
                        This will prevent it from being assigned to new roles, but existing assignments will remain.
                    @else
                        Are you sure you want to activate the permission "{{ $permission->display_name }}"?
                        This will allow it to be assigned to roles again.
                    @endif
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.permissions.toggle-status', $permission) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn {{ $permission->is_active ? 'btn-warning' : 'btn-success' }}">
                        @if($permission->is_active)
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

{{-- Delete Permission Modal --}}
@if(!$permission->is_system && $roleCount === 0)
<div class="modal fade" id="deletePermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Delete Permission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning!</strong> This action cannot be undone.
                </div>
                <p class="text-muted">
                    Are you sure you want to permanently delete the permission "{{ $permission->display_name }}"?
                </p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete Permission
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection
