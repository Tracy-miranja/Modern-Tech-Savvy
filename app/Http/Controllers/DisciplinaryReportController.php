<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Warning;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

/**
 * Disciplinary reports - Phase 3 of the GUIDE plan, built on the same
 * shared report engine as Attendance/Leave: cases filtered by
 * employee/department/period/stage/status, plus a per-case
 * escalation-trail printout.
 */
class DisciplinaryReportController extends Controller
{
    // ---- Cases -------------------------------------------------------

    public function casesPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->casesViewData($request, $business));
    }

    public function casesDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->casesViewData($request, $business), 'disciplinary-cases');
    }

    private function casesViewData(Request $request, Business $business): array
    {
        $filters = ReportFilters::fromRequest($request);
        if (!$filters->startDate) {
            $filters->startDate = now()->startOfYear();
            $filters->endDate = now()->endOfYear();
        }

        $query = Warning::where('business_id', $business->id)
            ->whereBetween('issue_date', [$filters->startDate->toDateString(), $filters->endDate->toDateString()])
            ->with(['employee.user:id,name', 'employee.department:id,name', 'stageType:id,name', 'issuedBy:id,name']);

        $filters->applyToEmployeeScopedQuery($query);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('stage_type_id')) {
            $query->where('stage_type_id', (int) $request->input('stage_type_id'));
        }

        $rows = $query->get()->sortByDesc('issue_date')->values();

        $data = [
            'business' => $business,
            'reportTitle' => 'Disciplinary Cases Report',
            'periodLabel' => $filters->periodLabel(),
            'meta' => ['Total Cases' => $rows->count()],
            'rows' => $rows,
            'filters' => $filters,
        ];

        return ['disciplinary.reports.cases', $data];
    }

    // ---- Escalation trail (per case) --------------------------------

    public function escalationTrailPreview(Request $request, Business $business, Warning $warning)
    {
        return $this->previewFor($this->escalationTrailViewData($business, $warning));
    }

    public function escalationTrailDownload(Request $request, Business $business, Warning $warning)
    {
        return $this->downloadFor($this->escalationTrailViewData($business, $warning), 'disciplinary-escalation-trail-' . $warning->id);
    }

    private function escalationTrailViewData(Business $business, Warning $warning): array
    {
        if ((int) $warning->business_id !== (int) $business->id) {
            abort(404);
        }

        // Walk back to the root of the chain, then forward through every
        // escalation from there - the trail is always printed oldest-first.
        $root = $warning;
        $seen = [];
        while ($root->previous_case_id && !in_array($root->previous_case_id, $seen, true)) {
            $seen[] = $root->previous_case_id;
            $root = $root->previousCase()->with(['employee.user', 'stageType', 'issuedBy'])->first() ?? $root;
            if (!$root) {
                break;
            }
        }

        $chain = collect();
        $current = $root;
        $seen = [];
        while ($current && !in_array($current->id, $seen, true)) {
            $chain->push($current);
            $seen[] = $current->id;
            $current = $current->nextCases()->with(['employee.user', 'stageType', 'issuedBy'])->first();
        }

        $data = [
            'business' => $business,
            'reportTitle' => 'Disciplinary Escalation Trail',
            'periodLabel' => optional($chain->first())->employee ? optional($chain->first()->employee->user)->name : null,
            'meta' => ['Case Levels' => $chain->count()],
            'chain' => $chain,
            'filters' => new ReportFilters(),
        ];

        return ['disciplinary.reports.escalation-trail', $data];
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
