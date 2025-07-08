<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Login Attempt Model
 *
 * Comprehensive login attempt tracking system with
 * security analysis, IP tracking, and threat detection.
 */
class LoginAttempt extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_attempts';

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
        'email',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
        'session_id',
        'location',
        'device_fingerprint',
        'two_factor_used',
        'metadata',
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
            'success' => 'boolean',
            'two_factor_used' => 'boolean',
            'location' => 'array',
            'metadata' => 'array',
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
                'user_id', 'email', 'ip_address', 'success', 'failure_reason',
                'two_factor_used'
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
     * Get the user who made this login attempt.
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
     * Scope a query to only include successful attempts.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope a query to only include failed attempts.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by email.
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeFromIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by today's attempts.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to filter by this week's attempts.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to filter by this month's attempts.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope a query to filter by recent attempts (last hour).
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the attempt age in human readable format.
     */
    public function getAgeAttribute()
    {
        return $this->created_at->diffForHumans();
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

    /**
     * Get the country from location data.
     */
    public function getCountryAttribute()
    {
        return $this->location['country'] ?? 'Unknown';
    }

    /**
     * Get the city from location data.
     */
    public function getCityAttribute()
    {
        return $this->location['city'] ?? 'Unknown';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

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
     * Log a login attempt.
     */
    public static function logAttempt($data)
    {
        // Enrich data with additional information
        $enrichedData = array_merge($data, [
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'location' => static::getLocationFromIp($data['ip_address']),
            'device_fingerprint' => static::generateDeviceFingerprint(),
        ]);

        return static::create($enrichedData);
    }

    /**
     * Get location from IP address (stub for external service).
     */
    private static function getLocationFromIp($ipAddress)
    {
        // This would integrate with a GeoIP service
        return [
            'country' => 'Unknown',
            'country_code' => 'XX',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => null,
            'longitude' => null,
        ];
    }

    /**
     * Generate device fingerprint.
     */
    private static function generateDeviceFingerprint()
    {
        $components = [
            request()->userAgent(),
            request()->ip(),
            request()->header('Accept-Language'),
            request()->header('Accept-Encoding'),
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Get failed attempts count for IP in time period.
     */
    public static function getFailedAttemptsForIp($ipAddress, $minutes = 60)
    {
        return static::failed()
                    ->fromIp($ipAddress)
                    ->where('created_at', '>=', now()->subMinutes($minutes))
                    ->count();
    }

    /**
     * Get failed attempts count for email in time period.
     */
    public static function getFailedAttemptsForEmail($email, $minutes = 60)
    {
        return static::failed()
                    ->forEmail($email)
                    ->where('created_at', '>=', now()->subMinutes($minutes))
                    ->count();
    }

    /**
     * Check if IP should be blocked.
     */
    public static function shouldBlockIp($ipAddress, $threshold = 10, $minutes = 60)
    {
        return static::getFailedAttemptsForIp($ipAddress, $minutes) >= $threshold;
    }

    /**
     * Check if email should be blocked.
     */
    public static function shouldBlockEmail($email, $threshold = 5, $minutes = 60)
    {
        return static::getFailedAttemptsForEmail($email, $minutes) >= $threshold;
    }

    /**
     * Get login statistics.
     */
    public static function getStatistics($period = 'today')
    {
        $query = static::query();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        return [
            'total' => $query->count(),
            'successful' => $query->successful()->count(),
            'failed' => $query->failed()->count(),
            'success_rate' => $query->count() > 0 ? round(($query->successful()->count() / $query->count()) * 100, 2) : 0,
            'unique_ips' => $query->distinct('ip_address')->count(),
            'unique_users' => $query->whereNotNull('user_id')->distinct('user_id')->count(),
            'with_2fa' => $query->where('two_factor_used', true)->count(),
        ];
    }

    /**
     * Get top IP addresses by attempt count.
     */
    public static function getTopIpAddresses($limit = 10, $period = 'today')
    {
        $query = static::query();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }

        return $query->selectRaw('ip_address, count(*) as attempts, sum(case when success then 1 else 0 end) as successful')
                     ->groupBy('ip_address')
                     ->orderBy('attempts', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Get failed attempts by country.
     */
    public static function getFailedAttemptsByCountry($limit = 10)
    {
        return static::failed()
                    ->whereNotNull('location')
                    ->get()
                    ->groupBy('country')
                    ->map(function ($attempts) {
                        return $attempts->count();
                    })
                    ->sortDesc()
                    ->take($limit);
    }

    /**
     * Get login trends (hourly data for last 24 hours).
     */
    public static function getLoginTrends()
    {
        $trends = [];

        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $hourStart = $hour->copy()->startOfHour();
            $hourEnd = $hour->copy()->endOfHour();

            $total = static::whereBetween('created_at', [$hourStart, $hourEnd])->count();
            $successful = static::successful()->whereBetween('created_at', [$hourStart, $hourEnd])->count();
            $failed = static::failed()->whereBetween('created_at', [$hourStart, $hourEnd])->count();

            $trends[] = [
                'hour' => $hour->format('H:00'),
                'total' => $total,
                'successful' => $successful,
                'failed' => $failed,
            ];
        }

        return $trends;
    }

    /**
     * Clean up old login attempts.
     */
    public static function cleanupOldAttempts($days = 90)
    {
        return static::where('created_at', '<', now()->subDays($days))
                    ->delete();
    }

    /**
     * Get suspicious login patterns.
     */
    public static function getSuspiciousPatterns()
    {
        return [
            'multiple_ips_same_user' => static::getMultipleIpsSameUser(),
            'multiple_users_same_ip' => static::getMultipleUsersSameIp(),
            'rapid_failed_attempts' => static::getRapidFailedAttempts(),
            'unusual_locations' => static::getUnusualLocations(),
        ];
    }

    /**
     * Get users with multiple IP addresses.
     */
    private static function getMultipleIpsSameUser()
    {
        return static::selectRaw('user_id, count(distinct ip_address) as ip_count')
                    ->whereNotNull('user_id')
                    ->recent()
                    ->groupBy('user_id')
                    ->having('ip_count', '>', 3)
                    ->orderBy('ip_count', 'desc')
                    ->get();
    }

    /**
     * Get IPs with multiple users.
     */
    private static function getMultipleUsersSameIp()
    {
        return static::selectRaw('ip_address, count(distinct user_id) as user_count')
                    ->whereNotNull('user_id')
                    ->recent()
                    ->groupBy('ip_address')
                    ->having('user_count', '>', 5)
                    ->orderBy('user_count', 'desc')
                    ->get();
    }

    /**
     * Get rapid failed attempts.
     */
    private static function getRapidFailedAttempts()
    {
        return static::failed()
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->selectRaw('ip_address, count(*) as attempts')
                    ->groupBy('ip_address')
                    ->having('attempts', '>', 5)
                    ->orderBy('attempts', 'desc')
                    ->get();
    }

    /**
     * Get unusual locations (stub implementation).
     */
    private static function getUnusualLocations()
    {
        // This would analyze user's typical locations vs current attempts
        return collect();
    }
}
