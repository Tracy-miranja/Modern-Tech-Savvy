<?php

namespace Tests\Feature;

use App\Enum\Status;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Coverage for the multi-business account work: a user's own account can be
 * attached to more than one business (as owner, business-admin's "Add
 * Business", or as an employee at more than one), and needs to be
 * correctly SCOPED to whichever one is currently active - never a naive
 * "first row wins" relation. See User::activeEmployee()/switchableBusinesses(),
 * BusinessSwitchController, and BusinessController::create()/store().
 */
class BusinessSwitchingTest extends TestCase
{
    private Business $amsol;

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
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeBusiness(string $label, ?int $ownerId = null): Business
    {
        return Business::create([
            'user_id' => $ownerId ?? User::factory()->create()->id,
            'company_name' => "Switch Test {$label} " . uniqid(),
            'slug' => 'switch-test-' . strtolower($label) . '-' . uniqid(),
            'industry' => 'Testing',
            'company_size' => '1-10',
            'phone' => '070000' . random_int(1000, 9999),
            'code' => strtoupper($label) . uniqid(),
            'currency' => 'KES',
            'country' => 'Kenya',
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);
    }

    private function makeEmployeeAt(User $user, Business $business, string $role = 'business-hr'): Employee
    {
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => $business->id,
            'department_id' => 1,
            'employee_code' => 'MULTI-' . uniqid(),
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

    public function test_business_admin_owning_two_businesses_can_switch_between_them(): void
    {
        $user = User::factory()->create();
        $user->assignRole('business-admin');

        $bizA = $this->makeBusiness('A', $user->id);
        $bizB = $this->makeBusiness('B', $user->id);

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('businesses.switch', $bizB->slug));

        $response->assertOk();
        $response->assertJsonPath('data.redirect_url', route('business.index', $bizB->slug));
        $this->assertSame($bizB->slug, session('active_business_slug'));
        // Role is global on the account, not per-business - switching
        // business must not touch it.
        $this->assertSame('business-admin', session('active_role'));
    }

    public function test_switching_to_a_business_the_account_has_no_relationship_to_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('business-admin');
        $bizA = $this->makeBusiness('A', $user->id);
        $unrelated = $this->makeBusiness('Unrelated');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('businesses.switch', $unrelated->slug));

        $response->assertForbidden();
        $this->assertSame($bizA->slug, session('active_business_slug'));
    }

    public function test_employee_with_records_at_two_businesses_resolves_to_the_active_one(): void
    {
        $user = User::factory()->create();
        $bizA = $this->makeBusiness('A');
        $bizB = $this->makeBusiness('B');

        $empA = $this->makeEmployeeAt($user, $bizA);
        $empB = $this->makeEmployeeAt($user, $bizB);

        session(['active_business_slug' => $bizA->slug]);
        $this->assertTrue($user->fresh()->activeEmployee()->is($empA));

        session(['active_business_slug' => $bizB->slug]);
        $this->assertTrue($user->fresh()->activeEmployee()->is($empB));
    }

    public function test_hr_employed_at_two_businesses_can_switch_and_reach_the_new_businesss_employee_list(): void
    {
        $user = User::factory()->create();
        $bizA = $this->makeBusiness('A');
        $bizB = $this->makeBusiness('B');

        $this->makeEmployeeAt($user, $bizA, 'business-hr');
        $this->makeEmployeeAt($user, $bizB, 'business-hr');

        $switchResponse = $this->actingAs($user)
            ->withSession(['active_business_slug' => $bizA->slug, 'active_role' => 'business-hr', '2fa_verified' => true])
            ->post(route('businesses.switch', $bizB->slug));

        $switchResponse->assertOk();
        // business-hr's "home" route is business.employees.index (lacks
        // access.dashboard) - Switch Business must land them there, not on
        // business.index, which would immediately 403 them.
        $switchResponse->assertJsonPath('data.redirect_url', route('business.employees.index', $bizB->slug));

        $this->get($switchResponse->json('data.redirect_url'))->assertOk();
    }

    public function test_add_business_route_redirects_non_business_admin_roles_instead_of_setup_form(): void
    {
        $user = User::factory()->create();
        $biz = $this->makeBusiness('A');
        $this->makeEmployeeAt($user, $biz, 'business-hr');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $biz->slug, 'active_role' => 'business-hr', '2fa_verified' => true])
            ->get(route('setup.business'));

        // Full page load - the new 403 handling redirects business-hr
        // somewhere they DO have access to, instead of a bare 403.
        $response->assertRedirect(route('business.employees.index', $biz->slug));
    }

    public function test_add_business_shows_a_blank_form_not_the_existing_businesss_stale_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('business-admin');
        $existing = $this->makeBusiness('Existing', $user->id);
        $user->setStatus(Status::ACTIVE); // already completed setup once

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $existing->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->get(route('setup.business'));

        $response->assertOk();
        $response->assertViewIs('auth.business-setup');
        $response->assertViewHas('business', null);
        $response->assertDontSee($existing->company_name);
    }

    public function test_business_admin_can_add_a_second_business_and_lands_on_it(): void
    {
        $user = User::factory()->create();
        $user->assignRole('business-admin');
        $first = $this->makeBusiness('First', $user->id);
        $user->setStatus(Status::ACTIVE);

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $first->slug, 'active_role' => 'business-admin', '2fa_verified' => true])
            ->post(route('businesses.store'), [
                'name' => 'Second Business ' . uniqid(),
                'company_size' => '1-10',
                'industry' => 'Testing',
                'phone' => '07' . random_int(10000000, 99999999),
                'country' => 'Kenya',
                'code' => '254',
                'registration_no' => 'REG-' . uniqid(),
                'tax_pin_no' => 'TAX-' . uniqid(),
                'business_license_no' => 'LIC-' . uniqid(),
                'physical_address' => 'Nairobi',
                'logo' => UploadedFile::fake()->image('logo.jpg'),
            ]);

        $response->assertCreated();
        $this->assertSame(2, $user->businesses()->count());

        $second = $response->json('data.business');
        $this->assertSame($second['slug'], session('active_business_slug'));
        $this->assertSame('business-admin', session('active_role'));
    }

    public function test_non_business_admin_cannot_post_directly_to_businesses_store(): void
    {
        $user = User::factory()->create();
        $biz = $this->makeBusiness('A');
        $this->makeEmployeeAt($user, $biz, 'business-employee');

        $response = $this->actingAs($user)
            ->withSession(['active_business_slug' => $biz->slug, 'active_role' => 'business-employee', '2fa_verified' => true])
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('businesses.store'), ['name' => 'Sneaky Business']);

        // AJAX request - stays JSON, doesn't get redirected.
        $response->assertForbidden();
        $this->assertFalse($user->fresh()->hasRole('business-admin'));
    }
}
