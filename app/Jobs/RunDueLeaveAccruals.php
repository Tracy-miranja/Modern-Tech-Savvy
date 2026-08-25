<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\LeavePeriod;
use App\Services\Leave\LeaveAccrualService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunDueLeaveAccruals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LeaveAccrualService $service): void
    {
        Business::query()->select('id')->chunkById(50, function ($businesses) use ($service) {
            foreach ($businesses as $business) {
                LeavePeriod::where('business_id', $business->id)
                    ->where('is_active', true)
                    ->where('can_accrue', true)
                    ->each(function (LeavePeriod $period) use ($service, $business) {
                        try {
                            $service->run($business, $period);
                        } catch (\Throwable $e) {
                            Log::error('Soft-cron leave accrual failed', [
                                'business_id' => $business->id,
                                'leave_period_id' => $period->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    });
            }
        });
    }
}
