<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Analytics Hub Email Queue Model
 *
 * Email queue management system with retry logic,
 * priority handling, and comprehensive delivery tracking.
 */
class EmailQueue extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'email_queue';

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
        'template_id',
        'to_email',
        'to_name',
        'from_email',
        'from_name',
        'reply_to',
        'cc',
        'bcc',
        'subject',
        'body',
        'variables',
        'priority',
        'status',
        'retry_count',
        'max_retries',
        'send_at',
        'sent_at',
        'failed_at',
        'error_message',
        'tracking_id',
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
            'variables' => 'array',
            'metadata' => 'array',
            'priority' => 'integer',
            'retry_count' => 'integer',
            'max_retries' => 'integer',
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
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
        'send_at',
        'sent_at',
        'failed_at',
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
                'template_id', 'to_email', 'subject', 'priority', 'status',
                'retry_count', 'send_at', 'sent_at', 'failed_at'
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
     * Get the email template used for this queue entry.
     */
    public function template()
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include pending emails.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include sent emails.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope a query to only include failed emails.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope a query to only include processing emails.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope a query to only include emails ready to send.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
                     ->where(function ($q) {
                         $q->whereNull('send_at')
                           ->orWhere('send_at', '<=', now());
                     });
    }

    /**
     * Scope a query to only include high priority emails.
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', '<=', 3);
    }

    /**
     * Scope a query to order by priority and send time.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc')
                     ->orderBy('send_at', 'asc')
                     ->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to filter by recipient email.
     */
    public function scopeForRecipient($query, $email)
    {
        return $query->where('to_email', $email);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Check if email is ready to send.
     */
    public function getIsReadyToSendAttribute()
    {
        return $this->status === 'pending' &&
               ($this->send_at === null || $this->send_at->isPast());
    }

    /**
     * Check if email has failed permanently.
     */
    public function getHasFailedPermanentlyAttribute()
    {
        return $this->status === 'failed' && $this->retry_count >= $this->max_retries;
    }

    /**
     * Check if email can be retried.
     */
    public function getCanRetryAttribute()
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark email as sent.
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark email as failed.
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Mark email as processing.
     */
    public function markAsProcessing()
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Reset email for retry.
     */
    public function resetForRetry()
    {
        if ($this->can_retry) {
            $this->update([
                'status' => 'pending',
                'send_at' => now()->addMinutes(5), // Delay retry by 5 minutes
                'error_message' => null,
            ]);
        }
    }

    /**
     * Schedule email for later sending.
     */
    public function scheduleFor($dateTime)
    {
        $this->update([
            'send_at' => $dateTime,
            'status' => 'pending',
        ]);
    }

    /**
     * Get rendered email content.
     */
    public function getRenderedContent()
    {
        if ($this->template) {
            return $this->template->render($this->variables ?: []);
        }

        return [
            'subject' => $this->subject,
            'body' => $this->body,
        ];
    }

    /**
     * Get retry delay in minutes based on retry count.
     */
    public function getRetryDelay()
    {
        // Exponential backoff: 5, 15, 45, 135 minutes
        return 5 * (3 ** $this->retry_count);
    }

    /**
     * Create email queue entry from template.
     */
    public static function createFromTemplate(EmailTemplate $template, $recipient, $variables = [], $options = [])
    {
        $rendered = $template->render($variables);
        $fromAddress = $template->getFromAddress();

        return static::create([
            'template_id' => $template->id,
            'to_email' => is_array($recipient) ? $recipient['email'] : $recipient,
            'to_name' => is_array($recipient) ? $recipient['name'] : null,
            'from_email' => $fromAddress['email'],
            'from_name' => $fromAddress['name'],
            'reply_to' => $template->reply_to,
            'cc' => $template->cc,
            'bcc' => $template->bcc,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'variables' => $variables,
            'priority' => $options['priority'] ?? $template->priority ?? 5,
            'max_retries' => $options['max_retries'] ?? 3,
            'send_at' => $options['send_at'] ?? null,
            'tracking_id' => $options['tracking_id'] ?? null,
            'metadata' => $options['metadata'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Get statistics for email queue.
     */
    public static function getStatistics()
    {
        return [
            'pending' => static::pending()->count(),
            'sent' => static::sent()->count(),
            'failed' => static::failed()->count(),
            'processing' => static::processing()->count(),
            'ready_to_send' => static::readyToSend()->count(),
            'high_priority' => static::highPriority()->pending()->count(),
        ];
    }

    /**
     * Process next batch of emails.
     */
    public static function getNextBatch($limit = 10)
    {
        return static::readyToSend()
                    ->byPriority()
                    ->limit($limit)
                    ->get();
    }

    /**
     * Clean up old sent emails.
     */
    public static function cleanupOldEmails($days = 30)
    {
        return static::sent()
                    ->where('sent_at', '<', now()->subDays($days))
                    ->delete();
    }
}
