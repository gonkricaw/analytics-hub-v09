@extends('layouts.app')

@section('title', 'Role Management')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Role Management</h1>
                <p class="text-gray-300 mt-2">Manage system roles and permissions</p>
            </div>
            <a href="{{ route('admin.roles.create') }}"
               class="bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-300">
                <i class="fas fa-plus mr-2"></i>Create Role
            </a>
        </div>
    </div>

    <!-- Alerts Container -->
    <div id="alerts-container" class="mb-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Roles Table -->
    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-white">System Roles</h2>
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" id="role-search"
                               placeholder="Search roles..."
                               class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block w-64 pl-10 p-2.5">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Filter -->
                    <select id="status-filter"
                            class="bg-gray-700 border border-gray-600 text-white text-sm rounded-lg focus:ring-orange-500 focus:border-orange-500 block p-2.5">
                        <option value="">All Roles</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="system">System</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700" id="roles-table">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Role
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Description
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Users
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Permissions
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Priority
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-gray-800 divide-y divide-gray-700">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-700 transition-colors duration-200" data-role-id="{{ $role->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($role->color)
                                        <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $role->color }}"></div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ $role->display_name ?: $role->name }}</div>
                                        <div class="text-sm text-gray-400">{{ $role->name }}</div>
                                        @if($role->is_system)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                <i class="fas fa-cog mr-1"></i>System
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-300 max-w-xs truncate">
                                    {{ $role->description ?: 'No description provided' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-white">{{ $role->users_count }}</span>
                                    @if($role->users_count > 0)
                                        <a href="{{ route('admin.roles.show', $role) }}"
                                           class="ml-2 text-orange-400 hover:text-orange-300 text-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-white">{{ $role->permissions_count }}</span>
                                @if($role->permissions_count > 0)
                                    <a href="#" onclick="showPermissions('{{ $role->id }}')"
                                       class="ml-2 text-orange-400 hover:text-orange-300 text-sm">
                                        <i class="fas fa-list"></i>
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="toggleRoleStatus('{{ $role->id }}')"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200 {{ $role->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                    <span class="w-2 h-2 mr-1 rounded-full {{ $role->is_active ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-300">{{ $role->priority }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- View Button -->
                                    <a href="{{ route('admin.roles.show', $role) }}"
                                       class="text-blue-400 hover:text-blue-300 transition-colors duration-200"
                                       title="View Role">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                       class="text-orange-400 hover:text-orange-300 transition-colors duration-200"
                                       title="Edit Role">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- Permissions Button -->
                                    <button onclick="managePermissions('{{ $role->id }}')"
                                            class="text-purple-400 hover:text-purple-300 transition-colors duration-200"
                                            title="Manage Permissions">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    @unless($role->is_system)
                                        <button onclick="deleteRole('{{ $role->id }}', '{{ $role->name }}')"
                                                class="text-red-400 hover:text-red-300 transition-colors duration-200"
                                                title="Delete Role">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-users-cog text-4xl mb-4"></i>
                                    <p class="text-lg font-medium">No roles found</p>
                                    <p class="text-sm">Get started by creating your first role</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Role Statistics -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users-cog text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Total Roles</p>
                    <p class="text-2xl font-semibold text-white">{{ $roles->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Active Roles</p>
                    <p class="text-2xl font-semibold text-white">{{ $roles->where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cog text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">System Roles</p>
                    <p class="text-2xl font-semibold text-white">{{ $roles->where('is_system', true)->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Total Users</p>
                    <p class="text-2xl font-semibold text-white">{{ $roles->sum('users_count') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div id="permissions-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-700">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-white" id="permissions-modal-title">Role Permissions</h3>
                    <button onclick="closePermissionsModal()" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="p-6" id="permissions-modal-content">
                <!-- Permissions content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('role-search');
    const statusFilter = document.getElementById('status-filter');
    const table = document.getElementById('roles-table');
    const rows = table.querySelectorAll('tbody tr');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        rows.forEach(row => {
            const roleCell = row.cells[0];
            const statusCell = row.cells[4];

            if (!roleCell || !statusCell) return;

            const roleName = roleCell.textContent.toLowerCase();
            const status = statusCell.textContent.toLowerCase();

            const matchesSearch = roleName.includes(searchTerm);
            const matchesStatus = !statusValue ||
                                (statusValue === 'active' && status.includes('active')) ||
                                (statusValue === 'inactive' && status.includes('inactive')) ||
                                (statusValue === 'system' && row.querySelector('.bg-blue-100'));

            row.style.display = matchesSearch && matchesStatus ? '' : 'none';
        });
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
});

// Toggle role status
async function toggleRoleStatus(roleId) {
    try {
        const response = await fetch(`/admin/roles/${roleId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Update status in the UI
            const row = document.querySelector(`tr[data-role-id="${roleId}"]`);
            const statusButton = row.querySelector('button[onclick*="toggleRoleStatus"]');
            const statusSpan = statusButton.querySelector('span:last-child');
            const statusDot = statusButton.querySelector('span:first-child span');

            if (data.data.is_active) {
                statusButton.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200 bg-green-100 text-green-800 hover:bg-green-200';
                statusDot.className = 'w-2 h-2 mr-1 rounded-full bg-green-400';
                statusSpan.textContent = 'Active';
            } else {
                statusButton.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors duration-200 bg-red-100 text-red-800 hover:bg-red-200';
                statusDot.className = 'w-2 h-2 mr-1 rounded-full bg-red-400';
                statusSpan.textContent = 'Inactive';
            }

            showAlert('Role status updated successfully', 'success');
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred while updating role status', 'error');
    }
}

// Show role permissions
async function showPermissions(roleId) {
    try {
        const response = await fetch(`/admin/roles/${roleId}/permissions`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            const modal = document.getElementById('permissions-modal');
            const content = document.getElementById('permissions-modal-content');

            let html = '<div class="space-y-4">';

            Object.keys(data.data).forEach(group => {
                html += `
                    <div>
                        <h4 class="text-md font-semibold text-white mb-2">${group}</h4>
                        <div class="space-y-1">
                `;

                data.data[group].forEach(permission => {
                    html += `
                        <div class="flex items-center justify-between py-1">
                            <span class="text-sm text-gray-300">${permission.display_name || permission.name}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>Granted
                            </span>
                        </div>
                    `;
                });

                html += '</div></div>';
            });

            html += '</div>';
            content.innerHTML = html;
            modal.classList.remove('hidden');
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred while loading permissions', 'error');
    }
}

// Close permissions modal
function closePermissionsModal() {
    document.getElementById('permissions-modal').classList.add('hidden');
}

// Manage permissions (redirect to permissions page)
function managePermissions(roleId) {
    window.location.href = `/admin/roles/${roleId}/edit#permissions`;
}

// Delete role
async function deleteRole(roleId, roleName) {
    if (!confirm(`Are you sure you want to delete the role "${roleName}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/admin/roles/${roleId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Remove row from table
            const row = document.querySelector(`tr[data-role-id="${roleId}"]`);
            row.remove();

            showAlert('Role deleted successfully', 'success');
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('An error occurred while deleting role', 'error');
    }
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertsContainer = document.getElementById('alerts-container');
    const alertClass = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
    const iconClass = type === 'success' ? 'fas fa-check-circle text-green-400' : 'fas fa-exclamation-circle text-red-400';

    const alertHtml = `
        <div class="${alertClass} px-4 py-3 rounded-lg mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="${iconClass}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">${message}</p>
                </div>
            </div>
        </div>
    `;

    alertsContainer.innerHTML = alertHtml;

    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertsContainer.innerHTML = '';
    }, 5000);
}
</script>
@endsection
