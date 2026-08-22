<?php

namespace Tests\Feature;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPaymentController;
use App\Http\Controllers\SystemHealthController;
use App\Models\Business;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Superadmin client management (module assignment pivot-preservation),
 * the manual payment ledger extending subscription_ends_at, and
 * Business::hasModule() now enforcing that expiry - all built on top of
 * the module-gating work from earlier this session.
 */
class ClientManagementAndSystemHealthTest extends TestCase
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

        DB::table('business_modules')->where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeClientBusiness(): Business
    {
        $owner = User::factory()->create();

        return Business::create([
            'user_id' => $owner->id,
            'company_name' => 'CMT Client ' . uniqid(),
            'slug' => 'cmt-client-' . uniqid(),
            'industry' => 'Technology',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'country' => 'Kenya',
            'code' => 'CMT' . rand(1000, 9999),
        ])->fresh();
    }

    private function makeModule(string $name): Module
    {
        return Module::create([
            'name' => $name,
            'description' => 'CMT test module',
            'price_monthly' => 10,
            'price_yearly' => 100,
            'is_core' => false,
            'features' => [],
        ]);
    }

    private function actingAsSuperAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));
        session(['active_business_slug' => 'amsol']);
        $this->actingAs($admin);

        return $admin;
    }

    // ---- Business::hasModule() expiry enforcement -----------------------

    public function test_hasmodule_returns_false_once_subscription_ends_at_has_passed(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Expiring Module ' . uniqid());
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => now()->subDay()]);

        $this->assertFalse($client->hasModule($module->slug));
    }

    public function test_hasmodule_returns_true_while_subscription_has_not_expired(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Active Module ' . uniqid());
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => now()->addMonth()]);

        $this->assertTrue($client->hasModule($module->slug));
    }

    public function test_hasmodule_returns_true_when_subscription_ends_at_is_null(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT No Expiry Module ' . uniqid());
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => null]);

        $this->assertTrue($client->hasModule($module->slug));
    }

    // ---- assignModules pivot preservation -------------------------------

    /**
     * Real bug: assigning ANY module to a client that has never had one
     * attached before (a brand new business, or the very first module ever
     * checked for it) 500'd - optional($existing->get($moduleId))->pivot
     * ->subscription_ends_at only null-safes the FIRST arrow
     * (optional($x)->pivot is fine when $x is null), but the second arrow
     * on the result is not - "Attempt to read property on null" once
     * ->pivot itself was null. Only ever hit via the real HTTP route
     * (every other test in this class calls the controller directly with
     * a client that already has a pivot row, which masked it). Fixed with
     * PHP's native ?-> chained through both links.
     */
    public function test_assign_modules_works_for_a_client_with_no_prior_module_assignment(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Brand New Module ' . uniqid());

        $admin = $this->actingAsSuperAdmin();
        $response = $this->actingAs($admin)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->post("/businesses/amsol/clients/{$client->slug}/modules/assign", [
                'modules' => [$module->id],
            ]);

        $response->assertOk();
        $pivot = $client->modules()->where('modules.id', $module->id)->first()->pivot;
        $this->assertTrue((bool) $pivot->is_active);
        $this->assertNull($pivot->subscription_ends_at);
    }

    /**
     * The client list's per-row "Assign Modules"/"Verify"/"Deactivate"
     * modals used stale Bootstrap 4 attributes (data-toggle/data-target/
     * data-dismiss, .close) while the rest of the app had already moved to
     * Bootstrap 5 - Bootstrap 5's JS silently ignores those, so clicking
     * "Assign Modules" on the list page did nothing at all (no error, no
     * network request - the modal just never opened). Locks in the fix.
     */
    public function test_client_list_uses_bootstrap5_modal_attributes_not_the_dead_bootstrap4_ones(): void
    {
        $client = $this->makeClientBusiness();
        $this->actingAsSuperAdmin();

        $request = Request::create('/x', 'POST');
        $response = (new ClientController())->fetch($request)->toResponse($request);
        $body = json_decode($response->getContent(), true);
        $html = $body['data'];

        $this->assertStringContainsString('data-bs-toggle="modal"', $html);
        $this->assertStringContainsString('data-bs-target="#modulesModal-' . $client->slug . '"', $html);
        $this->assertStringContainsString('data-bs-dismiss="modal"', $html);
        $this->assertStringNotContainsString('data-toggle=', $html);
        $this->assertStringNotContainsString('data-target=', $html);
        $this->assertStringNotContainsString('data-dismiss=', $html);
    }

    public function test_assign_modules_preserves_subscription_ends_at_for_a_module_that_stays_checked(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Keep Module ' . uniqid());
        $expiry = now()->addMonths(3)->startOfSecond();
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => $expiry]);

        $this->actingAsSuperAdmin();
        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['modules' => [$module->id]]);
        $response = $controller->assignModules($request, 'amsol', $client->slug)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $pivot = $client->modules()->where('modules.id', $module->id)->first()->pivot;
        $this->assertTrue((bool) $pivot->is_active);
        $this->assertSame($expiry->toDateTimeString(), \Carbon\Carbon::parse($pivot->subscription_ends_at)->toDateTimeString());
    }

    public function test_assign_modules_soft_disables_an_unchecked_module_instead_of_detaching_it(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Remove Module ' . uniqid());
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => now()->addMonth()]);

        $this->actingAsSuperAdmin();
        $controller = new ClientController();
        // Submitting with the module NOT in the selected list - it should
        // be soft-disabled (pivot row kept), not detached.
        $request = Request::create('/x', 'POST', ['modules' => []]);
        $controller->assignModules($request, 'amsol', $client->slug)->toResponse($request);

        $pivotRow = DB::table('business_modules')->where('business_id', $client->id)->where('module_id', $module->id)->first();
        $this->assertNotNull($pivotRow, 'Unchecking a module must not delete the pivot row (loses payment history).');
        $this->assertSame(0, (int) $pivotRow->is_active);
    }

    // ---- Payment ledger --------------------------------------------------

    public function test_recording_a_payment_for_a_specific_module_extends_its_subscription(): void
    {
        $client = $this->makeClientBusiness();
        $module = $this->makeModule('CMT Paid Module ' . uniqid());
        $client->modules()->attach($module->id, ['is_active' => true, 'subscription_ends_at' => now()->subDay()]);
        $this->assertFalse($client->hasModule($module->slug));

        $admin = $this->actingAsSuperAdmin();
        $controller = new ClientPaymentController();
        $periodEnd = now()->addYear()->toDateString();
        $request = Request::create('/x', 'POST', [
            'module_id' => $module->id,
            'amount' => 5000,
            'payment_method' => 'bank',
            'period_start' => now()->toDateString(),
            'period_end' => $periodEnd,
        ]);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->store($request, 'amsol', $client->slug)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($client->fresh()->hasModule($module->slug));

        $pivot = $client->modules()->where('modules.id', $module->id)->first()->pivot;
        $this->assertSame($periodEnd, \Carbon\Carbon::parse($pivot->subscription_ends_at)->toDateString());
    }

    public function test_recording_a_payment_with_no_module_selected_extends_every_attached_module(): void
    {
        $client = $this->makeClientBusiness();
        $moduleA = $this->makeModule('CMT Bundle A ' . uniqid());
        $moduleB = $this->makeModule('CMT Bundle B ' . uniqid());
        $client->modules()->attach($moduleA->id, ['is_active' => true, 'subscription_ends_at' => now()->subDay()]);
        $client->modules()->attach($moduleB->id, ['is_active' => true, 'subscription_ends_at' => now()->subDay()]);

        $admin = $this->actingAsSuperAdmin();
        $controller = new ClientPaymentController();
        $periodEnd = now()->addYear()->toDateString();
        $request = Request::create('/x', 'POST', [
            'amount' => 15000,
            'payment_method' => 'mpesa',
            'period_start' => now()->toDateString(),
            'period_end' => $periodEnd,
        ]);
        $request->setUserResolver(fn () => $admin);
        $controller->store($request, 'amsol', $client->slug)->toResponse($request);

        $client->refresh();
        $this->assertTrue($client->hasModule($moduleA->slug));
        $this->assertTrue($client->hasModule($moduleB->slug));
    }

    // ---- System Health smoke test ----------------------------------------

    public function test_system_health_page_renders_without_error(): void
    {
        $business = Business::find(1);
        $this->actingAsSuperAdmin();
        $controller = new SystemHealthController();

        $html = $controller->index($business)->render();

        $this->assertStringContainsString('System Health', $html);
        $this->assertStringContainsString('Database', $html);
    }
}
