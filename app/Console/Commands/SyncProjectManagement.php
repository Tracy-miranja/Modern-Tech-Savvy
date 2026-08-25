<?php

namespace App\Console\Commands;

use App\Services\Projects\ProjectSchedulerService;
use Illuminate\Console\Command;

class SyncProjectManagement extends Command
{
    protected $signature = 'project:sync';

    protected $description = 'Send due-soon and overdue project task reminder emails';

    public function handle(ProjectSchedulerService $service): int
    {
        $dueReminders = $service->sendDueReminders();
        $overdueReminders = $service->sendOverdueReminders();

        $this->info("Sent {$dueReminders} due-soon reminder(s) and {$overdueReminders} overdue reminder(s).");

        return self::SUCCESS;
    }
}
