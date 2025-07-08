<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Blacklisted IP Model
 *
 * IP address blacklisting system with automatic detection,
 * manual management, and comprehensive security tracking.
 */
class BlacklistedIp extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blacklisted_ips';

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
        'ip_address',
        'subnet_mask',
        'reason',
        'severity',
        'is_active',
        'is_automatic',
        'trigger_count',
        'last_attempt_at',
        'expires_at',
        'whitelisted_at',
        'whitelisted_by',
        'metadata',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_automatic' => 'boolean',
            'trigger_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'expires_at' => 'datetime',
            'whitelisted_at' => 'datetime',
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
        'last_attempt_at',
        'expires_at',
        'whitelisted_at',
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
                'ip_address', 'subnet_mask', 'reason', 'severity', 'is_active',
                'is_automatic', 'trigger_count', 'expires_at', 'whitelisted_at'
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
     * Get the user who created this blacklist entry.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who whitelisted this IP.
     */
    public function whitelister()
    {
        return $this->belongsTo(User::class, 'whitelisted_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active blacklisted IPs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include automatic blacklisted IPs.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope a query to only include manual blacklisted IPs.
     */
    public function scopeManual($query)
    {
        return $query->where('is_automatic', false);
    }

    /**
     * Scope a query to only include non-expired blacklisted IPs.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired blacklisted IPs.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include whitelisted IPs.
     */
    public function scopeWhitelisted($query)
    {
        return $query->whereNotNull('whitelisted_at');
    }

    /**
     * Scope a query to only include non-whitelisted IPs.
     */
    public function scopeNotWhitelisted($query)
    {
        return $query->whereNull('whitelisted_at');
    }

    /**
     * Scope a query to filter by severity level.
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to only include high severity IPs.
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * Scope a query to filter by IP address.
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the blacklist entry is expired.
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the IP is effectively blacklisted.
     */
    public function getIsEffectivelyBlacklistedAttribute()
    {
        return $this->is_active &&
               !$this->is_expired &&
               !$this->whitelisted_at;
    }

    /**
     * Get the remaining time until expiry.
     */
    public function getTimeUntilExpiryAttribute()
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a specific IP address is blacklisted.
     */
    public static function isBlacklisted($ipAddress)
    {
        return static::active()
                    ->notExpired()
                    ->notWhitelisted()
                    ->where(function ($query) use ($ipAddress) {
                        $query->where('ip_address', $ipAddress)
                              ->orWhere(function ($subQuery) use ($ipAddress) {
                                  $subQuery->whereNotNull('subnet_mask')
                                           ->whereRaw('? & inet(subnet_mask) = inet(ip_address)', [$ipAddress]);
                              });
                    })->exists();
    }

    /**
     * Get blacklist entry for an IP address.
     */
    public static function getBlacklistEntry($ipAddress)
    {
        return static::active()
                    ->notExpired()
                    ->notWhitelisted()
                    ->where(function ($query) use ($ipAddress) {
                        $query->where('ip_address', $ipAddress)
                              ->orWhere(function ($subQuery) use ($ipAddress) {
                                  $subQuery->whereNotNull('subnet_mask')
                                           ->whereRaw('? & inet(subnet_mask) = inet(ip_address)', [$ipAddress]);
                              });
                    })->first();
    }

    /**
     * Blacklist an IP address.
     */
    public static function blacklistIp($ipAddress, $options = [])
    {
        $existing = static::byIpAddress($ipAddress)->first();

        if ($existing) {
            // Update existing entry
            $existing->update([
                'is_active' => true,
                'trigger_count' => $existing->trigger_count + 1,
                'last_attempt_at' => now(),
                'reason' => $options['reason'] ?? $existing->reason,
                'severity' => $options['severity'] ?? $existing->severity,
                'expires_at' => $options['expires_at'] ?? $existing->expires_at,
                'whitelisted_at' => null,
                'whitelisted_by' => null,
            ]);

            return $existing;
        }

        // Create new entry
        return static::create([
            'ip_address' => $ipAddress,
            'subnet_mask' => $options['subnet_mask'] ?? null,
            'reason' => $options['reason'] ?? 'Automatic blacklist',
            'severity' => $options['severity'] ?? 'medium',
            'is_active' => true,
            'is_automatic' => $options['is_automatic'] ?? true,
            'trigger_count' => 1,
            'last_attempt_at' => now(),
            'expires_at' => $options['expires_at'] ?? now()->addDays(7),
            'metadata' => $options['metadata'] ?? [],
            'notes' => $options['notes'] ?? null,
            'created_by' => $options['created_by'] ?? null,
        ]);
    }

    /**
     * Whitelist an IP address.
     */
    public function whitelist($userId = null, $reason = null)
    {
        $this->update([
            'whitelisted_at' => now(),
            'whitelisted_by' => $userId,
            'notes' => $reason ? ($this->notes . "\n\nWhitelisted: " . $reason) : $this->notes,
        ]);
    }

    /**
     * Remove from whitelist.
     */
    public function removeFromWhitelist()
    {
        $this->update([
            'whitelisted_at' => null,
            'whitelisted_by' => null,
        ]);
    }

    /**
     * Extend blacklist expiry.
     */
    public function extendExpiry($days = 7)
    {
        $newExpiry = $this->expires_at ?
                    $this->expires_at->addDays($days) :
                    now()->addDays($days);

        $this->update([
            'expires_at' => $newExpiry,
        ]);
    }

    /**
     * Make blacklist permanent.
     */
    public function makePermanent()
    {
        $this->update([
            'expires_at' => null,
        ]);
    }

    /**
     * Increment trigger count.
     */
    public function incrementTriggerCount()
    {
        $this->increment('trigger_count');
        $this->update([
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Deactivate blacklist entry.
     */
    public function deactivate()
    {
        $this->update([
            'is_active' => false,
        ]);
    }

    /**
     * Activate blacklist entry.
     */
    public function activate()
    {
        $this->update([
            'is_active' => true,
            'whitelisted_at' => null,
            'whitelisted_by' => null,
        ]);
    }

    /**
     * Get blacklist statistics.
     */
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'expired' => static::expired()->count(),
            'automatic' => static::automatic()->count(),
            'manual' => static::manual()->count(),
            'whitelisted' => static::whitelisted()->count(),
            'high_severity' => static::highSeverity()->count(),
            'by_severity' => static::getBySeverityStats(),
        ];
    }

    /**
     * Get statistics by severity.
     */
    public static function getBySeverityStats()
    {
        return static::selectRaw('severity, count(*) as count')
                    ->groupBy('severity')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->pluck('count', 'severity')
                    ->toArray();
    }

    /**
     * Get top blacklisted IPs by trigger count.
     */
    public static function getTopOffenders($limit = 10)
    {
        return static::active()
                    ->orderBy('trigger_count', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Clean up expired blacklist entries.
     */
    public static function cleanupExpired($days = 30)
    {
        return static::expired()
                    ->where('expires_at', '<', now()->subDays($days))
                    ->where('is_automatic', true)
                    ->delete();
    }

    /**
     * Auto-blacklist IP based on failed attempts.
     */
    public static function autoBlacklistByFailedAttempts($ipAddress, $failedAttempts)
    {
        $thresholds = [
            50 => ['severity' => 'critical', 'days' => 30],
            30 => ['severity' => 'high', 'days' => 14],
            20 => ['severity' => 'medium', 'days' => 7],
            10 => ['severity' => 'low', 'days' => 1],
        ];

        foreach ($thresholds as $threshold => $config) {
            if ($failedAttempts >= $threshold) {
                return static::blacklistIp($ipAddress, [
                    'reason' => "Automatic blacklist: {$failedAttempts} failed attempts",
                    'severity' => $config['severity'],
                    'expires_at' => now()->addDays($config['days']),
                    'is_automatic' => true,
                ]);
            }
        }

        return null;
    }
}
