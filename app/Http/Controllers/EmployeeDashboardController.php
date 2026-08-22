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
use Illuminate\Support\Facades\Http;

class EmployeeDashboardController extends Controller
{
    // Dashboard Overview
public function index(Request $request)
{
    $page = "Dashboard";
    $user = Auth::user();
    $employeeId = optional($user->activeEmployee())->id;

   if (!$employeeId) {
    return view('employee.index', [
        'page' => $page,
        'leave_count' => 0,
        'pending_leaves' => 0,
        'work_days' => 0,
        'payslips' => 0,
        'leave_balances' => [],
    ])->with('error', 'Employee record not found.');
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


    // Scoped to the CURRENT period only - without this, an employee with
    // entitlement rows across several periods (2025, 2026, 2027, ...) saw
    // every one of them at once, including the same leave type repeated
    // with different, confusing numbers per period.
    $business = Business::findBySlug(session('active_business_slug'));
    $currentPeriod = $business
        ? $business->leavePeriods()
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->first()
        : null;

    $leave_balances = \App\Models\LeaveEntitlement::with('leaveType')
        ->where('employee_id', $employeeId)
        ->when($currentPeriod, fn ($q) => $q->where('leave_period_id', $currentPeriod->id))
        ->get()
        ->map(function ($entitlement) {
            return [
                'leave_type' => $entitlement->leaveType->name ?? 'Unknown',
                // total_days (grant + carryover + adjustment - the actual
                // usable pool, per LeaveEntitlement::recalculateTotals())
                // is what an employee should see as their balance -
                // entitled_days alone omits carryover/adjustments and
                // doesn't match what days_remaining is actually measured
                // against.
                'total_days' => $entitlement->total_days,
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
        // Retired/deactivated leave types stay visible to the business
        // admin (they still need to manage/reactivate them) but shouldn't
        // be a pickable option on an employee's own application form.
        $leaveTypes  = $business->leaveTypes()->where('is_active', true)->get();

        $employeeId = optional(Auth::user()->activeEmployee())->id;
        $leaveRequests = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('business_id', $business->id)
            ->where('employee_id', $employeeId) // ⚠️ employee_id, not user id
            ->latest()
            ->get();

        $colleagues = $business->employees()
            ->where('id', '!=', $employeeId)
            ->with('user')
            ->get();

        return view('leave.request-leave', compact('page', 'description', 'leaveTypes', 'leaveRequests', 'colleagues'));
    }

    public function viewLeaves()
    {
        $page = "My Leaves";

        $business   = Business::findBySlug(session('active_business_slug'));
        $employeeId = optional(Auth::user()->activeEmployee())->id;

        $leaves = LeaveRequest::with(['leaveType', 'employee.user'])
            ->where('business_id', $business->id)
            ->where('employee_id', $employeeId) // ⚠️ employee_id, not user id
            ->latest()
            ->get();

        // Deliberately its own view, not the business-side leave.index -
        // that one expects $departments/$locations/$leaveTypes/$leavePeriods
        // for its cross-employee filter dropdowns, which don't apply here:
        // an employee's own leave list needs no filtering by department/
        // location at all. The underlying fetch() AJAX endpoint already
        // scopes to the current employee when active_role is
        // business-employee (see LeaveRequestController::fetch()).
        return view('leave.portal-index', compact('page', 'leaves'));
    }

    public function leaveApplication(Request $request, string $business_slug, string $reference_number)
    {
        $business = Business::findBySlug($business_slug);

        $leave = LeaveRequest::with(['employee.user', 'leaveType', 'approvedBy'])
            ->where('reference_number', $reference_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        // Security: employees can only view their own requests here
        $employeeId = optional(Auth::user()->activeEmployee())->id;
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

    /**
     * "My Attendance" - self-service Daily/Monthly/Summary reports, always
     * forced to the caller's own employee record (see
     * AttendanceReportController::my*() - department/job-category/employee
     * filters are never accepted from an employee-portal request).
     */
    public function attendances(Request $request, Business $business)
    {
        $page = 'My Attendance';
        $employee = $request->user()->activeEmployee();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $thisMonthHours = 0.0;
        if ($employee) {
            $thisMonthHours = (float) Attendance::where('employee_id', $employee->id)
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->get()
                ->sum(fn ($a) => (float) $a->regular_hours + (float) $a->overtime_hours);
        }

        return view('attendances.employee-index', [
            'page' => $page,
            'business' => $business,
            'employee' => $employee,
            'thisMonthHoursLabel' => \App\Support\TimeFmt::hoursToTotalLabel($thisMonthHours),
        ]);
    }

    public function updateDetails(Request $request)
    {
        $page = 'Update Your Details';
        $description = '';
        return view('employees.update-details', compact('page', 'description'));
    }

    /**
     * P9 form landing page. Originally a blind "pick any of the last 6
     * years" form - every one of those years usually has no closed
     * payroll yet, so downloadP9() would silently back()-bounce with
     * nothing to show for it. Lists only years that actually have a
     * closed payroll run, each with its own direct download link -
     * matching viewPayslips()'s pattern, and impossible to submit a year
     * with nothing behind it.
     */
    public function viewP9Forms(Request $request, $business)
    {
        $business = Business::findBySlug($business);
        if (!$business) {
            return redirect()->back()->with('error', 'Business not found.');
        }

        $employee = Employee::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->first();

        $years = $employee
            ? EmployeePayroll::where('employee_id', $employee->id)
                ->whereHas('payroll', fn ($q) => $q->where('status', 'closed'))
                ->with('payroll:id,payrun_year')
                ->get()
                ->pluck('payroll.payrun_year')
                ->unique()
                ->sortDesc()
                ->values()
            : collect();

        return view('employee.p9', ['business' => $business, 'years' => $years]);
    }

    // Download P9 Form
    public function downloadP9(Request $request)
    {
        $employee = Auth::user()->activeEmployee();
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

        // No hard bounce on a missing employee record - same
        // graceful-empty pattern as the rest of the portal (see
        // OrganogramController::myTeam()) instead of silently back()ing
        // to nowhere with a flash message nothing displayed anyway.
        $payslips = $employee
            ? EmployeePayroll::where('employee_id', $employee->id)
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
                ->values()
            : collect();

        $page = "My Payslips";
        return view('employee.payslips', compact('page', 'payslips', 'employee', 'business'));
    }

    public function myAssets(Request $request, $business)
    {
        $business = Business::findBySlug($business);
        if (!$business || session('active_business_slug') !== $business->slug) {
            return redirect()->back()->with('error', 'Business not found or mismatched.');
        }

        $employee = Employee::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->first();

        // Same graceful-empty pattern as the rest of the portal - no
        // employee record just means nothing to show, not a bounce.
        $assignments = $employee
            ? \App\Models\AssetAssignment::where('business_id', $business->id)
                ->where('employee_id', $employee->id)
                ->whereNull('returned_at')
                ->with('asset')
                ->orderByDesc('assigned_at')
                ->get()
            : collect();

        $page = "My Assets";
        return view('employee.assets', compact('page', 'assignments', 'employee', 'business'));
    }

    public function myLearning(Request $request, $business)
    {
        $business = Business::findBySlug($business);
        if (!$business || session('active_business_slug') !== $business->slug) {
            return redirect()->back()->with('error', 'Business not found or mismatched.');
        }

        $employee = Employee::where('business_id', $business->id)
            ->where('user_id', Auth::id())
            ->first();

        // Same graceful-empty pattern as the rest of the portal - no
        // employee record just means nothing to show, not a bounce.
        $enrollments = $employee
            ? \App\Models\CourseEnrollment::where('business_id', $business->id)
                ->where('employee_id', $employee->id)
                ->with(['course.category', 'session'])
                ->orderByRaw("FIELD(status, 'in_progress', 'enrolled', 'completed', 'dropped')")
                ->orderByDesc('enrolled_at')
                ->get()
            : collect();

        $page = "My Learning";
        return view('employee.learning', compact('page', 'enrollments', 'employee', 'business'));
    }

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
        ->with(['payroll.business', 'payroll.location', 'employee.user'])
        ->first();

    if (!$employeePayroll) {
        return back()->with('error', 'Payslip not found!');
    }

    if ($employeePayroll->payroll->status !== 'closed') {
        return back()->with('error', 'Payslip is not available until payroll is closed.');
    }

    // Currency Conversion Logic
    $targetCurrency = strtoupper($employeePayroll->employee->user->country ?? 'USD');
    $baseCurrency = $employeePayroll->payroll->currency ?? 'KES';
    $exchangeRates = $this->getExchangeRates($baseCurrency, $targetCurrency);
    $exchangeRates = is_numeric($exchangeRates) ? floatval($exchangeRates) : 1.0;

    // Log the exchange rate for debugging
    Log::info('Exchange Rate Used for Download', [
        'base_currency' => $baseCurrency,
        'target_currency' => $targetCurrency,
        'exchange_rate' => $exchangeRates
    ]);

    // Determine entity and entityType
    $entity = $business;
    $entityType = 'business';
    if ($employeePayroll->payroll->location_id) {
        $location = Location::where('id', $employeePayroll->payroll->location_id)
            ->where('business_id', $business->id)
            ->first();
        if ($location) {
            $entity = $location;
            $entityType = 'location';
        }
    }

    try {
        $pdf = Pdf::loadView('payroll.reports.payslip', [
            'employeePayroll' => $employeePayroll,
            'business' => $business,
            'entity' => $entity,
            'entityType' => $entityType,
            'exchangeRates' => $exchangeRates,
            'targetCurrency' => $targetCurrency,
        ]);

        $monthName = Carbon::create($employeePayroll->payroll->payrun_year, $employeePayroll->payroll->payrun_month, 1)->monthName;

        return $pdf->download("Payslip_{$employeePayroll->payroll->payrun_year}_{$monthName}_{$employee->employee_code}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment',
        ]);
    } catch (\Exception $e) {
        Log::error('Payslip PDF Generation Error: ' . $e->getMessage());
        return back()->with('error', 'Failed to generate payslip PDF.');
    }
}
    private function getExchangeRates($baseCurrency, $targetCurrency)
{
    try {
        // Fetch latest exchange rates
        $response = Http::get("https://api.frankfurter.dev/v1/latest", [
            'base' => $baseCurrency,
            'symbols' => $targetCurrency
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $exchangeRate = $data['rates'][$targetCurrency] ?? null;

            if (is_numeric($exchangeRate)) {
                return floatval($exchangeRate);
            }

            Log::warning('No valid exchange rate found', [
                'base' => $baseCurrency,
                'target' => $targetCurrency
            ]);
            return 1.0;
        } else {
            Log::error('Frankfurter API Error: ' . $response->body());
            return 1.0;
        }
    } catch (\Exception $e) {
        Log::error('Frankfurter API Exception: ' . $e->getMessage());
        return 1.0;
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

    /**
     * Portal notifications page (database-channel notifications written by
     * things like LeaveStatusNotification, KpiAssigned, etc.).
     */
    public function notifications(Request $request, Business $business)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('employee.notifications', [
            'business' => $business,
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationRead(Request $request, Business $business, string $notification)
    {
        $record = $request->user()->notifications()->where('id', $notification)->first();

        if ($record && is_null($record->read_at)) {
            $record->markAsRead();
        }

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllNotificationsRead(Request $request, Business $business)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
