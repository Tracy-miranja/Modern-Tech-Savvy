<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeCareerEvent extends Model
{
    public const TYPES = ['promotion', 'salary_increment'];

    protected $fillable = [
        'business_id',
        'employee_id',
        'event_type',
        'effective_date',
        'old_job_category_id',
        'new_job_category_id',
        'old_department_id',
        'new_department_id',
        'old_salary',
        'new_salary',
        'reason',
        'notes',
        'status',
        'issued_by_id',
        'applied_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'old_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function oldJobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'old_job_category_id');
    }

    public function newJobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'new_job_category_id');
    }

    public function oldDepartment()
    {
        return $this->belongsTo(Department::class, 'old_department_id');
    }

    public function newDepartment()
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    /**
     * Pushes this event's new_* values onto the employee's live records
     * (EmploymentDetail for job_category/department, EmployeePaymentDetail
     * for salary) and marks the event applied. Idempotent - a second call
     * on an already-applied event is a no-op, so the daily scheduled
     * command can't double-apply if it somehow runs twice on the same day.
     */
    public function apply(): void
    {
        if ($this->status === 'applied') {
            return;
        }

        $employee = $this->employee;

        if ($this->new_job_category_id || $this->new_department_id) {
            $employmentDetail = $employee->employmentDetails;
            if ($employmentDetail) {
                $employmentDetail->update(array_filter([
                    'job_category_id' => $this->new_job_category_id,
                    'department_id' => $this->new_department_id,
                ], fn ($v) => $v !== null));
            }

            // department_id is ALSO a real column directly on employees
            // (unlike job_category_id, which only ever exists on
            // EmploymentDetail) and is what every department-scoped
            // query/filter in the app actually reads - Employee's own
            // getDepartmentIdAttribute() accessor only falls back to
            // EmploymentDetail when this raw column is null, so updating
            // EmploymentDetail alone left the employee showing their old
            // department everywhere except the one accessor path that
            // happened to already be null.
            if ($this->new_department_id) {
                $employee->update(['department_id' => $this->new_department_id]);
            }
        }

        if ($this->new_salary !== null) {
            $employee->paymentDetails()->updateOrCreate([], ['basic_salary' => $this->new_salary]);
        }

        $this->update(['status' => 'applied', 'applied_at' => now()]);
    }
}
