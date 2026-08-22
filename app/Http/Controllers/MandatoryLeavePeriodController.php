<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\LeaveEntitlement;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\MandatoryLeavePeriod;
use App\Traits\HandleTransactions;
use App\Http\RequestResponse;
use Illuminate\Http\Request;

/**
 * Company-mandated leave (e.g. a Dec 24-28 office shutdown): HR declares a
 * date range + leave type + scope (whole org / departments / locations),
 * and every affected employee's balance for that leave type is deducted
 * automatically via LeaveEntitlement::applyAdjustment() - see
 * MandatoryLeavePeriodDeduction for why a per-employee audit row is kept
 * (needed to correctly reverse exactly this period's own contribution on
 * edit/delete, without touching other manual adjustments on the same
 * entitlement).
 */
class MandatoryLeavePeriodController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $periods = MandatoryLeavePeriod::where('business_id', $business->id)
            ->with('leaveType')
            ->withCount('deductions')
            ->withSum('deductions', 'days_deducted')
            ->orderByDesc('start_date')
            ->get();

        $table = view('leave.mandatory_leave_periods_table', compact('periods'))->render();

        return RequestResponse::ok('Ok', $table);
    }

    public function create(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $leaveTypes = LeaveType::where('business_id', $business->id)->orderBy('name')->get();
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get();
        $locations = Location::where('business_id', $business->id)->orderBy('name')->get();

        $form = view('leave.mandatory_leave_period_form', [
            'period' => null,
            'leaveTypes' => $leaveTypes,
            'departments' => $departments,
            'locations' => $locations,
        ])->render();

        return RequestResponse::ok('Ok', $form);
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'mandatory_leave_period' => 'required|string|exists:mandatory_leave_periods,slug',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $period = MandatoryLeavePeriod::where('business_id', $business->id)
            ->where('slug', $validated['mandatory_leave_period'])
            ->firstOrFail();

        $leaveTypes = LeaveType::where('business_id', $business->id)->orderBy('name')->get();
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get();
        $locations = Location::where('business_id', $business->id)->orderBy('name')->get();

        $form = view('leave.mandatory_leave_period_form', compact('period', 'leaveTypes', 'departments', 'locations'))->render();

        return RequestResponse::ok('Ok', $form);
    }

    public function store(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Active business not found in session.');
        }

        $validated = $this->validatePeriod($request, $business);
        if ($validated instanceof RequestResponse) {
            return $validated;
        }

        return $this->handleTransaction(function () use ($validated, $business) {
            $period = MandatoryLeavePeriod::create([
                'business_id' => $business->id,
                'leave_type_id' => $validated['leave_type_id'],
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'scope_type' => $validated['scope_type'],
                'scope_ids' => $validated['scope_type'] === MandatoryLeavePeriod::SCOPE_ORGANIZATION ? null : $validated['scope_ids'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $summary = $this->applyDeductions($period);

            return RequestResponse::created('Company-mandated leave days created and applied.', $summary);
        });
    }

    public function update(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Active business not found in session.');
        }

        $request->validate(['mandatory_leave_period_slug' => 'required|string|exists:mandatory_leave_periods,slug']);

        $period = MandatoryLeavePeriod::where('business_id', $business->id)
            ->where('slug', $request->input('mandatory_leave_period_slug'))
            ->first();

        if (!$period) {
            return RequestResponse::badRequest('Company-mandated leave period not found.', 404);
        }

        $validated = $this->validatePeriod($request, $business);
        if ($validated instanceof RequestResponse) {
            return $validated;
        }

        return $this->handleTransaction(function () use ($validated, $period) {
            $this->reverseDeductions($period);

            $period->update([
                'leave_type_id' => $validated['leave_type_id'],
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'scope_type' => $validated['scope_type'],
                'scope_ids' => $validated['scope_type'] === MandatoryLeavePeriod::SCOPE_ORGANIZATION ? null : $validated['scope_ids'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $summary = $this->applyDeductions($period->fresh());

            return RequestResponse::ok('Company-mandated leave days updated and re-applied.', $summary);
        });
    }

    public function destroy(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $validated = $request->validate([
            'mandatory_leave_period' => 'required|string|exists:mandatory_leave_periods,slug',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $period = MandatoryLeavePeriod::where('business_id', $business->id)
                ->where('slug', $validated['mandatory_leave_period'])
                ->first();

            if (!$period) {
                return RequestResponse::badRequest('Company-mandated leave period not found.', 404);
            }

            $this->reverseDeductions($period);
            $period->delete();

            return RequestResponse::ok('Company-mandated leave days removed and balances restored.');
        });
    }

    /**
     * Shared validation for store()/update(). Returns the validated array,
     * or a RequestResponse (via RequestResponse::badRequest) on cross-field
     * failures the validator rules alone can't express (leave type /
     * department / location must belong to the active business).
     */
    private function validatePeriod(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scope_type' => 'required|in:' . implode(',', MandatoryLeavePeriod::SCOPE_TYPES),
            'scope_ids' => 'nullable|array',
            'scope_ids.*' => 'integer',
            'notes' => 'nullable|string',
        ]);

        $leaveType = LeaveType::where('business_id', $business->id)->find($validated['leave_type_id']);
        if (!$leaveType) {
            return RequestResponse::badRequest('Leave type not found in this business.', 422);
        }

        if ($validated['scope_type'] === MandatoryLeavePeriod::SCOPE_DEPARTMENT) {
            $scopeIds = (array) ($validated['scope_ids'] ?? []);
            $validCount = Department::where('business_id', $business->id)->whereIn('id', $scopeIds)->count();
            if (empty($scopeIds) || $validCount !== count($scopeIds)) {
                return RequestResponse::badRequest('Select one or more valid departments for this scope.', 422);
            }
        } elseif ($validated['scope_type'] === MandatoryLeavePeriod::SCOPE_LOCATION) {
            $scopeIds = (array) ($validated['scope_ids'] ?? []);
            $validCount = Location::where('business_id', $business->id)->whereIn('id', $scopeIds)->count();
            if (empty($scopeIds) || $validCount !== count($scopeIds)) {
                return RequestResponse::badRequest('Select one or more valid locations for this scope.', 422);
            }
        }

        return $validated;
    }

    /**
     * Applies the deduction to every affected employee's matching
     * entitlement (skipping anyone without one, same pattern as
     * LeaveEntitlementController::processCarryover()) and records a
     * MandatoryLeavePeriodDeduction row per application for later reversal.
     */
    private function applyDeductions(MandatoryLeavePeriod $period): array
    {
        $employees = $period->resolveAffectedEmployees()->get();
        $reason = "Company-mandated leave: {$period->name} ({$period->start_date->toDateString()} to {$period->end_date->toDateString()})";

        $applied = 0;
        $totalDays = 0.0;
        $skippedNoEntitlement = [];
        $skippedZeroDays = [];

        foreach ($employees as $employee) {
            $entitlement = LeaveEntitlement::where('business_id', $period->business_id)
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $period->leave_type_id)
                ->whereHas('leavePeriod', function ($q) use ($period) {
                    $q->where('start_date', '<=', $period->start_date)
                        ->where('end_date', '>=', $period->start_date);
                })
                ->first();

            if (!$entitlement) {
                $skippedNoEntitlement[] = $employee->id;
                continue;
            }

            $days = LeaveRequest::calculateTotalDays(
                $period->start_date,
                $period->end_date,
                false,
                $period->leaveType,
                $period->business_id,
                $employee->location_id
            );

            if ($days <= 0) {
                $skippedZeroDays[] = $employee->id;
                continue;
            }

            $entitlement->applyAdjustment(-$days, $reason);

            $period->deductions()->create([
                'employee_id' => $employee->id,
                'leave_entitlement_id' => $entitlement->id,
                'days_deducted' => $days,
            ]);

            $applied++;
            $totalDays += $days;
        }

        return [
            'period' => $period->fresh(),
            'applied' => $applied,
            'total_days_deducted' => $totalDays,
            'skipped_no_entitlement' => $skippedNoEntitlement,
            'skipped_zero_days' => $skippedZeroDays,
        ];
    }

    /**
     * Adds each deduction's days back to its entitlement (cumulative,
     * same applyAdjustment() used to deduct) and removes the audit rows -
     * called before both update() (to cleanly reapply with new params) and
     * destroy().
     */
    private function reverseDeductions(MandatoryLeavePeriod $period): void
    {
        foreach ($period->deductions as $deduction) {
            $entitlement = $deduction->leaveEntitlement;
            if ($entitlement) {
                $entitlement->applyAdjustment(
                    (float) $deduction->days_deducted,
                    "Reversed company-mandated leave: {$period->name}"
                );
            }
            $deduction->delete();
        }
    }
}
