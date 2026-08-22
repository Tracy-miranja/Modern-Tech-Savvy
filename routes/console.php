<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Business;

/*
|--------------------------------------------------------------------------
| Artisan Command Definitions
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote'); // no schedule here

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Schedules are defined here via the Schedule facade.
|
*/

// 1) Leave accruals – run daily at 02:30 Africa/Nairobi
Schedule::command('leave:run-accruals')
    ->name('leave-run-accruals')
    ->dailyAt('02:30')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();   // OK for command-based events

// 1b) Apply due employee career events (promotions/salary increments whose
// effective date has arrived) – daily at 03:00 Africa/Nairobi
Schedule::command('career-events:apply-pending')
    ->name('career-events-apply-pending')
    ->dailyAt('03:00')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

// 1c) Learning Management housekeeping - mandate auto-enroll sync +
// session/certificate-expiry reminder emails - daily at 03:15 Africa/Nairobi
Schedule::command('learning:sync')
    ->name('learning-sync')
    ->dailyAt('03:15')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

// 1d) Project Management housekeeping - due-soon and overdue task
// reminder emails - daily at 03:30 Africa/Nairobi
Schedule::command('project:sync')
    ->name('project-sync')
    ->dailyAt('03:30')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

// 2) Automated contract reminders – daily at 15:50 Africa/Nairobi
Schedule::call(function () {
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
    ->name('contract-reminders')
    ->dailyAt('15:50')
    ->timezone('Africa/Nairobi')
    ->onOneServer()      
    // ->runInBackground()  // NOT allowed on closures 
    ->withoutOverlapping();
// Note: runInBackground() is NOT allowed on closures; it causes errors.