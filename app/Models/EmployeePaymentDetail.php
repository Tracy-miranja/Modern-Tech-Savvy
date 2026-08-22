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
        'bank_branch_code',
        'is_consultant',
    'wht_payment_type',
    'wht_residency',
    'wht_pin',
    'consultant_shif_covered',
    'consultant_shif_basis',
    'consultant_shif_fixed_amount',
    'consultant_nssf_covered',
    'consultant_nssf_basis',
    'consultant_nssf_fixed_amount',
    ];

     protected $casts = [
        'is_consultant'                => 'boolean',
    'consultant_shif_covered'      => 'boolean',
    'consultant_nssf_covered'      => 'boolean',
    'consultant_shif_fixed_amount' => 'decimal:2',
    'consultant_nssf_fixed_amount' => 'decimal:2',
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
