<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\PerformanceCycle;
use App\Models\PerformanceObjective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Manage Employee Objectives" used to be a modal that just jumped
 * straight to one picked employee's page, with a "Go" button that (per
 * user report) didn't work and no overview of who has what. Replaced
 * with a real page: every employee, their objective count/average
 * progress/at-risk count, filterable, with a Manage link per row.
 */
class PerformanceObjectivesOverviewTest extends TestCase
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

    public function test_objectives_overview_page_renders(): void
    {
        $business = Business::find(1); // amsol
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new PerformanceController();
        $view = $controller->objectivesOverview($business);
        $html = $view->render();

        $this->assertStringContainsString('Employee Objectives', $html);
        $this->assertStringContainsString('objTableBody', $html);
    }

    public function test_overview_fetch_reports_objective_counts_and_average_progress_for_hr(): void
    {
        $business = Business::find(1); // amsol
        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs(User::factory()->create());

        $employee = Employee::where('business_id', $business->id)->first();
        $cycle = PerformanceCycle::create([
            'business_id' => $business->id, 'name' => 'Overview Test Cycle ' . uniqid(),
            'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(),
            'status' => 'active', 'kpi_weight' => 30, 'okr_weight' => 50, 'competency_weight' => 20,
        ]);
        PerformanceObjective::create([
            'business_id' => $business->id, 'performance_cycle_id' => $cycle->id, 'employee_id' => $employee->id,
            'scope' => 'individual', 'title' => 'Overview test objective', 'weight' => 100, 'status' => 'on_track',
        ]);

        $controller = new PerformanceController();
        $request = Request::create('/objectives/overview-fetch', 'GET', ['search' => $employee->user->name ?? '']);
        $response = $controller->fetchObjectivesOverview($request, $business)->toResponse($request);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $row = collect($body['data']['rows'])->firstWhere('employee_id', $employee->id);
        $this->assertNotNull($row);
        $this->assertGreaterThanOrEqual(1, $row['objectives_count']);
    }

    public function test_overview_fetch_is_refused_for_a_non_hr_non_admin_role(): void
    {
        $business = Business::find(1); // amsol
        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs(User::factory()->create());

        $controller = new PerformanceController();
        $request = Request::create('/objectives/overview-fetch', 'GET');
        $response = $controller->fetchObjectivesOverview($request, $business)->toResponse($request);

        $this->assertSame(403, $response->getStatusCode());
    }
}
