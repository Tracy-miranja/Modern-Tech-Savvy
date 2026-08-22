<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\LeaveEncashment;
use App\Models\LeaveEntitlement;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

/**
 * On-demand leave encashment - Leave-side only, no Payroll module
 * involvement (see the create_leave_encashments_table migration's
 * docblock). Approval is deliberately single-step by an HR/admin role
 * (gated at the route level, role:business-admin|business-hr|restricted-hr)
 * rather than reusing LeaveRequest's full multi-level approval_chain
 * machinery - that system is tightly coupled to LeaveRequest's own routing
 * (organogram/HOD/chief-of-staff resolution) and wiring it in for this
 * smaller, opt-in feature would be a disproportionate amount of coupling
 * for what this batch needs. Flagged as a scoped simplification, not an
 * oversight.
 */
class LeaveEncashmentController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request, Business $business)
    {
        $query = LeaveEncashment::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'leaveType:id,name', 'approvedBy:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $encashments = $query->orderByDesc('id')->get();

        return RequestResponse::ok('Encashments fetched.', $encashments);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'leave_type_id' => 'required|integer|exists:leave_types,id',
            'days_requested' => 'required|numeric|min:0.5',
        ]);

        $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
        if (!$employee) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        $leaveType = LeaveType::where('business_id', $business->id)->find($validated['leave_type_id']);
        if (!$leaveType) {
            return RequestResponse::badRequest('Leave type not found for this business.', 404);
        }

        $today = now()->toDateString();
        $entitlement = LeaveEntitlement::where('business_id', $business->id)
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereHas('leavePeriod', fn ($q) => $q->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today))
            ->with('leavePeriod')
            ->first();

        if (!$entitlement) {
            return RequestResponse::badRequest('This employee has no active entitlement for this leave type.');
        }

        $policy = LeavePolicy::where('leave_type_id', $leaveType->id)
            ->whereDate('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->where(function ($q) use ($employee) {
                $q->whereNull('department_id')->orWhere('department_id', $employee->department_id);
            })
            ->orderByRaw('department_id IS NULL')
            ->first();

        if (!$policy || !$policy->is_encashable) {
            return RequestResponse::badRequest('This leave type is not configured as encashable.');
        }

        $daysRequested = (float) $validated['days_requested'];

        if ($policy->max_encashable_days && $daysRequested > (float) $policy->max_encashable_days) {
            return RequestResponse::badRequest("You cannot encash more than {$policy->max_encashable_days} day(s) at a time for this leave type.");
        }

        if ($daysRequested > (float) $entitlement->days_remaining) {
            return RequestResponse::badRequest('You cannot encash more days than are currently remaining.');
        }

        $basicSalary = (float) ($employee->paymentDetails->basic_salary ?? 0);
        if ($basicSalary <= 0) {
            return RequestResponse::badRequest('This employee has no basic salary on file to compute an encashment rate.');
        }

        $dailyRate = round($basicSalary / 30, 2);
        $amount = round($dailyRate * $daysRequested, 2);

        $encashment = LeaveEncashment::create([
            'business_id' => $business->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $entitlement->leave_period_id,
            'days_requested' => $daysRequested,
            'daily_rate' => $dailyRate,
            'amount' => $amount,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return RequestResponse::created('Encashment requested.', $encashment->load('employee.user', 'leaveType'));
    }

    public function approve(Request $request, Business $business, LeaveEncashment $encashment)
    {
        if ((int) $encashment->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Encashment not found for this business.', 404);
        }
        if ($encashment->status !== 'pending') {
            return RequestResponse::badRequest('Only a pending encashment can be approved.');
        }

        return $this->handleTransaction(function () use ($encashment, $request) {
            $entitlement = LeaveEntitlement::where('business_id', $encashment->business_id)
                ->where('employee_id', $encashment->employee_id)
                ->where('leave_type_id', $encashment->leave_type_id)
                ->where('leave_period_id', $encashment->leave_period_id)
                ->first();

            if (!$entitlement) {
                return RequestResponse::badRequest('The originating entitlement no longer exists - cannot deduct days.');
            }
            if ((float) $encashment->days_requested > (float) $entitlement->days_remaining) {
                return RequestResponse::badRequest('Insufficient remaining balance to approve this encashment now.');
            }

            $entitlement->applyAdjustment(-(float) $encashment->days_requested, 'Leave encashment approved');

            $encashment->update([
                'status' => 'approved',
                'approved_by' => $request->user()?->id,
            ]);

            return RequestResponse::ok('Encashment approved.', $encashment->fresh());
        });
    }

    public function reject(Request $request, Business $business, LeaveEncashment $encashment)
    {
        if ((int) $encashment->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Encashment not found for this business.', 404);
        }
        if ($encashment->status !== 'pending') {
            return RequestResponse::badRequest('Only a pending encashment can be rejected.');
        }

        $validated = $request->validate(['rejection_reason' => 'required|string']);

        $encashment->update([
            'status' => 'rejected',
            'approved_by' => $request->user()?->id,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return RequestResponse::ok('Encashment rejected.', $encashment->fresh());
    }

    public function markDisbursed(Request $request, Business $business, LeaveEncashment $encashment)
    {
        if ((int) $encashment->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Encashment not found for this business.', 404);
        }
        if ($encashment->status !== 'approved') {
            return RequestResponse::badRequest('Only an approved encashment can be marked disbursed.');
        }

        $validated = $request->validate(['disbursed_note' => 'nullable|string']);

        $encashment->update([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'disbursed_note' => $validated['disbursed_note'] ?? null,
        ]);

        return RequestResponse::ok('Encashment marked disbursed.', $encashment->fresh());
    }
}
