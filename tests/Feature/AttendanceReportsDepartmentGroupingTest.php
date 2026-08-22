<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceReportController;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Attendance reports organized department-wise: row-level reports (Full,
 * Daily, Absent, Per-member - all sharing attendances.reports.rows) get a
 * department section header + divider before that department's rows;
 * Lateness/Overtime get a deeper department -> employee -> one row per
 * flagged day -> totals row structure (attendances.reports.person-days).
 */
class AttendanceReportsDepartmentGroupingTest extends TestCase
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

        Holiday::where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeEmployee(int $departmentId): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'employee_code' => 'ADG-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return $employee->fresh();
    }

    public function test_full_report_is_grouped_into_department_sections(): void
    {
        $deptA = Department::create(['business_id' => 1, 'name' => 'ADG Dept A ' . uniqid()]);
        $deptB = Department::create(['business_id' => 1, 'name' => 'ADG Dept B ' . uniqid()]);
        $empA = $this->makeEmployee($deptA->id);
        $empB = $this->makeEmployee($deptB->id);

        Attendance::create(['employee_id' => $empA->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8]);
        Attendance::create(['employee_id' => $empB->id, 'business_id' => 1, 'date' => '2026-08-03', 'regular_hours' => 8]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->fullPreview($request, $business);

        $this->assertStringContainsString($deptA->name, $html);
        $this->assertStringContainsString($deptB->name, $html);
        $this->assertStringContainsString($empA->user->name, $html);
        $this->assertStringContainsString($empB->user->name, $html);

        // The department name must appear as its own section header BEFORE
        // that department's employee row, not just anywhere on the page.
        $deptAPos = strpos($html, $deptA->name);
        $empAPos = strpos($html, $empA->user->name);
        $this->assertLessThan($empAPos, $deptAPos);

        // Department section headers ("IT — 10 records") must be centered
        // and bold to stand out from the data rows they separate.
        $this->assertMatchesRegularExpression(
            '/tr\.dept-section-header\s+td\s*\{[^}]*text-align:\s*center[^}]*\}/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/tr\.dept-section-header\s+td\s*\{[^}]*font-weight:\s*700[^}]*\}/s',
            $html
        );
    }

    public function test_lateness_report_lists_every_late_day_then_a_totals_row_per_employee(): void
    {
        $department = Department::create(['business_id' => 1, 'name' => 'ADG Lateness Dept ' . uniqid()]);
        $employee = $this->makeEmployee($department->id);

        Attendance::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'date' => '2026-08-03',
            'late_minutes' => 15, 'clock_in' => '2026-08-03 08:15:00', 'expected_clock_in' => '2026-08-03 08:00:00',
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'date' => '2026-08-04',
            'late_minutes' => 30, 'clock_in' => '2026-08-04 08:30:00', 'expected_clock_in' => '2026-08-04 08:00:00',
        ]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->latenessPreview($request, $business);

        $this->assertStringContainsString($department->name, $html);
        $this->assertStringContainsString($employee->user->name, $html);
        $this->assertStringContainsString('03 Aug 2026', $html);
        $this->assertStringContainsString('04 Aug 2026', $html);
        // 15 + 30 late minutes = 45 minutes = 0h 45m total.
        $this->assertStringContainsString('0h 45m', $html);
        $this->assertStringContainsString('Total for ' . $employee->user->name, $html);
    }

    public function test_overtime_report_lists_every_overtime_day_then_a_totals_row_per_employee(): void
    {
        $department = Department::create(['business_id' => 1, 'name' => 'ADG Overtime Dept ' . uniqid()]);
        $employee = $this->makeEmployee($department->id);

        Attendance::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'date' => '2026-08-03',
            'overtime_hours' => 1.5, 'clock_out' => '2026-08-03 18:30:00', 'expected_clock_out' => '2026-08-03 17:00:00',
        ]);
        Attendance::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'date' => '2026-08-04',
            'overtime_hours' => 2.0, 'clock_out' => '2026-08-04 19:00:00', 'expected_clock_out' => '2026-08-04 17:00:00',
        ]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/x', 'GET', ['start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        $html = $controller->overtimePreview($request, $business);

        $this->assertStringContainsString($department->name, $html);
        $this->assertStringContainsString($employee->user->name, $html);
        // 1.5 + 2.0 overtime hours = 3.5 = 3h 30m total.
        $this->assertStringContainsString('3h 30m', $html);
        $this->assertStringContainsString('Total for ' . $employee->user->name, $html);
    }
}
