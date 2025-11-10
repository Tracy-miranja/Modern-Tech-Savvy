<?php

namespace App\Observers;

use App\Models\Employee;
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

    /**
     * Handle the Employee "created" event.
     * Automatically entitle new employees for active leave periods.
     */
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

    /**
     * Handle the Employee "updated" event.
     * Re-entitle if employee becomes active or if department/job category changes.
     */
    public function updated(Employee $employee)
    {
        $relevantChanges = $employee->wasChanged(['is_active', 'department_id', 'job_category_id', 'gender', 'hire_date']);

        if (!$relevantChanges) {
            return;
        }

        // If becoming active, entitle for all periods
        if ($employee->wasChanged('is_active') && $employee->is_active) {
            Log::info("Employee {$employee->id} activated. Running entitlement.");
            
            try {
                $this->entitleEmployeeForActivePeriods($employee, true); // Force re-entitlement
            } catch (\Exception $e) {
                Log::error("Failed to entitle activated employee {$employee->id}: {$e->getMessage()}");
            }
        }

        // If department, job category, or gender changed, re-evaluate entitlements
        if ($employee->wasChanged(['department_id', 'job_category_id', 'gender'])) {
            Log::info("Employee {$employee->id} profile changed. Re-evaluating entitlements.");
            
            try {
                $this->reevaluateEntitlements($employee);
            } catch (\Exception $e) {
                Log::error("Failed to re-evaluate entitlements for employee {$employee->id}: {$e->getMessage()}");
            }
        }

        // If hire date changed, recalculate prorated entitlements
        if ($employee->wasChanged('hire_date')) {
            Log::info("Employee {$employee->id} hire date changed. Recalculating entitlements.");
            
            try {
                $this->entitleEmployeeForActivePeriods($employee, true); // Force recalculation
            } catch (\Exception $e) {
                Log::error("Failed to recalculate entitlements for employee {$employee->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Entitle employee for all active leave periods.
     */
    protected function entitleEmployeeForActivePeriods(Employee $employee, bool $force = false): void
    {
        $business = $employee->business;
        if (!$business) {
            Log::error("Employee {$employee->id} has no associated business.");
            return;
        }

        // Get active leave periods
        $activePeriods = LeavePeriod::where('business_id', $business->id)
            ->where('is_active', true)
            ->whereDate('end_date', '>=', now())
            ->get();

        // Get all active leave types
        $leaveTypes = LeaveType::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $entitled = 0;
        $skipped = 0;

        foreach ($activePeriods as $period) {
            foreach ($leaveTypes as $leaveType) {
                try {
                    // Check if already exists
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

                    // Resolve policy
                    $policy = $this->policyService->resolvePolicy(
                        $leaveType->id,
                        $employee,
                        Carbon::parse($period->start_date)
                    );

                    if (!$policy) {
                        $skipped++;
                        continue;
                    }

                    // Create or update entitlement
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

    /**
     * Re-evaluate existing entitlements when employee profile changes.
     * This may remove entitlements they're no longer eligible for.
     */
    protected function reevaluateEntitlements(Employee $employee): void
    {
        $business = $employee->business;
        if (!$business) {
            return;
        }

        // Get all existing entitlements
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

                // Check if still eligible
                $isEligible = $this->policyService->isEmployeeEligible(
                    $employee,
                    $leaveType,
                    Carbon::parse($period->start_date)
                );

                if (!$isEligible) {
                    // No longer eligible - check if they've used any days
                    if ($entitlement->days_taken > 0) {
                        Log::warning("Employee {$employee->id} no longer eligible for leave type {$leaveType->id} but has used days. Keeping entitlement.");
                        $kept++;
                    } else {
                        // Safe to remove
                        $entitlement->delete();
                        $removed++;
                        Log::info("Removed entitlement for employee {$employee->id}, leave type {$leaveType->id} - no longer eligible");
                    }
                } else {
                    // Still eligible - recalculate in case amounts changed
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