<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Business;
use App\Models\Department;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;

class AssetReportController extends Controller
{

    public function index(Business $business)
    {
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('assets.reports', compact('business', 'departments'));
    }

    public function registerPreview(Request $request, Business $business)
    {
        return $this->previewFor($this->registerViewData($request, $business));
    }

    public function registerDownload(Request $request, Business $business)
    {
        return $this->downloadFor($this->registerViewData($request, $business), 'asset-register');
    }

    private function registerViewData(Request $request, Business $business): array
    {
        $query = Asset::where('business_id', $business->id)
            ->with(['currentAssignment.employee.user:id,name', 'currentAssignment.employee.department:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('department_id')) {
            $departmentId = (int) $request->input('department_id');
            $query->whereHas('currentAssignment.employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        $rows = $query->orderBy('name')->get();

        $data = [
            'business' => $business,
            'reportTitle' => 'Asset Register',
            'periodLabel' => 'As at ' . now()->format('d M Y'),
            'meta' => ['Total Assets' => $rows->count()],
            'rows' => $rows,
        ];

        return ['assets.reports.register', $data];
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
