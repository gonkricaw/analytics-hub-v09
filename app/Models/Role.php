<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Role Model
 *
 * Role-based access control system with hierarchical permissions,
 * user assignments, and comprehensive audit logging.
 */
class Role extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';

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
        'name',
        'display_name',
        'description',
        'is_active',
        'is_system',
        'priority',
        'permissions',
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
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'priority' => 'integer',
            'permissions' => 'array',
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
                'name', 'display_name', 'description', 'is_active',
                'is_system', 'priority', 'permissions'
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
     * Get the users assigned to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withPivot(['starts_at', 'expires_at', 'is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active users assigned to this role.
     */
    public function activeUsers()
    {
        return $this->users()
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
     * Get the permissions assigned to this role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
                    ->withPivot(['is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active permissions assigned to this role.
     */
    public function activePermissions()
    {
        return $this->permissions()
                    ->wherePivot('is_active', true)
                    ->where('is_active', true);
    }

    /**
     * Get the menus accessible to this role.
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_roles', 'role_id', 'menu_id')
                    ->withPivot(['is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active menus accessible to this role.
     */
    public function activeMenus()
    {
        return $this->menus()
                    ->wherePivot('is_active', true)
                    ->where('is_active', true);
    }

    /**
     * Get the contents accessible to this role.
     */
    public function contents()
    {
        return $this->belongsToMany(Content::class, 'content_roles', 'role_id', 'content_id')
                    ->withPivot(['permission_level', 'is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active contents accessible to this role.
     */
    public function activeContents()
    {
        return $this->contents()
                    ->wherePivot('is_active', true)
                    ->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to exclude system roles.
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to only include system roles.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission($permissionName)
    {
        return $this->activePermissions()->where('name', $permissionName)->exists();
    }

    /**
     * Check if role has any of the given permissions.
     */
    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        return $this->activePermissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Check if role has all of the given permissions.
     */
    public function hasAllPermissions($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        $rolePermissions = $this->activePermissions()->pluck('name')->toArray();
        return empty(array_diff($permissions, $rolePermissions));
    }

    /**
     * Grant a permission to this role.
     */
    public function grantPermission($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission && !$this->hasPermission($permissionName)) {
            $this->permissions()->attach($permission->id, [
                'is_active' => true,
                'notes' => 'Granted via role management',
            ]);
        }
    }

    /**
     * Revoke a permission from this role.
     */
    public function revokePermission($permissionName)
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $this->permissions()->updateExistingPivot($permission->id, [
                'is_active' => false,
            ]);
        }
    }

    /**
     * Get the role's display name or fallback to name.
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->name;
    }
}
