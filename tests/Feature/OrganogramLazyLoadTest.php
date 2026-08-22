<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * OrganogramController::fetch() was rewritten from "load the whole company
 * nested in one payload" to lazy, one-level-at-a-time loading (see GUIDE
 * plan org-structure redesign, the 1000+-employee scale concern) - this
 * proves the new contract: roots only by default, direct reports only for
 * a given parent_id, has_children/direct_reports_count present, and search
 * returns the correct root-first ancestor chain.
 */
class OrganogramLazyLoadTest extends TestCase
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

    private function actingAsAdmin(Business $business)
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        return $this->actingAs($admin)->withSession([
            'active_business_slug' => $business->slug,
            'active_role' => 'business-admin',
            '2fa_verified' => true,
        ]);
    }

    private function makeEmployee(?int $managerId = null, int $businessId = 1): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'business_id' => $businessId,
            'department_id' => 1,
            'manager_id' => $managerId,
            'employee_code' => 'OLT-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();
    }

    public function test_fetch_with_no_parent_id_returns_only_root_nodes_with_child_counts(): void
    {
        $business = Business::find(1);
        $root = $this->makeEmployee();
        $child = $this->makeEmployee($root->id);
        $grandchild = $this->makeEmployee($child->id);

        $response = $this->actingAsAdmin($business)
            ->get(route('business.organogram.fetch', $business->slug));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($root->id));
        $this->assertFalse($ids->contains($child->id), 'Direct reports must not be included in the root-level response.');
        $this->assertFalse($ids->contains($grandchild->id));

        $rootNode = collect($response->json('data'))->firstWhere('id', $root->id);
        $this->assertTrue($rootNode['has_children']);
        $this->assertSame(1, $rootNode['direct_reports_count']);
    }

    public function test_fetch_with_parent_id_returns_only_that_employees_direct_reports(): void
    {
        $business = Business::find(1);
        $root = $this->makeEmployee();
        $child = $this->makeEmployee($root->id);
        $grandchild = $this->makeEmployee($child->id);

        $response = $this->actingAsAdmin($business)
            ->get(route('business.organogram.fetch', $business->slug) . '?parent_id=' . $root->id);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($child->id));
        $this->assertFalse($ids->contains($grandchild->id), 'Only ONE level should ever be returned per request, regardless of depth.');
        $this->assertFalse($ids->contains($root->id));

        $childNode = collect($response->json('data'))->firstWhere('id', $child->id);
        $this->assertTrue($childNode['has_children']);
        $this->assertSame(1, $childNode['direct_reports_count']);
    }

    public function test_fetch_treats_an_orphaned_manager_reference_as_a_root(): void
    {
        $business = Business::find(1);
        // manager_id references a real employees row (satisfies the FK) but
        // one that belongs to a DIFFERENT business - the realistic shape an
        // "orphaned" reference actually takes here.
        $otherBusinessId = \App\Models\Business::where('id', '!=', 1)->value('id');
        $foreignManager = $this->makeEmployee(null, $otherBusinessId);
        $orphan = $this->makeEmployee($foreignManager->id);

        $response = $this->actingAsAdmin($business)
            ->get(route('business.organogram.fetch', $business->slug));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($orphan->id), 'An employee whose manager belongs to a different business must still surface as a root, not silently vanish.');
    }

    public function test_search_returns_the_root_first_ancestor_chain(): void
    {
        $business = Business::find(1);
        $root = $this->makeEmployee();
        $middle = $this->makeEmployee($root->id);
        $target = $this->makeEmployee($middle->id);
        $target->user->update(['name' => 'Findable OLT ' . uniqid()]);

        $response = $this->actingAsAdmin($business)
            ->get(route('business.organogram.search', $business->slug) . '?q=' . urlencode('Findable OLT'));

        $response->assertOk();
        $result = collect($response->json('data'))->firstWhere('id', $target->id);

        $this->assertNotNull($result);
        $this->assertSame([$root->id, $middle->id], $result['ancestor_ids']);
    }
}
