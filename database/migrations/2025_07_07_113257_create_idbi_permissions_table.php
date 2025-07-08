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
     * Creates the permissions table for Analytics Hub permission management.
     * This table defines granular permissions that can be assigned to roles.
     * Supports modular permission system with categories and actions.
     *
     * Related Feature: Role & Permission Management
     * Dependencies: None (base table)
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Permission identification
            $table->string('name', 100)->unique()->comment('Permission name (unique identifier)');
            $table->string('display_name', 150)->comment('Human-readable permission name');
            $table->text('description')->nullable()->comment('Permission description and purpose');

            // Permission organization and categorization
            $table->string('module', 50)->comment('Module/feature this permission belongs to');
            $table->string('category', 50)->comment('Permission category (e.g., user, content, system)');
            $table->string('action', 50)->comment('Action type (create, read, update, delete, export, import)');
            $table->string('resource', 100)->nullable()->comment('Specific resource this permission applies to');

            // Permission configuration
            $table->string('slug', 120)->unique()->comment('URL-friendly permission identifier');
            $table->boolean('is_system')->default(false)->comment('System permission flag (cannot be deleted)');
            $table->boolean('is_active')->default(true)->comment('Permission active status');
            $table->integer('priority')->default(0)->comment('Permission priority for conflict resolution');

            // Permission metadata and restrictions
            $table->json('conditions')->nullable()->comment('Permission conditions and restrictions (JSON)');
            $table->json('settings')->nullable()->comment('Permission-specific settings (JSON)');
            $table->string('guard_name', 50)->default('web')->comment('Guard name for multi-auth systems');

            // Administrative fields
            $table->uuid('created_by')->nullable()->comment('User who created this permission');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this permission');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance optimization
            $table->index('name', 'idx_permissions_name');
            $table->index('module', 'idx_permissions_module');
            $table->index('category', 'idx_permissions_category');
            $table->index('action', 'idx_permissions_action');
            $table->index('resource', 'idx_permissions_resource');
            $table->index('is_active', 'idx_permissions_active');
            $table->index('is_system', 'idx_permissions_system');
            $table->index('priority', 'idx_permissions_priority');
            $table->index('guard_name', 'idx_permissions_guard');
            $table->index(['module', 'category'], 'idx_permissions_module_category');
            $table->index(['category', 'action'], 'idx_permissions_category_action');
            $table->index(['is_active', 'is_system'], 'idx_permissions_status');
            $table->index('created_at', 'idx_permissions_created');

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "permissions IS 'Analytics Hub permissions table - Granular permission system with modular organization and action-based access control'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
