<?php

namespace Tests\Feature;

use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\OrganogramController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\OrganogramPosition;
use App\Models\OrganogramRole;
use App\Models\User;
use App\Services\OrganizationOwnershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Replicating the Enterprise Organization Designer mockup: "executive
 * owner of a department" and "department HOD" don't exist as stored
 * fields anywhere - OrganizationOwnershipService derives both purely from
 * the existing OrganogramRole.reports_to_role_id chain + OrganogramPosition
 * department coverage (see its docblock). Also covers the new Functional
 * Manager (dotted-line) relationship and the endpoints built on top of
 * both (Overview tab, Organogram table/search/filter-options).
 */
class OrganizationOwnershipTest extends TestCase
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

    private function makeEmployee(?int $departmentId = 1, ?int $roleId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'organogram_role_id' => $roleId,
            'employee_code' => 'OWN-' . uniqid(),
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

    /**
     * CEO (root) -> COO (owns depts A + B) -> Head of Ops (only covers
     * dept A). Dept A should get COO as executive owner and Head of Ops
     * as HOD (further from root); dept B has only COO covering it, so
     * COO is both its executive owner and its HOD.
     */
    public function test_computes_executive_owner_and_hod_by_distance_from_the_root_role(): void
    {
        // Fresh departments, not the shared business's real 1/2 - those
        // already have real production roles/positions covering them,
        // which would out-rank (smaller distance-from-root) this test's
        // fixture and make the assertions depend on unrelated live data.
        $deptA = \App\Models\Department::create(['business_id' => 1, 'name' => 'Ownership Dept A ' . uniqid()]);
        $deptB = \App\Models\Department::create(['business_id' => 1, 'name' => 'Ownership Dept B ' . uniqid()]);

        $ceo = $this->makeRole(['name' => 'CEO ' . uniqid()]);
        $coo = $this->makeRole(['name' => 'COO ' . uniqid(), 'reports_to_role_id' => $ceo->id]);
        $headOfOps = $this->makeRole(['name' => 'Head of Ops ' . uniqid(), 'reports_to_role_id' => $coo->id]);

        $cooEmployee = $this->makeEmployee($deptA->id, $coo->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $coo->id, 'employee_id' => $cooEmployee->id])
            ->departments()->attach([$deptA->id, $deptB->id]);

        $headOfOpsEmployee = $this->makeEmployee($deptA->id, $headOfOps->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $headOfOps->id, 'employee_id' => $headOfOpsEmployee->id])
            ->departments()->attach([$deptA->id]);

        $ownership = app(OrganizationOwnershipService::class)->computeDepartmentOwnership(Business::find(1));

        $this->assertSame($coo->id, $ownership[$deptA->id]['executive']['role_id']);
        $this->assertSame($headOfOps->id, $ownership[$deptA->id]['hod']['role_id']);

        $this->assertSame($coo->id, $ownership[$deptB->id]['executive']['role_id']);
        $this->assertSame($coo->id, $ownership[$deptB->id]['hod']['role_id']);
    }

    public function test_department_with_no_covering_position_has_null_ownership(): void
    {
        $uncovered = \App\Models\Department::create(['business_id' => 1, 'name' => 'Ownership Uncovered Dept ' . uniqid()]);

        $ownership = app(OrganizationOwnershipService::class)->computeDepartmentOwnership(Business::find(1));

        $this->assertNull($ownership[$uncovered->id]['executive']);
        $this->assertNull($ownership[$uncovered->id]['hod']);
    }

    public function test_executive_cards_group_departments_under_their_owning_role(): void
    {
        $ceo = $this->makeRole(['name' => 'CEO ' . uniqid()]);
        $coo = $this->makeRole(['name' => 'COO Cards ' . uniqid(), 'reports_to_role_id' => $ceo->id]);

        $deptA = \App\Models\Department::create(['business_id' => 1, 'name' => 'Cards Dept A ' . uniqid()]);
        $deptB = \App\Models\Department::create(['business_id' => 1, 'name' => 'Cards Dept B ' . uniqid()]);

        $cooEmployee = $this->makeEmployee($deptA->id, $coo->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $coo->id, 'employee_id' => $cooEmployee->id])
            ->departments()->attach([$deptA->id, $deptB->id]);

        $this->makeEmployee($deptA->id);
        $this->makeEmployee($deptA->id);
        $this->makeEmployee($deptB->id);

        $cards = app(OrganizationOwnershipService::class)->executiveCards(Business::find(1));
        $card = collect($cards)->firstWhere('role_id', $coo->id);

        $this->assertNotNull($card);
        $this->assertCount(2, $card['departments']);
        $deptACard = collect($card['departments'])->firstWhere('id', $deptA->id);
        // The COO employee + the 2 extra employees created above.
        $this->assertSame(3, $deptACard['people_count']);
    }

    public function test_cross_functional_count_only_counts_a_functional_manager_in_a_different_department(): void
    {
        $sameDept = $this->makeEmployee(1);
        $sameDeptFunctionalManager = $this->makeEmployee(1);
        $sameDept->functional_manager_id = $sameDeptFunctionalManager->id;
        $sameDept->save();

        $crossDept = $this->makeEmployee(1);
        $crossDeptFunctionalManager = $this->makeEmployee(2);
        $crossDept->functional_manager_id = $crossDeptFunctionalManager->id;
        $crossDept->save();

        $count = app(OrganizationOwnershipService::class)->crossFunctionalCount(Business::find(1));

        $this->assertSame(1, $count);
    }

    public function test_overview_endpoint_returns_stats_roots_and_executive_cards(): void
    {
        $ceo = $this->makeRole(['name' => 'CEO Overview ' . uniqid()]);
        $ceoEmployee = $this->makeEmployee(1, $ceo->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $ceo->id, 'employee_id' => $ceoEmployee->id])
            ->departments()->attach([1]);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $response = $controller->fetchOverview($business, app(OrganizationOwnershipService::class))->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('stats', $payload['data']);
        $this->assertArrayHasKey('roots', $payload['data']);
        $this->assertArrayHasKey('executives', $payload['data']);
        $this->assertTrue(collect($payload['data']['roots'])->pluck('role_id')->contains($ceo->id));
    }

    public function test_assign_manager_also_sets_functional_manager_in_one_request(): void
    {
        $employee = $this->makeEmployee(1);
        $manager = $this->makeEmployee(1);
        $functionalManager = $this->makeEmployee(2);

        $business = Business::find(1);
        $this->actingAs(User::factory()->create());
        $controller = new OrganogramController();
        $request = Request::create('/organogram/assign-manager', 'POST', [
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'functional_manager_id' => $functionalManager->id,
        ]);
        $response = $controller->assignManager($request, $business)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $employee->refresh();
        $this->assertSame($manager->id, $employee->manager_id);
        $this->assertSame($functionalManager->id, $employee->functional_manager_id);
    }

    public function test_employee_cannot_be_their_own_functional_manager(): void
    {
        $employee = $this->makeEmployee(1);

        $business = Business::find(1);
        $this->actingAs(User::factory()->create());
        $controller = new OrganogramController();
        $request = Request::create('/organogram/assign-manager', 'POST', [
            'employee_id' => $employee->id,
            'functional_manager_id' => $employee->id,
        ]);
        $response = $controller->assignManager($request, $business)->toResponse($request);

        // RequestResponse::badRequest()'s second parameter is $data, not an
        // HTTP status override - every badRequest() call in this method
        // (including the pre-existing wouldCreateCycle() one) is actually
        // a 400, regardless of the literal "422" passed alongside the message.
        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_search_filters_by_department(): void
    {
        $inDept1 = $this->makeEmployee(1);
        $inDept2 = $this->makeEmployee(2);

        $business = Business::find(1);
        $controller = new OrganogramController();
        $request = Request::create('/x', 'GET', ['department_id' => 1]);
        $response = $controller->search($business, $request)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $ids = collect($payload['data'])->pluck('id');
        $this->assertTrue($ids->contains($inDept1->id));
        $this->assertFalse($ids->contains($inDept2->id));
    }

    public function test_search_filters_by_executive_owner(): void
    {
        $deptA = \App\Models\Department::create(['business_id' => 1, 'name' => 'Search Exec Dept A ' . uniqid()]);
        $deptB = \App\Models\Department::create(['business_id' => 1, 'name' => 'Search Exec Dept B ' . uniqid()]);

        $ceo = $this->makeRole(['name' => 'CEO Search ' . uniqid()]);
        $coo = $this->makeRole(['name' => 'COO Search ' . uniqid(), 'reports_to_role_id' => $ceo->id]);
        $cooEmployee = $this->makeEmployee($deptA->id, $coo->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $coo->id, 'employee_id' => $cooEmployee->id])
            ->departments()->attach([$deptA->id]);

        $inOwnedDept = $this->makeEmployee($deptA->id);
        $inOtherDept = $this->makeEmployee($deptB->id);

        $business = Business::find(1);
        $controller = new OrganogramController();
        $request = Request::create('/x', 'GET', ['executive_role_id' => $coo->id]);
        $response = $controller->search($business, $request)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $ids = collect($payload['data'])->pluck('id');
        $this->assertTrue($ids->contains($inOwnedDept->id));
        $this->assertFalse($ids->contains($inOtherDept->id));
    }

    public function test_table_endpoint_includes_hod_executive_owner_and_cross_functional_flag(): void
    {
        $deptA = \App\Models\Department::create(['business_id' => 1, 'name' => 'Table Dept A ' . uniqid()]);
        $deptB = \App\Models\Department::create(['business_id' => 1, 'name' => 'Table Dept B ' . uniqid()]);

        $ceo = $this->makeRole(['name' => 'CEO Table ' . uniqid()]);
        $coo = $this->makeRole(['name' => 'COO Table ' . uniqid(), 'reports_to_role_id' => $ceo->id]);
        $cooEmployee = $this->makeEmployee($deptA->id, $coo->id);
        OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $coo->id, 'employee_id' => $cooEmployee->id])
            ->departments()->attach([$deptA->id]);

        $functionalManager = $this->makeEmployee($deptB->id);
        $staff = $this->makeEmployee($deptA->id);
        $staff->functional_manager_id = $functionalManager->id;
        $staff->save();

        $business = Business::find(1);
        $controller = new OrganogramController();
        $response = $controller->table($business, Request::create('/x'), app(OrganizationOwnershipService::class))
            ->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $row = collect($payload['data'])->firstWhere('id', $staff->id);
        $this->assertNotNull($row);
        $this->assertSame($coo->name, $row['executive_owner']);
        $this->assertTrue($row['is_cross_functional']);
    }
}
