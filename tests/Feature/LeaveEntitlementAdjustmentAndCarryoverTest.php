<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveEntitlementController;
use App\Http\Controllers\LeaveRequestController;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for two leave-entitlement features that were
 * previously non-functional: adjustment (HR corrections with a required
 * reason, additive to entitled_days rather than a blunt overwrite) and
 * carryover (rolling an unused balance from one period into the next,
 * via LeavePolicyService::computeCarryover() which existed but was never
 * actually called anywhere).
 */
class LeaveEntitlementAdjustmentAndCarryoverTest extends TestCase
{
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

        \App\Models\Business::find(1)->update(['non_working_days' => []]);
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
            'employee_code' => 'ADJC-' . uniqid(),
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

    private function makeEntitlementSlug(LeaveEntitlement $entitlement): string
    {
        $raw = "{$entitlement->business_id}:{$entitlement->employee_id}:{$entitlement->leave_type_id}:{$entitlement->leave_period_id}";
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function test_adjustment_grants_extra_days_with_a_reason(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Adjustment Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Adjustment Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/adjust', 'POST', [
            'slug' => $this->makeEntitlementSlug($entitlement),
            'adjustment_days' => 3,
            'reason' => 'Goodwill grant for extra project work.',
        ]);
        $response = $controller->adjust($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $entitlement->refresh();
        $this->assertSame(3.0, $entitlement->adjustment_days);
        $this->assertSame('Goodwill grant for extra project work.', $entitlement->adjustment_reason);
        $this->assertSame(24.0, $entitlement->total_days);
        $this->assertSame(24.0, $entitlement->days_remaining);
    }

    public function test_adjustment_can_claw_back_days_and_is_cumulative(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Clawback Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Clawback Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $slug = $this->makeEntitlementSlug($entitlement);

        $first = Request::create('/leave-entitlements/adjust', 'POST', [
            'slug' => $slug, 'adjustment_days' => 5, 'reason' => 'First correction.',
        ]);
        $controller->adjust($first)->toResponse($first);

        $second = Request::create('/leave-entitlements/adjust', 'POST', [
            'slug' => $slug, 'adjustment_days' => -2, 'reason' => 'Partial clawback.',
        ]);
        $controller->adjust($second)->toResponse($second);

        $entitlement->refresh();
        $this->assertSame(3.0, $entitlement->adjustment_days); // 5 - 2, cumulative
        $this->assertSame('Partial clawback.', $entitlement->adjustment_reason);
        $this->assertSame(24.0, $entitlement->total_days); // 21 + 3
    }

    public function test_adjustment_requires_a_reason(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'No Reason Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'No Reason Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/adjust', 'POST', [
            'slug' => $this->makeEntitlementSlug($entitlement),
            'adjustment_days' => 3,
            // reason intentionally omitted
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->adjust($request);
    }

    public function test_carryover_rolls_remaining_balance_into_the_next_period_capped_by_policy(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Carryover Leave ' . uniqid()]);

        $fromPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Carryover From ' . uniqid(),
            'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => true,
        ]);
        $toPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Carryover To ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'department_id' => null,
            'job_category_id' => null,
            'gender_applicable' => 'all',
            'prorated_for_new_employees' => false,
            'default_days' => 21,
            'accrual_frequency' => 'yearly',
            'accrual_amount' => 0,
            'max_carryover_days' => 5,
            'minimum_service_days_required' => 0,
            'effective_date' => '2020-01-01',
            'is_active' => true,
        ]);

        // 10 days remaining in the "from" period, but policy caps carryover at 5.
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $fromPeriod->id, 'entitled_days' => 10, 'accrued_days' => 0,
            'total_days' => 10, 'days_taken' => 0, 'days_remaining' => 10,
        ]);
        $destination = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $toPeriod->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/process-carryover', 'POST', [
            'from_period_id' => $fromPeriod->id,
            'to_period_id' => $toPeriod->id,
        ]);
        $response = $controller->processCarryover($request, app(\App\Services\LeavePolicyService::class))->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $payload['data']['carried']);

        $destination->refresh();
        $this->assertSame(5.0, $destination->carryover_days); // capped, not the full 10
        $this->assertSame(26.0, $destination->total_days); // 21 + 5
    }

    /**
     * Carrying over shouldn't require HR to have already run "Set
     * Entitlements" for the destination period first - if a policy exists
     * but no entitlement row does yet, processCarryover() now creates the
     * baseline entitlement (like autoEntitleAll() would) and applies the
     * carryover to it, instead of skipping the employee entirely.
     */
    public function test_carryover_creates_the_destination_entitlement_when_it_does_not_exist_yet(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Carryover No Dest Leave ' . uniqid()]);

        $fromPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'No Dest From ' . uniqid(),
            'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => true,
        ]);
        $toPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'No Dest To ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        LeavePolicy::create([
            'leave_type_id' => $leaveType->id,
            'department_id' => null,
            'job_category_id' => null,
            'gender_applicable' => 'all',
            'prorated_for_new_employees' => false,
            'default_days' => 21,
            'accrual_frequency' => 'yearly',
            'accrual_amount' => 0,
            'max_carryover_days' => 5,
            'minimum_service_days_required' => 0,
            'effective_date' => '2020-01-01',
            'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $fromPeriod->id, 'entitled_days' => 10, 'accrued_days' => 0,
            'total_days' => 10, 'days_taken' => 0, 'days_remaining' => 10,
        ]);
        // Deliberately no destination entitlement created for $toPeriod.

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/process-carryover', 'POST', [
            'from_period_id' => $fromPeriod->id,
            'to_period_id' => $toPeriod->id,
        ]);
        $response = $controller->processCarryover($request, app(\App\Services\LeavePolicyService::class))->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $payload['data']['carried']);
        $this->assertEmpty($payload['data']['skipped_no_destination']);

        $destination = LeaveEntitlement::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('leave_period_id', $toPeriod->id)
            ->first();
        $this->assertNotNull($destination);
        $this->assertSame(5.0, $destination->carryover_days); // capped by max_carryover_days
    }

    public function test_carryover_skips_employees_with_no_applicable_policy_at_all(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Carryover No Policy Leave ' . uniqid()]);

        $fromPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'No Policy From ' . uniqid(),
            'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'is_active' => true,
        ]);
        $toPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'No Policy To ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $fromPeriod->id, 'entitled_days' => 10, 'accrued_days' => 0,
            'total_days' => 10, 'days_taken' => 0, 'days_remaining' => 10,
        ]);
        // No LeavePolicy at all for this leave type.

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/process-carryover', 'POST', [
            'from_period_id' => $fromPeriod->id,
            'to_period_id' => $toPeriod->id,
        ]);
        $response = $controller->processCarryover($request, app(\App\Services\LeavePolicyService::class))->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $payload['data']['carried']);
        $this->assertTrue(in_array($employee->id, $payload['data']['skipped_no_policy'], true));
    }

    // ---- Carryover type/value/expiry --------------------------------------

    public function test_computeCarryover_with_fixed_type_carries_the_flat_value_capped_by_max(): void
    {
        // computeCarryover() is the same shared applyCarryoverTypeAndCap()
        // helper calculateCarryover() uses, without needing a real
        // adjacent-period lookup (which auto-discovers "whichever period
        // immediately precedes this one" against the whole shared test
        // business - too fragile to depend on here since this test only
        // cares about the type/value math itself, not period discovery).
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Fixed Carryover Leave ' . uniqid()]);
        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 8, 'carryover_type' => 'fixed', 'carryover_value' => 4,
            'minimum_service_days_required' => 0, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        $carryover = app(LeavePolicyService::class)->computeCarryover($policy, 20.0);

        // Fixed value (4), well under both unused (20) and cap (8).
        $this->assertSame(4.0, $carryover);
    }

    public function test_computeCarryover_with_percent_type_carries_the_right_percentage(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Percent Carryover Leave ' . uniqid()]);
        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 50, 'carryover_type' => 'percent', 'carryover_value' => 50,
            'minimum_service_days_required' => 0, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        $carryover = app(LeavePolicyService::class)->computeCarryover($policy, 20.0);

        // 50% of 20 unused = 10, well under the 50-day cap.
        $this->assertSame(10.0, $carryover);
    }

    public function test_createOrUpdateEntitlement_computes_carryover_expiry_date_from_policy(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Expiry Carryover Leave ' . uniqid()]);

        // Far-future dates (not 2025/2026 like the other tests in this file)
        // so calculateCarryover()'s "immediately preceding period" auto-
        // discovery - which queries every LeavePeriod on the shared test
        // business, not just the two created here - can't accidentally
        // match some other real period instead of $fromPeriod.
        $fromPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Expiry From ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ]);
        $toPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Expiry To ' . uniqid(),
            'start_date' => '2031-01-01', 'end_date' => '2031-12-31', 'is_active' => true,
        ]);

        $policy = LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 10, 'carryover_type' => 'full', 'carryover_expiry_months' => 3,
            'minimum_service_days_required' => 0, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $fromPeriod->id, 'entitled_days' => 20, 'accrued_days' => 0,
            'total_days' => 20, 'days_taken' => 0, 'days_remaining' => 5,
        ]);

        $entitlement = app(LeavePolicyService::class)->createOrUpdateEntitlement($employee, $leaveType, $toPeriod, $policy);

        $this->assertNotNull($entitlement);
        $this->assertSame(5.0, $entitlement->carryover_days);
        $this->assertSame('2031-04-01', $entitlement->carryover_expiry_date->toDateString());
    }

    public function test_forfeitExpiredCarryover_claws_back_unused_carryover_via_adjustment(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Forfeit Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Forfeit Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        // 6 days carried over, only 2 used so far, expiry date already past.
        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'carryover_days' => 6, 'carryover_expiry_date' => '2026-03-31',
            'total_days' => 27, 'days_taken' => 2, 'days_remaining' => 25,
        ]);

        $forfeitedCount = app(LeavePolicyService::class)->forfeitExpiredCarryover(\Carbon\Carbon::parse('2026-04-01'));

        $this->assertSame(1, $forfeitedCount);
        $entitlement->refresh();
        $this->assertSame(-4.0, $entitlement->adjustment_days); // 6 carried - 2 used = 4 forfeited
        $this->assertSame('Carryover expired', $entitlement->adjustment_reason);
        $this->assertNull($entitlement->carryover_expiry_date);
    }

    public function test_forfeitExpiredCarryover_does_not_touch_entitlements_not_yet_expired(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Not Yet Expired Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Not Yet Expired Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'carryover_days' => 6, 'carryover_expiry_date' => '2026-12-31',
            'total_days' => 27, 'days_taken' => 0, 'days_remaining' => 27,
        ]);

        $forfeitedCount = app(LeavePolicyService::class)->forfeitExpiredCarryover(\Carbon\Carbon::parse('2026-04-01'));

        $this->assertSame(0, $forfeitedCount);
        $this->assertSame(0.0, (float) $entitlement->fresh()->adjustment_days);
    }

    // ---- Interval between consecutive requests ----------------------------

    public function test_leave_request_within_the_interval_window_is_rejected(): void
    {
        $employee = $this->makeEmployee();
        $business = Business::find(1);
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Interval Leave ' . uniqid(), 'allows_backdating' => true]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Interval Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 0, 'minimum_service_days_required' => 0,
            'min_interval_days' => 10, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);
        LeaveRequest::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-01', 'end_date' => '2026-06-03', 'total_days' => 3,
            'approved_by' => null, 'reason' => 'Prior request', 'reference_number' => 'REF-' . uniqid(),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        // Only 5 days after the prior request's end date - inside the 10-day cooldown.
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08', 'end_date' => '2026-06-09',
        ]);
        $storeRequest->setUserResolver(fn () => $employee->user);
        $response = $controller->store($storeRequest)->toResponse($storeRequest);

        $this->assertSame(400, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertStringContainsString('wait until', $payload['message']);
    }

    public function test_leave_request_outside_the_interval_window_is_allowed(): void
    {
        $employee = $this->makeEmployee();
        $business = Business::find(1);
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Interval OK Leave ' . uniqid(), 'allows_backdating' => true]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Interval OK Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'gender_applicable' => 'all',
            'prorated_for_new_employees' => false, 'default_days' => 21,
            'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 0, 'minimum_service_days_required' => 0,
            'min_interval_days' => 10, 'effective_date' => '2020-01-01', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);
        LeaveRequest::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-01', 'end_date' => '2026-06-03', 'total_days' => 3,
            'approved_by' => null, 'reason' => 'Prior request', 'reference_number' => 'REF-' . uniqid(),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        // 15 days after the prior request's end date - outside the 10-day cooldown.
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-18', 'end_date' => '2026-06-19',
        ]);
        $storeRequest->setUserResolver(fn () => $employee->user);
        $response = $controller->store($storeRequest)->toResponse($storeRequest);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
    }
}
