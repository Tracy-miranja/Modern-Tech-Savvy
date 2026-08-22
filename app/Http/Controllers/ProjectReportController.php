<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ProjectTask;
use App\Models\ProjectTimeLog;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

/**
 * Project reports - Project Reports + Time Tracking from the module's
 * feature list, built on the same shared report engine as every other
 * module. Two reports: Task Status (every task in the filtered scope) and
 * Time Tracking (every logged hour in the filtered scope).
 */
class ProjectReportController extends Controller
{
    /**
     * Trigger page for the Project Reports nav item - previously this
     * button lived inline on projects/index.blade.php; promoted to its own
     * page/route so Reports is directly reachable from the sidebar.
     */
    public function index(Business $business)
    {
        $departments = \App\Models\Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = \App\Models\JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('projects.reports', compact('business', 'departments', 'jobCategories'));
    }

    public function taskStatusPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->taskStatusViewData($request, $business));
    }

    public function taskStatusDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->taskStatusViewData($request, $business), 'project-task-status');
    }

    private function taskStatusViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);

        $query = ProjectTask::where('business_id', $business->id)
            ->with(['project:id,name', 'status:id,name,is_done', 'category:id,name', 'assignee.user:id,name', 'assignee.department:id,name']);

        if ($filters->projectId) {
            $query->where('project_id', $filters->projectId);
        }
        if (!empty($filters->employeeIds)) {
            $query->whereIn('assignee_employee_id', $filters->employeeIds);
        }
        if ($filters->departmentId) {
            $query->whereHas('assignee', fn ($q) => $q->where('department_id', $filters->departmentId));
        }
        if ($filters->jobCategoryId) {
            $query->whereHas('assignee.employmentDetails', fn ($q) => $q->where('job_category_id', $filters->jobCategoryId));
        }
        if ($filters->startDate) {
            $query->whereDate('due_date', '>=', $filters->startDate);
        }
        if ($filters->endDate) {
            $query->whereDate('due_date', '<=', $filters->endDate);
        }

        $rows = $query->orderBy('due_date')->get();

        $data = [
            'business' => $business,
            'reportTitle' => 'Project Task Status Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => [
                'Total Tasks' => $rows->count(),
                'Completed' => $rows->whereNotNull('completed_at')->count(),
                'Overdue' => $rows->filter(fn ($t) => $t->isOverdue())->count(),
            ],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['projects.reports.task_status', $data];
    }

    public function timeTrackingPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->timeTrackingViewData($request, $business));
    }

    public function timeTrackingDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->timeTrackingViewData($request, $business), 'project-time-tracking');
    }

    private function timeTrackingViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);

        $query = ProjectTimeLog::where('business_id', $business->id)
            ->with(['project:id,name', 'task:id,title', 'employee.user:id,name', 'employee.department:id,name']);

        $filters->applyToEmployeeScopedQuery($query);

        if ($filters->projectId) {
            $query->where('project_id', $filters->projectId);
        }
        if ($filters->startDate) {
            $query->whereDate('date', '>=', $filters->startDate);
        }
        if ($filters->endDate) {
            $query->whereDate('date', '<=', $filters->endDate);
        }

        $rows = $query->orderByDesc('date')->get();

        $data = [
            'business' => $business,
            'reportTitle' => 'Project Time Tracking Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => [
                'Total Entries' => $rows->count(),
                'Total Hours' => number_format((float) $rows->sum('hours'), 2),
            ],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['projects.reports.time_tracking', $data];
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
