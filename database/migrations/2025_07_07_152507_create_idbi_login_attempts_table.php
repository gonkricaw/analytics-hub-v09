<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create idbi_login_attempts table for tracking user login attempts and security
 *
 * This table tracks all login attempts (successful and failed) with detailed
 * information for security monitoring and analysis. It supports IP-based
 * security measures and activity analytics.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            // Primary key with UUID
            $table->uuid('id')->primary()->comment('Unique identifier for login attempt');

            // User reference (nullable for failed attempts with invalid email)
            $table->uuid('user_id')->nullable()->comment('Reference to user who attempted login');

            // Login attempt details
            $table->string('email', 255)->comment('Email address used in login attempt');
            $table->string('ip_address', 45)->comment('IP address of login attempt');
            $table->text('user_agent')->nullable()->comment('User agent string from browser');
            $table->json('browser_info')->nullable()->comment('Parsed browser information');
            $table->json('device_info')->nullable()->comment('Device information if available');

            // Attempt result
            $table->boolean('is_successful')->default(false)->comment('Whether login attempt was successful');
            $table->string('failure_reason', 100)->nullable()->comment('Reason for failed login attempt');
            $table->timestamp('attempted_at')->comment('When login attempt was made');

            // Security tracking
            $table->integer('attempt_count')->default(1)->comment('Number of attempts from this IP/user combination');
            $table->json('security_flags')->nullable()->comment('Security-related flags and warnings');

            // Geographic information (optional)
            $table->string('country_code', 2)->nullable()->comment('Country code based on IP');
            $table->string('region', 100)->nullable()->comment('Region/state based on IP');
            $table->string('city', 100)->nullable()->comment('City based on IP');

            // Additional tracking
            $table->string('session_id', 255)->nullable()->comment('Session ID if available');
            $table->text('notes')->nullable()->comment('Additional notes about the attempt');

            // Standard timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Indexes for performance
            $table->index(['user_id', 'attempted_at'], 'idx_login_attempts_user_date');
            $table->index(['ip_address', 'attempted_at'], 'idx_login_attempts_ip_date');
            $table->index(['email', 'attempted_at'], 'idx_login_attempts_email_date');
            $table->index(['is_successful', 'attempted_at'], 'idx_login_attempts_success_date');
            $table->index(['attempted_at'], 'idx_login_attempts_attempted_at');
            $table->index(['created_at'], 'idx_login_attempts_created_at');
            $table->index(['deleted_at'], 'idx_login_attempts_deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_attempts');
    }
};
