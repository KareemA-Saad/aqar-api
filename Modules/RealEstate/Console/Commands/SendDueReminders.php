<?php

declare(strict_types=1);

namespace Modules\RealEstate\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Reminder;
use Modules\RealEstate\Services\ReminderService;

/**
 * SendDueReminders
 *
 * Finds all due (not-completed, remind_at <= now) reminders across all
 * tenant databases, dispatches a notification to each agent, and marks
 * the reminder as completed.
 *
 * This command is designed to run in the context of a single tenant
 * (the scheduler runs per-tenant via TenantService or globally on
 *  the central DB — see Kernel.php).
 *
 * Run manually:   php artisan realestate:send-due-reminders
 * Scheduled:      every minute (see app/Console/Kernel.php)
 *
 * Part of F2.1: Follow-up Reminders & SLA Timers
 */
class SendDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'realestate:send-due-reminders
                            {--dry-run : List due reminders without sending or completing}';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications for all due follow-up reminders and mark them completed';

    public function __construct(
        protected ReminderService $reminderService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $due = $this->reminderService->getDueReminders();

        if ($due->isEmpty()) {
            $this->info('No due reminders found.');
            return self::SUCCESS;
        }

        $this->info("Found {$due->count()} due reminder(s).");

        $sent    = 0;
        $failed  = 0;

        foreach ($due as $reminder) {
            /** @var Reminder $reminder */
            try {
                if ($dryRun) {
                    $this->line(sprintf(
                        '  [DRY-RUN] Reminder #%d → Agent #%d "%s" (Inquiry #%d)',
                        $reminder->id,
                        $reminder->agent_id,
                        $reminder->agent?->name ?? 'unknown',
                        $reminder->inquiry_id
                    ));
                    continue;
                }

                $this->reminderService->dispatchAndComplete($reminder);
                $sent++;

                Log::info('RealEstate: sent due reminder', [
                    'reminder_id' => $reminder->id,
                    'agent_id'    => $reminder->agent_id,
                    'inquiry_id'  => $reminder->inquiry_id,
                ]);
            } catch (\Throwable $e) {
                $failed++;
                Log::error('RealEstate: failed to send reminder', [
                    'reminder_id' => $reminder->id,
                    'error'       => $e->getMessage(),
                ]);
                $this->error("  Failed reminder #{$reminder->id}: {$e->getMessage()}");
            }
        }

        if (!$dryRun) {
            $this->info("Sent: {$sent} | Failed: {$failed}");
        }

        return self::SUCCESS;
    }
}
