{{--
    Permission Index View
    Lists all permissions with search, filtering, and management options
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Permission Management')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Permission Management</h1>
            <p class="text-muted mb-0">Manage system permissions and access controls</p>
        </div>
        <div>
            <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create Permission
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Search and Filter Card --}}
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.permissions.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label text-light">Search Permissions</label>
                    <input type="text"
                           class="form-control bg-dark text-light border-secondary"
                           id="search"
                           name="search"
                           placeholder="Search by name, description, or module..."
                           value="{{ request('search') }}">
                </div>

                <div class="col-md-2">
                    <label for="module" class="form-label text-light">Module</label>
                    <select class="form-select bg-dark text-light border-secondary" id="module" name="module">
                        <option value="">All Modules</option>
                        @foreach($modules as $module)
                            <option value="{{ $module }}"
                                    {{ request('module') === $module ? 'selected' : '' }}>
                                {{ ucfirst($module) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="category" class="form-label text-light">Category</label>
                    <select class="form-select bg-dark text-light border-secondary" id="category" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}"
                                    {{ request('category') === $category ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $category)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label text-light">Status</label>
                    <select class="form-select bg-dark text-light border-secondary" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="system" class="form-label text-light">Type</label>
                    <select class="form-select bg-dark text-light border-secondary" id="system" name="system">
                        <option value="">All Types</option>
                        <option value="true" {{ request('system') === 'true' ? 'selected' : '' }}>System</option>
                        <option value="false" {{ request('system') === 'false' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Permissions Table --}}
    <div class="card bg-dark border-secondary">
        <div class="card-header bg-dark border-secondary">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 text-light">
                    <i class="fas fa-key me-2"></i>Permissions
                    <span class="badge bg-primary ms-2">{{ $permissions->total() }}</span>
                </h5>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-sort me-1"></i>Sort
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'module', 'direction' => 'asc']) }}">Module A-Z</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'module', 'direction' => 'desc']) }}">Module Z-A</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => 'asc']) }}">Name A-Z</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => 'desc']) }}">Name Z-A</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => 'desc']) }}">Newest First</a></li>
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => 'asc']) }}">Oldest First</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($permissions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Permission</th>
                                <th>Module</th>
                                <th>Category</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Roles</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $permission)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="text-light">{{ $permission->display_name }}</strong>
                                            <div class="text-muted small">{{ $permission->name }}</div>
                                            @if($permission->description)
                                                <div class="text-muted small mt-1">{{ Str::limit($permission->description, 60) }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($permission->module) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ ucfirst(str_replace('_', ' ', $permission->category)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ucfirst($permission->action) }}</span>
                                    </td>
                                    <td>
                                        @if($permission->is_active)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($permission->is_system)
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-shield-alt me-1"></i>System
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="fas fa-user me-1"></i>Custom
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $permission->roles_count ?? $permission->roles->count() }} role(s)</span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.permissions.show', $permission) }}"
                                               class="btn btn-outline-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.permissions.edit', $permission) }}"
                                               class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-{{ $permission->is_active ? 'secondary' : 'success' }} btn-sm"
                                                    onclick="togglePermissionStatus('{{ $permission->id }}', '{{ $permission->name }}', {{ $permission->is_active ? 'false' : 'true' }})">
                                                <i class="fas fa-{{ $permission->is_active ? 'pause' : 'play' }}"></i>
                                            </button>
                                            @if(!$permission->is_system)
                                                <button type="button"
                                                        class="btn btn-outline-danger btn-sm"
                                                        onclick="deletePermission('{{ $permission->id }}', '{{ $permission->name }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="card-footer bg-dark border-secondary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $permissions->firstItem() }} to {{ $permissions->lastItem() }} of {{ $permissions->total() }} permissions
                        </div>
                        {{ $permissions->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-key fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No permissions found</h5>
                    <p class="text-muted">No permissions match your current filters.</p>
                    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create First Permission
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Status Toggle Modal --}}
<div class="modal fade" id="toggleStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-light">Toggle Permission Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="toggleStatusMessage"></p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="toggleStatusForm" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn" id="toggleStatusButton"></button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
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
                <p class="text-muted" id="deleteMessage"></p>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
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

@endsection

@push('scripts')
<script>
// Toggle permission status
function togglePermissionStatus(permissionId, permissionName, newStatus) {
    const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    const message = document.getElementById('toggleStatusMessage');
    const form = document.getElementById('toggleStatusForm');
    const button = document.getElementById('toggleStatusButton');

    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    const buttonClass = newStatus === 'true' ? 'btn-success' : 'btn-warning';
    const icon = newStatus === 'true' ? 'play' : 'pause';

    message.textContent = `Are you sure you want to ${action} the permission "${permissionName}"?`;
    form.action = `{{ route('admin.permissions.index') }}/${permissionId}/toggle-status`;
    button.className = `btn ${buttonClass}`;
    button.innerHTML = `<i class="fas fa-${icon} me-2"></i>${action.charAt(0).toUpperCase() + action.slice(1)}`;

    modal.show();
}

// Delete permission
function deletePermission(permissionId, permissionName) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const message = document.getElementById('deleteMessage');
    const form = document.getElementById('deleteForm');

    message.textContent = `Are you sure you want to permanently delete the permission "${permissionName}"?`;
    form.action = `{{ route('admin.permissions.index') }}/${permissionId}`;

    modal.show();
}

// Auto-submit form on filter change
document.querySelectorAll('select[name="module"], select[name="category"], select[name="status"], select[name="system"]').forEach(function(select) {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>
@endpush
