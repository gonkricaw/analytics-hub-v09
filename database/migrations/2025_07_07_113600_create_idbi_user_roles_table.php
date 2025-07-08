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
     * Creates the user_roles pivot table for Analytics Hub user-role assignments.
     * This table establishes many-to-many relationships between users and roles.
     * Supports temporary role assignments and role scheduling.
     *
     * Related Feature: User Management & Role Assignment
     * Dependencies: users, roles tables
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Foreign key relationships
            $table->uuid('user_id')->comment('User ID reference');
            $table->uuid('role_id')->comment('Role ID reference');

            // Role assignment configuration
            $table->boolean('is_active')->default(true)->comment('Role assignment active status');
            $table->boolean('is_primary')->default(false)->comment('Primary role flag');
            $table->boolean('is_temporary')->default(false)->comment('Temporary role assignment flag');

            // Role scheduling and expiry
            $table->timestamp('starts_at')->nullable()->comment('Role assignment start timestamp');
            $table->timestamp('expires_at')->nullable()->comment('Role assignment expiry timestamp');
            $table->integer('duration_days')->nullable()->comment('Role assignment duration in days');

            // Assignment metadata
            $table->json('conditions')->nullable()->comment('Role assignment conditions (JSON)');
            $table->json('restrictions')->nullable()->comment('Role assignment restrictions (JSON)');
            $table->json('settings')->nullable()->comment('Role-specific user settings (JSON)');
            $table->string('assignment_reason', 200)->nullable()->comment('Reason for role assignment');

            // Administrative fields
            $table->uuid('assigned_by')->nullable()->comment('User who assigned this role');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this assignment');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate user-role assignments
            $table->unique(['user_id', 'role_id', 'deleted_at'], 'unique_user_role');

            // Indexes for performance optimization
            $table->index('user_id', 'idx_user_roles_user');
            $table->index('role_id', 'idx_user_roles_role');
            $table->index('is_active', 'idx_user_roles_active');
            $table->index('is_primary', 'idx_user_roles_primary');
            $table->index('is_temporary', 'idx_user_roles_temporary');
            $table->index('starts_at', 'idx_user_roles_starts');
            $table->index('expires_at', 'idx_user_roles_expires');
            $table->index('assigned_by', 'idx_user_roles_assigned_by');
            $table->index(['user_id', 'is_active'], 'idx_user_roles_user_active');
            $table->index(['role_id', 'is_active'], 'idx_user_roles_role_active');
            $table->index(['user_id', 'is_primary'], 'idx_user_roles_user_primary');
            $table->index(['expires_at', 'is_active'], 'idx_user_roles_expires_active');
            $table->index(['is_temporary', 'expires_at'], 'idx_user_roles_temporary_expires');
            $table->index('created_at', 'idx_user_roles_created');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "user_roles IS 'Analytics Hub user-roles pivot table - Many-to-many relationships between users and roles with scheduling and temporary assignment support'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
