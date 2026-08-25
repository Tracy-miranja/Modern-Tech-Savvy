<div class="payslip-container" style="margin: 0 auto; border: 1px solid #000; font-family: Arial, sans-serif; max-width: 300px; font-size: 12px;">
    <div class="payslip-header text-center" style="border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 10px;">
        <h4 style="margin: 0; font-size: 14px; ">{{ $payslip->employee->business->company_name }}</h4>
        <h6 style="color: red; margin: 5px 0; font-size: 12px;">Pay-Slip ({{ \Carbon\Carbon::parse($payslip->end_date)->format('F Y') }})</h6>
        <small style="font-size: 10px;">Ref: {{ date('YmdHis') }}</small>
    </div>

    <div class="section">
        <h6 style="margin-bottom: 5px; font-size: 12px;">Employee Information</h6>
        <p style="margin: 0; font-size: 10px;"><strong>Employee:</strong> {{ $payslip->employee->employee_code }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>Name:</strong> {{ $payslip->employee->user->name }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>Location:</strong> {{ $payslip->employee->location ? $payslip->employee->location->name : $payslip->employee->business->company_name }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>Designation:</strong> {{ $payslip->employee->designation }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>ID-Num:</strong> {{ $payslip->employee->national_id }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>Tax PIN:</strong> {{ $payslip->employee->tax_no }}</p>
        <p style="margin: 0; font-size: 10px;"><strong>Dept.:</strong> {{ $payslip->employee->department->name }}</p>
    </div>

    <div class="section">
        <h6 style="margin-bottom: 5px; font-size: 12px;">Earnings</h6>
        <p style="margin: 0; font-size: 10px;">Basic Salary: {{ number_format($payslip->basic_salary, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">House Allowance: {{ number_format($payslip->housing_allowance, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">Commuter Allowance: {{ number_format($payslip->commuter_allowance, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">Other Benefits: {{ number_format($payslip->other_benefits, 2) }}</p>
    </div>

    <div class="section">
        <h6 style="margin-bottom: 5px; font-size: 12px;">Deductions</h6>
        <p style="margin: 0; font-size: 10px;">PAYE: -{{ number_format($payslip->paye, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">NHIF Contribution: -{{ number_format($payslip->nhif, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">NSSF Contribution: -{{ number_format($payslip->nssf, 2) }}</p>

        @php
        $totalDeductions = $payslip->paye + $payslip->nhif + $payslip->nssf;
@endphp

        @if($payslip->deductions)
            @foreach($payslip->deductions as $deduction)
                <p style="margin: 0; font-size: 10px;">{{ $deduction['name'] }}: -{{ number_format($deduction['amount'], 2) }}</p>
                @php $totalDeductions += $deduction['amount'];@endphp
            @endforeach
        @endif

        <p style="margin: 0; font-size: 10px; font-weight: bold;">Total Deductions: -{{ number_format($totalDeductions, 2) }}</p>
    </div>

    <div class="section">
        <h6 style="margin-bottom: 5px; font-size: 12px;">Tax & Reliefs</h6>
        <p style="margin: 0; font-size: 10px;">Taxable Income: {{ number_format($payslip->taxable_income, 2) }}</p>
        <p style="margin: 0; font-size: 10px;">Tax Relief: {{ number_format($payslip->personal_relief, 2) }}</p>
    </div>

    <div class="section">
        <h5 class="text-center" style="color: green; margin-top: 5px; font-size: 14px;">
            NET PAY: KES {{ number_format($payslip->net_pay, 2) }}
        </h5>
    </div>

    <button onclick="printPayslip()" id="{{ $payslip->id }}" style="display: block; margin: 10px auto; padding: 5px 10px; font-size: 12px;"> <i class="fa-solid fa-print"></i> Print Payslip</button>

    <p class="text-muted text-center" style="font-size: 10px;">Report anomalies to your HR Department</p>
</div>

<style>

    .payslip-container {
            margin: 0.5in auto; /* Top/bottom margin, and center horizontally */
            border: 1px solid #000;
            padding: 0.25in; /* Adjust padding as needed */
            box-sizing: border-box; /* Include padding and border in width calculation */
            font-size: 10pt;
        }

        .payslip-container * {
            font-size: 10pt;
        }

        .payslip-container .payslip-header h4 {
            font-size: 12pt;
        }

        .payslip-container .section {
            margin-bottom: 0.2in; /* Adjust as needed */
            border-bottom: 1px solid #ccc;
            padding-bottom: 0.1in; /* Adjust as needed */
        }
        .payslip-container p {
            margin: 0 0 5px 0; /* Add small bottom margin to paragraphs */
        }

        .payslip-container .text-center {
            text-align: center;
        }

        .payslip-container .text-muted {
            font-size: 8pt;
        }

        .payslip-container button {
            display: none;
        }

</style>
