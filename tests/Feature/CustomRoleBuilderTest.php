<?php

namespace Tests\Feature;

use App\Http\Controllers\RoleController;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Coverage for the business-defined custom role builder (RoleController::
 * store/edit/update/destroy + Role::visibleTo/generateUniqueName): fixed
 * platform roles stay protected, custom roles are scoped per-business,
 * and a display name only needs to be unique WITHIN a business - not
 * globally - even though Spatie's own roles.name column is.
 */
class CustomRoleBuilderTest extends TestCase
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

    private function actAsBusiness(int $businessId): Business
    {
        $business = Business::findOrFail($businessId);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());
        return $business;
    }

    public function test_store_creates_a_custom_role_with_a_globally_unique_internal_name(): void
    {
        $business = $this->actAsBusiness(1);

        $request = Request::create('/roles/store', 'POST', [
            'display_name' => 'Leave Approver ' . uniqid(),
            'permissions' => ['module.leave-management.view', 'module.leave-management.approve'],
        ]);
        $response = (new RoleController())->store($request)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());

        $role = Role::where('business_id', $business->id)->where('is_custom', true)->latest('id')->first();
        $this->assertNotNull($role);
        $this->assertStringStartsWith('custom-' . $business->id . '-', $role->name);
        $this->assertTrue($role->hasPermissionTo('module.leave-management.approve'));
    }

    public function test_store_strips_permissions_for_modules_the_business_has_not_subscribed_to(): void
    {
        $business = $this->actAsBusiness(1);

        // crm-integration is a real seeded module slug; whether or not this
        // business actually has it active, forging a permission for a
        // DEFINITELY-inactive module name proves the server-side
        // re-validation, not the client's submitted checkbox state.
        $request = Request::create('/roles/store', 'POST', [
            'display_name' => 'Scoped Role ' . uniqid(),
            'permissions' => ['module.leave-management.view', 'module.not-a-real-module.view'],
        ]);
        $response = (new RoleController())->store($request)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode());

        $role = Role::with('permissions')->where('business_id', $business->id)->where('is_custom', true)->latest('id')->first();
        $this->assertFalse($role->permissions->pluck('name')->contains('module.not-a-real-module.view'));
        $this->assertTrue($role->permissions->pluck('name')->contains('module.leave-management.view'));
    }

    public function test_store_rejects_a_duplicate_display_name_within_the_same_business(): void
    {
        $business = $this->actAsBusiness(1);
        $name = 'Duplicate Name Role ' . uniqid();

        Role::create([
            'name' => Role::generateUniqueName($business->id, $name),
            'display_name' => $name,
            'guard_name' => 'web',
            'business_id' => $business->id,
            'is_custom' => true,
        ]);

        $request = Request::create('/roles/store', 'POST', ['display_name' => $name]);
        $response = (new RoleController())->store($request)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_two_different_businesses_can_reuse_the_same_display_name(): void
    {
        $name = 'Shared Display Name ' . uniqid();

        $businessA = $this->actAsBusiness(1);
        $requestA = Request::create('/roles/store', 'POST', ['display_name' => $name]);
        $responseA = (new RoleController())->store($requestA)->toResponse($requestA);
        $this->assertSame(201, $responseA->getStatusCode());

        $businessB = $this->actAsBusiness(15);
        $requestB = Request::create('/roles/store', 'POST', ['display_name' => $name]);
        $responseB = (new RoleController())->store($requestB)->toResponse($requestB);
        $this->assertSame(201, $responseB->getStatusCode());

        $roleA = Role::where('business_id', $businessA->id)->where('display_name', $name)->first();
        $roleB = Role::where('business_id', $businessB->id)->where('display_name', $name)->first();
        $this->assertNotNull($roleA);
        $this->assertNotNull($roleB);
        $this->assertNotSame($roleA->name, $roleB->name, 'Internal names must still differ even though display names match.');
    }

    public function test_update_renames_and_resyncs_permissions_for_a_custom_role(): void
    {
        $business = $this->actAsBusiness(1);
        $role = Role::create([
            'name' => Role::generateUniqueName($business->id, 'Original Name'),
            'display_name' => 'Original Name ' . uniqid(),
            'guard_name' => 'web',
            'business_id' => $business->id,
            'is_custom' => true,
        ]);
        $role->syncPermissions(['module.leave-management.view']);

        $newName = 'Renamed ' . uniqid();
        $request = Request::create('/roles/update', 'POST', [
            'role_id' => $role->id,
            'display_name' => $newName,
            'permissions' => ['module.leave-management.approve'],
        ]);
        $response = (new RoleController())->update($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $role->refresh();
        $this->assertSame($newName, $role->display_name);
        $this->assertTrue($role->hasPermissionTo('module.leave-management.approve'));
        $this->assertFalse($role->hasPermissionTo('module.leave-management.view'));
    }

    public function test_update_rejects_a_fixed_platform_role(): void
    {
        $this->actAsBusiness(1);
        $fixedRole = Role::where('name', 'business-hr')->where('guard_name', 'web')->firstOrFail();

        $request = Request::create('/roles/update', 'POST', [
            'role_id' => $fixedRole->id,
            'display_name' => 'Hijacked Name',
        ]);
        $response = (new RoleController())->update($request)->toResponse($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('business-hr', $fixedRole->fresh()->name);
    }

    public function test_destroy_rejects_a_fixed_platform_role(): void
    {
        $this->actAsBusiness(1);
        $fixedRole = Role::where('name', 'business-admin')->where('guard_name', 'web')->firstOrFail();

        $request = Request::create('/roles/destroy', 'POST', ['role_id' => $fixedRole->id]);
        $response = (new RoleController())->destroy($request)->toResponse($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(Role::find($fixedRole->id));
    }

    public function test_destroy_rejects_a_custom_role_belonging_to_a_different_business(): void
    {
        $otherBusiness = Business::findOrFail(15);
        $foreignRole = Role::create([
            'name' => Role::generateUniqueName($otherBusiness->id, 'Foreign Role'),
            'display_name' => 'Foreign Role ' . uniqid(),
            'guard_name' => 'web',
            'business_id' => $otherBusiness->id,
            'is_custom' => true,
        ]);

        $this->actAsBusiness(1);
        $request = Request::create('/roles/destroy', 'POST', ['role_id' => $foreignRole->id]);
        $response = (new RoleController())->destroy($request)->toResponse($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(Role::find($foreignRole->id));
    }

    public function test_destroy_removes_a_custom_role_belonging_to_the_active_business(): void
    {
        $business = $this->actAsBusiness(1);
        $role = Role::create([
            'name' => Role::generateUniqueName($business->id, 'Deletable Role'),
            'display_name' => 'Deletable Role ' . uniqid(),
            'guard_name' => 'web',
            'business_id' => $business->id,
            'is_custom' => true,
        ]);

        $request = Request::create('/roles/destroy', 'POST', ['role_id' => $role->id]);
        $response = (new RoleController())->destroy($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull(Role::find($role->id));
    }

    public function test_fetch_only_shows_this_businesss_own_custom_roles_alongside_the_fixed_set(): void
    {
        $businessA = $this->actAsBusiness(1);
        $ownRole = Role::create([
            'name' => Role::generateUniqueName($businessA->id, 'Own Role'),
            'display_name' => 'Own Role ' . uniqid(),
            'guard_name' => 'web',
            'business_id' => $businessA->id,
            'is_custom' => true,
        ]);

        $businessB = Business::findOrFail(15);
        $foreignRole = Role::create([
            'name' => Role::generateUniqueName($businessB->id, 'Foreign Visible Role'),
            'display_name' => 'Foreign Visible Role ' . uniqid(),
            'guard_name' => 'web',
            'business_id' => $businessB->id,
            'is_custom' => true,
        ]);

        $visible = Role::businessAssignable()->visibleTo($businessA->id)->pluck('id');
        $this->assertTrue($visible->contains($ownRole->id));
        $this->assertFalse($visible->contains($foreignRole->id));
    }
}
