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
     * Creates the idbi_content_roles pivot table for content-role access control.
     * Controls which roles can access specific content items.
     *
     * Related Feature: Content Management & Role-based Access
     * Dependencies: contents, idbi_roles tables
     */
    public function up(): void
    {
        Schema::create('content_roles', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Foreign key relationships
            $table->uuid('content_id')->comment('Content ID reference');
            $table->uuid('role_id')->comment('Role ID reference');

            // Access control configuration
            $table->boolean('can_view')->default(true)->comment('Can view content');
            $table->boolean('can_edit')->default(false)->comment('Can edit content');
            $table->boolean('can_delete')->default(false)->comment('Can delete content');
            $table->boolean('can_publish')->default(false)->comment('Can publish content');

            // Time-based access control
            $table->timestamp('access_starts_at')->nullable()->comment('Access start timestamp');
            $table->timestamp('access_expires_at')->nullable()->comment('Access expiry timestamp');

            // Administrative fields
            $table->uuid('granted_by')->nullable()->comment('User who granted content access');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this assignment');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate content-role assignments
            $table->unique(['content_id', 'role_id', 'deleted_at'], 'unique_content_role');

            // Indexes for performance optimization
            $table->index('content_id', 'idx_content_roles_content');
            $table->index('role_id', 'idx_content_roles_role');
            $table->index('can_view', 'idx_content_roles_view');
            $table->index('can_edit', 'idx_content_roles_edit');
            $table->index(['content_id', 'can_view'], 'idx_content_roles_content_view');
            $table->index(['role_id', 'can_view'], 'idx_content_roles_role_view');

            // Foreign key constraints
            $table->foreign('content_id')->references('id')->on('contents')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "content_roles IS 'Analytics Hub content-roles pivot table - Role-based content access control'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_roles');
    }
};
