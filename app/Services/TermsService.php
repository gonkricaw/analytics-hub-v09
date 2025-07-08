<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TermsUpdatedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Terms and Conditions Service
 *
 * Handles Terms & Conditions management including:
 * - Version tracking and updates
 * - User notification when terms are updated
 * - Bulk notification system
 * - Acceptance tracking and validation
 */
class TermsService
{
    /**
     * Update terms and conditions to new version
     *
     * @param string $newVersion
     * @param string $updateSummary
     * @return bool
     */
    public function updateTermsVersion($newVersion, $updateSummary = null)
    {
        $currentVersion = config('app.terms_version', '1.0');

        if ($newVersion === $currentVersion) {
            Log::info('Terms version update skipped - same version', [
                'current_version' => $currentVersion,
                'new_version' => $newVersion
            ]);
            return false;
        }

        try {
            // Update configuration (in production, this would be environment-specific)
            config(['app.terms_version' => $newVersion]);

            // Reset all users' terms acceptance status
            User::where('terms_accepted', true)->update([
                'terms_accepted' => false,
                'terms_accepted_at' => null
            ]);

            // Send notifications to all active users
            $this->notifyUsersOfTermsUpdate($newVersion, $currentVersion, $updateSummary);

            Log::info('Terms and conditions updated successfully', [
                'previous_version' => $currentVersion,
                'new_version' => $newVersion,
                'updated_at' => now()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update terms and conditions', [
                'error' => $e->getMessage(),
                'previous_version' => $currentVersion,
                'new_version' => $newVersion,
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Notify all users about terms update
     *
     * @param string $newVersion
     * @param string $previousVersion
     * @param string|null $updateSummary
     * @return void
     */
    protected function notifyUsersOfTermsUpdate($newVersion, $previousVersion, $updateSummary = null)
    {
        // Get all active users
        $users = User::where('status', 'active')->get();

        if ($users->isEmpty()) {
            Log::info('No active users to notify about terms update');
            return;
        }

        // Send notifications in batches to avoid overwhelming the system
        $notification = new TermsUpdatedNotification($newVersion, $previousVersion, $updateSummary);

        $users->chunk(50)->each(function ($userChunk) use ($notification) {
            Notification::send($userChunk, $notification);
        });

        Log::info('Terms update notifications sent', [
            'users_notified' => $users->count(),
            'new_version' => $newVersion,
            'previous_version' => $previousVersion
        ]);
    }

    /**
     * Check if user needs to accept updated terms
     *
     * @param User $user
     * @return bool
     */
    public function userNeedsToAcceptTerms(User $user)
    {
        if (!$user->terms_accepted) {
            return true;
        }

        $currentVersion = config('app.terms_version', '1.0');
        return $user->terms_version !== $currentVersion;
    }

    /**
     * Get users who haven't accepted current terms
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersNeedingTermsAcceptance()
    {
        $currentVersion = config('app.terms_version', '1.0');

        return User::where('status', 'active')
            ->where(function ($query) use ($currentVersion) {
                $query->where('terms_accepted', false)
                      ->orWhere('terms_version', '!=', $currentVersion)
                      ->orWhereNull('terms_version');
            })
            ->get();
    }

    /**
     * Get terms acceptance statistics
     *
     * @return array
     */
    public function getTermsAcceptanceStats()
    {
        $currentVersion = config('app.terms_version', '1.0');

        $totalUsers = User::where('status', 'active')->count();
        $acceptedUsers = User::where('status', 'active')
            ->where('terms_accepted', true)
            ->where('terms_version', $currentVersion)
            ->count();

        $pendingUsers = $totalUsers - $acceptedUsers;
        $acceptanceRate = $totalUsers > 0 ? round(($acceptedUsers / $totalUsers) * 100, 2) : 0;

        return [
            'current_version' => $currentVersion,
            'total_users' => $totalUsers,
            'accepted_users' => $acceptedUsers,
            'pending_users' => $pendingUsers,
            'acceptance_rate' => $acceptanceRate
        ];
    }

    /**
     * Mark user as having accepted terms
     *
     * @param User $user
     * @param string|null $version
     * @return bool
     */
    public function markUserTermsAccepted(User $user, $version = null)
    {
        $version = $version ?? config('app.terms_version', '1.0');

        try {
            $user->update([
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'terms_version' => $version
            ]);

            Log::info('User accepted terms', [
                'user_id' => $user->id,
                'email' => $user->email,
                'version' => $version,
                'accepted_at' => now()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark user terms acceptance', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'version' => $version
            ]);

            return false;
        }
    }

    /**
     * Send reminder notifications to users who haven't accepted terms
     *
     * @param int $daysOverdue
     * @return int Number of users reminded
     */
    public function sendTermsReminderNotifications($daysOverdue = 7)
    {
        $reminderDate = now()->subDays($daysOverdue);
        $currentVersion = config('app.terms_version', '1.0');

        $users = User::where('status', 'active')
            ->where(function ($query) use ($currentVersion, $reminderDate) {
                $query->where('terms_accepted', false)
                      ->orWhere('terms_version', '!=', $currentVersion)
                      ->orWhereNull('terms_version');
            })
            ->where('created_at', '<=', $reminderDate)
            ->get();

        if ($users->isEmpty()) {
            return 0;
        }

        $notification = new TermsUpdatedNotification(
            $currentVersion,
            null,
            'Reminder: You have not yet accepted our updated Terms & Conditions. Please review and accept them to continue using the system.'
        );

        $users->chunk(50)->each(function ($userChunk) use ($notification) {
            Notification::send($userChunk, $notification);
        });

        Log::info('Terms reminder notifications sent', [
            'users_reminded' => $users->count(),
            'days_overdue' => $daysOverdue,
            'version' => $currentVersion
        ]);

        return $users->count();
    }
}
