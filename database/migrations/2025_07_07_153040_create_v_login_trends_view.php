<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Create view v_login_trends for 15-day login data
 *
 * This view provides daily login trends for the last 15 days, showing successful
 * and failed login attempts, unique users, and login patterns.
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
        // Create view for login trends over the last 15 days
        $prefix = config('database.connections.pgsql.prefix');
        DB::statement("
            CREATE OR REPLACE VIEW v_login_trends AS
            SELECT
                DATE(la.attempted_at) as login_date,
                COUNT(la.id) as total_attempts,
                COUNT(CASE WHEN la.is_successful = true THEN 1 END) as successful_logins,
                COUNT(CASE WHEN la.is_successful = false THEN 1 END) as failed_logins,
                COUNT(DISTINCT la.user_id) as unique_users,
                COUNT(DISTINCT la.ip_address) as unique_ip_addresses,
                ROUND(
                    (COUNT(CASE WHEN la.is_successful = true THEN 1 END) * 100.0 /
                     NULLIF(COUNT(la.id), 0)), 2
                ) as success_rate_percentage,
                -- Peak hour analysis
                EXTRACT(HOUR FROM la.attempted_at) as peak_hour,
                COUNT(*) as hour_attempts,
                -- Weekend vs weekday classification
                CASE
                    WHEN EXTRACT(DOW FROM la.attempted_at) IN (0, 6) THEN 'Weekend'
                    ELSE 'Weekday'
                END as day_type,
                -- Time range classification
                CASE
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 6 AND 11 THEN 'Morning'
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 12 AND 17 THEN 'Afternoon'
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 18 AND 23 THEN 'Evening'
                    ELSE 'Night'
                END as time_period
            FROM {$prefix}login_attempts la
            WHERE la.attempted_at >= CURRENT_DATE - INTERVAL '15 days'
                AND la.attempted_at < CURRENT_DATE + INTERVAL '1 day'
                AND la.deleted_at IS NULL
            GROUP BY
                DATE(la.attempted_at),
                EXTRACT(HOUR FROM la.attempted_at),
                CASE
                    WHEN EXTRACT(DOW FROM la.attempted_at) IN (0, 6) THEN 'Weekend'
                    ELSE 'Weekday'
                END,
                CASE
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 6 AND 11 THEN 'Morning'
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 12 AND 17 THEN 'Afternoon'
                    WHEN EXTRACT(HOUR FROM la.attempted_at) BETWEEN 18 AND 23 THEN 'Evening'
                    ELSE 'Night'
                END
            ORDER BY
                login_date DESC,
                peak_hour ASC
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
        DB::statement("DROP VIEW IF EXISTS v_login_trends");
    }
};
