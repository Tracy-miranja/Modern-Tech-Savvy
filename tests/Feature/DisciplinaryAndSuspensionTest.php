<?php

namespace Tests\Feature;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\WarningController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for:
 *  - the Warning model's evolution into a full disciplinary case (stages,
 *    escalation chain, acknowledgement)
 *  - the previously-dead "Suspend" employee action, now wired through
 *    EmployeeContractAction with a working reinstate/reversal flow
 */
class DisciplinaryAndSuspensionTest extends TestCase
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
            'employee_code' => 'DISC-' . uniqid(),
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

    public function test_disciplinary_case_escalation_chain_and_level(): void
    {
        [$issuer,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();

        $verbal = Warning::create([
            'employee_id' => $employee->id,
            'business_id' => 1,
            'case_type' => 'verbal_warning',
            'severity' => 'low',
            'issue_date' => now(),
            'reason' => 'Late arrival',
            'status' => 'active',
            'issued_by' => $issuer->id,
        ]);

        $written = Warning::create([
            'employee_id' => $employee->id,
            'business_id' => 1,
            'case_type' => 'written_warning',
            'severity' => 'medium',
            'previous_case_id' => $verbal->id,
            'issue_date' => now(),
            'reason' => 'Repeated late arrival',
            'status' => 'active',
            'issued_by' => $issuer->id,
        ]);

        $this->assertSame(1, $verbal->escalation_level);
        $this->assertSame(2, $written->escalation_level);
        $this->assertSame('written_warning', $verbal->suggestedNextStage());
        $this->assertSame('final_warning', $written->suggestedNextStage());
        $this->assertTrue($written->previousCase->is($verbal));
        $this->assertTrue($verbal->nextCases->contains($written));
    }

    public function test_termination_is_the_final_stage_with_no_further_escalation(): void
    {
        [$issuer,] = $this->makeEmployeeUser();
        [, $employee] = $this->makeEmployeeUser();

        $termination = Warning::create([
            'employee_id' => $employee->id,
            'business_id' => 1,
            'case_type' => 'termination',
            'severity' => 'high',
            'issue_date' => now(),
            'reason' => 'Gross misconduct',
            'status' => 'active',
            'issued_by' => $issuer->id,
        ]);

        $this->assertNull($termination->suggestedNextStage());
    }

    public function test_suspend_action_updates_status_creates_record_and_notifies(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new EmployeeController();
        $request = Request::create('/contracts/store', 'POST', [
            'employee_id' => $employee->id,
            'action_type' => 'suspension',
            'reason' => 'Policy violation',
            'description' => 'Pending investigation.',
            'action_date' => now()->toDateString(),
        ]);
        $request->setUserResolver(fn () => $hrUser);

        $response = $controller->storeContractAction($request)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $employee->refresh();
        $this->assertSame('suspended', $employee->employmentDetails->status);
        $this->assertTrue((bool) $employee->is_exempt_from_payroll);

        $action = \App\Models\EmployeeContractAction::where('employee_id', $employee->id)
            ->where('action_type', 'suspension')
            ->first();
        $this->assertNotNull($action);
        $this->assertSame('active', $action->status);

        $this->assertSame(1, $employeeUser->fresh()->unreadNotifications->count());
    }

    public function test_reversing_a_suspension_reinstates_the_employee_and_notifies_them(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $action = \App\Models\EmployeeContractAction::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'action_type' => 'suspension',
            'reason' => 'Policy violation',
            'description' => null,
            'action_date' => now()->toDateString(),
            'status' => 'active',
            'issued_by_id' => $hrUser->id,
        ]);
        $employee->employmentDetails()->update(['status' => 'suspended']);
        $employee->update(['is_exempt_from_payroll' => true]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new EmployeeController();
        $request = Request::create("/contracts/{$action->id}/update", 'POST', [
            'employee_id' => $employee->id,
            'reason' => 'Policy violation',
            'description' => 'Investigation concluded, no further action.',
            'action_date' => now()->toDateString(),
            'status' => 'reversed',
        ]);
        $request->setUserResolver(fn () => $hrUser);

        $response = $controller->updateContractAction($request, $action->id)->toResponse($request);
        $this->assertSame(200, $response->getStatusCode());

        $employee->refresh();
        $this->assertSame('active', $employee->employmentDetails->status);
        $this->assertFalse((bool) $employee->is_exempt_from_payroll);

        $this->assertSame(1, $employeeUser->fresh()->unreadNotifications->count());
        $notification = $employeeUser->fresh()->unreadNotifications->first();
        $this->assertStringContainsString('reinstated', strtolower($notification->data['message']));
    }

    public function test_a_third_disciplinary_case_is_no_longer_blocked_by_the_old_hardcoded_cap(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs($hrUser);

        $controller = new WarningController();

        for ($i = 1; $i <= 3; $i++) {
            $request = Request::create('/warning/store', 'POST', [
                'employee_id' => $employee->id,
                'case_type' => 'written_warning',
                'issue_date' => now()->toDateString(),
                'reason' => "Incident #{$i}",
            ]);
            $request->setUserResolver(fn () => $hrUser);

            $response = $controller->store($request)->toResponse($request);
            $this->assertSame(201, $response->getStatusCode(), "Case #{$i} should not be blocked: " . $response->getContent());
        }

        $this->assertSame(3, Warning::where('employee_id', $employee->id)->count());
    }

    public function test_employee_can_acknowledge_their_own_disciplinary_case_but_not_someone_elses(): void
    {
        [$hrUser,] = $this->makeEmployeeUser();
        [$employeeUser, $employee] = $this->makeEmployeeUser();
        [$strangerUser,] = $this->makeEmployeeUser();
        $business = Business::find(1);

        $warning = Warning::create([
            'employee_id' => $employee->id,
            'business_id' => 1,
            'case_type' => 'written_warning',
            'severity' => 'medium',
            'issue_date' => now(),
            'reason' => 'Policy breach',
            'status' => 'active',
            'issued_by' => $hrUser->id,
        ]);

        $controller = new WarningController();

        // A stranger cannot acknowledge someone else's case.
        $this->actingAs($strangerUser);
        $request = Request::create("/disciplinary/{$warning->id}/acknowledge", 'POST');
        $request->setUserResolver(fn () => $strangerUser);
        $response = $controller->acknowledge($request, $warning->id)->toResponse($request);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertNull($warning->fresh()->acknowledged_at);

        // The actual employee can acknowledge it.
        $this->actingAs($employeeUser);
        $request2 = Request::create("/disciplinary/{$warning->id}/acknowledge", 'POST');
        $request2->setUserResolver(fn () => $employeeUser);
        $response2 = $controller->acknowledge($request2, $warning->id)->toResponse($request2);
        $this->assertSame(200, $response2->getStatusCode());

        $warning->refresh();
        $this->assertNotNull($warning->acknowledged_at);
        $this->assertSame($employee->id, $warning->acknowledged_by);
    }
}
