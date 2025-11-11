<?php

// app/Console/Commands/RunLeaveAccruals.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\LeavePeriod;
use App\Services\LeavePolicyService;
use Carbon\Carbon;

class RunLeaveAccruals extends Command
{
    protected $signature = 'leave:run-accruals {--as-of= : YYYY-MM-DD override}';
    protected $description = 'Process daily leave accruals for all active periods';

    public function handle(LeavePolicyService $svc): int
    {
        $asOf = $this->option('as-of') ? Carbon::parse($this->option('as-of')) : now();

        Business::query()->chunkById(50, function ($bizChunk) use ($svc, $asOf) {
            foreach ($bizChunk as $biz) {
                $periods = LeavePeriod::where('business_id', $biz->id)
                    ->whereDate('start_date', '<=', $asOf)
                    ->whereDate('end_date', '>=', $asOf)
                    ->get();

                foreach ($periods as $p) {
                    $processed = $svc->processAccruals($p, $asOf);
                    $this->info("{$biz->company_name} – {$p->name}: processed {$processed}");
                }
            }
        });

        return self::SUCCESS;
    }
}
