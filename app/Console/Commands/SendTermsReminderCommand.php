<?php

namespace App\Console\Commands;

use App\Services\TermsService;
use Illuminate\Console\Command;

/**
 * Send Terms Reminder Command
 *
 * Console command to send reminder notifications to users who haven't accepted
 * the current Terms & Conditions.
 */
class SendTermsReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terms:remind
                            {--days=7 : Number of days overdue before sending reminder}
                            {--stats : Show acceptance statistics only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications to users who haven\'t accepted current terms';

    protected $termsService;

    /**
     * Create a new command instance.
     *
     * @param TermsService $termsService
     */
    public function __construct(TermsService $termsService)
    {
        parent::__construct();
        $this->termsService = $termsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $daysOverdue = (int) $this->option('days');
        $showStatsOnly = $this->option('stats');

        // Get and display current stats
        $stats = $this->termsService->getTermsAcceptanceStats();

        $this->info("Terms & Conditions Acceptance Statistics");
        $this->info("==========================================");
        $this->info("Current version: {$stats['current_version']}");
        $this->info("Total active users: {$stats['total_users']}");
        $this->info("Users who accepted: {$stats['accepted_users']}");
        $this->info("Users pending acceptance: {$stats['pending_users']}");
        $this->info("Acceptance rate: {$stats['acceptance_rate']}%");

        // If only showing stats, exit here
        if ($showStatsOnly) {
            return 0;
        }

        // Check if there are users to remind
        if ($stats['pending_users'] === 0) {
            $this->info("\nAll users have accepted the current Terms & Conditions. No reminders needed.");
            return 0;
        }

        // Get users who need reminders
        $usersNeedingReminders = $this->termsService->getUsersNeedingTermsAcceptance();
        $filteredUsers = $usersNeedingReminders->filter(function ($user) use ($daysOverdue) {
            return $user->created_at->lte(now()->subDays($daysOverdue));
        });

        if ($filteredUsers->isEmpty()) {
            $this->info("\nNo users are overdue for terms acceptance ({$daysOverdue} days minimum).");
            return 0;
        }

        $this->info("\nUsers eligible for reminders: {$filteredUsers->count()} (overdue by {$daysOverdue}+ days)");

        // Confirm sending reminders
        if (!$this->confirm("Send reminder notifications to {$filteredUsers->count()} users?")) {
            $this->info('Reminder sending cancelled.');
            return 0;
        }

        // Send reminders
        $this->info('Sending reminder notifications...');
        $bar = $this->output->createProgressBar(1);
        $bar->setMessage('Sending notifications...');

        $remindedUsers = $this->termsService->sendTermsReminderNotifications($daysOverdue);

        $bar->advance();
        $bar->finish();

        $this->info("\n\nReminder notifications sent successfully!");
        $this->info("Users notified: {$remindedUsers}");
        $this->info("These users will receive email notifications and see in-app alerts.");

        return 0;
    }
}
