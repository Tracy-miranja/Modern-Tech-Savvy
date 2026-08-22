<?php

namespace Tests\Feature;

use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\MandatoryLeavePeriodController;
use App\Models\Business;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Holiday;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\MandatoryLeavePeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for company-mandated leave days: HR declares a date
 * range + leave type + scope (whole org / departments / locations), and
 * every affected employee's balance for that leave type is deducted
 * automatically, scoped correctly, and fully reversible on edit/delete.
 */
class MandatoryLeavePeriodTest extends TestCase
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

        // Pin business_id 1's non_working_days for the duration of this test
        // (rolled back after) so these hardcoded Nov-2026 weekday-count
        // assertions aren't at the mercy of whatever real config happens to
        // be set on the live business right now.
        Business::find(1)->update(['non_working_days' => []]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeDepartment(): Department
    {
        return Department::create(['business_id' => 1, 'name' => 'MLP Dept ' . uniqid()]);
    }

    private function makeLocation(?string $country = null): Location
    {
        return Location::create(['business_id' => 1, 'name' => 'MLP Location ' . uniqid(), 'country' => $country]);
    }

    private function makeEmployee(?int $departmentId = null, ?int $locationId = null): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId ?? 1,
            'location_id' => $locationId,
            'employee_code' => 'MLP-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId ?? 1,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    private function makeEntitlement(Employee $employee, LeaveType $leaveType, string $periodStart = '2026-01-01', string $periodEnd = '2026-12-31'): LeaveEntitlement
    {
        $period = LeavePeriod::create([
            'business_id' => 1,
            'name' => 'MLP Period ' . uniqid(),
            'start_date' => $periodStart,
            'end_date' => $periodEnd,
            'is_active' => true,
        ]);

        return LeaveEntitlement::create([
            'business_id' => 1,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'leave_period_id' => $period->id,
            'entitled_days' => 21,
            'accrued_days' => 0,
            'total_days' => 21,
            'days_taken' => 0,
            'days_remaining' => 21,
        ]);
    }

    private function actAsBusinessUser(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());
    }

    public function test_organization_scope_deducts_from_every_affected_employee(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Org Leave ' . uniqid(), 'exclude_public_holidays' => true, 'exclude_non_working_days' => true]);

        $employeeA = $this->makeEmployee();
        $employeeB = $this->makeEmployee();
        $entitlementA = $this->makeEntitlement($employeeA, $leaveType);
        $entitlementB = $this->makeEntitlement($employeeB, $leaveType);

        $this->actAsBusinessUser();

        $controller = new MandatoryLeavePeriodController();
        $request = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Org Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            // Mon 2026-11-02 .. Fri 2026-11-06 = 5 weekdays, no holidays configured.
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'scope_type' => 'organization',
        ]);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(201, $response->getStatusCode());

        $entitlementA->refresh();
        $entitlementB->refresh();
        $this->assertSame(-5.0, $entitlementA->adjustment_days);
        $this->assertSame(-5.0, $entitlementB->adjustment_days);
        $this->assertSame(16.0, $entitlementA->total_days);
        $this->assertSame(16.0, $entitlementB->total_days);
    }

    public function test_department_scope_only_deducts_the_chosen_department(): void
    {
        $deptX = $this->makeDepartment();
        $deptY = $this->makeDepartment();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Dept Leave ' . uniqid()]);

        $employeeX = $this->makeEmployee($deptX->id);
        $employeeY = $this->makeEmployee($deptY->id);
        $entitlementX = $this->makeEntitlement($employeeX, $leaveType);
        $entitlementY = $this->makeEntitlement($employeeY, $leaveType);

        $this->actAsBusinessUser();

        $controller = new MandatoryLeavePeriodController();
        $request = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Dept Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'scope_type' => 'department',
            'scope_ids' => [$deptX->id],
        ]);
        $controller->store($request)->toResponse($request);

        $entitlementX->refresh();
        $entitlementY->refresh();
        $this->assertSame(-2.0, $entitlementX->adjustment_days, 'The chosen department should be deducted.');
        $this->assertSame(0.0, $entitlementY->adjustment_days, 'A different department must not be touched.');
    }

    public function test_location_scope_deducts_only_that_locations_employees_and_respects_its_own_holidays(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Location Leave ' . uniqid(), 'exclude_public_holidays' => true]);

        $kenyaEmployee = $this->makeEmployee(null, $kenyaLocation->id);
        $ugandaEmployee = $this->makeEmployee(null, $ugandaLocation->id);
        $kenyaEntitlement = $this->makeEntitlement($kenyaEmployee, $leaveType);
        $ugandaEntitlement = $this->makeEntitlement($ugandaEmployee, $leaveType);

        // A Kenya-only holiday inside the mandated range - only the Kenya
        // employee's deduction should be reduced by it.
        $kenyaHoliday = Holiday::create([
            'business_id' => 1,
            'location_id' => $kenyaLocation->id,
            'name' => 'MLP Kenya Holiday ' . uniqid(),
            'date' => '2026-11-04', // Wednesday, inside the range below
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $this->actAsBusinessUser();

        $controller = new MandatoryLeavePeriodController();
        $request = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Location Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            // Mon 2026-11-02 .. Fri 2026-11-06 = 5 weekdays.
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'scope_type' => 'location',
            'scope_ids' => [$kenyaLocation->id, $ugandaLocation->id],
        ]);
        $controller->store($request)->toResponse($request);

        $kenyaEntitlement->refresh();
        $ugandaEntitlement->refresh();
        $this->assertSame(-4.0, $kenyaEntitlement->adjustment_days, 'Kenya employee loses 5 weekdays minus their own Kenya-only holiday.');
        $this->assertSame(-5.0, $ugandaEntitlement->adjustment_days, 'Uganda employee is unaffected by the Kenya-only holiday.');

        $kenyaHoliday->delete();
    }

    public function test_employee_without_a_matching_entitlement_is_skipped_and_reported(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Skip Leave ' . uniqid()]);

        $employeeWithEntitlement = $this->makeEmployee();
        $employeeWithoutEntitlement = $this->makeEmployee();
        $entitlement = $this->makeEntitlement($employeeWithEntitlement, $leaveType);
        // Deliberately no entitlement created for $employeeWithoutEntitlement.

        $this->actAsBusinessUser();

        $controller = new MandatoryLeavePeriodController();
        $request = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Skip Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-02',
            'scope_type' => 'organization',
        ]);
        $response = $controller->store($request)->toResponse($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue(in_array($employeeWithoutEntitlement->id, $payload['data']['skipped_no_entitlement'], true));
        $this->assertFalse(in_array($employeeWithEntitlement->id, $payload['data']['skipped_no_entitlement'], true));

        $entitlement->refresh();
        $this->assertSame(-1.0, $entitlement->adjustment_days);
    }

    public function test_updating_a_period_reverses_the_old_deduction_before_reapplying(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Update Leave ' . uniqid()]);
        $employee = $this->makeEmployee();
        $entitlement = $this->makeEntitlement($employee, $leaveType);

        $this->actAsBusinessUser();
        $controller = new MandatoryLeavePeriodController();

        $storeRequest = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Update Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            // Mon-Fri = 5 days
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'scope_type' => 'organization',
        ]);
        $controller->store($storeRequest)->toResponse($storeRequest);

        $entitlement->refresh();
        $this->assertSame(-5.0, $entitlement->adjustment_days);

        $period = MandatoryLeavePeriod::where('business_id', 1)->where('name', 'like', 'Update Shutdown%')->firstOrFail();

        // Shrink the range to just 2 days (Mon-Tue).
        $updateRequest = Request::create('/mandatory-leave-days/update', 'POST', [
            'mandatory_leave_period_slug' => $period->slug,
            'name' => $period->name,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'scope_type' => 'organization',
        ]);
        $controller->update($updateRequest)->toResponse($updateRequest);

        $entitlement->refresh();
        $this->assertSame(-2.0, $entitlement->adjustment_days, 'The old 5-day deduction should be fully reversed before the new 2-day one is applied, not stacked.');
        $this->assertSame(1, $period->fresh()->deductions()->count(), 'Exactly one deduction row should exist per employee after re-applying, not accumulated duplicates.');
    }

    public function test_two_concurrent_mandatory_periods_on_the_same_entitlement_stay_independent(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Concurrent Leave ' . uniqid()]);
        $employee = $this->makeEmployee();
        $entitlement = $this->makeEntitlement($employee, $leaveType);

        $this->actAsBusinessUser();
        $controller = new MandatoryLeavePeriodController();

        // First shutdown: Mon-Tue (2 days).
        $firstRequest = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Easter Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'scope_type' => 'organization',
        ]);
        $controller->store($firstRequest)->toResponse($firstRequest);

        // Second, unrelated shutdown: Wed-Fri (3 days).
        $secondRequest = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Year End Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-06',
            'scope_type' => 'organization',
        ]);
        $controller->store($secondRequest)->toResponse($secondRequest);

        $entitlement->refresh();
        $this->assertSame(-5.0, $entitlement->adjustment_days, 'Both periods should stack cumulatively (-2 + -3).');

        $firstPeriod = MandatoryLeavePeriod::where('business_id', 1)->where('name', 'like', 'Easter Shutdown%')->firstOrFail();
        $secondPeriod = MandatoryLeavePeriod::where('business_id', 1)->where('name', 'like', 'Year End Shutdown%')->firstOrFail();

        // Deleting only the first period must restore just its own 2 days,
        // leaving the second period's 3-day deduction untouched.
        $deleteRequest = Request::create('/mandatory-leave-days/delete', 'POST', ['mandatory_leave_period' => $firstPeriod->slug]);
        $controller->destroy($deleteRequest)->toResponse($deleteRequest);

        $entitlement->refresh();
        $this->assertSame(-3.0, $entitlement->adjustment_days, 'Only the deleted periods own 2-day contribution should be reversed.');
        $this->assertSame(1, $secondPeriod->fresh()->deductions()->count(), 'The second periods own deduction row must be untouched.');
    }

    public function test_deleting_a_period_restores_the_balance(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Delete Leave ' . uniqid()]);
        $employee = $this->makeEmployee();
        $entitlement = $this->makeEntitlement($employee, $leaveType);

        $this->actAsBusinessUser();
        $controller = new MandatoryLeavePeriodController();

        $storeRequest = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Delete Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'scope_type' => 'organization',
        ]);
        $controller->store($storeRequest)->toResponse($storeRequest);
        $entitlement->refresh();
        $this->assertSame(-5.0, $entitlement->adjustment_days);

        $period = MandatoryLeavePeriod::where('business_id', 1)->where('name', 'like', 'Delete Shutdown%')->firstOrFail();

        $destroyRequest = Request::create('/mandatory-leave-days/delete', 'POST', [
            'mandatory_leave_period' => $period->slug,
        ]);
        $response = $controller->destroy($destroyRequest)->toResponse($destroyRequest);

        $this->assertSame(200, $response->getStatusCode());
        $entitlement->refresh();
        $this->assertSame(0.0, $entitlement->adjustment_days);
        $this->assertSame(21.0, $entitlement->total_days);
        $this->assertSame(0, MandatoryLeavePeriod::where('id', $period->id)->count());
    }

    public function test_department_scope_requires_at_least_one_valid_department(): void
    {
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Validation Leave ' . uniqid()]);
        $this->actAsBusinessUser();

        $controller = new MandatoryLeavePeriodController();
        $request = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Invalid Scope Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-06',
            'scope_type' => 'department',
            // scope_ids intentionally omitted
        ]);
        $response = $controller->store($request)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, MandatoryLeavePeriod::where('business_id', 1)->where('name', 'like', 'Invalid Scope Shutdown%')->count());
    }

    public function test_business_calendar_only_shows_department_scoped_period_when_that_department_is_filtered(): void
    {
        $deptX = $this->makeDepartment();
        $leaveType = LeaveType::create(['business_id' => 1, 'name' => 'MLP Calendar Leave ' . uniqid()]);
        $employee = $this->makeEmployee($deptX->id);
        $this->makeEntitlement($employee, $leaveType);

        $this->actAsBusinessUser();
        $controller = new MandatoryLeavePeriodController();
        $storeRequest = Request::create('/mandatory-leave-days/store', 'POST', [
            'name' => 'Calendar Dept Shutdown ' . uniqid(),
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'scope_type' => 'department',
            'scope_ids' => [$deptX->id],
        ]);
        $storeResponse = $controller->store($storeRequest)->toResponse($storeRequest);
        $payload = json_decode($storeResponse->getContent(), true);
        $periodName = $payload['data']['period']['name'];

        $business = Business::find(1);
        $calendarController = new LeaveCalendarController();

        // Unfiltered: shows everything, including department-scoped periods.
        $unfilteredRequest = Request::create('/events', 'GET', ['start' => '2026-11-01', 'end' => '2026-11-30']);
        $unfilteredEvents = collect($calendarController->businessEvents($unfilteredRequest, $business)->getData(true));
        $this->assertTrue($unfilteredEvents->contains(fn ($e) => str_contains($e['title'], $periodName)));

        // Filtered by the matching department: still shows.
        $matchingRequest = Request::create('/events', 'GET', ['start' => '2026-11-01', 'end' => '2026-11-30', 'department_id' => $deptX->id]);
        $matchingEvents = collect($calendarController->businessEvents($matchingRequest, $business)->getData(true));
        $this->assertTrue($matchingEvents->contains(fn ($e) => str_contains($e['title'], $periodName)));

        // Filtered by a different department: hidden.
        $otherDept = $this->makeDepartment();
        $otherRequest = Request::create('/events', 'GET', ['start' => '2026-11-01', 'end' => '2026-11-30', 'department_id' => $otherDept->id]);
        $otherEvents = collect($calendarController->businessEvents($otherRequest, $business)->getData(true));
        $this->assertFalse($otherEvents->contains(fn ($e) => str_contains($e['title'], $periodName)));
    }
}
