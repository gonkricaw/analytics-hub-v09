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
     * Creates the menus table for Analytics Hub hierarchical menu system.
     * This table supports up to 3-level menu hierarchy with role-based visibility.
     * Includes icon support, ordering, and dynamic menu generation capabilities.
     *
     * Related Feature: Menu Management & Navigation
     * Dependencies: users table
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Menu identification and hierarchy
            $table->string('name', 100)->comment('Menu item name');
            $table->string('display_name', 150)->comment('Display name for UI');
            $table->string('slug', 120)->unique()->comment('URL-friendly menu identifier');
            $table->uuid('parent_id')->nullable()->comment('Parent menu ID for hierarchy');
            $table->integer('level')->default(0)->comment('Menu level (0=root, 1=child, 2=sub-child)');
            $table->integer('sort_order')->default(0)->comment('Menu item sort order');

            // Menu content and routing
            $table->string('url', 500)->nullable()->comment('Menu URL/route');
            $table->string('route_name', 100)->nullable()->comment('Laravel route name');
            $table->json('route_params')->nullable()->comment('Route parameters (JSON)');
            $table->enum('target', ['_self', '_blank', '_parent', '_top'])->default('_self')->comment('Link target');

            // Menu appearance and behavior
            $table->string('icon', 100)->nullable()->comment('Icon identifier (Iconify)');
            $table->string('icon_color', 7)->nullable()->comment('Icon color (hex)');
            $table->string('css_class', 200)->nullable()->comment('Additional CSS classes');
            $table->text('description')->nullable()->comment('Menu description');

            // Menu status and configuration
            $table->boolean('is_active')->default(true)->comment('Menu active status');
            $table->boolean('is_visible')->default(true)->comment('Menu visibility flag');
            $table->boolean('is_system')->default(false)->comment('System menu flag (cannot be deleted)');
            $table->boolean('requires_auth')->default(true)->comment('Authentication required flag');

            // Menu type and functionality
            $table->enum('menu_type', ['link', 'dropdown', 'separator', 'header'])->default('link')->comment('Menu item type');
            $table->string('content_type', 50)->nullable()->comment('Associated content type');
            $table->uuid('content_id')->nullable()->comment('Associated content ID');

            // Menu permissions and access control
            $table->json('permissions')->nullable()->comment('Required permissions (JSON array)');
            $table->json('conditions')->nullable()->comment('Display conditions (JSON)');
            $table->json('settings')->nullable()->comment('Menu-specific settings (JSON)');

            // Menu metadata
            $table->string('tooltip', 200)->nullable()->comment('Menu tooltip text');
            $table->integer('click_count')->default(0)->comment('Menu click counter');
            $table->timestamp('last_accessed')->nullable()->comment('Last access timestamp');

            // Administrative fields
            $table->uuid('created_by')->nullable()->comment('User who created this menu');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this menu');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance optimization
            $table->index('parent_id', 'idx_menus_parent');
            $table->index('level', 'idx_menus_level');
            $table->index('sort_order', 'idx_menus_sort');
            $table->index('is_active', 'idx_menus_active');
            $table->index('is_visible', 'idx_menus_visible');
            $table->index('is_system', 'idx_menus_system');
            $table->index('menu_type', 'idx_menus_type');
            $table->index('route_name', 'idx_menus_route');
            $table->index('content_type', 'idx_menus_content_type');
            $table->index('content_id', 'idx_menus_content_id');
            $table->index(['parent_id', 'sort_order'], 'idx_menus_parent_sort');
            $table->index(['level', 'is_active'], 'idx_menus_level_active');
            $table->index(['is_active', 'is_visible'], 'idx_menus_status');            $table->index(['parent_id', 'level', 'sort_order'], 'idx_menus_hierarchy');
            $table->index('created_at', 'idx_menus_created');

            // Foreign key constraints
            // Self-referencing foreign key will be added after table creation
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add check constraint for 3-level hierarchy limit using raw SQL
        DB::statement('ALTER TABLE ' . config('database.connections.pgsql.prefix') . 'menus ADD CONSTRAINT chk_menu_level_limit CHECK (level >= 0 AND level <= 2)');

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "menus IS 'Analytics Hub menus table - Hierarchical menu system with 3-level support, role-based visibility, and dynamic content association'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
