<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Services\LeavePolicyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface; // <-- add this

class SyncLeavePolicies extends Command
{
    protected $signature = 'leave:sync-policies
                            {--business= : Business slug to scope sync}
                            {--period=   : Leave period slug to scope sync}
                            {--dry-run   : Show what would be done without making changes}
                            {--remove-ineligible : Remove entitlements for ineligible employees}
                            {--simulate-carryover : Show what carryover would be calculated}
                            {--simulate-accruals : Show what accruals would be calculated}'; // <-- removed --verbose

    protected $description = 'Sync leave policies to entitlements: apply gender, department, job category rules, proration, minimum service, and carryover';

    protected LeavePolicyService $policyService;

    public function handle(LeavePolicyService $policyService): int
    {
        $this->policyService = $policyService;

        $business = null;
        $period = null;

        if ($slug = $this->option('business')) {
            $business = Business::where('slug', $slug)->first();
            if (!$business) {
                $this->error("Business not found: {$slug}");
                return self::FAILURE;
            }
            $this->info("Scoped to business: {$business->company_name}");
        }

        if ($slug = $this->option('period')) {
            $period = LeavePeriod::where('slug', $slug)->first();
            if (!$period) {
                $this->error("Leave period not found: {$slug}");
                return self::FAILURE;
            }
            $this->info("Scoped to period: {$period->name}");
        }

        $dryRun            = (bool)$this->option('dry-run');
        $removeIneligible  = (bool)$this->option('remove-ineligible');
        $simulateCarryover = (bool)$this->option('simulate-carryover');
        $simulateAccruals  = (bool)$this->option('simulate-accruals');

        // Use Symfony verbosity: -v (verbose), -vv (very verbose), -vvv (debug)
        $verbosity = $this->getOutput()->getVerbosity();
        $verbose   = $verbosity >= OutputInterface::VERBOSITY_VERBOSE; // boolean, used by the rest of your code

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
        }
        if ($simulateCarryover) {
            $this->info('CARRYOVER SIMULATION MODE');
        }
        if ($simulateAccruals) {
            $this->info('ACCRUALS SIMULATION MODE');
        }

        $this->info('Starting leave policy sync...');
        $this->newLine();

        $stats = [
            'created' => 0,
            'updated' => 0,
            'removed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'ineligible_details' => [],
        ];

        // Get periods to process
        $periodQuery = LeavePeriod::query();
        if ($business) {
            $periodQuery->where('business_id', $business->id);
        }
        if ($period) {
            $periodQuery->where('id', $period->id);
        }
        $periods = $periodQuery->get();

        foreach ($periods as $leavePeriod) {
            $this->info("Processing period: {$leavePeriod->name} ({$leavePeriod->start_date} to {$leavePeriod->end_date})");

            $businessForPeriod = $leavePeriod->business;
            if (!$businessForPeriod) continue;

            // Get leave types
            $leaveTypes = LeaveType::where('business_id', $businessForPeriod->id)
                ->when(Schema::hasColumn('leave_types', 'is_active'), fn ($q) => $q->where('is_active', 1))
                ->get();

            // Get employees
            $employees = Employee::where('business_id', $businessForPeriod->id)
                ->when(Schema::hasColumn('employees', 'is_active'), fn ($q) => $q->where('is_active', 1))
                ->when(Schema::hasColumn('employees', 'status'), fn ($q) => $q->where('status', 'active'))
                ->when(Schema::hasColumn('employees', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->with(['department', 'jobCategory', 'user', 'employmentDetail'])
                ->get();

            $this->info("  Found {$leaveTypes->count()} leave types and {$employees->count()} employees");

            // First, check for existing ineligible entitlements
            if ($removeIneligible || $verbose) {
                $this->checkExistingEntitlements($leavePeriod, $employees, $leaveTypes, $stats, $dryRun, $removeIneligible, $verbose);
            }

            // Then process all employee/leave type combinations
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    try {
                        $result = $this->processEntitlement(
                            $employee,
                            $leaveType,
                            $leavePeriod,
                            $dryRun,
                            $removeIneligible,
                            $simulateCarryover,
                            $simulateAccruals,
                            $verbose,
                            $stats
                        );

                        $stats[$result]++;

                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $this->error("  Error: Employee {$employee->id}, Leave Type {$leaveType->name}: {$e->getMessage()}");
                        Log::error('Leave policy sync error', [
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'period_id' => $leavePeriod->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            $this->newLine();
        }

        // Display summary
        $this->newLine();
        $this->info('=== Sync Summary ===');
        $this->line("Created: {$stats['created']}");
        $this->line("Updated: {$stats['updated']}");
        $this->line("Removed: {$stats['removed']}");
        $this->line("Skipped: {$stats['skipped']}");
        
        if ($stats['errors'] > 0) {
            $this->error("Errors: {$stats['errors']}");
        }

        // Show ineligible details
        if (!empty($stats['ineligible_details'])) {
            $this->newLine();
            $this->warn('=== Ineligibility Details ===');
            foreach ($stats['ineligible_details'] as $detail) {
                $this->line($detail);
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN COMPLETE - No changes were saved');
        }

        return self::SUCCESS;
    }

    protected function checkExistingEntitlements(
        LeavePeriod $period,
        $employees,
        $leaveTypes,
        array &$stats,
        bool $dryRun,
        bool $removeIneligible,
        bool $verbose
    ): void {
        $existingEntitlements = LeaveEntitlement::where('leave_period_id', $period->id)
            ->with(['employee.user', 'leaveType'])
            ->get();

        if ($existingEntitlements->isEmpty()) {
            if ($verbose) {
                $this->line("  No existing entitlements found for this period");
            }
            return;
        }

        $this->line("  Checking {$existingEntitlements->count()} existing entitlements...");

        foreach ($existingEntitlements as $entitlement) {
            $employee = $entitlement->employee;
            $leaveType = $entitlement->leaveType;

            if (!$employee || !$leaveType) continue;

            $employeeName = $employee->user?->name ?: ('Employee ' . $employee->id);
            $onDate = Carbon::parse($period->start_date);

            // Check if employee is still in our active list
            $employeeStillActive = $employees->contains('id', $employee->id);
            if (!$employeeStillActive) {
                if ($verbose) {
                    $this->line("    {$employeeName} - {$leaveType->name}: Employee no longer active");
                }
                if ($removeIneligible && !$dryRun) {
                    $entitlement->delete();
                    $stats['removed']++;
                }
                continue;
            }

            // Check eligibility
            $policy = $this->policyService->resolvePolicy($leaveType->id, $employee, $onDate);
            $isEligible = $policy && $this->policyService->isEmployeeEligible($employee, $leaveType, $onDate);

            if (!$isEligible) {
                $reason = $this->getIneligibilityReason($employee, $leaveType, $policy, $onDate);
                
                $message = "    INELIGIBLE: {$employeeName} - {$leaveType->name}: {$reason}";
                $this->warn($message);
                $stats['ineligible_details'][] = $message;

                if ($removeIneligible) {
                    if (!$dryRun) {
                        $entitlement->delete();
                    }
                    $this->line("      " . ($dryRun ? "[Would remove]" : "[Removed]"));
                    if (!$dryRun) {
                        $stats['removed']++;
                    }
                }
            }
        }
    }

    protected function getIneligibilityReason(
        Employee $employee,
        LeaveType $leaveType,
        $policy,
        Carbon $onDate
    ): string {
        if (!$policy) {
            return "No matching policy found";
        }

        $reasons = [];

        // Gender check
        $policyGender = strtolower($policy->gender_applicable ?? 'all');
        $employeeGender = strtolower($employee->gender ?? '');
        
        if ($policyGender !== 'all' && $policyGender !== $employeeGender) {
            $reasons[] = "Gender mismatch (policy: {$policyGender}, employee: {$employeeGender})";
        }

        // Department check
        if ($policy->department_id && $policy->department_id !== $employee->department_id) {
            $policyDept = $policy->department?->name ?? "Dept #{$policy->department_id}";
            $empDept = $employee->department?->name ?? "Dept #{$employee->department_id}";
            $reasons[] = "Department mismatch (policy: {$policyDept}, employee: {$empDept})";
        }

        // Job category check
        if ($policy->job_category_id && $policy->job_category_id !== $employee->job_category_id) {
            $policyJob = $policy->jobCategory?->name ?? "Job #{$policy->job_category_id}";
            $empJob = $employee->jobCategory?->name ?? "Job #{$employee->job_category_id}";
            $reasons[] = "Job category mismatch (policy: {$policyJob}, employee: {$empJob})";
        }

        // Minimum service check
        if ($policy->minimum_service_days_required > 0) {
            $employmentDate = $employee->employment_date
                ?? optional($employee->employmentDetail)->employment_date
                ?? null;

            if (!$employmentDate) {
                $reasons[] = "No employment date";
            } else {
                $ed = Carbon::parse($employmentDate);
                $serviceDays = $ed->diffInDays($onDate);
                if ($serviceDays < $policy->minimum_service_days_required) {
                    $reasons[] = "Insufficient service days ({$serviceDays}/{$policy->minimum_service_days_required})";
                }
            }
        }

        return empty($reasons) ? "Unknown reason" : implode(', ', $reasons);
    }

protected function processEntitlement(
    Employee $employee,
    LeaveType $leaveType,
    LeavePeriod $period,
    bool $dryRun,
    bool $removeIneligible,
    bool $simulateCarryover,
    bool $simulateAccruals,
    bool $verbose,
    array &$stats
): string {
    $onDate = Carbon::parse($period->start_date);
    $employeeName = $employee->user?->name ?: ('Employee ' . $employee->id);

    // Existing entitlement (if any)
    $existing = LeaveEntitlement::where([
        'business_id'     => $employee->business_id,
        'employee_id'     => $employee->id,
        'leave_type_id'   => $leaveType->id,
        'leave_period_id' => $period->id,
    ])->first();

    // Resolve policy
    $policy = $this->policyService->resolvePolicy($leaveType->id, $employee, $onDate);
    if (!$policy) {
        if ($verbose) $this->line("    {$employeeName} - {$leaveType->name}: No policy found");
        return 'skipped';
    }

    // Eligibility
    if (!$this->policyService->isEmployeeEligible($employee, $leaveType, $onDate)) {
        if ($existing && !$removeIneligible && $verbose) {
            $reason = $this->getIneligibilityReason($employee, $leaveType, $policy, $onDate);
            $this->warn("    KEEP (not eligible): {$employeeName} - {$leaveType->name}: {$reason}");
        }
        return 'skipped';
    }

    // Reference: base/default (we no longer add this to totals directly)
    $entitledDays = (float) ($policy->default_days ?? 0);

    // Carryover (from previous period, capped by policy)
    $carryover = $this->policyService->calculateCarryover($employee, $leaveType, $period, $policy);
    if ($simulateCarryover) {
        $this->info("    CARRYOVER: {$employeeName} - {$leaveType->name}: {$carryover} days (max: {$policy->max_carryover_days})");
    }

    // Accrual toggles
    $hasAccruableFlag = Schema::hasColumn('leave_types', 'allowance_accruable');
    $isAccruable = $hasAccruableFlag ? (bool)$leaveType->allowance_accruable : true;
    $freq = strtolower((string)($policy->accrual_frequency ?? 'monthly'));

    // Front-load rule:
    // - If NOT accruable  -> credit full now
    // - OR if frequency is YEARLY -> credit full at period start (front-load)
    $shouldFrontLoad = (!$isAccruable) || ($freq === 'yearly');

    // Build a transient entitlement for accrual calc context if we don't have an existing row yet
    $entForCalc = $existing ?: (new LeaveEntitlement([
        'employee_id'     => $employee->id,
        'leave_type_id'   => $leaveType->id,
        'leave_period_id' => $period->id,
        'accrued_days'    => 0,
        'last_accrued_at' => $period->start_date,
    ]));
    $entForCalc->setRelation('leaveType', $leaveType);
    $entForCalc->setRelation('leavePeriod', $period);
    $entForCalc->setRelation('employee', $employee);

    // Compute accrued now
    $asOf = now();
    $accruedDays = $shouldFrontLoad
        ? (float)$entitledDays
        : (float)$this->policyService->calculateAccruedDays($entForCalc, $policy, $asOf);

    if ($simulateAccruals) {
        $this->info("    ACCRUALS: {$employeeName} - {$leaveType->name}: {$accruedDays} days (freq: {$freq}, amount: {$policy->accrual_amount})");
    }

    // Totals follow the new rule
    $totalDays = (float)$carryover + (float)$accruedDays;

    if ($existing) {
        $oldTotal = (float)$existing->total_days;

        if (!$dryRun) {
            $existing->entitled_days  = $entitledDays;   // reference/reporting
            $existing->carryover_days = $carryover;
            $existing->accrued_days   = $accruedDays;
            $existing->total_days     = $totalDays;
            $existing->days_remaining = max(0, $totalDays - (float)($existing->days_taken ?? 0));
            // For front-loaded types, anchor accrual at the start of the period
            if ($shouldFrontLoad) {
                $existing->last_accrued_at = $period->start_date;
            }
            $existing->save();
        }

        if (abs($oldTotal - $totalDays) > 0.01) {
            $this->line("  Updated: {$employeeName} - {$leaveType->name}: total {$oldTotal} → {$totalDays} (carryover {$carryover}, accrued {$accruedDays})");
            return 'updated';
        }

        return 'skipped';
    }

    // Create new
    if (!$dryRun) {
        LeaveEntitlement::create([
            'business_id'     => $employee->business_id,
            'employee_id'     => $employee->id,
            'leave_type_id'   => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days'   => $entitledDays,   // reference
            'carryover_days'  => $carryover,
            'accrued_days'    => $accruedDays,
            'total_days'      => $totalDays,
            'days_taken'      => 0,
            'days_remaining'  => $totalDays,
            // For front-loaded types, set anchor at period start
            'last_accrued_at' => $shouldFrontLoad ? $period->start_date : $period->start_date,
        ]);
    }

    $this->line("  Created: {$employeeName} - {$leaveType->name}: total {$totalDays} days (carryover {$carryover}, accrued {$accruedDays})");
    return 'created';
}



}