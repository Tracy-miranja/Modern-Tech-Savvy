<?php

namespace Tests\Feature;

use App\Http\Controllers\PerformanceReportController;
use App\Models\Business;
use App\Models\Employee;
use App\Models\EmploymentDetail;
use App\Models\Department;
use App\Models\Kpi;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceFeedbackResponse;
use App\Models\PerformanceObjective;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance reports (GUIDE plan Phase 5, the final phase) - Cycle roster
 * report and 360 report, both built on the same shared engine as
 * Attendance/Leave/Disciplinary/Offboarding. Scores are computed live
 * (never read off a possibly-nonexistent PerformanceReview row), so an
 * employee with zero objectives/KPIs still shows as an explicit zero.
 */
class PerformanceReportsTest extends TestCase
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
            'employee_code' => 'PRT-' . uniqid(),
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

    private function makeCycle(array $overrides = []): PerformanceCycle
    {
        return PerformanceCycle::create(array_merge([
            'business_id' => 1,
            'name' => 'PRT Cycle ' . uniqid(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'kpi_weight' => 40,
            'okr_weight' => 40,
            'competency_weight' => 20,
            'status' => 'active',
        ], $overrides));
    }

    // ---- Cycle roster report -----------------------------------------

    public function test_cycle_report_shows_an_explicit_zero_for_an_employee_with_no_data_yet(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();
        [, $employee] = $this->makeEmployeeUser();

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id, 'employee_ids' => [$employee->id]]);
        $html = $controller->cyclePreview($request, $business);

        $this->assertStringContainsString(htmlspecialchars(optional($employee->user)->name), $html);
        $this->assertStringContainsString('0%', $html);
    }

    public function test_cycle_report_computes_live_scores_and_a_grade_band(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();
        [, $employee] = $this->makeEmployeeUser();

        $objective = PerformanceObjective::create([
            'business_id' => 1, 'performance_cycle_id' => $cycle->id, 'employee_id' => $employee->id,
            'title' => 'PRT Objective', 'weight' => 100, 'status' => 'on_track',
        ]);
        $objective->keyResults()->create(['description' => 'KR', 'target_value' => 10, 'current_value' => 10, 'weight' => 100]);

        $kpi = Kpi::create([
            'name' => 'PRT KPI', 'slug' => 'prt-kpi-' . uniqid(), 'business_id' => 1,
            'employee_id' => $employee->id, 'model_type' => 'manual', 'target_value' => 10, 'comparison_operator' => '>=',
        ]);
        $kpi->results()->create(['model_type' => 'manual', 'model_id' => 0, 'result_value' => 10, 'meets_target' => true, 'measured_at' => now()->toDateString()]);

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id, 'employee_ids' => [$employee->id]]);
        $html = $controller->cyclePreview($request, $business);

        // OKR + KPI both at 100%, competency defaults to 0 (no manual review
        // row) -> overall = (100*40 + 100*40 + 0*20) / 100 = 80 -> "green" band.
        $this->assertStringContainsString('100%', $html);
        $this->assertStringContainsString('80%', $html);
        $this->assertStringContainsString('green', $html);
    }

    public function test_cycle_report_respects_the_department_filter(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();
        [, $inDept] = $this->makeEmployeeUser();
        [, $otherDept] = $this->makeEmployeeUser();
        $otherDepartment = Department::create(['business_id' => 1, 'name' => 'PRT Dept ' . uniqid()]);
        $otherDept->update(['department_id' => $otherDepartment->id]);

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id, 'department_id' => 1]);
        $html = $controller->cyclePreview($request, $business);

        $this->assertStringContainsString(htmlspecialchars(optional($inDept->user)->name), $html);
        $this->assertStringNotContainsString(htmlspecialchars(optional($otherDept->user)->name), $html);
    }

    // ---- 360 report ---------------------------------------------------

    public function test_360_report_requires_exactly_one_employee_selected(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id]);
        $html = $controller->threeSixtyPreview($request, $business);

        $this->assertStringContainsString('Select exactly one employee', $html);
    }

    public function test_360_report_compiles_submitted_feedback_and_flags_pending_reviewers(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();
        [, $subject] = $this->makeEmployeeUser();
        [, $reviewerA] = $this->makeEmployeeUser();
        [, $reviewerB] = $this->makeEmployeeUser();
        [$requester,] = $this->makeEmployeeUser();

        $requestA = PerformanceFeedbackRequest::create([
            'business_id' => 1, 'performance_cycle_id' => $cycle->id, 'subject_employee_id' => $subject->id,
            'reviewer_employee_id' => $reviewerA->id, 'requested_by' => $requester->id, 'status' => 'submitted',
        ]);
        PerformanceFeedbackResponse::create([
            'performance_feedback_request_id' => $requestA->id,
            'answers' => ['strengths' => 'Great at debugging.', 'growth_areas' => 'Could delegate more.', 'collaboration_example' => 'Helped ship X.', 'additional_comments' => 'Keep it up.'],
            'submitted_at' => now(),
        ]);

        PerformanceFeedbackRequest::create([
            'business_id' => 1, 'performance_cycle_id' => $cycle->id, 'subject_employee_id' => $subject->id,
            'reviewer_employee_id' => $reviewerB->id, 'requested_by' => $requester->id, 'status' => 'pending',
        ]);

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id, 'employee_ids' => [$subject->id]]);
        $html = $controller->threeSixtyPreview($request, $business);

        $this->assertStringContainsString(htmlspecialchars(optional($reviewerA->user)->name), $html);
        $this->assertStringContainsString('Great at debugging.', $html);
        $this->assertStringContainsString(htmlspecialchars(optional($reviewerB->user)->name), $html);
        $this->assertStringContainsString('No response submitted yet.', $html);
    }

    public function test_360_report_only_includes_feedback_for_the_selected_employee(): void
    {
        $business = Business::find(1);
        $cycle = $this->makeCycle();
        [, $subject] = $this->makeEmployeeUser();
        [, $otherSubject] = $this->makeEmployeeUser();
        [, $reviewer] = $this->makeEmployeeUser();
        [$requester,] = $this->makeEmployeeUser();

        PerformanceFeedbackRequest::create([
            'business_id' => 1, 'performance_cycle_id' => $cycle->id, 'subject_employee_id' => $subject->id,
            'reviewer_employee_id' => $reviewer->id, 'requested_by' => $requester->id, 'status' => 'pending',
        ]);
        $notMine = PerformanceFeedbackRequest::create([
            'business_id' => 1, 'performance_cycle_id' => $cycle->id, 'subject_employee_id' => $otherSubject->id,
            'reviewer_employee_id' => $reviewer->id, 'requested_by' => $requester->id, 'status' => 'submitted',
        ]);
        PerformanceFeedbackResponse::create([
            'performance_feedback_request_id' => $notMine->id,
            'answers' => ['strengths' => 'PRT unique marker text should not leak.', 'growth_areas' => '', 'collaboration_example' => '', 'additional_comments' => ''],
            'submitted_at' => now(),
        ]);

        $controller = new PerformanceReportController();
        $request = Request::create('/x', 'GET', ['cycle_id' => $cycle->id, 'employee_ids' => [$subject->id]]);
        $html = $controller->threeSixtyPreview($request, $business);

        $this->assertStringNotContainsString('PRT unique marker text should not leak.', $html);
    }
}
