<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Role & Permission Seeder
 *
 * Seeds initial roles and permissions for the Analytics Hub system
 * Creates system roles, custom roles, and basic permissions for testing
 *
 * Analytics Hub v0.9
 */
class RolePermissionSeeder extends Seeder
{    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Temporarily disable activity logging for seeding
        activity()->disableLogging();

        // Clear existing data to avoid conflicts
        $this->command->info('Clearing existing roles and permissions...');
        \DB::table('role_permissions')->delete();
        \DB::table('user_roles')->delete();
        Role::query()->forceDelete();
        Permission::query()->forceDelete();

        // Create Permissions first
        $permissions = [
            // User Management
            ['name' => 'users.view', 'display_name' => 'View Users', 'description' => 'Can view user listings and profiles', 'module' => 'users', 'category' => 'user_management', 'action' => 'read'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'description' => 'Can create new user accounts', 'module' => 'users', 'category' => 'user_management', 'action' => 'create'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'description' => 'Can edit existing user accounts', 'module' => 'users', 'category' => 'user_management', 'action' => 'update'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'description' => 'Can delete user accounts', 'module' => 'users', 'category' => 'user_management', 'action' => 'delete'],
            ['name' => 'users.manage_roles', 'display_name' => 'Manage User Roles', 'description' => 'Can assign and remove roles from users', 'module' => 'users', 'category' => 'user_management', 'action' => 'update'],

            // Role Management
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'description' => 'Can view role listings and details', 'module' => 'roles', 'category' => 'role_management', 'action' => 'read'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'description' => 'Can create new roles', 'module' => 'roles', 'category' => 'role_management', 'action' => 'create'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'description' => 'Can edit existing roles', 'module' => 'roles', 'category' => 'role_management', 'action' => 'update'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'description' => 'Can delete roles', 'module' => 'roles', 'category' => 'role_management', 'action' => 'delete'],
            ['name' => 'permissions.manage', 'display_name' => 'Manage Permissions', 'description' => 'Can assign and remove permissions', 'module' => 'permissions', 'category' => 'role_management', 'action' => 'update'],

            // Content Management
            ['name' => 'content.view', 'display_name' => 'View Content', 'description' => 'Can view content listings', 'module' => 'content', 'category' => 'content_management', 'action' => 'read'],
            ['name' => 'content.create', 'display_name' => 'Create Content', 'description' => 'Can create new content', 'module' => 'content', 'category' => 'content_management', 'action' => 'create'],
            ['name' => 'content.edit', 'display_name' => 'Edit Content', 'description' => 'Can edit existing content', 'module' => 'content', 'category' => 'content_management', 'action' => 'update'],
            ['name' => 'content.delete', 'display_name' => 'Delete Content', 'description' => 'Can delete content', 'module' => 'content', 'category' => 'content_management', 'action' => 'delete'],
            ['name' => 'content.publish', 'display_name' => 'Publish Content', 'description' => 'Can publish and unpublish content', 'module' => 'content', 'category' => 'content_management', 'action' => 'update'],

            // Analytics
            ['name' => 'analytics.view', 'display_name' => 'View Analytics', 'description' => 'Can view analytics reports', 'module' => 'analytics', 'category' => 'analytics', 'action' => 'read'],
            ['name' => 'analytics.export', 'display_name' => 'Export Analytics', 'description' => 'Can export analytics data', 'module' => 'analytics', 'category' => 'analytics', 'action' => 'export'],
            ['name' => 'analytics.advanced', 'display_name' => 'Advanced Analytics', 'description' => 'Can access advanced analytics features', 'module' => 'analytics', 'category' => 'analytics', 'action' => 'read'],

            // System Administration
            ['name' => 'system.view', 'display_name' => 'View System Settings', 'description' => 'Can view system configuration', 'module' => 'system', 'category' => 'system_administration', 'action' => 'read'],
            ['name' => 'system.edit', 'display_name' => 'Edit System Settings', 'description' => 'Can edit system configuration', 'module' => 'system', 'category' => 'system_administration', 'action' => 'update'],
            ['name' => 'system.backup', 'display_name' => 'System Backup', 'description' => 'Can perform system backups', 'module' => 'system', 'category' => 'system_administration', 'action' => 'export'],
            ['name' => 'system.maintenance', 'display_name' => 'System Maintenance', 'description' => 'Can perform system maintenance', 'module' => 'system', 'category' => 'system_administration', 'action' => 'update'],

            // Terms & Conditions
            ['name' => 'terms.manage', 'display_name' => 'Manage Terms', 'description' => 'Can update Terms & Conditions', 'module' => 'terms', 'category' => 'terms_management', 'action' => 'update'],
            ['name' => 'terms.view_stats', 'display_name' => 'View Terms Stats', 'description' => 'Can view Terms & Conditions acceptance statistics', 'module' => 'terms', 'category' => 'terms_management', 'action' => 'read'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create([
                'id' => Str::uuid(),
                'name' => $permissionData['name'],
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description'],
                'module' => $permissionData['module'],
                'category' => $permissionData['category'],
                'action' => $permissionData['action'],
                'slug' => str_replace('.', '-', $permissionData['name']),
                'is_active' => true,
                'is_system' => in_array($permissionData['module'], ['system', 'terms']), // Mark system modules as system permissions
            ]);
        }

        // Create System Roles
        $superAdminRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Full system access with all permissions',
            'slug' => 'super-administrator',
            'is_system' => true,
            'is_active' => true,
        ]);

        $adminRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Administrative access with most permissions',
            'slug' => 'administrator',
            'is_system' => true,
            'is_active' => true,
        ]);

        $managerRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Management level access for content and users',
            'slug' => 'manager',
            'is_system' => true,
            'is_active' => true,
        ]);

        $editorRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'editor',
            'display_name' => 'Editor',
            'description' => 'Content creation and editing permissions',
            'slug' => 'editor',
            'is_system' => false,
            'is_active' => true,
        ]);

        $analystRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'analyst',
            'display_name' => 'Data Analyst',
            'description' => 'Analytics viewing and reporting permissions',
            'slug' => 'data-analyst',
            'is_system' => false,
            'is_active' => true,
        ]);

        $userRole = Role::create([
            'id' => Str::uuid(),
            'name' => 'user',
            'display_name' => 'Standard User',
            'description' => 'Basic user permissions',
            'slug' => 'standard-user',
            'is_system' => true,
            'is_active' => true,
        ]);

        // Assign permissions to roles
        $allPermissions = Permission::all();

        // Super Admin gets all permissions
        $superAdminRole->permissions()->attach($allPermissions->pluck('id'));

        // Admin gets all except some system-level permissions
        $adminPermissions = $allPermissions->whereNotIn('name', ['system.backup', 'system.maintenance']);
        $adminRole->permissions()->attach($adminPermissions->pluck('id'));

        // Manager gets user and content management permissions
        $managerPermissions = $allPermissions->whereIn('category', ['user_management', 'content_management', 'analytics']);
        $managerRole->permissions()->attach($managerPermissions->pluck('id'));

        // Editor gets content management permissions
        $editorPermissions = $allPermissions->whereIn('category', ['content_management'])->whereNotIn('name', ['content.delete']);
        $editorRole->permissions()->attach($editorPermissions->pluck('id'));

        // Analyst gets analytics permissions
        $analystPermissions = $allPermissions->whereIn('category', ['analytics']);
        $analystRole->permissions()->attach($analystPermissions->pluck('id'));

        // User gets basic view permissions
        $userPermissions = $allPermissions->whereIn('name', ['content.view', 'analytics.view']);
        $userRole->permissions()->attach($userPermissions->pluck('id'));

        // Assign roles to existing users if any
        $users = User::all();
        if ($users->count() > 0) {
            // Assign first user as super admin
            $firstUser = $users->first();
            $firstUser->roles()->attach($superAdminRole->id);

            // Assign remaining users random roles for testing
            foreach ($users->skip(1) as $user) {
                $randomRole = [$adminRole, $managerRole, $editorRole, $analystRole, $userRole][rand(0, 4)];
                $user->roles()->attach($randomRole->id);
            }
        }

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info("Created {$allPermissions->count()} permissions and 6 roles");
        $this->command->info("Assigned roles to {$users->count()} users");

        // Re-enable activity logging
        activity()->enableLogging();
    }
}
