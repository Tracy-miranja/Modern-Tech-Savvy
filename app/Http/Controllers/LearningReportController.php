<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CourseEnrollment;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

class LearningReportController extends Controller
{

    public function index(Business $business)
    {
        $departments = \App\Models\Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = \App\Models\JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('learning.reports', compact('business', 'departments', 'jobCategories'));
    }

    public function completionsPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->completionsViewData($request, $business));
    }

    public function completionsDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->completionsViewData($request, $business), 'learning-completions');
    }

    private function completionsViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);

        $query = CourseEnrollment::where('business_id', $business->id)
            ->with(['course:id,title', 'session:id,start_date,location', 'employee.user:id,name', 'employee.department:id,name']);

        $filters->applyToEmployeeScopedQuery($query);

        if ($filters->courseId) {
            $query->where('course_id', $filters->courseId);
        }
        if ($filters->startDate) {
            $query->where('enrolled_at', '>=', $filters->startDate);
        }
        if ($filters->endDate) {
            $query->where('enrolled_at', '<=', $filters->endDate);
        }

        $rows = $query->orderByDesc('enrolled_at')->get();

        $data = [
            'business' => $business,
            'reportTitle' => 'Learning Completions Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => [
                'Total Enrollments' => $rows->count(),
                'Completed' => $rows->where('status', 'completed')->count(),
            ],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['learning.reports.completions', $data];
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
