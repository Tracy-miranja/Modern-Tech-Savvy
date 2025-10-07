<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Payslip;
use App\Models\Business;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\EmployeePayroll;
use App\Models\Location;
use App\Http\Responses\RequestResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeDashboardController extends Controller
{
    // Dashboard Overview
public function index(Request $request)
{
    $page = "Dashboard";
    $user = Auth::user();
    $employeeId = optional($user->employee)->id;

    if (!$employeeId) {
        return view('employee.index', compact('page'))
            ->with('error', 'Employee record not found.');
    }

    // Total leaves requested
    $leave_count = LeaveRequest::where('employee_id', $employeeId)->count();

 // Pending
$pending_leaves = LeaveRequest::where('employee_id', $employeeId)
    ->whereNull('approved_by')
    ->whereNull('rejection_reason')
    ->count();

// Approved
$approved_leaves = LeaveRequest::where('employee_id', $employeeId)
    ->whereNotNull('approved_by')
    ->whereNull('rejection_reason')
    ->count();

// Rejected
$rejected_leaves = LeaveRequest::where('employee_id', $employeeId)
    ->whereNotNull('rejection_reason')
    ->count();


    $work_days = Attendance::where('employee_id', $employeeId)->count();


    $payslips = EmployeePayroll::where('employee_id', $employeeId)->count();


    $leave_balances = \App\Models\LeaveEntitlement::with('leaveType')
        ->where('employee_id', $employeeId)
        ->get()
        ->map(function ($entitlement) {
            return [
                'leave_type' => $entitlement->leaveType->name ?? 'Unknown',
                'entitled_days' => $entitlement->entitled_days,
                'days_taken' => $entitlement->days_taken,
                'days_remaining' => $entitlement->days_remaining,
            ];
        });

    return view('employee.index', compact(
        'page',
        'leave_count',
        'pending_leaves',
        'work_days',
        'payslips',
        'leave_balances'
    ));
}


    // public function index(Request $request)
    // {
    //     $page = "Dashboard";
    //     $employee = Auth::user();
    //     $leave_count = LeaveRequest::where('employee_id', $employee->id)->count();
    //     $pending_leaves = LeaveRequest::where('employee_id', $employee->id)->where('approved_by', 'pending')->count();
    //     return view('employee.index', compact('page', 'leave_count', 'pending_leaves'));
    // }

    // Leave Requests
    public function requestLeave()
    {
        $page        = "Request Leave";
        $description = "";
        $business    = Business::findBySlug(session('active_business_slug'));
        $leaveTypes  = $business->leaveTypes;

        $employeeId = optional(Auth::user()->employee)->id;
        $leaveRequests = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('business_id', $business->id)
            ->where('employee_id', $employeeId) // ⚠️ employee_id, not user id
            ->latest()
            ->get();

        return view('leave.request-leave', compact('page', 'description', 'leaveTypes', 'leaveRequests'));
    }

    public function viewLeaves()
    {
        $page = "My Leaves";

        $business   = Business::findBySlug(session('active_business_slug'));
        $employeeId = optional(Auth::user()->employee)->id;

        $leaves = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('business_id', $business->id)
            ->where('employee_id', $employeeId) // ⚠️ employee_id, not user id
            ->latest()
            ->get();

        return view('leave.index', compact('page', 'leaves'));
    }

    public function leaveApplication(Request $request, string $business_slug, string $reference_number)
    {
        $business = Business::findBySlug($business_slug);

        $leave = LeaveRequest::with(['employee.user', 'leaveType', 'approvedBy'])
            ->where('reference_number', $reference_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        // Security: employees can only view their own requests here
        $employeeId = optional(Auth::user()->employee)->id;
        if ((int)$leave->employee_id !== (int)$employeeId) {
            abort(403, 'You are not allowed to view this request.');
        }

        $page        = 'Leave - #' . $reference_number;
        $description = '';

        // No call to $leave->statuses(); show.blade builds the timeline from the model fields
        return view('leave.show', ['page' => $page, 'description' => $description, 'leave' => $leave, 'business' => $business]);
    }

    public function clockInOut(Request $request)
    {
        $page = 'Clock In';
        $description = '';
        return view('attendances.employee-clockin', compact('page', 'description'));
    }

    public function updateDetails(Request $request)
    {
        $page = 'Update Your Details';
        $description = '';
        return view('employees.update-details', compact('page', 'description'));
    }

    // Download P9 Form
    public function downloadP9(Request $request)
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            return back()->with('error', 'Employee record not found.');
        }

        $year = $request->query('year', now()->year); // Default to current year

        $payrolls = EmployeePayroll::where('employee_id', $employee->id)
            ->whereHas('payroll', fn($q) => $q->where('payrun_year', $year))
            ->with('payroll')
            ->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', "No payroll data available for $year.");
        }

        $data = $this->prepareP9Data($employee, $payrolls, $year);
        $pdf = Pdf::loadView('payroll.reports.p9_employee', [
            'employee' => $employee,
            'year' => $year,
            'data' => $data,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("P9_{$year}_{$employee->employee_code}.pdf");
    }

    public function viewPayslips(Request $request, $business)
    {
        $business = Business::findBySlug($business);
        if (!$business || session('active_business_slug') !== $business->slug) {
            return redirect()->back()->with('error', 'Business not found or mismatched.');
        }

        $employee = Employee::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->first();
        if (!$employee) {
            return redirect()->back()->with('error', 'Employee record not found.');
        }

      $payslips = EmployeePayroll::where('employee_id', $employee->id)
    ->with(['payroll'])
    ->get()
    ->map(function ($ep) {
        if (!$ep->payroll) {
            \Log::warning('Payroll record missing for EmployeePayroll', ['employee_payroll_id' => $ep->id, 'payroll_id' => $ep->payroll_id]);
            return null; // Skip records with missing payroll
        }
        return [
            'payroll_id' => $ep->payroll_id,
            'year' => $ep->payroll->payrun_year,
            'month' => $ep->payroll->payrun_month,
            'month_name' => Carbon::create($ep->payroll->payrun_year, $ep->payroll->payrun_month, 1)->monthName,
            'status' => $ep->payroll->status,
        ];
    })
    ->filter() // Remove null entries
    ->sortByDesc('year')
    ->sortByDesc('month')
    ->values();

        log::info($payslips);

        $page = "My Payslips";
        return view('employee.payslips', compact('page', 'payslips', 'employee', 'business'));
    }
//     public function viewPayslips(Request $request, $business)
// {
//     $business = Business::findBySlug($business);
//     if (!$business || session('active_business_slug') !== $business->slug) {
//         return redirect()->back()->with('error', 'Business not found or mismatched.');
//     }

//     $employee = Employee::where('business_id', $business->id)
//         ->where('user_id', Auth::id())
//         ->first();
//     if (!$employee) {
//         return redirect()->back()->with('error', 'Employee record not found.');
//     }

//     $payslips = EmployeePayroll::where('employee_id', $employee->id)
//         ->with(['payroll'])
//         ->get();

//     \Log::info('EmployeePayroll records for user', [
//         'user_id' => Auth::id(),
//         'employee_id' => $employee->id,
//         'count' => $payslips->count(),
//         'data' => $payslips->toArray()
//     ]);

//     $payslips = $payslips->map(function ($ep) {
//         if (!$ep->payroll) {
//             \Log::warning('Payroll record missing for EmployeePayroll', [
//                 'employee_payroll_id' => $ep->id,
//                 'payroll_id' => $ep->payroll_id
//             ]);
//             return null;
//         }
//         return [
//             'payroll_id' => $ep->payroll_id,
//             'year' => $ep->payroll->payrun_year,
//             'month' => $ep->payroll->payrun_month,
//             'month_name' => Carbon::create($ep->payroll->payrun_year, $ep->payroll->payrun_month, 1)->monthName,
//             'status' => $ep->payroll->status,
//         ];
//     })->filter()->sortByDesc('year')->sortByDesc('month')->values();

//     if ($payslips->isEmpty()) {
//         \Log::info('No valid payslips found for user', ['user_id' => Auth::id(), 'employee_id' => $employee->id]);
//         return view('employee.payslips', [
//             'page' => 'My Payslips',
//             'payslips' => [],
//             'employee' => $employee,
//             'business' => $business,
//             'message' => 'No payslips found for this employee.'
//         ]);
//     }

//     \Log::info('Mapped payslips', ['payslips' => $payslips->toArray()]);

//     $page = "My Payslips";
//     return view('employee.payslips', compact('page', 'payslips', 'employee', 'business'));
// }

   public function downloadPayslip(Request $request, $business, $id)
{
    // Get business
    $business = Business::findBySlug($business);
    if (!$business || session('active_business_slug') !== $business->slug) {
        return redirect()->back()->with('error', 'Business not found or mismatched.');
    }

    // Get employee for this business
    $employee = Employee::where('business_id', $business->id)
        ->where('user_id', Auth::id())
        ->first();

    if (!$employee) {
        return back()->with('error', 'Employee record not found.');
    }

    // Get employee payroll
    $employeePayroll = EmployeePayroll::where('employee_id', $employee->id)
        ->where('payroll_id', $id)
        ->with(['payroll.business', 'employee.user'])
        ->first();

    if (!$employeePayroll) {
        return back()->with('error', 'Payslip not found!');
    }

    if ($employeePayroll->payroll->status !== 'closed') {
        return back()->with('error', 'Payslip is not available until payroll is closed.');
    }

    try {
        $pdf = Pdf::loadView('payroll.reports.payslip', [
            'employeePayroll' => $employeePayroll,
            'business' => $employeePayroll->payroll->business,
            'entity' => $employeePayroll->payroll->business,
            'entityType' => 'business',
            'exchangeRates' => ['rate' => 1],
            'targetCurrency' => $employeePayroll->payroll->currency ?? 'KES',
        ]);

        $monthName = Carbon::create($employeePayroll->payroll->payrun_year, $employeePayroll->payroll->payrun_month, 1)->monthName;

        return $pdf->download("Payslip_{$employeePayroll->payroll->payrun_year}_{$monthName}_{$employee->employee_code}.pdf", [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment',
]);

    } catch (\Exception $e) {
        \Log::error('Payslip download error: ' . $e->getMessage());
        return back()->with('error', 'Failed to generate payslip. Please try again.');
    }
}

private function prepareP9Data($employee, $employeePayrolls, $business = null)
{

\Log::info('P9A Debug:', [
    'employee_id' => $employee->id,
    'employee_business_id' => $employee->business_id,
    'business_loaded' => $employee->relationLoaded('business'),
    'business_exists' => !is_null($employee->business),
    'business_company_name' => $employee->business->company_name ?? 'null',
    'business_tax_pin' => $employee->business->tax_pin_no ?? 'null',
]);
    // Fetch business from employee if not provided
    if (!$business) {
        $business = $employee->business ?? null; // Assuming Employee model has a 'business' relationship
        if (!$business) {
            if (!empty($employeePayrolls)) {
                $firstPayroll = $employeePayrolls->first();
                if ($firstPayroll && $firstPayroll->payroll && $firstPayroll->payroll->business) {
                    $business = $firstPayroll->payroll->business;
                }
            }
            if (!$business) {
                $business = new \stdClass();
                $business->tax_pin_no = 'N/A';
                $business->company_name = 'N/A';
            }
        }
    }

    // Initialize monthly data array with default values for 12 months
    $monthlyData = array_fill(1, 12, [
        'basic_salary' => 0,              // A
        'benefits_non_cash' => 0,         // B
        'value_of_quarters' => 0,         // C
        'total_gross_pay' => 0,           // D
        'retirement_e1' => 0,             // E1 (30% of A)
        'retirement_e2' => 0,             // E2 (Actual)
        'retirement_e3' => 30000,         // E3 (Fixed KRA limit for 2025)
        'ahl' => 0,                       // F (Affordable Housing Levy)
        'shif' => 0,                      // G (Social Health Insurance Fund)
        'prmf' => 0,                      // H (Post Retirement Medical Fund)
        'owner_occupied_interest' => 0,   // I
        'total_deductions' => 0,          // J (Sum of defined contribution + other deductions)
        'chargeable_pay' => 0,            // K (D - J)
        'tax_charged' => 0,               // L
        'personal_relief' => 2400,        // M (KRA standard, monthly)
        'insurance_relief' => 0,          // N (Max 5000/month)
        'paye' => 0,                      // O (L - M - N)
    ]);

    // Populate monthly data from payroll records
    foreach ($employeePayrolls as $ep) {
        $month = (int)$ep->payroll->payrun_month;
        $deductions = json_decode($ep->deductions, true) ?? [];

        $basicSalary = (float)($ep->basic_salary ?? 0);
        $grossPay = (float)($ep->gross_pay ?? 0);
        $housingLevy = (float)($deductions['ahl'] ?? $ep->housing_levy ?? ($grossPay * 0.015));
        $shif = (float)($deductions['shif'] ?? $ep->shif ?? 0);
        $nssfContribution = (float)($deductions['nssf'] ?? $ep->nssf ?? 0);
        $pensionContribution = (float)($deductions['pension'] ?? 0);
        $retirementE2 = $nssfContribution + $pensionContribution;

        $retirementE1 = $basicSalary * 0.3;
        $retirementE3 = 30000;
        $retirementContribution = min($retirementE1, $retirementE2, $retirementE3);

        $postRetirementMedical = min((float)($deductions['prmf'] ?? 0), 15000);
        $mortgageInterest = min((float)($deductions['owner_occupied_interest'] ?? 0), 30000);

        $totalDeductions = $retirementContribution + $housingLevy + $shif + $postRetirementMedical + $mortgageInterest;

        // Chargeable pay
        $chargeablePay = max(0, $grossPay - $totalDeductions);

        // --- Updated progressive KRA tax computation (current rates as of 2025) ---
        $taxCharged = 0;
        $tempPay = $chargeablePay;

        if ($tempPay > 800000) {
            $taxCharged += ($tempPay - 800000) * 0.35;
            $tempPay = 800000;
        }

        if ($tempPay > 500000) {
            $taxCharged += ($tempPay - 500000) * 0.325;
            $tempPay = 500000;
        }

        if ($tempPay > 32333.33) {
            $taxCharged += ($tempPay - 32333.33) * 0.30;
            $tempPay = 32333.33;
        }

        if ($tempPay > 24000) {
            $taxCharged += ($tempPay - 24000) * 0.25;
            $tempPay = 24000;
        }

        $taxCharged += $tempPay * 0.10;

        // Reliefs
        $personalRelief = (float)($ep->personal_relief ?? 2400);
        $insurancePremium = (float)($deductions['insurance_premium'] ?? 0);
        $insuranceRelief = min((float)($ep->insurance_relief ?? ($insurancePremium * 0.15)), 5000);

        // PAYE
        $paye = max(0, $taxCharged - $personalRelief - $insuranceRelief);

        $monthlyData[$month] = [
            'basic_salary' => $basicSalary,
            'benefits_non_cash' => (float)($ep->benefits_non_cash ?? 0),
            'value_of_quarters' => (float)($ep->value_of_quarters ?? 0),
            'total_gross_pay' => $grossPay,
            'retirement_e1' => $retirementE1,
            'retirement_e2' => $retirementE2,
            'retirement_e3' => $retirementE3,
            'ahl' => $housingLevy,
            'shif' => $shif,
            'prmf' => $postRetirementMedical,
            'owner_occupied_interest' => $mortgageInterest,
            'total_deductions' => $totalDeductions,
            'chargeable_pay' => $chargeablePay,
            'tax_charged' => $taxCharged,
            'personal_relief' => $personalRelief,
            'insurance_relief' => $insuranceRelief,
            'paye' => $paye,
        ];
    }

   // Calculate totals across all months
$totals = [
    'basic_salary' => array_sum(array_column($monthlyData, 'basic_salary')),
    'benefits_non_cash' => array_sum(array_column($monthlyData, 'benefits_non_cash')),
    'value_of_quarters' => array_sum(array_column($monthlyData, 'value_of_quarters')),
    'total_gross_pay' => array_sum(array_column($monthlyData, 'total_gross_pay')),
    'retirement_e1' => array_sum(array_column($monthlyData, 'retirement_e1')),
    'retirement_e2' => array_sum(array_column($monthlyData, 'retirement_e2')),
    'retirement_e3' => array_sum(array_column($monthlyData, 'retirement_e3')),
    'ahl' => array_sum(array_column($monthlyData, 'ahl')),
    'shif' => array_sum(array_column($monthlyData, 'shif')),
    'prmf' => array_sum(array_column($monthlyData, 'prmf')),
    'owner_occupied_interest' => array_sum(array_column($monthlyData, 'owner_occupied_interest')),
    'total_deductions' => array_sum(array_column($monthlyData, 'total_deductions')),
    'chargeable_pay' => array_sum(array_column($monthlyData, 'chargeable_pay')),
    'tax_charged' => array_sum(array_column($monthlyData, 'tax_charged')),
    'personal_relief' => array_sum(array_column($monthlyData, 'personal_relief')),
    'insurance_relief' => array_sum(array_column($monthlyData, 'insurance_relief')),
];

// Compute total PAYE correctly as (L_total - M_total - N_total)
$totals['paye'] = max(0, $totals['tax_charged'] - $totals['personal_relief'] - $totals['insurance_relief']);


    // Split employee full name into main name and other names
    $employeeNameParts = explode(' ', trim($employee->full_name), 2);

    return [
        'employer_pin' => $business->tax_pin_no ?? 'N/A',           // Employer's PIN from Business model
        'employer_name' => $business->company_name ?? 'N/A',         // Employer's name from Business model
        'employee_main_name' => $employeeNameParts[0] ?? '', // First name or part of full name
        'employee_other_names' => $employeeNameParts[1] ?? '', // Remaining names
        'employee_pin' => $employee->tax_no ?? 'N/A',        // Employee's tax number
        'monthly_data' => $monthlyData,
        'totals' => $totals,
    ];
}
// private function prepareP9Data($employee, $payrolls, $year)
// {
//     $monthlyData = array_fill(1, 12, [
//         'basic_salary' => 0,              // A
//         'benefits_non_cash' => 0,         // B
//         'value_of_quarters' => 0,         // C
//         'total_gross_pay' => 0,           // D
//         'retirement_e1' => 0,             // E1 (30% of A)
//         'retirement_e2' => 0,             // E2 (Actual)
//         'retirement_e3' => 30000,         // E3 (Fixed KRA limit for 2025)
//         'ahl' => 0,                       // F
//         'shif' => 0,                      // G
//         'prmf' => 0,                      // H
//         'owner_occupied_interest' => 0,   // I
//         'total_deductions' => 0,          // J
//         'chargeable_pay' => 0,            // K
//         'tax_charged' => 0,               // L
//         'personal_relief' => 2400,        // M
//         'insurance_relief' => 0,          // N
//         'paye' => 0,                      // O
//     ]);

//     foreach ($payrolls as $ep) {
//         $month = (int)$ep->payroll->payrun_month;
//         $deductions = json_decode($ep->deductions, true) ?? [];
//         $basicSalary = (float)$ep->basic_salary;
//         $grossPay = (float)$ep->gross_pay;
//         $taxableIncome = (float)$ep->taxable_income;
//         $paye = (float)$ep->paye;
//         $personalRelief = (float)($ep->personal_relief ?? 2400);
//         $insuranceRelief = (float)($ep->insurance_relief ?? 0);
//         $retirementE1 = $basicSalary * 0.3; // 30% of basic salary
//         $retirementE2 = (float)($ep->pension ?? ($deductions['pension'] ?? 0)); // Actual contribution
//         $ahl = (float)($deductions['ahl'] ?? 0);
//         $shif = (float)($deductions['shif'] ?? 0);
//         $prmf = min((float)($deductions['prmf'] ?? 0), 15000); // Cap at 15,000
//         $owner_occupied_interest = min((float)($deductions['owner_occupied_interest'] ?? 0), 30000); // Cap at 30,000

//         $total_deductions = min($retirementE1, $retirementE2, 30000) + $ahl + $shif + $prmf + $owner_occupied_interest;

//         $monthlyData[$month] = [
//             'basic_salary' => $basicSalary,
//             'benefits_non_cash' => (float)($ep->benefits_non_cash ?? ($deductions['benefits_non_cash'] ?? 0)),
//             'value_of_quarters' => (float)($ep->value_of_quarters ?? ($deductions['value_of_quarters'] ?? 0)),
//             'total_gross_pay' => $grossPay,
//             'retirement_e1' => $retirementE1,
//             'retirement_e2' => $retirementE2,
//             'retirement_e3' => 30000,
//             'ahl' => $ahl,
//             'shif' => $shif,
//             'prmf' => $prmf,
//             'owner_occupied_interest' => $owner_occupied_interest,
//             'total_deductions' => $total_deductions,
//             'chargeable_pay' => $taxableIncome,
//             'tax_charged' => $paye + $personalRelief + $insuranceRelief,
//             'personal_relief' => $personalRelief,
//             'insurance_relief' => $insuranceRelief,
//             'paye' => $paye,
//         ];
//     }

//     $totals = [
//         'basic_salary' => array_sum(array_column($monthlyData, 'basic_salary')),
//         'benefits_non_cash' => array_sum(array_column($monthlyData, 'benefits_non_cash')),
//         'value_of_quarters' => array_sum(array_column($monthlyData, 'value_of_quarters')),
//         'total_gross_pay' => array_sum(array_column($monthlyData, 'total_gross_pay')),
//         'retirement_e1' => array_sum(array_column($monthlyData, 'retirement_e1')),
//         'retirement_e2' => array_sum(array_column($monthlyData, 'retirement_e2')),
//         'retirement_e3' => array_sum(array_column($monthlyData, 'retirement_e3')),
//         'ahl' => array_sum(array_column($monthlyData, 'ahl')),
//         'shif' => array_sum(array_column($monthlyData, 'shif')),
//         'prmf' => array_sum(array_column($monthlyData, 'prmf')),
//         'owner_occupied_interest' => array_sum(array_column($monthlyData, 'owner_occupied_interest')),
//         'total_deductions' => array_sum(array_column($monthlyData, 'total_deductions')),
//         'chargeable_pay' => array_sum(array_column($monthlyData, 'chargeable_pay')),
//         'tax_charged' => array_sum(array_column($monthlyData, 'tax_charged')),
//         'personal_relief' => array_sum(array_column($monthlyData, 'personal_relief')),
//         'insurance_relief' => array_sum(array_column($monthlyData, 'insurance_relief')),
//         'paye' => array_sum(array_column($monthlyData, 'paye')),
//     ];

//     $employeeNameParts = explode(' ', $employee->full_name, 2);

//     return [
//         'employer_pin' => $employee->tax_no ?? 'N/A',
//         'employer_name' => $employee->user->name ?? 'N/A',
//         'employee_main_name' => $employeeNameParts[0] ?? '',
//         'employee_other_names' => $employeeNameParts[1] ?? '',
//         'employee_pin' => $employee->tax_no ?? 'N/A',
//         'monthly_data' => $monthlyData,
//         'totals' => $totals,
//     ];
// }


    public function accountSettings()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        return view('employee.settings', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Validate the incoming data
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'required|in:male,female',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'address' => 'required|string|max:255',
            'permanent_address' => 'nullable|string|max:255',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'avatar' => 'nullable|image|max:2048', // Max 2MB for profile picture
            'spouse_surname_name' => 'nullable|string|max:255',
            'spouse_first_name' => 'nullable|string|max:255',
            'spouse_middle_name' => 'nullable|string|max:255',
            'spouse_date_of_birth' => 'nullable|date',
            'spouse_phone' => 'nullable|string|max:20',
            'emmergency_contact_name.*' => 'required|string|max:255',
            'emmergency_contact_relationship.*' => 'required|string|max:255',
            'emmergency_contact_phone.*' => 'required|string|max:20',
        ]);

        // Handle file upload for avatar
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        // Update employee details
        $employee->update($validated);

        // Handle emergency contacts (assuming a separate table or JSON column)
        if ($request->has('emmergency_contact_name')) {
            $emergencyContacts = [];
            foreach ($request->emmergency_contact_name as $index => $name) {
                $emergencyContacts[] = [
                    'name' => $name,
                    'relationship' => $request->emmergency_contact_relationship[$index],
                    'phone' => $request->emmergency_contact_phone[$index],
                ];
            }
            // Save to a JSON column or related table
            $employee->emergency_contacts = json_encode($emergencyContacts); // If using JSON column
            $employee->save();
        }

        return redirect()->route('account.settings')->with('success', 'Profile updated successfully!');
    }
}
