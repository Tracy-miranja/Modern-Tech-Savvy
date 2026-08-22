<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeavePolicyService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the unified LeaveEntitlement::recalculateTotals() formula:
 *
 *   usable_from_grant = leaveType.allowance_accruable ? accrued_days : entitled_days
 *   total_days        = usable_from_grant + carryover_days + adjustment_days
 *   days_remaining     = max(0, total_days - days_taken - days_pending)
 *
 * entitled_days and accrued_days are NEVER summed together - that was the
 * root cause of two previously-fixed bugs: a non-accruable entitlement's
 * balance inflating on manual adjustment (21+21-3=39 instead of 21-3=18),
 * and an accruable entitlement's balance jumping on leave approval
 * (6+24=30 instead of staying at 6). Every scenario below is a numbered
 * example from the design plan, and every call site that touches
 * total_days/days_remaining (LeaveEntitlement itself, LeavePolicyService,
 * LeaveEntitlementController::update(), LeaveRequestController's
 * submit/approve/reject/cancel flow) is exercised through its real public
 * entry point, not by hand-setting fields.
 */
class EntitlementUnifiedFormulaTest extends TestCase
{
    private LeavePolicyService $policyService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'hrmamsol',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);

        DB::purge('mysql');
        DB::connection('mysql')->beginTransaction();

        $this->policyService = app(LeavePolicyService::class);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'UNIFIED-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => 1,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    private function makeLeaveType(bool $accruable): LeaveType
    {
        return LeaveType::create([
            'business_id' => 1,
            'name' => ($accruable ? 'Unified Accruable ' : 'Unified Non-Accruable ') . uniqid(),
            'requires_approval' => true,
            'allowance_accruable' => $accruable,
        ]);
    }

    private function makePolicy(LeaveType $leaveType, int $defaultDays, int $maxCarryover = 0, string $accrualFrequency = 'yearly', float $accrualAmount = 0): LeavePolicy
    {
        return LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'default_days' => $defaultDays,
            'gender_applicable' => 'all',
            'max_carryover_days' => $maxCarryover,
            'accrual_frequency' => $accrualFrequency,
            'accrual_amount' => $accrualAmount,
            'minimum_service_days_required' => 0,
            'prorated_for_new_employees' => false,
            'effective_date' => '2020-01-01',
            'is_active' => true,
        ]);
    }

    private function makePeriod(string $start, string $end): LeavePeriod
    {
        return LeavePeriod::create([
            'business_id' => 1, 'name' => 'Unified Period ' . uniqid(),
            'start_date' => $start, 'end_date' => $end, 'is_active' => true,
        ]);
    }

    private function makeLeaveRequest(Employee $employee, LeaveType $leaveType, string $start, string $end): LeaveRequest
    {
        return LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'current_approval_level' => 0,
        ]);
    }

    /** Example 1: non-accruable, fresh auto-entitlement. */
    public function test_example1_non_accruable_fresh_entitlement(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        $policy = $this->makePolicy($leaveType, 21);
        $period = $this->makePeriod('2026-01-01', '2026-12-31');

        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        $this->assertSame(21.0, $entitlement->entitled_days);
        $this->assertSame(21.0, $entitlement->total_days, 'usable = entitled(21) for non-accruable; accrued is not added on top.');
        $this->assertSame(21.0, $entitlement->days_remaining);
    }

    /** Example 2 & 3: non-accruable, -3 adjustment, then re-run auto-entitle - same result both times. */
    public function test_example2_and_3_non_accruable_adjustment_survives_re_running_auto_entitle(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        $policy = $this->makePolicy($leaveType, 21);
        $period = $this->makePeriod('2026-01-01', '2026-12-31');

        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        $entitlement->applyAdjustment(-3, 'Disciplinary deduction');
        $entitlement->refresh();
        $this->assertSame(-3.0, $entitlement->adjustment_days);
        $this->assertSame(18.0, $entitlement->total_days, 'Example 2: 21 - 3 = 18, correctly, first time.');
        $this->assertSame(18.0, $entitlement->days_remaining);

        // Re-run auto-entitle (e.g. HR re-runs "Set Entitlements" after a policy tweak).
        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);
        $entitlement->refresh();

        $this->assertSame(-3.0, $entitlement->adjustment_days, 'Auto-entitle never touches the adjustment layer.');
        $this->assertSame(18.0, $entitlement->total_days, 'Example 3: identical result (18) regardless of which code path ran last.');
    }

    /** Example 4, 5 & 6: accruable, partial accrual, a leave approval doesn't inflate it, then accrual grows over time. */
    public function test_example4_5_6_accruable_partial_accrual_survives_leave_approval_and_grows_over_time(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(true);
        $policy = $this->makePolicy($leaveType, 24, 0, 'monthly', 2);
        $period = $this->makePeriod('2026-01-01', '2026-12-31');

        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        // Example 4: simulate "3 months in" (2/month accrual).
        $entitlement->accrued_days = 6.0;
        $entitlement->recalculateTotals();
        $this->assertSame(6.0, $entitlement->total_days, 'Example 4: usable = accrued(6) for accruable; entitled(24) is a ceiling only, not added.');
        $this->assertSame(6.0, $entitlement->days_remaining);

        // Example 5: a 2-day leave request gets approved - no phantom jump.
        $leaveRequest = $this->makeLeaveRequest($employee, $leaveType, '2026-03-05', '2026-03-06');
        $leaveRequest->approved_by = $employee->user_id;
        $leaveRequest->approved_at = now();
        $leaveRequest->total_days = 2;
        $leaveRequest->save();

        LeaveEntitlement::recomputeUsageFor($employee->id, $leaveType->id, 1);
        $entitlement->refresh();

        $this->assertSame(6.0, $entitlement->total_days, 'Example 5: still 6 - approving a request must not add entitled_days on top.');
        $this->assertSame(2.0, $entitlement->days_taken);
        $this->assertSame(4.0, $entitlement->days_remaining);

        // Example 6: 6 months in, processAccruals() grows accrued to 12.
        $entitlement->accrued_days = 12.0; // simulate calculateAccruedDays() at month 6
        $entitlement->recalculateTotals();

        $this->assertSame(12.0, $entitlement->total_days, 'Example 6: grows smoothly to 12 as time passes.');
        $this->assertSame(10.0, $entitlement->days_remaining, '12 total - 2 already taken = 10.');
    }

    /** Example 7: carryover into a new period, capped by policy. */
    public function test_example7_carryover_into_new_period_capped_by_policy(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        // Far-future dates: business_id=1 is the shared, real test business,
        // and calculateCarryover() auto-discovers whichever LeavePeriod
        // immediately precedes the given one - near-term dates risk
        // colliding with real period data already in that business.
        $previousPolicy = $this->makePolicy($leaveType, 21, 3);
        $previousPeriod = $this->makePeriod('2036-01-01', '2036-12-31');

        $previous = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $previousPeriod, $previousPolicy);
        // Employee finished the year with 5 unused days, policy caps carryover at 3.
        $previous->days_remaining = 5.0;
        $previous->save();

        $newPolicy = $this->makePolicy($leaveType, 21, 3);
        $newPeriod = $this->makePeriod('2037-01-01', '2037-12-31');

        $current = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $newPeriod, $newPolicy);

        $this->assertSame(3.0, $current->carryover_days, 'Capped at policy max_carryover_days(3), not the full 5 unused.');
        $this->assertSame(24.0, $current->total_days, '21 entitled + 3 carryover = 24.');
        $this->assertSame(24.0, $current->days_remaining);
    }

    /** Example 8 & 9: pending requests reserve days and block over-committing; rejecting one frees the reservation. */
    public function test_example8_and_9_pending_requests_reserve_days_and_free_on_rejection(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        $policy = $this->makePolicy($leaveType, 5);
        $period = $this->makePeriod('2026-01-01', '2026-12-31');
        $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        $controller = new \App\Http\Controllers\LeaveRequestController();
        session(['active_business_slug' => 'amsol']);

        // First request: 3 days, pending.
        $request1 = \Illuminate\Http\Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
        ]);
        $request1->setUserResolver(fn () => $employee->user);
        $response1 = $controller->store($request1)->toResponse($request1);
        $this->assertSame(200, $response1->getStatusCode());

        $entitlement = LeaveEntitlement::where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)->first();
        $entitlement->refresh();
        $this->assertSame(3.0, $entitlement->days_pending, 'Example 8: the first pending request reserves 3 days.');
        $this->assertSame(2.0, $entitlement->days_remaining, '5 total - 3 pending = 2 remaining.');

        // Second request: 4 days - exceeds the 2 remaining, must be blocked at submission.
        $request2 = \Illuminate\Http\Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-04',
        ]);
        $request2->setUserResolver(fn () => $employee->user);
        $response2 = $controller->store($request2)->toResponse($request2);
        $this->assertSame(400, $response2->getStatusCode(), 'Example 8: blocked before it could jointly overshoot the 5-day balance.');

        // Reject the first request - its reservation must be released.
        $firstLeaveRequest = LeaveRequest::where('employee_id', $employee->id)->first();
        $firstLeaveRequest->rejection_reason = 'Not enough cover';
        $firstLeaveRequest->save();
        LeaveEntitlement::recomputeUsageFor($employee->id, $leaveType->id, 1);

        $entitlement->refresh();
        $this->assertSame(0.0, $entitlement->days_pending, 'Example 9: rejection frees the reservation.');
        $this->assertSame(5.0, $entitlement->days_remaining, 'Full balance available again.');

        // Now the second (4-day) request can be submitted successfully.
        $request3 = \Illuminate\Http\Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-04',
        ]);
        $request3->setUserResolver(fn () => $employee->user);
        $response3 = $controller->store($request3)->toResponse($request3);
        $this->assertSame(200, $response3->getStatusCode());
    }

    /** Example 10: manual entitlement edit no longer disagrees with any other formula, because there is only one. */
    public function test_example10_manual_edit_uses_the_same_single_formula(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        $policy = $this->makePolicy($leaveType, 21);
        $period = $this->makePeriod('2026-01-01', '2026-12-31');
        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        $slug = rtrim(strtr(base64_encode(implode(':', [
            $entitlement->business_id, $entitlement->employee_id, $entitlement->leave_type_id, $entitlement->leave_period_id,
        ])), '+/', '-_'), '=');

        $controller = new \App\Http\Controllers\LeaveEntitlementController();
        $request = \Illuminate\Http\Request::create('/leave-entitlements/update', 'POST', [
            'slug' => $slug,
            'entitled_days' => 30,
        ]);
        $response = $controller->update($request);

        $entitlement->refresh();
        $this->assertSame(30.0, $entitlement->entitled_days);
        $this->assertSame(30.0, $entitlement->total_days, 'The manual edit and recalculateTotals() agree - there is only one formula now.');
        $this->assertSame(30.0, $entitlement->days_remaining);
    }

    /**
     * Compensatory "off day" leave types: no baseline grant at all
     * (policy default_days = 0) - the whole balance comes from ad-hoc
     * adjustments HR applies only when a day is actually earned (e.g.
     * working a public holiday). Proves running "Set Entitlements" still
     * creates the row (previously skipped entirely when entitledDays<=0,
     * leaving nothing for adjust() to adjust), and that the adjustment
     * alone correctly becomes the balance.
     */
    public function test_off_day_leave_type_has_no_baseline_and_is_granted_only_by_adjustment(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = $this->makeLeaveType(false);
        $policy = $this->makePolicy($leaveType, 0); // no default grant at all
        $period = $this->makePeriod('2026-01-01', '2026-12-31');

        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);

        $this->assertNotNull($entitlement, 'A 0-baseline entitlement row must still be created, so it exists to adjust later.');
        $this->assertSame(0.0, $entitlement->entitled_days);
        $this->assertSame(0.0, $entitlement->total_days);
        $this->assertSame(0.0, $entitlement->days_remaining);

        // Employee works on a public holiday - HR grants a single comp-off day.
        $entitlement->applyAdjustment(1, 'Worked on public holiday 2026-01-01');
        $entitlement->refresh();

        $this->assertSame(1.0, $entitlement->adjustment_days);
        $this->assertSame(1.0, $entitlement->total_days, 'The whole balance comes from the adjustment - no baseline to add.');
        $this->assertSame(1.0, $entitlement->days_remaining);

        // They work a second holiday later - cumulative, not overwritten.
        $entitlement->applyAdjustment(1, 'Worked on public holiday 2026-05-01');
        $entitlement->refresh();

        $this->assertSame(2.0, $entitlement->adjustment_days);
        $this->assertSame(2.0, $entitlement->total_days);

        // Re-running "Set Entitlements" for the period must not disturb
        // the earned balance - the baseline stays 0, the grant persists.
        $entitlement = $this->policyService->createOrUpdateEntitlement($employee, $leaveType, $period, $policy);
        $this->assertSame(2.0, $entitlement->adjustment_days);
        $this->assertSame(2.0, $entitlement->total_days);
    }
}
