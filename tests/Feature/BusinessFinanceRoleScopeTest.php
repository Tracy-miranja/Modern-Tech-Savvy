<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * business-finance used to be listed in every one of the 11 module route
 * gates - identical reach to business-hr, despite the name implying a
 * finance-only scope. Narrowed to the modules a finance/accounts person
 * actually needs (payroll - the core of the role, plus attendance/leave/
 * employee data that payroll runs on) and removed from the rest
 * (organization structure, performance, assets, learning, projects,
 * recruitment, CRM).
 */
class BusinessFinanceRoleScopeTest extends TestCase
{
    private Business $business;

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

        $this->business = Business::find(1); // amsol
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function actingAsBusinessFinance(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'business-finance', 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $this->business->id,
            'department_id' => 1,
            'employee_code' => 'BFRS-' . uniqid(),
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

        session([
            'active_business_slug' => $this->business->slug,
            'active_role' => 'business-finance',
            '2fa_verified' => true,
        ]);
        $this->actingAs($user);

        return $user;
    }

    public function test_business_finance_can_still_reach_payroll_attendance_leave_and_employees(): void
    {
        $this->actingAsBusinessFinance();

        $this->get(route('business.payroll.index', $this->business->slug))->assertOk();
        $this->get(route('business.attendances.index', $this->business->slug))->assertOk();
        $this->get(route('business.leave.index', $this->business->slug))->assertOk();
        $this->get(route('business.employees.index', $this->business->slug))->assertOk();
    }

    public function test_business_finance_is_blocked_from_non_finance_modules(): void
    {
        $this->actingAsBusinessFinance();

        $this->getJson(route('business.organization-structure.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.performance.cycles.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.assets.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.learning.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.projects.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.recruitment.jobs.index', $this->business->slug))->assertStatus(403);
        $this->getJson(route('business.crm.contacts.index', $this->business->slug))->assertStatus(403);
    }
}
