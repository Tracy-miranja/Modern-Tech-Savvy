<?php

namespace Tests\Feature;

use App\Http\Controllers\AssetReportController;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Asset Register report - the Asset Management module had no reporting
 * at all before this (see AssetReportController's docblock). One report:
 * a full asset listing filterable by status/department, showing who
 * (if anyone) currently holds each asset.
 */
class AssetReportsTest extends TestCase
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

    private function makeEmployee(int $departmentId): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'employee_code' => 'ART-' . uniqid(),
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

    public function test_register_lists_every_asset_with_its_current_assignee(): void
    {
        $department = Department::create(['business_id' => 1, 'name' => 'Asset Report Dept ' . uniqid()]);
        $employee = $this->makeEmployee($department->id);

        $assigned = Asset::create([
            'business_id' => 1, 'name' => 'Laptop ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(),
            'category' => 'Electronics', 'status' => 'assigned', 'condition' => 'Good',
            'purchase_date' => '2024-01-01', 'purchase_cost' => 1200.50,
        ]);
        AssetAssignment::create([
            'asset_id' => $assigned->id, 'business_id' => 1, 'employee_id' => $employee->id,
            'assigned_by_user_id' => User::factory()->create()->id,
            'assigned_at' => now(),
        ]);

        $unassigned = Asset::create([
            'business_id' => 1, 'name' => 'Spare Monitor ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(),
            'category' => 'Electronics', 'status' => 'available', 'condition' => 'Good',
            'purchase_date' => '2024-02-01', 'purchase_cost' => 300,
        ]);

        $business = Business::find(1);
        $controller = new AssetReportController();
        $html = $controller->registerPreview(Request::create('/x', 'GET'), $business);

        $this->assertStringContainsString($assigned->name, $html);
        $this->assertStringContainsString($employee->user->name, $html);
        $this->assertStringContainsString($unassigned->name, $html);
        $this->assertStringContainsString('Unassigned', $html);
    }

    public function test_register_filters_by_status(): void
    {
        $available = Asset::create([
            'business_id' => 1, 'name' => 'Available Asset ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(), 'status' => 'available',
        ]);
        $retired = Asset::create([
            'business_id' => 1, 'name' => 'Retired Asset ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(), 'status' => 'retired',
        ]);

        $business = Business::find(1);
        $controller = new AssetReportController();
        $html = $controller->registerPreview(Request::create('/x', 'GET', ['status' => 'retired']), $business);

        $this->assertStringContainsString($retired->name, $html);
        $this->assertStringNotContainsString($available->name, $html);
    }

    public function test_register_filters_by_department_via_current_assignment(): void
    {
        $deptA = Department::create(['business_id' => 1, 'name' => 'Asset Report Dept A ' . uniqid()]);
        $deptB = Department::create(['business_id' => 1, 'name' => 'Asset Report Dept B ' . uniqid()]);
        $employeeA = $this->makeEmployee($deptA->id);
        $employeeB = $this->makeEmployee($deptB->id);

        $assigner = User::factory()->create();

        $assetA = Asset::create(['business_id' => 1, 'name' => 'Dept A Asset ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(), 'status' => 'assigned']);
        AssetAssignment::create(['asset_id' => $assetA->id, 'business_id' => 1, 'employee_id' => $employeeA->id, 'assigned_by_user_id' => $assigner->id, 'assigned_at' => now()]);

        $assetB = Asset::create(['business_id' => 1, 'name' => 'Dept B Asset ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(), 'status' => 'assigned']);
        AssetAssignment::create(['asset_id' => $assetB->id, 'business_id' => 1, 'employee_id' => $employeeB->id, 'assigned_by_user_id' => $assigner->id, 'assigned_at' => now()]);

        $business = Business::find(1);
        $controller = new AssetReportController();
        $html = $controller->registerPreview(Request::create('/x', 'GET', ['department_id' => $deptA->id]), $business);

        $this->assertStringContainsString($assetA->name, $html);
        $this->assertStringNotContainsString($assetB->name, $html);
    }

    public function test_reports_index_page_returns_200(): void
    {
        $business = Business::find(1);
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => $business->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.assets.reports.index', $business->slug));

        $response->assertOk();
        $response->assertSee('Asset Reports');
    }
}
