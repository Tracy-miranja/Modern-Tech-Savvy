<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for reusing the Attendance module's Holiday model inside
 * Leave's day-count calculation, instead of LeaveType duplicating exclusions
 * via its own disconnected excluded_dates field.
 */
class LeaveHolidayExclusionTest extends TestCase
{
    /**
     * See LeaveEntitlementBugFixesTest for why this connection dance is needed:
     * .env.testing's shared DB_DATABASE var points the default (sqlite) and the
     * 'mysql' connection at the same value, so 'mysql' is re-declared here to
     * target the real hrmamsol data this test needs (business_id 1), wrapped
     * in a transaction that's always rolled back.
     */
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

        \App\Models\Business::find(1)->update(['non_working_days' => []]);
        // Real holidays already on file for amsol (e.g. Madaraka Day, a
        // real Kenyan public holiday, falls in June - a range this suite
        // uses) would add unexpected exclusions on top of the ones each
        // test explicitly sets up.
        Holiday::where('business_id', 1)->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    public function test_calculate_total_days_excludes_a_public_holiday_by_default(): void
    {
        $holiday = Holiday::create([
            'business_id' => 1,
            'name' => 'Test Holiday ' . uniqid(),
            'date' => '2026-06-03', // Wednesday
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Holiday Exclusion Leave ' . uniqid(),
            'exclude_public_holidays' => true,
        ]);

        // Mon 2026-06-01 .. Fri 2026-06-05 = 5 weekdays, minus the Wed holiday = 4.
        $total = LeaveRequest::calculateTotalDays(
            '2026-06-01',
            '2026-06-05',
            false,
            $leaveType,
            1
        );

        $this->assertSame(4.0, $total);

        $holiday->delete();
    }

    public function test_calculate_total_days_ignores_holidays_when_leave_type_opts_out(): void
    {
        $holiday = Holiday::create([
            'business_id' => 1,
            'name' => 'Test Holiday ' . uniqid(),
            'date' => '2026-06-03',
            'is_recurring' => false,
            'is_working_day' => false,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Holiday Inclusion Leave ' . uniqid(),
            'exclude_public_holidays' => false,
        ]);

        $total = LeaveRequest::calculateTotalDays(
            '2026-06-01',
            '2026-06-05',
            false,
            $leaveType,
            1
        );

        $this->assertSame(5.0, $total);

        $holiday->delete();
    }

    public function test_calculate_total_days_excludes_recurring_holiday_across_years(): void
    {
        $holiday = Holiday::create([
            'business_id' => 1,
            'name' => 'Recurring Test Holiday ' . uniqid(),
            'date' => '2020-12-25', // original year irrelevant; recurring by month/day
            'is_recurring' => true,
            'is_working_day' => false,
        ]);

        $leaveType = LeaveType::create([
            'business_id' => 1,
            'name' => 'Recurring Holiday Leave ' . uniqid(),
            'exclude_public_holidays' => true,
        ]);

        // Thu 2026-12-24 .. Fri 2026-12-25 = 2 weekdays, minus the recurring
        // Dec 25 holiday (projected onto 2026) = 1.
        $total = LeaveRequest::calculateTotalDays(
            '2026-12-24',
            '2026-12-25',
            false,
            $leaveType,
            1
        );

        $this->assertSame(1.0, $total);

        $holiday->delete();
    }
}
