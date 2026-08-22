<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectTaskCategoryController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectTaskStatusController;
use App\Http\Controllers\ProjectTimeLogController;
use App\Mail\ProjectTaskDueReminderMail;
use App\Mail\ProjectTaskOverdueMail;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\ProjectTaskStatus;
use App\Models\ProjectTimeLog;
use App\Models\User;
use App\Services\Projects\ProjectSchedulerService;
use App\Services\ProjectTaskStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Project Management - Projects, the Kanban board (drag-drop persistence
 * via reorder()), Resource Allocation, Time Tracking, Team Collaboration
 * (comments), Settings (configurable statuses/categories), reports, and
 * the due/overdue reminder scheduler. Mirrors the LearningManagement*
 * test files' isolation pattern.
 */
class ProjectManagementTest extends TestCase
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

        DB::table('business_modules')->where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function actingAsAdmin(Business $business): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return $admin;
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'PMT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();
    }

    private function seedStatuses(Business $business): void
    {
        app(ProjectTaskStatusService::class)->ensureSeeded($business);
    }

    // ---- Module gating (HTTP-level, mirrors ModuleGatingAndAssetsTest) ---

    public function test_middleware_allows_access_when_business_never_selected_modules(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.projects.index', $business->slug));

        $response->assertOk();
    }

    public function test_middleware_redirects_when_business_has_other_modules_but_not_project_management(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $other = Module::create(['name' => 'PMT Other Module ' . uniqid(), 'description' => 'x', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false, 'features' => []]);
        $business->modules()->attach($other->id, ['is_active' => true]);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.projects.index', $business->slug));

        $response->assertRedirect(route('business.index', $business->slug));
    }

    // ---- Projects ----------------------------------------------------------

    public function test_a_project_can_be_created_updated_and_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new ProjectController();

        $storeResponse = $controller->store(Request::create('/x', 'POST', ['name' => 'PMT Website Revamp']), $business)->toResponse(Request::create('/x'));
        $this->assertSame(201, $storeResponse->getStatusCode(), $storeResponse->getContent());
        $project = Project::where('business_id', 1)->where('name', 'PMT Website Revamp')->first();
        $this->assertSame('planning', $project->status);

        $updateResponse = $controller->update(Request::create('/x', 'POST', ['status' => 'active']), $business, $project)->toResponse(Request::create('/x'));
        $this->assertSame(200, $updateResponse->getStatusCode());
        $this->assertSame('active', $project->fresh()->status);

        $destroyResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $project)->toResponse(Request::create('/x'));
        $this->assertSame(200, $destroyResponse->getStatusCode());
        $this->assertNull(Project::find($project->id));
    }

    public function test_a_project_with_tasks_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Has Tasks', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();
        ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id, 'title' => 'A task', 'position' => 1]);

        $controller = new ProjectController();
        $response = $controller->destroy(Request::create('/x', 'DELETE'), $business, $project)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(Project::find($project->id));
    }

    // ---- Kanban board / tasks ----------------------------------------------

    public function test_creating_a_task_defaults_to_the_first_status_and_appends_position(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Board Defaults', 'status' => 'active']);
        $firstStatus = ProjectTaskStatus::where('business_id', 1)->ordered()->first();

        $controller = new ProjectTaskController();
        $response = $controller->store(Request::create('/x', 'POST', ['title' => 'PMT Task 1']), $business, $project)->toResponse(Request::create('/x'));

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $task = ProjectTask::where('project_id', $project->id)->first();
        $this->assertSame($firstStatus->id, $task->project_task_status_id);
        $this->assertSame(1, $task->position);
    }

    public function test_reorder_moves_a_task_to_a_new_column_and_stamps_completed_at_when_done(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Reorder', 'status' => 'active']);
        $todo = ProjectTaskStatus::where('business_id', 1)->where('slug', 'to-do')->first();
        $done = ProjectTaskStatus::where('business_id', 1)->where('slug', 'done')->first();
        $task = ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $todo->id, 'title' => 'PMT Move Me', 'position' => 1]);

        $controller = new ProjectTaskController();
        $request = Request::create('/x', 'POST', [
            'columns' => [
                ['status_id' => $todo->id, 'task_ids' => []],
                ['status_id' => $done->id, 'task_ids' => [$task->id]],
            ],
        ]);
        $response = $controller->reorder($request, $business, $project)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $task->refresh();
        $this->assertSame($done->id, $task->project_task_status_id);
        $this->assertNotNull($task->completed_at);
    }

    public function test_reorder_clears_completed_at_when_moved_out_of_a_done_column(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Reopen', 'status' => 'active']);
        $todo = ProjectTaskStatus::where('business_id', 1)->where('slug', 'to-do')->first();
        $done = ProjectTaskStatus::where('business_id', 1)->where('slug', 'done')->first();
        $task = ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $done->id, 'title' => 'PMT Reopen Me', 'position' => 1, 'completed_at' => now()]);

        $controller = new ProjectTaskController();
        $request = Request::create('/x', 'POST', [
            'columns' => [
                ['status_id' => $todo->id, 'task_ids' => [$task->id]],
                ['status_id' => $done->id, 'task_ids' => []],
            ],
        ]);
        $controller->reorder($request, $business, $project)->toResponse($request);

        $this->assertNull($task->fresh()->completed_at);
    }

    // ---- Settings: statuses / categories -----------------------------------

    public function test_the_last_active_done_status_cannot_be_disabled_or_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $done = ProjectTaskStatus::where('business_id', 1)->where('slug', 'done')->first();

        $controller = new ProjectTaskStatusController();
        $service = app(ProjectTaskStatusService::class);

        $updateResponse = $controller->update(Request::create('/x', 'POST', ['is_active' => false]), $business, $done, $service)->toResponse(Request::create('/x'));
        $this->assertSame(400, $updateResponse->getStatusCode());

        $destroyResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $done, $service)->toResponse(Request::create('/x'));
        $this->assertSame(400, $destroyResponse->getStatusCode());
        $this->assertNotNull(ProjectTaskStatus::find($done->id));
    }

    public function test_a_task_category_in_use_cannot_be_deleted(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Category Guard', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();

        $categoryController = new ProjectTaskCategoryController();
        $categoryController->store(Request::create('/x', 'POST', ['name' => 'PMT Bug']), $business);
        $category = \App\Models\ProjectTaskCategory::where('business_id', 1)->where('name', 'PMT Bug')->first();

        ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id, 'project_task_category_id' => $category->id, 'title' => 'PMT Bug Task', 'position' => 1]);

        $response = $categoryController->destroy(Request::create('/x', 'DELETE'), $business, $category)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(\App\Models\ProjectTaskCategory::find($category->id));
    }

    /**
     * Settings redesign: up/down reordering, editing (including color), and
     * an explicit "level" field on the Add/Edit form - previously statuses
     * could only be added/deleted, never reordered or edited from the UI.
     */
    public function test_kanban_columns_can_be_created_at_a_chosen_level_edited_and_reordered(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $service = app(ProjectTaskStatusService::class);
        $controller = new ProjectTaskStatusController();

        $storeResponse = $controller->store(Request::create('/x', 'POST', [
            'name' => 'PMT Triage', 'color' => '#ff0000', 'sequence_order' => 1,
        ]), $business)->toResponse(Request::create('/x'));
        $this->assertSame(201, $storeResponse->getStatusCode());
        $created = ProjectTaskStatus::where('business_id', 1)->where('slug', 'pmt-triage')->first();
        $this->assertSame(1, $created->sequence_order);

        $updateResponse = $controller->update(Request::create('/x', 'POST', [
            'color' => '#00ff00', 'sequence_order' => 3,
        ]), $business, $created, $service)->toResponse(Request::create('/x'));
        $this->assertSame(200, $updateResponse->getStatusCode());
        $created->refresh();
        $this->assertSame('#00ff00', $created->color);
        $this->assertSame(3, $created->sequence_order);

        $allIds = ProjectTaskStatus::where('business_id', 1)->ordered()->pluck('id')->all();
        $reversed = array_reverse($allIds);
        $reorderResponse = $controller->reorder(Request::create('/x', 'POST', ['ordered_ids' => $reversed]), $business, $service)
            ->toResponse(Request::create('/x'));
        $this->assertSame(200, $reorderResponse->getStatusCode());
        $this->assertSame($reversed, ProjectTaskStatus::where('business_id', 1)->ordered()->pluck('id')->all());
    }

    public function test_task_categories_can_be_created_at_a_chosen_level_edited_and_reordered(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new ProjectTaskCategoryController();

        $controller->store(Request::create('/x', 'POST', ['name' => 'PMT Cat A']), $business);
        $controller->store(Request::create('/x', 'POST', ['name' => 'PMT Cat B']), $business);
        $catA = \App\Models\ProjectTaskCategory::where('business_id', 1)->where('name', 'PMT Cat A')->first();
        $catB = \App\Models\ProjectTaskCategory::where('business_id', 1)->where('name', 'PMT Cat B')->first();

        $updateResponse = $controller->update(Request::create('/x', 'POST', [
            'color' => '#123456', 'sequence_order' => 5,
        ]), $business, $catA)->toResponse(Request::create('/x'));
        $this->assertSame(200, $updateResponse->getStatusCode());
        $catA->refresh();
        $this->assertSame('#123456', $catA->color);
        $this->assertSame(5, $catA->sequence_order);

        $reorderResponse = $controller->reorder(Request::create('/x', 'POST', [
            'ordered_ids' => [$catB->id, $catA->id],
        ]), $business)->toResponse(Request::create('/x'));
        $this->assertSame(200, $reorderResponse->getStatusCode());
        $this->assertSame(
            [$catB->id, $catA->id],
            \App\Models\ProjectTaskCategory::where('business_id', 1)->ordered()->whereIn('id', [$catA->id, $catB->id])->pluck('id')->all()
        );
    }

    // ---- Resource Allocation -----------------------------------------------

    public function test_a_member_can_be_added_removed_and_re_added(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Members', 'status' => 'active']);
        $employee = $this->makeEmployee();

        $controller = new ProjectMemberController();
        $addResponse = $controller->store(Request::create('/x', 'POST', ['employee_id' => $employee->id, 'allocation_percentage' => 50]), $business, $project)->toResponse(Request::create('/x'));
        $this->assertSame(201, $addResponse->getStatusCode(), $addResponse->getContent());

        $member = ProjectMember::where('project_id', $project->id)->where('employee_id', $employee->id)->first();
        $removeResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $member)->toResponse(Request::create('/x'));
        $this->assertSame(200, $removeResponse->getStatusCode());
        $this->assertNotNull($member->fresh()->left_at);

        $reAddResponse = $controller->store(Request::create('/x', 'POST', ['employee_id' => $employee->id]), $business, $project)->toResponse(Request::create('/x'));
        $this->assertSame(201, $reAddResponse->getStatusCode(), $reAddResponse->getContent());
        $this->assertNull($member->fresh()->left_at);
        $this->assertSame(1, ProjectMember::where('project_id', $project->id)->where('employee_id', $employee->id)->count());
    }

    // ---- Time Tracking -------------------------------------------------

    public function test_time_can_be_logged_and_removed(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Time', 'status' => 'active']);
        $employee = $this->makeEmployee();

        $controller = new ProjectTimeLogController();
        $storeResponse = $controller->store(Request::create('/x', 'POST', [
            'employee_id' => $employee->id, 'date' => now()->toDateString(), 'hours' => 3.5,
        ]), $business, $project)->toResponse(Request::create('/x'));
        $this->assertSame(201, $storeResponse->getStatusCode(), $storeResponse->getContent());

        $log = ProjectTimeLog::where('project_id', $project->id)->first();
        $destroyResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $log)->toResponse(Request::create('/x'));
        $this->assertSame(200, $destroyResponse->getStatusCode());
        $this->assertNull(ProjectTimeLog::find($log->id));
    }

    // ---- Team Collaboration (comments) ------------------------------------

    public function test_an_employee_can_comment_on_a_task(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $employee = Employee::create([
            'user_id' => $admin->id, 'business_id' => 1, 'department_id' => 1,
            'employee_code' => 'PMT-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999), 'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Comments', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();
        $task = ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id, 'title' => 'PMT Commented Task', 'position' => 1]);

        $controller = new ProjectTaskController();
        $request = Request::create('/x', 'POST', ['comment' => 'Looks good to me.']);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->storeComment($request, $business, $task)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $this->assertSame(1, $task->comments()->count());
    }

    // ---- Reports ------------------------------------------------------

    public function test_task_status_and_time_tracking_reports_render_without_error(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $employee = $this->makeEmployee();
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Reportable', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();
        ProjectTask::create(['project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id, 'assignee_employee_id' => $employee->id, 'title' => 'PMT Reportable Task', 'position' => 1]);
        ProjectTimeLog::create(['project_id' => $project->id, 'business_id' => 1, 'employee_id' => $employee->id, 'date' => now(), 'hours' => 2]);

        $controller = new ProjectReportController();
        $taskHtml = $controller->taskStatusPreview(Request::create('/x'), $business);
        $timeHtml = $controller->timeTrackingPreview(Request::create('/x'), $business);

        $this->assertStringContainsString('PMT Reportable Task', $taskHtml);
        $this->assertStringContainsString('Project Time Tracking Report', $timeHtml);
    }

    // ---- Reminder scheduler --------------------------------------------

    public function test_due_soon_reminder_is_sent_exactly_once_on_the_configured_day(): void
    {
        Mail::fake();
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $business->update(['project_task_due_reminder_days' => 2]);
        $this->seedStatuses($business);
        $employee = $this->makeEmployee();
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Due Soon', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();
        ProjectTask::create([
            'project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id,
            'assignee_employee_id' => $employee->id, 'title' => 'PMT Due Task', 'position' => 1,
            'due_date' => now()->addDays(2),
        ]);

        $service = new ProjectSchedulerService();
        $firstRun = $service->sendDueReminders();
        $secondRun = $service->sendDueReminders();

        $this->assertSame(1, $firstRun);
        $this->assertSame(0, $secondRun);
        Mail::assertSent(ProjectTaskDueReminderMail::class, 1);
    }

    public function test_overdue_reminder_is_sent_once_a_task_passes_its_due_date(): void
    {
        Mail::fake();
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $this->seedStatuses($business);
        $employee = $this->makeEmployee();
        $project = Project::create(['business_id' => 1, 'name' => 'PMT Overdue', 'status' => 'active']);
        $status = ProjectTaskStatus::where('business_id', 1)->ordered()->first();
        ProjectTask::create([
            'project_id' => $project->id, 'business_id' => 1, 'project_task_status_id' => $status->id,
            'assignee_employee_id' => $employee->id, 'title' => 'PMT Overdue Task', 'position' => 1,
            'due_date' => now()->subDays(1),
        ]);

        $service = new ProjectSchedulerService();
        $sent = $service->sendOverdueReminders();

        $this->assertSame(1, $sent);
        Mail::assertSent(ProjectTaskOverdueMail::class, 1);
    }
}
