<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Notification Model
 *
 * Comprehensive notification system with user targeting,
 * read tracking, and flexible delivery options.
 */
class Notification extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

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
        'title',
        'message',
        'type',
        'priority',
        'is_global',
        'is_active',
        'icon',
        'color',
        'url',
        'action_text',
        'action_url',
        'metadata',
        'expires_at',
        'created_by',
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
            'priority' => 'integer',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'expires_at' => 'datetime',
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
        'expires_at',
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
                'title', 'message', 'type', 'priority', 'is_global', 'is_active',
                'expires_at', 'created_by'
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
     * Get the user who created this notification.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users who have received this notification.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_notifications', 'notification_id', 'user_id')
                    ->withPivot(['is_read', 'read_at'])
                    ->withTimestamps();
    }

    /**
     * Get the users who have read this notification.
     */
    public function readUsers()
    {
        return $this->users()->wherePivot('is_read', true);
    }

    /**
     * Get the users who have not read this notification.
     */
    public function unreadUsers()
    {
        return $this->users()->wherePivot('is_read', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active notifications.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include global notifications.
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope a query to only include non-global notifications.
     */
    public function scopeNonGlobal($query)
    {
        return $query->where('is_global', false);
    }

    /**
     * Scope a query to only include non-expired notifications.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include expired notifications.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to filter by notification type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeOfPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include high priority notifications.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '<=', 3);
    }

    /**
     * Scope a query to order by priority and creation date.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc')
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to get notifications for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_global', true)
              ->orWhereHas('users', function ($subQuery) use ($userId) {
                  $subQuery->where('user_id', $userId);
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Check if notification is expired.
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if notification is currently valid.
     */
    public function getIsValidAttribute()
    {
        return $this->is_active && !$this->is_expired;
    }

    /**
     * Get the notification icon with default fallback.
     */
    public function getIconAttribute($value)
    {
        return $value ?: $this->getDefaultIcon();
    }

    /**
     * Get the notification color with default fallback.
     */
    public function getColorAttribute($value)
    {
        return $value ?: $this->getDefaultColor();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get default icon based on notification type.
     */
    private function getDefaultIcon()
    {
        $icons = [
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'exclamation-circle',
            'system' => 'cog',
            'user' => 'user',
            'security' => 'shield',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get default color based on notification type.
     */
    private function getDefaultColor()
    {
        $colors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'system' => 'gray',
            'user' => 'indigo',
            'security' => 'purple',
        ];

        return $colors[$this->type] ?? 'blue';
    }

    /**
     * Send notification to specific users.
     */
    public function sendToUsers($userIds)
    {
        if (is_string($userIds)) {
            $userIds = [$userIds];
        }

        $syncData = [];
        foreach ($userIds as $userId) {
            $syncData[$userId] = [
                'is_read' => false,
                'read_at' => null,
            ];
        }

        $this->users()->sync($syncData);
    }

    /**
     * Send notification to all active users.
     */
    public function sendToAllUsers()
    {
        $userIds = User::active()->pluck('id')->toArray();
        $this->sendToUsers($userIds);
    }

    /**
     * Mark notification as read for a specific user.
     */
    public function markAsReadForUser($userId)
    {
        $this->users()->updateExistingPivot($userId, [
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark notification as unread for a specific user.
     */
    public function markAsUnreadForUser($userId)
    {
        $this->users()->updateExistingPivot($userId, [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get read status for a specific user.
     */
    public function getReadStatusForUser($userId)
    {
        $user = $this->users()->where('user_id', $userId)->first();
        return $user ? $user->pivot->is_read : false;
    }

    /**
     * Get read count for this notification.
     */
    public function getReadCount()
    {
        return $this->readUsers()->count();
    }

    /**
     * Get unread count for this notification.
     */
    public function getUnreadCount()
    {
        return $this->unreadUsers()->count();
    }

    /**
     * Get total recipient count.
     */
    public function getTotalRecipientCount()
    {
        if ($this->is_global) {
            return User::active()->count();
        }

        return $this->users()->count();
    }

    /**
     * Expire the notification.
     */
    public function expire()
    {
        $this->update([
            'expires_at' => now(),
            'is_active' => false,
        ]);
    }

    /**
     * Extend notification expiry.
     */
    public function extendExpiry($days = 7)
    {
        $this->update([
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Create a global notification.
     */
    public static function createGlobal($data)
    {
        $data['is_global'] = true;
        return static::create($data);
    }

    /**
     * Create a targeted notification.
     */
    public static function createTargeted($data, $userIds)
    {
        $data['is_global'] = false;
        $notification = static::create($data);
        $notification->sendToUsers($userIds);
        return $notification;
    }

    /**
     * Get notification statistics.
     */
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'global' => static::global()->count(),
            'expired' => static::expired()->count(),
            'high_priority' => static::highPriority()->count(),
        ];
    }

    /**
     * Get all notification types.
     */
    public static function getTypes()
    {
        return static::distinct('type')
                    ->whereNotNull('type')
                    ->pluck('type')
                    ->sort()
                    ->values()
                    ->all();
    }

    /**
     * Clean up expired notifications.
     */
    public static function cleanupExpired($days = 30)
    {
        return static::expired()
                    ->where('expires_at', '<', now()->subDays($days))
                    ->delete();
    }
}
