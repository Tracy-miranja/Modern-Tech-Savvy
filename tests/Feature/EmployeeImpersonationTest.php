<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for EmployeeController::loginAsEmployee() /
 * stopImpersonatingEmployee() - a real Auth session swap (unlike the
 * business-level impersonation in ClientController, which only flips
 * active_business_slug/active_role without ever changing who's logged in).
 * Exercised through real HTTP requests so the actual auth guard swap is
 * what gets verified, not just the controller's return value.
 */
class EmployeeImpersonationTest extends TestCase
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

    private function makeEmployeeWithRole(string $role): Employee
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'IMP-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return $employee->fresh();
    }

    public function test_hr_can_log_in_as_a_plain_employee(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(Role::firstOrCreate(['name' => 'business-hr', 'guard_name' => 'web']));
        $employee = $this->makeEmployeeWithRole('business-employee');
        $business = Business::find(1);

        $response = $this->actingAs($hr)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-hr'])
            ->post(route('employees.login-as', $employee->id));

        $response->assertOk();
        $response->assertJsonPath('data.redirect_url', route('myaccount.index', ['business' => $business->slug]));

        $this->assertSame($employee->user_id, auth()->id());
        $this->assertSame('business-employee', session('active_role'));
        $this->assertSame($business->slug, session('active_business_slug'));
        $this->assertSame($hr->id, session('impersonating_original_user_id'));
    }

    public function test_hr_cannot_log_in_as_another_admin(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(Role::firstOrCreate(['name' => 'business-hr', 'guard_name' => 'web']));
        $otherAdmin = $this->makeEmployeeWithRole('business-admin');
        $business = Business::find(1);

        $response = $this->actingAs($hr)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-hr'])
            ->post(route('employees.login-as', $otherAdmin->id));

        $response->assertForbidden();
        $this->assertSame($hr->id, auth()->id());
    }

    public function test_plain_employee_cannot_trigger_login_as(): void
    {
        $employee = $this->makeEmployeeWithRole('business-employee');
        $target = $this->makeEmployeeWithRole('business-employee');
        $business = Business::find(1);

        $response = $this->actingAs($employee->user)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-employee'])
            ->post(route('employees.login-as', $target->id));

        $response->assertForbidden();
    }

    public function test_stop_impersonating_restores_the_original_admin_session(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(Role::firstOrCreate(['name' => 'business-hr', 'guard_name' => 'web']));
        $employee = $this->makeEmployeeWithRole('business-employee');
        $business = Business::find(1);

        $this->actingAs($hr)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-hr'])
            ->post(route('employees.login-as', $employee->id));

        $this->assertSame($employee->user_id, auth()->id());

        $response = $this->post(route('employees.stop-impersonating'));

        $response->assertOk();
        $this->assertSame($hr->id, auth()->id());
        $this->assertSame('business-hr', session('active_role'));
        $this->assertSame($business->slug, session('active_business_slug'));
        $this->assertNull(session('impersonating_original_user_id'));
    }
}
