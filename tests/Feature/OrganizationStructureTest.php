<?php

namespace Tests\Feature;

use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\OrganogramController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OrganogramPosition;
use App\Models\OrganogramRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the redesigned organization structure:
 *  - Roles have an explicit reports_to_role_id edge (not a numeric ladder)
 *    so peers and non-linear structures (an ED who's a peer of, or
 *    outranks, the MD) are representable.
 *  - A Position is the actual assignment: one employee holding one role,
 *    covering one or more departments and/or teams - this is what lets
 *    "Manager 1" cover 5 departments and "Manager 2" cover a different 5.
 *  - Teams are an optional layer inside a department with their own more
 *    specific position coverage.
 *  - A role can map to a Spatie permission role, auto-granted on assignment.
 */
class OrganizationStructureTest extends TestCase
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

    private function makeEmployee(?int $departmentId = 1, ?int $roleId = null, ?int $teamId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'team_id' => $teamId,
            'organogram_role_id' => $roleId,
            'employee_code' => 'ORGS-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    private function makeRole(array $overrides = []): OrganogramRole
    {
        return OrganogramRole::create(array_merge([
            'business_id' => 1,
            'name' => 'Role ' . uniqid(),
            'level' => 1,
        ], $overrides));
    }

    public function test_employee_reports_to_whoever_holds_the_target_role_over_their_department(): void
    {
        $hod = $this->makeRole(['name' => 'HOD ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $hod->id]);

        $hodEmployee = $this->makeEmployee(1, $hod->id);
        $staffEmployee = $this->makeEmployee(1, $staff->id);

        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $hod->id, 'employee_id' => $hodEmployee->id])
            ->departments()->attach([1]);

        $staffEmployee->syncManagerFromTemplate();

        $this->assertSame($hodEmployee->id, $staffEmployee->fresh()->manager_id);
    }

    public function test_peer_roles_with_no_reporting_edge_have_no_manager(): void
    {
        $ed = $this->makeRole(['name' => 'ED ' . uniqid()]);
        $md = $this->makeRole(['name' => 'MD ' . uniqid()]); // deliberately no reports_to_role_id - a peer, not a subordinate

        $mdEmployee = $this->makeEmployee(1, $md->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $ed->id, 'employee_id' => $this->makeEmployee(1, $ed->id)->id])
            ->departments()->attach([1]);

        $mdEmployee->syncManagerFromTemplate();

        $this->assertNull($mdEmployee->fresh()->manager_id);
    }

    public function test_one_position_can_cover_multiple_departments(): void
    {
        $manager = $this->makeRole(['name' => 'Regional Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1, 2, 3]);

        $staffInDept1 = $this->makeEmployee(1, $staff->id);
        $staffInDept2 = $this->makeEmployee(2, $staff->id);
        $staffInDept3 = $this->makeEmployee(3, $staff->id);

        $staffInDept1->syncManagerFromTemplate();
        $staffInDept2->syncManagerFromTemplate();
        $staffInDept3->syncManagerFromTemplate();

        $this->assertSame($managerEmployee->id, $staffInDept1->fresh()->manager_id);
        $this->assertSame($managerEmployee->id, $staffInDept2->fresh()->manager_id);
        $this->assertSame($managerEmployee->id, $staffInDept3->fresh()->manager_id);
    }

    public function test_department_not_covered_by_the_position_gets_no_manager(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1]); // only department 1

        $staffInDept2 = $this->makeEmployee(2, $staff->id);
        $staffInDept2->syncManagerFromTemplate();

        $this->assertNull($staffInDept2->fresh()->manager_id);
    }

    public function test_team_scoped_position_takes_precedence_over_department_scoped_position(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $teamLead = $this->makeRole(['name' => 'Team Lead ' . uniqid(), 'reports_to_role_id' => $manager->id]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $teamLead->id]);

        $team = Team::create(['business_id' => 1, 'department_id' => 1, 'name' => 'Squad ' . uniqid()]);

        $departmentManager = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $teamLead->id, 'employee_id' => $departmentManager->id])
            ->departments()->attach([1]);

        $teamLeadEmployee = $this->makeEmployee(1, $teamLead->id, $team->id);
        $position = OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $teamLead->id, 'employee_id' => $teamLeadEmployee->id]);
        $position->teams()->attach([$team->id]);

        $staffEmployee = $this->makeEmployee(1, $staff->id, $team->id);
        $staffEmployee->syncManagerFromTemplate();

        // Both a department-level and a team-level "Team Lead" position
        // exist; the employee is IN the team, so the team-scoped one wins.
        $this->assertSame($teamLeadEmployee->id, $staffEmployee->fresh()->manager_id);
    }

    public function test_manual_override_still_pins_one_employee_without_affecting_siblings(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1]);

        $elsewhereManager = $this->makeEmployee(3, null);
        $staffA = $this->makeEmployee(1, $staff->id);
        $staffB = $this->makeEmployee(1, $staff->id);
        $staffA->syncManagerFromTemplate();
        $staffB->syncManagerFromTemplate();

        $business = Business::find(1);
        $this->actingAs(User::factory()->create());
        $controller = new OrganogramController();
        $request = Request::create('/organogram/assign-manager', 'POST', [
            'employee_id' => $staffA->id,
            'manager_id' => $elsewhereManager->id,
        ]);
        $controller->assignManager($request, $business)->toResponse($request);

        $this->assertSame($elsewhereManager->id, $staffA->fresh()->manager_id);
        $this->assertTrue((bool) $staffA->fresh()->manager_override);
        $this->assertSame($managerEmployee->id, $staffB->fresh()->manager_id);
    }

    public function test_role_reporting_edge_rejects_a_cycle(): void
    {
        $roleA = $this->makeRole(['name' => 'A ' . uniqid()]);
        $roleB = $this->makeRole(['name' => 'B ' . uniqid(), 'reports_to_role_id' => $roleA->id]);

        // A reporting to B would create A -> B -> A.
        $this->assertTrue($roleA->wouldCreateCycle($roleB->id));
    }

    public function test_storing_a_role_with_a_cyclical_reports_to_is_rejected_via_controller(): void
    {
        $business = Business::find(1);
        $roleA = $this->makeRole(['name' => 'A ' . uniqid()]);
        $roleB = $this->makeRole(['name' => 'B ' . uniqid(), 'reports_to_role_id' => $roleA->id]);

        $this->actingAs(User::factory()->create());
        $controller = new OrganizationStructureController();
        $request = Request::create("/organization-structure/roles/{$roleA->id}", 'POST', [
            'name' => $roleA->name,
            'level' => 1,
            'reports_to_role_id' => $roleB->id,
        ]);
        $response = $controller->updateRole($request, $business, $roleA)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNull($roleA->fresh()->reports_to_role_id);
    }

    public function test_assigning_a_position_sets_the_employees_own_role_and_grants_the_mapped_permission(): void
    {
        $business = Business::find(1);
        $role = $this->makeRole(['name' => 'HOD ' . uniqid(), 'spatie_role_name' => 'head-of-department']);
        $employee = $this->makeEmployee(1, null);

        $this->actingAs(User::factory()->create());
        $controller = new OrganizationStructureController();
        $request = Request::create('/organization-structure/positions', 'POST', [
            'employee_id' => $employee->id,
            'organogram_role_id' => $role->id,
            'department_ids' => [1],
        ]);
        $response = $controller->storePosition($request, $business)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($role->id, $employee->fresh()->organogram_role_id);
        $this->assertTrue($employee->fresh()->user->hasRole('head-of-department'));
    }

    public function test_removing_a_position_resyncs_reports_to_no_manager(): void
    {
        $business = Business::find(1);
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        $position = OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id]);
        $position->departments()->attach([1]);

        $staffEmployee = $this->makeEmployee(1, $staff->id);
        $staffEmployee->syncManagerFromTemplate();
        $this->assertSame($managerEmployee->id, $staffEmployee->fresh()->manager_id);

        $this->actingAs(User::factory()->create());
        $controller = new OrganizationStructureController();
        $response = $controller->destroyPosition($business, $position)->toResponse(Request::create('/x'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($staffEmployee->fresh()->manager_id);
    }

    public function test_sync_all_skips_manually_overridden_employees(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);
        $otherManager = $this->makeEmployee(1, $manager->id);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1]);

        $staffEmployee = $this->makeEmployee(1, $staff->id);
        $staffEmployee->manager_id = $otherManager->id;
        $staffEmployee->manager_override = true;
        $staffEmployee->save();

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $response = $controller->syncAll($business)->toResponse(Request::create('/x'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($otherManager->id, $staffEmployee->fresh()->manager_id);
    }

    public function test_leave_approval_routes_correctly_through_a_position_derived_manager(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1]);

        $staffEmployee = $this->makeEmployee(1, $staff->id);
        $staffEmployee->syncManagerFromTemplate();

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Org Structure Leave ' . uniqid()]);
        $leaveRequest = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $staffEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'current_approval_level' => 0,
        ]);

        session(['active_role' => 'business-employee']);
        $this->assertTrue($leaveRequest->canUserApprove($managerEmployee->user));
    }

    public function test_fetch_assignments_groups_positions_by_department_and_team(): void
    {
        $role = $this->makeRole(['name' => 'HOD ' . uniqid()]);
        $employee = $this->makeEmployee(1, $role->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $role->id, 'employee_id' => $employee->id])
            ->departments()->attach([1]);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $response = $controller->fetchAssignments($business)->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $department = collect($payload['data']['tree'])->firstWhere('id', 1);
        $this->assertNotNull($department);
        $positionEmployeeIds = collect($department['positions'])->pluck('employee_id');
        $this->assertTrue($positionEmployeeIds->contains($employee->id));
    }

    public function test_bulk_assign_role_gives_rank_and_file_employees_a_rung_and_syncs_them(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(1, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([1]);

        // No organogram_role_id at all - exactly the "sync does nothing for
        // regular employees" scenario being fixed.
        $rankAndFile = $this->makeEmployee(1, null);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $request = Request::create('/organization-structure/bulk-assign-role', 'POST', [
            'organogram_role_id' => $staff->id,
            'department_id' => 1,
        ]);
        $response = $controller->bulkAssignRole($request, $business)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $rankAndFile->refresh();
        $this->assertSame($staff->id, $rankAndFile->organogram_role_id);
        $this->assertSame($managerEmployee->id, $rankAndFile->manager_id);
    }

    public function test_bulk_assign_role_does_not_overwrite_existing_role_unless_asked(): void
    {
        $roleA = $this->makeRole(['name' => 'A ' . uniqid()]);
        $roleB = $this->makeRole(['name' => 'B ' . uniqid()]);
        $employee = $this->makeEmployee(1, $roleA->id);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();

        // Without overwrite_existing, the employee's existing role is left alone.
        $request = Request::create('/organization-structure/bulk-assign-role', 'POST', [
            'organogram_role_id' => $roleB->id,
            'department_id' => 1,
        ]);
        $controller->bulkAssignRole($request, $business)->toResponse($request);
        $this->assertSame($roleA->id, $employee->fresh()->organogram_role_id);

        // With overwrite_existing, it's replaced.
        $request = Request::create('/organization-structure/bulk-assign-role', 'POST', [
            'organogram_role_id' => $roleB->id,
            'department_id' => 1,
            'overwrite_existing' => true,
        ]);
        $controller->bulkAssignRole($request, $business)->toResponse($request);
        $this->assertSame($roleB->id, $employee->fresh()->organogram_role_id);
    }

    public function test_bulk_assign_role_can_be_scoped_to_a_single_team(): void
    {
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid()]);
        $team = Team::create(['business_id' => 1, 'department_id' => 1, 'name' => 'Squad ' . uniqid()]);

        $inTeam = $this->makeEmployee(1, null, $team->id);
        $outsideTeam = $this->makeEmployee(1, null);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $request = Request::create('/organization-structure/bulk-assign-role', 'POST', [
            'organogram_role_id' => $staff->id,
            'department_id' => 1,
            'team_id' => $team->id,
        ]);
        $controller->bulkAssignRole($request, $business)->toResponse($request);

        $this->assertSame($staff->id, $inTeam->fresh()->organogram_role_id);
        $this->assertNull($outsideTeam->fresh()->organogram_role_id);
    }

    public function test_position_covers_employee_assigned_to_department_via_multi_department_pivot(): void
    {
        $manager = $this->makeRole(['name' => 'Manager ' . uniqid()]);
        $staff = $this->makeRole(['name' => 'Staff ' . uniqid(), 'reports_to_role_id' => $manager->id]);

        $managerEmployee = $this->makeEmployee(2, $manager->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $manager->id, 'employee_id' => $managerEmployee->id])
            ->departments()->attach([2]);

        // Primary department is 1, but also assigned to department 2 via the
        // employee_departments pivot (e.g. a shared/cross-department hire).
        $staffEmployee = $this->makeEmployee(1, $staff->id);
        $staffEmployee->departments()->attach([2]);

        $staffEmployee->syncManagerFromTemplate();

        $this->assertSame($managerEmployee->id, $staffEmployee->fresh()->manager_id);
    }
}
