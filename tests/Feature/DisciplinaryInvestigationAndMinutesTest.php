<?php

namespace Tests\Feature;

use App\Http\Controllers\DisciplinaryInvestigationController;
use App\Http\Controllers\DisciplinaryMinutesController;
use App\Models\Business;
use App\Models\DisciplinaryInvestigation;
use App\Models\DisciplinaryMinutes;
use App\Models\Employee;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Investigation and Minutes CRUD (GUIDE plan Phase 3) - both scoped to the
 * active business's own case, never someone else's.
 */
class DisciplinaryInvestigationAndMinutesTest extends TestCase
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
            'employee_code' => 'DIM-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        return [$user->fresh(), $employee->fresh()];
    }

    private function makeWarning(int $businessId = 1): Warning
    {
        [$issuer, $employee] = $this->makeEmployeeUser();

        return Warning::create([
            'employee_id' => $employee->id, 'business_id' => $businessId, 'case_type' => 'written_warning',
            'severity' => 'medium', 'issue_date' => now(), 'reason' => 'DIM Test case',
            'status' => 'active', 'issued_by' => $issuer->id,
        ]);
    }

    public function test_investigation_can_be_recorded_and_updated_for_a_case(): void
    {
        $business = Business::find(1);
        $warning = $this->makeWarning();
        [, $investigator] = $this->makeEmployeeUser();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new DisciplinaryInvestigationController();
        $request = Request::create("/warning/{$warning->id}/investigations/store", 'POST', [
            'investigator_id' => $investigator->id,
            'started_at' => now()->toDateString(),
            'findings' => 'Initial findings',
        ]);
        $response = $controller->store($request, $warning->id)->toResponse($request);
        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $investigation = DisciplinaryInvestigation::where('warning_id', $warning->id)->first();
        $this->assertNotNull($investigation);
        $this->assertSame($investigator->id, $investigation->investigator_id);

        $updateRequest = Request::create("/warning/{$warning->id}/investigations/{$investigation->id}/update", 'POST', [
            'outcome' => 'Substantiated',
            'findings' => 'Updated findings',
        ]);
        $updateResponse = $controller->update($updateRequest, $warning->id, $investigation)->toResponse($updateRequest);
        $this->assertSame(200, $updateResponse->getStatusCode());
        $this->assertSame('Substantiated', $investigation->fresh()->outcome);
    }

    public function test_investigation_cannot_be_recorded_against_a_case_from_a_different_business(): void
    {
        $otherBusinessId = Business::where('id', '!=', 1)->value('id');
        $foreignWarning = $this->makeWarning($otherBusinessId);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new DisciplinaryInvestigationController();
        $request = Request::create("/warning/{$foreignWarning->id}/investigations/store", 'POST', ['findings' => 'Should not save']);
        $response = $controller->store($request, $foreignWarning->id)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, DisciplinaryInvestigation::where('warning_id', $foreignWarning->id)->count());
    }

    public function test_a_case_can_have_multiple_minutes_records(): void
    {
        $business = Business::find(1);
        $warning = $this->makeWarning();

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new DisciplinaryMinutesController();

        $first = Request::create("/warning/{$warning->id}/minutes/store", 'POST', [
            'meeting_date' => now()->toDateString(), 'notes' => 'Initial hearing',
        ]);
        $controller->store($first, $warning->id)->toResponse($first);

        $second = Request::create("/warning/{$warning->id}/minutes/store", 'POST', [
            'meeting_date' => now()->addDays(3)->toDateString(), 'notes' => 'Follow-up hearing',
        ]);
        $response = $controller->store($second, $warning->id)->toResponse($second);
        $this->assertSame(201, $response->getStatusCode());

        $this->assertSame(2, DisciplinaryMinutes::where('warning_id', $warning->id)->count());
    }

    public function test_minutes_cannot_be_deleted_from_a_different_business_case(): void
    {
        $otherBusinessId = Business::where('id', '!=', 1)->value('id');
        $foreignWarning = $this->makeWarning($otherBusinessId);
        $minutes = DisciplinaryMinutes::create([
            'warning_id' => $foreignWarning->id, 'business_id' => $otherBusinessId,
            'meeting_date' => now(), 'notes' => 'Foreign minutes',
        ]);

        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new DisciplinaryMinutesController();
        $request = Request::create("/warning/{$foreignWarning->id}/minutes/{$minutes->id}/destroy", 'POST');
        $response = $controller->destroy($request, $foreignWarning->id, $minutes)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNotNull(DisciplinaryMinutes::find($minutes->id));
    }
}
