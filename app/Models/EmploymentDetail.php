<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class EmploymentDetail extends Model
{
    use LogsActivity;
    protected $fillable = [
        'employee_id',
        'department_id',
        'job_category_id',
        'shift_id',
        'employment_date',
        'probation_end_date',
        'contract_end_date',
        'retirement_date',
        'employment_term',
        'job_description',
        'license_reg_number',
        'license_expiry_date',
        'second_probation_end_date',

    ];

    protected $casts = [
        'employment_date' => 'date',
        'probation_end_date' => 'date',
        'retirement_date' => 'date',
        'contract_end_date' => 'date',
        'employment_term' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'license_expiry_date' => 'date',
        'second_probation_end_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
