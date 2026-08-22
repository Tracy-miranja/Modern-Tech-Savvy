<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "View employee details" 500'd for every single employee, always -
 * resources/views/employees/_view.blade.php's career-events destroy URL
 * used @json(route(..., [3-element array])), which has exactly 4
 * top-level commas. Laravel's @json() Blade directive silently truncates
 * anything past the 3rd top-level comma (compiles via a naive
 * explode(',', ...), no paren/bracket awareness - see
 * feedback_blade_json_directive_bug memory, first found 2026-07-14 in a
 * different file) - the compiled PHP ends up with a mismatched '[' and
 * fails at render time with "Unclosed '[' does not match ')'", not at
 * Blade-compile time, which is why `view:cache`/`compileString()` checks
 * don't catch it. Fixed by bypassing the directive with json_encode()
 * directly (Laravel's own default @json() flags).
 */
class EmployeeViewDetailsTest extends TestCase
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

    public function test_view_employee_details_renders_successfully(): void
    {
        $business = Business::find(1); // amsol
        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id, 'business_id' => $business->id, 'department_id' => 1,
            'employee_code' => 'VIEWDETAIL-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create([
            'employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1,
            'employment_date' => '2020-01-01', 'employment_term' => 'permanent',
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new EmployeeController();
        $request = Request::create('/employees/view', 'POST', ['employee_id' => $employee->id]);
        $response = $controller->view($request);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), $body['message'] ?? '');
        $this->assertStringContainsString($employee->employee_code, $body['data']);
    }
}
