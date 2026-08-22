<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Business;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the business-wide "non-working days" setting
 * (the day list itself, e.g. Saturday/Sunday, configurable via the Leave
 * Settings page) combined with LeaveType.exclude_non_working_days (the
 * per-type opt-in for whether those days are excluded from that type's
 * day count) - mirrors the existing exclude_public_holidays pattern.
 * Revised 2026-07-15: this used to apply unconditionally to every leave
 * type; per explicit instruction it's now per-type, matching how public
 * holiday exclusion already worked.
 */
class LeaveNonWorkingDaysTest extends TestCase
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

        // Real holidays already on file for amsol (e.g. Madaraka Day, June
        // 1) would add unexpected exclusions on top of the non-working-day
        // exclusions this suite is specifically testing.
        \App\Models\Holiday::where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    public function test_calculate_total_days_excludes_business_non_working_days(): void
    {
        $business = Business::find(1);
        $business->update(['non_working_days' => [0, 6]]); // Sunday, Saturday

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Annual Leave ' . uniqid(),
            'exclude_public_holidays' => true,
        ]);

        // Mon 2026-06-01 .. Sun 2026-06-07 = 7 calendar days, minus Sat+Sun = 5.
        $total = LeaveRequest::calculateTotalDays(
            '2026-06-01',
            '2026-06-07',
            false,
            $leaveType,
            1
        );

        $this->assertSame(5.0, $total);
    }

    public function test_non_working_days_are_not_excluded_without_a_leave_type(): void
    {
        $business = Business::find(1);
        $business->update(['non_working_days' => [0, 6]]);

        // No LeaveType passed at all - there's no per-type opt-in signal to
        // read, so (consistent with how public-holiday exclusion already
        // behaves in this case) nothing extra is excluded.
        $total = LeaveRequest::calculateTotalDays('2026-06-01', '2026-06-07', false, null, 1);

        $this->assertSame(7.0, $total);
    }

    public function test_a_leave_type_can_opt_out_of_excluding_non_working_days(): void
    {
        $business = Business::find(1);
        $business->update(['non_working_days' => [0, 6]]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Opted Out Leave ' . uniqid(),
            'exclude_non_working_days' => false,
        ]);

        // Same Mon-Sun range as the "excludes" test, but this type doesn't
        // exclude non-working days - all 7 calendar days count.
        $total = LeaveRequest::calculateTotalDays('2026-06-01', '2026-06-07', false, $leaveType, 1);

        $this->assertSame(7.0, $total);
    }

    public function test_no_non_working_days_configured_excludes_nothing_extra(): void
    {
        $business = Business::find(1);
        $business->update(['non_working_days' => []]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'No Non-Working Days Configured Leave ' . uniqid(),
        ]);

        $total = LeaveRequest::calculateTotalDays('2026-06-01', '2026-06-07', false, $leaveType, 1);

        $this->assertSame(7.0, $total);
    }

    public function test_updating_leave_settings_persists_non_working_days(): void
    {
        $business = Business::find(1);
        $user = User::factory()->create();
        $this->actingAs($user);
        session(['active_business_slug' => $business->slug]);

        $controller = new DashboardController();
        $request = Request::create('/leave/settings', 'POST', ['non_working_days' => [0, 6]]);
        $response = $controller->updateLeaveSettings($request)->toResponse($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([0, 6], $business->fresh()->non_working_days);
    }
}
