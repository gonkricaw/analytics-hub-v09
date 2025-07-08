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
     * Creates the users table for Analytics Hub user management.
     * This table serves as the central user authentication and management system
     * supporting role-based access control, password policies, and user tracking.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key - UUID for better security and distribution
            $table->uuid('id')->primary();

            // Basic user information
            $table->string('username', 50)->unique()->comment('Unique login username');
            $table->string('email', 100)->unique()->comment('User email address');
            $table->string('full_name', 150)->comment('User full name');
            $table->string('password')->comment('Hashed password');

            // User status and verification
            $table->enum('status', ['active', 'suspended', 'pending', 'expired'])
                  ->default('pending')
                  ->comment('User account status');
            $table->timestamp('email_verified_at')->nullable()->comment('Email verification timestamp');
            $table->boolean('is_first_login')->default(true)->comment('Track first login for password change');

            // Terms and conditions
            $table->boolean('terms_accepted')->default(false)->comment('T&C acceptance flag');
            $table->timestamp('terms_accepted_at')->nullable()->comment('T&C acceptance timestamp');
            $table->string('terms_version', 20)->nullable()->comment('Accepted T&C version');

            // Password policy tracking
            $table->timestamp('password_changed_at')->nullable()->comment('Last password change timestamp');
            $table->integer('password_change_count')->default(0)->comment('Total password changes');
            $table->boolean('password_expires')->default(true)->comment('Password expiration flag');
            $table->integer('password_expiry_days')->default(90)->comment('Password expiry period in days');

            // Security and access tracking
            $table->timestamp('last_login_at')->nullable()->comment('Last successful login timestamp');
            $table->string('last_login_ip', 45)->nullable()->comment('Last login IP address');
            $table->string('last_login_user_agent', 500)->nullable()->comment('Last login user agent');
            $table->integer('failed_login_attempts')->default(0)->comment('Failed login attempt counter');
            $table->timestamp('locked_until')->nullable()->comment('Account lock expiry timestamp');

            // Session management
            $table->string('remember_token', 100)->nullable()->comment('Remember me token');
            $table->timestamp('session_expires_at')->nullable()->comment('Session expiry timestamp');

            // User preferences
            $table->json('preferences')->nullable()->comment('User preferences (JSON)');
            $table->string('timezone', 50)->default('UTC')->comment('User timezone');
            $table->string('language', 5)->default('en')->comment('User language preference');

            // Administrative fields
            $table->uuid('created_by')->nullable()->comment('User who created this record');
            $table->uuid('updated_by')->nullable()->comment('User who last updated this record');
            $table->text('notes')->nullable()->comment('Administrative notes');

            // Audit timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance optimization
            $table->index('status', 'idx_users_status');
            $table->index('email_verified_at', 'idx_users_email_verified');
            $table->index('last_login_at', 'idx_users_last_login');
            $table->index('created_at', 'idx_users_created');
            $table->index('password_changed_at', 'idx_users_password_changed');
            $table->index(['status', 'email_verified_at'], 'idx_users_status_verified');
            $table->index('failed_login_attempts', 'idx_users_failed_attempts');

            // Foreign key constraints (will be added after related tables are created)
            // These will be added in subsequent migrations
            // $table->foreign('created_by')->references('id')->on('users');
            // $table->foreign('updated_by')->references('id')->on('users');
        });

        // Add table comment for documentation
        DB::statement("COMMENT ON TABLE " . config('database.connections.pgsql.prefix') . "users IS 'Analytics Hub users table - Central user management with UUID primary keys, soft deletes, and comprehensive security tracking'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
