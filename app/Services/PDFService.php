<?php
namespace App\Services;

use TCPDF;
use DateTime;
use App\Models\Payroll;
use App\Models\Business;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeePayroll;
use Illuminate\Support\Facades\Log;

class PDFService
{
    protected $pdf;
    protected $employee;

    public function __construct(Employee $employee = null)
    {
        $this->pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor('Your Company');
        $this->pdf->setPrintFooter(false);
        $this->pdf->setPrintHeader(false);
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->pdf->SetAutoPageBreak(TRUE, 10);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->pdf->setFontSubsetting(true);

        $this->employee = $employee;
    }

    public function generatePdf(Business $business, string $title, array $headers, array $rows, string $filename = "report.pdf")
    {
        $this->pdf->AddPage('L');
        $logoPath = $business->getImageUrl();

        if ($logoPath && file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 10, 5, 40);
        }

        $this->pdf->SetFont('', 'B', 16);
        $this->pdf->Cell(0, 15, $title, 0, 1, 'C');
        $this->pdf->Ln(5);
        $this->pdf->SetFont('', 'B', 10);

        $colWidths = [];
        foreach ($headers as $index => $header) {
            $maxWidth = $this->pdf->GetStringWidth($header) + 8;
            foreach ($rows as $row) {
                $cellWidth = $this->pdf->GetStringWidth($row[$index] ?? '') + 8;
                if ($cellWidth > $maxWidth) {
                    $maxWidth = $cellWidth;
                }
            }
            $colWidths[$index] = $maxWidth;
        }

        $maxPageWidth = 270;
        $totalWidth = array_sum($colWidths);
        if ($totalWidth > $maxPageWidth) {
            $scaleFactor = $maxPageWidth / $totalWidth;
            foreach ($colWidths as $index => $width) {
                $colWidths[$index] = $width * $scaleFactor;
            }
        }

        $this->pdf->SetFillColor(200, 200, 200);
        $this->pdf->SetTextColor(0, 0, 0);

        foreach ($headers as $index => $header) {
            $this->pdf->Cell($colWidths[$index], 10, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();

        $this->pdf->SetFont('', '', 9);
        $rowCount = 0;
        foreach ($rows as $row) {
            $rowCount++;
            $fill = ($rowCount % 2 == 0) ? [240, 240, 240] : [255, 255, 255];
            $this->pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
            foreach ($row as $index => $cell) {
                $alignment = is_numeric($cell) ? 'R' : 'L';
                $this->pdf->Cell($colWidths[$index], 8, $cell ?: '-', 1, 0, $alignment, true);
            }
            $this->pdf->Ln();
        }

        return $this->pdf->Output($filename, 'I');
    }

    public function generateBusinessPayrollPdf(Business $business, int $month, int $year, string $filename = "payroll_report.pdf")
    {

        $this->pdf->SetTitle($business->company_name . ' - Payroll');
        $this->pdf->AddPage('L');

        $this->pdf->SetFont('', 'B', 16);
        $this->pdf->Cell(0, 0, $business->company_name, 0, 1);

        $this->pdf->Ln(5);
        $this->pdf->SetFont('', 'B', 12);
        $dateObj = DateTime::createFromFormat('!m', $month);
        $monthName = $dateObj->format('F');

        $this->pdf->Cell(100, 0, 'PAYROLL REPORT');
        $this->pdf->Cell(100, 0, "YEAR $year");
        $this->pdf->Cell(100, 0, "MONTH {$monthName}", 0, 1);

        $employees = $business->employees;

        $payroll = Payroll::where('payrun_year', $year)
            ->where('payrun_month', $month)
            ->where('business_id', $business->id)
            ->first();

        if (!$payroll) {
            $this->pdf->Ln(10);
            $this->pdf->SetFont('', 'B', 12);
            $this->pdf->Cell(0, 0, 'No payroll data found for the selected period.', 0, 1, 'C');
            return $this->pdf->Output($filename, 'I');
        }

        $employeePayrollData = [];
        foreach ($employees as $employee) {
            $employeePayrollData[$employee->id] = $employee->payrolls;
        }

        $pdfData = [];
        foreach ($employees as $employee) {
            $employeePayroll = $employeePayrollData[$employee->id];

            Log::debug($employeePayroll);

            if (!$employeePayroll) {
                continue;
            }

            $deductions = json_decode($employeePayroll->deductions, true);

            $insuranceRelief = $deductions['insurance_relief'] ?? 0;
            $pension = $deductions['pension'] ?? 0;
            $standingOrder = $deductions['standing_order'] ?? 0;
            $miscDeductions = $deductions['misc'] ?? 0;
            $pensionRefund = $deductions['pension_refund'] ?? 0;
            $totalDeductionsAmount = $employeePayroll->gross_pay - $employeePayroll->net_pay;
            $rounded = floor($employeePayroll->gross_pay / 20);

            $nameParts = explode(' ', trim($employee->user->name));
            $firstName = $nameParts[0] ?? '';
            $middleName = $nameParts[1] ?? '';
            $lastName = $nameParts[count($nameParts) - 1] ?? '';
            $initials = strtoupper(substr($firstName, 0, 1)) . strtoupper(substr($middleName, 0, 1));

            $pdfData[] = [
                'month' => $dateObj->format('M'),
                'basic_salary' => number_format($employeePayroll->basic_salary, 2),
                'housing_allowance' => number_format($employeePayroll->housing_allowance, 2),
                'overtime' => number_format($employeePayroll->overtime, 2),
                'gross_pay' => number_format($employeePayroll->gross_pay, 2),
                'rounded' => number_format($rounded, 2),
                'housing_levy' => number_format($employeePayroll->housing_levy, 2),
                'pay_after_tax' => number_format($employeePayroll->pay_after_tax, 2),
                'paye' => number_format($employeePayroll->paye, 2),
                'personal_relief' => '(' . number_format($employeePayroll->personal_relief, 2) . ')',
                'insurance_relief' => number_format($insuranceRelief, 2),
                'nssf' => number_format($employeePayroll->nssf, 2),
                'nhif' => number_format($employeePayroll->nhif, 2),
                'pension' => number_format($pension, 2),
                'standing_order' => number_format($standingOrder, 2),
                'advance_recovery' => number_format($employeePayroll->advance_recovery, 2),
                'loan_repayment' => number_format($employeePayroll->loan_repayment, 2),
                'misc' => number_format($miscDeductions, 2),
                'pension_refund' => number_format($pensionRefund, 2),
                'total_deductions' => number_format($totalDeductionsAmount, 2),
                'net_pay' => number_format($employeePayroll->net_pay, 2),
                'initials' => $initials,
                'last_name' => $lastName,
            ];
        }

        $rows = [
            'MONTH',
            'DETAILS',
            'EARNINGS',
            'BASIC',
            'ALLOWANCES',
            'OVERTIME',
            '',
            'a. GROSS PAY',
            'T/CALCULATION',
            'b. Round to pounds',
            'c. Housing Levy',
            '15% Sub. Hse.',
            'd. C/Pay (b+c)',
            'e.Tax Charged',
            'f. Monthly Relief',
            'Insurance Relief',
            '',
            'DEDUCTIONS',
            'h. Tax Deducted',
            'N.S.S.F',
            'N.H.I.F',
            'PENSION PLAN',
            '',
            'STANDING ORDER',
            'ADVANCES',
            'LOAN REP/HELB',
            'MISC DED/REF',
            'PENSION REFUND',
            '',
            'T/DEDUCTIONS',
            'NET PAY',
            'NAME'
        ];

        $this->pdf->SetFont('', '', 10);
        $i = 0;
        $netSum = $total_deduction = 0;

        foreach ($rows as $row) {

            $fill = ($i == 0 || $i == 7 || $i == 13 || $i == 29);

            if ($i == 31 || $i == 30 || $i == 0 || $i == 2 || $i == 7 || $i == 8 || $i == 17 || $i == 29) {
                $this->pdf->SetFont('', 'B');
            } else {
                $this->pdf->SetFont('', '');
            }

            if ($i == 6) {
                $this->pdf->Cell(35, 0, $row, 1, 0, 'R');
            } else {
                $this->pdf->Cell(35, 0, $row, 1, 0, '', $fill);
            }

            $w = 20;
            $totalBasic = $totalAllowance = $totalOvertime = $totalGross = 0;
            $totalTax = $totalNssf = $totalNhif = $totalPension = $totalDeductions = $totalNetPay = 0;

            foreach ($pdfData as $employeeData) {
                $this->pdf->SetFont('', '', 10);

                if ($i == 0) {
                    $this->pdf->Cell($w, 0, $employeeData['month'], 1, 0, '', $fill);
                } elseif ($i == 1) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 2) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 3) {
                    $this->pdf->Cell($w, 0, $employeeData['basic_salary'], 1, 0, 'R');
                    $totalBasic += (float) str_replace(',', '', $employeeData['basic_salary']);
                } elseif ($i == 4) {
                    $this->pdf->Cell($w, 0, $employeeData['housing_allowance'], 1, 0, 'R');
                    $totalAllowance += (float) str_replace(',', '', $employeeData['housing_allowance']);
                } elseif ($i == 5) {
                    $this->pdf->Cell($w, 0, $employeeData['overtime'], 1, 0, 'R');
                    $totalOvertime += (float) str_replace(',', '', $employeeData['overtime']);
                } elseif ($i == 6) {
                    $this->pdf->Cell($w, 0, '', 1, 0, 'R');
                } elseif ($i == 7) {
                    $this->pdf->Cell($w, 0, $employeeData['gross_pay'], 1, 0, 'R', $fill);
                    $totalGross += (float) str_replace(',', '', $employeeData['gross_pay']);
                } elseif ($i == 8) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 9) {
                    $this->pdf->Cell($w, 0, $employeeData['rounded'], 1, 0, 'R');
                } elseif ($i == 10) {
                    $this->pdf->Cell($w, 0, $employeeData['housing_levy'], 1, 0, 'R');
                } elseif ($i == 11) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 12) {
                    $this->pdf->Cell($w, 0, $employeeData['pay_after_tax'], 1, 0, 'R');
                } elseif ($i == 13) {
                    $this->pdf->Cell($w, 0, $employeeData['paye'], 1, 0, 'R', $fill);
                    $totalTax += (float) str_replace(',', '', $employeeData['paye']);
                } elseif ($i == 14) {
                    $this->pdf->Cell($w, 0, $employeeData['personal_relief'], 1, 0, 'R');
                } elseif ($i == 15) {
                    $this->pdf->Cell($w, 0, $employeeData['insurance_relief'], 1, 0, 'R');
                } elseif ($i == 16) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 17) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 18) {
                    $this->pdf->Cell($w, 0, $employeeData['paye'], 1, 0, 'R');
                } elseif ($i == 19) {
                    $this->pdf->Cell($w, 0, $employeeData['nssf'], 1, 0, 'R');
                    $totalNssf += (float) str_replace(',', '', $employeeData['nssf']);
                } elseif ($i == 20) {
                    $this->pdf->Cell($w, 0, $employeeData['nhif'], 1, 0, 'R');
                    $totalNhif += (float) str_replace(',', '', $employeeData['nhif']);
                } elseif ($i == 21) {
                    $this->pdf->Cell($w, 0, $employeeData['pension'], 1, 0, 'R');
                    $totalPension += (float) str_replace(',', '', $employeeData['pension']);
                } elseif ($i == 22) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 23) {
                    $this->pdf->Cell($w, 0, $employeeData['standing_order'], 1, 0, 'R');
                } elseif ($i == 24) {
                    $this->pdf->Cell($w, 0, $employeeData['advance_recovery'], 1, 0, 'R');
                } elseif ($i == 25) {
                    $this->pdf->Cell($w, 0, $employeeData['loan_repayment'], 1, 0, 'R');
                } elseif ($i == 26) {
                    $this->pdf->Cell($w, 0, $employeeData['misc'], 1, 0, 'R');
                } elseif ($i == 27) {
                    $this->pdf->Cell($w, 0, $employeeData['pension_refund'], 1, 0, 'R');
                } elseif ($i == 28) {
                    $this->pdf->Cell($w, 0, '', 1);
                } elseif ($i == 29) {
                    $this->pdf->Cell($w, 0, $employeeData['total_deductions'], 1, 0, 'R', $fill);
                    $totalDeductions += (float) str_replace(',', '', $employeeData['total_deductions']);
                } elseif ($i == 30) {
                    $this->pdf->Cell($w, 0, $employeeData['net_pay'], 1, 0, 'R');
                    $totalNetPay += (float) str_replace(',', '', $employeeData['net_pay']);
                } elseif ($i == 31) {
                    $this->pdf->SetFont('', 'B', 8);
                    $this->pdf->Cell($w, 4.7, $employeeData['initials'] . ' ' . $employeeData['last_name'], 1, 0, 'R');
                    $this->pdf->SetFont('', '', 10);
                }
            }

            $this->pdf->SetFont('', 'B', 10);

            if (in_array($i, [1, 2, 5, 6, 8, 9, 10, 11, 12, 15, 16, 17, 22, 23, 24, 25, 26, 27, 28])) {
                $this->pdf->Cell($w, 0, '', 1);
            } elseif ($i == 0) {
                $this->pdf->Cell($w, 0, $dateObj->format('M'), 1, 0, '', $fill);
            } elseif ($i == 3) {
                $this->pdf->Cell($w, 0, number_format($totalBasic, 2), 1, 0, 'R');
            } elseif ($i == 4) {
                $this->pdf->Cell($w, 0, number_format($totalAllowance, 2), 1, 0, 'R');
            } elseif ($i == 7) {
                $this->pdf->Cell($w, 0, number_format($totalGross, 2), 1, 0, 'R', $fill);
            } elseif ($i == 13) {
                $this->pdf->Cell($w, 0, number_format($totalTax, 2), 1, 0, 'R', $fill);
            } elseif ($i == 14) {
                $totalRelief = $employees->count() * 2400;
                $this->pdf->Cell($w, 0, '(' . number_format($totalRelief, 2) . ')', 1, 0, 'R');
            } elseif ($i == 18) {
                $this->pdf->Cell($w, 0, number_format($totalTax, 2), 1, 0, 'R');
            } elseif ($i == 19) {
                $this->pdf->Cell($w, 0, number_format($totalNssf, 2), 1, 0, 'R');
            } elseif ($i == 20) {
                $this->pdf->Cell($w, 0, number_format($totalNhif, 2), 1, 0, 'R');
            } elseif ($i == 21) {
                $this->pdf->Cell($w, 0, number_format($totalPension, 2), 1, 0, 'R');
            } elseif ($i == 29) {
                $this->pdf->Cell($w, 0, number_format($totalDeductions, 2), 1, 0, 'R', $fill);
            } elseif ($i == 30) {
                $this->pdf->Cell($w, 0, number_format($totalNetPay, 2), 1, 0, 'R');
            } elseif ($i == 31) {
                $this->pdf->Cell($w, 0, "TOTAL", 1, 0, 'R');
            }

            $this->pdf->Ln();
            $i++;
        }

        return $this->pdf->Output($filename, 'I');
    }

    public function setEmployeeModel(Employee $employee)
    {
        $this->employee = $employee;
        return $this;
    }
}