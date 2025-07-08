<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub User Activity Model
 *
 * Comprehensive user activity tracking system with
 * detailed logging, IP tracking, and analytics support.
 */
class UserActivity extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_activities';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'ip_address',
        'user_agent',
        'session_id',
        'url',
        'method',
        'route_name',
        'controller',
        'action',
        'parameters',
        'response_code',
        'response_time',
        'memory_usage',
        'metadata',
        'risk_score',
        'is_suspicious',
        'flagged_reason',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parameters' => 'array',
            'metadata' => 'array',
            'response_code' => 'integer',
            'response_time' => 'float',
            'memory_usage' => 'integer',
            'risk_score' => 'integer',
            'is_suspicious' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id', 'activity_type', 'description', 'ip_address',
                'risk_score', 'is_suspicious', 'flagged_reason'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user who performed this activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to filter by activity type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeFromIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to filter by session.
     */
    public function scopeFromSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope a query to only include suspicious activities.
     */
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    /**
     * Scope a query to only include non-suspicious activities.
     */
    public function scopeNotSuspicious($query)
    {
        return $query->where('is_suspicious', false);
    }

    /**
     * Scope a query to filter by risk score range.
     */
    public function scopeRiskScoreRange($query, $min, $max)
    {
        return $query->whereBetween('risk_score', [$min, $max]);
    }

    /**
     * Scope a query to only include high risk activities.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_score', '>=', 70);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by today's activities.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to filter by this week's activities.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to filter by this month's activities.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the activity age in human readable format.
     */
    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the risk level based on score.
     */
    public function getRiskLevelAttribute()
    {
        if ($this->risk_score >= 90) {
            return 'critical';
        } elseif ($this->risk_score >= 70) {
            return 'high';
        } elseif ($this->risk_score >= 50) {
            return 'medium';
        } elseif ($this->risk_score >= 30) {
            return 'low';
        } else {
            return 'minimal';
        }
    }

    /**
     * Get the browser from user agent.
     */
    public function getBrowserAttribute()
    {
        return $this->extractBrowserFromUserAgent($this->user_agent);
    }

    /**
     * Get the operating system from user agent.
     */
    public function getOperatingSystemAttribute()
    {
        return $this->extractOSFromUserAgent($this->user_agent);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Flag activity as suspicious.
     */
    public function flagAsSuspicious($reason = null)
    {
        $this->update([
            'is_suspicious' => true,
            'flagged_reason' => $reason,
            'risk_score' => max($this->risk_score, 70),
        ]);
    }

    /**
     * Clear suspicious flag.
     */
    public function clearSuspiciousFlag()
    {
        $this->update([
            'is_suspicious' => false,
            'flagged_reason' => null,
        ]);
    }

    /**
     * Update risk score.
     */
    public function updateRiskScore($score)
    {
        $this->update([
            'risk_score' => max(0, min(100, $score)),
        ]);
    }

    /**
     * Get location from IP address (stub for external service).
     */
    public function getLocationFromIp()
    {
        // This would integrate with a GeoIP service
        return [
            'country' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown',
        ];
    }

    /**
     * Extract browser from user agent string.
     */
    private function extractBrowserFromUserAgent($userAgent)
    {
        if (!$userAgent) return 'Unknown';

        $browsers = [
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Edge' => 'Edge',
            'Opera' => 'Opera',
            'Internet Explorer' => 'Trident',
        ];

        foreach ($browsers as $browser => $identifier) {
            if (str_contains($userAgent, $identifier)) {
                return $browser;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract OS from user agent string.
     */
    private function extractOSFromUserAgent($userAgent)
    {
        if (!$userAgent) return 'Unknown';

        $systems = [
            'Windows' => 'Windows',
            'MacOS' => 'Macintosh',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iOS' => 'iPhone|iPad',
        ];

        foreach ($systems as $os => $identifier) {
            if (preg_match("/$identifier/i", $userAgent)) {
                return $os;
            }
        }

        return 'Unknown';
    }

    /**
     * Log user activity.
     */
    public static function logActivity($data)
    {
        // Calculate risk score based on activity
        $riskScore = static::calculateRiskScore($data);

        $data['risk_score'] = $riskScore;
        $data['is_suspicious'] = $riskScore >= 70;

        return static::create($data);
    }

    /**
     * Calculate risk score for activity.
     */
    private static function calculateRiskScore($data)
    {
        $score = 0;

        // Base score for activity type
        $riskTypes = [
            'login' => 10,
            'logout' => 5,
            'failed_login' => 30,
            'password_change' => 20,
            'profile_update' => 15,
            'admin_action' => 40,
            'system_access' => 50,
            'data_export' => 60,
            'security_setting' => 70,
        ];

        $score += $riskTypes[$data['activity_type']] ?? 10;

        // Increase score for suspicious response codes
        if (isset($data['response_code'])) {
            if ($data['response_code'] >= 400) {
                $score += 20;
            }
        }

        // Increase score for high response times
        if (isset($data['response_time']) && $data['response_time'] > 5000) {
            $score += 10;
        }

        // Add IP-based risk (this would check against known bad IPs)
        if (isset($data['ip_address'])) {
            // Stub for IP reputation check
            $score += 0;
        }

        return min(100, max(0, $score));
    }

    /**
     * Get activity statistics.
     */
    public static function getStatistics($userId = null)
    {
        $query = $userId ? static::forUser($userId) : static::query();

        return [
            'total' => $query->count(),
            'today' => $query->today()->count(),
            'this_week' => $query->thisWeek()->count(),
            'this_month' => $query->thisMonth()->count(),
            'suspicious' => $query->suspicious()->count(),
            'high_risk' => $query->highRisk()->count(),
            'by_type' => static::getActivityTypeStats($userId),
        ];
    }

    /**
     * Get activity statistics by type.
     */
    public static function getActivityTypeStats($userId = null)
    {
        $query = $userId ? static::forUser($userId) : static::query();

        return $query->selectRaw('activity_type, count(*) as count')
                     ->groupBy('activity_type')
                     ->orderBy('count', 'desc')
                     ->get()
                     ->pluck('count', 'activity_type')
                     ->toArray();
    }

    /**
     * Get top IP addresses by activity count.
     */
    public static function getTopIpAddresses($limit = 10)
    {
        return static::selectRaw('ip_address, count(*) as count')
                    ->groupBy('ip_address')
                    ->orderBy('count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Clean up old activities.
     */
    public static function cleanupOldActivities($days = 90)
    {
        return static::where('created_at', '<', now()->subDays($days))
                    ->where('is_suspicious', false)
                    ->delete();
    }
}
