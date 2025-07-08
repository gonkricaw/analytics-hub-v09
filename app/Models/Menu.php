<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Menu Model
 *
 * Hierarchical menu system with 3-level structure,
 * role-based access control, and dynamic navigation.
 */
class Menu extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'menus';

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
        'parent_id',
        'name',
        'display_name',
        'description',
        'url',
        'icon',
        'level',
        'sort_order',
        'is_active',
        'is_system',
        'target',
        'css_class',
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
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
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
                'parent_id', 'name', 'display_name', 'url', 'icon',
                'level', 'sort_order', 'is_active', 'is_system'
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
     * Get the parent menu item.
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Get the child menu items.
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    /**
     * Get the active child menu items.
     */
    public function activeChildren()
    {
        return $this->children()
                    ->where('is_active', true);
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all active descendants
     */
    public function activeDescendants()
    {
        return $this->activeChildren()->with('activeDescendants');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.)
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    /**
     * Get the roles that have access to this menu.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_roles', 'menu_id', 'role_id')
                    ->withPivot(['is_active', 'notes'])
                    ->withTimestamps();
    }

    /**
     * Get the active roles that have access to this menu.
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
     * Scope a query to only include active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to exclude system menus.
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to only include system menus.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to only include top-level menus.
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include menus of a specific level.
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the menu's display name or fallback to name.
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->name;
    }

    /**
     * Check if this menu has children.
     */
    public function hasChildren()
    {
        return $this->children()->exists();
    }

    /**
     * Check if this menu has active children.
     */
    public function hasActiveChildren()
    {
        return $this->activeChildren()->exists();
    }

    /**
     * Get the full path of this menu (including ancestors).
     */
    public function getFullPath()
    {
        $path = collect([$this->name]);
        $ancestors = $this->ancestors()->reverse();

        foreach ($ancestors as $ancestor) {
            $path->prepend($ancestor->name);
        }

        return $path->implode(' > ');
    }

    /**
     * Check if the menu is accessible to a specific role.
     */
    public function isAccessibleToRole($roleId)
    {
        return $this->activeRoles()->where('id', $roleId)->exists();
    }

    /**
     * Check if the menu is accessible to any of the given roles.
     */
    public function isAccessibleToRoles($roleIds)
    {
        if (is_string($roleIds)) {
            $roleIds = [$roleIds];
        }

        return $this->activeRoles()->whereIn('id', $roleIds)->exists();
    }

    /**
     * Get breadcrumb navigation for this menu.
     */
    public function getBreadcrumb()
    {
        $breadcrumb = collect();
        $ancestors = $this->ancestors()->reverse();

        foreach ($ancestors as $ancestor) {
            $breadcrumb->push([
                'name' => $ancestor->display_name,
                'url' => $ancestor->url,
                'icon' => $ancestor->icon,
            ]);
        }

        $breadcrumb->push([
            'name' => $this->display_name,
            'url' => $this->url,
            'icon' => $this->icon,
        ]);

        return $breadcrumb;
    }

    /**
     * Build hierarchical menu structure.
     */
    public static function buildHierarchy($menus = null)
    {
        if ($menus === null) {
            $menus = static::active()->ordered()->get();
        }

        $grouped = $menus->groupBy('parent_id');

        return static::buildTree($grouped, null);
    }

    /**
     * Recursively build menu tree.
     */
    private static function buildTree($grouped, $parentId)
    {
        $tree = collect();

        if (isset($grouped[$parentId])) {
            foreach ($grouped[$parentId] as $menu) {
                $menu->children = static::buildTree($grouped, $menu->id);
                $tree->push($menu);
            }
        }

        return $tree;
    }
}
