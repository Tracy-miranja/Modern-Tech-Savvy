<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeCareerEvent;
use Carbon\Carbon;

class EmployeeCareerEventService
{

    public function record(Employee $employee, array $data): EmployeeCareerEvent
    {
        $employmentDetail = $employee->employmentDetails;
        $paymentDetail = $employee->paymentDetails;

        $event = EmployeeCareerEvent::create([
            'business_id' => $employee->business_id,
            'employee_id' => $employee->id,
            'event_type' => $data['event_type'],
            'effective_date' => $data['effective_date'],
            'old_job_category_id' => $employmentDetail?->job_category_id,
            'new_job_category_id' => $data['new_job_category_id'] ?? null,
            'old_department_id' => $employmentDetail?->department_id,
            'new_department_id' => $data['new_department_id'] ?? null,
            'old_salary' => $paymentDetail?->basic_salary,
            'new_salary' => $data['new_salary'] ?? null,
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'issued_by_id' => $data['issued_by_id'],
        ]);

        if (Carbon::parse($event->effective_date)->lessThanOrEqualTo(now()->startOfDay())) {
            $event->apply();
        }

        return $event->fresh();
    }

    public function applyDuePendingEvents(): int
    {
        $due = EmployeeCareerEvent::where('status', 'pending')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->get();

        foreach ($due as $event) {
            $event->apply();
        }

        return $due->count();
    }
}
