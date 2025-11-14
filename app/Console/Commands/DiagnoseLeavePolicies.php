<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Business;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Employee;
use App\Models\LeaveEntitlement;
use App\Models\LeavePolicy;
use Carbon\Carbon;

class DiagnoseLeavePolicies extends Command
{
    protected $signature = 'leave:diagnose
                            {--business= : Business slug}
                            {--period= : Leave period slug}
                            {--employee= : Specific employee ID}
                            {--leave-type= : Specific leave type slug}';

    protected $description = 'Diagnose leave policy configuration and entitlement issues';

    public function handle(): int
    {
        $business = null;
        if ($slug = $this->option('business')) {
            $business = Business::where('slug', $slug)->first();
            if (!$business) {
                $this->error("Business not found: {$slug}");
                return self::FAILURE;
            }
        }

        $period = null;
        if ($slug = $this->option('period')) {
            $period = LeavePeriod::where('slug', $slug)->first();
            if (!$period) {
                $this->error("Period not found: {$slug}");
                return self::FAILURE;
            }
            $business = $business ?? $period->business;
        }

        if (!$business) {
            $this->error("Please specify --business or --period");
            return self::FAILURE;
        }

        $this->info("=== Leave Policy Diagnostic ===");
        $this->info("Business: {$business->company_name}");
        if ($period) {
            $this->info("Period: {$period->name}");
        }
        $this->newLine();

        // Show all leave types and their policies
        $this->showLeaveTypes($business);

        // Show policy-to-employee matching
        if ($period) {
            $this->showPolicyMatching($business, $period);
        }

        // Show specific employee details if requested
        if ($empId = $this->option('employee')) {
            $this->showEmployeeDetails($empId, $business, $period);
        }

        // Show specific leave type details if requested
        if ($ltSlug = $this->option('leave-type')) {
            $this->showLeaveTypeDetails($ltSlug, $business, $period);
        }

        return self::SUCCESS;
    }

    protected function showLeaveTypes(Business $business): void
    {
        $this->info('--- Leave Types ---');
        
        $leaveTypes = LeaveType::where('business_id', $business->id)->get();
        
        foreach ($leaveTypes as $lt) {
            $this->line("• {$lt->name} (ID: {$lt->id})");
            
            $policies = LeavePolicy::where('leave_type_id', $lt->id)->get();
            
            if ($policies->isEmpty()) {
                $this->warn("  ⚠ NO POLICIES CONFIGURED");
                continue;
            }

            foreach ($policies as $policy) {
                $dept = $policy->department_id 
                    ? ($policy->department?->name ?? "Dept #{$policy->department_id}")
                    : "ALL";
                $job = $policy->job_category_id 
                    ? ($policy->jobCategory?->name ?? "Job #{$policy->job_category_id}")
                    : "ALL";
                $gender = strtoupper($policy->gender_applicable ?? 'ALL');

                $this->line("  Policy #{$policy->id}:");
                $this->line("    - Gender: {$gender}");
                $this->line("    - Department: {$dept}");
                $this->line("    - Job Category: {$job}");
                $this->line("    - Default Days: {$policy->default_days}");
                $this->line("    - Carryover Max: {$policy->max_carryover_days}");
                $this->line("    - Min Service Days: {$policy->minimum_service_days_required}");
                $this->line("    - Prorated: " . ($policy->prorated_for_new_employees ? 'YES' : 'NO'));
                $this->line("    - Accrual: {$policy->accrual_frequency} / {$policy->accrual_amount} days");
                $this->line("    - Effective: {$policy->effective_date} to " . ($policy->end_date ?? 'indefinite'));
            }
            $this->newLine();
        }
    }

    protected function showPolicyMatching(Business $business, LeavePeriod $period): void
    {
        $this->info('--- Policy Matching Matrix ---');
        
        $employees = Employee::where('business_id', $business->id)
            ->with(['department', 'jobCategory', 'user'])
            ->get();

        $leaveTypes = LeaveType::where('business_id', $business->id)->get();

        $matrix = [];
        
        foreach ($employees as $emp) {
            $empName = $emp->user?->name ?? "Emp #{$emp->id}";
            $empGender = strtolower($emp->gender ?? 'unknown');
            $empDept = $emp->department?->name ?? 'None';
            $empJob = $emp->jobCategory?->name ?? 'None';

            foreach ($leaveTypes as $lt) {
                $matchingPolicies = LeavePolicy::where('leave_type_id', $lt->id)
                    ->whereDate('effective_date', '<=', $period->start_date)
                    ->where(function ($q) use ($period) {
                        $q->whereNull('end_date')
                          ->orWhereDate('end_date', '>=', $period->start_date);
                    })
                    ->get()
                    ->filter(function ($p) use ($empGender) {
                        $pg = strtolower($p->gender_applicable ?? 'all');
                        return $pg === 'all' || $pg === $empGender;
                    })
                    ->filter(function ($p) use ($emp) {
                        return is_null($p->department_id) || $p->department_id === $emp->department_id;
                    })
                    ->filter(function ($p) use ($emp) {
                        return is_null($p->job_category_id) || $p->job_category_id === $emp->job_category_id;
                    });

                $hasMatch = $matchingPolicies->isNotEmpty();
                $symbol = $hasMatch ? '✓' : '✗';
                
                if (!$hasMatch) {
                    $this->line("{$symbol} {$empName} ({$empGender}, {$empDept}, {$empJob}) → {$lt->name}");
                    
                    // Explain why no match
                    $allPolicies = LeavePolicy::where('leave_type_id', $lt->id)->get();
                    if ($allPolicies->isEmpty()) {
                        $this->warn("    Reason: No policies exist for this leave type");
                    } else {
                        foreach ($allPolicies as $p) {
                            $reasons = [];
                            
                            $pg = strtolower($p->gender_applicable ?? 'all');
                            if ($pg !== 'all' && $pg !== $empGender) {
                                $reasons[] = "gender ({$pg} ≠ {$empGender})";
                            }
                            
                            if ($p->department_id && $p->department_id !== $emp->department_id) {
                                $reasons[] = "department";
                            }
                            
                            if ($p->job_category_id && $p->job_category_id !== $emp->job_category_id) {
                                $reasons[] = "job category";
                            }
                            
                            if (!empty($reasons)) {
                                $this->warn("    Policy #{$p->id} excluded: " . implode(', ', $reasons));
                            }
                        }
                    }
                }
            }
        }

        $this->newLine();
    }

    protected function showEmployeeDetails(int $empId, Business $business, ?LeavePeriod $period): void
    {
        $this->info("--- Employee Details ---");
        
        $employee = Employee::with(['department', 'jobCategory', 'user'])->find($empId);
        
        if (!$employee) {
            $this->error("Employee not found: {$empId}");
            return;
        }

        $this->line("Name: " . ($employee->user?->name ?? 'N/A'));
        $this->line("Gender: " . ($employee->gender ?? 'N/A'));
        $this->line("Department: " . ($employee->department?->name ?? 'N/A'));
        $this->line("Job Category: " . ($employee->jobCategory?->name ?? 'N/A'));
        $this->line("Employment Date: " . ($employee->employment_date ?? optional($employee->employmentDetail)->employment_date ?? 'N/A'));
        $this->newLine();

        if ($period) {
            $this->line("Entitlements for period: {$period->name}");
            $entitlements = LeaveEntitlement::where('employee_id', $empId)
                ->where('leave_period_id', $period->id)
                ->with('leaveType')
                ->get();

            if ($entitlements->isEmpty()) {
                $this->warn("  No entitlements found");
            } else {
                foreach ($entitlements as $ent) {
                    $this->line("  • {$ent->leaveType->name}: {$ent->entitled_days} entitled, " .
                        "{$ent->accrued_days} accrued, {$ent->days_taken} taken, {$ent->days_remaining} remaining");
                }
            }
        }

        $this->newLine();
    }

    protected function showLeaveTypeDetails(string $slug, Business $business, ?LeavePeriod $period): void
    {
        $this->info("--- Leave Type Details ---");
        
        $leaveType = LeaveType::where('slug', $slug)
            ->where('business_id', $business->id)
            ->first();

        if (!$leaveType) {
            $this->error("Leave type not found: {$slug}");
            return;
        }

        $this->line("Name: {$leaveType->name}");
        $this->line("ID: {$leaveType->id}");
        $this->newLine();

        $policies = LeavePolicy::where('leave_type_id', $leaveType->id)->get();
        $this->line("Policies: {$policies->count()}");
        
        foreach ($policies as $policy) {
            $this->line("  Policy #{$policy->id}:");
            $this->line("    Gender: " . strtoupper($policy->gender_applicable ?? 'ALL'));
            $this->line("    Dept: " . ($policy->department?->name ?? 'ALL'));
            $this->line("    Job: " . ($policy->jobCategory?->name ?? 'ALL'));
            $this->line("    Days: {$policy->default_days}");
        }
        $this->newLine();

        if ($period) {
            $this->line("Entitlements for period: {$period->name}");
            $entitlements = LeaveEntitlement::where('leave_type_id', $leaveType->id)
                ->where('leave_period_id', $period->id)
                ->with('employee.user')
                ->get();

            if ($entitlements->isEmpty()) {
                $this->warn("  No entitlements found");
            } else {
                foreach ($entitlements as $ent) {
                    $empName = $ent->employee?->user?->name ?? "Emp #{$ent->employee_id}";
                    $this->line("  • {$empName}: {$ent->entitled_days} entitled");
                }
            }
        }

        $this->newLine();
    }
}