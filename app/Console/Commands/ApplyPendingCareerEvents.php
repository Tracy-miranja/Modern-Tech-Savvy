<?php

namespace App\Console\Commands;

use App\Services\EmployeeCareerEventService;
use Illuminate\Console\Command;

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
