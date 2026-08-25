<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveType;
use App\Models\LeavePeriod;
use App\Services\LeavePolicyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    protected LeavePolicyService $policyService;

    public function __construct(LeavePolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    public function created(Employee $employee)
    {
        if (!$employee->is_active) {
            Log::info("Employee {$employee->id} created but not active. Skipping auto-entitlement.");
            return;
        }

        Log::info("Auto-entitling new employee: {$employee->id}");

        try {
            $this->entitleEmployeeForActivePeriods($employee);
        } catch (\Exception $e) {
            Log::error("Failed to auto-entitle employee {$employee->id}: {$e->getMessage()}");
        }
    }

    public function updated(Employee $employee)
    {
        $relevantChanges = $employee->wasChanged(['is_active', 'department_id', 'job_category_id', 'gender', 'employment_date']);

        if (!$relevantChanges) {
            return;
        }

        if ($employee->wasChanged('is_active') && $employee->is_active) {
            Log::info("Employee {$employee->id} activated. Running entitlement.");

            try {
                $this->entitleEmployeeForActivePeriods($employee, true);
            } catch (\Exception $e) {
                Log::error("Failed to entitle activated employee {$employee->id}: {$e->getMessage()}");
            }
        }

        if ($employee->wasChanged(['department_id', 'job_category_id', 'gender'])) {
            Log::info("Employee {$employee->id} profile changed. Re-evaluating entitlements.");

            try {
                $this->reevaluateEntitlements($employee);
            } catch (\Exception $e) {
                Log::error("Failed to re-evaluate entitlements for employee {$employee->id}: {$e->getMessage()}");
            }
        }

        if ($employee->wasChanged('employment_date')) {
            Log::info("Employee {$employee->id} employment date changed. Recalculating entitlements.");

            try {
                $this->entitleEmployeeForActivePeriods($employee, true);
            } catch (\Exception $e) {
                Log::error("Failed to recalculate entitlements for employee {$employee->id}: {$e->getMessage()}");
            }
        }
    }

    protected function entitleEmployeeForActivePeriods(Employee $employee, bool $force = false): void
    {
        $business = $employee->business;
        if (!$business) {
            Log::error("Employee {$employee->id} has no associated business.");
            return;
        }

        $activePeriods = LeavePeriod::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereDate('end_date', '>=', now())
            ->get();

        $leaveTypes = LeaveType::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $entitled = 0;
        $skipped = 0;

        foreach ($activePeriods as $period) {
            foreach ($leaveTypes as $leaveType) {
                try {

                    $exists = \App\Models\LeaveEntitlement::where([
                        'business_id' => $business->id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'leave_period_id' => $period->id,
                    ])->exists();

                    if ($exists && !$force) {
                        $skipped++;
                        continue;
                    }

                    $policy = $this->policyService->resolvePolicy(
                        $leaveType->id,
                        $employee,
                        Carbon::parse($period->start_date)
                    );

                    if (!$policy) {
                        $skipped++;
                        continue;
                    }

                    $entitlement = $this->policyService->createOrUpdateEntitlement(
                        $employee,
                        $leaveType,
                        $period,
                        $policy
                    );

                    if ($entitlement) {
                        $entitled++;
                    } else {
                        $skipped++;
                    }

                } catch (\Exception $e) {
                    Log::error("Error entitling employee {$employee->id} for leave type {$leaveType->id}: {$e->getMessage()}");
                }
            }
        }

        Log::info("Employee {$employee->id} entitlement complete: {$entitled} entitled, {$skipped} skipped");
    }

    protected function reevaluateEntitlements(Employee $employee): void
    {
        $business = $employee->business;
        if (!$business) {
            return;
        }

        $existingEntitlements = \App\Models\LeaveEntitlement::where('employee_id', $employee->id)
            ->with(['leaveType', 'leavePeriod'])
            ->get();

        $removed = 0;
        $updated = 0;
        $kept = 0;

        foreach ($existingEntitlements as $entitlement) {
            try {
                $period = $entitlement->leavePeriod;
                $leaveType = $entitlement->leaveType;

                $isEligible = $this->policyService->isEmployeeEligible(
                    $employee,
                    $leaveType,
                    Carbon::parse($period->start_date)
                );

                if (!$isEligible) {

                    if ($entitlement->days_taken > 0) {
                        Log::warning("Employee {$employee->id} no longer eligible for leave type {$leaveType->id} but has used days. Keeping entitlement.");
                        $kept++;
                    } else {

                        $entitlement->delete();
                        $removed++;
                        Log::info("Removed entitlement for employee {$employee->id}, leave type {$leaveType->id} - no longer eligible");
                    }
                } else {

                    $policy = $this->policyService->resolvePolicy(
                        $leaveType->id,
                        $employee,
                        Carbon::parse($period->start_date)
                    );

                    if ($policy) {
                        $this->policyService->createOrUpdateEntitlement(
                            $employee,
                            $leaveType,
                            $period,
                            $policy
                        );
                        $updated++;
                    }
                }

            } catch (\Exception $e) {
                Log::error("Error re-evaluating entitlement {$entitlement->id}: {$e->getMessage()}");
            }
        }

        Log::info("Re-evaluation complete for employee {$employee->id}: {$updated} updated, {$removed} removed, {$kept} kept despite ineligibility");
    }
}