{{--
    Permission Create View
    Creates new system permissions
    Part of Role & Permission Management System
    Analytics Hub v0.9
--}}
@extends('layouts.admin')

@section('title', 'Create Permission')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-light">Create Permission</h1>
            <p class="text-muted mb-0">Add a new permission to the system</p>
        </div>
        <div>
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
                        <i class="fas fa-key me-2"></i>Permission Details
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.store') }}" method="POST">
                        @csrf

                        {{-- Basic Information --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label text-light">Permission Name *</label>
                                <input type="text"
                                       class="form-control bg-dark text-light border-secondary @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
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
                                       value="{{ old('display_name') }}"
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
                                      placeholder="Brief description of what this permission allows">{{ old('description') }}</textarea>
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
                                       value="{{ old('module') }}"
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
                                       value="{{ old('category') }}"
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
                                        <option value="{{ $action }}" {{ old('action') === $action ? 'selected' : '' }}>
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
                                       value="{{ old('resource') }}"
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
                                       value="{{ old('priority', 0) }}"
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
                                           {{ old('is_active', true) ? 'checked' : '' }}>
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
                                           {{ old('is_system') ? 'checked' : '' }}>
                                    <label class="form-check-label text-light" for="is_system">
                                        System Permission
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
                                                      style="font-family: monospace;">{{ old('conditions') }}</textarea>
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
                                                      style="font-family: monospace;">{{ old('settings') }}</textarea>
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
                                                  placeholder="Internal notes about this permission">{{ old('notes') }}</textarea>
                                        @error('notes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Help Panel --}}
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="card-title mb-0 text-light">
                        <i class="fas fa-info-circle me-2"></i>Permission Guidelines
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="text-warning">Naming Conventions</h6>
                    <ul class="text-muted small mb-3">
                        <li><strong>Name:</strong> Use dot notation (module.action)</li>
                        <li><strong>Module:</strong> lowercase, singular noun</li>
                        <li><strong>Category:</strong> snake_case grouping</li>
                        <li><strong>Action:</strong> CRUD or specific action</li>
                    </ul>

                    <h6 class="text-warning">Common Modules</h6>
                    <div class="mb-3">
                        @foreach($modules as $module)
                            <span class="badge bg-info me-1 mb-1">{{ $module }}</span>
                        @endforeach
                    </div>

                    <h6 class="text-warning">Common Categories</h6>
                    <div class="mb-3">
                        @foreach($categories as $category)
                            <span class="badge bg-secondary me-1 mb-1">{{ str_replace('_', ' ', $category) }}</span>
                        @endforeach
                    </div>

                    <h6 class="text-warning">Action Types</h6>
                    <ul class="text-muted small mb-3">
                        <li><strong>create:</strong> Add new records</li>
                        <li><strong>read/view:</strong> View existing records</li>
                        <li><strong>update/edit:</strong> Modify existing records</li>
                        <li><strong>delete:</strong> Remove records</li>
                        <li><strong>export:</strong> Export data</li>
                        <li><strong>import:</strong> Import data</li>
                        <li><strong>manage:</strong> Full control</li>
                    </ul>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> System permissions cannot be deleted and should be used for core functionality only.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Auto-generate permission name from module and action
document.addEventListener('DOMContentLoaded', function() {
    const moduleInput = document.getElementById('module');
    const actionSelect = document.getElementById('action');
    const nameInput = document.getElementById('name');
    const displayNameInput = document.getElementById('display_name');

    function updateName() {
        const module = moduleInput.value.toLowerCase().trim();
        const action = actionSelect.value.toLowerCase().trim();

        if (module && action) {
            nameInput.value = `${module}.${action}`;

            // Auto-generate display name if empty
            if (!displayNameInput.value) {
                const actionText = action.charAt(0).toUpperCase() + action.slice(1);
                const moduleText = module.charAt(0).toUpperCase() + module.slice(1);
                displayNameInput.value = `${actionText} ${moduleText}`;
            }
        }
    }

    moduleInput.addEventListener('input', updateName);
    actionSelect.addEventListener('change', updateName);

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
        }
    });
});
</script>
@endpush
