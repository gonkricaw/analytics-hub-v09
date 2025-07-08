<?php

namespace App\Console\Commands;

use App\Services\TermsService;
use Illuminate\Console\Command;

/**
 * Update Terms and Conditions Command
 *
 * Console command to update Terms & Conditions version and notify all users.
 * This command will reset all users' terms acceptance status and send notifications.
 */
class UpdateTermsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terms:update
                            {version : The new terms version}
                            {--summary= : Summary of changes}
                            {--send-reminders : Send reminder notifications to users who haven\'t accepted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Terms & Conditions version and notify users';

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
        $newVersion = $this->argument('version');
        $summary = $this->option('summary');
        $sendReminders = $this->option('send-reminders');

        // Validate version format
        if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $newVersion)) {
            $this->error('Invalid version format. Please use format: x.x or x.x.x');
            return 1;
        }

        // Show current state
        $currentVersion = config('app.terms_version', '1.0');
        $this->info("Current Terms Version: {$currentVersion}");
        $this->info("New Terms Version: {$newVersion}");

        if ($summary) {
            $this->info("Update Summary: {$summary}");
        }

        // Show stats before update
        $stats = $this->termsService->getTermsAcceptanceStats();
        $this->info("Current acceptance stats:");
        $this->info("- Total active users: {$stats['total_users']}");
        $this->info("- Users who accepted current terms: {$stats['accepted_users']}");
        $this->info("- Users pending acceptance: {$stats['pending_users']}");
        $this->info("- Acceptance rate: {$stats['acceptance_rate']}%");

        // Confirm update
        if (!$this->confirm("Do you want to update Terms & Conditions to version {$newVersion}? This will reset all users' acceptance status and send notifications.")) {
            $this->info('Terms update cancelled.');
            return 0;
        }

        // Show progress
        $this->info('Updating Terms & Conditions...');
        $bar = $this->output->createProgressBar(3);

        // Step 1: Update version
        $bar->setMessage('Updating terms version...');
        $bar->advance();

        $success = $this->termsService->updateTermsVersion($newVersion, $summary);

        if (!$success) {
            $this->error('Failed to update Terms & Conditions. Check logs for details.');
            return 1;
        }

        // Step 2: Show updated stats
        $bar->setMessage('Calculating new stats...');
        $bar->advance();

        $newStats = $this->termsService->getTermsAcceptanceStats();

        // Step 3: Send reminders if requested
        if ($sendReminders) {
            $bar->setMessage('Sending reminder notifications...');
            $bar->advance();

            $remindedUsers = $this->termsService->sendTermsReminderNotifications(0);
            $this->info("\nReminder notifications sent to {$remindedUsers} users.");
        } else {
            $bar->advance();
        }

        $bar->finish();

        // Show results
        $this->info("\n\nTerms & Conditions updated successfully!");
        $this->info("New version: {$newVersion}");
        $this->info("Users requiring acceptance: {$newStats['pending_users']}");

        if ($newStats['pending_users'] > 0) {
            $this->warn("Users will be prompted to accept new terms on their next login.");
            $this->info("You can send reminder notifications using: php artisan terms:remind");
        }

        return 0;
    }
}
