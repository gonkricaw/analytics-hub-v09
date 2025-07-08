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
     * Creates the role_permissions pivot table for Analytics Hub RBAC system.
     * This table establishes many-to-many relationships between roles and permissions.
     * Supports conditional permissions and permission overrides.
     *
     * Related Feature: Role & Permission Management
     * Dependencies: roles, permissions tables
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Foreign key relationships
            $table->uuid('role_id')->comment('Role ID reference');
            $table->uuid('permission_id')->comment('Permission ID reference');

            // Permission configuration and overrides
            $table->boolean('is_granted')->default(true)->comment('Permission granted flag');
            $table->boolean('is_denied')->default(false)->comment('Permission explicitly denied flag');
            $table->boolean('is_inherited')->default(false)->comment('Permission inherited from parent role');

            // Conditional permissions and restrictions
            $table->json('conditions')->nullable()->comment('Permission conditions (JSON)');
            $table->json('restrictions')->nullable()->comment('Permission restrictions (JSON)');
            $table->json('settings')->nullable()->comment('Permission-specific settings (JSON)');

            // Permission metadata
            $table->string('scope', 100)->nullable()->comment('Permission scope (global, department, etc.)');
            $table->timestamp('expires_at')->nullable()->comment('Permission expiry timestamp');
            $table->integer('priority')->default(0)->comment('Permission priority for conflict resolution');

            // Administrative fields
            $table->uuid('granted_by')->nullable()->comment('User who granted this permission');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this permission');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate role-permission assignments
            $table->unique(['role_id', 'permission_id', 'deleted_at'], 'unique_role_permission');

            // Indexes for performance optimization
            $table->index('role_id', 'idx_role_permissions_role');
            $table->index('permission_id', 'idx_role_permissions_permission');
            $table->index('is_granted', 'idx_role_permissions_granted');
            $table->index('is_denied', 'idx_role_permissions_denied');
            $table->index('is_inherited', 'idx_role_permissions_inherited');
            $table->index('expires_at', 'idx_role_permissions_expires');
            $table->index('priority', 'idx_role_permissions_priority');
            $table->index('scope', 'idx_role_permissions_scope');
            $table->index(['role_id', 'is_granted'], 'idx_role_permissions_role_granted');
            $table->index(['permission_id', 'is_granted'], 'idx_role_permissions_permission_granted');
            $table->index(['is_granted', 'is_denied'], 'idx_role_permissions_status');
            $table->index('created_at', 'idx_role_permissions_created');

            // Foreign key constraints
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "role_permissions IS 'Analytics Hub role-permissions pivot table - Many-to-many relationships between roles and permissions with conditional access control'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
