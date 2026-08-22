<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayrollDetail extends Model
{
    protected $fillable = [
        'employee_id',
        'business_id',
        'has_insurance',
        'insurance_premium',
        'has_mortgage',
        'mortgage_interest',
        'has_hosp',
        'hosp_deposit',
        'has_helb',
        'has_disability_exemption',
        'pwd_certificate_no',
        'pwd_ncpwd_membership_no',
        'pwd_exemption_limit',
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
        'has_disability_exemption' => 'boolean',
        'pwd_exemption_limit'      => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
