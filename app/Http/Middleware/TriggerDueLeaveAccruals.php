<?php

namespace App\Http\Middleware;

use App\Jobs\RunDueLeaveAccruals;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Soft cron" for leave accrual: on the first request of a new calendar day,
 * dispatch RunDueLeaveAccruals once, then skip on every subsequent request
 * until the date changes again. This is what makes accrual "just happen"
 * without anyone needing to run an artisan command - the OS-level scheduled
 * `leave:run-accruals` command stays as the primary path, this is the
 * self-healing fallback for hosting environments where cron isn't guaranteed.
 */
class TriggerDueLeaveAccruals
{
    private const CACHE_KEY = 'leave_accrual:last_run_date';
    private const LOCK_KEY = 'leave_accrual:lock';

    public function handle(Request $request, Closure $next): Response
    {
        $today = now()->toDateString();

        if (Cache::get(self::CACHE_KEY) !== $today) {
            $lock = Cache::lock(self::LOCK_KEY, 60);

            if ($lock->get()) {
                try {
                    // Re-check inside the lock: another request may have won the race.
                    if (Cache::get(self::CACHE_KEY) !== $today) {
                        RunDueLeaveAccruals::dispatch();
                        Cache::forever(self::CACHE_KEY, $today);
                    }
                } finally {
                    $lock->release();
                }
            }
        }

        return $next($request);
    }
}
