<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Http\Controllers\ClientController;
use App\Mail\AccessRequestMail;
use App\Mail\BusinessStatusMail;
use App\Mail\InviteUserMail;
use App\Models\AccessRequest;
use App\Models\Business;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The "Account Sharing" workflow (invite a colleague into a business /
 * approve their request) - previously fully implemented in ClientController
 * but unrouted. Wired up onto business.clients.request-access/grant-access
 * (GET pages) and business.clients.access.request/access.grant (POST
 * actions), see routes/web.php and routes/requests.php.
 */
class AccessSharingWorkflowTest extends TestCase
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

    private function actingAsBusinessAdmin(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'business-admin', 'guard_name' => 'web']));

        $business = Business::create([
            'user_id' => $admin->id,
            'company_name' => 'ASW Business ' . uniqid(),
            'slug' => 'asw-business-' . uniqid(),
            'industry' => 'Technology',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'country' => 'Kenya',
            'code' => 'ASW' . rand(1000, 9999),
        ])->fresh();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($admin);

        return [$admin, $business];
    }

    public function test_request_access_creates_a_pending_request_and_invites_a_new_user(): void
    {
        Mail::fake();
        [$admin, $business] = $this->actingAsBusinessAdmin();

        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['email' => 'newcolleague@example.com']);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->requestAccess($request)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $accessRequest = AccessRequest::where('business_id', $business->id)
            ->where('email', 'newcolleague@example.com')
            ->first();
        $this->assertNotNull($accessRequest);
        $this->assertSame(Status::PENDING, $accessRequest->status()->name);
        $this->assertNotEmpty($accessRequest->registration_token);

        Mail::assertSent(InviteUserMail::class);
        Mail::assertNotSent(AccessRequestMail::class);
    }

    public function test_request_access_sends_the_existing_user_variant_when_the_email_already_has_an_account(): void
    {
        Mail::fake();
        [$admin, $business] = $this->actingAsBusinessAdmin();
        $existingUser = User::factory()->create(['email' => 'already-has-account@example.com']);

        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['email' => 'already-has-account@example.com']);
        $request->setUserResolver(fn () => $admin);
        $controller->requestAccess($request)->toResponse($request);

        Mail::assertSent(AccessRequestMail::class);
        Mail::assertNotSent(InviteUserMail::class);
    }

    public function test_request_access_rejects_a_duplicate_pending_request_for_the_same_email(): void
    {
        Mail::fake();
        [$admin, $business] = $this->actingAsBusinessAdmin();

        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['email' => 'dup@example.com']);
        $request->setUserResolver(fn () => $admin);
        $controller->requestAccess($request)->toResponse($request);

        $second = Request::create('/x', 'POST', ['email' => 'dup@example.com']);
        $second->setUserResolver(fn () => $admin);
        $response = $controller->requestAccess($second)->toResponse($second);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(1, AccessRequest::where('business_id', $business->id)->where('email', 'dup@example.com')->count());
    }

    public function test_grant_access_assigns_the_spatie_role_creates_a_client_row_and_approves_the_request(): void
    {
        Mail::fake();
        [$admin, $business] = $this->actingAsBusinessAdmin();
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $accessRequest = AccessRequest::create([
            'requester_id' => $admin->id,
            'business_id' => $business->id,
            'email' => $invitee->email,
            'registration_token' => 'test-token-' . uniqid(),
        ]);
        $accessRequest->setStatus(Status::PENDING);

        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['request_id' => $accessRequest->id, 'role' => 'business-employee']);
        $request->setUserResolver(fn () => $admin);
        $response = $controller->grantAccess($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($invitee->fresh()->hasRole('business-employee'));
        $this->assertTrue(
            Client::where('business_id', $business->id)->where('employee_id', $invitee->id)->exists()
        );
        $this->assertSame(Status::APPROVED, $accessRequest->fresh()->status()->name);

        Mail::assertSent(BusinessStatusMail::class);
    }

    public function test_grant_access_rejects_an_unknown_request_id(): void
    {
        [$admin, $business] = $this->actingAsBusinessAdmin();

        $controller = new ClientController();
        $request = Request::create('/x', 'POST', ['request_id' => 999999, 'role' => 'business-employee']);
        $request->setUserResolver(fn () => $admin);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->grantAccess($request);
    }

    // ---- Page-view smoke tests (direct controller call, matching this
    // suite's established pattern - a real HTTP GET here would also need
    // to clear VerifyBusiness/EnsureTwoFactorAuthenticated, which the
    // sibling ClientManagementAndSystemHealthTest sidesteps the same way
    // for its system-health smoke test). ---------------------------------

    public function test_request_access_page_renders_for_a_business_admin(): void
    {
        [$admin, $business] = $this->actingAsBusinessAdmin();

        $controller = new ClientController();
        $html = $controller->showRequestAccess(Request::create('/x'))->render();

        $this->assertStringContainsString('Request Access', $html);
        $this->assertStringContainsString('Account Sharing', $html);
    }

    public function test_grant_access_page_lists_pending_requests_for_a_business_admin(): void
    {
        [$admin, $business] = $this->actingAsBusinessAdmin();
        $accessRequest = AccessRequest::create([
            'requester_id' => $admin->id,
            'business_id' => $business->id,
            'email' => 'pending-listee@example.com',
            'registration_token' => 'test-token-' . uniqid(),
        ]);
        $accessRequest->setStatus(Status::PENDING);

        $controller = new ClientController();
        $html = $controller->showGrantAccess(Request::create('/x'))->render();

        $this->assertStringContainsString('pending-listee@example.com', $html);
    }
}
