{{--
    Permission Edit View
    Edit existing system permissions
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Edit Permission: ' . $permission->display_name)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Edit Permission</h1>
            <p class="text-muted mb-0">Modify permission details and settings</p>
        </div>
        <div>
            <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-outline-info me-2">
                <i class="fas fa-eye me-2"></i>View Details
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-edit me-2"></i>Permission Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Basic Information --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label text-light">Permission Name *</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $permission->name) }}"
                                       placeholder="e.g., users.create"
                                       required>
                                <div class="form-text text-muted">Use dot notation (module.action)</div>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="display_name" class="form-label text-light">Display Name *</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('display_name') is-invalid @enderror"
                                       id="display_name"
                                       name="display_name"
                                       value="{{ old('display_name', $permission->display_name) }}"
                                       placeholder="e.g., Create Users"
                                       required>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label text-light">Description</label>
                            <textarea class="form-control bg-dark text-light border-secondary @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="2"
                                      placeholder="Brief description of what this permission allows">{{ old('description', $permission->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Module and Category --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="module" class="form-label text-light">Module *</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('module') is-invalid @enderror"
                                       id="module"
                                       name="module"
                                       value="{{ old('module', $permission->module) }}"
                                       placeholder="e.g., users"
                                       list="modulesList"
                                       required>
                                <datalist id="modulesList">
                                    @foreach($modules as $module)
                                        <option value="{{ $module }}">
                                    @endforeach
                                </datalist>
                                @error('module')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="category" class="form-label text-light">Category *</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('category') is-invalid @enderror"
                                       id="category"
                                       name="category"
                                       value="{{ old('category', $permission->category) }}"
                                       placeholder="e.g., user_management"
                                       list="categoriesList"
                                       required>
                                <datalist id="categoriesList">
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">
                                    @endforeach
                                </datalist>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="action" class="form-label text-light">Action *</label>
                                <select class="form-select bg-dark text-light border-secondary @error('action') is-invalid @enderror"
                                        id="action"
                                        name="action"
                                        required>
                                    <option value="">Select Action</option>
                                    @foreach($actions as $action)
                                        <option value="{{ $action }}"
                                                {{ old('action', $permission->action) === $action ? 'selected' : '' }}>
                                            {{ ucfirst($action) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('action')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Resource and Priority --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="resource" class="form-label text-light">Resource (Optional)</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('resource') is-invalid @enderror"
                                       id="resource"
                                       name="resource"
                                       value="{{ old('resource', $permission->resource) }}"
                                       placeholder="Specific resource this permission applies to">
                                <div class="form-text text-muted">Optional: specify a particular resource</div>
                                @error('resource')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label text-light">Priority</label>
                                <input type="number"
                                       class="form-control bg-dark text-light border-secondary @error('priority') is-invalid @enderror"
                                       id="priority"
                                       name="priority"
                                       value="{{ old('priority', $permission->priority) }}"
                                       min="0"
                                       max="1000">
                                <div class="form-text text-muted">Higher numbers = higher priority (0-1000)</div>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Status and Type Settings --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_active"
                                           name="is_active"
                                           value="1"
                                           {{ old('is_active', $permission->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label text-light" for="is_active">
                                        Active Permission
                                    </label>
                                    <div class="form-text text-muted">Active permissions can be assigned to roles</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="is_system"
                                           name="is_system"
                                           value="1"
                                           {{ old('is_system', $permission->is_system) ? 'checked' : '' }}
                                           @if($permission->is_system) disabled @endif>
                                    <label class="form-check-label text-light" for="is_system">
                                        System Permission
                                        @if($permission->is_system)
                                            <i class="fas fa-lock ms-1 text-warning" title="Cannot be changed"></i>
                                        @endif
                                    </label>
                                    <div class="form-text text-muted">System permissions cannot be deleted</div>
                                </div>
                            </div>
                        </div>

                        {{-- Advanced Settings (Collapsible) --}}
                        <div class="mb-3">
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#advancedSettings">
                                <i class="fas fa-cog me-2"></i>Advanced Settings
                            </button>
                        </div>

                        <div class="collapse" id="advancedSettings">
                            <div class="card bg-secondary border-secondary mb-3">
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="conditions" class="form-label text-light">Conditions (JSON)</label>
                                            <textarea class="form-control bg-dark text-light border-secondary @error('conditions') is-invalid @enderror"
                                                      id="conditions"
                                                      name="conditions"
                                                      rows="3"
                                                      placeholder='{"time": "business_hours", "ip": "internal"}'
                                                      style="font-family: monospace;">{{ old('conditions', $permission->conditions ? json_encode($permission->conditions, JSON_PRETTY_PRINT) : '') }}</textarea>
                                            <div class="form-text text-muted">JSON object defining permission conditions</div>
                                            @error('conditions')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="settings" class="form-label text-light">Settings (JSON)</label>
                                            <textarea class="form-control bg-dark text-light border-secondary @error('settings') is-invalid @enderror"
                                                      id="settings"
                                                      name="settings"
                                                      rows="3"
                                                      placeholder='{"rate_limit": 100, "cache_ttl": 3600}'
                                                      style="font-family: monospace;">{{ old('settings', $permission->settings ? json_encode($permission->settings, JSON_PRETTY_PRINT) : '') }}</textarea>
                                            <div class="form-text text-muted">JSON object for permission-specific settings</div>
                                            @error('settings')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label text-light">Administrative Notes</label>
                                        <textarea class="form-control bg-dark text-light border-secondary @error('notes') is-invalid @enderror"
                                                  id="notes"
                                                  name="notes"
                                                  rows="2"
                                                  placeholder="Internal notes about this permission">{{ old('notes', $permission->notes) }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Permission Info Panel --}}
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary mb-3">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-info-circle me-2"></i>Permission Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-5"><strong class="text-light">ID:</strong></div>
                        <div class="col-7"><small class="text-muted font-monospace">{{ $permission->id }}</small></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong class="text-light">Slug:</strong></div>
                        <div class="col-7"><span class="text-muted">{{ $permission->slug }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong class="text-light">Created:</strong></div>
                        <div class="col-7"><span class="text-muted">{{ $permission->created_at->format('M d, Y H:i') }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong class="text-light">Updated:</strong></div>
                        <div class="col-7"><span class="text-muted">{{ $permission->updated_at->format('M d, Y H:i') }}</span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-5"><strong class="text-light">Roles:</strong></div>
                        <div class="col-7"><span class="text-muted">{{ $permission->roles->count() }} assigned</span></div>
                    </div>
                </div>
            </div>

            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-question-circle me-2"></i>Edit Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="text-warning">Important Notes</h6>
                    <ul class="text-muted small mb-3">
                        <li>Changing the name will update the slug automatically</li>
                        <li>System permissions cannot have their system status changed</li>
                        <li>Deactivating will prevent assignment to new roles</li>
                        <li>JSON fields must contain valid JSON syntax</li>
                    </ul>

                    @if($permission->roles->count() > 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This permission is currently assigned to {{ $permission->roles->count() }} role(s).
                        </div>
                    @endif

                    @if($permission->is_system)
                        <div class="alert alert-warning">
                            <i class="fas fa-shield-alt me-2"></i>
                            This is a system permission and cannot be deleted.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate JSON fields
    function validateJson(fieldId) {
        const field = document.getElementById(fieldId);
        if (field.value.trim()) {
            try {
                JSON.parse(field.value);
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } catch (e) {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
        } else {
            field.classList.remove('is-invalid', 'is-valid');
        }
    }

    ['conditions', 'settings'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', () => validateJson(fieldId));
            // Validate on page load if there's content
            if (field.value.trim()) {
                validateJson(fieldId);
            }
        }
    });
});
</script>
@endpush
