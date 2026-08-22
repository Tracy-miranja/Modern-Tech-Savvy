<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Business;
use App\Models\LeaveEntitlement;
use App\Services\LeavePolicyService;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use Illuminate\Validation\Rule;
use App\Traits\HandleTransactions;

class LeavePeriodController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriods = $business->leavePeriods()->get();
        $leavePeriodTable = view('leave._leave_periods_table', compact('leavePeriods'))->render();
        return RequestResponse::ok('Leave periods fetched successfully.', $leavePeriodTable);
    }

    /**
     * Blank create form - used to reset #leavePeriodsFormContainer back to
     * "create" mode after an edit is cancelled or successfully saved.
     */
    public function create(Request $request)
    {
        $form = view('leave._leave_period_form', ['leavePeriod' => null])->render();
        return RequestResponse::ok('Ok', $form);
    }

    public function store(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('leave_periods', 'name')->where('business_id', $business?->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'is_active' => 'nullable|boolean',
            'accept_applications' => 'nullable|boolean',
            'can_accrue' => 'nullable|boolean',
            'restrict_applications_within_dates' => 'nullable|boolean',
            'archive' => 'nullable|boolean',
            'autocreate' => 'nullable|boolean',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));

            $leavePeriod = $business->leavePeriods()->create([
                'name' => $validatedData['name'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'accept_applications' => $validatedData['accept_applications'] ?? true,
                'can_accrue' => $validatedData['can_accrue'] ?? true,
                'restrict_applications_within_dates' => $validatedData['restrict_applications_within_dates'] ?? false,
                'archive' => $validatedData['archive'] ?? false,
                'autocreate' => $validatedData['autocreate'] ?? false,
            ])->setStatus(Status::ACTIVE);

            return RequestResponse::created('Leave period created successfully.');
        });
    }

    public function showDetails($leavePeriodId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->findOrFail($leavePeriodId);

        $detailsView = view('leave._leave_period_details', compact('leavePeriod'))->render();

        return RequestResponse::ok('Leave period details fetched successfully.', $detailsView);
    }

    /**
     * Same details view as showDetails(), reached via the flat AJAX route
     * (POST, id in the body) that the "View" button actually calls.
     */
    public function show(Request $request)
    {
        $validated = $request->validate(['id' => 'required|integer']);

        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->findOrFail($validated['id']);

        $detailsView = view('leave._leave_period_details', compact('leavePeriod'))->render();

        return RequestResponse::ok('Leave period details fetched successfully.', $detailsView);
    }

    /**
     * Returns the edit form pre-filled for the given leave period, for the
     * "Edit" button to inject into #leavePeriodsFormContainer.
     */
    public function edit(Request $request)
    {
        $validated = $request->validate(['leave_period_slug' => 'required|string|exists:leave_periods,slug']);

        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->where('slug', $validated['leave_period_slug'])->firstOrFail();

        $form = view('leave._leave_period_form', compact('leavePeriod'))->render();

        return RequestResponse::ok('Ok', $form);
    }

    public function update(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $currentLeavePeriod = $business?->leavePeriods()->where('slug', $request->input('leave_period_slug'))->first();

        $validatedData = $request->validate([
            'leave_period_slug' => 'required|string|exists:leave_periods,slug',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('leave_periods', 'name')
                    ->where('business_id', $business?->id)
                    ->ignore($currentLeavePeriod?->id),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'is_active' => 'nullable|boolean',
            'accept_applications' => 'nullable|boolean',
            'can_accrue' => 'nullable|boolean',
            'restrict_applications_within_dates' => 'nullable|boolean',
            'archive' => 'nullable|boolean',
            'autocreate' => 'nullable|boolean',
        ]);

        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            $leavePeriod = $business->leavePeriods()->where('slug', $validatedData['leave_period_slug'])->firstOrFail();

            $leavePeriod->update([
                'name' => $validatedData['name'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'is_active' => $validatedData['is_active'] ?? $leavePeriod->is_active,
                'accept_applications' => $validatedData['accept_applications'] ?? $leavePeriod->accept_applications,
                'can_accrue' => $validatedData['can_accrue'] ?? $leavePeriod->can_accrue,
                'restrict_applications_within_dates' => $validatedData['restrict_applications_within_dates'] ?? $leavePeriod->restrict_applications_within_dates,
                'archive' => $validatedData['archive'] ?? $leavePeriod->archive,
                'autocreate' => $validatedData['autocreate'] ?? $leavePeriod->autocreate,
            ]);

            return RequestResponse::ok('Leave period updated successfully.');
        });
    }

    // Return leave period as JSON for editing
    public function fetchJson($leavePeriodId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->findOrFail($leavePeriodId);

        return response()->json([
            'id' => $leavePeriod->id,
            'name' => $leavePeriod->name,
            'start_date' => $leavePeriod->start_date->format('Y-m-d'),
            'end_date' => $leavePeriod->end_date->format('Y-m-d'),
            'accept_applications' => $leavePeriod->accept_applications,
            'can_accrue' => $leavePeriod->can_accrue,
            'restrict_applications_within_dates' => $leavePeriod->restrict_applications_within_dates,
            'autocreate' => $leavePeriod->autocreate,
            'slug' => $leavePeriod->slug,
        ]);
    }

    /**
     * Closes a leave period: blocks new leave requests dated within it
     * (LeaveRequestController::store()'s guard) and triggers carryover for
     * every entitlement in it into whichever period follows next -
     * reusing LeavePolicyService::createOrUpdateEntitlement() exactly as
     * the accrual pipeline already does, just invoked explicitly now
     * instead of only lazily whenever someone next opens the following
     * period. See LeavePeriod::nextPeriod()/isClosed() and the
     * add_status_and_close_fields_to_leave_periods_table migration's
     * docblock for the "Phase 1 of Year Open/Close" framing.
     */
    public function close(Request $request, LeavePolicyService $policyService)
    {
        $validated = $request->validate(['leave_period_slug' => 'required|string|exists:leave_periods,slug']);

        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->where('slug', $validated['leave_period_slug'])->firstOrFail();

        if ($leavePeriod->isClosed()) {
            return RequestResponse::badRequest('This leave period is already closed.');
        }

        return $this->handleTransaction(function () use ($leavePeriod, $request, $policyService) {
            $nextPeriod = $leavePeriod->nextPeriod();
            $carriedCount = 0;

            if ($nextPeriod) {
                $entitlements = LeaveEntitlement::where('leave_period_id', $leavePeriod->id)
                    ->with(['employee', 'leaveType'])
                    ->get();

                foreach ($entitlements as $entitlement) {
                    if (!$entitlement->employee || !$entitlement->leaveType) {
                        continue;
                    }

                    $policy = $policyService->resolvePolicy($entitlement->leave_type_id, $entitlement->employee, $nextPeriod->start_date);
                    if (!$policy) {
                        continue;
                    }

                    $policyService->createOrUpdateEntitlement($entitlement->employee, $entitlement->leaveType, $nextPeriod, $policy);
                    $carriedCount++;
                }
            }

            $leavePeriod->update([
                'period_status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $request->user()?->id,
            ]);

            return RequestResponse::ok(
                $nextPeriod
                    ? "Leave period closed. Carryover computed into \"{$nextPeriod->name}\" for {$carriedCount} entitlement(s)."
                    : 'Leave period closed. No following period exists yet, so no carryover was computed.',
                $leavePeriod->fresh()
            );
        });
    }

    /**
     * Reopening is a rare, support-only action - does not undo carryover
     * already computed into the next period, deliberately (see the
     * migration's docblock).
     */
    public function reopen(Request $request)
    {
        $validated = $request->validate(['leave_period_slug' => 'required|string|exists:leave_periods,slug']);

        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = $business->leavePeriods()->where('slug', $validated['leave_period_slug'])->firstOrFail();

        if (!$leavePeriod->isClosed()) {
            return RequestResponse::badRequest('This leave period is not closed.');
        }

        $leavePeriod->update(['period_status' => 'open', 'closed_at' => null, 'closed_by' => null]);

        return RequestResponse::ok('Leave period reopened.', $leavePeriod->fresh());
    }

    public function destroy(Request $request)
    {
        $validatedData = $request->validate([
            'leave_period_slug' => 'required|string|exists:leave_periods,slug',
        ]);
        
        return $this->handleTransaction(function () use ($validatedData) {
            $business = Business::findBySlug(session('active_business_slug'));
            $leavePeriod = $business->leavePeriods()->where('slug', $validatedData['leave_period_slug'])->firstOrFail();

            $leavePeriod->delete();

            return RequestResponse::ok('Leave period deleted successfully.');
        });
    }
}
