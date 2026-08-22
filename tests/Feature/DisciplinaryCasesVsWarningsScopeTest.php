<?php

namespace Tests\Feature;

use App\Http\Controllers\WarningController;
use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Cases" and "Warnings" are two filtered views of the same warnings
 * table - not every warning rises to the level of a disciplinary case,
 * only those whose stage is flagged is_disciplinary_case (see the
 * Configure tab). This covers the ?scope=cases filter and pagination
 * added to WarningController::fetch() for the disciplinary redesign.
 */
class DisciplinaryCasesVsWarningsScopeTest extends TestCase
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

    public function test_cases_scope_only_returns_warnings_whose_stage_is_flagged_as_a_case(): void
    {
        $business = Business::find(1); // amsol
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $employee = Employee::where('business_id', $business->id)->first();

        $warningStage = DisciplinaryStageType::create([
            'business_id' => $business->id, 'name' => 'Test Verbal ' . uniqid(), 'slug' => 'test_verbal_' . uniqid(),
            'sequence_order' => 900, 'is_terminal' => false, 'requires_response' => false, 'is_disciplinary_case' => false,
        ]);
        $caseStage = DisciplinaryStageType::create([
            'business_id' => $business->id, 'name' => 'Test Final ' . uniqid(), 'slug' => 'test_final_' . uniqid(),
            'sequence_order' => 901, 'is_terminal' => true, 'requires_response' => true, 'is_disciplinary_case' => true,
        ]);

        $plainWarning = Warning::create([
            'business_id' => $business->id, 'employee_id' => $employee->id, 'stage_type_id' => $warningStage->id,
            'severity' => 'low', 'issue_date' => now()->toDateString(), 'reason' => 'Late', 'status' => 'active', 'issued_by' => auth()->id(),
        ]);
        $caseWarning = Warning::create([
            'business_id' => $business->id, 'employee_id' => $employee->id, 'stage_type_id' => $caseStage->id,
            'severity' => 'high', 'issue_date' => now()->toDateString(), 'reason' => 'Misconduct', 'status' => 'active', 'issued_by' => auth()->id(),
        ]);

        $controller = new WarningController();

        $allRequest = Request::create('/warning/fetch', 'POST', []);
        $allResponse = $controller->fetch($allRequest)->toResponse($allRequest);
        $allBody = json_decode($allResponse->getContent(), true);
        $this->assertStringContainsString('Misconduct', $allBody['data']['html']);
        $this->assertStringContainsString('Late', $allBody['data']['html']);

        $casesRequest = Request::create('/warning/fetch', 'POST', ['scope' => 'cases']);
        $casesResponse = $controller->fetch($casesRequest)->toResponse($casesRequest);
        $casesBody = json_decode($casesResponse->getContent(), true);
        $this->assertStringContainsString('Misconduct', $casesBody['data']['html']);
        $this->assertStringNotContainsString('Late', $casesBody['data']['html']);
    }

    public function test_fetch_paginates_and_reports_current_and_last_page(): void
    {
        $business = Business::find(1); // amsol
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $employee = Employee::where('business_id', $business->id)->first();

        for ($i = 0; $i < 3; $i++) {
            Warning::create([
                'business_id' => $business->id, 'employee_id' => $employee->id,
                'severity' => 'low', 'issue_date' => now()->toDateString(), 'reason' => 'Paginate test ' . uniqid(), 'status' => 'active', 'issued_by' => auth()->id(),
            ]);
        }

        $controller = new WarningController();
        $request = Request::create('/warning/fetch', 'POST', ['per_page' => 2]);
        $response = $controller->fetch($request)->toResponse($request);
        $body = json_decode($response->getContent(), true);

        $this->assertSame(1, $body['data']['current_page']);
        $this->assertGreaterThanOrEqual(2, $body['data']['last_page']);
    }
}
