<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Business;
use App\Models\Location;
use App\Services\PDFService;
use Illuminate\Http\Request;
use App\Models\EmployeePayroll;

class DownloadController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function downloads(Request $request)
    {
        $fileType = $request->file_type;

        switch ($fileType) {
            case 'bankAdviceTemplate':
                $this->bankAdviceTemplate($request);
                break;
            case 'payrollReport':
                $this->payrollReport($request);
                break;
            default:

                break;
        }
    }
    private function bankAdviceTemplate(Request $request)
    {

        $payroll_id = $request->payroll_id;
        $payroll = Payroll::findOrFail($payroll_id);

        $payrun_year = $payroll->payrun_year;
        $payrun_month = $payroll->payrun_month;

        $business = Business::findBySlug(session('active_business_slug'));
        if($request->has('location')) {
            $business = $request->has('location') ? Location::findBySlug($request->location) : null;
        }
        $employees = $business->employees;

        $headers = [
            "EMPLOYEE NAME",
            "ACCOUNT NUMBER",
            "BANK NAME/BRANCH",
            "BRANCH CODE",
            "AMOUNT (KSH)",
            "REFERENCE (COMPANY NAME)"
        ];

        $rows = [];
        $totalAmount = 0;
        foreach ($employees as $employee) {
            $employeePayroll = EmployeePayroll::getEmployeePayrollByMonthYear($employee->id, $payrun_year, $payrun_month);
            $salary = $employeePayroll->basic_salary;
            if ($salary) {
                $totalAmount += $salary;
                $rows[] = [
                    "{$employee->user->name}",
                    $employee->paymentDetails->account_number,
                    "{$employee->paymentDetails->bank_name} / {$employee->paymentDetails->bank_branch}",
                    $employee->paymentDetails->bank_branch_code,
                    number_format($salary, 2),
                    $business->company_name
                ];
            }
        }

        if (empty($rows)) {
            return back()->with('error', 'No payroll data available.');
        }

        $title = "{$business->company_name} - Bank Advice Slip";
        $filename = "bank_advice_{$request->month}_{$request->year}.pdf";

        return $this->pdfService->generatePdf($business,$title, $headers, $rows, $filename);

    }

    public function payrollReport(Request $request)
    {
        $payroll_id = $request->payroll_id;
        $payroll = Payroll::findOrFail($payroll_id);

        $payrun_year = $payroll->payrun_year;
        $payrun_month = $payroll->payrun_month;

        $business = Business::findBySlug(session('active_business_slug'));

        if ($request->has('location')) {
            $business = Location::findBySlug($request->location);
        }

        if (!$business) {
            return back()->with('error', 'Business or location not found.');
        }

        $filename = "payroll_report_{$payrun_month}_{$payrun_year}.pdf";

        return $this->pdfService->generateBusinessPayrollPdf(
            $business,
            $payrun_month,
            $payrun_year,
            $filename
        );
    }
}
