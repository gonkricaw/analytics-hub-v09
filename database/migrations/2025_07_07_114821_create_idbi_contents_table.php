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
     * Creates the contents table for Analytics Hub content management.
     * This table stores both custom HTML content and encrypted embedded URLs.
     * Supports content versioning, access tracking, and security features.
     *
     * Related Feature: Content Management & Security
     * Dependencies: users table
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Content identification and organization
            $table->string('title', 200)->comment('Content title');
            $table->string('slug', 250)->unique()->comment('URL-friendly content identifier');
            $table->text('description')->nullable()->comment('Content description');
            $table->string('keywords', 500)->nullable()->comment('Content keywords for search');

            // Content type and data
            $table->enum('content_type', ['html', 'embedded', 'iframe', 'redirect'])->comment('Type of content');
            $table->longText('content')->nullable()->comment('HTML content or encrypted URL');
            $table->text('content_preview')->nullable()->comment('Content preview/excerpt');
            $table->string('original_url', 1000)->nullable()->comment('Original URL (for embedded content)');
            $table->text('encrypted_url')->nullable()->comment('AES-256 encrypted URL');
            $table->string('url_mask', 100)->nullable()->comment('UUID-based URL mask');

            // Content status and publishing
            $table->enum('status', ['draft', 'published', 'archived', 'expired'])->default('draft')->comment('Content status');
            $table->boolean('is_active')->default(true)->comment('Content active flag');
            $table->boolean('is_featured')->default(false)->comment('Featured content flag');
            $table->boolean('requires_auth')->default(true)->comment('Authentication required flag');
            $table->timestamp('published_at')->nullable()->comment('Content publication timestamp');
            $table->timestamp('expires_at')->nullable()->comment('Content expiry timestamp');

            // Content security and protection
            $table->boolean('prevent_copy')->default(false)->comment('Prevent copy/paste flag');
            $table->boolean('prevent_print')->default(false)->comment('Prevent printing flag');
            $table->boolean('prevent_inspect')->default(false)->comment('Prevent browser inspection flag');
            $table->boolean('disable_right_click')->default(false)->comment('Disable right-click flag');
            $table->json('security_settings')->nullable()->comment('Additional security settings (JSON)');

            // Content analytics and tracking
            $table->integer('view_count')->default(0)->comment('Total view count');
            $table->integer('unique_views')->default(0)->comment('Unique view count');
            $table->timestamp('last_viewed_at')->nullable()->comment('Last view timestamp');
            $table->uuid('last_viewed_by')->nullable()->comment('Last viewer user ID');
            $table->json('analytics_data')->nullable()->comment('Analytics data (JSON)');

            // Content metadata and settings
            $table->json('meta_data')->nullable()->comment('Content metadata (JSON)');
            $table->json('settings')->nullable()->comment('Content-specific settings (JSON)');
            $table->string('template', 100)->nullable()->comment('Content template identifier');
            $table->string('layout', 100)->default('default')->comment('Content layout');

            // Content versioning
            $table->integer('version')->default(1)->comment('Content version number');
            $table->uuid('parent_id')->nullable()->comment('Parent content ID for versions');
            $table->boolean('is_current_version')->default(true)->comment('Current version flag');
            $table->text('version_notes')->nullable()->comment('Version change notes');

            // Content embedding and iframe settings
            $table->integer('iframe_width')->nullable()->comment('Iframe width (pixels)');
            $table->integer('iframe_height')->nullable()->comment('Iframe height (pixels)');
            $table->string('iframe_sandbox', 500)->nullable()->comment('Iframe sandbox attributes');
            $table->boolean('allow_fullscreen')->default(false)->comment('Allow fullscreen flag');
            $table->json('iframe_settings')->nullable()->comment('Iframe-specific settings (JSON)');

            // Administrative fields
            $table->uuid('created_by')->nullable()->comment('User who created this content');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this content');
            $table->uuid('published_by')->nullable()->comment('User who published this content');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance optimization
            $table->index('slug', 'idx_contents_slug');
            $table->index('content_type', 'idx_contents_type');
            $table->index('status', 'idx_contents_status');
            $table->index('is_active', 'idx_contents_active');
            $table->index('is_featured', 'idx_contents_featured');
            $table->index('requires_auth', 'idx_contents_auth');
            $table->index('published_at', 'idx_contents_published');
            $table->index('expires_at', 'idx_contents_expires');
            $table->index('view_count', 'idx_contents_views');
            $table->index('last_viewed_at', 'idx_contents_last_viewed');
            $table->index('version', 'idx_contents_version');
            $table->index('parent_id', 'idx_contents_parent');
            $table->index('is_current_version', 'idx_contents_current');
            $table->index('url_mask', 'idx_contents_url_mask');
            $table->index(['status', 'is_active'], 'idx_contents_status_active');
            $table->index(['content_type', 'status'], 'idx_contents_type_status');
            $table->index(['published_at', 'expires_at'], 'idx_contents_publish_window');
            $table->index(['is_featured', 'published_at'], 'idx_contents_featured_published');
            $table->index('created_at', 'idx_contents_created');

            // Foreign key constraints (self-referencing will be added after table creation)
            // $table->foreign('parent_id')->references('id')->on('contents')->onDelete('set null');
            $table->foreign('last_viewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('published_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "contents IS 'Analytics Hub contents table - Comprehensive content management with HTML/embedded support, URL encryption, analytics tracking, and security features'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
