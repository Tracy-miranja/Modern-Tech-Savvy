<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\Business;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('leave:run-accruals')
    ->name('leave-run-accruals')
    ->dailyAt('02:30')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('career-events:apply-pending')
    ->name('career-events-apply-pending')
    ->dailyAt('03:00')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('learning:sync')
    ->name('learning-sync')
    ->dailyAt('03:15')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('project:sync')
    ->name('project-sync')
    ->dailyAt('03:30')
    ->timezone('Africa/Nairobi')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::call(function () {
    try {
        if (!Session::isStarted()) {
            Session::start();
        }

        $slug = Session::get('active_business_slug');

        if (!$slug) {

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

    ->withoutOverlapping();
