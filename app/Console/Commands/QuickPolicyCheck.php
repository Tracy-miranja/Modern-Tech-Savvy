<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\LeavePolicy;
use App\Models\LeaveEntitlement;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class QuickPolicyCheck extends Command
{
    protected $signature = 'leave:quick-check
                            {--business= : Business slug}
                            {--period= : Leave period slug}';

    protected $description = 'Quick check for common policy issues';

    public function handle(): int
    {
        $business = Business::where('slug', $this->option('business'))->first();
        $period = LeavePeriod::where('slug', $this->option('period'))->first();

        if (!$business || !$period) {
            $this->error('Business and period required');
            return self::FAILURE;
        }

        $this->info("=== Quick Policy Check ===");
        $this->info("Business: {$business->company_name}");
        $this->info("Period: {$period->name}");
        $this->newLine();

        $this->checkPoliciesExist($business);

        $this->checkEffectiveDates($business, $period);

        $this->checkGenderMismatches($period);

        $this->checkEntitledDaysDiscrepancies($period);

        $this->checkPolicyCoverage($business);

        return self::SUCCESS;
    }

    protected function checkPoliciesExist(Business $business): void
    {
        $this->info('Check 1: Policy Configuration');

        $leaveTypes = LeaveType::where('business_id', $business->id)->get();

        foreach ($leaveTypes as $lt) {
            $policyCount = LeavePolicy::where('leave_type_id', $lt->id)->count();

            if ($policyCount === 0) {
                $this->error("  ✗ {$lt->name}: NO POLICIES CONFIGURED");
            } else {
                $this->info("  ✓ {$lt->name}: {$policyCount} policies");

                $policies = LeavePolicy::where('leave_type_id', $lt->id)->get();
                foreach ($policies as $p) {
                    $gender = strtoupper($p->gender_applicable ?? 'NULL');
                    $dept = $p->department_id ? "Dept #{$p->department_id}" : 'ALL';
                    $job = $p->job_category_id ? "Job #{$p->job_category_id}" : 'ALL';

                    $this->line("      Policy #{$p->id}: {$gender}, {$dept}, {$job}, {$p->default_days} days");
                }
            }
        }

        $this->newLine();
    }

    protected function checkEffectiveDates(Business $business, LeavePeriod $period): void
    {
        $this->info('Check 2: Effective Date Coverage');

        $periodStart = $period->start_date;
        $periodEnd = $period->end_date;

        $policies = LeavePolicy::whereHas('leaveType', function ($q) use ($business) {
            $q->where('business_id', $business->id);
        })->get();

        $activeCount = 0;
        $inactiveCount = 0;

        foreach ($policies as $p) {
            $effective = $p->effective_date;
            $end = $p->end_date;

            $isActive = $effective <= $periodStart && ($end === null || $end >= $periodStart);

            if ($isActive) {
                $activeCount++;
            } else {
                $inactiveCount++;
                $this->warn("  ⚠ Policy #{$p->id} not active for period (effective: {$effective}, end: " . ($end ?? 'none') . ")");
            }
        }

        $this->line("  Active policies for this period: {$activeCount}");
        if ($inactiveCount > 0) {
            $this->warn("  Inactive policies: {$inactiveCount}");
        }

        $this->newLine();
    }

    protected function checkGenderMismatches(LeavePeriod $period): void
    {
        $this->info('Check 3: Gender Mismatches');

        $mismatches = DB::table('leave_entitlements as le')
            ->join('employees as e', 'e.id', '=', 'le.employee_id')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('leave_types as lt', 'lt.id', '=', 'le.leave_type_id')
            ->join('leave_policies as lp', 'lp.leave_type_id', '=', 'lt.id')
            ->where('le.leave_period_id', $period->id)
            ->whereRaw('LOWER(lp.gender_applicable) != "all"')
            ->whereRaw('LOWER(e.gender) != LOWER(lp.gender_applicable)')
            ->select('u.name', 'e.gender as emp_gender', 'lt.name as leave_type', 'lp.gender_applicable as policy_gender', 'le.entitled_days')
            ->get();

        if ($mismatches->isEmpty()) {
            $this->info('  ✓ No gender mismatches found');
        } else {
            $this->error("  ✗ Found {$mismatches->count()} gender mismatches:");
            foreach ($mismatches as $m) {
                $this->line("      {$m->name} ({$m->emp_gender}) has {$m->leave_type} (policy: {$m->policy_gender})");
            }
            $this->newLine();
            $this->warn('  Run with --remove-ineligible to fix this');
        }

        $this->newLine();
    }

    protected function checkEntitledDaysDiscrepancies(LeavePeriod $period): void
    {
        $this->info('Check 4: Entitled Days vs Policy Defaults');

        $discrepancies = DB::table('leave_entitlements as le')
            ->join('employees as e', 'e.id', '=', 'le.employee_id')
            ->join('users as u', 'u.id', '=', 'e.user_id')
            ->join('leave_types as lt', 'lt.id', '=', 'le.leave_type_id')
            ->join('leave_policies as lp', 'lp.leave_type_id', '=', 'lt.id')
            ->where('le.leave_period_id', $period->id)
            ->whereRaw('le.entitled_days > (lp.default_days + lp.max_carryover_days)')
            ->select('u.name', 'lt.name as leave_type', 'le.entitled_days', 'lp.default_days', 'lp.max_carryover_days')
            ->get();

        if ($discrepancies->isEmpty()) {
            $this->info('  ✓ All entitled days within policy limits');
        } else {
            $this->warn("  ⚠ Found {$discrepancies->count()} entitled days exceeding policy max:");
            foreach ($discrepancies as $d) {
                $maxAllowed = $d->default_days + $d->max_carryover_days;
                $this->line("      {$d->name} - {$d->leave_type}: {$d->entitled_days} days (max: {$maxAllowed})");
            }
            $this->newLine();
            $this->info('  Note: This might be OK if carryover was added. Check with --simulate-carryover');
        }

        $this->newLine();
    }

    protected function checkPolicyCoverage(Business $business): void
    {
        $this->info('Check 5: Employee Coverage');

        $employees = Employee::where('business_id', $business->id)
            ->with(['department', 'jobCategory'])
            ->get();

        $leaveTypes = LeaveType::where('business_id', $business->id)->get();

        $totalCombinations = $employees->count() * $leaveTypes->count();
        $coveredCount = 0;
        $uncovered = [];

        foreach ($employees as $emp) {
            foreach ($leaveTypes as $lt) {
                $matchingPolicies = LeavePolicy::where('leave_type_id', $lt->id)
                    ->where(function ($q) use ($emp) {
                        $q->whereNull('department_id')
                          ->orWhere('department_id', $emp->department_id);
                    })
                    ->where(function ($q) use ($emp) {
                        $q->whereNull('job_category_id')
                          ->orWhere('job_category_id', $emp->job_category_id);
                    })
                    ->where(function ($q) use ($emp) {
                        $q->where('gender_applicable', 'all')
                          ->orWhereRaw('LOWER(gender_applicable) = ?', [strtolower($emp->gender ?? '')]);
                    })
                    ->count();

                if ($matchingPolicies > 0) {
                    $coveredCount++;
                } else {
                    $uncovered[] = [
                        'employee' => $emp->user->name ?? "Emp #{$emp->id}",
                        'gender' => $emp->gender,
                        'dept' => $emp->department?->name ?? 'None',
                        'job' => $emp->jobCategory?->name ?? 'None',
                        'leave_type' => $lt->name,
                    ];
                }
            }
        }

        $coveragePercent = $totalCombinations > 0 ? round(($coveredCount / $totalCombinations) * 100, 1) : 0;

        $this->line("  Coverage: {$coveredCount}/{$totalCombinations} combinations ({$coveragePercent}%)");

        if (!empty($uncovered)) {
            $this->warn("  ⚠ {count($uncovered)} employee/leave type combinations have no matching policy");

            if (count($uncovered) <= 10) {
                foreach ($uncovered as $u) {
                    $this->line("      {$u['employee']} ({$u['gender']}, {$u['dept']}, {$u['job']}) → {$u['leave_type']}");
                }
            } else {
                $this->line("      (Showing first 10)");
                foreach (array_slice($uncovered, 0, 10) as $u) {
                    $this->line("      {$u['employee']} ({$u['gender']}, {$u['dept']}, {$u['job']}) → {$u['leave_type']}");
                }
            }
        }

        $this->newLine();
    }
}