<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveCalendarController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the leave calendar: approved leave + business
 * holidays should both surface as events, scoped correctly per view.
 */
class LeaveCalendarTest extends TestCase
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

        // amsol has real holidays already on file that can land inside
        // whatever date range a test picks - clear them so only the
        // holiday(s) a test explicitly creates show up in calendar events.
        Holiday::where('business_id', 1)->delete();
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
            'employee_code' => 'CAL-' . uniqid(),
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

    public function test_employee_calendar_shows_own_approved_leave_and_holidays(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $approver = User::factory()->create();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Calendar Test Leave ' . uniqid(),
        ]);

        LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-11',
            'current_approval_level' => 1,
            'approved_by' => $approver->id,
        ]);

        $holiday = Holiday::create([
            'business_id' => 1,
            'name' => 'Calendar Test Holiday ' . uniqid(),
            'date' => '2026-12-15',
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $this->actingAs($user);
        $controller = new LeaveCalendarController();
        $request = Request::create('/leave/calendar/events', 'GET', [
            'start' => '2026-12-01',
            'end' => '2026-12-31',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = $controller->employeeEvents($request, $business);
        $events = json_decode($response->getContent(), true);

        $leaveEvent = collect($events)->firstWhere('extendedProps.type', 'leave');
        $holidayEvent = collect($events)->firstWhere('extendedProps.type', 'holiday');

        $this->assertNotNull($leaveEvent, 'Expected the approved leave event to be present.');
        $this->assertNotNull($holidayEvent, 'Expected the holiday event to be present.');
        $this->assertSame('2026-12-10', $leaveEvent['start']);
        $this->assertSame('2026-12-15', $holidayEvent['start']);
    }

    public function test_employee_calendar_excludes_unrelated_colleagues_leave(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        [, $strangerEmployee] = $this->makeEmployeeUser();
        $approver = User::factory()->create();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Calendar Stranger Leave ' . uniqid(),
        ]);

        LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $strangerEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-12-20',
            'end_date' => '2026-12-21',
            'current_approval_level' => 1,
            'approved_by' => $approver->id,
        ]);

        $this->actingAs($user);
        $controller = new LeaveCalendarController();
        $request = Request::create('/leave/calendar/events', 'GET', [
            'start' => '2026-12-01',
            'end' => '2026-12-31',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = $controller->employeeEvents($request, $business);
        $events = json_decode($response->getContent(), true);

        $leaveEvents = collect($events)->where('extendedProps.type', 'leave');
        $this->assertTrue($leaveEvents->isEmpty(), 'Should not see an unrelated colleague\'s leave.');
    }

    public function test_business_calendar_shows_all_approved_leave_in_business(): void
    {
        [, $employeeA] = $this->makeEmployeeUser();
        [, $employeeB] = $this->makeEmployeeUser();
        $approver = User::factory()->create();
        $business = Business::find(1);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Calendar Business Leave ' . uniqid(),
        ]);

        foreach ([$employeeA, $employeeB] as $emp) {
            LeaveRequest::create([
                'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
                'employee_id' => $emp->id,
                'business_id' => 1,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2027-01-05',
                'end_date' => '2027-01-06',
                'current_approval_level' => 1,
                'approved_by' => $approver->id,
            ]);
        }

        $controller = new LeaveCalendarController();
        $request = Request::create('/leave/calendar/events', 'GET', [
            'start' => '2027-01-01',
            'end' => '2027-01-31',
        ]);

        $response = $controller->businessEvents($request, $business);
        $events = json_decode($response->getContent(), true);

        $leaveEvents = collect($events)->where('extendedProps.type', 'leave');
        $this->assertCount(2, $leaveEvents);
    }

    // ---- Leave Planner (row-per-employee timeline + capacity strip) -------

    public function test_business_events_carry_structured_employee_fields_for_the_planner(): void
    {
        [, $employee] = $this->makeEmployeeUser();
        $approver = User::factory()->create();
        $business = Business::find(1);

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Planner Fields Leave ' . uniqid()]);

        LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id, 'business_id' => 1, 'leave_type_id' => $leaveType->id,
            'start_date' => '2027-02-05', 'end_date' => '2027-02-06',
            'current_approval_level' => 1, 'approved_by' => $approver->id,
        ]);

        $controller = new LeaveCalendarController();
        $request = Request::create('/leave/calendar/events', 'GET', ['start' => '2027-02-01', 'end' => '2027-02-28']);
        $response = $controller->businessEvents($request, $business);
        $events = json_decode($response->getContent(), true);

        $leaveEvent = collect($events)->firstWhere('extendedProps.type', 'leave');
        $this->assertNotNull($leaveEvent);
        $this->assertSame($employee->id, $leaveEvent['extendedProps']['employee_id']);
        $this->assertSame($employee->user->name, $leaveEvent['extendedProps']['employee_name']);
        $this->assertSame($employee->department_id, $leaveEvent['extendedProps']['department_id']);
        $this->assertSame($leaveType->name, $leaveEvent['extendedProps']['leave_type']);
    }

    public function test_team_headcount_scopes_by_department(): void
    {
        [, $employeeA] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $otherDepartment = \App\Models\Department::create(['business_id' => 1, 'name' => 'Planner Dept ' . uniqid()]);
        $employeeA->update(['department_id' => $otherDepartment->id]);

        $controller = new LeaveCalendarController();

        $scopedRequest = Request::create('/x', 'GET', ['department_id' => $otherDepartment->id]);
        $scopedResponse = $controller->teamHeadcount($scopedRequest, $business);
        $scopedCount = json_decode($scopedResponse->getContent(), true)['count'];

        $this->assertSame(1, $scopedCount);
    }
}
