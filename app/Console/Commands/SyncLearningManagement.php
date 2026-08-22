<?php

namespace App\Console\Commands;

use App\Services\Learning\LearningSchedulerService;
use Illuminate\Console\Command;

/**
 * Daily Learning Management housekeeping - auto-enrolls employees newly
 * matching an active course mandate, and sends due session/certificate-
 * expiry reminders. Scheduled daily in routes/console.php, mirroring
 * leave:run-accruals and career-events:apply-pending.
 */
class SyncLearningManagement extends Command
{
    protected $signature = 'learning:sync';

    protected $description = 'Auto-enroll employees newly matching an active course mandate, and send due session/certificate-expiry reminders';

    public function handle(LearningSchedulerService $service): int
    {
        $enrolled = $service->syncMandateEnrollments();
        $sessionReminders = $service->sendSessionReminders();
        $certReminders = $service->sendCertificateExpiryReminders();

        $this->info("Auto-enrolled {$enrolled} via mandates, sent {$sessionReminders} session reminder(s) and {$certReminders} certificate expiry reminder(s).");

        return self::SUCCESS;
    }
}
