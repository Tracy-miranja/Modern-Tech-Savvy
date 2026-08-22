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
 * Leave Cancellation - a full-year-away request can be withdrawn (tracked via
 * cancelled_at, not hard-deleted like destroy()) by its owner or an HR-tier
 * role, distinct from revoke() (shortening an already-started approved leave).
 */
class LeaveCancellationTest extends TestCase
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
            'user_id' => $user->id, 'business_id' => 1, 'department_id' => 1,
            'employee_code' => 'CXL-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();

        EmploymentDetail::create([
            'employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1,
            'employment_date' => '2020-01-01', 'employment_term' => 'permanent',
        ]);

        return $employee;
    }

    private function makeRequest(Employee $employee, LeaveType $leaveType, string $start, string $end, bool $approved = false): LeaveRequest
    {
        $data = [
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'current_approval_level' => 0,
        ];

        if ($approved) {
            $data['approved_by'] = User::factory()->create()->id;
            $data['approved_at'] = now();
        }

        return LeaveRequest::create($data);
    }

    public function test_owner_can_cancel_a_future_pending_request_and_status_becomes_cancelled(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel Owner Leave ' . uniqid()]);
        $leave = $this->makeRequest($employee, $leaveType, '2030-06-01', '2030-06-03');

        session(['active_business_slug' => Business::find(1)->slug, 'active_role' => 'business-employee']);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/cancel', 'POST', [
            'reference_number' => $leave->reference_number,
            'reason' => 'Changed my mind',
        ]);
        $request->setUserResolver(fn () => $employee->user);
        $response = $controller->cancel($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $leave->refresh();
        $this->assertSame('cancelled', $leave->status);
        $this->assertNotNull($leave->cancelled_at);
        $this->assertSame($employee->user->id, $leave->cancelled_by);
        $this->assertSame('Changed my mind', $leave->cancellation_reason);
    }

    public function test_cancelling_an_approved_request_restores_the_entitlement_balance(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel Restore Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Cancel Restore Period ' . uniqid(),
            'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0, 'total_days' => 21,
            'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $leave = $this->makeRequest($employee, $leaveType, '2030-07-01', '2030-07-03', approved: true);

        LeaveEntitlement::recomputeUsageFor($employee->id, $leaveType->id, 1);
        $entitlement = LeaveEntitlement::where('employee_id', $employee->id)->where('leave_type_id', $leaveType->id)->first();
        $this->assertGreaterThan(0, $entitlement->days_taken, 'Sanity check: approved leave should count as usage before cancellation.');

        session(['active_business_slug' => Business::find(1)->slug, 'active_role' => 'business-employee']);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/cancel', 'POST', ['reference_number' => $leave->reference_number]);
        $request->setUserResolver(fn () => $employee->user);
        $response = $controller->cancel($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());

        $entitlement->refresh();
        $this->assertSame(0.0, (float) $entitlement->days_taken, 'Cancelling should release the days back to the balance.');
    }

    public function test_owner_cannot_cancel_a_request_that_has_already_started(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel Started Leave ' . uniqid(), 'allows_backdating' => true]);
        $leave = $this->makeRequest($employee, $leaveType, now()->subDay()->toDateString(), now()->addDay()->toDateString(), approved: true);

        session(['active_business_slug' => Business::find(1)->slug, 'active_role' => 'business-employee']);
        $this->actingAs($employee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/cancel', 'POST', ['reference_number' => $leave->reference_number]);
        $request->setUserResolver(fn () => $employee->user);
        $response = $controller->cancel($request);

        $this->assertSame(403, $response->getStatusCode());
        $leave->refresh();
        $this->assertNull($leave->cancelled_at);
    }

    public function test_unrelated_employee_cannot_cancel_someone_elses_request(): void
    {
        $owner = $this->makeEmployee();
        $stranger = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel Stranger Leave ' . uniqid()]);
        $leave = $this->makeRequest($owner, $leaveType, '2030-08-01', '2030-08-03');

        session(['active_business_slug' => Business::find(1)->slug, 'active_role' => 'business-employee']);
        $this->actingAs($stranger->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/cancel', 'POST', ['reference_number' => $leave->reference_number]);
        $request->setUserResolver(fn () => $stranger->user);
        $response = $controller->cancel($request);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_business_hr_can_cancel_a_future_request_on_behalf_of_an_employee(): void
    {
        $employee = $this->makeEmployee();
        $hrEmployee = $this->makeEmployee(); // canUserCancel requires an activeEmployee() for non-admin roles, same as canUserRevoke()
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel HR Leave ' . uniqid()]);
        $leave = $this->makeRequest($employee, $leaveType, '2030-09-01', '2030-09-03');

        session(['active_business_slug' => Business::find(1)->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrEmployee->user);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/cancel', 'POST', ['reference_number' => $leave->reference_number]);
        $request->setUserResolver(fn () => $hrEmployee->user);
        $response = $controller->cancel($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertSame('cancelled', $leave->fresh()->status);
    }

    public function test_cancelled_requests_are_excluded_from_pending_and_approved_scopes(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Cancel Scope Leave ' . uniqid()]);
        $leave = $this->makeRequest($employee, $leaveType, '2030-10-01', '2030-10-03');
        $leave->cancel('Test cancel', $employee->user);

        $this->assertFalse(LeaveRequest::where('id', $leave->id)->status('pending')->exists());
        $this->assertTrue(LeaveRequest::where('id', $leave->id)->status('cancelled')->exists());
    }
}
