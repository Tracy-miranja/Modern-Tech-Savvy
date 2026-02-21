<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeePaymentDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'basic_salary',
         'hourly_rate',
        'payment_type',
        'currency',
        'payment_mode',
        'account_name',
        'account_number',
        'bank_name',
        'bank_code',
        'bank_branch',
        'bank_branch_code'
    ];

     protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    /**
     * Check if the employee is paid hourly
     */
    public function isHourlyPaid(): bool
    {
        return $this->payment_type === 'hourly';
    }

    /**
     * Check if the employee is paid salary
     */
    public function isSalaryPaid(): bool
    {
        return $this->payment_type === 'salary';
    }
}
