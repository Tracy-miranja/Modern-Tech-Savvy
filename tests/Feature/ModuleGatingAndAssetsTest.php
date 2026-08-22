<?php

namespace Tests\Feature;

use App\Http\Controllers\AssetController;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Module-access gating (Business::hasModule() + EnsureBusinessModuleActive)
 * and the Asset Management module built to exercise it - see the
 * grandfather-clause docblock on Business::hasModule() for why enabling
 * gating can never silently lock out a business that never selected
 * modules at all.
 */
class ModuleGatingAndAssetsTest extends TestCase
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

        // Guarantee a clean slate regardless of incidental prior state -
        // the grandfather-clause tests below depend on business 1 genuinely
        // having zero business_modules rows at the start of each test.
        DB::table('business_modules')->where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeModule(string $name): Module
    {
        return Module::create([
            'name' => $name,
            'description' => 'MGA test module',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'is_core' => false,
            'features' => [],
        ]);
    }

    private function actingAsAdmin(Business $business): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));
        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return $admin;
    }

    // ---- Business::hasModule() -----------------------------------------

    public function test_grandfather_clause_allows_access_when_the_business_never_selected_any_modules(): void
    {
        $business = Business::find(1);

        $this->assertSame(0, $business->modules()->count());
        $this->assertTrue($business->hasModule('asset-management'));
    }

    public function test_hasmodule_returns_false_when_the_business_has_other_modules_but_not_this_one(): void
    {
        $business = Business::find(1);
        $other = $this->makeModule('MGA Other Module ' . uniqid());
        $business->modules()->attach($other->id, ['is_active' => true]);

        $this->assertFalse($business->hasModule('asset-management'));
    }

    public function test_hasmodule_returns_true_when_the_module_is_active_for_the_business(): void
    {
        $business = Business::find(1);
        $assetModule = Module::firstOrCreate(
            ['slug' => 'asset-management'],
            ['name' => 'Asset Management', 'description' => 'x', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false, 'features' => []]
        );
        $business->modules()->attach($assetModule->id, ['is_active' => true]);

        $this->assertTrue($business->hasModule('asset-management'));
    }

    public function test_hasmodule_returns_false_when_the_module_is_attached_but_inactive(): void
    {
        $business = Business::find(1);
        $assetModule = Module::firstOrCreate(
            ['slug' => 'asset-management'],
            ['name' => 'Asset Management', 'description' => 'x', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false, 'features' => []]
        );
        $business->modules()->attach($assetModule->id, ['is_active' => false]);

        $this->assertFalse($business->hasModule('asset-management'));
    }

    // ---- Middleware (HTTP-level, since middleware only runs through the
    // real route pipeline, not a direct controller call) -------------------

    public function test_middleware_redirects_when_business_has_other_modules_but_not_asset_management(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $other = $this->makeModule('MGA Other Module ' . uniqid());
        $business->modules()->attach($other->id, ['is_active' => true]);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.assets.index', $business->slug));

        $response->assertRedirect(route('business.index', $business->slug));
    }

    public function test_middleware_allows_access_when_business_never_selected_modules(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);

        $response = $this->withSession(['active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.assets.index', $business->slug));

        $response->assertOk();
    }

    // ---- Asset CRUD + assign/return ------------------------------------

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'MGA-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ])->fresh();
    }

    public function test_an_asset_cannot_be_created_with_a_duplicate_tag(): void
    {
        $business = Business::find(1);
        $this->actingAsAdmin($business);
        $controller = new AssetController();

        $tag = 'MGA-TAG-' . uniqid();
        Asset::create(['business_id' => 1, 'name' => 'Laptop A', 'asset_tag' => $tag, 'status' => 'available']);

        $request = Request::create('/x', 'POST', ['name' => 'Laptop B', 'asset_tag' => $tag]);
        $response = $controller->store($request, $business)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(1, Asset::where('business_id', 1)->where('asset_tag', $tag)->count());
    }

    public function test_assigning_and_returning_an_asset_tracks_status_and_history(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $asset = Asset::create(['business_id' => 1, 'name' => 'MGA Laptop', 'asset_tag' => 'MGA-' . uniqid(), 'status' => 'available']);

        $controller = new AssetController();

        $assignRequest = Request::create('/x', 'POST', ['employee_id' => $employee->id]);
        $assignRequest->setUserResolver(fn () => $admin);
        $assignResponse = $controller->assign($assignRequest, $business, $asset)->toResponse($assignRequest);
        $this->assertSame(201, $assignResponse->getStatusCode(), $assignResponse->getContent());
        $this->assertSame('assigned', $asset->fresh()->status);

        $assignment = AssetAssignment::where('asset_id', $asset->id)->whereNull('returned_at')->first();
        $this->assertNotNull($assignment);
        $this->assertSame($employee->id, $assignment->employee_id);

        $returnRequest = Request::create('/x', 'POST', ['return_condition' => 'fair']);
        $returnRequest->setUserResolver(fn () => $admin);
        $returnResponse = $controller->returnAsset($returnRequest, $business, $asset)->toResponse($returnRequest);
        $this->assertSame(200, $returnResponse->getStatusCode());

        $asset->refresh();
        $this->assertSame('available', $asset->status);
        $this->assertSame('fair', $asset->condition);
        $this->assertNotNull($assignment->fresh()->returned_at);
    }

    public function test_an_assigned_asset_cannot_be_deleted_until_returned(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();
        $asset = Asset::create(['business_id' => 1, 'name' => 'MGA Phone', 'asset_tag' => 'MGA-' . uniqid(), 'status' => 'available']);

        $controller = new AssetController();
        $assignRequest = Request::create('/x', 'POST', ['employee_id' => $employee->id]);
        $assignRequest->setUserResolver(fn () => $admin);
        $controller->assign($assignRequest, $business, $asset)->toResponse($assignRequest);

        $destroyResponse = $controller->destroy(Request::create('/x', 'DELETE'), $business, $asset)->toResponse(Request::create('/x'));
        $this->assertSame(400, $destroyResponse->getStatusCode());
        $this->assertNotNull(Asset::find($asset->id));
    }

    public function test_employee_assets_endpoint_only_returns_currently_assigned_ones(): void
    {
        $business = Business::find(1);
        $admin = $this->actingAsAdmin($business);
        $employee = $this->makeEmployee();

        $current = Asset::create(['business_id' => 1, 'name' => 'MGA Current', 'asset_tag' => 'MGA-' . uniqid(), 'status' => 'assigned']);
        AssetAssignment::create(['asset_id' => $current->id, 'business_id' => 1, 'employee_id' => $employee->id, 'assigned_at' => now(), 'assigned_by_user_id' => $admin->id]);

        $returned = Asset::create(['business_id' => 1, 'name' => 'MGA Returned', 'asset_tag' => 'MGA-' . uniqid(), 'status' => 'available']);
        AssetAssignment::create(['asset_id' => $returned->id, 'business_id' => 1, 'employee_id' => $employee->id, 'assigned_at' => now()->subDays(5), 'assigned_by_user_id' => $admin->id, 'returned_at' => now()]);

        $controller = new AssetController();
        $response = $controller->employeeAssets(Request::create('/x'), $business, $employee)->toResponse(Request::create('/x'));
        $data = json_decode($response->getContent(), true)['data'];

        $this->assertCount(1, $data);
        $this->assertSame($current->id, $data[0]['asset_id']);
    }
}
