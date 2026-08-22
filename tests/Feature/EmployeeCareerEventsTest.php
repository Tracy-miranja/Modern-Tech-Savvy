<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeCareerEventController;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use App\Models\EmployeePaymentDetail;
use App\Models\EmploymentDetail;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\EmployeeCareerEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Employee career history - promotions and salary increments. Covers the
 * user's explicit design choices: a dedicated structured model (not a
 * free-text log), one-step HR-recorded, and a future effective_date that
 * defers the actual change until the scheduled command applies it.
 */
class EmployeeCareerEventsTest extends TestCase
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

    private function makeEmployeeWithDetails(int $departmentId, int $jobCategoryId, float $salary): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'employee_code' => 'CAR-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId,
            'job_category_id' => $jobCategoryId,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        EmployeePaymentDetail::create([
            'employee_id' => $employee->id,
            'basic_salary' => $salary,
            'currency' => 'KES',
            'payment_mode' => 'bank',
        ]);

        return $employee->fresh();
    }

    private function actingAsAdmin(Business $business, User $admin)
    {
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);
    }

    // ---- Immediate vs deferred application --------------------------

    public function test_recording_a_salary_increment_with_todays_date_applies_immediately(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $employee = $this->makeEmployeeWithDetails(1, 1, 50000);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();
        $request = Request::create('/x', 'POST', [
            'event_type' => 'salary_increment',
            'effective_date' => now()->toDateString(),
            'new_salary' => 65000,
            'reason' => 'CAR Annual review raise',
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->store($request, $business, $employee)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $event = EmployeeCareerEvent::where('employee_id', $employee->id)->first();
        $this->assertSame('applied', $event->status);
        $this->assertNotNull($event->applied_at);
        $this->assertSame('65000.00', $employee->paymentDetails->fresh()->basic_salary);
    }

    /**
     * department_id is a real column directly on employees (unlike
     * job_category_id, which only ever lives on EmploymentDetail) and is
     * what every department-scoped query/filter in the app actually
     * reads. apply() used to only update EmploymentDetail's copy, so a
     * promotion's job category change took effect everywhere but its
     * department change was invisible outside the one accessor path that
     * happened to fall back to EmploymentDetail.
     */
    public function test_an_applied_promotion_updates_the_employees_own_department_id_not_just_employment_details(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $oldDepartment = Department::create(['business_id' => 1, 'name' => 'CAR Old Department ' . uniqid()]);
        $newDepartment = Department::create(['business_id' => 1, 'name' => 'CAR New Department ' . uniqid()]);
        $oldCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR Old Role ' . uniqid()]);
        $newCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR New Role ' . uniqid()]);
        $employee = $this->makeEmployeeWithDetails($oldDepartment->id, $oldCategory->id, 50000);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();
        $request = Request::create('/x', 'POST', [
            'event_type' => 'promotion',
            'effective_date' => now()->toDateString(),
            'new_job_category_id' => $newCategory->id,
            'new_department_id' => $newDepartment->id,
            'reason' => 'CAR Promoted with department move',
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->store($request, $business, $employee)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $event = EmployeeCareerEvent::where('employee_id', $employee->id)->first();
        $this->assertSame('applied', $event->status);

        $this->assertSame($newCategory->id, $employee->employmentDetails->fresh()->job_category_id);
        $this->assertSame($newDepartment->id, $employee->employmentDetails->fresh()->department_id);
        $this->assertSame($newDepartment->id, $employee->fresh()->department_id, "The employee's own department_id column must update too - it's what department-scoped queries actually read.");
    }

    public function test_recording_a_promotion_with_a_future_date_stays_pending_and_does_not_touch_current_state(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $oldCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR Old Role ' . uniqid()]);
        $newCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR New Role ' . uniqid()]);
        $employee = $this->makeEmployeeWithDetails(1, $oldCategory->id, 50000);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();
        $request = Request::create('/x', 'POST', [
            'event_type' => 'promotion',
            'effective_date' => now()->addMonth()->toDateString(),
            'new_job_category_id' => $newCategory->id,
            'reason' => 'CAR Promoted next month',
        ]);
        $request->setUserResolver(fn () => $admin);
        $controller->store($request, $business, $employee)->toResponse($request);

        $event = EmployeeCareerEvent::where('employee_id', $employee->id)->first();
        $this->assertSame('pending', $event->status);
        $this->assertNull($event->applied_at);
        $this->assertSame($oldCategory->id, $employee->employmentDetails->fresh()->job_category_id, 'A future-dated event must not touch current state yet.');
    }

    // ---- Old-value capture ---------------------------------------------

    public function test_old_values_are_captured_automatically_from_current_state(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $oldCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR Old ' . uniqid()]);
        $newCategory = JobCategory::create(['business_id' => 1, 'name' => 'CAR New ' . uniqid()]);
        $employee = $this->makeEmployeeWithDetails(1, $oldCategory->id, 40000);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();
        $request = Request::create('/x', 'POST', [
            'event_type' => 'promotion',
            'effective_date' => now()->toDateString(),
            'new_job_category_id' => $newCategory->id,
            'reason' => 'CAR Promotion',
        ]);
        $request->setUserResolver(fn () => $admin);
        $controller->store($request, $business, $employee)->toResponse($request);

        $event = EmployeeCareerEvent::where('employee_id', $employee->id)->first();
        $this->assertSame($oldCategory->id, $event->old_job_category_id);
        $this->assertSame($newCategory->id, $event->new_job_category_id);
        $this->assertSame($newCategory->id, $employee->employmentDetails->fresh()->job_category_id);
    }

    // ---- Scheduled command ------------------------------------------

    public function test_scheduled_command_applies_due_pending_events_but_not_future_ones(): void
    {
        $employeeDue = $this->makeEmployeeWithDetails(1, 1, 30000);
        $employeeNotDue = $this->makeEmployeeWithDetails(1, 1, 30000);
        $admin = User::factory()->create();

        $dueEvent = EmployeeCareerEvent::create([
            'business_id' => 1, 'employee_id' => $employeeDue->id, 'event_type' => 'salary_increment',
            'effective_date' => now()->toDateString(), 'old_salary' => 30000, 'new_salary' => 40000,
            'reason' => 'CAR Due', 'status' => 'pending', 'issued_by_id' => $admin->id,
        ]);
        $notDueEvent = EmployeeCareerEvent::create([
            'business_id' => 1, 'employee_id' => $employeeNotDue->id, 'event_type' => 'salary_increment',
            'effective_date' => now()->addMonth()->toDateString(), 'old_salary' => 30000, 'new_salary' => 45000,
            'reason' => 'CAR Not due', 'status' => 'pending', 'issued_by_id' => $admin->id,
        ]);

        $applied = app(EmployeeCareerEventService::class)->applyDuePendingEvents();

        $this->assertSame(1, $applied);
        $this->assertSame('applied', $dueEvent->fresh()->status);
        $this->assertSame('40000.00', $employeeDue->paymentDetails->fresh()->basic_salary);
        $this->assertSame('pending', $notDueEvent->fresh()->status);
        $this->assertSame('30000.00', $employeeNotDue->paymentDetails->fresh()->basic_salary, 'A not-yet-due event must not touch current state.');
    }

    // ---- Guard rails -----------------------------------------------------

    public function test_a_pending_event_can_be_removed_but_an_applied_one_cannot(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $employee = $this->makeEmployeeWithDetails(1, 1, 30000);

        $pending = EmployeeCareerEvent::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'event_type' => 'salary_increment',
            'effective_date' => now()->addMonth()->toDateString(), 'old_salary' => 30000, 'new_salary' => 35000,
            'reason' => 'CAR Pending', 'status' => 'pending', 'issued_by_id' => $admin->id,
        ]);
        $applied = EmployeeCareerEvent::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'event_type' => 'salary_increment',
            'effective_date' => now()->toDateString(), 'old_salary' => 25000, 'new_salary' => 30000,
            'reason' => 'CAR Applied', 'status' => 'applied', 'applied_at' => now(), 'issued_by_id' => $admin->id,
        ]);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();

        $pendingResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $employee, $pending)->toResponse(Request::create('/x'));
        $this->assertSame(200, $pendingResponse->getStatusCode());
        $this->assertNull(EmployeeCareerEvent::find($pending->id));

        $appliedResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $employee, $applied)->toResponse(Request::create('/x'));
        $this->assertSame(400, $appliedResponse->getStatusCode());
        $this->assertNotNull(EmployeeCareerEvent::find($applied->id));
    }

    public function test_salary_increment_requires_a_new_salary_amount(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $employee = $this->makeEmployeeWithDetails(1, 1, 30000);

        $this->actingAsAdmin($business, $admin);
        $controller = new EmployeeCareerEventController();
        $request = Request::create('/x', 'POST', [
            'event_type' => 'salary_increment',
            'effective_date' => now()->toDateString(),
            'reason' => 'CAR Missing amount',
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->store($request, $business, $employee)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, EmployeeCareerEvent::where('employee_id', $employee->id)->count());
    }
}
