<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ReportPdfService
{

    public function previewHtml(string $view, array $data): string
    {
        return View::make($view, $data)->render();
    }

    public function pdf(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait')
    {
        return Pdf::loadView($view, $data)->setPaper($paper, $orientation);
    }

    public function download(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->pdf($view, $data, $paper, $orientation)->download($filename);
    }
}
