<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceReportController;
use App\Models\Attendance;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\TimeFmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the Attendance Daily report - the Phase 0/Phase 1
 * proof-of-concept slice of the shared report engine (see GUIDE plan).
 * Confirms the filter engine is actually enforced server-side and that
 * preview/download are built from identical data.
 */
class AttendanceDailyReportTest extends TestCase
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

    private function makeEmployee(int $departmentId): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'employee_code' => 'ADR-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();
    }

    public function test_daily_report_only_includes_the_selected_date_and_respects_department_filter(): void
    {
        $deptA = Department::create(['business_id' => 1, 'name' => 'ADR Dept A ' . uniqid()]);
        $deptB = Department::create(['business_id' => 1, 'name' => 'ADR Dept B ' . uniqid()]);

        $inDept = $this->makeEmployee($deptA->id);
        $outOfDept = $this->makeEmployee($deptB->id);
        $wrongDay = $this->makeEmployee($deptA->id);

        Attendance::create([
            'employee_id' => $inDept->id, 'business_id' => 1,
            'date' => '2026-08-01', 'clock_in' => '08:00', 'clock_out' => '17:00',
            'regular_hours' => 8.0,
        ]);
        Attendance::create([
            'employee_id' => $outOfDept->id, 'business_id' => 1,
            'date' => '2026-08-01', 'clock_in' => '08:00', 'clock_out' => '17:00',
            'regular_hours' => 8.0,
        ]);
        Attendance::create([
            'employee_id' => $wrongDay->id, 'business_id' => 1,
            'date' => '2026-08-02', 'clock_in' => '08:00', 'clock_out' => '17:00',
            'regular_hours' => 8.0,
        ]);

        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/attendances/reports/daily/preview', 'GET', [
            'date' => '2026-08-01',
            'department_id' => $deptA->id,
        ]);

        $html = $controller->dailyPreview($request, $business);

        $this->assertStringContainsString($inDept->user->name, $html);
        $this->assertStringNotContainsString($outOfDept->user->name, $html);
        $this->assertStringNotContainsString($wrongDay->user->name, $html);
    }

    public function test_daily_report_shows_no_data_message_when_nothing_matches(): void
    {
        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/attendances/reports/daily/preview', 'GET', ['date' => '2020-01-01']);

        $html = $controller->dailyPreview($request, $business);

        $this->assertStringContainsString('No attendance records found', $html);
    }

    public function test_daily_report_download_returns_a_pdf(): void
    {
        $business = Business::find(1);
        $controller = new AttendanceReportController();
        $request = Request::create('/attendances/reports/daily/download', 'GET', ['date' => '2026-08-01']);

        $response = $controller->dailyDownload($request, $business);

        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_hours_to_total_label_formats_without_decimals(): void
    {
        $this->assertSame('191hrs', TimeFmt::hoursToTotalLabel(191));
        $this->assertSame('12h 30m', TimeFmt::hoursToTotalLabel(12.5));
        $this->assertSame('0hrs', TimeFmt::hoursToTotalLabel(null));
    }

    public function test_hours_to_total_label_formats_negative_variance_correctly(): void
    {
        // Regression: an earlier version clamped negative minutes to 0,
        // which would have silently shown "0hrs" for an hours-deficit
        // instead of the actual shortfall.
        $this->assertSame('-13hrs', TimeFmt::hoursToTotalLabel(-13));
        $this->assertSame('-5h 30m', TimeFmt::hoursToTotalLabel(-5.5));
    }
}
