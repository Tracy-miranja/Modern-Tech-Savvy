<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeavePolicyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the entitlement bugs fixed in this pass:
 *  - minimum-service-days gate used a dead optional() fallback + unsigned diff
 *  - getRemainingDays()/entitlement lookups mixed usage across leave periods
 *  - carryover_days (the live replacement for the broken carry_forward column)
 *    was not wired into total_days calculations
 */
class LeaveEntitlementBugFixesTest extends TestCase
{
    /**
     * Deliberately targets the real 'mysql' (hrmamsol) connection, scoped to a
     * manually-managed transaction that's always rolled back, instead of the
     * sqlite default used by the rest of the suite - this test exercises real
     * seeded data (business/department/job_category id 1) that only exists
     * there. Managed manually (not via the DatabaseTransactions trait) because
     * that trait opens its transaction during parent::setUp(), before this
     * class's own connection override would otherwise get a chance to apply.
     *
     * .env.testing points the shared DB_DATABASE var at sqlite ':memory:' (see
     * the note in that file) - that var is read by BOTH the mysql and sqlite
     * connection configs, so the mysql connection is re-declared explicitly
     * below instead of relying on env() for this one test class.
     */
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

        Business::find(1)->update(['non_working_days' => []]);
        // Real holidays already on file for amsol can land inside a test's
        // date range and shift total_days/remaining-days off the numbers
        // this suite hardcodes.
        \App\Models\Holiday::where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployee(Carbon $employmentDate, array $overrides = []): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'T-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ], $overrides));

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => 1,
            'job_category_id' => 1,
            'employment_date' => $employmentDate->toDateString(),
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    public function test_minimum_service_gate_blocks_employee_hired_after_period_start(): void
    {
        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Gate Test Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Gate Test Leave ' . uniqid(),
        ]);

        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'default_days' => 21,
            'accrual_frequency' => 'monthly',
            'accrual_amount' => 1.75,
            'effective_date' => '2025-01-01',
            'minimum_service_days_required' => 30,
            'is_active' => true,
        ]);

        // Hired 10 days AFTER the period starts -> must fail the gate (0 days).
        $newHire = $this->makeEmployee(Carbon::parse('2026-01-11'));
        // Hired 5 years before the period starts -> must pass the gate.
        $tenured = $this->makeEmployee(Carbon::parse('2021-01-01'));

        $service = app(LeavePolicyService::class);

        $this->assertSame(0.0, $service->computeEntitledDays($policy, $newHire, $period));
        $this->assertSame(21.0, $service->computeEntitledDays($policy, $tenured, $period));
    }

    public function test_computed_entitled_days_uses_employment_date_not_missing_hired_at(): void
    {
        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'HiredAt Test Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'HiredAt Test Leave ' . uniqid(),
        ]);

        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'default_days' => 21,
            'accrual_frequency' => 'monthly',
            'accrual_amount' => 1.75,
            'effective_date' => '2025-01-01',
            'minimum_service_days_required' => 30,
            'is_active' => true,
        ]);

        // Employee::hired_at does not exist as a column; this used to always
        // resolve to Optional(null) and silently zero out entitlements.
        $employee = $this->makeEmployee(Carbon::parse('2020-01-01'));

        $service = app(LeavePolicyService::class);
        $entitled = $service->computeEntitledDays($policy, $employee, $period);

        $this->assertSame(21.0, $entitled);
    }

    public function test_prorated_entitlement_computes_a_partial_amount_for_a_mid_period_hire(): void
    {
        // Regression: computeEntitledDays() referenced $employmentDate,
        // $periodStart and $periodEnd inside the prorated_for_new_employees
        // branch, but never defined any of them (only $hiredAt and
        // $defaultDays exist in this method) - so turning proration on threw
        // "Undefined variable" on every entitlement, while leaving it off
        // skipped the branch entirely and worked fine.
        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Prorated Test Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Prorated Test Leave ' . uniqid(),
        ]);

        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'default_days' => 24,
            'accrual_amount' => 0,
            'prorated_for_new_employees' => true,
            'effective_date' => '2025-01-01',
            'is_active' => true,
        ]);

        // Hired exactly halfway through the period (day 183 of 365) -> ~12 days.
        $employee = $this->makeEmployee(Carbon::parse('2026-07-02'));

        $service = app(LeavePolicyService::class);
        $entitled = $service->computeEntitledDays($policy, $employee, $period);

        $this->assertGreaterThan(0.0, $entitled);
        $this->assertLessThan(24.0, $entitled);
    }

    public function test_get_remaining_days_does_not_leak_usage_across_leave_periods(): void
    {
        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Period Scope Leave ' . uniqid(),
        ]);

        $period2025 = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'FY2025 ' . uniqid(),
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);

        $period2026 = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'FY2026 ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee(Carbon::parse('2020-01-01'));
        $approver = User::factory()->create();

        $entitlement2025 = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period2025->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        $entitlement2026 = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period2026->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        // 5 weekdays taken in 2025 (Mon-Fri).
        $req2025 = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2025-06-02',
            'end_date' => '2025-06-06',
            'current_approval_level' => 1,
            'approved_by' => $approver->id,
        ]);

        // 3 weekdays taken in 2026 (Mon-Wed).
        $req2026 = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'current_approval_level' => 1,
            'approved_by' => $approver->id,
        ]);

        $this->assertSame(5.0, $req2025->fresh()->total_days);
        $this->assertSame(3.0, $req2026->fresh()->total_days);

        $remaining2025 = $entitlement2025->getRemainingDays();
        $remaining2026 = $entitlement2026->getRemainingDays();

        // Before the fix, both periods would sum ALL approved usage (5 + 3 = 8),
        // understating whichever period's balance was checked second.
        $this->assertSame(5.0, $entitlement2025->fresh()->days_taken);
        $this->assertSame(16.0, $remaining2025);

        $this->assertSame(3.0, $entitlement2026->fresh()->days_taken);
        $this->assertSame(18.0, $remaining2026);
    }

    public function test_carryover_days_are_included_in_total_and_remaining(): void
    {
        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Carryover Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Carryover Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $employee = $this->makeEmployee(Carbon::parse('2020-01-01'));

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 2,
            'carryover_days' => 5,
            'total_days' => 0,
            'days_taken' => 0,
            'days_remaining' => 0,
        ]);

        $entitlement->calculateRemainingDays();

        $this->assertSame(28.0, $entitlement->fresh()->total_days);
        $this->assertSame(28.0, $entitlement->fresh()->days_remaining);

        // applyPolicyNumbers() must also persist carryover_days onto the model.
        $entitlement->applyPolicyNumbers(entitled: 21, carryover: 5, accrued: 2);
        $this->assertSame(5.0, $entitlement->carryover_days);
        $this->assertSame(28.0, $entitlement->total_days);
    }
}
