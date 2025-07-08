<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub System Configuration Model
 *
 * Centralized system configuration management with
 * encryption support, caching, and comprehensive audit trail.
 */
class SystemConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'system_configs';

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
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_encrypted',
        'is_public',
        'is_editable',
        'is_system',
        'validation_rules',
        'default_value',
        'metadata',
        'notes',
        'updated_by',
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
            'is_editable' => 'boolean',
            'is_system' => 'boolean',
            'validation_rules' => 'array',
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
                'key', 'value', 'type', 'category', 'is_encrypted', 'is_public',
                'is_editable', 'is_system', 'updated_by'
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
     * Get the user who last updated this configuration.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include public configurations.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private configurations.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope a query to only include editable configurations.
     */
    public function scopeEditable($query)
    {
        return $query->where('is_editable', true);
    }

    /**
     * Scope a query to only include non-system configurations.
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to only include system configurations.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by key.
     */
    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the value attribute, decrypting if necessary.
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            try {
                return Crypt::decrypt($value);
            } catch (\Exception $e) {
                return $value; // Return original if decryption fails
            }
        }

        return $this->castValue($value);
    }

    /**
     * Set the value attribute, encrypting if necessary.
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value) {
            $this->attributes['value'] = Crypt::encrypt($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get the typed value based on configuration type.
     */
    public function getTypedValueAttribute()
    {
        return $this->castValue($this->value);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Cast value to appropriate type.
     */
    private function castValue($value)
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Get configuration value by key.
     */
    public static function get($key, $default = null)
    {
        $cacheKey = "system_config_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $config = static::byKey($key)->first();
            return $config ? $config->typed_value : $default;
        });
    }

    /**
     * Set configuration value by key.
     */
    public static function set($key, $value, $options = [])
    {
        $config = static::byKey($key)->first();

        if ($config) {
            if (!$config->is_editable) {
                throw new \Exception("Configuration '{$key}' is not editable.");
            }

            $config->update([
                'value' => $value,
                'updated_by' => $options['updated_by'] ?? null,
            ]);
        } else {
            $config = static::create([
                'key' => $key,
                'value' => $value,
                'type' => $options['type'] ?? 'string',
                'category' => $options['category'] ?? 'general',
                'description' => $options['description'] ?? null,
                'is_encrypted' => $options['is_encrypted'] ?? false,
                'is_public' => $options['is_public'] ?? false,
                'is_editable' => $options['is_editable'] ?? true,
                'is_system' => $options['is_system'] ?? false,
                'validation_rules' => $options['validation_rules'] ?? null,
                'default_value' => $options['default_value'] ?? null,
                'metadata' => $options['metadata'] ?? null,
                'notes' => $options['notes'] ?? null,
                'updated_by' => $options['updated_by'] ?? null,
            ]);
        }

        // Clear cache
        Cache::forget("system_config_{$key}");

        return $config;
    }

    /**
     * Check if configuration exists.
     */
    public static function has($key)
    {
        return static::byKey($key)->exists();
    }

    /**
     * Remove configuration.
     */
    public static function remove($key)
    {
        $config = static::byKey($key)->first();

        if ($config) {
            if (!$config->is_editable) {
                throw new \Exception("Configuration '{$key}' cannot be deleted.");
            }

            $config->delete();
            Cache::forget("system_config_{$key}");
            return true;
        }

        return false;
    }

    /**
     * Get all configurations by category.
     */
    public static function getByCategory($category)
    {
        return static::byCategory($category)->get()->mapWithKeys(function ($config) {
            return [$config->key => $config->typed_value];
        });
    }

    /**
     * Get all public configurations.
     */
    public static function getPublicConfigs()
    {
        return static::public()->get()->mapWithKeys(function ($config) {
            return [$config->key => $config->typed_value];
        });
    }

    /**
     * Validate configuration value.
     */
    public function validateValue($value)
    {
        if (!$this->validation_rules) {
            return true;
        }

        $validator = \Validator::make(
            ['value' => $value],
            ['value' => $this->validation_rules]
        );

        return $validator->passes();
    }

    /**
     * Reset to default value.
     */
    public function resetToDefault()
    {
        if ($this->default_value !== null) {
            $this->update(['value' => $this->default_value]);
            Cache::forget("system_config_{$this->key}");
        }
    }

    /**
     * Export configurations for backup.
     */
    public static function export($includeSystem = false)
    {
        $query = static::query();

        if (!$includeSystem) {
            $query->nonSystem();
        }

        return $query->get()->map(function ($config) {
            return [
                'key' => $config->key,
                'value' => $config->is_encrypted ? '[ENCRYPTED]' : $config->value,
                'type' => $config->type,
                'category' => $config->category,
                'description' => $config->description,
                'is_encrypted' => $config->is_encrypted,
                'is_public' => $config->is_public,
                'is_editable' => $config->is_editable,
                'is_system' => $config->is_system,
                'validation_rules' => $config->validation_rules,
                'default_value' => $config->default_value,
                'metadata' => $config->metadata,
                'notes' => $config->notes,
            ];
        });
    }

    /**
     * Import configurations from backup.
     */
    public static function import($configurations, $options = [])
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($configurations as $config) {
            try {
                if ($config['is_encrypted'] && $config['value'] === '[ENCRYPTED]') {
                    $skipped++;
                    continue;
                }

                $existing = static::byKey($config['key'])->first();

                if ($existing && !$options['overwrite']) {
                    $skipped++;
                    continue;
                }

                static::set($config['key'], $config['value'], [
                    'type' => $config['type'],
                    'category' => $config['category'],
                    'description' => $config['description'],
                    'is_encrypted' => $config['is_encrypted'],
                    'is_public' => $config['is_public'],
                    'is_editable' => $config['is_editable'],
                    'is_system' => $config['is_system'],
                    'validation_rules' => $config['validation_rules'],
                    'default_value' => $config['default_value'],
                    'metadata' => $config['metadata'],
                    'notes' => $config['notes'],
                    'updated_by' => $options['updated_by'] ?? null,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Error importing {$config['key']}: " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Clear all configuration cache.
     */
    public static function clearCache()
    {
        $keys = static::pluck('key');

        foreach ($keys as $key) {
            Cache::forget("system_config_{$key}");
        }
    }

    /**
     * Get configuration statistics.
     */
    public static function getStatistics()
    {
        return [
            'total' => static::count(),
            'public' => static::public()->count(),
            'encrypted' => static::where('is_encrypted', true)->count(),
            'editable' => static::editable()->count(),
            'system' => static::system()->count(),
            'by_category' => static::getByCategoryStats(),
            'by_type' => static::getByTypeStats(),
        ];
    }

    /**
     * Get statistics by category.
     */
    public static function getByCategoryStats()
    {
        return static::selectRaw('category, count(*) as count')
                    ->groupBy('category')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->pluck('count', 'category')
                    ->toArray();
    }

    /**
     * Get statistics by type.
     */
    public static function getByTypeStats()
    {
        return static::selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->pluck('count', 'type')
                    ->toArray();
    }

    /**
     * Initialize default system configurations.
     */
    public static function initializeDefaults()
    {
        $defaults = [
            'app.name' => [
                'value' => 'Analytics Hub',
                'type' => 'string',
                'category' => 'application',
                'description' => 'Application name',
                'is_public' => true,
                'is_system' => true,
            ],
            'app.version' => [
                'value' => '1.0.0',
                'type' => 'string',
                'category' => 'application',
                'description' => 'Application version',
                'is_public' => true,
                'is_system' => true,
            ],
            'security.session_timeout' => [
                'value' => 30,
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Session timeout in minutes',
                'is_public' => false,
                'is_system' => false,
            ],
            'security.max_login_attempts' => [
                'value' => 30,
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false,
                'is_system' => false,
            ],
            'security.lockout_duration' => [
                'value' => 60,
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Lockout duration in minutes',
                'is_public' => false,
                'is_system' => false,
            ],
        ];

        foreach ($defaults as $key => $config) {
            if (!static::has($key)) {
                static::set($key, $config['value'], $config);
            }
        }
    }
}
