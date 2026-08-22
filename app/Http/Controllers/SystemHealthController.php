<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only platform diagnostics - super-admin only. Every check is
 * live/computed, nothing is faked: if there's no way to know something
 * (e.g. true scheduler last-run time, since no Horizon/Telescope is
 * installed) that's stated explicitly rather than presenting a plausible
 * but fabricated value.
 */
class SystemHealthController extends Controller
{
    public function index(Business $business)
    {
        $checks = [
            'Database' => $this->checkDatabase(),
            'Queue' => $this->checkQueue(),
            'Cache' => $this->checkCache(),
            'Storage' => $this->checkStorage(),
            'Migrations' => $this->checkMigrations(),
            'Scheduler' => $this->checkScheduler(),
        ];

        $environment = $this->environmentInfo();
        $businessStats = $this->businessStats();
        $recentErrors = $this->recentErrorLogLines();

        return view('system-health.index', compact('business', 'checks', 'environment', 'businessStats', 'recentErrors'));
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $latencyMs = round((microtime(true) - $start) * 1000, 1);

            return [
                'status' => 'ok',
                'message' => "Connected ({$latencyMs}ms)",
                'detail' => 'Database: ' . DB::connection()->getDatabaseName(),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Connection failed', 'detail' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        $connection = config('queue.default');
        $failedCount = null;

        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedCount = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $e) {
            // leave $failedCount null - not fatal to the rest of the page
        }

        return [
            'status' => $connection === 'sync' ? 'warning' : 'ok',
            'message' => $connection === 'sync'
                ? 'Queue driver is "sync" - jobs run inline, not in the background.'
                : "Driver: {$connection}",
            'detail' => $failedCount !== null ? "{$failedCount} failed job(s) on record" : 'No failed_jobs table found',
        ];
    }

    private function checkCache(): array
    {
        try {
            $key = 'system_health_check_' . uniqid();
            Cache::put($key, true, 5);
            $ok = Cache::get($key) === true;
            Cache::forget($key);

            return [
                'status' => $ok ? 'ok' : 'error',
                'message' => 'Driver: ' . config('cache.default'),
                'detail' => $ok ? 'Read/write check passed' : 'Read/write check failed',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Cache check failed', 'detail' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        $path = storage_path('app');
        $writable = is_writable($path);
        $freeBytes = @disk_free_space($path);
        $totalBytes = @disk_total_space($path);
        $freeGb = $freeBytes ? round($freeBytes / 1073741824, 1) : null;
        $totalGb = $totalBytes ? round($totalBytes / 1073741824, 1) : null;

        return [
            'status' => $writable ? 'ok' : 'error',
            'message' => $writable ? 'Storage writable' : 'Storage NOT writable',
            'detail' => $freeGb !== null ? "{$freeGb} GB free of {$totalGb} GB" : 'Disk space unavailable',
        ];
    }

    private function checkMigrations(): array
    {
        try {
            $ran = DB::table('migrations')->pluck('migration')->all();
            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($f) => basename($f, '.php'))
                ->all();
            $pending = array_diff($files, $ran);

            return [
                'status' => empty($pending) ? 'ok' : 'warning',
                'message' => empty($pending) ? 'All migrations applied' : count($pending) . ' pending migration(s)',
                'detail' => empty($pending)
                    ? count($ran) . ' migrations applied'
                    : implode(', ', array_slice($pending, 0, 5)) . (count($pending) > 5 ? ', …' : ''),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Could not check migrations', 'detail' => $e->getMessage()];
        }
    }

    private function checkScheduler(): array
    {
        return [
            'status' => 'info',
            'message' => 'career-events:apply-pending, leave:run-accruals, contract:send-reminders are registered',
            'detail' => 'No execution history is tracked here (no Horizon/Telescope installed) - confirm the server cron actually calls `php artisan schedule:run` every minute.',
        ];
    }

    private function environmentInfo(): array
    {
        return [
            'App Environment' => config('app.env'),
            'Debug Mode' => config('app.debug') ? 'ON (should be OFF in production)' : 'OFF',
            'PHP Version' => PHP_VERSION,
            'Laravel Version' => app()->version(),
            'Timezone' => config('app.timezone'),
            'Memory Limit' => ini_get('memory_limit'),
            'Session Driver' => config('session.driver'),
            'Cache Driver' => config('cache.default'),
            'Queue Driver' => config('queue.default'),
        ];
    }

    private function businessStats(): array
    {
        $activeModuleSubs = DB::table('business_modules')->where('is_active', true)->count();
        $expiredModuleSubs = DB::table('business_modules')
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', '<', now()->toDateString())
            ->count();

        return [
            'Businesses' => Business::count(),
            'Employees' => Employee::count(),
            'Active Module Subscriptions' => $activeModuleSubs,
            'Expired (but still marked active) Subscriptions' => $expiredModuleSubs,
        ];
    }

    private function recentErrorLogLines(int $limit = 30): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return [];
        }

        try {
            $size = filesize($logPath);
            $readSize = min($size, 200000); // last ~200KB - avoid loading a huge log file into memory
            $handle = fopen($logPath, 'r');
            fseek($handle, -$readSize, SEEK_END);
            $content = fread($handle, $readSize);
            fclose($handle);

            $allLines = explode("\n", $content);
            $errorLines = array_values(array_filter(
                $allLines,
                fn ($l) => str_contains($l, '.ERROR') || str_contains($l, '.CRITICAL')
            ));

            return array_slice(array_reverse($errorLines), 0, $limit);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
