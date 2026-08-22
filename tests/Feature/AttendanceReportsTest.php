<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceReportController;
use App\Models\Attendance;
use App\Models\AttendancePolicy;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Holiday;
use App\Models\JobCategory;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendancePolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the rest of the Attendance reports suite (Full,
 * Summary, Monthly, Lateness, Absent, Overtime, Per-member) and the
 * Expected Working Hours policy resolver - see GUIDE plan Phase 1.
 */
class AttendanceReportsTest extends TestCase
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

        // Real holidays already on file for business 1 can land inside a
        // test's date range and shift working-day counts off the numbers
        // this suite hardcodes.
        Holiday::where('business_id', 1)->delete();
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
            'employee_code' => 'ART-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        // employees.job_category_id isn't a real column - Employee's own
        // job_category_id is a PHP accessor falling back to
        // employmentDetails->job_category_id (Employee::getJobCategoryIdAttribute),
        // so that's the real place to set it for a test.
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

    // ---- Row-level reports -------------------------------------------

    public function test_lateness_report_only_includes_rows_with_late_minutes(): void
    {
        $onTime = $this->makeEmployee();
        $late = $this->makeEmployee();

        Attendance::create(['employee_id' => $onTime->id, 'business_id' => 1, 'date' => '2026-08-03', 'late_minutes' => 0]);
        Attendance::create(['employee_id' => $late->id, 'business_id' => 1, 'date' => '2026-08-03', 'late_minutes' => 15]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->latenessPreview($request, $business);

        $this->assertStringContainsString($late->user->name, $html);
        $this->assertStringNotContainsString($onTime->user->name, $html);
    }

    public function test_overtime_report_only_includes_rows_with_overtime(): void
    {
        $noOt = $this->makeEmployee();
        $withOt = $this->makeEmployee();

        Attendance::create(['employee_id' => $noOt->id, 'business_id' => 1, 'date' => '2026-08-03', 'overtime_hours' => 0]);
        Attendance::create(['employee_id' => $withOt->id, 'business_id' => 1, 'date' => '2026-08-03', 'overtime_hours' => 2.5]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->overtimePreview($request, $business);

        $this->assertStringContainsString($withOt->user->name, $html);
        $this->assertStringNotContainsString($noOt->user->name, $html);
    }

    public function test_absent_report_excludes_employees_with_no_work_schedule(): void
    {
        $flexible = $this->makeEmployee();
        $scheduled = $this->makeEmployee();

        $shift = Shift::create(['business_id' => 1, 'name' => 'ART Shift ' . uniqid(), 'start_time' => '08:00', 'end_time' => '17:00']);
        WorkSchedule::create([
            'business_id' => 1, 'employee_id' => $scheduled->id, 'shift_id' => $shift->id,
            'working_days' => [1, 2, 3, 4, 5], 'effective_from' => '2026-01-01',
        ]);

        Attendance::create(['employee_id' => $flexible->id, 'business_id' => 1, 'date' => '2026-08-03', 'is_absent' => true, 'is_working_day' => true, 'is_holiday' => false]);
        Attendance::create(['employee_id' => $scheduled->id, 'business_id' => 1, 'date' => '2026-08-03', 'is_absent' => true, 'is_working_day' => true, 'is_holiday' => false]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->absentPreview($request, $business);

        $this->assertStringContainsString($scheduled->user->name, $html);
        $this->assertStringNotContainsString($flexible->user->name, $html);
    }

    public function test_per_member_report_requires_exactly_one_employee_selected(): void
    {
        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->perMemberPreview($request, $business);

        $this->assertStringContainsString('Select exactly one employee', $html);
    }

    // ---- Aggregated reports ---------------------------------------------

    public function test_job_category_filter_on_reports_does_not_crash_and_scopes_correctly(): void
    {
        // Regression: ReportFilters originally filtered job_category_id as
        // a raw column on `employees`, but that column doesn't exist at
        // all (it's a PHP accessor falling back to employment_details) -
        // any report actually filtered by job category would throw a SQL
        // error ("Unknown column 'job_category_id'").
        $jobCategory = JobCategory::create(['business_id' => 1, 'name' => 'ART Job ' . uniqid()]);
        $inCategory = $this->makeEmployee(1, $jobCategory->id);
        $outOfCategory = $this->makeEmployee(1);

        Attendance::create(['employee_id' => $inCategory->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8]);
        Attendance::create(['employee_id' => $outOfCategory->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['date' => '2026-08-03', 'job_category_id' => $jobCategory->id]);
        $html = $controller->dailyPreview($request, $business);

        $this->assertStringContainsString($inCategory->user->name, $html);
        $this->assertStringNotContainsString($outOfCategory->user->name, $html);
    }

    public function test_summary_report_shows_zero_record_employees_explicitly(): void
    {
        $department = Department::create(['business_id' => 1, 'name' => 'ART Dept ' . uniqid()]);
        $withRecords = $this->makeEmployee($department->id);
        $noRecords = $this->makeEmployee($department->id);

        Attendance::create(['employee_id' => $withRecords->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8, 'is_absent' => false]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'department_id' => $department->id]);
        $html = $controller->summaryPreview($request, $business);

        $this->assertStringContainsString($withRecords->user->name, $html);
        $this->assertStringContainsString($noRecords->user->name, $html, 'An employee with zero attendance rows must still appear, not be silently dropped.');
    }

    public function test_monthly_report_includes_expected_actual_and_variance_columns(): void
    {
        $employee = $this->makeEmployee();
        Attendance::create(['employee_id' => $employee->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8, 'is_absent' => false, 'is_working_day' => true]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31', 'employee_ids' => [$employee->id]]);
        $html = $controller->monthlyPreview($request, $business);

        $this->assertStringContainsString('Expected Hours', $html);
        $this->assertStringContainsString('Variance', $html);
        $this->assertStringContainsString($employee->user->name, $html);
    }

    // ---- AttendancePolicyService resolver --------------------------------

    public function test_policy_resolution_follows_the_same_specificity_precedence_as_leave_policies(): void
    {
        $department = Department::create(['business_id' => 1, 'name' => 'APT Dept ' . uniqid()]);
        $jobCategory = JobCategory::create(['business_id' => 1, 'name' => 'APT Job ' . uniqid()]);
        $employee = $this->makeEmployee($department->id, $jobCategory->id);

        $service = app(AttendancePolicyService::class);

        // Nothing configured -> sane fallback.
        $this->assertSame(8.0, $service->resolveHoursPerDay($employee));

        // Business-wide default.
        AttendancePolicy::create(['business_id' => 1, 'expected_hours_per_day' => 7.5]);
        $this->assertSame(7.5, $service->resolveHoursPerDay($employee));

        // Department-level override beats the business default.
        AttendancePolicy::create(['business_id' => 1, 'department_id' => $department->id, 'expected_hours_per_day' => 8.5]);
        $this->assertSame(8.5, $service->resolveHoursPerDay($employee));

        // Department+job-category combo beats department-only (more specific).
        AttendancePolicy::create(['business_id' => 1, 'department_id' => $department->id, 'job_category_id' => $jobCategory->id, 'expected_hours_per_day' => 9.0]);
        $this->assertSame(9.0, $service->resolveHoursPerDay($employee));

        // Employee-specific override beats everything.
        AttendancePolicy::create(['business_id' => 1, 'employee_id' => $employee->id, 'expected_hours_per_day' => 6.0]);
        $this->assertSame(6.0, $service->resolveHoursPerDay($employee));
    }

    public function test_expected_hours_for_period_excludes_non_working_days_and_holidays(): void
    {
        $employee = $this->makeEmployee();
        AttendancePolicy::create(['business_id' => 1, 'expected_hours_per_day' => 8.0]);

        $shift = Shift::create(['business_id' => 1, 'name' => 'APT Shift ' . uniqid(), 'start_time' => '08:00', 'end_time' => '17:00']);
        WorkSchedule::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'shift_id' => $shift->id,
            'working_days' => [1, 2, 3, 4, 5], 'effective_from' => '2026-01-01', // Mon-Fri
        ]);

        // A public holiday on a Monday within the period.
        Holiday::create(['business_id' => 1, 'name' => 'APT Holiday', 'date' => '2026-08-03', 'is_recurring' => false, 'is_working_day' => false]);

        $service = app(AttendancePolicyService::class);
        // August 2026: Sat 1 - Mon 31. Mon-Fri working days = 21, minus 1 holiday (Mon 3rd) = 20.
        $expected = $service->resolveExpectedHoursForPeriod($employee, \Carbon\Carbon::parse('2026-08-01'), \Carbon\Carbon::parse('2026-08-31'));

        $this->assertSame(160.0, $expected); // 20 working days x 8 hrs
    }

    // ---- Policy CRUD endpoint --------------------------------------------

    public function test_attendance_policy_can_be_created_and_deleted_via_the_controller(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        $storeResponse = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('business.attendances.policies.store', $business->slug), [
                'expected_hours_per_day' => 7.0,
            ]);
        $storeResponse->assertCreated();

        $policyId = AttendancePolicy::where('business_id', 1)->where('expected_hours_per_day', 7.0)->latest('id')->value('id');
        $this->assertNotNull($policyId);

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->delete(route('business.attendances.policies.destroy', [$business->slug, $policyId]))
            ->assertOk();

        $this->assertNull(AttendancePolicy::find($policyId));
    }
}
