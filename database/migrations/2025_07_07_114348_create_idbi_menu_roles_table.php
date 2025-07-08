<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the idbi_menu_roles pivot table for Analytics Hub menu-role relationships.
     * This table controls which roles can access specific menu items.
     * Supports conditional access and menu visibility rules per role.
     *
     * Related Feature: Menu Management & Role-based Navigation
     * Dependencies: menus, idbi_roles tables
     */
    public function up(): void
    {
        Schema::create('menu_roles', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Foreign key relationships
            $table->uuid('menu_id')->comment('Menu ID reference');
            $table->uuid('role_id')->comment('Role ID reference');

            // Access control configuration
            $table->boolean('can_view')->default(true)->comment('Can view menu item');
            $table->boolean('can_access')->default(true)->comment('Can access menu content');
            $table->boolean('is_visible')->default(true)->comment('Menu visibility for this role');
            $table->boolean('is_restricted')->default(false)->comment('Additional restrictions applied');

            // Conditional access and restrictions
            $table->json('conditions')->nullable()->comment('Access conditions (JSON)');
            $table->json('restrictions')->nullable()->comment('Access restrictions (JSON)');
            $table->json('settings')->nullable()->comment('Role-specific menu settings (JSON)');

            // Time-based access control
            $table->timestamp('access_starts_at')->nullable()->comment('Access start timestamp');
            $table->timestamp('access_expires_at')->nullable()->comment('Access expiry timestamp');
            $table->json('time_restrictions')->nullable()->comment('Time-based access rules (JSON)');

            // Menu customization per role
            $table->string('custom_label', 150)->nullable()->comment('Custom menu label for this role');
            $table->string('custom_icon', 100)->nullable()->comment('Custom icon for this role');
            $table->string('custom_url', 500)->nullable()->comment('Custom URL override for this role');
            $table->integer('custom_sort_order')->nullable()->comment('Custom sort order for this role');

            // Administrative fields
            $table->uuid('granted_by')->nullable()->comment('User who granted menu access');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this assignment');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate menu-role assignments
            $table->unique(['menu_id', 'role_id', 'deleted_at'], 'unique_menu_role');

            // Indexes for performance optimization
            $table->index('menu_id', 'idx_menu_roles_menu');
            $table->index('role_id', 'idx_menu_roles_role');
            $table->index('can_view', 'idx_menu_roles_view');
            $table->index('can_access', 'idx_menu_roles_access');
            $table->index('is_visible', 'idx_menu_roles_visible');
            $table->index('is_restricted', 'idx_menu_roles_restricted');
            $table->index('access_starts_at', 'idx_menu_roles_starts');
            $table->index('access_expires_at', 'idx_menu_roles_expires');
            $table->index('granted_by', 'idx_menu_roles_granted_by');
            $table->index(['menu_id', 'can_view'], 'idx_menu_roles_menu_view');
            $table->index(['role_id', 'can_view'], 'idx_menu_roles_role_view');
            $table->index(['menu_id', 'is_visible'], 'idx_menu_roles_menu_visible');
            $table->index(['role_id', 'is_visible'], 'idx_menu_roles_role_visible');
            $table->index(['can_view', 'can_access', 'is_visible'], 'idx_menu_roles_permissions');
            $table->index(['access_expires_at', 'can_access'], 'idx_menu_roles_expires_access');
            $table->index('created_at', 'idx_menu_roles_created');

            // Foreign key constraints
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "menu_roles IS 'Analytics Hub menu-roles pivot table - Role-based menu access control with conditional visibility and time-based restrictions'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_roles');
    }
};
