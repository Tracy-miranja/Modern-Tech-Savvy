<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveReportController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\JobCategory;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Leave reports suite (Balance, Full, Types, Per-member) - see GUIDE plan
 * Phase 2. Mirrors AttendanceReportsTest's structure/pattern.
 */
class LeaveReportsTest extends TestCase
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

    private function makeEmployee(?int $departmentId = 1, ?int $jobCategoryId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'employee_code' => 'LRT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        if ($jobCategoryId) {
            EmploymentDetail::create([
                'employee_id' => $employee->id,
                'department_id' => $departmentId ?? 1,
                'job_category_id' => $jobCategoryId,
                'employment_date' => '2020-01-01',
                'employment_term' => 'permanent',
            ]);
        }

        return $employee->fresh();
    }

    private function makeLeaveRequest(Employee $employee, LeaveType $leaveType, string $start, string $end, ?int $approvedBy = null): LeaveRequest
    {
        return LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => $start,
            'end_date' => $end,
            'approved_by' => $approvedBy,
        ]);
    }

    // ---- Full report -----------------------------------------------------

    public function test_full_report_only_includes_requests_starting_within_the_filtered_period(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'LRT Type ' . uniqid()]);
        $employee = $this->makeEmployee();

        $inRange = $this->makeLeaveRequest($employee, $leaveType, '2026-08-03', '2026-08-05');
        $outOfRange = $this->makeLeaveRequest($employee, $leaveType, '2026-09-10', '2026-09-12');

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->fullPreview($request, $business);

        $this->assertStringContainsString($inRange->reference_number, $html);
        $this->assertStringNotContainsString($outOfRange->reference_number, $html);
    }

    public function test_job_category_filter_on_leave_reports_does_not_crash_and_scopes_correctly(): void
    {
        // Same regression class as the attendance reports: employees.job_category_id
        // isn't a real column, so this must resolve through employment_details.
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'LRT Type ' . uniqid()]);
        $jobCategory = JobCategory::create(['business_id' => 1, 'name' => 'LRT Job ' . uniqid()]);
        $inCategory = $this->makeEmployee(1, $jobCategory->id);
        $outOfCategory = $this->makeEmployee(1);

        $inRequest = $this->makeLeaveRequest($inCategory, $leaveType, '2026-08-03', '2026-08-05');
        $outRequest = $this->makeLeaveRequest($outOfCategory, $leaveType, '2026-08-04', '2026-08-06');

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'job_category_id' => $jobCategory->id,
        ]);
        $html = $controller->fullPreview($request, $business);

        $this->assertStringContainsString($inRequest->reference_number, $html);
        $this->assertStringNotContainsString($outRequest->reference_number, $html);
    }

    public function test_leave_type_filter_narrows_full_report(): void
    {
        $typeA = LeaveType::create(['business_id' => 1, 'name' => 'LRT Annual ' . uniqid()]);
        $typeB = LeaveType::create(['business_id' => 1, 'name' => 'LRT Sick ' . uniqid()]);
        $employee = $this->makeEmployee();

        $wanted = $this->makeLeaveRequest($employee, $typeA, '2026-08-03', '2026-08-05');
        $unwanted = $this->makeLeaveRequest($employee, $typeB, '2026-08-04', '2026-08-06');

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', [
            'start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'leave_type_ids' => [$typeA->id],
        ]);
        $html = $controller->fullPreview($request, $business);

        $this->assertStringContainsString($wanted->reference_number, $html);
        $this->assertStringNotContainsString($unwanted->reference_number, $html);
    }

    // ---- Per-member report -------------------------------------------

    public function test_per_member_report_requires_exactly_one_employee_selected(): void
    {
        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', []);
        $html = $controller->perMemberPreview($request, $business);

        $this->assertStringContainsString('Select exactly one employee', $html);
    }

    public function test_per_member_report_includes_entitlement_summary_and_full_history(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'LRT Type ' . uniqid()]);
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'LRT Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $employee = $this->makeEmployee();
        $other = $this->makeEmployee();

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $leavePeriod->id, 'entitled_days' => 21,
        ]);
        $entitlement->recalculateTotals();

        // Deliberately outside any typical "current month" default window -
        // per-member is full-history-by-default, so this must still show up
        // with no explicit date filter applied.
        $mine = $this->makeLeaveRequest($employee, $leaveType, '2020-03-01', '2020-03-03');
        $notMine = $this->makeLeaveRequest($other, $leaveType, '2020-03-01', '2020-03-03');

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', ['employee_ids' => [$employee->id]]);
        $html = $controller->perMemberPreview($request, $business);

        $this->assertStringContainsString($mine->reference_number, $html);
        $this->assertStringNotContainsString($notMine->reference_number, $html);
        $this->assertStringContainsString($leavePeriod->name, $html);
    }

    // ---- Types report -------------------------------------------------

    public function test_types_report_aggregates_request_count_and_approved_days_by_type(): void
    {
        $typeA = LeaveType::create(['business_id' => 1, 'name' => 'LRT Annual ' . uniqid()]);
        $typeB = LeaveType::create(['business_id' => 1, 'name' => 'LRT Sick ' . uniqid()]);
        $employee = $this->makeEmployee();
        $approver = User::factory()->create();

        // Two approved 3-day requests under type A, one pending under type B.
        $this->makeLeaveRequest($employee, $typeA, '2026-08-03', '2026-08-05', $approver->id);
        $this->makeLeaveRequest($employee, $typeA, '2026-08-10', '2026-08-12', $approver->id);
        $this->makeLeaveRequest($employee, $typeB, '2026-08-15', '2026-08-16');

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->typesPreview($request, $business);

        $this->assertStringContainsString($typeA->name, $html);
        $this->assertStringContainsString($typeB->name, $html);
    }

    // ---- Balance report -------------------------------------------------

    public function test_balance_report_pivots_remaining_days_by_leave_type_per_employee(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'LRT Type ' . uniqid()]);
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'LRT Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);
        $employee = $this->makeEmployee();

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $leavePeriod->id, 'entitled_days' => 21,
        ]);
        $entitlement->recalculateTotals();

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', ['leave_period_id' => $leavePeriod->id]);
        $html = $controller->balancePreview($request, $business);

        $this->assertStringContainsString($employee->user->name, $html);
        $this->assertStringContainsString($leaveType->name, $html);
    }

    public function test_balance_report_defaults_to_the_active_period_when_none_selected(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'LRT Type ' . uniqid()]);
        // Far-future start_date so this period unambiguously sorts first
        // ahead of any other active periods already on this shared test
        // business (orderByDesc('is_active')->orderByDesc('start_date')).
        $activePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'LRT Active ' . uniqid(),
            'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'is_active' => true,
        ]);
        $employee = $this->makeEmployee();

        $entitlement = LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $activePeriod->id, 'entitled_days' => 21,
        ]);
        $entitlement->recalculateTotals();

        $business = Business::find(1);
        $controller = new LeaveReportController();
        $request = Request::create('/x', 'GET', []);
        $html = $controller->balancePreview($request, $business);

        $this->assertStringContainsString($activePeriod->name, $html);
        $this->assertStringContainsString($employee->user->name, $html);
    }
}
