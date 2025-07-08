<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub User Avatar Model
 *
 * User avatar management system with file handling,
 * size validation, and comprehensive audit trail.
 */
class UserAvatar extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_avatars';

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
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'is_active',
        'upload_ip',
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
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'is_active' => 'boolean',
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
                'user_id', 'filename', 'file_size', 'mime_type',
                'width', 'height', 'is_active'
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
     * Get the user who owns this avatar.
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
     * Scope a query to only include active avatars.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full URL to the avatar.
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the full path to the avatar file.
     */
    public function getFullPathAttribute()
    {
        return Storage::path($this->file_path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getFileSizeHumanAttribute()
    {
        return $this->formatBytes($this->file_size);
    }

    /**
     * Get the aspect ratio of the avatar.
     */
    public function getAspectRatioAttribute()
    {
        if ($this->width && $this->height) {
            return round($this->width / $this->height, 2);
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Format bytes into human readable format.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Set this avatar as active and deactivate others for the user.
     */
    public function setAsActive()
    {
        // Deactivate all other avatars for this user
        static::forUser($this->user_id)->update(['is_active' => false]);

        // Activate this avatar
        $this->update(['is_active' => true]);
    }

    /**
     * Check if the avatar file exists.
     */
    public function fileExists()
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete the avatar file from storage.
     */
    public function deleteFile()
    {
        if ($this->fileExists()) {
            Storage::delete($this->file_path);
        }
    }

    /**
     * Get image dimensions.
     */
    public function getDimensions()
    {
        if ($this->fileExists()) {
            $imageInfo = getimagesize($this->full_path);
            return [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
            ];
        }
        return ['width' => null, 'height' => null];
    }

    /**
     * Update dimensions from actual file.
     */
    public function updateDimensions()
    {
        $dimensions = $this->getDimensions();
        $this->update([
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
        ]);
    }

    /**
     * Create avatar from uploaded file.
     */
    public static function createFromUpload($user, $uploadedFile, $options = [])
    {
        $filename = time() . '_' . $user->id . '.' . $uploadedFile->getClientOriginalExtension();
        $path = $uploadedFile->storeAs('avatars', $filename, 'public');

        $dimensions = getimagesize($uploadedFile->getRealPath());

        $avatar = static::create([
            'user_id' => $user->id,
            'filename' => $filename,
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'is_active' => $options['is_active'] ?? true,
            'upload_ip' => request()->ip(),
            'metadata' => $options['metadata'] ?? [],
            'notes' => $options['notes'] ?? null,
        ]);

        if ($options['is_active'] ?? true) {
            $avatar->setAsActive();
        }

        return $avatar;
    }

    /**
     * Get default avatar URL.
     */
    public static function getDefaultAvatarUrl($user = null)
    {
        // You can implement different default avatar logic here
        // For example, generate avatars based on user initials or use a service like Gravatar
        $initial = $user ? strtoupper(substr($user->full_name ?: $user->username, 0, 1)) : 'U';
        return "https://ui-avatars.com/api/?name={$initial}&size=200&background=random";
    }

    /**
     * Get avatar URL for a user (active avatar or default).
     */
    public static function getAvatarUrl($user)
    {
        $activeAvatar = static::forUser($user->id)->active()->first();
        return $activeAvatar ? $activeAvatar->url : static::getDefaultAvatarUrl($user);
    }

    /**
     * Clean up old inactive avatars.
     */
    public static function cleanupOldAvatars($days = 30)
    {
        $oldAvatars = static::where('is_active', false)
                           ->where('created_at', '<', now()->subDays($days))
                           ->get();

        foreach ($oldAvatars as $avatar) {
            $avatar->deleteFile();
            $avatar->delete();
        }

        return $oldAvatars->count();
    }

    /**
     * Get avatar statistics.
     */
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'total_size' => static::sum('file_size'),
            'average_size' => static::avg('file_size'),
            'by_mime_type' => static::selectRaw('mime_type, count(*) as count')
                                   ->groupBy('mime_type')
                                   ->orderBy('count', 'desc')
                                   ->get()
                                   ->pluck('count', 'mime_type')
                                   ->toArray(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($avatar) {
            $avatar->deleteFile();
        });
    }
}
