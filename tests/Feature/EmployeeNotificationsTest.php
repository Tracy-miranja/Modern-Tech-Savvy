<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeDashboardController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Regression coverage for the portal notifications page: previously the
 * sidebar link pointed at a static notifications.html file, and even the
 * named Laravel route rendered a view that didn't exist on disk.
 */
class EmployeeNotificationsTest extends TestCase
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
            'employee_code' => 'NOTIF-' . uniqid(),
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

    public function test_notifications_page_lists_real_database_notifications(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => \App\Models\LeaveType::create([
                'business_id' => 1,
                'name' => 'Notif Test Leave ' . uniqid(),
            ])->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'current_approval_level' => 0,
        ]);

        $user->notify(new LeaveStatusNotification($leaveRequest));

        $controller = new EmployeeDashboardController();
        $request = Request::create('/notifications');
        $request->setUserResolver(fn () => $user);

        $response = $controller->notifications($request, $business);
        $notifications = $response->getData()['notifications'];

        $this->assertCount(1, $notifications);
        $this->assertNull($notifications->first()->read_at);
    }

    public function test_mark_notification_read_only_affects_the_targeted_notification(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveType = \App\Models\LeaveType::create([
            'business_id' => 1,
            'name' => 'Notif Test Leave 2 ' . uniqid(),
        ]);

        foreach (range(1, 2) as $i) {
            $leaveRequest = LeaveRequest::create([
                'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
                'employee_id' => $employee->id,
                'business_id' => 1,
                'leave_type_id' => $leaveType->id,
                'start_date' => "2026-09-1{$i}",
                'end_date' => "2026-09-1{$i}",
                'current_approval_level' => 0,
            ]);
            $user->notify(new LeaveStatusNotification($leaveRequest));
        }

        $this->assertSame(2, $user->fresh()->unreadNotifications->count());

        $firstId = $user->fresh()->notifications->first()->id;

        $controller = new EmployeeDashboardController();
        $request = Request::create("/notifications/{$firstId}/read", 'POST');
        $request->setUserResolver(fn () => $user);

        $controller->markNotificationRead($request, $business, $firstId);

        $this->assertSame(1, $user->fresh()->unreadNotifications->count());
    }

    public function test_mark_all_notifications_read(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $leaveType = \App\Models\LeaveType::create([
            'business_id' => 1,
            'name' => 'Notif Test Leave 3 ' . uniqid(),
        ]);

        foreach (range(1, 3) as $i) {
            $leaveRequest = LeaveRequest::create([
                'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
                'employee_id' => $employee->id,
                'business_id' => 1,
                'leave_type_id' => $leaveType->id,
                'start_date' => "2026-10-0{$i}",
                'end_date' => "2026-10-0{$i}",
                'current_approval_level' => 0,
            ]);
            $user->notify(new LeaveStatusNotification($leaveRequest));
        }

        $this->assertSame(3, $user->fresh()->unreadNotifications->count());

        $controller = new EmployeeDashboardController();
        $request = Request::create('/notifications/read-all', 'POST');
        $request->setUserResolver(fn () => $user);

        $controller->markAllNotificationsRead($request, $business);

        $this->assertSame(0, $user->fresh()->unreadNotifications->count());
    }
}
