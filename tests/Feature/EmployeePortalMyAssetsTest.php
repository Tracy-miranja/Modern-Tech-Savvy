<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "My Assets" - a new employee-portal page (business-employee side) showing
 * only assets currently assigned to the logged-in employee, requested
 * alongside My Projects/Performance/Disciplinary as portal parity work.
 */
class EmployeePortalMyAssetsTest extends TestCase
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
            'employee_code' => 'MYASSET-' . uniqid(), 'gender' => 'male', 'date_of_birth' => '1990-01-01',
            'marital_status' => 'single', 'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);
        EmploymentDetail::create(['employee_id' => $employee->id, 'department_id' => 1, 'job_category_id' => 1, 'employment_date' => '2020-01-01', 'employment_term' => 'permanent']);

        return [$user->fresh(), $employee->fresh()];
    }

    public function test_my_assets_page_shows_only_currently_assigned_assets_for_the_logged_in_employee(): void
    {
        [$user, $employee] = $this->makeEmployeeUser();
        [, $otherEmployee] = $this->makeEmployeeUser();

        $mine = Asset::create([
            'business_id' => $this->business->id, 'name' => 'My Laptop ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(),
            'category' => 'Electronics', 'status' => 'assigned', 'condition' => 'Good',
            'purchase_date' => '2024-01-01', 'purchase_cost' => 1200.50,
        ]);
        AssetAssignment::create([
            'asset_id' => $mine->id, 'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'assigned_by_user_id' => $user->id, 'assigned_at' => now(),
        ]);

        $returned = Asset::create([
            'business_id' => $this->business->id, 'name' => 'Old Monitor ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(),
            'category' => 'Electronics', 'status' => 'available', 'condition' => 'Good',
            'purchase_date' => '2023-01-01', 'purchase_cost' => 300,
        ]);
        AssetAssignment::create([
            'asset_id' => $returned->id, 'business_id' => $this->business->id, 'employee_id' => $employee->id,
            'assigned_by_user_id' => $user->id, 'assigned_at' => now()->subYear(), 'returned_at' => now()->subMonth(),
        ]);

        $othersAsset = Asset::create([
            'business_id' => $this->business->id, 'name' => 'Someone Elses Laptop ' . uniqid(), 'asset_tag' => 'TAG-' . uniqid(),
            'category' => 'Electronics', 'status' => 'assigned', 'condition' => 'Good',
            'purchase_date' => '2024-01-01', 'purchase_cost' => 1200.50,
        ]);
        AssetAssignment::create([
            'asset_id' => $othersAsset->id, 'business_id' => $this->business->id, 'employee_id' => $otherEmployee->id,
            'assigned_by_user_id' => $user->id, 'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'active_business_slug' => $this->business->slug,
                'active_role' => 'business-employee',
                '2fa_verified' => true,
            ])
            ->get(route('myaccount.assets.index', $this->business->slug));

        $response->assertOk();
        $response->assertSee($mine->name);
        $response->assertDontSee($returned->name);
        $response->assertDontSee($othersAsset->name);
    }
}
