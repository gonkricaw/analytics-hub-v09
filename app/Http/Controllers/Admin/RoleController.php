<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Role Management Controller
 *
 * Handles CRUD operations for roles with comprehensive security features including:
 * - Role creation, editing, deletion
 * - Permission assignment and management
 * - User role assignments
 * - Hierarchical role management
 * - Audit logging for all operations
 * - Role-based access control validation
 */
class RoleController extends Controller
{
    /**
     * Display a listing of roles
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Get roles with related data
            $roles = Role::with(['permissions', 'users'])
                ->withCount(['users', 'permissions'])
                ->orderBy('name')
                ->get();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $roles
                ]);
            }

            return view('admin.roles.index', compact('roles'));

        } catch (\Exception $e) {
            Log::error('Failed to load roles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load roles'
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load roles');
        }
    }

    /**
     * Show the form for creating a new role
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $permissions = Permission::where('is_active', true)
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:roles,name',
            'slug' => 'required|string|max:100|unique:roles,slug|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'level' => 'required|integer|min:1|max:100',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::transaction(function () use ($request) {
                // Create role
                $role = Role::create([
                    'name' => $request->name,
                    'slug' => $request->slug,
                    'description' => $request->description,
                    'is_active' => $request->boolean('is_active', true),
                    'level' => $request->level,
                    'color' => $request->color,
                    'metadata' => [
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                    ],
                ]);

                // Assign permissions
                if ($request->has('permissions')) {
                    $permissions = Permission::whereIn('id', $request->permissions)
                        ->where('is_active', true)
                        ->pluck('id');

                    $role->permissions()->sync($permissions);
                }

                // Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($role)
                    ->withProperties([
                        'role_data' => $role->toArray(),
                        'permissions_assigned' => $request->permissions ?? [],
                        'ip_address' => request()->ip(),
                    ])
                    ->log('role_created');

                Log::info('Role created successfully', [
                    'role_id' => $role->id,
                    'name' => $role->name,
                    'created_by' => auth()->id(),
                    'permissions_count' => count($request->permissions ?? []),
                ]);

                $this->role = $role;
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully',
                    'data' => $this->role->load('permissions')
                ]);
            }

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create role', [
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create role')
                ->withInput();
        }
    }

    /**
     * Display the specified role
     *
     * @param Role $role
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Role $role, Request $request)
    {
        try {
            $role->load(['permissions', 'users.roles']);

            // Get all permissions and users for the modals
            $allPermissions = Permission::orderBy('category', 'asc')
                                      ->orderBy('name', 'asc')
                                      ->get();

            $allUsers = User::where('is_active', true)
                           ->orderBy('first_name', 'asc')
                           ->orderBy('last_name', 'asc')
                           ->get();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $role
                ]);
            }

            return view('admin.roles.show', compact('role', 'allPermissions', 'allUsers'));

        } catch (\Exception $e) {
            Log::error('Failed to load role', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load role'
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load role');
        }
    }

    /**
     * Show the form for editing the specified role
     *
     * @param Role $role
     * @return \Illuminate\View\View
     */
    public function edit(Role $role)
    {
        $permissions = Permission::where('is_active', true)
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role
     *
     * @param Request $request
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('roles')->ignore($role->id)],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
            'level' => 'required|integer|min:1|max:100',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::transaction(function () use ($request, $role) {
                $originalData = $role->toArray();

                // Update role
                $role->update([
                    'name' => $request->name,
                    'slug' => $request->slug,
                    'description' => $request->description,
                    'is_active' => $request->boolean('is_active', true),
                    'level' => $request->level,
                    'color' => $request->color,
                    'metadata' => array_merge($role->metadata ?? [], [
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]),
                ]);

                // Update permissions
                if ($request->has('permissions')) {
                    $permissions = Permission::whereIn('id', $request->permissions)
                        ->where('is_active', true)
                        ->pluck('id');

                    $role->permissions()->sync($permissions);
                }

                // Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($role)
                    ->withProperties([
                        'original_data' => $originalData,
                        'updated_data' => $role->toArray(),
                        'permissions_assigned' => $request->permissions ?? [],
                        'ip_address' => request()->ip(),
                    ])
                    ->log('role_updated');

                Log::info('Role updated successfully', [
                    'role_id' => $role->id,
                    'name' => $role->name,
                    'updated_by' => auth()->id(),
                    'permissions_count' => count($request->permissions ?? []),
                ]);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully',
                    'data' => $role->load('permissions')
                ]);
            }

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'input' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update role')
                ->withInput();
        }
    }

    /**
     * Remove the specified role from storage
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Role $role, Request $request)
    {
        try {
            // Check if role has users
            $userCount = $role->users()->count();
            if ($userCount > 0) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete role. It is assigned to {$userCount} user(s)."
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', "Cannot delete role. It is assigned to {$userCount} user(s).");
            }

            // Check if it's a system role
            if ($role->is_system) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete system role.'
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', 'Cannot delete system role.');
            }

            DB::transaction(function () use ($role) {
                $roleData = $role->toArray();

                // Detach permissions
                $role->permissions()->detach();

                // Soft delete role
                $role->delete();

                // Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'deleted_role' => $roleData,
                        'ip_address' => request()->ip(),
                    ])
                    ->log('role_deleted');

                Log::info('Role deleted successfully', [
                    'role_id' => $role->id,
                    'name' => $role->name,
                    'deleted_by' => auth()->id(),
                ]);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);
            }

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete role'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete role');
        }
    }

    /**
     * Toggle role status (active/inactive)
     *
     * @param Role $role
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Role $role, Request $request)
    {
        try {
            $oldStatus = $role->is_active;
            $role->update(['is_active' => !$role->is_active]);

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $role->is_active,
                    'ip_address' => request()->ip(),
                ])
                ->log('role_status_changed');

            Log::info('Role status changed', [
                'role_id' => $role->id,
                'name' => $role->name,
                'old_status' => $oldStatus,
                'new_status' => $role->is_active,
                'changed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role status updated successfully',
                'data' => [
                    'is_active' => $role->is_active,
                    'status_text' => $role->is_active ? 'Active' : 'Inactive'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle role status', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role status'
            ], 500);
        }
    }

    /**
     * Get role permissions
     *
     * @param Role $role
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPermissions(Role $role)
    {
        try {
            $permissions = $role->permissions()
                ->where('is_active', true)
                ->orderBy('group')
                ->orderBy('name')
                ->get()
                ->groupBy('group');

            return response()->json([
                'success' => true,
                'data' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get role permissions', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get role permissions'
            ], 500);
        }
    }

    /**
     * Bulk assign permissions to role
     *
     * @param Role $role
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignPermissions(Role $role, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($role, $request) {
                $permissions = Permission::whereIn('id', $request->permissions)
                    ->where('is_active', true)
                    ->pluck('id');

                $role->permissions()->sync($permissions);

                // Log activity
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($role)
                    ->withProperties([
                        'permissions_assigned' => $request->permissions,
                        'ip_address' => request()->ip(),
                    ])
                    ->log('role_permissions_assigned');

                Log::info('Role permissions assigned', [
                    'role_id' => $role->id,
                    'permissions_count' => count($request->permissions),
                    'assigned_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully',
                'data' => $role->load('permissions')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign permissions to role', [
                'role_id' => $role->id,
                'error' => $e->getMessage(),
                'permissions' => $request->permissions ?? []
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions'
            ], 500);
        }
    }
}
