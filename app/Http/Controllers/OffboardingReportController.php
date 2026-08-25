<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\OffboardingChecklist;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

class OffboardingReportController extends Controller
{
    // ---- Status --------------------------------------------------------

    public function statusPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->statusViewData($request, $business));
    }

    public function statusDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->statusViewData($request, $business), 'offboarding-status');
    }

    private function statusViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfYear();
            $filters->endDate = now()->endOfYear();
        }

        $query = OffboardingChecklist::where('business_id', $business->id)
            ->whereBetween('initiated_at', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->with(['employee.user:id,name', 'employee.department:id,name', 'tasks']);

        $filters->applyToEmployeeScopedQuery($query, 'employee');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $rows = $query->get()->sortByDesc('initiated_at')->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Offboarding Status Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Checklists' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['offboarding.reports.status', $data];
    }

    // ---- Final clearance summary (per checklist) ------------------------

    public function clearanceSummaryPreview(Request $request, Business $business, OffboardingChecklist $checklist)
    {
        return $this->previewFor($this->clearanceSummaryViewData($business, $checklist));
    }

    public function clearanceSummaryDownload(Request $request, Business $business, OffboardingChecklist $checklist)
    {
        return $this->downloadFor($this->clearanceSummaryViewData($business, $checklist), 'offboarding-clearance-' . $checklist->id);
    }

    private function clearanceSummaryViewData(Business $business, OffboardingChecklist $checklist): array
    {
        if ((int) $checklist->business_id !== (int) $business->id) {
            abort(404);
        }

        $checklist->load(['employee.user', 'employee.department', 'tasks.completedBy', 'contractAction']);

        $data = [
            'business' => $business,
            'reportTitle' => 'Offboarding Final Clearance Summary',
            'periodLabel' => optional($checklist->employee->user)->name,
            'meta' => [
                'Status' => ucfirst(str_replace('_', ' ', $checklist->status)),
                'Tasks Complete' => $checklist->progressPercent() . '%',
            ],
            'checklist' => $checklist,
            'filters' => new ReportFilters(),
        ];

        return ['offboarding.reports.clearance-summary', $data];
    }

    // ---- Shared helpers ------------------------------------------------

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
