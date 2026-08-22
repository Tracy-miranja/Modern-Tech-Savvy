<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveDelegationController;
use App\Http\Controllers\LeaveRequestController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveDelegation;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the reliever/delegation feature: submitting a leave
 * request with a reliever creates a LeaveDelegation and notifies them (mail +
 * portal), and the reliever can accept/decline from their own portal.
 */
class LeaveDelegationTest extends TestCase
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

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'DEL-' . uniqid(),
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

    public function test_submitting_a_leave_request_with_a_reliever_creates_a_delegation_and_notifies_them(): void
    {
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser();
        [$relieverUser, $relieverEmployee] = $this->makeEmployeeUser();

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Delegation Test Leave ' . uniqid(),
        ]);

        $period = \App\Models\LeavePeriod::create([
            'business_id' => 1,
            'name' => 'Delegation Test Period ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);

        \App\Models\LeaveEntitlement::create([
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

        session(['active_business_slug' => Business::find(1)->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $request = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Testing reliever flow',
            'handover_notes' => 'Please keep an eye on the Q4 report.',
            'reliever_employee_id' => $relieverEmployee->id,
        ]);
        $request->setUserResolver(fn () => $requesterUser);

        $response = $controller->store($request)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $payload['message'] ?? 'unknown error');

        $delegation = LeaveDelegation::where('delegate_id', $relieverEmployee->id)
            ->where('employee_id', $requesterEmployee->id)
            ->first();

        $this->assertNotNull($delegation);
        $this->assertSame('Please keep an eye on the Q4 report.', $delegation->duties_delegated);
        $this->assertSame('pending', $delegation->status);

        $this->assertSame(1, $relieverUser->fresh()->unreadNotifications->count());
        $notificationData = $relieverUser->fresh()->unreadNotifications->first()->data;
        $this->assertStringContainsString('cover', strtolower($notificationData['message']));
    }

    public function test_reliever_can_accept_a_delegation_and_requester_is_notified(): void
    {
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser();
        [$relieverUser, $relieverEmployee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Delegation Accept Leave ' . uniqid(),
        ]);

        $leaveRequest = \App\Models\LeaveRequest::create([
            'reference_number' => \App\Models\LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $requesterEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'current_approval_level' => 0,
        ]);

        $delegation = LeaveDelegation::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'delegate_id' => $relieverEmployee->id,
            'leave_request_id' => $leaveRequest->id,
            'duties_delegated' => 'Cover the front desk.',
        ]);

        $this->actingAs($relieverUser);
        $controller = new LeaveDelegationController();
        $request = Request::create("/delegations/{$delegation->id}/accept", 'POST');
        $request->setUserResolver(fn () => $relieverUser);

        $response = $controller->accept($request, $business, $delegation)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $delegation->refresh();
        $this->assertTrue($delegation->delegate_accepted);
        $this->assertNotNull($delegation->accepted_at);
        $this->assertSame('accepted', $delegation->status);

        $this->assertSame(1, $requesterUser->fresh()->unreadNotifications->count());
    }

    public function test_reliever_can_decline_a_delegation(): void
    {
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser();
        [$relieverUser, $relieverEmployee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Delegation Decline Leave ' . uniqid(),
        ]);

        $leaveRequest = \App\Models\LeaveRequest::create([
            'reference_number' => \App\Models\LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $requesterEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'current_approval_level' => 0,
        ]);

        $delegation = LeaveDelegation::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'delegate_id' => $relieverEmployee->id,
            'leave_request_id' => $leaveRequest->id,
            'duties_delegated' => 'Cover the front desk.',
        ]);

        $this->actingAs($relieverUser);
        $controller = new LeaveDelegationController();
        $request = Request::create("/delegations/{$delegation->id}/decline", 'POST');
        $request->setUserResolver(fn () => $relieverUser);

        $response = $controller->decline($request, $business, $delegation)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $delegation->refresh();
        $this->assertFalse($delegation->delegate_accepted);
        $this->assertNotNull($delegation->declined_at);
        $this->assertSame('declined', $delegation->status);
    }

    public function test_a_stranger_cannot_respond_to_someone_elses_delegation(): void
    {
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser();
        [, $relieverEmployee] = $this->makeEmployeeUser();
        [$strangerUser,] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Delegation Stranger Leave ' . uniqid(),
        ]);

        $leaveRequest = \App\Models\LeaveRequest::create([
            'reference_number' => \App\Models\LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $requesterEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-21',
            'current_approval_level' => 0,
        ]);

        $delegation = LeaveDelegation::create([
            'business_id' => 1,
            'employee_id' => $requesterEmployee->id,
            'delegate_id' => $relieverEmployee->id,
            'leave_request_id' => $leaveRequest->id,
            'duties_delegated' => 'Cover the front desk.',
        ]);

        $this->actingAs($strangerUser);
        $controller = new LeaveDelegationController();
        $request = Request::create("/delegations/{$delegation->id}/accept", 'POST');
        $request->setUserResolver(fn () => $strangerUser);

        $response = $controller->accept($request, $business, $delegation)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());

        $this->assertFalse($delegation->fresh()->delegate_accepted);
    }
}
