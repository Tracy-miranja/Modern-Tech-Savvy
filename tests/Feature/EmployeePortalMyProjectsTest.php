<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "My Projects" - a new employee-portal page (business-employee side)
 * reusing the exact same Kanban board/task/comment/time-log controllers
 * and view as the business-admin side (see ProjectController::showBoard()'s
 * $routePrefix trick and EnsureProjectMember), scoped to projects the
 * logged-in employee manages or is an active member of.
 */
class EmployeePortalMyProjectsTest extends TestCase
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
            'employee_code' => 'MYPROJ-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create(['employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1, 'employment_date' => '2020-01-01', 'employment_term' => 'permanent']);

        return [$user->fresh(), $employee->fresh()];
    }

    private function actingAsPortalEmployee(User $user)
    {
        return $this->actingAs($user)->withSession([
            'active_business_slug' => $this->business->slug,
            'active_role' => 'business-employee',
            '2fa_verified' => true,
        ]);
    }

    public function test_my_projects_index_only_lists_projects_the_employee_manages_or_belongs_to(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();

        $managed = Project::create(['business_id' => $this->business->id, 'name' => 'Managed Project ' . uniqid(), 'manager_employee_id' => $employee->id, 'status' => 'active']);
        $memberOf = Project::create(['business_id' => $this->business->id, 'name' => 'Member Project ' . uniqid(), 'status' => 'active']);
        ProjectMember::create(['project_id' => $memberOf->id, 'business_id' => $this->business->id, 'employee_id' => $employee->id, 'joined_at' => now()]);
        $unrelated = Project::create(['business_id' => $this->business->id, 'name' => 'Unrelated Project ' . uniqid(), 'status' => 'active']);

        $response = $this->actingAsPortalEmployee($user)->get(route('myaccount.projects.index', $this->business->slug));

        $response->assertOk();
        $response->assertSee($managed->name);
        $response->assertSee($memberOf->name);
        $response->assertDontSee($unrelated->name);
    }

    public function test_a_project_member_can_open_the_board_and_the_underlying_data_endpoint(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $project = Project::create(['business_id' => $this->business->id, 'name' => 'Board Access Project ' . uniqid(), 'status' => 'active']);
        ProjectMember::create(['project_id' => $project->id, 'business_id' => $this->business->id, 'employee_id' => $employee->id, 'joined_at' => now()]);

        $this->actingAsPortalEmployee($user)
            ->get(route('myaccount.projects.board', [$this->business->slug, $project->id]))
            ->assertOk();

        $this->actingAsPortalEmployee($user)
            ->getJson(route('myaccount.projects.board.fetch', [$this->business->slug, $project->id]))
            ->assertOk();
    }

    public function test_a_non_member_is_blocked_from_the_board(): void
    {
        [$user] = $this->makeEmployeeUser();
        $project = Project::create(['business_id' => $this->business->id, 'name' => 'Not My Project ' . uniqid(), 'status' => 'active']);

        $this->actingAsPortalEmployee($user)
            ->getJson(route('myaccount.projects.board.fetch', [$this->business->slug, $project->id]))
            ->assertStatus(403);
    }

    public function test_a_project_member_can_create_a_task_comment_and_log_time_on_their_project(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        $project = Project::create(['business_id' => $this->business->id, 'name' => 'Interactive Project ' . uniqid(), 'status' => 'active']);
        ProjectMember::create(['project_id' => $project->id, 'business_id' => $this->business->id, 'employee_id' => $employee->id, 'joined_at' => now()]);

        // Visiting the board first seeds the business's default Kanban
        // columns (ProjectTaskStatusService::ensureSeeded(), triggered by
        // showBoard()) - matches the real flow (open a project, then add a
        // task), and tasks.store needs an active status to fall back to.
        $this->actingAsPortalEmployee($user)->get(route('myaccount.projects.board', [$this->business->slug, $project->id]));

        $taskResponse = $this->actingAsPortalEmployee($user)
            ->postJson(route('myaccount.projects.tasks.store', [$this->business->slug, $project->id]), [
                'title' => 'A task created from the portal',
            ]);
        $taskResponse->assertCreated();
        $taskId = $taskResponse->json('data.id');
        $this->assertNotNull($taskId);

        $this->actingAsPortalEmployee($user)
            ->postJson(route('myaccount.projects.tasks.comments.store', [$this->business->slug, $taskId]), [
                'comment' => 'Working on it.',
            ])
            ->assertCreated();

        $this->actingAsPortalEmployee($user)
            ->postJson(route('myaccount.projects.time-logs.store', [$this->business->slug, $project->id]), [
                'employee_id' => $employee->id,
                'date' => now()->format('Y-m-d'),
                'hours' => 2.5,
            ])
            ->assertCreated();
    }

    public function test_a_non_member_cannot_create_a_task_on_someone_elses_project(): void
    {
        [$user] = $this->makeEmployeeUser();
        $project = Project::create(['business_id' => $this->business->id, 'name' => 'Locked Out Project ' . uniqid(), 'status' => 'active']);

        $this->actingAsPortalEmployee($user)
            ->postJson(route('myaccount.projects.tasks.store', [$this->business->slug, $project->id]), [
                'title' => 'Should not be allowed',
            ])
            ->assertStatus(403);
    }
}
