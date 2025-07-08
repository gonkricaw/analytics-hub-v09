<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub User Model
 *
 * Central user management with UUID primary keys, role-based access control,
 * password policies, and comprehensive security tracking.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

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
        'username',
        'email',
        'full_name',
        'password',
        'status',
        'is_first_login',
        'terms_accepted',
        'terms_accepted_at',
        'terms_version',
        'password_expires',
        'password_expiry_days',
        'preferences',
        'timezone',
        'language',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'session_expires_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted' => 'boolean',
            'is_first_login' => 'boolean',
            'password_expires' => 'boolean',
            'preferences' => 'array',
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
        'email_verified_at',
        'terms_accepted_at',
        'password_changed_at',
        'last_login_at',
        'locked_until',
        'session_expires_at',
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
                'username', 'email', 'full_name', 'status',
                'terms_accepted', 'password_changed_at', 'last_login_at'
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
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot(['starts_at', 'expires_at', 'is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active roles for the user.
     */
    public function activeRoles()
    {
        return $this->roles()
                    ->wherePivot('is_active', true)
                    ->wherePivot(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    })
                    ->wherePivot(function ($query) {
                        $query->whereNull('starts_at')
                              ->orWhere('starts_at', '<=', now());
                    });
    }

    /**
     * Get the user's avatars.
     */
    public function avatars()
    {
        return $this->hasMany(UserAvatar::class, 'user_id');
    }

    /**
     * Get the user's active avatar.
     */
    public function activeAvatar()
    {
        return $this->hasOne(UserAvatar::class, 'user_id')->where('is_active', true);
    }

    /**
     * Get the user's login attempts.
     */
    public function loginAttempts()
    {
        return $this->hasMany(LoginAttempt::class, 'user_id');
    }

    /**
     * Get the user's password history.
     */
    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class, 'user_id')->latest();
    }

    /**
     * Get the user's notifications.
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'user_notifications', 'user_id', 'notification_id')
                    ->withPivot(['is_read', 'read_at'])
                    ->withTimestamps();
    }

    /**
     * Get the user's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->wherePivot('is_read', false);
    }

    /**
     * Get contents created by this user.
     */
    public function createdContents()
    {
        return $this->hasMany(Content::class, 'created_by');
    }

    /**
     * Get contents updated by this user.
     */
    public function updatedContents()
    {
        return $this->hasMany(Content::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope a query to only include users with expired passwords.
     */
    public function scopePasswordExpired($query)
    {
        return $query->where('password_expires', true)
                     ->where('password_changed_at', '<', now()->subDays(90));
    }

    /**
     * Scope a query to only include first-time login users.
     */
    public function scopeFirstLogin($query)
    {
        return $query->where('is_first_login', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's full name.
     */
    public function getNameAttribute()
    {
        return $this->full_name;
    }

    /**
     * Check if user has accepted terms and conditions.
     */
    public function getHasAcceptedTermsAttribute()
    {
        return $this->terms_accepted && $this->terms_accepted_at;
    }

    /**
     * Check if user's password is expired.
     */
    public function getIsPasswordExpiredAttribute()
    {
        if (!$this->password_expires) {
            return false;
        }

        if (!$this->password_changed_at) {
            return true; // Never changed password
        }

        return $this->password_changed_at->addDays($this->password_expiry_days)->isPast();
    }

    /**
     * Check if user account is locked.
     */
    public function getIsLockedAttribute()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if user is online (last login within 30 minutes).
     */
    public function getIsOnlineAttribute()
    {
        return $this->last_login_at && $this->last_login_at->gt(now()->subMinutes(30));
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has a specific role.
     */
    public function hasRole($roleName)
    {
        return $this->activeRoles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->activeRoles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission($permissionName)
    {
        return $this->activeRoles()
                    ->whereHas('permissions', function ($query) use ($permissionName) {
                        $query->where('name', $permissionName)
                              ->where('is_active', true);
                    })->exists();
    }

    /**
     * Mark user as having accepted terms.
     */
    public function acceptTerms($version = null)
    {
        $this->update([
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => $version ?? config('app.terms_version', '1.0'),
        ]);
    }

    /**
     * Reset failed login attempts.
     */
    public function resetFailedAttempts()
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Increment failed login attempts and lock if necessary.
     */
    public function incrementFailedAttempts()
    {
        $attempts = $this->failed_login_attempts + 1;
        $updates = ['failed_login_attempts' => $attempts];

        // Lock account after 30 failed attempts
        if ($attempts >= 30) {
            $updates['locked_until'] = now()->addHours(1);
        }

        $this->update($updates);
    }
}
