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
use App\Notifications\LeaveStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression coverage locking in that an employee actually receives an
 * email once their leave request is approved (and rejected) -
 * LeaveStatusNotification was already correctly wired (ShouldQueue +
 * Queueable both active, via() includes 'mail', toMail() fully defined)
 * but had no test proving it fires on the real approval path.
 */
class LeaveApprovalEmailTest extends TestCase
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

    private function makeEmployeeUser(?int $managerId = null): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'manager_id' => $managerId,
            'employee_code' => 'EMAIL-' . uniqid(),
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

    public function test_employee_receives_an_email_when_their_leave_is_approved(): void
    {
        Notification::fake();

        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Email Approval Leave ' . uniqid(),
            'requires_approval' => true,
        ]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Email Approval Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $requesterEmployee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 21, 'accrued_days' => 0,
            'total_days' => 21, 'days_taken' => 0, 'days_remaining' => 21,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $storeRequest = Request::create('/leave/requests', 'POST', [
            'employee_id' => $requesterEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-08',
        ]);
        $storeRequest->setUserResolver(fn () => $requesterUser);
        $controller->store($storeRequest)->toResponse($storeRequest);

        $leaveRequest = LeaveRequest::where('employee_id', $requesterEmployee->id)->first();

        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $statusRequest = Request::create('/leave/status', 'POST', [
            'reference_number' => $leaveRequest->reference_number,
            'status' => 'approved',
        ]);
        $statusRequest->setUserResolver(fn () => $managerUser);
        $response = $controller->status($statusRequest)->toResponse($statusRequest);

        $this->assertSame(200, $response->getStatusCode());

        Notification::assertSentTo(
            $requesterUser,
            LeaveStatusNotification::class,
            function (LeaveStatusNotification $notification, array $channels) use ($requesterUser) {
                return in_array('mail', $channels, true)
                    && $notification->leave->status === 'approved'
                    && in_array('mail', $notification->via($requesterUser), true);
            }
        );
    }

    public function test_employee_receives_an_email_when_their_leave_is_rejected(): void
    {
        Notification::fake();

        [$managerUser, $managerEmployee] = $this->makeEmployeeUser();
        [$requesterUser, $requesterEmployee] = $this->makeEmployeeUser($managerEmployee->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Email Rejection Leave ' . uniqid(),
            'requires_approval' => true,
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($requesterUser);

        $controller = new LeaveRequestController();
        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $requesterEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-08',
            'current_approval_level' => 0,
        ]);

        session(['active_role' => 'business-employee']);
        $this->actingAs($managerUser);
        $statusRequest = Request::create('/leave/status', 'POST', [
            'reference_number' => $leaveRequest->reference_number,
            'status' => 'rejected',
            'rejection_reason' => 'Insufficient coverage during that period.',
        ]);
        $statusRequest->setUserResolver(fn () => $managerUser);
        $response = $controller->status($statusRequest)->toResponse($statusRequest);

        $this->assertSame(200, $response->getStatusCode());

        Notification::assertSentTo(
            $requesterUser,
            LeaveStatusNotification::class,
            function (LeaveStatusNotification $notification, array $channels) {
                return in_array('mail', $channels, true) && $notification->leave->status === 'rejected';
            }
        );
    }
}
