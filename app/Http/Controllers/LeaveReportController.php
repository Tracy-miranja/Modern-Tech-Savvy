<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

class LeaveReportController extends Controller
{

    public function index(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $leavePeriods = $business->leavePeriods()->orderByDesc('start_date')->get(['id', 'name']);
        $jobCategories = JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('leave.reports', compact('business', 'departments', 'leaveTypes', 'leavePeriods', 'jobCategories'));
    }

    // ---- Balance ---------------------------------------------------------

    public function balancePreview(Request $request, Business $business)
    {
        return $this->previewFor($this->balanceViewData($request, $business));
    }

    public function balanceDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->balanceViewData($request, $business), 'leave-balance');
    }

    private function balanceViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);

        $leavePeriod = $filters->leavePeriodId
            ? LeavePeriod::where('business_id', $business->id)->find($filters->leavePeriodId)
            : LeavePeriod::where('business_id', $business->id)
                ->orderByDesc('is_active')->orderByDesc('start_date')->first();

        if (!$leavePeriod) {
            return ['leave.reports.balance', [
                'business' => $business,
                'reportTitle' => 'Leave Balance Report',
                'periodLabel' => 'No leave period configured',
                'meta' => [],
                'rows' => collect(),
                'leaveTypeNames' => collect(),
                'filters' => $filters,
            ]];
        }

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id)

            ->whereHas('employee');

        $filters->applyToEmployeeScopedQuery($query);

        if (!empty($filters->leaveTypeIds)) {
            $query->whereIn('leave_type_id', $filters->leaveTypeIds);
        }

        $entitlements = $query->with(['employee.user', 'employee.department', 'leaveType'])->get();

        $leaveTypeNames = $entitlements->pluck('leaveType.name')->filter()->unique()->sort()->values();

        $rows = $entitlements->groupBy('employee_id')->map(function ($employeeEntitlements) use ($leaveTypeNames) {
            $employee = $employeeEntitlements->first()->employee;
            $remainingByType = [];
            foreach ($leaveTypeNames as $typeName) {
                $match = $employeeEntitlements->first(fn ($e) => optional($e->leaveType)->name === $typeName);
                $remainingByType[$typeName] = $match ? (float) $match->days_remaining : null;
            }
            return ['employee' => $employee, 'remaining' => $remainingByType];
        })->sortBy(fn ($row) => optional(optional($row['employee'])->user)->name)->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Leave Balance Report',
            'periodLabel' => $leavePeriod->name,
            'meta' => ['Leave Period' => $leavePeriod->name, 'Total Employees' => $rows->count()],
            'rows' => $rows,
            'leaveTypeNames' => $leaveTypeNames,
            'filters' => $filters,
        ];

        return ['leave.reports.balance', $data];
    }

    // ---- Master (full entitlement breakdown, one row per employee+type) ---

    public function masterPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->masterViewData($request, $business));
    }

    public function masterDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->masterViewData($request, $business), 'leave-master');
    }

    private function masterViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);

        $leavePeriod = $filters->leavePeriodId
            ? LeavePeriod::where('business_id', $business->id)->find($filters->leavePeriodId)
            : LeavePeriod::where('business_id', $business->id)
                ->orderByDesc('is_active')->orderByDesc('start_date')->first();

        if (!$leavePeriod) {
            return ['leave.reports.master', [
                'business' => $business,
                'reportTitle' => 'Leave Master Report',
                'periodLabel' => 'No leave period configured',
                'meta' => [],
                'rows' => collect(),
                'filters' => $filters,
            ]];
        }

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id)
            ->whereHas('employee');

        $filters->applyToEmployeeScopedQuery($query);

        if (!empty($filters->leaveTypeIds)) {
            $query->whereIn('leave_type_id', $filters->leaveTypeIds);
        }

        $rows = $query->with(['employee.user', 'employee.department', 'leaveType'])
            ->get()
            ->sortBy(fn ($e) => optional(optional($e->employee)->user)->name . '|' . optional($e->leaveType)->name)
            ->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Leave Master Report',
            'periodLabel' => $leavePeriod->name,
            'meta' => ['Leave Period' => $leavePeriod->name, 'Total Records' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['leave.reports.master', $data];
    }

    // ---- Full --------------------------------------------------------------

    public function fullPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->fullViewData($request, $business));
    }

    public function fullDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->fullViewData($request, $business), 'leave-full');
    }

    private function fullViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentYear($request);

        return $this->rowReportViewData($business, $filters, 'Full Leave Report');
    }

    // ---- Per-member ----------------------------------------------------

    public function perMemberPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->perMemberViewData($request, $business));
    }

    public function perMemberDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->perMemberViewData($request, $business), 'leave-per-member');
    }

    private function perMemberViewData(Request $request, Business $business): array
    {

        $filters = ReportFilters::fromRequest($request);

        [$view, $data] = $this->rowReportViewData($business, $filters, 'Per-Member Leave Report');

        if (empty($filters->employeeIds)) {
            $data['rows'] = collect();
            $data['error'] = 'Select exactly one employee for a per-member report.';
        } else {
            $data['entitlements'] = LeaveEntitlement::where('business_id', $business->id)
                ->where('employee_id', $filters->employeeIds[0])
                ->with(['leaveType:id,name', 'leavePeriod:id,name'])
                ->get()
                ->sortByDesc(fn ($e) => optional($e->leavePeriod)->start_date)
                ->values();
        }

        return [$view, $data];
    }

    // ---- Types -------------------------------------------------------------

    public function typesPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->typesViewData($request, $business));
    }

    public function typesDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->typesViewData($request, $business), 'leave-types');
    }

    private function typesViewData(Request $request, Business $business): array
    {
        $filters = $this->periodFiltersDefaultingToCurrentYear($request);

        $query = LeaveRequest::where('business_id', $business->id)
            ->whereBetween('start_date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->with('leaveType:id,name');

        $filters->applyToEmployeeScopedQuery($query);

        $requests = $query->get();

        $rows = $requests->groupBy('leave_type_id')->map(function ($group) {
            $approved = $group->filter(fn ($r) => $r->status === 'approved');
            return [
                'leave_type' => $group->first()->leaveType,
                'request_count' => $group->count(),
                'total_days' => round((float) $approved->sum('total_days'), 1),
                'average_duration' => $approved->count() > 0 ? round((float) $approved->avg('total_days'), 1) : 0.0,
            ];
        })->sortBy(fn ($r) => optional($r['leave_type'])->name)->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Leave Types Usage Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Leave Types' => $rows->count(), 'Total Requests' => $requests->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['leave.reports.types', $data];
    }

    // ---- Shared helpers ------------------------------------------------

    private function periodFiltersDefaultingToCurrentYear(Request $request): ReportFilters
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfYear();
            $filters->endDate = now()->endOfYear();
        }

        return $filters;
    }

    private function rowReportViewData(Business $business, ReportFilters $filters, string $title): array
    {
        $query = LeaveRequest::where('business_id', $business->id)
            ->with(['employee.user:id,name', 'employee.department:id,name', 'leaveType:id,name', 'approvedBy:id,name']);

        if ($filters->startDate && $filters->endDate) {
            $query->whereBetween('start_date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()]);
        }

        $filters->applyToEmployeeScopedQuery($query);

        if (!empty($filters->leaveTypeIds)) {
            $query->whereIn('leave_type_id', $filters->leaveTypeIds);
        }

        $rows = $query->get()
            ->sortByDesc(fn ($r) => $r->start_date)
            ->values();

        $data = [
            'business' => $business,
            'reportTitle' => $title,
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Records' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['leave.reports.rows', $data];
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
