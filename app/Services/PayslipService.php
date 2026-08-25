<?php

namespace App\Services;

use App\Models\EmployeePayroll;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipService
{
    public function generatePayslipPdf($payslip_id)
    {
        $payslip = EmployeePayroll::find($payslip_id);
        $pdf = Pdf::loadView('payroll._payslip_details', compact('payslip'))->setPaper([0, 0, 80, 297], 'portrait');
        return $pdf->stream('payslip_' . $payslip->id . '.pdf');
    }
}
