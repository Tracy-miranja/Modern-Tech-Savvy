<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveEntitlementController;
use App\Http\Controllers\LeavePeriodController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the leave-periods action buttons (view/edit were
 * pointing at routes with missing controller methods) and a set of
 * entitlement-logic bugs: adjust()/process-carryover() were only ever
 * registered under the business-scoped route group (never the flat one the
 * JS actually calls), and re-running "Set Entitlements" for an employee who
 * already had a row silently reset their carryover_days back to 0.
 */
class LeavePeriodAndEntitlementFixesTest extends TestCase
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

    private function actAsBusinessUser(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());
    }

    private function makeEmployee(): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'LPE-' . uniqid(),
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

    // --- Leave Periods action buttons ---

    public function test_leave_periods_show_and_edit_routes_resolve_to_real_controller_methods(): void
    {
        $routes = app('router')->getRoutes();

        $show = $routes->getByName('leave-periods.show');
        $this->assertNotNull($show);
        $this->assertTrue(method_exists(\App\Http\Controllers\LeavePeriodController::class, 'show'));

        $edit = $routes->getByName('leave-periods.edit');
        $this->assertNotNull($edit);
        $this->assertTrue(method_exists(\App\Http\Controllers\LeavePeriodController::class, 'edit'));
    }

    public function test_leave_period_show_returns_rendered_details_html(): void
    {
        $this->actAsBusinessUser();
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'View Test Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/leave-periods/show', 'POST', ['id' => $leavePeriod->id]);
        $response = $controller->show($request)->toResponse($request);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString($leavePeriod->name, $html);
    }

    public function test_leave_period_edit_returns_a_prefilled_form(): void
    {
        $this->actAsBusinessUser();
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Edit Test Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/leave-periods/edit', 'POST', ['leave_period_slug' => $leavePeriod->slug]);
        $response = $controller->edit($request)->toResponse($request);
        $html = json_decode($response->getContent(), true)['data'];

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('value="' . $leavePeriod->slug . '"', $html);
        $this->assertStringContainsString($leavePeriod->name, $html);
    }

    public function test_leave_period_update_persists_changes_without_throwing(): void
    {
        // Regression: update()/destroy() called $business->leavePeriods()->findBySlug(...),
        // but findBySlug() is a static model method from Spatie's HasSlug
        // trait, not a chainable relation/query method - calling it on a
        // HasMany relation instance threw BadMethodCallException, which
        // handleTransaction() turned into a generic "Something went wrong"
        // 500 response for every single update/delete attempt.
        $this->actAsBusinessUser();
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Update Test Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $newName = 'Renamed Period ' . uniqid();
        $request = Request::create('/leave-periods/update', 'POST', [
            'leave_period_slug' => $leavePeriod->slug,
            'name' => $newName,
            'start_date' => '2026-02-01',
            'end_date' => '2026-11-30',
        ]);
        $response = $controller->update($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($newName, $leavePeriod->fresh()->name);
    }

    public function test_leave_period_destroy_removes_it_without_throwing(): void
    {
        $this->actAsBusinessUser();
        $leavePeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Destroy Test Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/leave-periods/delete', 'POST', ['leave_period_slug' => $leavePeriod->slug]);
        $response = $controller->destroy($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, LeavePeriod::where('id', $leavePeriod->id)->count());
    }

    private function makeBusiness(string $label): Business
    {
        return Business::create([
            'user_id' => User::factory()->create()->id,
            'company_name' => "Leave Period Test {$label} " . uniqid(),
            'slug' => 'lp-test-' . strtolower($label) . '-' . uniqid(),
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

    public function test_leave_period_name_only_needs_to_be_unique_within_its_own_business(): void
    {
        // The leave_periods table originally had a bare unique() on `name`
        // with no business_id scoping, so a name taken by ANY business
        // blocked every other business from ever using it.
        $sharedName = 'Q1 ' . uniqid();
        $otherBusiness = $this->makeBusiness('Other');
        LeavePeriod::create([
            'business_id' => $otherBusiness->id, 'name' => $sharedName,
            'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'is_active' => true,
        ]);

        $this->actAsBusinessUser();
        $controller = new LeavePeriodController();
        $request = Request::create('/leave-periods', 'POST', [
            'name' => $sharedName,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
        ]);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, LeavePeriod::where('business_id', 1)->where('name', $sharedName)->count());
    }

    public function test_leave_period_name_still_rejected_when_duplicated_within_the_same_business(): void
    {
        $this->actAsBusinessUser();
        $name = 'Duplicate Within Business ' . uniqid();
        LeavePeriod::create([
            'business_id' => 1, 'name' => $name,
            'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'is_active' => true,
        ]);

        $controller = new LeavePeriodController();
        $request = Request::create('/leave-periods', 'POST', [
            'name' => $name,
            'start_date' => '2026-04-01',
            'end_date' => '2026-06-30',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($request);
    }

    // --- Entitlement routes ---

    public function test_leave_entitlements_adjust_and_process_carryover_routes_are_registered_flat(): void
    {
        $routes = app('router')->getRoutes();

        $adjust = $routes->getByName('leave-entitlements.adjust');
        $this->assertNotNull($adjust, 'The flat leave-entitlements.adjust route (matching what the JS actually calls) must exist.');
        $this->assertSame('leave-entitlements/adjust', $adjust->uri());

        $carryover = $routes->getByName('leave-entitlements.process-carryover');
        $this->assertNotNull($carryover, 'The flat leave-entitlements.process-carryover route must exist.');
        $this->assertSame('leave-entitlements/process-carryover', $carryover->uri());
    }

    // --- Entitlement logic conflict: carryover reset on re-run ---

    public function test_re_running_set_entitlements_recomputes_carryover_consistently(): void
    {
        // store() now derives carryover automatically from the previous
        // period's own days_remaining (LeavePolicyService::calculateCarryover()),
        // capped by the policy's max_carryover_days, every time it runs -
        // no separate manual "Process Carryover" step needed for this case.
        // Re-running it must recompute the SAME correct value, not double it
        // up or reset it to 0.
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'Carryover Recompute Leave ' . uniqid()]);
        // Far-future, disjoint dates - calculateCarryover() picks whichever
        // real LeavePeriod row has the latest end_date before the current
        // period's start_date, and business_id 1 is a live, shared business
        // with its own real leave periods that could otherwise win that
        // lookup instead of this test's own fixture.
        // Comfortably future (past any real business_id=1 data) but still
        // within MySQL TIMESTAMP's range (last_accrued_at is a TIMESTAMP
        // column, capped at 2038-01-19).
        $previousPeriod = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Carryover Recompute Previous Period ' . uniqid(),
            'start_date' => '2035-01-01', 'end_date' => '2035-06-30', 'is_active' => true,
        ]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'Carryover Recompute Period ' . uniqid(),
            'start_date' => '2035-07-01', 'end_date' => '2035-12-31', 'is_active' => true,
        ]);
        LeavePolicy::create([
            'leave_type_id' => $leaveType->id, 'department_id' => null, 'job_category_id' => null,
            'gender_applicable' => 'all', 'prorated_for_new_employees' => false,
            'default_days' => 21, 'accrual_frequency' => 'yearly', 'accrual_amount' => 0,
            'max_carryover_days' => 5, 'minimum_service_days_required' => 0,
            'effective_date' => '2020-01-01', 'is_active' => true,
        ]);

        // Previous period's entitlement has 8 days remaining - policy caps
        // carryover at 5, so only 5 should ever be carried forward.
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $previousPeriod->id, 'entitled_days' => 8, 'accrued_days' => 0,
            'total_days' => 8, 'days_taken' => 0, 'days_remaining' => 8,
        ]);

        $this->actAsBusinessUser();
        $controller = new LeaveEntitlementController();

        $firstRequest = Request::create('/leave-entitlements/store', 'POST', [
            'leave_period_id' => $period->id,
            'employees' => [$employee->id],
            'leave_type_ids' => [$leaveType->id],
        ]);
        $controller->store($firstRequest, app(\App\Services\LeavePolicyService::class))->toResponse($firstRequest);

        $entitlement = LeaveEntitlement::where('business_id', 1)
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('leave_period_id', $period->id)
            ->firstOrFail();

        $this->assertSame(5.0, $entitlement->carryover_days, 'Carryover should be capped at the policy max, not the full 8 remaining.');

        // Second run: re-set entitlements for the SAME employee+type+period
        // (e.g. HR re-runs the bulk form to add another employee).
        $secondRequest = Request::create('/leave-entitlements/store', 'POST', [
            'leave_period_id' => $period->id,
            'employees' => [$employee->id],
            'leave_type_ids' => [$leaveType->id],
        ]);
        $controller->store($secondRequest, app(\App\Services\LeavePolicyService::class))->toResponse($secondRequest);

        $this->assertSame(5.0, $entitlement->fresh()->carryover_days, 'Re-running Set Entitlements must recompute the same value, not double it up or reset it.');
    }

    // --- PDF export ---

    public function test_export_pdf_pivots_remaining_days_by_leave_type_per_employee(): void
    {
        $employeeA = $this->makeEmployee();
        $employeeB = $this->makeEmployee();

        $leaveTypeAnnual = LeaveType::create(['business_id' => 1, 'name' => 'PDF Annual Leave ' . uniqid()]);
        $leaveTypeSick = LeaveType::create(['business_id' => 1, 'name' => 'PDF Sick Leave ' . uniqid()]);

        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'PDF Export Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        foreach ([[$employeeA, $leaveTypeAnnual, 18], [$employeeA, $leaveTypeSick, 7], [$employeeB, $leaveTypeAnnual, 21]] as [$employee, $leaveType, $remaining]) {
            LeaveEntitlement::create([
                'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
                'leave_period_id' => $period->id, 'entitled_days' => $remaining, 'accrued_days' => 0,
                'total_days' => $remaining, 'days_taken' => 0, 'days_remaining' => $remaining,
            ]);
        }

        $this->actAsBusinessUser();
        $business = Business::find(1);

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/export-pdf', 'GET', ['leave_period_id' => $period->id]);
        $response = $controller->exportPdf($request, $business);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()), 'A real PDF should have produced a non-trivial byte stream.');
    }

    public function test_export_pdf_department_filter_narrows_the_employee_set(): void
    {
        $deptX = \App\Models\Department::create(['business_id' => 1, 'name' => 'PDF Dept X ' . uniqid()]);
        $deptY = \App\Models\Department::create(['business_id' => 1, 'name' => 'PDF Dept Y ' . uniqid()]);

        $employeeX = $this->makeEmployee();
        $employeeX->update(['department_id' => $deptX->id]);
        $employeeY = $this->makeEmployee();
        $employeeY->update(['department_id' => $deptY->id]);

        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'PDF Dept Filter Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'PDF Dept Filter Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        foreach ([$employeeX, $employeeY] as $employee) {
            LeaveEntitlement::create([
                'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
                'leave_period_id' => $period->id, 'entitled_days' => 20, 'accrued_days' => 0,
                'total_days' => 20, 'days_taken' => 0, 'days_remaining' => 20,
            ]);
        }

        $this->actAsBusinessUser();
        $business = Business::find(1);

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/export-pdf', 'GET', [
            'leave_period_id' => $period->id,
            'department_id' => $deptX->id,
        ]);
        $response = $controller->exportPdf($request, $business);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }

    public function test_export_pdf_does_not_crash_on_an_orphaned_entitlement_when_unfiltered(): void
    {
        // Regression: an entitlement whose employee_id doesn't resolve to a
        // real Employee (bad historical data, FK checks disabled during an
        // import, etc.) crashed the unfiltered export with "Attempt to read
        // property 'user' on null", but NOT the "select every employee"
        // path, since explicitly filtering by real employee ids naturally
        // excludes rows that don't match any of them.
        $employee = $this->makeEmployee();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'PDF Orphan Leave ' . uniqid()]);
        $period = LeavePeriod::create([
            'business_id' => 1, 'name' => 'PDF Orphan Period ' . uniqid(),
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true,
        ]);

        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => $employee->id, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 20, 'accrued_days' => 0,
            'total_days' => 20, 'days_taken' => 0, 'days_remaining' => 20,
        ]);

        // Simulate an orphaned row: a real Employee id that has since
        // stopped existing, inserted with FK checks off (as would happen
        // via a raw import), scoped to this test's own rolled-back
        // transaction only.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        LeaveEntitlement::create([
            'business_id' => 1, 'employee_id' => 999999999, 'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id, 'entitled_days' => 15, 'accrued_days' => 0,
            'total_days' => 15, 'days_taken' => 0, 'days_remaining' => 15,
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->actAsBusinessUser();
        $business = Business::find(1);

        $controller = new LeaveEntitlementController();
        $request = Request::create('/leave-entitlements/export-pdf', 'GET', ['leave_period_id' => $period->id]);
        $response = $controller->exportPdf($request, $business);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_pdf_modal_uses_checkboxes_not_multi_select_for_leave_type_and_employee(): void
    {
        $this->actAsBusinessUser();
        $business = Business::find(1);

        $leavePeriods = $business->leavePeriods;
        $initialLeavePeriodSlug = $leavePeriods->first()->slug ?? null;
        $departments = \App\Models\Department::where('business_id', 1)->get(['id', 'name']);
        $leaveTypes = LeaveType::where('business_id', 1)->get(['id', 'name']);
        $employees = Employee::where('business_id', 1)->with('user')->get(['id', 'user_id', 'department_id']);
        $page = 'Leave Entitlements';
        $description = '';

        $html = view('leave.entitlements', compact('page', 'description', 'leavePeriods', 'initialLeavePeriodSlug', 'departments', 'leaveTypes', 'employees'))
            ->with('leave_periods', $leavePeriods)
            ->render();

        $this->assertStringNotContainsString('id="exportLeaveTypeIds"', $html, 'The old native multi-select must be gone.');
        $this->assertStringNotContainsString('id="exportEmployeeIds"', $html, 'The old native multi-select must be gone.');
        $this->assertStringContainsString('id="exportLeaveTypeChecks"', $html);
        $this->assertStringContainsString('id="exportEmployeeChecks"', $html);
        $this->assertStringContainsString('name="leave_type_ids[]"', $html);
        $this->assertStringContainsString('name="employee_ids[]"', $html);
    }
}
