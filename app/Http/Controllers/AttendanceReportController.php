<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Services\AttendancePolicyService;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use App\Models\WorkSchedule;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{

    public function index(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('attendances.reports', compact('business', 'departments', 'jobCategories'));
    }

    // ---- Daily -------------------------------------------------------

    public function dailyPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->dailyViewData($request, $business));
    }

    public function dailyDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->dailyViewData($request, $business), 'attendance-daily');
    }

    private function dailyViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfDay();
            $filters->endDate = now()->endOfDay();
        }

        return $this->rowReportViewData($business, $filters, 'daily', 'Daily Attendance Report');
    }

    // ---- Full ----------------------------------------------------------

    public function fullPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->fullViewData($request, $business));
    }

    public function fullDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->fullViewData($request, $business), 'attendance-full');
    }

    private function fullViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        return $this->rowReportViewData($business, $filters, 'full', 'Full Attendance Report');
    }

    // ---- Lateness --------------------------------------------------------

    public function latenessPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->latenessViewData($request, $business));
    }

    public function latenessDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->latenessViewData($request, $business), 'attendance-lateness');
    }

    private function latenessViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        return $this->personDayReportViewData($business, $filters, 'lateness', 'Lateness Report', function ($query) {
            $query->where('late_minutes', '>', 0);
        });
    }

    // ---- Absent ------------------------------------------------------

    public function absentPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->absentViewData($request, $business));
    }

    public function absentDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->absentViewData($request, $business), 'attendance-absent');
    }

    private function absentViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        return $this->rowReportViewData($business, $filters, 'absent', 'Absence Report', function ($query) {
            $query->where('is_absent', true)
                ->where('is_working_day', true)
                ->where('is_holiday', false);
        });
    }

    // ---- Overtime ------------------------------------------------------

    public function overtimePreview(Request $request, Business $business)
    {
        return $this->previewFor($this->overtimeViewData($request, $business));
    }

    public function overtimeDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->overtimeViewData($request, $business), 'attendance-overtime');
    }

    private function overtimeViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        return $this->personDayReportViewData($business, $filters, 'overtime', 'Overtime Report', function ($query) {
            $query->where('overtime_hours', '>', 0);
        });
    }

    // ---- Per-member ------------------------------------------------------

    public function perMemberPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->perMemberViewData($request, $business));
    }

    public function perMemberDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->perMemberViewData($request, $business), 'attendance-per-member');
    }

    private function perMemberViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        [$view, $data] = $this->rowReportViewData($business, $filters, 'per-member', 'Per-Member Attendance Report');

        if (empty($filters->employeeIds)) {
            $data['rows'] = collect();
            $data['error'] = 'Select exactly one employee for a per-member report.';
        }

        return [$view, $data];
    }

    // ---- Summary ---------------------------------------------------------

    public function summaryPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->summaryViewData($request, $business));
    }

    public function summaryDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->summaryViewData($request, $business), 'attendance-summary');
    }

    private function summaryViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);

        return $this->aggregatedViewData($business, $filters, 'summary', 'Attendance Summary Report', false);
    }

    // ---- Monthly -----------------------------------------------------

    public function monthlyPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->monthlyViewData($request, $business));
    }

    public function monthlyDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->monthlyViewData($request, $business), 'attendance-monthly');
    }

    private function monthlyViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfMonth();
            $filters->endDate = now()->endOfMonth();
        }

        return $this->aggregatedViewData($business, $filters, 'monthly', 'Monthly Attendance Report', true);
    }

    // department's attendance data, no matter what query params it sends.

    public function myDailyPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->myDailyViewData($request, $business));
    }

    public function myDailyDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->myDailyViewData($request, $business), 'my-attendance-daily');
    }

    private function myDailyViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfDay();
            $filters->endDate = now()->endOfDay();
        }
        $this->forceOwnEmployee($request, $filters);

        return $this->rowReportViewData($business, $filters, 'daily', 'My Daily Attendance');
    }

    public function myMonthlyPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->myMonthlyViewData($request, $business));
    }

    public function myMonthlyDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->myMonthlyViewData($request, $business), 'my-attendance-monthly');
    }

    private function myMonthlyViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfMonth();
            $filters->endDate = now()->endOfMonth();
        }
        $this->forceOwnEmployee($request, $filters);

        return $this->aggregatedViewData($business, $filters, 'monthly', 'My Monthly Attendance', true);
    }

    public function mySummaryPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->mySummaryViewData($request, $business));
    }

    public function mySummaryDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->mySummaryViewData($request, $business), 'my-attendance-summary');
    }

    private function mySummaryViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentMonth($request);
        $this->forceOwnEmployee($request, $filters);

        return $this->aggregatedViewData($business, $filters, 'summary', 'My Attendance Summary', false);
    }

    private function forceOwnEmployee(Request $request, ReportFilters $filters): ReportFilters
    {
        $employee = $request->user()->activeEmployee();
        abort_if(!$employee, 403, 'No employee record found for this account.');

        $filters->employeeIds = [$employee->id];
        $filters->departmentId = null;
        $filters->jobCategoryId = null;

        return $filters;
    }

    // ---- Shared helpers ------------------------------------------------

    private function periodFiltersDefaultingToCurrentMonth(Request $request): ReportFilters
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfMonth();
            $filters->endDate = now()->endOfMonth();
        }

        return $filters;
    }

    private function rowReportViewData(Business $business, ReportFilters $filters, string $viewKey, string $title, ?callable $extra = null): array
    {
        $query = Attendance::where('business_id', $business->id)
            ->whereBetween('date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->with(['employee.user:id,name', 'employee.department:id,name']);

        $filters->applyToEmployeeScopedQuery($query);

        if ($extra) {
            $extra($query);
        }

        $rows = $query->get()
            ->sortBy(fn ($a) => (optional(optional($a->employee)->user)->name ?? '') . '_' . $a->date)
            ->values();

        if ($viewKey === 'absent' && $rows->isNotEmpty()) {
            $scheduledEmployeeIds = WorkSchedule::whereIn('employee_id', $rows->pluck('employee_id')->unique())
                ->pluck('employee_id')->unique()->flip();
            $rows = $rows->filter(fn ($row) => isset($scheduledEmployeeIds[$row->employee_id]))->values();
        }

        $data = [
            'business' => $business,
            'reportTitle' => $title,
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Records' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['attendances.reports.rows', $data];
    }

    private function personDayReportViewData(Business $business, ReportFilters $filters, string $viewKey, string $title, callable $extra): array
    {
        $query = Attendance::where('business_id', $business->id)
            ->whereBetween('date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->with(['employee.user:id,name', 'employee.department:id,name']);

        $filters->applyToEmployeeScopedQuery($query);
        $extra($query);

        $records = $query->get();

        $departments = $records
            ->groupBy(fn ($row) => optional(optional($row->employee)->department)->name ?? 'No Department')
            ->sortKeys()
            ->map(function ($deptRows) {
                return $deptRows
                    ->groupBy('employee_id')
                    ->map(function ($empRows) {
                        $days = $empRows->sortBy('date')->values();

                        return [
                            'employee' => $days->first()->employee,
                            'days' => $days,
                            'total_late_minutes' => (float) $days->sum('late_minutes'),
                            'total_overtime_hours' => (float) $days->sum('overtime_hours'),
                        ];
                    })
                    ->sortBy(fn ($e) => optional($e['employee']->user)->name)
                    ->values();
            });

        $data = [
            'business' => $business,
            'reportTitle' => $title,
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Records' => $records->count()],
            'departments' => $departments,
            'viewKey' => $viewKey,
            'filters' => $filters,
        ];

        return ['attendances.reports.person-days', $data];
    }

    private function aggregatedViewData(Business $business, ReportFilters $filters, string $viewKey, string $title, bool $includeExpectedHours): array
    {
        $employees = $filters->matchingEmployees($business);

        $attendances = Attendance::where('business_id', $business->id)
            ->whereBetween('date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $policyService = $includeExpectedHours ? app(AttendancePolicyService::class) : null;

        $rows = $employees->map(function ($employee) use ($attendances, $filters, $policyService) {
            $records = $attendances->get($employee->id, collect());
            $hasSchedule = WorkSchedule::where('employee_id', $employee->id)->exists();

            $row = [
                'employee' => $employee,
                'is_flexible' => !$hasSchedule,
                'present_days' => $records->where('is_absent', false)->where('is_on_leave', false)->count(),
                'on_leave_days' => $records->where('is_on_leave', true)->count(),
                'late_days' => $records->where('late_minutes', '>', 0)->count(),
                'absent_days' => $records->where('is_absent', true)->where('is_working_day', true)->where('is_holiday', false)->count(),
                'regular_hours' => round((float) $records->sum('regular_hours'), 2),
                'overtime_hours' => round((float) $records->sum('overtime_hours'), 2),
            ];

            if ($policyService) {
                $expected = $policyService->resolveExpectedHoursForPeriod($employee, $filters->startDate, $filters->endDate);
                $actual = $row['regular_hours'] + $row['overtime_hours'];
                $row['expected_hours'] = $expected;
                $row['actual_hours'] = round($actual, 2);
                $row['variance_hours'] = round($actual - $expected, 2);
            }

            return $row;
        })->sortBy(fn ($r) => optional($r['employee']->user)->name)->values();

        $data = [
            'business' => $business,
            'reportTitle' => $title,
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Employees' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ["attendances.reports.{$viewKey}", $data];
    }

    private function previewFor(array $viewAndData): string
    {
        [$view, $data] = $viewAndData;

        return app(ReportPdfService::class)->previewHtml($view, $data);
    }

    private function downloadFor(array $viewAndData, string $filenamePrefix)
    {
        [$view, $data] = $viewAndData;

        $filename = $filenamePrefix . '-' . $data['filters']->startDate->format('Y-m-d') . '.pdf';

        return app(ReportPdfService::class)->download($view, $data, $filename, 'a4', 'landscape');
    }
}
