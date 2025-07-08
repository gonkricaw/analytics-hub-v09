<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Permission Management Controller
 *
 * Handles CRUD operations for system permissions
 * Part of the Role & Permission Management System
 *
 * Features:
 * - Permission listing with search and filtering
 * - Permission creation, editing, and deletion
 * - Module and category management
 * - Permission status management
 * - Activity logging for all operations
 *
 * Analytics Hub v0.9
 */
class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     *
     * Supports search, filtering by module, category, and status
     * Includes pagination and sorting options
     *
     * @param Request $request HTTP request with search and filter parameters
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Permission::query();

            // Search functionality
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'ilike', "%{$searchTerm}%")
                      ->orWhere('display_name', 'ilike', "%{$searchTerm}%")
                      ->orWhere('description', 'ilike', "%{$searchTerm}%")
                      ->orWhere('module', 'ilike', "%{$searchTerm}%");
                });
            }

            // Filter by module
            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }

            // Filter by category
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            // Filter by system permissions
            if ($request->filled('system')) {
                $query->where('is_system', $request->system === 'true');
            }

            // Sorting
            $sortField = $request->get('sort', 'module');
            $sortDirection = $request->get('direction', 'asc');

            $allowedSorts = ['name', 'display_name', 'module', 'category', 'action', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Add secondary sort for consistency
            if ($sortField !== 'name') {
                $query->orderBy('name', 'asc');
            }

            $permissions = $query->paginate(15)->withQueryString();

            // Get filter options for the UI
            $modules = Permission::distinct()->pluck('module')->sort()->values();
            $categories = Permission::distinct()->pluck('category')->sort()->values();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $permissions,
                    'filters' => [
                        'modules' => $modules,
                        'categories' => $categories
                    ]
                ]);
            }

            return view('admin.permissions.index', compact('permissions', 'modules', 'categories'));

        } catch (\Exception $e) {
            Log::error('Failed to load permissions', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load permissions'
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to load permissions');
        }
    }

    /**
     * Show the form for creating a new permission
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Get existing modules and categories for selection
        $modules = Permission::distinct()->pluck('module')->sort()->values();
        $categories = Permission::distinct()->pluck('category')->sort()->values();
        $actions = ['create', 'read', 'update', 'delete', 'export', 'import', 'manage'];

        return view('admin.permissions.create', compact('modules', 'categories', 'actions'));
    }

    /**
     * Store a newly created permission in storage
     *
     * @param Request $request HTTP request with permission data
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:permissions,name',
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'module' => 'required|string|max:50',
                'category' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'resource' => 'nullable|string|max:100',
                'is_system' => 'boolean',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0|max:1000',
                'conditions' => 'nullable|json',
                'settings' => 'nullable|json',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Generate slug from name
            $validatedData['slug'] = Str::slug($validatedData['name']);

            // Set defaults
            $validatedData['is_system'] = $request->boolean('is_system', false);
            $validatedData['is_active'] = $request->boolean('is_active', true);
            $validatedData['priority'] = $validatedData['priority'] ?? 0;

            $permission = Permission::create($validatedData);

            // Log the creation
            activity()
                ->performedOn($permission)
                ->log('Permission created');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission created successfully',
                    'data' => $permission
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission created successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();

        } catch (\Exception $e) {
            Log::error('Failed to create permission', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create permission'
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to create permission')
                           ->withInput();
        }
    }

    /**
     * Display the specified permission
     *
     * @param Permission $permission The permission to display
     * @param Request $request HTTP request
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Permission $permission, Request $request)
    {
        try {
            $permission->load(['roles']);

            // Get usage statistics
            $roleCount = $permission->roles()->count();
            $activeRoleCount = $permission->roles()->where('is_active', true)->count();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $permission,
                    'stats' => [
                        'role_count' => $roleCount,
                        'active_role_count' => $activeRoleCount
                    ]
                ]);
            }

            return view('admin.permissions.show', compact('permission', 'roleCount', 'activeRoleCount'));

        } catch (\Exception $e) {
            Log::error('Failed to load permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load permission'
                ], 500);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('error', 'Failed to load permission');
        }
    }

    /**
     * Show the form for editing the specified permission
     *
     * @param Permission $permission The permission to edit
     * @return \Illuminate\View\View
     */
    public function edit(Permission $permission)
    {
        // Get existing modules and categories for selection
        $modules = Permission::distinct()->pluck('module')->sort()->values();
        $categories = Permission::distinct()->pluck('category')->sort()->values();
        $actions = ['create', 'read', 'update', 'delete', 'export', 'import', 'manage'];

        return view('admin.permissions.edit', compact('permission', 'modules', 'categories', 'actions'));
    }

    /**
     * Update the specified permission in storage
     *
     * @param Request $request HTTP request with updated permission data
     * @param Permission $permission The permission to update
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Permission $permission)
    {
        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('permissions', 'name')->ignore($permission->id)
                ],
                'display_name' => 'required|string|max:100',
                'description' => 'nullable|string|max:500',
                'module' => 'required|string|max:50',
                'category' => 'required|string|max:50',
                'action' => 'required|string|max:50',
                'resource' => 'nullable|string|max:100',
                'is_system' => 'boolean',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0|max:1000',
                'conditions' => 'nullable|json',
                'settings' => 'nullable|json',
                'notes' => 'nullable|string|max:1000'
            ]);

            // Update slug if name changed
            if ($validatedData['name'] !== $permission->name) {
                $validatedData['slug'] = Str::slug($validatedData['name']);
            }

            // Handle boolean fields
            $validatedData['is_system'] = $request->boolean('is_system');
            $validatedData['is_active'] = $request->boolean('is_active');

            $permission->update($validatedData);

            // Log the update
            activity()
                ->performedOn($permission)
                ->log('Permission updated');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission updated successfully',
                    'data' => $permission
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                           ->withErrors($e->errors())
                           ->withInput();

        } catch (\Exception $e) {
            Log::error('Failed to update permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update permission'
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to update permission')
                           ->withInput();
        }
    }

    /**
     * Remove the specified permission from storage
     *
     * Prevents deletion of system permissions or permissions in use
     *
     * @param Permission $permission The permission to delete
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Permission $permission, Request $request)
    {
        try {
            // Prevent deletion of system permissions
            if ($permission->is_system) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete system permissions'
                    ], 422);
                }

                return redirect()->back()
                               ->with('error', 'Cannot delete system permissions');
            }

            // Check if permission is in use
            $roleCount = $permission->roles()->count();
            if ($roleCount > 0) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete permission: assigned to {$roleCount} role(s)"
                    ], 422);
                }

                return redirect()->back()
                               ->with('error', "Cannot delete permission: assigned to {$roleCount} role(s)");
            }

            // Log the deletion before it happens
            activity()
                ->performedOn($permission)
                ->log('Permission deleted');

            $permission->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission deleted successfully'
                ]);
            }

            return redirect()->route('admin.permissions.index')
                           ->with('success', 'Permission deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete permission', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete permission'
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to delete permission');
        }
    }

    /**
     * Toggle permission status (active/inactive)
     *
     * @param Permission $permission The permission to toggle
     * @param Request $request HTTP request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Permission $permission, Request $request)
    {
        try {
            $permission->update([
                'is_active' => !$permission->is_active
            ]);

            $status = $permission->is_active ? 'activated' : 'deactivated';

            // Log the status change
            activity()
                ->performedOn($permission)
                ->log("Permission {$status}");

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Permission {$status} successfully",
                    'data' => $permission
                ]);
            }

            return redirect()->back()
                           ->with('success', "Permission {$status} successfully");

        } catch (\Exception $e) {
            Log::error('Failed to toggle permission status', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle permission status'
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Failed to toggle permission status');
        }
    }
}
