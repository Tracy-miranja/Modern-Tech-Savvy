<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Business;

/**
 * Kernel class handles the registration and scheduling of Artisan commands.
 *
 * This class defines scheduled tasks such as leave accruals and contract reminders,
 * ensuring they run at specified times in the Africa/Nairobi timezone.
 *
 * Special Considerations:
 * - Session usage in CLI: If any scheduled command relies on session data, ensure
 *   that session drivers and configurations are compatible with CLI execution.
 * - Logging: Scheduled tasks may log output for monitoring and debugging.
 */
class Kernel extends ConsoleKernel {

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /* Run leave accruals daily (Africa/Nairobi time)
        $schedule->command('leave:run-accruals')
            ->dailyAt('02:30')
            ->timezone('Africa/Nairobi')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // Automated contract reminders using a dedicated Artisan command
        $schedule->command('contract:send-reminders')
            ->dailyAt('15:50')
            ->timezone('Africa/Nairobi')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();
        ->runInBackground();*/
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
                Log::error('Contract reminder scheduler failed', [
                    'active_business_slug' => isset($slug) ? $slug : null,
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);

        require base_path('routes/console.php');
        require base_path('routes/console.php');
    }
}

