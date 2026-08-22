<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveRequestController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end regression coverage for the leave lifecycle: application ->
 * approval -> entitlement deduction. Written after discovering
 * `deductLeaveEntitlementSafely()` looked up the entitlement without
 * scoping by leave period - with more than one period on file for the
 * same leave type, it could silently deduct against (or recompute) the
 * wrong period's row.
 */
class LeaveApplicationLifecycleTest extends TestCase
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

        // Pin non_working_days for this test's duration (rolled back after)
        // so hardcoded weekday-count assertions aren't at the mercy of
        // whatever real config is currently set on the live business.
        Business::find(1)->update(['non_working_days' => []]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployeeUser(?int $managerId = null): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'manager_id' => $managerId,
            'employee_code' => 'LIFE-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh()];
    }

    public function test_full_application_and_approval_deducts_entitlement_correctly(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Lifecycle Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Lifecycle Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07', // Monday
            'end_date' => '2026-09-09',   // Wednesday - 3 weekdays
        ]);
        $storeRequest->setUserResolver(fn () => $requesterUser);
        $storeResponse = $controller->store($storeRequest)->toResponse($storeRequest);
        $storePayload = json_decode($storeResponse->getContent(), true);
        $this->assertSame(200, $storeResponse->getStatusCode(), $storePayload['message'] ?? 'unknown error');

        $leaveRequest = LeaveRequest::where('employee_id', $requesterEmployee->id)->first();
        $this->assertNotNull($leaveRequest);
        $this->assertSame(3.0, $leaveRequest->total_days);
        $this->assertSame('pending', $leaveRequest->status);

        // days_taken is untouched until approval, but the pending request
        // now reserves its days against days_remaining - two simultaneous
        // pending requests can no longer jointly overshoot the balance
        // undetected (see EntitlementUnifiedFormulaTest for the full
        // scenario this guards against).
        $this->assertSame(0.0, $entitlement->fresh()->days_taken);
        $this->assertSame(3.0, $entitlement->fresh()->days_pending);
        $this->assertSame(18.0, $entitlement->fresh()->days_remaining);

        // The requester's real manager (organogram) approves.
        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $statusRequest = Request::create('/leave/status', 'POST', [
            'reference_number' => $leaveRequest->reference_number,
            'status' => 'approved',
        ]);
        $statusRequest->setUserResolver(fn () => $managerUser);
        $statusResponse = $controller->status($statusRequest)->toResponse($statusRequest);
        $statusPayload = json_decode($statusResponse->getContent(), true);
        $this->assertSame(200, $statusResponse->getStatusCode(), $statusPayload['message'] ?? 'unknown error');

        $leaveRequest->refresh();
        $this->assertSame('approved', $leaveRequest->status);
        $this->assertNotNull($leaveRequest->approved_by);

        $entitlement->refresh();
        $this->assertSame(3.0, $entitlement->days_taken);
        $this->assertSame(18.0, $entitlement->days_remaining);
    }

    public function test_deduction_only_touches_the_entitlement_for_the_covering_period(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Multi-Period Leave ' . uniqid(),
        ]);

        // Two adjacent periods, same leave type, both with entitlement rows.
        $period2025 = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Lifecycle 2025 ' . uniqid(),
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
        ]);
        $period2026 = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Lifecycle 2026 ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $entitlement2025 = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
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
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period2026->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        // A leave request that falls inside the 2026 period only.
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-14', // Monday
            'end_date' => '2026-09-15',   // Tuesday - 2 weekdays
        ]);
        $storeRequest->setUserResolver(fn () => $requesterUser);
        $controller->store($storeRequest)->toResponse($storeRequest);

        $leaveRequest = LeaveRequest::where('employee_id', $requesterEmployee->id)
            ->where('start_date', '2026-09-14')->first();
        $this->assertNotNull($leaveRequest);

        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $statusRequest = Request::create('/leave/status', 'POST', [
            'reference_number' => $leaveRequest->reference_number,
            'status' => 'approved',
        ]);
        $statusRequest->setUserResolver(fn () => $managerUser);
        $controller->status($statusRequest)->toResponse($statusRequest);

        // Only the 2026 entitlement should reflect the deduction; 2025 is untouched.
        $this->assertSame(2.0, $entitlement2026->fresh()->days_taken);
        $this->assertSame(19.0, $entitlement2026->fresh()->days_remaining);
        $this->assertSame(0.0, $entitlement2025->fresh()->days_taken);
        $this->assertSame(21.0, $entitlement2025->fresh()->days_remaining);
    }

    public function test_application_is_rejected_when_it_exceeds_remaining_entitlement(): void
    {
        [, $requesterEmployee] = $this->makeEmployeeUser();
        $requesterUser = $requesterEmployee->user;
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Insufficient Balance Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Insufficient Balance Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 1,
            'accrued_days' => 0,
            'total_days' => 1,
            'days_taken' => 0,
            'days_remaining' => 1,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-21', // Monday
            'end_date' => '2026-09-23',   // Wednesday - 3 weekdays, only 1 available
        ]);
        $storeRequest->setUserResolver(fn () => $requesterUser);
        $response = $controller->store($storeRequest)->toResponse($storeRequest);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, LeaveRequest::where('employee_id', $requesterEmployee->id)->count());
    }

    public function test_rejecting_a_leave_request_does_not_touch_entitlement(): void
    {
        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Rejection Leave ' . uniqid(),
        ]);

        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Rejection Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-28',
            'end_date' => '2026-09-29',
        ]);
        $storeRequest->setUserResolver(fn () => $requesterUser);
        $controller->store($storeRequest)->toResponse($storeRequest);

        $leaveRequest = LeaveRequest::where('employee_id', $requesterEmployee->id)->first();

        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $statusRequest = Request::create('/leave/status', 'POST', [
            'reference_number' => $leaveRequest->reference_number,
            'status' => 'rejected',
            'rejection_reason' => 'Not enough coverage that week.',
        ]);
        $statusRequest->setUserResolver(fn () => $managerUser);
        $response = $controller->status($statusRequest)->toResponse($statusRequest);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('rejected', $leaveRequest->fresh()->status);
        $this->assertSame(0.0, $entitlement->fresh()->days_taken);
        $this->assertSame(21.0, $entitlement->fresh()->days_remaining);
    }
}
