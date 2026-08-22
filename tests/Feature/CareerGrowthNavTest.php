<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use App\Models\EmploymentDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Career Growth" - a new business-wide nav item under Employee Management.
 * Career events (promotions/salary increments) previously only existed
 * per-employee (Career History tab on the employee detail page) with no
 * standalone list anywhere in the nav.
 */
class CareerGrowthNavTest extends TestCase
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

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));
        return $user;
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id, 'business_id' => $this->business->id, 'department_id' => 1,
            'employee_code' => 'CAREER-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create(['employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1, 'employment_date' => '2020-01-01', 'employment_term' => 'permanent']);

        return $employee;
    }

    public function test_career_growth_index_page_renders(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.career-growth.index', $this->business->slug))
            ->assertOk()
            ->assertSee('Career Growth');
    }

    public function test_career_growth_fetch_lists_events_business_wide(): void
    {
        $admin = $this->makeAdminUser();
        $employeeA = $this->makeEmployee();
        $employeeB = $this->makeEmployee();

        $promotion = EmployeeCareerEvent::create([
            'business_id' => $this->business->id, 'employee_id' => $employeeA->id,
            'event_type' => 'promotion', 'effective_date' => now()->subDay(),
            'reason' => 'Excellent performance', 'status' => 'applied',
            'issued_by_id' => $admin->id,
        ]);
        $increment = EmployeeCareerEvent::create([
            'business_id' => $this->business->id, 'employee_id' => $employeeB->id,
            'event_type' => 'salary_increment', 'effective_date' => now()->addMonth(),
            'old_salary' => 50000, 'new_salary' => 60000,
            'reason' => 'Annual review', 'status' => 'pending',
            'issued_by_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->getJson(route('business.career-growth.fetch', $this->business->slug));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($promotion->id));
        $this->assertTrue($ids->contains($increment->id));
    }

    public function test_career_growth_fetch_filters_by_event_type(): void
    {
        $admin = $this->makeAdminUser();
        $employee = $this->makeEmployee();

        $promotion = EmployeeCareerEvent::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'event_type' => 'promotion', 'effective_date' => now(),
            'reason' => 'Promotion reason', 'status' => 'applied',
            'issued_by_id' => $admin->id,
        ]);
        $increment = EmployeeCareerEvent::create([
            'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'event_type' => 'salary_increment', 'effective_date' => now(),
            'old_salary' => 50000, 'new_salary' => 55000,
            'reason' => 'Increment reason', 'status' => 'applied',
            'issued_by_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $this->business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->getJson(route('business.career-growth.fetch', $this->business->slug) . '?event_type=promotion');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($promotion->id));
        $this->assertFalse($ids->contains($increment->id));
    }
}
