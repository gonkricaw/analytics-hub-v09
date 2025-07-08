<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Content Model
 *
 * Secure content management with encryption support,
 * role-based access control, and comprehensive audit trail.
 */
class Content extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contents';

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
        'content',
        'content_type',
        'status',
        'is_encrypted',
        'is_public',
        'view_count',
        'metadata',
        'tags',
        'slug',
        'excerpt',
        'featured_image',
        'publish_at',
        'expires_at',
        'created_by',
        'updated_by',
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
            'is_encrypted' => 'boolean',
            'is_public' => 'boolean',
            'view_count' => 'integer',
            'metadata' => 'array',
            'tags' => 'array',
            'publish_at' => 'datetime',
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
        'publish_at',
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
                'title', 'content_type', 'status', 'is_encrypted', 'is_public',
                'slug', 'publish_at', 'expires_at', 'created_by', 'updated_by'
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
     * Get the user who created this content.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this content.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the roles that have access to this content.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'content_roles', 'content_id', 'role_id')
                    ->withPivot(['permission_level', 'is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active roles that have access to this content.
     */
    public function activeRoles()
    {
        return $this->roles()
                    ->wherePivot('is_active', true)
                    ->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include published content.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where(function ($q) {
                         $q->whereNull('publish_at')
                           ->orWhere('publish_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope a query to only include draft content.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include public content.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private content.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope a query to only include encrypted content.
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope a query to filter by content type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('content_type', $type);
    }

    /**
     * Scope a query to search content by title or content.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ILIKE', '%' . $search . '%')
              ->orWhere('content', 'ILIKE', '%' . $search . '%')
              ->orWhere('excerpt', 'ILIKE', '%' . $search . '%');
        });
    }

    /**
     * Scope a query to filter by tags.
     */
    public function scopeWithTags($query, $tags)
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Scope a query to order by popularity (view count).
     */
    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the content attribute, decrypting if necessary.
     */
    public function getContentAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                return Crypt::decrypt($value);
            } catch (\Exception $e) {
                return $value; // Return original if decryption fails
            }
        }

        return $value;
    }

    /**
     * Set the content attribute, encrypting if necessary.
     */
    public function setContentAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['content'] = Crypt::encrypt($value);
        } else {
            $this->attributes['content'] = $value;
        }
    }

    /**
     * Get the excerpt or generate from content.
     */
    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }

        $content = strip_tags($this->content);
        return str_limit($content, 200);
    }

    /**
     * Check if content is currently published.
     */
    public function getIsPublishedAttribute()
    {
        return $this->status === 'published' &&
               ($this->publish_at === null || $this->publish_at->isPast()) &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if content is expired.
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if content is scheduled for future publication.
     */
    public function getIsScheduledAttribute()
    {
        return $this->publish_at && $this->publish_at->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Increment the view count for this content.
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Check if content is accessible to a specific role.
     */
    public function isAccessibleToRole($roleId)
    {
        if ($this->is_public) {
            return true;
        }

        return $this->activeRoles()->where('id', $roleId)->exists();
    }

    /**
     * Check if content is accessible to any of the given roles.
     */
    public function isAccessibleToRoles($roleIds)
    {
        if ($this->is_public) {
            return true;
        }

        if (is_string($roleIds)) {
            $roleIds = [$roleIds];
        }

        return $this->activeRoles()->whereIn('id', $roleIds)->exists();
    }

    /**
     * Get the permission level for a specific role.
     */
    public function getPermissionLevelForRole($roleId)
    {
        $role = $this->activeRoles()->where('id', $roleId)->first();
        return $role ? $role->pivot->permission_level : null;
    }

    /**
     * Encrypt the content.
     */
    public function encryptContent()
    {
        if (!$this->is_encrypted) {
            $this->is_encrypted = true;
            $this->save();
        }
    }

    /**
     * Decrypt the content.
     */
    public function decryptContent()
    {
        if ($this->is_encrypted) {
            $this->is_encrypted = false;
            $this->save();
        }
    }

    /**
     * Publish the content.
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'publish_at' => now(),
        ]);
    }

    /**
     * Unpublish the content.
     */
    public function unpublish()
    {
        $this->update([
            'status' => 'draft',
        ]);
    }

    /**
     * Schedule the content for publication.
     */
    public function schedulePublication($publishAt)
    {
        $this->update([
            'status' => 'scheduled',
            'publish_at' => $publishAt,
        ]);
    }

    /**
     * Get all content types.
     */
    public static function getContentTypes()
    {
        return static::distinct('content_type')
                    ->whereNotNull('content_type')
                    ->pluck('content_type')
                    ->sort()
                    ->values()
                    ->all();
    }

    /**
     * Get all tags.
     */
    public static function getAllTags()
    {
        return static::whereNotNull('tags')
                    ->pluck('tags')
                    ->flatten()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
    }
}
