<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\OrganogramCanvasEdge;
use App\Models\OrganogramCanvasNode;
use App\Models\OrganogramRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The Figma-style structure canvas (see GUIDE plan org-structure redesign):
 * departments/roles/job categories as draggable nodes, connections between
 * roles ARE the real reports_to_role_id edge (not just a picture of one).
 */
class OrganizationStructureCanvasTest extends TestCase
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

    public function test_canvas_graph_auto_syncs_nodes_for_every_department_role_and_job_category(): void
    {
        $business = Business::find(1);
        $department = Department::create(['business_id' => 1, 'name' => 'OSC Dept ' . uniqid()]);
        $role = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Role ' . uniqid(), 'level' => 1]);
        $jobCategory = JobCategory::create(['business_id' => 1, 'name' => 'OSC Job ' . uniqid()]);

        $response = $this->actingAsAdmin($business)
            ->get(route('business.organization-structure.canvas.graph', $business->slug));

        $response->assertOk();
        $labels = collect($response->json('data.nodes'))->pluck('label');

        $this->assertTrue($labels->contains($department->name));
        $this->assertTrue($labels->contains($role->name));
        $this->assertTrue($labels->contains($jobCategory->name));
    }

    public function test_connecting_two_roles_sets_reports_to_role_id_and_shows_up_as_an_edge(): void
    {
        $business = Business::find(1);
        $parent = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Parent ' . uniqid(), 'level' => 1]);
        $child = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Child ' . uniqid(), 'level' => 2]);

        $session = $this->actingAsAdmin($business);
        $session->get(route('business.organization-structure.canvas.graph', $business->slug));

        $parentNode = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'role')->where('ref_id', $parent->id)->firstOrFail();
        $childNode = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'role')->where('ref_id', $child->id)->firstOrFail();

        $response = $session->postJson(route('business.organization-structure.canvas.edges.store', $business->slug), [
            'from_node_id' => $childNode->id,
            'to_node_id' => $parentNode->id,
        ]);

        $response->assertOk();
        $this->assertSame($parent->id, $child->fresh()->reports_to_role_id);

        $graph = $session->get(route('business.organization-structure.canvas.graph', $business->slug));
        $edge = collect($graph->json('data.edges'))->firstWhere('from_node_id', $childNode->id);
        $this->assertNotNull($edge);
        $this->assertSame('reports_to_role_id', $edge['source']);
    }

    public function test_connecting_two_roles_that_would_create_a_cycle_is_rejected(): void
    {
        $business = Business::find(1);
        $roleA = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC A ' . uniqid(), 'level' => 1]);
        $roleB = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC B ' . uniqid(), 'level' => 2, 'reports_to_role_id' => $roleA->id]);

        $session = $this->actingAsAdmin($business);
        $session->get(route('business.organization-structure.canvas.graph', $business->slug));

        $nodeA = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'role')->where('ref_id', $roleA->id)->firstOrFail();
        $nodeB = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'role')->where('ref_id', $roleB->id)->firstOrFail();

        // A already reports (indirectly) nowhere, B reports to A - now try
        // to make A report to B, which would create a 2-node cycle.
        $response = $session->postJson(route('business.organization-structure.canvas.edges.store', $business->slug), [
            'from_node_id' => $nodeA->id,
            'to_node_id' => $nodeB->id,
        ]);

        $response->assertStatus(400);
        $this->assertNull($roleA->fresh()->reports_to_role_id);
    }

    public function test_removing_a_role_role_edge_clears_reports_to_role_id(): void
    {
        $business = Business::find(1);
        $parent = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Parent ' . uniqid(), 'level' => 1]);
        $child = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Child ' . uniqid(), 'level' => 2, 'reports_to_role_id' => $parent->id]);

        $session = $this->actingAsAdmin($business);

        $session->deleteJson(route('business.organization-structure.canvas.edges.destroy', [$business->slug, 'role-' . $child->id]))
            ->assertOk();

        $this->assertNull($child->fresh()->reports_to_role_id);
    }

    public function test_connecting_a_role_and_a_department_is_purely_visual_and_does_not_touch_any_fk(): void
    {
        $business = Business::find(1);
        $role = OrganogramRole::create(['business_id' => 1, 'name' => 'OSC Role ' . uniqid(), 'level' => 1]);
        $department = Department::create(['business_id' => 1, 'name' => 'OSC Dept ' . uniqid()]);

        $session = $this->actingAsAdmin($business);
        $session->get(route('business.organization-structure.canvas.graph', $business->slug));

        $roleNode = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'role')->where('ref_id', $role->id)->firstOrFail();
        $deptNode = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'department')->where('ref_id', $department->id)->firstOrFail();

        $response = $session->postJson(route('business.organization-structure.canvas.edges.store', $business->slug), [
            'from_node_id' => $roleNode->id,
            'to_node_id' => $deptNode->id,
        ]);

        $response->assertCreated();
        $this->assertSame('custom', $response->json('data.source'));
        $this->assertDatabaseHas('organogram_canvas_edges', [
            'from_node_id' => $roleNode->id,
            'to_node_id' => $deptNode->id,
        ]);
        // No FK on OrganogramRole to touch here - this connection is purely
        // a visual grouping, unlike the role-role case above.
        $this->assertNull($role->fresh()->reports_to_role_id);
    }

    public function test_updating_a_node_position_persists(): void
    {
        $business = Business::find(1);
        $department = Department::create(['business_id' => 1, 'name' => 'OSC Dept ' . uniqid()]);

        $session = $this->actingAsAdmin($business);
        $session->get(route('business.organization-structure.canvas.graph', $business->slug));

        $node = OrganogramCanvasNode::where('business_id', 1)->where('node_type', 'department')->where('ref_id', $department->id)->firstOrFail();

        $session->postJson(route('business.organization-structure.canvas.nodes.position', [$business->slug, $node->id]), [
            'pos_x' => 123,
            'pos_y' => 456,
        ])->assertOk();

        $this->assertSame(123, $node->fresh()->pos_x);
        $this->assertSame(456, $node->fresh()->pos_y);
    }
}
