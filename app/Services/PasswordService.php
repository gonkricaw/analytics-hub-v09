<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Password Management Service
 *
 * Handles password validation, history tracking, expiry checks,
 * and password strength enforcement according to security policies.
 *
 * Security Features:
 * - Password complexity validation (8+ chars, mixed case, numbers, special chars)
 * - Password history tracking (prevents reuse of last 5 passwords)
 * - Password expiry enforcement (90 days)
 * - Force password change on first login
 * - Password strength scoring
 *
 * @package App\Services
 */
class PasswordService
{
    /**
     * Password history limit (number of previous passwords to track)
     */
    const PASSWORD_HISTORY_LIMIT = 5;

    /**
     * Password expiry days
     */
    const PASSWORD_EXPIRY_DAYS = 90;

    /**
     * Validate password strength and complexity
     *
     * @param string $password The password to validate
     * @param User|null $user The user (for history checking)
     * @return array Validation result with success status and errors
     */
    public function validatePassword(string $password, ?User $user = null): array
    {
        $errors = [];

        // Length validation
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        // Uppercase letter validation
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        // Lowercase letter validation
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        // Number validation
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        // Special character validation
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>).';
        }

        // Password history validation (if user provided)
        if ($user && $this->isPasswordInHistory($password, $user)) {
            $errors[] = 'Password cannot be one of your last ' . self::PASSWORD_HISTORY_LIMIT . ' passwords.';
        }

        // Common password validation
        if ($this->isCommonPassword($password)) {
            $errors[] = 'Password is too common. Please choose a more unique password.';
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password)
        ];
    }

    /**
     * Check if password is in user's password history
     *
     * @param string $password The password to check
     * @param User $user The user to check against
     * @return bool True if password is in history
     */
    private function isPasswordInHistory(string $password, User $user): bool
    {
        $passwordHistories = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(self::PASSWORD_HISTORY_LIMIT)
            ->get();

        foreach ($passwordHistories as $history) {
            if (Hash::check($password, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if password is in common password list
     *
     * @param string $password The password to check
     * @return bool True if password is common
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', 'qwerty',
            'abc123', 'password1', 'admin', 'letmein', 'welcome',
            'monkey', '1234567890', 'dragon', 'master', 'superman',
            'baseball', 'football', 'basketball', 'soccer', 'trustno1'
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Calculate password strength score (0-100)
     *
     * @param string $password The password to score
     * @return int Password strength score
     */
    private function calculatePasswordStrength(string $password): int
    {
        $score = 0;

        // Length bonus
        $score += min(strlen($password) * 2, 25);

        // Character variety bonus
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $score += 15;

        // Additional complexity bonus
        if (preg_match('/[!@#$%^&*(),.?":{}|<>].*[!@#$%^&*(),.?":{}|<>]/', $password)) $score += 10;
        if (strlen($password) >= 12) $score += 10;
        if (strlen($password) >= 16) $score += 10;

        return min($score, 100);
    }

    /**
     * Add password to user's password history
     *
     * @param string $password The password to add
     * @param User $user The user to add history for
     * @return void
     */
    public function addToPasswordHistory(string $password, User $user): void
    {
        // Create new password history entry
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($password),
            'created_at' => now()
        ]);

        // Clean up old password history entries (keep only last 5)
        $oldEntries = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->skip(self::PASSWORD_HISTORY_LIMIT)
            ->get();

        foreach ($oldEntries as $entry) {
            $entry->delete();
        }
    }

    /**
     * Check if user's password has expired
     *
     * @param User $user The user to check
     * @return bool True if password has expired
     */
    public function isPasswordExpired(User $user): bool
    {
        if (!$user->password_expires) {
            return false;
        }

        if (!$user->password_changed_at) {
            return true; // No password change date means expired
        }

        $expiryDate = $user->password_changed_at->addDays($user->password_expiry_days ?? self::PASSWORD_EXPIRY_DAYS);
        return now()->greaterThan($expiryDate);
    }

    /**
     * Get days until password expires
     *
     * @param User $user The user to check
     * @return int Days until expiry (negative if expired)
     */
    public function getDaysUntilExpiry(User $user): int
    {
        if (!$user->password_expires || !$user->password_changed_at) {
            return 0;
        }

        $expiryDate = $user->password_changed_at->addDays($user->password_expiry_days ?? self::PASSWORD_EXPIRY_DAYS);
        return now()->diffInDays($expiryDate, false);
    }

    /**
     * Force password change for user
     *
     * @param User $user The user to force password change for
     * @return void
     */
    public function forcePasswordChange(User $user): void
    {
        $user->update([
            'password_expires' => true,
            'password_changed_at' => now()->subDays(self::PASSWORD_EXPIRY_DAYS + 1)
        ]);
    }

    /**
     * Update user password with validation and history tracking
     *
     * @param User $user The user to update
     * @param string $newPassword The new password
     * @param bool $forceChange Whether to force password change on next login
     * @return array Result with success status and messages
     */
    public function updatePassword(User $user, string $newPassword, bool $forceChange = false): array
    {
        // Validate password
        $validation = $this->validatePassword($newPassword, $user);

        if (!$validation['success']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        // Add current password to history before updating
        if ($user->password) {
            $this->addToPasswordHistory($user->password, $user);
        }

        // Update user password
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'password_change_count' => $user->password_change_count + 1,
            'is_first_login' => $forceChange ? true : $user->is_first_login
        ]);

        // Add new password to history
        $this->addToPasswordHistory($newPassword, $user);

        return [
            'success' => true,
            'message' => 'Password updated successfully.',
            'strength' => $validation['strength']
        ];
    }

    /**
     * Generate a secure temporary password
     *
     * @param int $length Password length (default: 12)
     * @return string Generated password
     */
    public function generateTemporaryPassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()';

        $password = '';

        // Ensure at least one character from each type
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining length with random characters
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }
}
