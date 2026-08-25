<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveysExport implements FromCollection, WithHeadings, WithMapping
{
    protected $leads;

    public function __construct($leads)
    {
        $this->leads = $leads;
    }

    public function collection()
    {
        return $this->leads;
    }

    public function headings(): array
    {
        $headings = ['ID', 'Name', 'Email', 'Country', 'Feedback', 'Status', 'Submitted At'];
        if ($this->leads->isNotEmpty()) {
            $firstLead = $this->leads->first();
            foreach ($firstLead->survey_responses ?? [] as $field) {
                $headings[] = $field['label'];
            }
        }
        return $headings;
    }

    public function map($lead): array
    {
        $row = [
            $lead->id,
            $lead->name,
            $lead->email,
            $lead->country,
            $lead->feedback,
            $lead->status,
            $lead->created_at->toDateTimeString(),
        ];

        foreach ($lead->survey_responses ?? [] as $field) {
            $row[] = $field['value'] ?? 'N/A';
        }
        return $row;
    }
}
