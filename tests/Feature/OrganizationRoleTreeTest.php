<?php

namespace Tests\Feature;

use App\Http\Controllers\OrganizationStructureController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\OrganogramPosition;
use App\Models\OrganogramRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for OrganizationStructureController::fetchRoleTree(),
 * which powers the visual "Organization Tree" tab - a role-based hierarchy
 * (reports_to_role_id edges), each role carrying its position holders and
 * department/team coverage.
 */
class OrganizationRoleTreeTest extends TestCase
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

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'RT-' . uniqid(),
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

        return $employee->fresh();
    }

    public function test_role_tree_nests_children_under_their_parent_role_with_position_coverage(): void
    {
        $ed = OrganogramRole::create(['business_id' => 1, 'name' => 'RT-ED-' . uniqid(), 'level' => 1]);
        $hod = OrganogramRole::create(['business_id' => 1, 'name' => 'RT-HOD-' . uniqid(), 'level' => 2, 'reports_to_role_id' => $ed->id]);

        $edEmployee = $this->makeEmployee();
        $edEmployee->update(['organogram_role_id' => $ed->id]);
        $position = OrganogramPosition::create(['business_id' => 1, 'organogram_role_id' => $ed->id, 'employee_id' => $edEmployee->id]);
        $position->departments()->attach([1]);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $response = $controller->fetchRoleTree($business)->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $roots = collect($payload['data']);
        $edNode = $roots->firstWhere('id', $ed->id);

        $this->assertNotNull($edNode, 'ED role should be a root node (no reports_to_role_id).');
        $this->assertSame(1, $edNode['employees_count']);
        $this->assertCount(1, $edNode['positions']);
        $this->assertSame($edEmployee->id, $edNode['positions'][0]['employee_id']);
        $this->assertNotEmpty($edNode['positions'][0]['coverage']);

        $hodChild = collect($edNode['children'])->firstWhere('id', $hod->id);
        $this->assertNotNull($hodChild, 'HOD role should nest under ED as a child.');
    }

    public function test_role_with_missing_parent_reference_is_still_treated_as_a_root(): void
    {
        // The FK only guarantees reports_to_role_id points at SOME real
        // row, not one within this business's own role set - a role
        // pointing at another business's role (e.g. left over from a
        // business_id change) must still surface as a root here.
        $otherBusiness = Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => 'RT Other Business ' . uniqid(),
            'slug' => 'rt-other-business-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'code' => 'RTO' . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);
        $otherRole = OrganogramRole::create(['business_id' => $otherBusiness->id, 'name' => 'RT-OtherRole-' . uniqid(), 'level' => 1]);

        $orphan = OrganogramRole::create([
            'business_id' => 1,
            'name' => 'RT-Orphan-' . uniqid(),
            'level' => 1,
            'reports_to_role_id' => $otherRole->id,
        ]);

        $business = Business::find(1);
        $controller = new OrganizationStructureController();
        $response = $controller->fetchRoleTree($business)->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $roots = collect($payload['data']);
        $this->assertNotNull($roots->firstWhere('id', $orphan->id));
    }
}
