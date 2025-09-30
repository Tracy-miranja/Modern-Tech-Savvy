<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BankAdviceExport implements FromArray, WithHeadings
{
    protected $payroll;

    public function __construct($payroll)
    {
        $this->payroll = $payroll;
    }

    public function array(): array
    {
        $reference = $this->payroll->payrun_month . '/' . $this->payroll->payrun_year;

        return $this->payroll->employeePayrolls->map(function ($ep) use ($reference) {
            return [
                // Code (blank)
                '',

                // Amount (Net Pay)
                number_format($ep->net_pay ?? 0, 2),

                // Debit details (blank, to be filled manually)
                '',
                '',
                '',

                // Employee details
                $ep->employee->paymentDetails->account_number ?? 'N/A',
                $ep->employee->paymentDetails->bank_name ?? 'N/A',
                $ep->employee->paymentDetails->bank_branch ?? 'N/A',
                $ep->employee->user->name ?? 'N/A',

                // Reference (Payroll Period)
                $reference,

                // Payment Mode
                '',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Amount',
            'Debit Account',
            'Debit Bank',
            'Debit Branch',
            'Employee Account',
            'Employee Bank',
            'Employee Branch',
            'Employee Name',
            'Reference',
            'status',
        ];
    }
}
