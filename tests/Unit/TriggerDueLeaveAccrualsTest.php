<?php

namespace Tests\Unit;

use App\Http\Middleware\TriggerDueLeaveAccruals;
use App\Jobs\RunDueLeaveAccruals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TriggerDueLeaveAccrualsTest extends TestCase
{
    public function test_dispatches_accrual_job_once_per_day_and_then_skips(): void
    {
        Bus::fake();
        Cache::forget('leave_accrual:last_run_date');

        $middleware = new TriggerDueLeaveAccruals();
        $next = fn ($request) => response('ok');

        // First request of the day: should dispatch.
        $middleware->handle(Request::create('/'), $next);
        Bus::assertDispatchedTimes(RunDueLeaveAccruals::class, 1);

        // Second request, same day: should NOT dispatch again.
        $middleware->handle(Request::create('/'), $next);
        Bus::assertDispatchedTimes(RunDueLeaveAccruals::class, 1);
    }

    public function test_dispatches_again_after_the_cached_date_changes(): void
    {
        Bus::fake();
        Cache::forget('leave_accrual:last_run_date');

        $middleware = new TriggerDueLeaveAccruals();
        $next = fn ($request) => response('ok');

        $middleware->handle(Request::create('/'), $next);
        Bus::assertDispatchedTimes(RunDueLeaveAccruals::class, 1);

        // Simulate the date having moved on since the last run.
        Cache::forever('leave_accrual:last_run_date', '2000-01-01');

        $middleware->handle(Request::create('/'), $next);
        Bus::assertDispatchedTimes(RunDueLeaveAccruals::class, 2);
    }
}
