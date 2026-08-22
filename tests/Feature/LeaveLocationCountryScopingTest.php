<?php

namespace Tests\Feature;

use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveCalendarController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression coverage for Location.country / Holiday.location_id: a
 * business with branches in different countries (e.g. Kenya and Uganda)
 * must have each branch's employees subject to that branch's own
 * country's public holidays - on top of whatever holidays apply
 * business-wide - not a single business-wide holiday set for everyone.
 */
class LeaveLocationCountryScopingTest extends TestCase
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

        Business::find(1)->update(['non_working_days' => []]);
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    private function makeLocation(?string $country): Location
    {
        return Location::create([
            'business_id' => 1,
            'name' => 'Test Location ' . uniqid(),
            'country' => $country,
        ]);
    }

    private function makeEmployeeAt(?int $locationId, ?int $departmentId = 1): Employee
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => $departmentId,
            'location_id' => $locationId,
            'employee_code' => 'LOC-' . uniqid(),
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'national_id' => (string) random_int(10000000, 99999999),
            'tax_no' => 'A' . random_int(1000000, 9999999),
        ]);

        EmploymentDetail::create([
            'employee_id' => $employee->id,
            'department_id' => $departmentId,
            'job_category_id' => 1,
            'employment_date' => '2020-01-01',
            'employment_term' => 'permanent',
        ]);

        return $employee->fresh();
    }

    public function test_resolved_country_falls_back_to_business_country_when_unset(): void
    {
        $location = $this->makeLocation(null);

        $this->assertSame('Kenya', $location->resolvedCountry(), 'business_id 1 is a Kenyan business; a location with no country of its own should inherit it.');
    }

    public function test_resolved_country_uses_its_own_value_when_set(): void
    {
        $location = $this->makeLocation('Uganda');

        $this->assertSame('Uganda', $location->resolvedCountry());
    }

    public function test_is_holiday_business_wide_holiday_applies_regardless_of_location(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');

        $date = Carbon::create(2027, 3, 15);
        $holiday = Holiday::create([
            'business_id' => 1,
            'location_id' => null,
            'name' => 'Company Founding Day ' . uniqid(),
            'date' => $date,
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $this->assertNotNull(Holiday::isHoliday(1, $date, null));
        $this->assertNotNull(Holiday::isHoliday(1, $date, $kenyaLocation->id));
        $this->assertNotNull(Holiday::isHoliday(1, $date, $ugandaLocation->id));

        $holiday->delete();
    }

    public function test_is_holiday_location_specific_holiday_only_applies_at_its_own_location(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');

        $date = Carbon::create(2027, 6, 9); // Uganda Heroes Day, arbitrary test date
        $holiday = Holiday::create([
            'business_id' => 1,
            'location_id' => $ugandaLocation->id,
            'name' => 'Uganda Test Holiday ' . uniqid(),
            'date' => $date,
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $this->assertNotNull(Holiday::isHoliday(1, $date, $ugandaLocation->id), 'The holiday must apply at its own location.');
        $this->assertNull(Holiday::isHoliday(1, $date, $kenyaLocation->id), 'A Uganda-only holiday must NOT apply at a Kenya location.');
        $this->assertNull(Holiday::isHoliday(1, $date, null), 'A location-specific holiday must NOT leak into the business-wide (no location) view.');

        $holiday->delete();
    }

    public function test_leave_day_calculation_excludes_only_the_employees_own_location_holidays(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');

        $kenyaEmployee = $this->makeEmployeeAt($kenyaLocation->id);
        $ugandaEmployee = $this->makeEmployeeAt($ugandaLocation->id);

        // Business-wide holiday: excluded for everyone.
        $businessWide = Holiday::create([
            'business_id' => 1,
            'location_id' => null,
            'name' => 'Business Wide Holiday ' . uniqid(),
            'date' => '2027-08-04', // Wednesday
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        // Kenya-only holiday: excluded only for the Kenya employee.
        $kenyaOnly = Holiday::create([
            'business_id' => 1,
            'location_id' => $kenyaLocation->id,
            'name' => 'Kenya Only Holiday ' . uniqid(),
            'date' => '2027-08-05', // Thursday
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        // Uganda-only holiday: excluded only for the Uganda employee.
        $ugandaOnly = Holiday::create([
            'business_id' => 1,
            'location_id' => $ugandaLocation->id,
            'name' => 'Uganda Only Holiday ' . uniqid(),
            'date' => '2027-08-06', // Friday
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Location Scoping Leave ' . uniqid(),
            'exclude_public_holidays' => true,
        ]);

        // Mon 2027-08-02 .. Fri 2027-08-06 = 5 weekdays.
        $kenyaLeave = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $kenyaEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-08-02',
            'end_date' => '2027-08-06',
            'current_approval_level' => 0,
        ]);

        $ugandaLeave = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $ugandaEmployee->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-08-02',
            'end_date' => '2027-08-06',
            'current_approval_level' => 0,
        ]);

        // Kenya employee: 5 weekdays - business-wide - Kenya-only = 3. Uganda-only holiday does NOT apply.
        $this->assertSame(3.0, (float) $kenyaLeave->fresh()->total_days, 'Kenya employee should only lose business-wide + Kenya-only holidays.');

        // Uganda employee: 5 weekdays - business-wide - Uganda-only = 3. Kenya-only holiday does NOT apply.
        $this->assertSame(3.0, (float) $ugandaLeave->fresh()->total_days, 'Uganda employee should only lose business-wide + Uganda-only holidays.');

        $businessWide->delete();
        $kenyaOnly->delete();
        $ugandaOnly->delete();
    }

    public function test_leave_day_calculation_for_employee_with_no_location_only_sees_business_wide_holidays(): void
    {
        $ugandaLocation = $this->makeLocation('Uganda');
        $employeeWithNoLocation = $this->makeEmployeeAt(null);

        $ugandaOnly = Holiday::create([
            'business_id' => 1,
            'location_id' => $ugandaLocation->id,
            'name' => 'Uganda Only Holiday ' . uniqid(),
            'date' => '2027-09-01', // Wednesday
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'No Location Leave ' . uniqid(),
            'exclude_public_holidays' => true,
        ]);

        // Mon 2027-08-30 .. Fri 2027-09-03 = 5 weekdays.
        $leave = LeaveRequest::create([
            'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
            'employee_id' => $employeeWithNoLocation->id,
            'business_id' => 1,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-08-30',
            'end_date' => '2027-09-03',
            'current_approval_level' => 0,
        ]);

        $this->assertSame(5.0, (float) $leave->fresh()->total_days, 'An employee with no location should not be affected by a location-specific holiday.');

        $ugandaOnly->delete();
    }

    public function test_business_calendar_events_filters_holidays_by_location(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');

        $businessWide = Holiday::create([
            'business_id' => 1,
            'location_id' => null,
            'name' => 'Calendar Business Wide ' . uniqid(),
            'date' => '2027-10-01',
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $ugandaOnly = Holiday::create([
            'business_id' => 1,
            'location_id' => $ugandaLocation->id,
            'name' => 'Calendar Uganda Only ' . uniqid(),
            'date' => '2027-10-02',
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $business = Business::find(1);
        $controller = new LeaveCalendarController();

        $requestNoFilter = Request::create('/events', 'GET', ['start' => '2027-10-01', 'end' => '2027-10-31']);
        $eventsNoFilter = $controller->businessEvents($requestNoFilter, $business)->getData(true);
        $namesNoFilter = collect($eventsNoFilter)->pluck('title');
        $this->assertTrue($namesNoFilter->contains(fn ($t) => str_contains($t, $businessWide->name)));
        $this->assertFalse($namesNoFilter->contains(fn ($t) => str_contains($t, $ugandaOnly->name)), 'Without a location filter, location-specific holidays must not appear.');

        $requestUganda = Request::create('/events', 'GET', ['start' => '2027-10-01', 'end' => '2027-10-31', 'location_id' => $ugandaLocation->id]);
        $eventsUganda = $controller->businessEvents($requestUganda, $business)->getData(true);
        $namesUganda = collect($eventsUganda)->pluck('title');
        $this->assertTrue($namesUganda->contains(fn ($t) => str_contains($t, $businessWide->name)));
        $this->assertTrue($namesUganda->contains(fn ($t) => str_contains($t, $ugandaOnly->name)), 'Filtering by the Uganda location must include its own holiday.');

        $requestKenya = Request::create('/events', 'GET', ['start' => '2027-10-01', 'end' => '2027-10-31', 'location_id' => $kenyaLocation->id]);
        $eventsKenya = $controller->businessEvents($requestKenya, $business)->getData(true);
        $namesKenya = collect($eventsKenya)->pluck('title');
        $this->assertFalse($namesKenya->contains(fn ($t) => str_contains($t, $ugandaOnly->name)), 'Filtering by the Kenya location must NOT include the Uganda-only holiday.');

        $businessWide->delete();
        $ugandaOnly->delete();
    }

    public function test_business_calendar_events_location_filter_also_restricts_leave_events(): void
    {
        $kenyaLocation = $this->makeLocation('Kenya');
        $ugandaLocation = $this->makeLocation('Uganda');

        $kenyaEmployee = $this->makeEmployeeAt($kenyaLocation->id);
        $ugandaEmployee = $this->makeEmployeeAt($ugandaLocation->id);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Calendar Leave Type ' . uniqid(),
            'exclude_public_holidays' => false,
        ]);

        $approverUser = User::factory()->create();

        foreach ([$kenyaEmployee, $ugandaEmployee] as $employee) {
            LeaveRequest::create([
                'reference_number' => LeaveRequest::generateUniqueReferenceNumber(1),
                'employee_id' => $employee->id,
                'business_id' => 1,
                'leave_type_id' => $leaveType->id,
                'start_date' => '2027-11-10',
                'end_date' => '2027-11-11',
                'current_approval_level' => 1,
                'approved_by' => $approverUser->id,
            ]);
        }

        $business = Business::find(1);
        $controller = new LeaveCalendarController();

        $requestUganda = Request::create('/events', 'GET', ['start' => '2027-11-01', 'end' => '2027-11-30', 'location_id' => $ugandaLocation->id]);
        $eventsUganda = collect($controller->businessEvents($requestUganda, $business)->getData(true));

        $ugandaLeaveEventsCount = $eventsUganda->where('extendedProps.type', 'leave')->count();
        $this->assertSame(1, $ugandaLeaveEventsCount, 'Filtering the business calendar by the Uganda location should only show the Uganda employee\'s leave.');
    }

    public function test_available_countries_guesses_the_locations_own_country_not_the_business_default(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);

        $ugandaLocation = $this->makeLocation('Uganda');

        Http::fake([
            'https://date.nager.at/api/v3/AvailableCountries' => Http::response([
                ['countryCode' => 'KE', 'name' => 'Kenya'],
                ['countryCode' => 'UG', 'name' => 'Uganda'],
            ], 200),
        ]);

        $controller = new HolidayController();

        $requestNoLocation = Request::create('/countries', 'GET');
        $responseNoLocation = $controller->availableCountries($requestNoLocation)->toResponse($requestNoLocation);
        $dataNoLocation = json_decode($responseNoLocation->getContent(), true);
        $this->assertSame('KE', $dataNoLocation['data']['guessed_country_code'], 'With no location, should guess the business\'s own country (Kenya).');

        $requestWithLocation = Request::create('/countries', 'GET', ['location_id' => $ugandaLocation->id]);
        $responseWithLocation = $controller->availableCountries($requestWithLocation)->toResponse($requestWithLocation);
        $dataWithLocation = json_decode($responseWithLocation->getContent(), true);
        $this->assertSame('UG', $dataWithLocation['data']['guessed_country_code'], 'With the Uganda location given, should guess Uganda instead of the business default.');
    }

    public function test_import_from_api_scopes_imported_holidays_to_the_given_location(): void
    {
        $business = Business::find(1);
        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $ugandaLocation = $this->makeLocation('Uganda');

        Http::fake([
            'https://date.nager.at/api/v3/PublicHolidays/2029/UG' => Http::response([
                ['name' => 'Test Import Holiday ' . uniqid(), 'date' => '2029-01-26', 'fixed' => true],
            ], 200),
        ]);

        $controller = new HolidayController();
        $request = Request::create('/import', 'POST', [
            'country_code' => 'UG',
            'year' => 2029,
            'location_id' => $ugandaLocation->id,
        ]);

        $controller->importFromApi($request);

        $imported = Holiday::where('business_id', 1)
            ->where('location_id', $ugandaLocation->id)
            ->whereYear('date', 2029)
            ->first();

        $this->assertNotNull($imported, 'The imported holiday should be persisted scoped to the given location.');
        $this->assertSame($ugandaLocation->id, $imported->location_id);

        $imported->delete();
    }
}
