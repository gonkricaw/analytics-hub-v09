<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Password History Model
 *
 * Password history tracking system for enforcing
 * password reuse policies and security compliance.
 */
class PasswordHistory extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_histories';

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
        'password_hash',
        'changed_at',
        'changed_by',
        'ip_address',
        'user_agent',
        'reason',
        'strength_score',
        'metadata',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
            'strength_score' => 'integer',
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
        'changed_at',
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
                'user_id', 'changed_at', 'changed_by', 'ip_address',
                'reason', 'strength_score'
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
     * Get the user who owns this password history.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who changed the password.
     */
    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('changed_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by recent changes.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('changed_at', 'desc');
    }

    /**
     * Scope a query to filter by strength score range.
     */
    public function scopeStrengthRange($query, $min, $max)
    {
        return $query->whereBetween('strength_score', [$min, $max]);
    }

    /**
     * Scope a query to filter by weak passwords.
     */
    public function scopeWeakPasswords($query)
    {
        return $query->where('strength_score', '<', 60);
    }

    /**
     * Scope a query to filter by strong passwords.
     */
    public function scopeStrongPasswords($query)
    {
        return $query->where('strength_score', '>=', 80);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the password age in human readable format.
     */
    public function getAgeAttribute()
    {
        return $this->changed_at->diffForHumans();
    }

    /**
     * Get the password strength level.
     */
    public function getStrengthLevelAttribute()
    {
        if ($this->strength_score >= 90) {
            return 'Excellent';
        } elseif ($this->strength_score >= 80) {
            return 'Strong';
        } elseif ($this->strength_score >= 60) {
            return 'Good';
        } elseif ($this->strength_score >= 40) {
            return 'Fair';
        } else {
            return 'Weak';
        }
    }

    /**
     * Get the password strength color for UI.
     */
    public function getStrengthColorAttribute()
    {
        if ($this->strength_score >= 80) {
            return 'green';
        } elseif ($this->strength_score >= 60) {
            return 'blue';
        } elseif ($this->strength_score >= 40) {
            return 'yellow';
        } else {
            return 'red';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a password has been used before by the user.
     */
    public static function hasPasswordBeenUsed($userId, $password, $limit = 5)
    {
        $recentPasswords = static::forUser($userId)
                                ->latest()
                                ->limit($limit)
                                ->pluck('password_hash');

        foreach ($recentPasswords as $hash) {
            if (Hash::check($password, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Record a password change.
     */
    public static function recordPasswordChange($userId, $password, $options = [])
    {
        $strengthScore = static::calculatePasswordStrength($password);

        return static::create([
            'user_id' => $userId,
            'password_hash' => Hash::make($password),
            'changed_at' => now(),
            'changed_by' => $options['changed_by'] ?? $userId,
            'ip_address' => $options['ip_address'] ?? request()->ip(),
            'user_agent' => $options['user_agent'] ?? request()->userAgent(),
            'reason' => $options['reason'] ?? 'Password changed',
            'strength_score' => $strengthScore,
            'metadata' => $options['metadata'] ?? [],
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Calculate password strength score.
     */
    public static function calculatePasswordStrength($password)
    {
        $score = 0;
        $length = strlen($password);

        // Length scoring
        if ($length >= 12) {
            $score += 25;
        } elseif ($length >= 8) {
            $score += 15;
        } elseif ($length >= 6) {
            $score += 10;
        }

        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) {
            $score += 5;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $score += 5;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 5;
        }
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $score += 10;
        }

        // Complexity bonuses
        if (preg_match('/[a-z].*[A-Z]|[A-Z].*[a-z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[a-zA-Z].*[0-9]|[0-9].*[a-zA-Z]/', $password)) {
            $score += 10;
        }
        if (preg_match('/[a-zA-Z0-9].*[^a-zA-Z0-9]|[^a-zA-Z0-9].*[a-zA-Z0-9]/', $password)) {
            $score += 10;
        }

        // Deductions for common patterns
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 10; // Repeated characters
        }
        if (preg_match('/123|abc|qwe|asd|zxc/i', $password)) {
            $score -= 15; // Common sequences
        }

        return max(0, min(100, $score));
    }

    /**
     * Get password history for a user.
     */
    public static function getPasswordHistoryForUser($userId, $limit = 10)
    {
        return static::forUser($userId)
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->map(function ($history) {
                        return [
                            'id' => $history->id,
                            'changed_at' => $history->changed_at,
                            'age' => $history->age,
                            'strength_score' => $history->strength_score,
                            'strength_level' => $history->strength_level,
                            'strength_color' => $history->strength_color,
                            'reason' => $history->reason,
                            'ip_address' => $history->ip_address,
                            'changed_by' => $history->changer ? $history->changer->name : 'System',
                        ];
                    });
    }

    /**
     * Get password statistics.
     */
    public static function getStatistics($userId = null)
    {
        $query = $userId ? static::forUser($userId) : static::query();

        return [
            'total_changes' => $query->count(),
            'recent_changes' => $query->recent(30)->count(),
            'average_strength' => round($query->avg('strength_score'), 2),
            'weak_passwords' => $query->weakPasswords()->count(),
            'strong_passwords' => $query->strongPasswords()->count(),
            'strength_distribution' => [
                'excellent' => $query->strengthRange(90, 100)->count(),
                'strong' => $query->strengthRange(80, 89)->count(),
                'good' => $query->strengthRange(60, 79)->count(),
                'fair' => $query->strengthRange(40, 59)->count(),
                'weak' => $query->strengthRange(0, 39)->count(),
            ],
        ];
    }

    /**
     * Get password change trends.
     */
    public static function getPasswordChangeTrends($days = 30)
    {
        $trends = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = static::whereDate('changed_at', $date)->count();
            $avgStrength = static::whereDate('changed_at', $date)->avg('strength_score');

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'changes' => $count,
                'avg_strength' => $avgStrength ? round($avgStrength, 2) : 0,
            ];
        }

        return $trends;
    }

    /**
     * Get users with weak password history.
     */
    public static function getUsersWithWeakPasswords($limit = 10)
    {
        return static::select('user_id')
                    ->selectRaw('AVG(strength_score) as avg_strength')
                    ->selectRaw('COUNT(*) as total_changes')
                    ->groupBy('user_id')
                    ->having('avg_strength', '<', 60)
                    ->orderBy('avg_strength', 'asc')
                    ->limit($limit)
                    ->with('user')
                    ->get();
    }

    /**
     * Clean up old password history.
     */
    public static function cleanupOldHistory($days = 365, $keepCount = 10)
    {
        $users = static::distinct('user_id')->pluck('user_id');
        $cleaned = 0;

        foreach ($users as $userId) {
            $oldPasswords = static::forUser($userId)
                                 ->where('changed_at', '<', now()->subDays($days))
                                 ->orderBy('changed_at', 'desc')
                                 ->skip($keepCount)
                                 ->get();

            foreach ($oldPasswords as $password) {
                $password->delete();
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Validate password against history policy.
     */
    public static function validatePasswordPolicy($userId, $password, $options = [])
    {
        $errors = [];
        $historyLimit = $options['history_limit'] ?? 5;
        $minStrength = $options['min_strength'] ?? 60;

        // Check against password history
        if (static::hasPasswordBeenUsed($userId, $password, $historyLimit)) {
            $errors[] = "Password has been used in the last {$historyLimit} password changes.";
        }

        // Check password strength
        $strength = static::calculatePasswordStrength($password);
        if ($strength < $minStrength) {
            $errors[] = "Password strength is too low. Minimum required: {$minStrength}, Current: {$strength}.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength_score' => $strength,
        ];
    }
}
