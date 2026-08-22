<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingPayment extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'payroll_id',
        'employee_payroll_id', 'payment_type', 'residency',
        'gross_amount', 'wht_rate', 'wht_amount', 'net_amount',
        'shif_company_cost', 'nssf_company_cost', 'total_company_cost',
        'payment_date', 'is_remitted', 'remittance_date',
        'certificate_no', 'currency',
    ];

    protected $casts = [
        'payment_date'     => 'date',
        'remittance_date'  => 'date',
        'is_remitted'      => 'boolean',
        'gross_amount'     => 'decimal:2',
        'wht_amount'       => 'decimal:2',
        'net_amount'       => 'decimal:2',
    ];

    public function employee()  { return $this->belongsTo(Employee::class); }
    public function business()  { return $this->belongsTo(Business::class); }
    public function payroll()   { return $this->belongsTo(Payroll::class); }
}
