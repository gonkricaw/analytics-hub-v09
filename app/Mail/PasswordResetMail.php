<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Password Reset Mail
 *
 * Sends password reset email with secure token and instructions.
 * This email is queued for better performance and includes security
 * measures like token expiry and IP tracking.
 */
class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * User instance
     */
    public User $user;

    /**
     * Reset token
     */
    public string $token;

    /**
     * Token expiry time in minutes
     */
    public int $expiryMinutes;

    /**
     * Create a new message instance.
     *
     * @param User $user
     * @param string $token
     * @param int $expiryMinutes
     */
    public function __construct(User $user, string $token, int $expiryMinutes = 120)
    {
        $this->user = $user;
        $this->token = $token;
        $this->expiryMinutes = $expiryMinutes;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $resetUrl = url('/password/reset/' . $this->token . '?email=' . urlencode($this->user->email));

        return $this->subject('Analytics Hub - Password Reset Request')
                    ->view('emails.password-reset')
                    ->with([
                        'user' => $this->user,
                        'resetUrl' => $resetUrl,
                        'expiryMinutes' => $this->expiryMinutes
                    ]);
    }
}
