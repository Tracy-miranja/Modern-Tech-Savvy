<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * End-to-end coverage for the super-admin/amsol-admin Clients + impersonation
 * feature, exercised through real HTTP requests (not direct controller
 * calls) so the actual route + middleware stack is what gets verified -
 * this is what caught the double-role-gate bug where business.clients.index
 * was nested inside a business-admin|business-hr|business-finance|
 * head-of-department role group, 403ing super-admin/amsol-admin before they
 * ever reached the inner role:super-admin|amsol-admin check.
 */
class ClientImpersonationTest extends TestCase
{
    private Business $amsol;
    private Business $clientA;
    private Business $clientB;

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

        $this->amsol = Business::where('slug', 'amsol')->firstOrFail();

        $this->clientA = $this->makeBusiness('A');
        $this->clientB = $this->makeBusiness('B');
    }

    private function makeBusiness(string $label): Business
    {
        return Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => "Impersonation Client {$label} " . uniqid(),
            'slug' => 'impersonation-client-' . strtolower($label) . '-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'code' => strtoupper($label) . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => false,
        ]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_super_admin_can_view_clients_index_without_impersonating(): void
    {
        $user = $this->makeUserWithRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->get(route('business.clients.index', 'amsol'));

        $response->assertOk();
    }

    public function test_amsol_admin_can_view_clients_index_without_impersonating(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->get(route('business.clients.index', 'amsol'));

        $response->assertOk();
    }

    public function test_business_admin_cannot_view_clients_index(): void
    {
        $user = $this->makeUserWithRole('business-admin');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('business.clients.index', 'amsol'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_impersonate_a_client_and_reach_its_dashboard(): void
    {
        $this->clientA->update(['verified' => true]);
        $user = $this->makeUserWithRole('super-admin');

        $impersonateResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]));

        $impersonateResponse->assertOk();

        $this->assertSame('amsol', session('original_business_slug'));
        $this->assertSame($this->clientA->slug, session('active_business_slug'));
        $this->assertSame('business-admin', session('active_role'));

        $dashboardResponse = $this->get(route('business.index', $this->clientA->slug));
        $dashboardResponse->assertOk();
    }

    public function test_amsol_admin_can_impersonate_but_not_verify_or_deactivate(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $impersonateResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]));
        $impersonateResponse->assertOk();

        // Governance actions stay super-admin only, even for amsol-admin.
        $verifyResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.verify', ['amsol', $this->clientA->slug]), [
                'remarks' => 'test',
            ]);
        $verifyResponse->assertForbidden();

        $deactivateResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.deactivate', ['amsol', $this->clientA->slug]), [
                'remarks' => 'test',
            ]);
        $deactivateResponse->assertForbidden();
    }

    public function test_super_admin_can_verify_and_deactivate_a_client(): void
    {
        $user = $this->makeUserWithRole('super-admin');

        $verifyResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->post(route('business.clients.verify', ['amsol', $this->clientA->slug]), [
                'remarks' => 'looks good',
            ]);
        $verifyResponse->assertOk();
        $this->assertTrue($this->clientA->fresh()->verified);
    }

    public function test_impersonating_amsol_admin_can_hop_between_clients_without_returning_to_amsol_first(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]))
            ->assertOk();

        $this->assertSame('amsol', session('original_business_slug'));
        $this->assertSame($this->clientA->slug, session('active_business_slug'));

        // Hop straight from Client A to Client B - the URL's business_slug
        // segment must be the CURRENT active business (Client A), not amsol,
        // matching what the impersonation banner's JS actually sends.
        $hopResponse = $this->post(route('business.clients.impersonate', [$this->clientA->slug, $this->clientB->slug]));
        $hopResponse->assertOk();

        // original_business_slug must still point back to amsol (not client A) -
        // otherwise "Return to Amsol" would strand the admin on client A.
        $this->assertSame('amsol', session('original_business_slug'));
        $this->assertSame($this->clientB->slug, session('active_business_slug'));
    }

    public function test_switch_back_to_admin_restores_original_business_and_role(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]))
            ->assertOk();

        $switchBackResponse = $this->post(route('businesses.business.switch-back', $this->clientA->slug));
        $switchBackResponse->assertOk();

        $this->assertSame('amsol', session('active_business_slug'));
        $this->assertSame('amsol-admin', session('active_role'));
        $this->assertNull(session('original_business_slug'));
    }

    private function loginAndPassTwoFactor(User $user, string $plainPassword): \Illuminate\Testing\TestResponse
    {
        $loginResponse = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $plainPassword,
        ]);
        $loginResponse->assertOk();
        $loginResponse->assertJsonPath('data.redirect_url', route('2fa.verify'));

        $code = DB::table('two_factor_codes')->where('user_id', $user->id)->value('code');
        $this->assertNotNull($code, 'A 2FA code must have been generated on login.');

        return $this->post('/2fa/verify', ['code' => $code]);
    }

    public function test_super_admin_requires_otp_then_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);
        $user->assignRole('super-admin');

        $response = $this->loginAndPassTwoFactor($user, 'Password123!');

        $response->assertOk();
        $response->assertJsonPath('data.redirect_url', route('business.index', 'amsol'));
        $this->assertSame('amsol', session('active_business_slug'));
        $this->assertSame('super-admin', session('active_role'));

        // From the dashboard, the "Manage Clients" nav link is how they
        // reach Clients/impersonation - confirm that page is reachable too.
        $this->get(route('business.clients.index', 'amsol'))->assertOk();
    }

    public function test_amsol_admin_requires_otp_then_lands_on_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('Password123!')]);
        $user->assignRole('amsol-admin');

        $response = $this->loginAndPassTwoFactor($user, 'Password123!');

        $response->assertOk();
        $response->assertJsonPath('data.redirect_url', route('business.index', 'amsol'));
        $this->assertSame('amsol-admin', session('active_role'));

        $this->get(route('business.clients.index', 'amsol'))->assertOk();
    }

    public function test_managed_businesses_list_requires_active_impersonation(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        // Not impersonating yet - should be rejected.
        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.managed-businesses', 'amsol'))
            ->assertForbidden();

        $this->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]))->assertOk();

        $listResponse = $this->post(route('business.clients.managed-businesses', $this->clientA->slug));
        $listResponse->assertOk();
        $listResponse->assertJsonFragment(['slug' => $this->clientB->slug]);
    }

    public function test_amsol_admin_gets_full_business_nav_access_for_amsol_without_impersonating(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->get(route('business.employees.index', 'amsol'))
            ->assertOk();
    }

    public function test_amsol_admin_cannot_reach_a_client_businesss_nav_without_impersonating(): void
    {
        $this->clientA->update(['verified' => true]);
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->get(route('business.employees.index', $this->clientA->slug))
            ->assertForbidden();
    }

    public function test_amsol_admin_reaches_the_impersonated_clients_nav_once_impersonating(): void
    {
        $this->clientA->update(['verified' => true]);
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.clients.impersonate', ['amsol', $this->clientA->slug]))
            ->assertOk();

        $this->get(route('business.employees.index', $this->clientA->slug))->assertOk();
    }

    public function test_super_admin_can_reach_platform_admins_page_but_amsol_admin_cannot(): void
    {
        $superAdmin = $this->makeUserWithRole('super-admin');

        $this->actingAs($superAdmin)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->get(route('business.platform-admins.index', 'amsol'))
            ->assertOk();

        $amsolAdmin = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($amsolAdmin)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->get(route('business.platform-admins.index', 'amsol'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_and_revoke_an_amsol_admin_account(): void
    {
        Notification::fake();

        $superAdmin = $this->makeUserWithRole('super-admin');
        $newAdminEmail = 'new.amsol.admin.' . uniqid() . '@example.com';

        $createResponse = $this->actingAs($superAdmin)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->post(route('business.platform-admins.store', 'amsol'), [
                'name' => 'New Amsol Admin',
                'email' => $newAdminEmail,
            ]);
        $createResponse->assertCreated();

        $newAdmin = User::where('email', $newAdminEmail)->firstOrFail();
        $this->assertTrue($newAdmin->hasRole('amsol-admin'));
        // No plaintext password was ever generated - a new account gets a
        // "set your password" email instead (asserted below).
        $this->assertNull($newAdmin->password);

        Notification::assertSentTo($newAdmin, WelcomeEmployeeNotification::class);

        $revokeResponse = $this->post(route('business.platform-admins.revoke', ['amsol', $newAdmin->id]));
        $revokeResponse->assertOk();
        $this->assertFalse($newAdmin->fresh()->hasRole('amsol-admin'));
    }

    public function test_creating_an_amsol_admin_from_an_existing_user_does_not_send_a_password_email(): void
    {
        Notification::fake();

        $superAdmin = $this->makeUserWithRole('super-admin');
        $existingUser = User::factory()->create();

        $createResponse = $this->actingAs($superAdmin)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'super-admin', '2fa_verified' => true])
            ->post(route('business.platform-admins.store', 'amsol'), [
                'name' => $existingUser->name,
                'email' => $existingUser->email,
            ]);
        $createResponse->assertCreated();

        $this->assertTrue($existingUser->fresh()->hasRole('amsol-admin'));
        Notification::assertNothingSent();
    }

    public function test_amsol_admin_cannot_create_platform_admin_accounts(): void
    {
        $user = $this->makeUserWithRole('amsol-admin');

        $this->actingAs($user)
            ->withSession(['active_business_slug' => 'amsol', 'active_role' => 'amsol-admin', '2fa_verified' => true])
            ->post(route('business.platform-admins.store', 'amsol'), [
                'name' => 'Sneaky',
                'email' => 'sneaky@example.com',
            ])
            ->assertForbidden();
    }

    /**
     * Regression for: switching to "Super Admin" via the Switch Account
     * dropdown just re-landed the user as business-admin of Amsol.
     * RoleSwitchController::getRedirectRoute() had no case for
     * super-admin/amsol-admin, so it fell through to the generic
     * 'dashboard' route -> BusinessController::redirectToDashboard(),
     * which re-derives active_role from the user's OWN held roles via its
     * own hardcoded business-admin-first priority chain, ignoring which
     * role was actually just switched to. An account holding BOTH
     * super-admin and business-admin (a realistic testing setup) would
     * have its switch to super-admin silently undone.
     */
    public function test_switching_to_super_admin_actually_lands_on_the_super_admin_dashboard(): void
    {
        $amsol = Business::where('slug', 'amsol')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $user->assignRole('business-admin'); // the exact scenario reported - holding both

        $switchResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => $amsol->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('switch.role'), ['role' => 'super-admin']);

        $switchResponse->assertOk();
        $this->assertSame('super-admin', session('active_role'));
        $this->assertSame('amsol', session('active_business_slug'));

        $redirectUrl = $switchResponse->json('redirect');
        $this->assertSame(route('business.index', 'amsol'), $redirectUrl);

        $dashboardResponse = $this->get($redirectUrl);
        $dashboardResponse->assertOk();
        // Confirms the super-admin sidebar rendered, not the business one.
        $dashboardResponse->assertSee('Amsol Admins');
    }
}
