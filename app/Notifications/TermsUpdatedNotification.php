<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * Terms and Conditions Update Notification
 *
 * Notifies users when Terms & Conditions have been updated and require re-acceptance.
 * Sent via email and stored in database for in-app notifications.
 */
class TermsUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $newVersion;
    protected $previousVersion;
    protected $updateSummary;

    /**
     * Create a new notification instance.
     *
     * @param string $newVersion
     * @param string $previousVersion
     * @param string $updateSummary
     */
    public function __construct($newVersion, $previousVersion = null, $updateSummary = null)
    {
        $this->newVersion = $newVersion;
        $this->previousVersion = $previousVersion;
        $this->updateSummary = $updateSummary ?? 'Our Terms & Conditions have been updated. Please review and accept the new terms.';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Terms & Conditions Updated - Action Required')
            ->greeting('Hello ' . $notifiable->full_name . ',')
            ->line('We have updated our Terms & Conditions and your acceptance is required.')
            ->line($this->updateSummary)
            ->line('You will be prompted to review and accept the new terms on your next login.')
            ->action('Review Terms & Conditions', route('terms.show'))
            ->line('Version: ' . $this->newVersion)
            ->line('If you have any questions about these changes, please contact your system administrator.')
            ->salutation('Best regards,<br>Analytics Hub Team');
    }

    /**
     * Get the database representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'terms_updated',
            'title' => 'Terms & Conditions Updated',
            'message' => $this->updateSummary,
            'data' => [
                'new_version' => $this->newVersion,
                'previous_version' => $this->previousVersion,
                'action_required' => true,
                'action_url' => route('terms.show'),
                'action_text' => 'Review Terms'
            ],
            'priority' => 'high'
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'terms_updated',
            'new_version' => $this->newVersion,
            'previous_version' => $this->previousVersion,
            'update_summary' => $this->updateSummary,
            'notified_at' => now()
        ];
    }
}
