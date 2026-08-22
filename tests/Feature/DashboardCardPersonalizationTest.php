<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Business;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The dashboard used to show every card to every business regardless of
 * relevance - "Total Clients" (an Amsol-platform-only metric, always 0 for
 * a client business) and "Active Loans"/"Active Advances" (Payroll
 * Management features) rendered unconditionally. Cards are now built
 * per-business: Total Clients only for Amsol's own business, loans/
 * advances only when Payroll Management is actually subscribed.
 */
class DashboardCardPersonalizationTest extends TestCase
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

    private function makeClientBusiness(): Business
    {
        $owner = User::factory()->create();

        return Business::create([
            'user_id' => $owner->id,
            'company_name' => 'DCP Client ' . uniqid(),
            'slug' => 'dcp-client-' . uniqid(),
            'industry' => 'Technology',
            'company_size' => '1-10',
            'phone' => '0700000000',
            'country' => 'Kenya',
            'currency' => 'KES',
            'code' => 'DCP' . uniqid(),
            'physical_address' => 'Nairobi',
            'verified' => true,
        ]);
    }

    private function cardTitlesFor(Business $business): array
    {
        session(['active_business_slug' => $business->slug]);
        $view = (new DashboardController())->index(Request::create('/x', 'GET'));

        return collect($view->getData()['cards'])->pluck('title')->all();
    }

    public function test_amsols_own_dashboard_shows_total_clients(): void
    {
        $amsol = Business::find(1);

        $this->assertContains('Total Clients', $this->cardTitlesFor($amsol));
    }

    public function test_a_client_business_never_sees_total_clients(): void
    {
        $client = $this->makeClientBusiness();

        $this->assertNotContains('Total Clients', $this->cardTitlesFor($client));
    }

    public function test_a_business_without_payroll_module_does_not_see_loan_or_advance_cards(): void
    {
        $client = $this->makeClientBusiness();
        // Give it some OTHER module so it's no longer grandfathered to
        // full access - a genuine "did not select payroll" business.
        $otherModule = Module::firstOrCreate(['name' => 'Asset Management'], ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]);
        $client->modules()->attach($otherModule->id, ['is_active' => true]);

        $titles = $this->cardTitlesFor($client->fresh());

        $this->assertNotContains('Active Loans', $titles);
        $this->assertNotContains('Active Advances', $titles);
    }

    public function test_a_business_with_payroll_module_sees_loan_and_advance_cards(): void
    {
        $client = $this->makeClientBusiness();
        $payrollModule = Module::firstOrCreate(['name' => 'Payroll Management'], ['description' => 'Test', 'price_monthly' => 0, 'price_yearly' => 0, 'is_core' => false]);
        $client->modules()->attach($payrollModule->id, ['is_active' => true]);

        $titles = $this->cardTitlesFor($client->fresh());

        $this->assertContains('Active Loans', $titles);
        $this->assertContains('Active Advances', $titles);
    }

    public function test_core_cards_always_show_regardless_of_business(): void
    {
        $client = $this->makeClientBusiness();
        $titles = $this->cardTitlesFor($client);

        $this->assertContains('Business Employees', $titles);
        $this->assertContains('On Leave Employees', $titles);
        $this->assertContains('Locations', $titles);
        $this->assertContains('Leave Requests', $titles);
        $this->assertContains('Employee Turnover', $titles);
    }
}
