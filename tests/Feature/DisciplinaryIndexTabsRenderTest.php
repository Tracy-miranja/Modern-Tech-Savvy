<?php

namespace Tests\Feature;

use App\Http\Controllers\WarningController;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The disciplinary page was restructured from a flat "form + list" layout
 * into Configure/Cases/Warnings/Reports tabs, with the issue/edit form
 * moved into a shared modal. This is a render smoke test to catch any
 * Blade breakage (e.g. a stray @json() truncation - see
 * feedback_blade_json_directive_bug memory) introduced by that change.
 */
class DisciplinaryIndexTabsRenderTest extends TestCase
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

    public function test_warning_index_renders_with_new_tab_structure(): void
    {
        $business = Business::find(1); // amsol
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        view()->share('errors', new \Illuminate\Support\ViewErrorBag());

        $controller = new WarningController();
        $view = $controller->index(Request::create('/employees/warning', 'GET'));
        $html = $view->render();

        $this->assertStringContainsString('id="tab-configure"', $html);
        $this->assertStringContainsString('id="tab-cases"', $html);
        $this->assertStringContainsString('id="tab-warnings"', $html);
        $this->assertStringContainsString('id="tab-reports"', $html);
        $this->assertStringContainsString('id="warningFormModal"', $html);
    }
}
