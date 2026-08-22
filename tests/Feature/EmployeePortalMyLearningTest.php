<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "My Learning" - a new employee-portal page (business-employee side)
 * showing only the logged-in employee's own course enrollments, following
 * the same portal-parity pattern as My Assets/My Projects. The employee
 * portal previously had no Learning Management entry point at all despite
 * the admin side having a full module for it.
 */
class EmployeePortalMyLearningTest extends TestCase
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

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']));

        $employee = Employee::create([
            'user_id' => $user->id, 'business_id' => $this->business->id, 'department_id' => 1,
            'employee_code' => 'MYLEARN-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create(['employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1, 'employment_date' => '2020-01-01', 'employment_term' => 'permanent']);

        return [$user->fresh(), $employee->fresh()];
    }

    public function test_my_learning_page_shows_only_the_logged_in_employees_own_enrollments(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $myCourse = Course::create([
            'business_id' => $this->business->id, 'title' => 'My Onboarding Course ' . uniqid(),
            'provider' => 'Internal', 'duration_hours' => 4, 'status' => 'active',
        ]);
        CourseEnrollment::create([
            'business_id' => $this->business->id, 'course_id' => $myCourse->id, 'employee_id' => $employee->id,
            'status' => 'completed', 'enrolled_at' => now()->subMonth(), 'completed_at' => now(),
            'certificate_issued' => true, 'certificate_number' => 'CERT-' . uniqid(),
        ]);

        $othersCourse = Course::create([
            'business_id' => $this->business->id, 'title' => 'Someone Elses Course ' . uniqid(),
            'provider' => 'Internal', 'duration_hours' => 2, 'status' => 'active',
        ]);
        CourseEnrollment::create([
            'business_id' => $this->business->id, 'course_id' => $othersCourse->id, 'employee_id' => $otherEmployee->id,
            'status' => 'enrolled', 'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.learning.index', $this->business->slug));

        $response->assertOk();
        $response->assertSee($myCourse->title);
        $response->assertSee('CERT-', false);
        $response->assertDontSee($othersCourse->title);
    }

    public function test_my_learning_page_shows_empty_state_with_no_enrollments(): void
    {
        [$user] = $this->makeEmployeeUser();

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.learning.index', $this->business->slug));

        $response->assertOk();
        $response->assertSee('no courses or training assigned');
    }
}
