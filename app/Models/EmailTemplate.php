<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Email Template Model
 *
 * Templated email system with variable substitution,
 * multi-language support, and comprehensive audit trail.
 */
class EmailTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_templates';

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
        'subject',
        'body',
        'template_type',
        'language',
        'is_active',
        'is_system',
        'variables',
        'default_from_name',
        'default_from_email',
        'reply_to',
        'cc',
        'bcc',
        'priority',
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
            'variables' => 'array',
            'priority' => 'integer',
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
                'name', 'subject', 'template_type', 'language', 'is_active',
                'is_system', 'default_from_name', 'default_from_email', 'priority'
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
     * Get the email queue entries that used this template.
     */
    public function emailQueue()
    {
        return $this->hasMany(EmailQueue::class, 'template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to exclude system templates.
     */
    public function scopeNonSystem($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope a query to only include system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope a query to filter by template type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeOfLanguage($query, $language)
    {
        return $query->where('language', $language);
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
     * Render the template with provided variables.
     */
    public function render($variables = [])
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Get available variables for this template.
     */
    public function getAvailableVariables()
    {
        return $this->variables ?: [];
    }

    /**
     * Extract variables from template content.
     */
    public function extractVariables()
    {
        $content = $this->subject . ' ' . $this->body;
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        return array_unique($matches[1]);
    }

    /**
     * Validate template variables.
     */
    public function validateVariables($variables = [])
    {
        $required = $this->getAvailableVariables();
        $missing = array_diff($required, array_keys($variables));

        return empty($missing) ? true : $missing;
    }

    /**
     * Get the template's default from address.
     */
    public function getFromAddress()
    {
        return [
            'name' => $this->default_from_name ?: config('mail.from.name'),
            'email' => $this->default_from_email ?: config('mail.from.address'),
        ];
    }

    /**
     * Get all template types.
     */
    public static function getTemplateTypes()
    {
        return static::distinct('template_type')
                    ->whereNotNull('template_type')
                    ->pluck('template_type')
                    ->sort()
                    ->values()
                    ->all();
    }

    /**
     * Get all languages.
     */
    public static function getLanguages()
    {
        return static::distinct('language')
                    ->whereNotNull('language')
                    ->pluck('language')
                    ->sort()
                    ->values()
                    ->all();
    }

    /**
     * Find template by type and language.
     */
    public static function findByTypeAndLanguage($type, $language = 'en')
    {
        return static::active()
                    ->ofType($type)
                    ->ofLanguage($language)
                    ->first();
    }

    /**
     * Get default template for a type (fallback to English).
     */
    public static function getDefaultTemplate($type)
    {
        $template = static::findByTypeAndLanguage($type, 'en');

        if (!$template) {
            $template = static::active()->ofType($type)->first();
        }

        return $template;
    }

    /**
     * Clone template for a different language.
     */
    public function cloneForLanguage($language)
    {
        $clone = $this->replicate();
        $clone->language = $language;
        $clone->name = $this->name . ' (' . strtoupper($language) . ')';
        $clone->save();

        return $clone;
    }

    /**
     * Check if template has all required variables.
     */
    public function hasRequiredVariables($variables = [])
    {
        $required = $this->getAvailableVariables();
        $provided = array_keys($variables);

        return empty(array_diff($required, $provided));
    }

    /**
     * Preview template with sample data.
     */
    public function preview($sampleData = [])
    {
        $defaultSample = [
            'user_name' => 'John Doe',
            'user_email' => 'john@example.com',
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
        ];

        $variables = array_merge($defaultSample, $sampleData);

        return $this->render($variables);
    }
}
