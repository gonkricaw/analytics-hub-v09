<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Create view v_top_active_users for monthly login statistics
 *
 * This view provides insights into the most active users based on their login activity
 * within the current month. It aggregates login attempts to show user engagement.
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
        // Create view for top active users based on monthly login statistics
        $prefix = config('database.connections.pgsql.prefix');
        DB::statement("
            CREATE OR REPLACE VIEW v_top_active_users AS
            SELECT
                u.id,
                u.email,
                u.full_name,
                u.username,
                COUNT(DISTINCT DATE(la.attempted_at)) as login_days_count,
                COUNT(la.id) as total_login_attempts,
                COUNT(CASE WHEN la.is_successful = true THEN 1 END) as successful_logins,
                COUNT(CASE WHEN la.is_successful = false THEN 1 END) as failed_logins,
                ROUND(
                    (COUNT(CASE WHEN la.is_successful = true THEN 1 END) * 100.0 /
                     NULLIF(COUNT(la.id), 0)), 2
                ) as success_rate_percentage,
                MAX(la.attempted_at) as last_login_attempt,
                u.status,
                u.email_verified_at,
                u.created_at as user_created_at
            FROM {$prefix}users u
            LEFT JOIN {$prefix}login_attempts la ON u.id = la.user_id
                AND la.attempted_at >= DATE_TRUNC('month', CURRENT_DATE)
                AND la.attempted_at < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month'
                AND la.deleted_at IS NULL
            WHERE u.deleted_at IS NULL
            GROUP BY
                u.id, u.email, u.full_name, u.username,
                u.status, u.email_verified_at, u.created_at
            ORDER BY
                successful_logins DESC,
                login_days_count DESC,
                total_login_attempts DESC
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the view
        $prefix = config('database.connections.pgsql.prefix');
        DB::statement("DROP VIEW IF EXISTS v_top_active_users");
    }
};
