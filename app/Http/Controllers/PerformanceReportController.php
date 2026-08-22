<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\PerformanceCycle;
use App\Models\PerformanceFeedbackRequest;
use App\Models\PerformanceObjective;
use App\Models\PerformanceReview;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

/**
 * Performance reports - Phase 5 (final phase) of the GUIDE plan, built on
 * the same shared report engine as Attendance/Leave/Disciplinary/Offboarding:
 * a Cycle roster report (kpi/okr/competency/overall scores + grade band per
 * employee) and a 360 report (one employee's full scorecard + compiled
 * feedback from every reviewer). Scores are computed live via
 * PerformanceReview::compute*Score() rather than read off a persisted
 * PerformanceReview row - most employees never get one created until
 * someone opens their performance page (fetchReview() is firstOrCreate,
 * called lazily), so reading only existing rows would silently omit anyone
 * nobody has looked at yet.
 */
class PerformanceReportController extends Controller
{
    /**
     * Trigger page for the general Performance Reports nav item - previously
     * this button lived inline on performance/cycles.blade.php, which also
     * never actually passed real $departments/$jobCategories to the modal
     * (those filters silently did nothing) - fixed here while relocating.
     * The per-employee 360/cycle report button on performance/employee.blade.php
     * stays where it is, scoped to that one employee.
     */
    public function index(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('performance.reports', compact('business', 'departments', 'jobCategories'));
    }

    // ---- Cycle roster ----------------------------------------------------

    public function cyclePreview(Request $request, Business $business)
    {
        return $this->previewFor($this->cycleViewData($request, $business));
    }

    public function cycleDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->cycleViewData($request, $business), 'performance-cycle');
    }

    private function cycleViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        $cycle = $this->resolveCycle($request, $business);

        if (!$cycle) {
            return ['performance.reports.cycle', [
                'business' => $business,
                'reportTitle' => 'Performance Cycle Report',
                'periodLabel' => 'No cycle selected',
                'meta' => [],
                'rows' => collect(),
                'filters' => $filters,
                'error' => 'Select a performance cycle to generate this report.',
            ]];
        }

        $employees = $filters->matchingEmployees($business);

        $rows = $employees->map(fn ($employee) => $this->scoreRowFor($employee, $cycle))
            ->sortBy(fn ($row) => optional($row['employee']->user)->name)
            ->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Performance Cycle Report',
            'periodLabel' => $cycle->name,
            'meta' => ['Cycle' => $cycle->name, 'Total Employees' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['performance.reports.cycle', $data];
    }

    // ---- 360 (per employee per cycle) -----------------------------------

    public function threeSixtyPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->threeSixtyViewData($request, $business));
    }

    public function threeSixtyDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->threeSixtyViewData($request, $business), 'performance-360');
    }

    private function threeSixtyViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        $cycle = $this->resolveCycle($request, $business);

        $data = [
            'business' => $business,
            'reportTitle' => '360 Performance Report',
            'periodLabel' => optional($cycle)->name,
            'meta' => [],
            'filters' => $filters,
        ];

        if (!$cycle) {
            $data['error'] = 'Select a performance cycle to generate this report.';
            return ['performance.reports.three-sixty', $data];
        }

        if (count($filters->employeeIds) !== 1) {
            $data['error'] = 'Select exactly one employee for a 360 report.';
            return ['performance.reports.three-sixty', $data];
        }

        $employee = \App\Models\Employee::where('business_id', $business->id)
            ->with('user', 'department')
            ->find($filters->employeeIds[0]);

        if (!$employee) {
            $data['error'] = 'Employee not found for this business.';
            return ['performance.reports.three-sixty', $data];
        }

        $scoreRow = $this->scoreRowFor($employee, $cycle);

        $objectives = PerformanceObjective::where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->with('keyResults')
            ->get();

        $feedback = PerformanceFeedbackRequest::where('performance_cycle_id', $cycle->id)
            ->where('subject_employee_id', $employee->id)
            ->with(['reviewer.user', 'response'])
            ->get();

        $data['employee'] = $employee;
        $data['cycle'] = $cycle;
        $data['scoreRow'] = $scoreRow;
        $data['objectives'] = $objectives;
        $data['feedback'] = $feedback;
        $data['questions'] = \App\Models\PerformanceFeedbackResponse::QUESTIONS;
        $data['meta'] = ['Employee' => optional($employee->user)->name, 'Cycle' => $cycle->name, 'Reviewers' => $feedback->count()];

        return ['performance.reports.three-sixty', $data];
    }

    // ---- Shared helpers ------------------------------------------------

    private function resolveCycle(Request $request, Business $business): ?PerformanceCycle
    {
        if ($request->filled('cycle_id')) {
            return PerformanceCycle::where('business_id', $business->id)->find((int) $request->input('cycle_id'));
        }

        return PerformanceCycle::where('business_id', $business->id)
            ->orderByDesc('status')
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Live-computed kpi/okr/overall scores + a display-only grade band for
     * one employee in one cycle - never persisted, so it's always current
     * and never depends on a PerformanceReview row actually existing yet.
     * competency_score has no compute*() method (it's manager-entered), so
     * it's pulled from the real row when one exists, else 0.
     */
    private function scoreRowFor($employee, PerformanceCycle $cycle): array
    {
        $existingReview = PerformanceReview::where('performance_cycle_id', $cycle->id)
            ->where('employee_id', $employee->id)
            ->first();

        $transient = new PerformanceReview([
            'performance_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
        ]);
        $transient->setRelation('cycle', $cycle);

        $kpiScore = $transient->computeKpiScore();
        $okrScore = $transient->computeOkrScore();
        $competencyScore = (float) ($existingReview->competency_score ?? 0);

        $transient->kpi_score = $kpiScore;
        $transient->okr_score = $okrScore;
        $transient->competency_score = $competencyScore;
        $overallScore = $transient->computeOverallScore();

        return [
            'employee' => $employee,
            'kpi_score' => $kpiScore,
            'okr_score' => $okrScore,
            'competency_score' => $competencyScore,
            'overall_score' => $overallScore,
            'grade_band' => PerformanceObjective::gradeBandForScore($overallScore / 100),
        ];
    }

    private function previewFor(array $viewAndData): string
    {
        [$view, $data] = $viewAndData;

        return app(ReportPdfService::class)->previewHtml($view, $data);
    }

    private function downloadFor(array $viewAndData, string $filenamePrefix)
    {
        [$view, $data] = $viewAndData;

        $filename = $filenamePrefix . '-' . now()->format('Y-m-d') . '.pdf';

        return app(ReportPdfService::class)->download($view, $data, $filename, 'a4', 'landscape');
    }
}
