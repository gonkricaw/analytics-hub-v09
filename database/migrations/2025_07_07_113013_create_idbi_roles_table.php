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
     * Creates the roles table for Analytics Hub role-based access control.
     * This table defines system roles that control user permissions and access levels.
     * Supports hierarchical role structure for the Analytics Hub system.
     *
     * Related Feature: Role & Permission Management
     * Dependencies: None (base table)
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Role identification and information
            $table->string('name', 50)->unique()->comment('Role name (unique identifier)');
            $table->string('display_name', 100)->comment('Human-readable role name');
            $table->text('description')->nullable()->comment('Role description and purpose');

            // Role hierarchy and organization
            $table->uuid('parent_id')->nullable()->comment('Parent role ID for hierarchy');
            $table->integer('level')->default(0)->comment('Role hierarchy level (0=root)');
            $table->string('slug', 60)->unique()->comment('URL-friendly role identifier');

            // Role configuration and behavior
            $table->boolean('is_system')->default(false)->comment('System role flag (cannot be deleted)');
            $table->boolean('is_active')->default(true)->comment('Role active status');
            $table->boolean('is_default')->default(false)->comment('Default role for new users');
            $table->integer('max_users')->nullable()->comment('Maximum users allowed for this role');

            // Permission inheritance and security
            $table->boolean('inherit_permissions')->default(false)->comment('Inherit parent role permissions');
            $table->json('restrictions')->nullable()->comment('Role-specific restrictions (JSON)');
            $table->integer('priority')->default(0)->comment('Role priority for conflict resolution');

            // Role metadata and settings
            $table->json('settings')->nullable()->comment('Role-specific settings (JSON)');
            $table->string('color', 7)->nullable()->comment('Role color for UI display');
            $table->string('icon', 50)->nullable()->comment('Role icon identifier');

            // Administrative fields
            $table->uuid('created_by')->nullable()->comment('User who created this role');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this role');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance optimization
            $table->index('name', 'idx_roles_name');
            $table->index('parent_id', 'idx_roles_parent');
            $table->index('level', 'idx_roles_level');
            $table->index('is_active', 'idx_roles_active');
            $table->index('is_system', 'idx_roles_system');
            $table->index('is_default', 'idx_roles_default');
            $table->index('priority', 'idx_roles_priority');
            $table->index(['parent_id', 'level'], 'idx_roles_hierarchy');
            $table->index(['is_active', 'is_system'], 'idx_roles_status');
            $table->index('created_at', 'idx_roles_created');

            // Foreign key constraints (self-referencing will be added after table creation)
            // $table->foreign('parent_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "roles IS 'Analytics Hub roles table - Role-based access control system with hierarchical structure and inheritance support'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
