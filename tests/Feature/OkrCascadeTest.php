<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\PerformanceCycle;
use App\Models\PerformanceObjective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for OKR Phase 01: the 3-tier cascade (company ->
 * department -> individual), bottom-up alignment proposals with a single
 * approval step, and the "every objective always has a weight" rule.
 */
class OkrCascadeTest extends TestCase
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

    private function makeEmployee(?int $managerId = null): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'manager_id' => $managerId,
            'employee_code' => 'OKR-' . uniqid(),
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

        return [$user->fresh(), $employee->fresh()];
    }

    private function makeCycle(): PerformanceCycle
    {
        return PerformanceCycle::create([
            'business_id' => 1,
            'name' => 'OKR Cycle ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 40,
            'okr_weight' => 40,
            'competency_weight' => 20,
            'status' => 'active',
        ]);
    }

    public function test_objective_creation_requires_an_explicit_weight(): void
    {
        [$hrUser,] = $this->makeEmployee();
        [, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'No weight given',
            // weight intentionally omitted
        ]);
        $request->setUserResolver(fn () => $hrUser);

        // Calling the controller directly (bypassing the HTTP kernel) means
        // Laravel's exception handler never converts this into a redirect/422
        // response - the validator throws directly, which is itself proof
        // the field is enforced as required.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessageMatches('/weight/i');

        try {
            $controller->storeObjective($request, $business, $employee);
        } finally {
            $this->assertSame(0, PerformanceObjective::where('title', 'No weight given')->count());
        }
    }

    public function test_only_hr_can_create_company_or_department_objectives(): void
    {
        [$employeeUser, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($employeeUser);

        $controller = new PerformanceController();
        $request = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'scope' => 'company',
            'title' => 'Sneaky company pillar',
            'weight' => 100,
        ]);
        $request->setUserResolver(fn () => $employeeUser);

        $response = $controller->storeObjective($request, $business, $employee)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, PerformanceObjective::where('title', 'Sneaky company pillar')->count());
    }

    public function test_individual_objective_can_align_to_department_but_not_to_another_individual(): void
    {
        [$hrUser,] = $this->makeEmployee();
        [, $deptOwner] = $this->makeEmployee();
        [, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);

        $controller = new PerformanceController();

        // HR creates a departmental objective.
        $deptRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'scope' => 'department',
            'department_id' => 1,
            'title' => 'Grow department revenue',
            'weight' => 100,
        ]);
        $deptRequest->setUserResolver(fn () => $hrUser);
        $deptResponse = $controller->storeObjective($deptRequest, $business, $deptOwner)->toResponse($deptRequest);
        $this->assertSame(201, $deptResponse->getStatusCode());
        $deptObjective = PerformanceObjective::where('title', 'Grow department revenue')->first();
        $this->assertSame('approved', $deptObjective->alignment_status);

        // Individual objective aligning to the department objective: OK.
        $indivRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'parent_objective_id' => $deptObjective->id,
            'title' => 'Close 10 new deals',
            'weight' => 100,
        ]);
        $indivRequest->setUserResolver(fn () => $hrUser);
        $indivResponse = $controller->storeObjective($indivRequest, $business, $employee)->toResponse($indivRequest);
        $this->assertSame(201, $indivResponse->getStatusCode());

        // A second individual objective cannot align to another individual objective.
        $firstIndividual = PerformanceObjective::where('title', 'Close 10 new deals')->first();
        $badRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'parent_objective_id' => $firstIndividual->id,
            'title' => 'Sideways alignment',
            'weight' => 100,
        ]);
        $badRequest->setUserResolver(fn () => $hrUser);
        $badResponse = $controller->storeObjective($badRequest, $business, $employee)->toResponse($badRequest);
        $this->assertSame(400, $badResponse->getStatusCode());
    }

    public function test_self_service_alignment_proposal_needs_owner_approval(): void
    {
        [, $deptOwnerEmployee] = $this->makeEmployee();
        [$hrUser,] = $this->makeEmployee();
        [$employeeUser, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        // HR sets up the department objective, owned by deptOwnerEmployee.
        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);
        $controller = new PerformanceController();
        $deptRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'scope' => 'department',
            'department_id' => 1,
            'title' => 'Ship the platform migration',
            'weight' => 100,
        ]);
        $deptRequest->setUserResolver(fn () => $hrUser);
        $controller->storeObjective($deptRequest, $business, $deptOwnerEmployee)->toResponse($deptRequest);
        $deptObjective = PerformanceObjective::where('title', 'Ship the platform migration')->first();

        // The employee proposes their own individual objective aligned to it.
        session(['active_role' => 'business-employee']);
        $this->actingAs($employeeUser);
        $proposeRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'parent_objective_id' => $deptObjective->id,
            'title' => 'Migrate my team\'s services',
            'weight' => 100,
        ]);
        $proposeRequest->setUserResolver(fn () => $employeeUser);
        $proposeResponse = $controller->storeObjective($proposeRequest, $business, $employee)->toResponse($proposeRequest);
        $this->assertSame(201, $proposeResponse->getStatusCode());

        $proposedObjective = PerformanceObjective::where('title', "Migrate my team's services")->first();
        $this->assertSame('proposed', $proposedObjective->alignment_status);

        // A stranger cannot approve it.
        [$strangerUser,] = $this->makeEmployee();
        session(['active_role' => 'business-employee']);
        $this->actingAs($strangerUser);
        $strangerRequest = Request::create("/objectives/{$proposedObjective->id}/approve-alignment", 'POST');
        $strangerRequest->setUserResolver(fn () => $strangerUser);
        $strangerResponse = $controller->approveAlignment($strangerRequest, $business, $proposedObjective)->toResponse($strangerRequest);
        $this->assertSame(400, $strangerResponse->getStatusCode());
        $this->assertSame('proposed', $proposedObjective->fresh()->alignment_status);

        // The department objective's owner can approve it.
        $deptOwnerUser = $deptOwnerEmployee->user;
        session(['active_role' => 'business-employee']);
        $this->actingAs($deptOwnerUser);
        $approveRequest = Request::create("/objectives/{$proposedObjective->id}/approve-alignment", 'POST');
        $approveRequest->setUserResolver(fn () => $deptOwnerUser);
        $approveResponse = $controller->approveAlignment($approveRequest, $business, $proposedObjective)->toResponse($approveRequest);
        $this->assertSame(200, $approveResponse->getStatusCode());
        $this->assertSame('approved', $proposedObjective->fresh()->alignment_status);
    }

    public function test_declining_an_alignment_sends_it_back_to_draft_and_clears_the_parent(): void
    {
        [, $deptOwnerEmployee] = $this->makeEmployee();
        [$hrUser,] = $this->makeEmployee();
        [$employeeUser, $employee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);
        $controller = new PerformanceController();
        $deptRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'scope' => 'department',
            'department_id' => 1,
            'title' => 'Cut support ticket backlog',
            'weight' => 100,
        ]);
        $deptRequest->setUserResolver(fn () => $hrUser);
        $controller->storeObjective($deptRequest, $business, $deptOwnerEmployee)->toResponse($deptRequest);
        $deptObjective = PerformanceObjective::where('title', 'Cut support ticket backlog')->first();

        session(['active_role' => 'business-employee']);
        $this->actingAs($employeeUser);
        $proposeRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'parent_objective_id' => $deptObjective->id,
            'title' => 'Reduce my queue by half',
            'weight' => 100,
        ]);
        $proposeRequest->setUserResolver(fn () => $employeeUser);
        $controller->storeObjective($proposeRequest, $business, $employee)->toResponse($proposeRequest);
        $proposedObjective = PerformanceObjective::where('title', 'Reduce my queue by half')->first();

        $deptOwnerUser = $deptOwnerEmployee->user;
        session(['active_role' => 'business-employee']);
        $this->actingAs($deptOwnerUser);
        $declineRequest = Request::create("/objectives/{$proposedObjective->id}/decline-alignment", 'POST');
        $declineRequest->setUserResolver(fn () => $deptOwnerUser);
        $declineResponse = $controller->declineAlignment($declineRequest, $business, $proposedObjective)->toResponse($declineRequest);
        $this->assertSame(200, $declineResponse->getStatusCode());

        $proposedObjective->refresh();
        $this->assertSame('draft', $proposedObjective->alignment_status);
        $this->assertNull($proposedObjective->parent_objective_id);
    }

    public function test_cascade_fetch_returns_only_approved_company_and_department_objectives(): void
    {
        [$hrUser,] = $this->makeEmployee();
        [, $deptOwnerEmployee] = $this->makeEmployee();
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-hr']);
        $this->actingAs($hrUser);
        $controller = new PerformanceController();

        $companyRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'scope' => 'company',
            'title' => 'Become the market leader',
            'weight' => 100,
        ]);
        $companyRequest->setUserResolver(fn () => $hrUser);
        $controller->storeObjective($companyRequest, $business, $deptOwnerEmployee)->toResponse($companyRequest);

        [, $regularEmployee] = $this->makeEmployee();
        session(['active_role' => 'business-employee']);
        $this->actingAs($regularEmployee->user);
        $indivRequest = Request::create('/objectives', 'POST', [
            'performance_cycle_id' => $cycle->id,
            'title' => 'My own personal goal',
            'weight' => 100,
        ]);
        $indivRequest->setUserResolver(fn () => $regularEmployee->user);
        $controller->storeObjective($indivRequest, $business, $regularEmployee)->toResponse($indivRequest);

        $fetchRequest = Request::create('/objectives/cascade', 'GET', ['performance_cycle_id' => $cycle->id]);
        $response = $controller->fetchCascadeObjectives($fetchRequest, $business)->toResponse($fetchRequest);
        $payload = json_decode($response->getContent(), true);

        $titles = collect($payload['data'])->pluck('title');
        $this->assertTrue($titles->contains('Become the market leader'));
        $this->assertFalse($titles->contains('My own personal goal'), 'Individual-scope objectives must not appear in the cascade picker.');
    }
}
