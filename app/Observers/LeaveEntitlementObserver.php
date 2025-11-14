<?php
// App/Observers/LeaveEntitlementObserver.php
namespace App\Observers;

use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeavePeriod;
use App\Models\Employee;
use App\Services\LeavePolicyService;
use Carbon\Carbon;

class LeaveEntitlementObserver
{
    public function __construct(private LeavePolicyService $policyService) {}

    public function creating(LeaveEntitlement $e): void
    {
        $this->applySnapshot($e);
    }

    public function updating(LeaveEntitlement $e): void
    {
        // Only if caller didn’t already set custom numbers
        $this->applySnapshot($e);
    }

    private function applySnapshot(LeaveEntitlement $e): void
    {
        // need the related models
        $employee = $e->relationLoaded('employee') ? $e->employee : Employee::find($e->employee_id);
        $leaveType= $e->relationLoaded('leaveType') ? $e->leaveType : LeaveType::find($e->leave_type_id);
        $period   = $e->relationLoaded('leavePeriod') ? $e->leavePeriod : LeavePeriod::find($e->leave_period_id);

        if (!$employee || !$leaveType || !$period) return;

        $policy = $this->policyService->resolvePolicy($leaveType->id, $employee, Carbon::parse($period->start_date));
        if (!$policy) return;

        $snap = $this->policyService->buildEntitlementSnapshot($employee, $leaveType, $period, $policy, now());

        // keep entitled for reference, persist new rule fields
        $e->entitled_days  = $snap['entitled'];
        $e->carryover_days = $snap['carryover'];
        $e->accrued_days   = $snap['accrued'];
        $e->total_days     = $snap['total'];
        $e->days_remaining = max(0, $e->total_days - (float)($e->days_taken ?? 0));
        $e->last_accrued_at = $e->last_accrued_at ?? $period->start_date;
    }
}
