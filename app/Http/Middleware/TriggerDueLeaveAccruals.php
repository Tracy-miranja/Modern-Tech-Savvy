<?php

namespace App\Http\Middleware;

use App\Jobs\RunDueLeaveAccruals;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

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
