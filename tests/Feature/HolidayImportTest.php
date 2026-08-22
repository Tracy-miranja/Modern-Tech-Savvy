<?php

namespace Tests\Feature;

use App\Http\Controllers\HolidayController;
use App\Models\Business;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression coverage for auto-loading a business's public holiday
 * calendar from Nager.Date (a free, no-API-key public holiday service),
 * instead of requiring fully manual entry. Network calls are faked so
 * this never depends on the real service being reachable.
 */
class HolidayImportTest extends TestCase
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

        // amsol has real, previously-imported holidays (e.g. Nager.Date's
        // own New Year's Day/Good Friday) that would collide with this
        // test's fake import payload - importFromApi() skips creating a
        // holiday if one already exists for the same business/location/
        // name/date, so a stale real row silently shadows the fresh one
        // this test expects. Rolled back with everything else.
        Holiday::where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    public function test_available_countries_guesses_the_business_country(): void
    {
        $business = Business::find(1);
        $business->update(['country' => 'Kenya']);

        Http::fake([
            'date.nager.at/api/v3/AvailableCountries' => Http::response([
                ['countryCode' => 'KE', 'name' => 'Kenya'],
                ['countryCode' => 'US', 'name' => 'United States'],
            ], 200),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new HolidayController();
        $response = $controller->availableCountries(Request::create('/x'))->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('KE', $payload['data']['guessed_country_code']);
    }

    public function test_import_creates_holidays_and_marks_fixed_dates_recurring(): void
    {
        $business = Business::find(1);

        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/2026/KE' => Http::response([
                ['date' => '2026-01-01', 'name' => "New Year's Day", 'fixed' => true],
                ['date' => '2026-04-03', 'name' => 'Good Friday', 'fixed' => false],
            ], 200),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new HolidayController();
        $request = Request::create('/holidays/import', 'POST', ['country_code' => 'KE', 'year' => 2026]);
        $response = $controller->importFromApi($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());

        $newYear = Holiday::where('business_id', $business->id)->where('name', "New Year's Day")->first();
        $this->assertNotNull($newYear);
        $this->assertTrue((bool) $newYear->is_recurring);

        $goodFriday = Holiday::where('business_id', $business->id)->where('name', 'Good Friday')->first();
        $this->assertNotNull($goodFriday);
        $this->assertFalse((bool) $goodFriday->is_recurring);
    }

    public function test_import_is_safe_to_re_run_without_duplicating(): void
    {
        $business = Business::find(1);

        Http::fake([
            'date.nager.at/api/v3/PublicHolidays/2026/KE' => Http::response([
                ['date' => '2026-01-01', 'name' => "New Year's Day", 'fixed' => true],
            ], 200),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new HolidayController();
        $request1 = Request::create('/holidays/import', 'POST', ['country_code' => 'KE', 'year' => 2026]);
        $controller->importFromApi($request1)->toResponse($request1);

        $request2 = Request::create('/holidays/import', 'POST', ['country_code' => 'KE', 'year' => 2026]);
        $controller->importFromApi($request2)->toResponse($request2);

        $this->assertSame(1, Holiday::where('business_id', $business->id)->where('name', "New Year's Day")->count());
    }

    public function test_import_handles_the_service_being_unreachable(): void
    {
        $business = Business::find(1);

        Http::fake([
            'date.nager.at/*' => Http::response([], 500),
        ]);

        session(['active_business_slug' => $business->slug]);
        $this->actingAs(User::factory()->create());

        $controller = new HolidayController();
        $request = Request::create('/holidays/import', 'POST', ['country_code' => 'KE', 'year' => 2026]);
        $response = $controller->importFromApi($request)->toResponse($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
