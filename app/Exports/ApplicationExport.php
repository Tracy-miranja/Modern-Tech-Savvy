<?php

namespace App\Exports;

use App\Models\Application;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicationExport implements FromCollection, WithHeadings
{
    protected $applications;

    public function __construct($applications)
    {
        $this->applications = $applications;
    }

    public function collection()
    {
        return $this->applications->map(function ($application) {
            return [
                'Applicant Name' => $application->applicant->user
                    ? $application->applicant->user->name
                    : 'Applicant #' . $application->applicant->id,
                'Position' => $application->jobPost->title,
                'Status' => ucfirst($application->stage),
                'Applied On' => $application->created_at->format('M d, Y'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Applicant Name', 'Position', 'Status', 'Applied On'];
    }
}
