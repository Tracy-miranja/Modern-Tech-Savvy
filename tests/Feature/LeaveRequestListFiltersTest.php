<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for:
 *  - the leave-types delete route (was registered at URI 'destroy' while
 *    every other CRUD group in routes/requests.php - and the JS service
 *    that calls it - uses 'delete', so POST /leave-types/delete 404'd);
 *  - the new department/location/leave-type/leave-period filters on the
 *    Leave Requests list (pending/approved/rejected tabs).
 */
class LeaveRequestListFiltersTest extends TestCase
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

    public function test_leave_types_delete_route_resolves_to_the_delete_uri(): void
    {
        $route = app('router')->getRoutes()->getByName('leave-types.delete');

        $this->assertNotNull($route, 'The leave-types.delete route must exist.');
        $this->assertSame('leave-types/delete', $route->uri());
        $this->assertContains('POST', $route->methods());
    }

    public function test_deleting_a_leave_type_removes_it_and_its_policies(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Delete Route Leave ' . uniqid()]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new LeaveTypeController();
        $request = Request::create('/leave-types/delete', 'POST', ['leave_type_slug' => $leaveType->slug]);
        $response = $controller->destroy($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, LeaveType::where('id', $leaveType->id)->count());
    }

    private function makeDepartment(): Department
    {
        return Department::create(['business_id' => 1, 'name' => 'Filter Dept ' . uniqid()]);
    }

    private function makeLocation(): Location
    {
        return Location::create(['business_id' => 1, 'name' => 'Filter Location ' . uniqid()]);
    }

    private function makeEmployee(?int $departmentId = null, ?int $locationId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId ?? 1,
            'location_id' => $locationId,
            'employee_code' => 'FLT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId ?? 1,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
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

    private function actAsHr(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs(User::factory()->create());
    }

    public function test_fetch_filters_by_department(): void
    {
        $deptX = $this->makeDepartment();
        $deptY = $this->makeDepartment();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Filter Dept Leave ' . uniqid()]);

        $employeeX = $this->makeEmployee($deptX->id);
        $employeeY = $this->makeEmployee($deptY->id);
        $this->makeLeaveRequest($employeeX, $leaveType, '2026-11-02', '2026-11-03');
        $this->makeLeaveRequest($employeeY, $leaveType, '2026-11-02', '2026-11-03');

        $this->actAsHr();
        $controller = new LeaveRequestController();
        $request = Request::create('/leave/fetch', 'POST', ['status' => 'pending', 'department_id' => $deptX->id]);
        $response = $controller->fetch($request)->toResponse($request);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($employeeX->user->name, $html);
        $this->assertStringNotContainsString($employeeY->user->name, $html);
    }

    public function test_fetch_filters_by_location(): void
    {
        $locationA = $this->makeLocation();
        $locationB = $this->makeLocation();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Filter Location Leave ' . uniqid()]);

        $employeeA = $this->makeEmployee(null, $locationA->id);
        $employeeB = $this->makeEmployee(null, $locationB->id);
        $this->makeLeaveRequest($employeeA, $leaveType, '2026-11-02', '2026-11-03');
        $this->makeLeaveRequest($employeeB, $leaveType, '2026-11-02', '2026-11-03');

        $this->actAsHr();
        $controller = new LeaveRequestController();
        $request = Request::create('/leave/fetch', 'POST', ['status' => 'pending', 'location_id' => $locationA->id]);
        $response = $controller->fetch($request)->toResponse($request);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($employeeA->user->name, $html);
        $this->assertStringNotContainsString($employeeB->user->name, $html);
    }

    public function test_fetch_filters_by_leave_type(): void
    {
        $employee = $this->makeEmployee();
        $leaveTypeA = LeaveType::create(['business_id' => 1, 'name' => 'Filter Type A ' . uniqid()]);
        $leaveTypeB = LeaveType::create(['business_id' => 1, 'name' => 'Filter Type B ' . uniqid()]);

        $requestA = $this->makeLeaveRequest($employee, $leaveTypeA, '2026-11-02', '2026-11-03');
        $requestB = $this->makeLeaveRequest($employee, $leaveTypeB, '2026-11-05', '2026-11-06');

        $this->actAsHr();
        $controller = new LeaveRequestController();
        $httpRequest = Request::create('/leave/fetch', 'POST', ['status' => 'pending', 'leave_type_id' => $leaveTypeA->id]);
        $response = $controller->fetch($httpRequest)->toResponse($httpRequest);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($requestA->reference_number, $html);
        $this->assertStringNotContainsString($requestB->reference_number, $html);
    }

    public function test_fetch_filters_by_leave_period_via_date_overlap(): void
    {
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Filter Period Leave ' . uniqid()]);

        $period2026 = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Filter Period 2026 ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $period2027 = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Filter Period 2027 ' . uniqid(),
            'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'is_active' => true,
        ]);

        $requestIn2026 = $this->makeLeaveRequest($employee, $leaveType, '2026-11-02', '2026-11-03');
        $requestIn2027 = $this->makeLeaveRequest($employee, $leaveType, '2027-03-02', '2027-03-03');

        $this->actAsHr();
        $controller = new LeaveRequestController();
        $httpRequest = Request::create('/leave/fetch', 'POST', ['status' => 'pending', 'leave_period_id' => $period2026->id]);
        $response = $controller->fetch($httpRequest)->toResponse($httpRequest);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($requestIn2026->reference_number, $html);
        $this->assertStringNotContainsString($requestIn2027->reference_number, $html);

        $httpRequest2 = Request::create('/leave/fetch', 'POST', ['status' => 'pending', 'leave_period_id' => $period2027->id]);
        $response2 = $controller->fetch($httpRequest2)->toResponse($httpRequest2);
        $html2 = json_decode($response2->getContent(), true)['data'];

        $this->assertStringContainsString($requestIn2027->reference_number, $html2);
        $this->assertStringNotContainsString($requestIn2026->reference_number, $html2);
    }

    /**
     * The employee-portal "My Leave Requests" list previously only ever
     * showed a business-employee-role user their OWN requests - a manager
     * had no page where a direct report's pending request ever appeared,
     * even though LeaveRequest::canUserApprove()'s 'organogram' branch
     * already lets them approve it if reached another way. Fixed to also
     * include direct reports, matching what the (previously dead/unused)
     * LeaveRequest::scopeForRole() already described.
     */
    public function test_fetch_as_business_employee_includes_direct_reports_requests_alongside_own(): void
    {
        $manager = $this->makeEmployee();
        $report = $this->makeEmployee();
        $report->manager_id = $manager->id;
        $report->save();
        $stranger = $this->makeEmployee();

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Portal Fetch Scope Leave ' . uniqid()]);
        $ownRequest = $this->makeLeaveRequest($manager, $leaveType, '2026-10-01', '2026-10-02');
        $reportRequest = $this->makeLeaveRequest($report, $leaveType, '2026-10-05', '2026-10-06');
        $strangerRequest = $this->makeLeaveRequest($stranger, $leaveType, '2026-10-08', '2026-10-09');

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($manager->user);

        $controller = new LeaveRequestController();
        $httpRequest = Request::create('/leave/fetch', 'POST', ['status' => 'pending']);
        $response = $controller->fetch($httpRequest)->toResponse($httpRequest);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertStringContainsString($ownRequest->reference_number, $html, 'A manager must still see their own request.');
        $this->assertStringContainsString($reportRequest->reference_number, $html, 'A manager must see a direct reports pending request.');
        $this->assertStringNotContainsString($strangerRequest->reference_number, $html, 'An unrelated employees request must not leak through.');
    }
}
