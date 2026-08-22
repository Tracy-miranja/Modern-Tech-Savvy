<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Kpi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression coverage for the read-only "KPIs" tab on an employee's
 * performance page - part of the Phase-04-adjacent UI overhaul that pulls
 * KPIs into the same page as OKRs/reviews instead of leaving them on a
 * disconnected, unlinked page.
 */
class PerformanceKpiForEmployeeTest extends TestCase
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

    private function makeEmployee(): array
    {
        $user = User::factory()->create();

        $employee = Employee::create([
            'user_id' => $user->id,
            'business_id' => 1,
            'department_id' => 1,
            'employee_code' => 'PKFE-' . uniqid(),
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

    public function test_fetches_only_this_employees_individually_assigned_kpis(): void
    {
        [$user, $employee] = $this->makeEmployee();
        [, $otherEmployee] = $this->makeEmployee();
        $business = Business::find(1);

        $mine = Kpi::create([
            'name' => 'Close 10 tickets',
            'slug' => 'close-10-tickets-' . uniqid(),
            'business_id' => 1,
            'employee_id' => $employee->id,
            'model_type' => 'manual',
            'target_value' => 10,
            'comparison_operator' => '>=',
        ]);
        $mine->results()->create([
            'model_type' => 'manual',
            'model_id' => 0,
            'result_value' => 7,
            'meets_target' => false,
            'measured_at' => now()->toDateString(),
        ]);

        Kpi::create([
            'name' => 'Someone elses KPI',
            'slug' => 'someone-elses-kpi-' . uniqid(),
            'business_id' => 1,
            'employee_id' => $otherEmployee->id,
            'model_type' => 'manual',
            'target_value' => 5,
            'comparison_operator' => '>=',
        ]);

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($user);

        $controller = new PerformanceController();
        $response = $controller->fetchKpisForEmployee($business, $employee)->toResponse(Request::create('/x'));
        $payload = json_decode($response->getContent(), true);

        $names = collect($payload['data'])->pluck('name');
        $this->assertTrue($names->contains('Close 10 tickets'));
        $this->assertFalse($names->contains('Someone elses KPI'));

        $mineData = collect($payload['data'])->firstWhere('name', 'Close 10 tickets');
        $this->assertSame(7.0, (float) $mineData['latest_result']);
        $this->assertSame(70.0, (float) $mineData['progress_percentage']);
    }

    public function test_less_than_or_equal_kpi_hits_100_percent_at_exactly_the_target(): void
    {
        // Regression: the <= branch of Kpi::getProgressPercentage() used
        // (($target - $result) / $target) * 100 + 50, which only reached
        // 100% when result was 0 and gave a merely-on-target result just
        // 50% - backwards for a "stay at/below this ceiling" KPI, where
        // hitting the target exactly should be a full 100%.
        [, $employee] = $this->makeEmployee();

        $kpi = Kpi::create([
            'name' => 'Keep error rate under 5',
            'slug' => 'keep-error-rate-' . uniqid(),
            'business_id' => 1,
            'employee_id' => $employee->id,
            'model_type' => 'manual',
            'target_value' => 5,
            'comparison_operator' => '<=',
        ]);
        $kpi->results()->create([
            'model_type' => 'manual', 'model_id' => 0,
            'result_value' => 5, 'meets_target' => true, 'measured_at' => now()->toDateString(),
        ]);
        $this->assertSame(100.0, $kpi->fresh()->getProgressPercentage());

        // Comfortably under the ceiling is still a full 100%, not a bonus
        // beyond it.
        $kpi->results()->create([
            'model_type' => 'manual', 'model_id' => 0,
            'result_value' => 0, 'meets_target' => true, 'measured_at' => now()->toDateString(),
        ]);
        $this->assertSame(100.0, $kpi->fresh()->getProgressPercentage());

        // Overshooting the ceiling scales the score back down.
        $kpi->results()->create([
            'model_type' => 'manual', 'model_id' => 0,
            'result_value' => 10, 'meets_target' => false, 'measured_at' => now()->toDateString(),
        ]);
        $this->assertSame(0.0, $kpi->fresh()->getProgressPercentage());
    }

    public function test_a_stranger_cannot_view_another_employees_kpis(): void
    {
        [, $employee] = $this->makeEmployee();
        [$strangerUser,] = $this->makeEmployee();
        $business = Business::find(1);

        session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
        $this->actingAs($strangerUser);

        $controller = new PerformanceController();
        $response = $controller->fetchKpisForEmployee($business, $employee)->toResponse(Request::create('/x'));

        $this->assertSame(400, $response->getStatusCode());
    }
}
