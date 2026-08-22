<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

/**
 * One shared render path for every report PDF. Both preview() and
 * download() render the SAME Blade view with the SAME data - the preview
 * shown in the report modal and the downloaded PDF can never drift apart,
 * because they're literally the same template.
 */
class ReportPdfService
{
    /**
     * Plain HTML string for the in-browser preview modal.
     */
    public function previewHtml(string $view, array $data): string
    {
        return View::make($view, $data)->render();
    }

    /**
     * A DomPDF instance ready for ->download()/->stream(), built from the
     * exact same view/data as previewHtml().
     */
    public function pdf(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait')
    {
        return Pdf::loadView($view, $data)->setPaper($paper, $orientation);
    }

    public function download(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->pdf($view, $data, $paper, $orientation)->download($filename);
    }
}
