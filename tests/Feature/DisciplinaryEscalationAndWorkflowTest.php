<?php

namespace Tests\Feature;

use App\Http\Controllers\WarningController;
use App\Models\Business;
use App\Models\DisciplinaryStageType;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warning;
use App\Services\DisciplinaryStageTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Escalation using the business's CONFIGURED stage order (not the old
 * hardcoded ladder), Show Cause responses, and the case detail page's
 * business scoping - see GUIDE plan Phase 3.
 */
class DisciplinaryEscalationAndWorkflowTest extends TestCase
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

    private function makeEmployeeUser(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'DEW-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return [$user->fresh(), $employee->fresh()];
    }

    // ---- Escalation using configured order --------------------------

    public function test_escalate_creates_the_next_case_at_the_configured_stage_and_resolves_the_prior_one(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);
        [$issuer, $employee] = $this->makeEmployeeUser();

        $verbalStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'verbal_warning')->first();
        $writtenStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'written_warning')->first();

        $verbal = Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'stage_type_id' => $verbalStage->id, 'severity' => 'low', 'issue_date' => now(),
            'reason' => 'DEW Late arrival', 'status' => 'active', 'issued_by' => $issuer->id,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($issuer);

        $controller = new WarningController();
        $request = Request::create("/warning/{$verbal->id}/escalate", 'POST', ['reason' => 'Repeated lateness']);
        $request->setUserResolver(fn () => $issuer);
        $response = $controller->escalate($request, $verbal->id)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $verbal->refresh();
        $this->assertSame('resolved', $verbal->status);

        $escalated = Warning::where('previous_case_id', $verbal->id)->first();
        $this->assertNotNull($escalated);
        $this->assertSame($writtenStage->id, $escalated->stage_type_id);
        $this->assertSame('written_warning', $escalated->case_type);
        $this->assertSame($employee->id, $escalated->employee_id);
        // Written Warning requires_response=true per the stock seed - the
        // new case should have a response deadline set automatically.
        $this->assertNotNull($escalated->response_due_at);
    }

    public function test_escalate_is_rejected_once_a_case_is_already_at_the_final_configured_stage(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);
        [$issuer, $employee] = $this->makeEmployeeUser();

        $terminationStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'termination')->first();

        $termination = Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'termination',
            'stage_type_id' => $terminationStage->id, 'severity' => 'high', 'issue_date' => now(),
            'reason' => 'DEW Gross misconduct', 'status' => 'active', 'issued_by' => $issuer->id,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($issuer);

        $controller = new WarningController();
        $request = Request::create("/warning/{$termination->id}/escalate", 'POST');
        $request->setUserResolver(fn () => $issuer);
        $response = $controller->escalate($request, $termination->id)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('active', $termination->fresh()->status);
        $this->assertSame(0, Warning::where('previous_case_id', $termination->id)->count());
    }

    public function test_escalation_respects_a_businesss_own_custom_stage_order_not_just_the_stock_five(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);
        [$issuer, $employee] = $this->makeEmployeeUser();

        // Insert a custom stage between Verbal and Written - escalation
        // from Verbal must land here, not skip straight to Written.
        $verbalStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'verbal_warning')->first();
        DisciplinaryStageType::where('business_id', 1)->where('sequence_order', '>', $verbalStage->sequence_order)
            ->increment('sequence_order');
        $coaching = DisciplinaryStageType::create([
            'business_id' => 1, 'name' => 'Coaching Session', 'slug' => 'coaching_session',
            'sequence_order' => $verbalStage->sequence_order + 1, 'is_terminal' => false, 'requires_response' => false,
        ]);

        $verbal = Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'stage_type_id' => $verbalStage->id, 'severity' => 'low', 'issue_date' => now(),
            'reason' => 'DEW Custom stage test', 'status' => 'active', 'issued_by' => $issuer->id,
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($issuer);

        $controller = new WarningController();
        $request = Request::create("/warning/{$verbal->id}/escalate", 'POST');
        $request->setUserResolver(fn () => $issuer);
        $controller->escalate($request, $verbal->id)->toResponse($request);

        $escalated = Warning::where('previous_case_id', $verbal->id)->first();
        $this->assertSame($coaching->id, $escalated->stage_type_id);
    }

    // ---- Show Cause response ------------------------------------------

    public function test_employee_can_submit_a_response_to_their_own_case_but_not_someone_elses(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);
        [$issuer,] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser();
        [$strangerUser,] = $this->makeEmployeeUser();

        $writtenStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'written_warning')->first();
        $warning = Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'written_warning',
            'stage_type_id' => $writtenStage->id, 'severity' => 'medium', 'issue_date' => now(),
            'reason' => 'DEW Show cause test', 'status' => 'active', 'issued_by' => $issuer->id,
            'response_due_at' => now()->addDays(7),
        ]);

        $controller = new WarningController();

        // A stranger cannot respond on someone else's behalf.
        $this->actingAs($strangerUser);
        $strangerRequest = Request::create("/disciplinary/{$warning->id}/respond", 'POST', ['employee_response' => 'Not mine to answer.']);
        $strangerRequest->setUserResolver(fn () => $strangerUser);
        $strangerResponse = $controller->submitResponse($strangerRequest, $warning->id)->toResponse($strangerRequest);
        $this->assertSame(400, $strangerResponse->getStatusCode());
        $this->assertNull($warning->fresh()->employee_response);

        // The actual employee can respond.
        $this->actingAs($employeeUser);
        $request = Request::create("/disciplinary/{$warning->id}/respond", 'POST', ['employee_response' => 'Here is my explanation.']);
        $request->setUserResolver(fn () => $employeeUser);
        $response = $controller->submitResponse($request, $warning->id)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $warning->refresh();
        $this->assertSame('Here is my explanation.', $warning->employee_response);
        $this->assertNotNull($warning->employee_responded_at);
    }

    // ---- store() with stage_type_id -----------------------------------

    public function test_issuing_a_case_with_a_stage_type_sets_response_due_at_when_the_stage_requires_it(): void
    {
        $business = Business::find(1);
        app(DisciplinaryStageTypeService::class)->ensureSeeded($business);
        [$issuer, $employee] = $this->makeEmployeeUser();

        $suspensionStage = DisciplinaryStageType::where('business_id', 1)->where('slug', 'suspension')->first();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($issuer);

        $controller = new WarningController();
        $request = Request::create('/warning/store', 'POST', [
            'employee_id' => $employee->id,
            'stage_type_id' => $suspensionStage->id,
            'issue_date' => now()->toDateString(),
            'reason' => 'DEW Suspension test',
        ]);
        $request->setUserResolver(fn () => $issuer);
        $response = $controller->store($request)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $warning = Warning::where('employee_id', $employee->id)->where('reason', 'DEW Suspension test')->first();
        $this->assertSame($suspensionStage->id, $warning->stage_type_id);
        $this->assertSame('suspension', $warning->case_type);
        $this->assertNotNull($warning->response_due_at);
    }

    // ---- Case detail page scoping --------------------------------------

    public function test_case_detail_page_throws_not_found_for_a_case_belonging_to_a_different_business(): void
    {
        [$issuer, $employee] = $this->makeEmployeeUser();

        $warning = Warning::create([
            'employee_id' => $employee->id, 'business_id' => 1, 'case_type' => 'verbal_warning',
            'severity' => 'low', 'issue_date' => now(), 'reason' => 'DEW Scope test',
            'status' => 'active', 'issued_by' => $issuer->id,
        ]);

        $otherBusinessId = Business::where('id', '!=', 1)->value('id');
        $otherBusiness = Business::find($otherBusinessId);

        $controller = new WarningController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $controller->show(Request::create('/x'), $otherBusiness, $warning);
    }
}
