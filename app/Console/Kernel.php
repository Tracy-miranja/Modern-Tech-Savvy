<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Business;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run leave accruals daily (Africa/Nairobi time)
        $schedule->command('leave:run-accruals')
            ->dailyAt('02:30')
            ->timezone('Africa/Nairobi')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // Automated contract reminders (legacy code needs session-based active_business_slug)
        $schedule->call(function () {
            try {
                if (!Session::isStarted()) {
                    Session::start();
                }

                $slug = Session::get('active_business_slug');

                if (!$slug) {
                    // Prefer business id 1 if it exists, otherwise first business
                    $business = Business::find(1) ?: Business::query()->orderBy('id')->first();
                    if ($business) {
                        Session::put('active_business_slug', $business->slug);
                    }
                }

                app()->call('App\Http\Controllers\EmployeeController@sendAutomatedContractReminders');
            } catch (\Throwable $e) {
                Log::error('Contract reminder scheduler failed', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        })
        ->dailyAt('15:50')
        ->timezone('Africa/Nairobi')
        ->onOneServer()
        ->withoutOverlapping()
        ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
