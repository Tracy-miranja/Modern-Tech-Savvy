<?php

namespace App\Console\Commands;

use App\Services\EmployeeCareerEventService;
use Illuminate\Console\Command;

/**
 * Applies any promotion/salary-increment career event whose effective_date
 * has arrived - see the employee_career_events migration's docblock.
 * Scheduled daily in routes/console.php, mirroring leave:run-accruals.
 */
class ApplyPendingCareerEvents extends Command
{
    protected $signature = 'career-events:apply-pending';

    protected $description = 'Apply pending employee career events (promotions/salary increments) whose effective date has arrived';

    public function handle(EmployeeCareerEventService $service): int
    {
        $count = $service->applyDuePendingEvents();

        $this->info("Applied {$count} due career event(s).");

        return self::SUCCESS;
    }
}
