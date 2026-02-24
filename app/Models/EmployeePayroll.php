<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    protected $fillable = [
        'payroll_id',
        'employee_id',
        'employee_payment_detail_id',
        'basic_salary',
        'housing_allowance',
        'gross_pay',
        'paye',
        'paye_before_reliefs',
        'shif',
        'nssf',
        'pension',
        'housing_levy',
        'helb',
        'taxable_income',
        'reliefs',
        'personal_relief',
        'insurance_relief',
        'pay_after_tax',
        'loan_repayment',
        'advance_recovery',
        'deductions_after_tax',
        'net_pay',
        'deductions',
        'overtime',
        'allowances',
        'bank_name',
        'account_number',
        'attendance_present',
        'attendance_absent',
        'days_in_month',
        'pwd_exemption_applied',
        'pwd_exemption_amount',
    ];

    protected $casts = [
        'allowances' => 'json',
        'deductions' => 'json',
        'overtime' => 'json',
        'reliefs' => 'json',
        'basic_salary' => 'float',
        'housing_allowance' => 'float',
        'gross_pay' => 'float',
        'paye' => 'float',
        'paye_before_reliefs' => 'float',
        'shif' => 'float',
        'nssf' => 'float',
        'pension' => 'float',
        'housing_levy' => 'float',
        'helb' => 'float',
        'taxable_income' => 'float',
        'personal_relief' => 'float',
        'insurance_relief' => 'float',
        'pay_after_tax' => 'float',
        'loan_repayment' => 'float',
        'advance_recovery' => 'float',
        'deductions_after_tax' => 'float',
        'net_pay' => 'float',
        'pwd_exemption_applied' => 'boolean',
        'pwd_exemption_amount'  => 'float',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function paymentDetail()
    {
        return $this->belongsTo(EmployeePaymentDetail::class, 'employee_payment_detail_id');
    }

    public static function getEmployeePayrollByMonthYear($employeeId, $year = null, $month = null)
    {
        return self::where('employee_id', $employeeId)
            ->when($year && $month, function ($query) use ($year, $month) {
                $query->whereHas('payroll', function ($subQuery) use ($year, $month) {
                    $subQuery->where('payrun_year', $year)->where('payrun_month', $month);
                });
            })
            ->latest('created_at')
            ->first();
    }
}
