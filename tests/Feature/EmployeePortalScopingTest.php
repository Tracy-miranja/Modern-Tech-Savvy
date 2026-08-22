<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Business;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Employee-portal cleanup (see GUIDE plan): an employee-portal request must
 * never be able to pull another employee's or another department's data,
 * no matter what query params it sends - "no way an employee can be
 * filtering employees or departments as they only see what's theirs".
 */
class EmployeePortalScopingTest extends TestCase
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

    /** @return array{0: User, 1: Employee} */
    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'EPS-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return [$user, $employee->fresh()];
    }

    private function sessionFor(Business $business): array
    {
        return [
            'active_business_slug' => $business->slug,
            'active_role' => 'business-employee',
            '2fa_verified' => true,
        ];
    }

    // ---- Attendance --------------------------------------------------

    public function test_my_daily_attendance_report_ignores_the_employee_ids_filter_and_only_returns_the_callers_own_data(): void
    {
        $business = Business::find(1);
        [$me, $myEmployee] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $today = now()->toDateString();
        Attendance::create(['employee_id' => $myEmployee->id, 'business_id' => 1, 'date' => $today, 'regular_hours' => 8]);
        Attendance::create(['employee_id' => $otherEmployee->id, 'business_id' => 1, 'date' => $today, 'regular_hours' => 8]);

        // Deliberately try to smuggle another employee's id through the
        // filter param a malicious/curious employee could still send by
        // hand - it must be discarded server-side, not just hidden in the UI.
        $url = route('myaccount.attendances.reports.daily.preview', $business->slug)
            . '?' . http_build_query(['employee_ids' => [$otherEmployee->id], 'date' => $today]);

        $response = $this->actingAs($me)->withSession($this->sessionFor($business))->get($url);

        $response->assertOk();
        $html = $response->getContent();
        // htmlspecialchars() because Faker names can contain an apostrophe
        // (e.g. "D'Amore"), which Blade escapes to &#039; in the rendered output.
        $this->assertStringContainsString(htmlspecialchars($myEmployee->user->name), $html);
        $this->assertStringNotContainsString(htmlspecialchars($otherEmployee->user->name), $html);
    }

    public function test_my_monthly_attendance_report_ignores_the_department_filter_and_stays_scoped_to_the_caller(): void
    {
        $business = Business::find(1);
        [$me, $myEmployee] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $monthStart = now()->startOfMonth()->toDateString();
        Attendance::create(['employee_id' => $myEmployee->id, 'business_id' => 1, 'date' => $monthStart, 'regular_hours' => 8, 'is_absent' => false]);
        Attendance::create(['employee_id' => $otherEmployee->id, 'business_id' => 1, 'date' => $monthStart, 'regular_hours' => 8, 'is_absent' => false]);

        // A department_id the caller doesn't even belong to - must be
        // ignored, not used to widen or redirect the query.
        $url = route('myaccount.attendances.reports.monthly.preview', $business->slug)
            . '?' . http_build_query(['department_id' => 999999]);

        $response = $this->actingAs($me)->withSession($this->sessionFor($business))->get($url);

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString(htmlspecialchars($myEmployee->user->name), $html);
        $this->assertStringNotContainsString(htmlspecialchars($otherEmployee->user->name), $html);
    }

    // ---- Disciplinary --------------------------------------------------

    public function test_my_disciplinary_index_only_lists_the_callers_own_warnings(): void
    {
        $business = Business::find(1);
        [$me, $myEmployee] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $mine = Warning::create([
            'employee_id' => $myEmployee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'severity' => 'low', 'issue_date' => now(), 'reason' => 'EPS Mine ' . uniqid(), 'status' => 'active',
            'issued_by' => $me->id,
        ]);
        $notMine = Warning::create([
            'employee_id' => $otherEmployee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'severity' => 'low', 'issue_date' => now(), 'reason' => 'EPS Not Mine ' . uniqid(), 'status' => 'active',
            'issued_by' => $me->id,
        ]);

        $response = $this->actingAs($me)
            ->withSession($this->sessionFor($business))
            ->get(route('myaccount.disciplinary.index', $business->slug));

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringContainsString($mine->reason, $html);
        $this->assertStringNotContainsString($notMine->reason, $html);
    }

    public function test_acknowledge_cannot_acknowledge_another_employees_warning(): void
    {
        $business = Business::find(1);
        [$me] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $notMine = Warning::create([
            'employee_id' => $otherEmployee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'severity' => 'low', 'issue_date' => now(), 'reason' => 'EPS Ack ' . uniqid(), 'status' => 'active',
            'issued_by' => $me->id,
        ]);

        // WarningController::acknowledge already scopes by employee_id - this
        // asserts that protection actually holds: rejected, not silently OK.
        $this->actingAs($me)
            ->withSession($this->sessionFor($business))
            ->post(route('myaccount.disciplinary.acknowledge', [$business->slug, $notMine->id]))
            ->assertStatus(400);

        $this->assertNull($notMine->fresh()->acknowledged_at, 'A warning belonging to a different employee must never be acknowledgeable by this caller.');
    }
}
