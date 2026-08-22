<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OffboardingReportController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\OffboardingChecklist;
use App\Models\OffboardingTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Offboarding (GUIDE plan Phase 4): a checklist is auto-created exactly
 * once per termination action, seeded with the 5 stock tasks, and the
 * checklist auto-completes once every task is done.
 */
class OffboardingTest extends TestCase
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

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'OFF-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh()];
    }

    private function terminate(User $hrUser, Employee $employee, Business $business, string $reason = 'OFF Test termination')
    {
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new EmployeeController();
        $request = Request::create('/contracts/store', 'POST', [
            'employee_id' => $employee->id,
            'action_type' => 'termination',
            'reason' => $reason,
            'action_date' => now()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $hrUser);

        return $controller->storeContractAction($request)->toResponse($request);
    }

    // ---- Auto-creation ---------------------------------------------------

    public function test_terminating_an_employee_auto_creates_a_checklist_with_the_five_stock_tasks(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $response = $this->terminate($hrUser, $employee, $business);
        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();
        $this->assertNotNull($checklist);
        $this->assertSame('in_progress', $checklist->status);
        $this->assertSame(5, $checklist->tasks()->count());
        $this->assertEqualsCanonicalizing(
            ['asset_return', 'access_revocation', 'exit_interview', 'final_settlement', 'knowledge_handover'],
            $checklist->tasks()->pluck('task_key')->all()
        );
    }

    public function test_a_checklist_is_created_exactly_once_per_termination_not_duplicated(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $this->terminate($hrUser, $employee, $business);

        $this->assertSame(1, OffboardingChecklist::where('employee_id', $employee->id)->count());
    }

    public function test_suspension_does_not_create_an_offboarding_checklist(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new EmployeeController();
        $request = Request::create('/contracts/store', 'POST', [
            'employee_id' => $employee->id,
            'action_type' => 'suspension',
            'reason' => 'OFF Suspension test',
            'action_date' => now()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $hrUser);
        $controller->storeContractAction($request)->toResponse($request);

        $this->assertSame(0, OffboardingChecklist::where('employee_id', $employee->id)->count());
    }

    // ---- Task updates + auto-complete -----------------------------------

    public function test_marking_every_task_done_flips_the_checklist_to_completed(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $this->terminate($hrUser, $employee, $business);

        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);
        $controller = new OffboardingController();

        foreach ($checklist->tasks as $task) {
            $request = Request::create("/offboarding/{$checklist->id}/tasks/{$task->id}", 'POST', ['is_done' => true]);
            $request->setUserResolver(fn () => $hrUser);
            $response = $controller->updateTask($request, $business, $checklist->id, $task)->toResponse($request);
            $this->assertSame(200, $response->getStatusCode());
        }

        $checklist->refresh();
        $this->assertSame('completed', $checklist->status);
        $this->assertNotNull($checklist->completed_at);
        $this->assertSame(100, $checklist->progressPercent());
    }

    public function test_unmarking_a_task_on_a_completed_checklist_reopens_it(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $this->terminate($hrUser, $employee, $business);

        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();
        $checklist->tasks()->update(['is_done' => true]);
        $checklist->refreshStatus();
        $this->assertSame('completed', $checklist->fresh()->status);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);
        $controller = new OffboardingController();
        $firstTask = $checklist->tasks()->first();

        $request = Request::create("/offboarding/{$checklist->id}/tasks/{$firstTask->id}", 'POST', ['is_done' => false]);
        $request->setUserResolver(fn () => $hrUser);
        $controller->updateTask($request, $business, $checklist->id, $firstTask)->toResponse($request);

        $checklist->refresh();
        $this->assertSame('in_progress', $checklist->status);
        $this->assertNull($checklist->completed_at);
    }

    // ---- Custom tasks + scoping -------------------------------------------

    public function test_a_custom_task_can_be_added_and_removed(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $this->terminate($hrUser, $employee, $business);
        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);
        $controller = new OffboardingController();

        $storeRequest = Request::create("/offboarding/{$checklist->id}/tasks", 'POST', ['name' => 'Return company vehicle']);
        $storeRequest->setUserResolver(fn () => $hrUser);
        $storeResponse = $controller->storeTask($storeRequest, $business, $checklist->id)->toResponse($storeRequest);
        $this->assertSame(201, $storeResponse->getStatusCode());
        $this->assertSame(6, $checklist->tasks()->count());

        $customTask = OffboardingTask::where('checklist_id', $checklist->id)->where('name', 'Return company vehicle')->first();
        $destroyRequest = Request::create("/offboarding/{$checklist->id}/tasks/{$customTask->id}", 'DELETE');
        $destroyRequest->setUserResolver(fn () => $hrUser);
        $destroyResponse = $controller->destroyTask($destroyRequest, $business, $checklist->id, $customTask)->toResponse($destroyRequest);
        $this->assertSame(200, $destroyResponse->getStatusCode());
        $this->assertSame(5, $checklist->tasks()->count());
    }

    public function test_a_task_cannot_be_updated_through_a_different_businesss_checklist(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $this->terminate($hrUser, $employee, $business);
        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();
        $task = $checklist->tasks()->first();

        $otherBusinessId = Business::where('id', '!=', 1)->value('id');
        $otherBusiness = Business::find($otherBusinessId);

        session(['active_business_slug' => $otherBusiness->slug]);
        $this->actingAs($hrUser);
        $controller = new OffboardingController();

        $request = Request::create("/offboarding/{$checklist->id}/tasks/{$task->id}", 'POST', ['is_done' => true]);
        $request->setUserResolver(fn () => $hrUser);
        $response = $controller->updateTask($request, $otherBusiness, $checklist->id, $task)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertFalse($task->fresh()->is_done);
    }

    // ---- Reports ---------------------------------------------------------

    public function test_status_report_shows_the_checklist_and_clearance_summary_lists_every_task(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);
        $this->terminate($hrUser, $employee, $business);
        $checklist = OffboardingChecklist::where('employee_id', $employee->id)->first();

        $controller = new OffboardingReportController();

        $statusRequest = Request::create('/x', 'GET', [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);
        $statusHtml = $controller->statusPreview($statusRequest, $business);
        // htmlspecialchars() because Faker names can contain an apostrophe
        // (e.g. "O'Hara"), which Blade escapes to &#039; in the rendered output.
        $this->assertStringContainsString(htmlspecialchars(optional($employee->user)->name), $statusHtml);

        $clearanceHtml = $controller->clearanceSummaryPreview(Request::create('/x'), $business, $checklist);
        foreach ($checklist->tasks as $task) {
            // htmlspecialchars() because task names like "System & Access
            // Revocation" get escaped to "&amp;" in the rendered output.
            $this->assertStringContainsString(htmlspecialchars($task->name), $clearanceHtml);
        }
    }
}
