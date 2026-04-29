<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Location;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Models\Overtime;
use App\Models\Advance;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\Deduction;
use App\Models\Allowance;
use App\Models\Relief;
use App\Models\EmployeeDeduction;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeRelief;
use App\Models\PayrollFormula;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Exports\PayrollExport;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PayslipMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use App\Models\PayrollFormulaBracket;
use App\Models\EmployeePayrollDetail;
use App\Models\PayrollSettings;
use App\Models\EmployeePaymentDetail;
use Illuminate\Support\Facades\Http;
use App\Exports\P9Export;
use App\Exports\BankAdviceExport;
use Illuminate\Support\Facades\File;
use App\Exports\NssfMonthlySummaryExport;
use App\Exports\ShifMonthlySummaryExport;
use App\Services\CurrencyService;


use function Illuminate\Log\log;

class PayrollController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $locations = $business->locations->prepend((object) [
            'id' => 'business_' . $business->id,
            'name' => $business->company_name,
        ]);

        $years = range(date('Y') + 5, date('Y'));

        $employees = Employee::where('business_id', $business->id)
            ->with(['user', 'location'])
            ->get(['id', 'user_id', 'location_id', 'employee_code']);

        return view('payroll.index', [
            'page' => 'Process Payroll',
            'years' => $years,
            'months' => range(1, 12),
            'locations' => $locations,
            'departments' => $business->departments,
            'jobCategories' => $business->job_categories,
            'employees' => $employees,
            'allowances' => $business->allowances,
            'deductions' => $business->deductions,
            'reliefs' => $business->reliefs,
            'business' => $business,
        ]);
    }

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $payroll = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $request->year)
            ->where('payrun_month', $request->month)
            ->first();

        if ($payroll && $payroll->status === 'closed') {
            return RequestResponse::badRequest('This month has already been closed.');
        }

        $employees = $this->getFilteredEmployees($request, $business);
        $warnings = $this->checkMissingData($employees);
        $options = $this->parseOptions($request);
        $nonExemptedEmployees = $employees->filter(fn($e) => !array_key_exists($e->id, $options['exempted_employees']));
        $daysInMonth = $request->working_days;

        $years = range(date('Y') + 5, date('Y'));

        return RequestResponse::ok('success', [
            'html' => view('payroll._table', [
                'employees' => $employees,
                'warnings' => $warnings,
                'options' => $options,
                'allowances' => $business->allowances,
                'deductions' => $business->deductions,
                'reliefs' => $business->reliefs,
                'daysInMonth' => $daysInMonth,
                'years' => $years,
                'business' => $business,
            ])->render(),
            'count' => $nonExemptedEmployees->count(),
            'warnings' => $warnings,
            'options' => $options,
            'years' => $years,
            'employees' => $employees->map(function ($employee) use ($business) {
                return [
                    'id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'name' => $employee->user?->name ?? 'N/A',
                    'location' => $employee->location?->name ?? $business->name,
                    'location_id' => $employee->location_id ?? 'business_' . $business->id,
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'department' => $employee->employmentDetails?->department?->name ?? 'N/A',
                    'job_category' => $employee->employmentDetails?->jobCategory?->name ?? 'N/A',
                ];
            })->values()->toArray(),
        ]);
    }

    public function availableItems(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $employeeIds = Employee::where('business_id', $business->id)->pluck('id')->toArray();

        $allowances = Allowance::where('business_id', $business->id)
            ->get(['id', 'slug', 'name', 'amount', 'rate', 'type', 'calculation_basis', 'is_taxable', 'applies_to'])
            ->map(function ($allowance) {
                return [
                    'id' => $allowance->id,
                    'slug' => $allowance->slug,
                    'name' => $allowance->name,
                    'amount' => $allowance->amount ?? 0,
                    'rate' => $allowance->rate ?? 0,
                    'type' => $allowance->type ?? 'fixed',
                    'calculation_basis' => $allowance->calculation_basis ?? null,
                    'is_taxable' => $allowance->is_taxable ?? false,
                    'applies_to' => $allowance->applies_to ?? null,
                ];
            })->toArray();

        $deductions = Deduction::where('business_id', $business->id)
            ->get(['id', 'name', 'slug', 'actual_amount', 'rate', 'calculation_basis', 'computation_method', 'fraction_to_consider', 'is_statutory', 'limit', 'is_optional'])
            ->map(function ($deduction) {
                return [
                    'id' => $deduction->id,
                    'slug' => $deduction->slug,
                    'name' => $deduction->name,
                    'amount' => $deduction->amount ?? 0,
                    'rate' => $deduction->rate ?? 0,
                    'type' => $deduction->type ?? 'fixed',
                    'calculation_basis' => $deduction->calculation_basis ?? null,
                    'computation_method' => $deduction->computation_method ?? null,
                    'fraction_to_consider' => $deduction->fraction_to_consider ?? null,
                    'is_statutory' => $deduction->is_statutory ?? false,
                    'limit' => $deduction->limit ?? null,
                    'is_optional' => $deduction->is_optional ?? false,
                ];
            })->toArray();

        $reliefs = Relief::where('business_id', $business->id)
            ->get(['id', 'name', 'slug', 'amount', 'computation_method', 'percentage_of_amount', 'limit'])
            ->map(function ($relief) {
                return [
                    'id' => $relief->id,
                    'slug' => $relief->slug,
                    'name' => $relief->name,
                    'amount' => $relief->amount ?? 0,
                    'computation_method' => $relief->computation_method ?? null,
                    'percentage_of_amount' => $relief->percentage_of_amount ?? null,
                    'limit' => $relief->limit ?? null,
                ];
            })->toArray();

        $loans = Loan::whereIn('employee_id', $employeeIds)
            ->with('repayments')
            ->get(['id', 'employee_id', 'start_date', 'amount'])
            ->map(function ($loan) {
                $totalRepayments = $loan->repayments->sum('amount') ?? 0;
                $remaining = $loan->amount - $totalRepayments;
                return [
                    'id' => $loan->id,
                    'employee_id' => $loan->employee_id,
                    'start_date' => $loan->start_date?->format('Y-m-d'),
                    'amount' => $loan->amount ?? 0,
                    'remaining' => $remaining > 0 ? $remaining : 0,
                ];
            })
            ->filter(fn($loan) => $loan['remaining'] > 0)
            ->values()
            ->toArray();

        $advances = Advance::whereIn('employee_id', $employeeIds)
            ->get(['id', 'employee_id', 'date', 'amount'])
            ->map(function ($advance) {
                return [
                    'id' => $advance->id,
                    'employee_id' => $advance->employee_id,
                    'date' => $advance->date?->format('Y-m-d'),
                    'amount' => $advance->amount ?? 0,
                    'is_active' => false,
                ];
            })->toArray();

        return RequestResponse::ok('success', [
            'allowances' => $allowances,
            'deductions' => $deductions,
            'reliefs' => $reliefs,
            'loans' => $loans,
            'advances' => $advances,
        ]);
    }

    public function defaultAmount(Request $request, $type, $itemId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $modelMap = [
            'allowances' => Allowance::class,
            'deductions' => Deduction::class,
            'reliefs' => Relief::class,
            'loans' => Loan::class,
            'advances' => Advance::class,
        ];

        if (!isset($modelMap[$type])) return RequestResponse::badRequest('Invalid item type.');

        $model = $modelMap[$type];
        $item = $model::find($itemId);
        if (!$item) return RequestResponse::badRequest('Item not found.');

        $amount = $type === 'loans' ? ($item->amount - ($item->repayments->sum('amount') ?? 0)) : ($item->amount ?? 0);
        $rate = $item->rate ?? 0;

        return RequestResponse::ok('success', ['amount' => $amount, 'rate' => $rate]);
    }

    public function fetchEmployeesForSettings(Request $request)
     {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $year = $request->year;
        $month = $request->month;

        $employees = $this->getFilteredEmployees($request, $business);
        $employees->load([
            'user',
            'employeeAllowances.allowance',
            'employeeDeductions.deduction',
            'reliefs' => fn($q) => $q->whereNotNull('relief_id'),
           'overtimes' => fn($q) => $q->whereYear('date', $year)
                            ->whereMonth('date', $month)
                            ->where('status', 'approved'),
            'loans.repayments',
            'advances',
        ]);

        $payrollSettings = PayrollSettings::where('year', $year)
            ->where('month', $month)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $formattedEmployees = $employees->map(function ($employee) use ($payrollSettings, $year, $month) {
            $settings = $payrollSettings->get($employee->id);
            $hasSettings = !is_null($settings);

            $mapItems = function ($type, $settingsData, $employeeData, $modelClass, $key) use ($employee, $hasSettings) {
                $sourceData = $employeeData->mapWithKeys(function ($item) use ($modelClass, $employee, $key, $type) {
                    $itemId = $type === 'reliefs' ? $item->id : $item->$key;
                    $defaultItem = $modelClass::find($itemId);
                    $itemName = $defaultItem ? $defaultItem->name : "Unknown " . ucfirst(substr($type, 0, -1)) . " (ID: {$itemId})";

                    $pivotData = [
                        'user_id' => $employee->user_id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->user?->name ?? 'N/A',
                        'item_name' => $itemName,
                        'item_id' => $itemId,
                        'amount' => floatval($type === 'reliefs' ? $item->pivot->amount : $item->amount ?? $defaultItem?->amount ?? 0),
                        'rate' => floatval($type === 'reliefs' ? $item->pivot->rate : $item->rate ?? $defaultItem?->rate ?? 0),
                        'is_active' => $type === 'reliefs' ? $item->pivot->is_active : $item->is_active ?? true,
                    ];
                    if ($type === 'allowances') $pivotData['is_taxable'] = $defaultItem->is_taxable ?? false;
                    if ($type === 'deductions') $pivotData['is_statutory'] = $defaultItem->is_statutory ?? false;
                    if ($defaultItem && $defaultItem->calculation_basis) $pivotData['calculation_basis'] = $defaultItem->calculation_basis;

                    return [$itemId => $pivotData];
                })->all();

                // FIXED
                if ($hasSettings && $settingsData) {
                    $settingsById = [];
                    foreach ($settingsData as $item) {
                        $key = (int)($item['item_id'] ?? 0);
                        if (!$key) continue;
                        $settingsById[$key] = array_merge($item, [
                            'amount' => floatval($item['amount'] ?? 0),
                            'rate'   => floatval($item['rate'] ?? 0),
                        ]);
                    }
                    return array_replace($sourceData, $settingsById);
                }
                return $sourceData;
            };

         $mapOvertime = function ($settingsData, $employeeOvertimes) use ($employee, $hasSettings) {
    $sourceData = $employeeOvertimes->mapWithKeys(function ($overtime) use ($employee) {
        return [$overtime->id => [
            'user_id'       => $employee->user_id,
            'employee_code' => $employee->employee_code,
            'name'          => $employee->user?->name ?? 'N/A',
            'item_name'     => "Overtime on {$overtime->date?->format('Y-m-d')} ({$overtime->overtime_hours} hrs)",
            'item_id'       => $overtime->id,
            'amount'        => floatval($overtime->overtime_hours ?? 0), // hours — used in calculation
            'total_pay'     => floatval($overtime->total_pay ?? 0),      // hours × multiplier — for display
            'rate'          => floatval($overtime->rate ?? 0),
            'is_active'     => $overtime->status === 'approved',         // only approved are active by default
        ]];
    })->all();

    return $hasSettings && $settingsData
        ? array_merge($sourceData, array_map(function ($item) {
            return array_merge($item, ['amount' => floatval($item['amount'] ?? 0)]);
        }, $settingsData))
        : $sourceData;
};

            $mapLoansAdvances = function ($settingsData, $employeeData, $type) use ($employee, $hasSettings) {
                $sourceData = $employeeData->mapWithKeys(function ($item) use ($employee, $type) {
                    $remaining = $type === 'loans' ? floatval($item->amount - ($item->repayments->sum('amount') ?? 0)) : floatval($item->amount);
                    return [$item->id => [
                        'user_id' => $employee->user_id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->user?->name ?? 'N/A',
                        'item_name' => $type === 'loans' ? "Loan started {$item->start_date?->format('Y-m-d')}" : "Advance on {$item->date?->format('Y-m-d')}",
                        'item_id' => $item->id,
                        'amount' => $remaining > 0 ? $remaining : 0,
                        'is_active' => $item->is_active ?? ($remaining > 0),
                    ]];
                })->all();

                return $hasSettings && $settingsData ? array_merge($sourceData, array_map(function ($item) {
                    return array_merge($item, ['amount' => floatval($item['amount'] ?? 0)]);
                }, $settingsData)) : $sourceData;
            };

            $formattedEmployee = [
                'id' => $employee->id,
                'name' => $employee->user?->name ?? 'N/A',
                'employee_code' => $employee->employee_code,
                'allowances' => $mapItems('allowances', $settings?->allowances, $employee->employeeAllowances, Allowance::class, 'allowance_id'),
                'deductions' => $mapItems('deductions', $settings?->deductions, $employee->employeeDeductions, Deduction::class, 'deduction_id'),
                'reliefs' => $mapItems('reliefs', $settings?->reliefs, $employee->reliefs, Relief::class, 'relief_id'),
                'overtimes' => $mapOvertime($settings?->overtime, $employee->overtimes),
                'loans' => $mapLoansAdvances($settings?->loans, $employee->loans, 'loans'),
                'advances' => $mapLoansAdvances($settings?->advances, $employee->advances, 'advances'),
                'absenteeism_charge' => [
                    'user_id' => $employee->user_id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->user?->name ?? 'N/A',
                    'item_name' => 'Absenteeism Charge',
                    'item_id' => null,
                    'amount' => floatval($hasSettings && !is_null($settings->absenteeism_charge) ? $settings->absenteeism_charge : 0),
                ],
            ];

            return $formattedEmployee;
        })->values()->toArray();

        return RequestResponse::ok('success', ['employees' => $formattedEmployees]);
    }

    public function saveSettings(Request $request)
    {
        $input = $request->json()->all();
        $year      = $input['year'] ?? $request->year;
        $month     = $input['month'] ?? $request->month;
        $employees = $input['employees'] ?? $request->employees;
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $year = $request->year;
        $month = $request->month;
        $employees = $request->employees;

        if (!is_numeric($year) || !is_numeric($month) || !is_array($employees) || empty($employees)) {
            return RequestResponse::badRequest('Missing or invalid required fields: year, month, or employees.');
        }

        try {
            DB::beginTransaction();
            foreach ($employees as $employeeData) {
                if (!isset($employeeData['id']) || !is_numeric($employeeData['id'])) {
                    throw new \Exception("Invalid or missing employee_id in data: " . json_encode($employeeData));
                }

                $employeeId = $employeeData['id'];
                $employee = Employee::with('user')->findOrFail($employeeId);
                $existingSettings = PayrollSettings::where('employee_id', $employeeId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

                $mergedData = [
                    'allowances' => $this->formatSettingsData($employeeData['allowances'] ?? [], $existingSettings?->allowances ?? [], $employee, Allowance::class),
                    'deductions' => $this->formatSettingsData($employeeData['deductions'] ?? [], $existingSettings?->deductions ?? [], $employee, Deduction::class),
                    'reliefs' => $this->formatSettingsData($employeeData['reliefs'] ?? [], $existingSettings?->reliefs ?? [], $employee, Relief::class),
                    'overtime' => $this->formatSettingsData($employeeData['overtime'] ?? [], $existingSettings?->overtime ?? [], $employee, Overtime::class),
                    'loans' => $this->formatSettingsData($employeeData['loans'] ?? [], $existingSettings?->loans ?? [], $employee, Loan::class),
                    'advances' => $this->formatSettingsData($employeeData['advances'] ?? [], $existingSettings?->advances ?? [], $employee, Advance::class),
                    'absenteeism_charge' => floatval($employeeData['absenteeism_charge']['amount'] ?? ($existingSettings?->absenteeism_charge ?? 0)),
                ];

                PayrollSettings::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'year' => $year,
                        'month' => $month,
                    ],
                    $mergedData
                );
            }
            DB::commit();
            return RequestResponse::ok('success', 'Payroll settings saved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save payroll settings: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to save payroll settings: ' . $e->getMessage());
        }
    }

    private function formatSettingsData($newData, $existingData, $employee, $modelClass)
    {
        $formatted = [];

        $existingData = is_array($existingData) ? $existingData : [];
        $existingById = array_map(function ($item) {
            return [
                'amount' => floatval($item['amount'] ?? 0),
                'rate' => floatval($item['rate'] ?? 0),
                'is_active' => filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'item_name' => $item['item_name'] ?? null,
                'user_id' => $item['user_id'] ?? null,
                'employee_code' => $item['employee_code'] ?? null,
                'name' => $item['name'] ?? null,
                'item_id' => $item['item_id'] ?? null,
            ];
        }, array_column($existingData, null, 'item_id'));

        foreach ($newData as $id => $item) {
            $defaultItem = $modelClass::find($id);
            $itemName = $defaultItem ? $defaultItem->name : ($item['item_name'] ?? "Unknown Item (ID: {$id})");

            if ($modelClass === Overtime::class) {
                $itemName = $defaultItem ? "Overtime on {$defaultItem->date?->format('Y-m-d')}" : $itemName;
            } elseif ($modelClass === Loan::class) {
                $itemName = $defaultItem ? "Loan started {$defaultItem->start_date?->format('Y-m-d')}" : $itemName;
            } elseif ($modelClass === Advance::class) {
                $itemName = $defaultItem ? "Advance on {$defaultItem->date?->format('Y-m-d')}" : $itemName;
            }

            $newItem = [
                'user_id' => $employee->user_id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->user?->name ?? 'N/A',
                'item_name' => $itemName,
                'item_id' => $id,
                'amount' => floatval($item['amount'] ?? 0),
                'rate' => floatval($item['rate'] ?? 0),
                'is_active' => filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            $formatted[$id] = $newItem;
        }

        foreach ($existingById as $id => $existingItem) {
            if (!isset($newData[$id])) {
                $formatted[$id] = array_merge($existingItem, [
                    'is_active' => false,
                    'amount' => floatval($existingItem['amount'] ?? 0),
                    'rate' => floatval($existingItem['rate'] ?? 0),
                ]);
            }
        }

        return $formatted;
    }

    public function store(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $previewData = session('payroll_preview_data');
        if (!$previewData) {
            return RequestResponse::badRequest('No preview data found. Please generate a preview first.');
        }

        $payrollData = $previewData['payroll_data'];
        $year = $previewData['year'];
        $month = $previewData['month'];
        $businessId = $previewData['business_id'];
        $locationId = $previewData['location_id'];
        $options = $previewData['options'];
        $nonExemptedEmployeeIds = $previewData['non_exempted_employee_ids'];

        if (empty($payrollData)) {
            Log::warning('No payroll data in session');
            return RequestResponse::badRequest('No payroll data available to store.');
        }

        return $this->handleTransaction(function () use ($business, $payrollData, $year, $month, $businessId, $locationId, $options) {
            $payroll = Payroll::where('payrun_year', $year)
                ->where('payrun_month', $month)
                ->where('business_id', $businessId)
                ->first();

            if ($payroll) {
                $payroll->update([
                    'location_id' => $locationId,
                    'payroll_type' => 'monthly',
                    'status' => 'open',
                    'staff' => count($payrollData),
                    'currency' => $business->currency ?? 'KES',
                ]);
            } else {
                $payroll = Payroll::create([
                    'payrun_year' => $year,
                    'payrun_month' => $month,
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'payroll_type' => 'monthly',
                    'status' => 'open',
                    'staff' => count($payrollData),
                    'currency' => $business->currency ?? 'KES',
                ]);
            }

            foreach ($payrollData as $data) {
                $paymentDetail = EmployeePaymentDetail::where('employee_id', $data['employee_id'])->first();
                if ($paymentDetail && $paymentDetail->payment_type === 'hourly') {
                    $paymentDetail->update(['basic_salary' => $data['basic_salary']]);
                    Log::info('Updated basic salary for hourly employee on payroll save', [
                        'employee_id' => $data['employee_id'],
                        'basic_salary' => $data['basic_salary']
                    ]);
                }
                $employeePayroll = EmployeePayroll::where('payroll_id', $payroll->id)
                    ->where('employee_id', $data['employee_id'])
                    ->first();

                $payrollAttributes = [
                    'payroll_id' => $payroll->id,
                    'employee_id' => $data['employee_id'],
                    'employee_payment_detail_id' => $paymentDetail ? $paymentDetail->id : null,
                    'basic_salary' => $data['basic_salary'],
                    'gross_pay' => $data['gross_pay'],
                    'overtime' => json_encode(['amount' => $data['overtime']]),
                    'allowances' => json_encode($data['allowances']),
                    'shif' => $data['shif'],
                    'nssf' => $data['nssf'],
                    'paye' => $data['paye'],
                    'paye_before_reliefs' => $data['paye_before_reliefs'],
                    'housing_levy' => $data['housing_levy'],
                    'helb' => $data['helb'],
                    'taxable_income' => $data['taxable_income'],
                    'reliefs' => json_encode($data['reliefs']),
                    'personal_relief' => $data['personal_relief'],
                    'insurance_relief' => $data['insurance_relief'],
                    'pay_after_tax' => $data['gross_pay'] - $data['paye'],
                    'loan_repayment' => $data['loan_repayment'],
                    'advance_recovery' => $data['advance_recovery'],
                    'deductions_after_tax' => $data['gross_pay'] - $data['paye'] - $data['net_pay'],
                    'net_pay' => $data['net_pay'],
                    'deductions' => json_encode($data['deductions']),
                    'bank_name' => $data['bank_name'],
                    'account_number' => $data['account_number'],
                    'attendance_present' => $data['attendance_present'],
                    'attendance_absent' => $data['attendance_absent'],
                    'days_in_month' => $data['days_in_month'],
                    'pwd_exemption_applied' => $data['pwd_exemption_applied'] ?? false,
                    'pwd_exemption_amount'  => $data['pwd_exemption_amount'] ?? 0,
                    // Pension fields for payslip display — stored so view can distinguish cash vs non-cash
                    'employee_pension'         => $data['employee_pension'] ?? 0,
                    'employer_pension'         => $data['employer_pension'] ?? 0,
                    'employer_pension_exempt'  => $data['employer_pension_exempt'] ?? 0,
                    'employer_pension_taxable' => $data['employer_pension_taxable'] ?? 0,
                    'taxable_gross'            => $data['taxable_gross'] ?? $data['gross_pay'],
                    'employee_currency'  => $data['employee_currency'] ?? 'KES',
                    'tax_currency'       => $data['tax_currency'] ?? 'KES',
                    'exchange_rate'      => $data['exchange_rate'] ?? 1.0,
                    'basic_salary_orig'  => $data['basic_salary_orig'] ?? $data['basic_salary'],
                    'gross_pay_orig'     => $data['gross_pay_orig'] ?? $data['gross_pay'],
                    'net_pay_orig'       => $data['net_pay_orig'] ?? $data['net_pay'],
                    'is_consultant'  => $data['is_consultant'] ?? false,
                    'wht_amount'     => $data['wht_amount'] ?? 0,
                    'wht_rate'       => $data['wht_rate'] ?? 0,
                ];

                if ($employeePayroll) {
                    $employeePayroll->update($payrollAttributes);
                } else {
                    EmployeePayroll::create($payrollAttributes);
                }

                $this->updateLoanAndAdvance($data, $year, $month, $options);
            }

            session()->forget('payroll_preview_data');

            return RequestResponse::ok('success', [
                'redirect_url' => route('business.payroll.view', ['business' => $business->slug, 'id' => $payroll->id]),
            ]);
        }, function ($e) use ($year, $month) {
            return RequestResponse::badRequest('Failed to process payroll: ' . $e->getMessage());
        });
    }

    public function addAdjustment(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $employee = Employee::findOrFail($request->employee_id);

        $options = $this->parseOptions($request);

        if ($request->allowances) {
            foreach ($request->allowances as $allowanceId) {
                $allowance = Allowance::find($allowanceId);
                if ($allowance) {
                    $amount = $allowance->amount;
                    EmployeeAllowance::updateOrCreate(
                        ['employee_id' => $employee->id, 'allowance_id' => $allowanceId],
                        ['amount' => $amount, 'is_active' => true]
                    );
                }
            }
        }

        if ($request->deductions) {
            foreach ($request->deductions as $deductionId) {
                $deduction = Deduction::find($deductionId);
                if ($deduction) {
                    $amount = $deduction->amount;
                    EmployeeDeduction::updateOrCreate(
                        ['employee_id' => $employee->id, 'deduction_id' => $deductionId],
                        ['amount' => $amount, 'is_active' => true]
                    );
                }
            }
        }

        if ($request->reliefs) {
            foreach ($request->reliefs as $reliefId) {
                $relief = Relief::find($reliefId);
                if ($relief) {
                    $amount = $relief->amount;
                    EmployeeRelief::updateOrCreate(
                        ['employee_id' => $employee->id, 'relief_id' => $reliefId],
                        ['amount' => $amount, 'is_active' => true]
                    );
                }
            }
        }

        if ($request->loans) {
            foreach ($request->loans as $loanId => $amount) {
                $loan = Loan::find($loanId);
                if ($loan && $amount > 0) {
                    $options['recover_loans']['specific'][$loanId] = min(floatval($amount), $loan->amount - $loan->repayments->sum('amount'));
                }
            }
        }

        if ($request->advances) {
            foreach ($request->advances as $advanceId => $amount) {
                $advance = Advance::find($advanceId);
                if ($advance && $amount > 0) {
                    $options['recover_advances']['specific'][$advanceId] = min(floatval($amount), $advance->amount);
                }
            }
        }

        if ($request->overtime) {
            foreach ($request->overtime as $overtimeId => $value) {
                $overtime = Overtime::find($overtimeId);
                if ($overtime && $value) {
                    $options['pay_overtime']['specific'][$overtimeId] = true;
                }
            }
        }

        $employee->load('employeeAllowances.allowance', 'employeeDeductions.deduction', 'loans', 'advances', 'overtimes');
        return RequestResponse::ok('success', [
            'allowances' => $employee->employeeAllowances->map(fn($ea) => $ea->allowance ? "{$ea->allowance->name} (" . number_format($ea->allowance->amount ?? 0, 2) . ")" : null)->filter()->toArray(),
            'deductions' => $employee->employeeDeductions->map(fn($ed) => $ed->deduction ? "{$ed->deduction->name} (" . number_format($ed->deduction->amount ?? 0, 2) . ")" : null)->filter()->toArray(),
            'loans' => $employee->loans->map(fn($l) => ['id' => $l->id, 'amount' => $l->amount, 'remaining' => $l->amount - $l->repayments->sum('amount')])->toArray(),
            'advances' => $employee->advances->map(fn($a) => ['id' => $a->id, 'date' => $a->date?->format('Y-m-d'), 'amount' => $a->amount])->toArray(),
            'overtimes' => $employee->overtimes->map(fn($o) => ['id' => $o->id, 'hours' => $o->overtime_hours, 'rate' => $o->rate, 'total_pay' => $o->total_pay])->toArray(),
            'options' => $options,
        ]);
    }

    protected function getFilteredEmployees(Request $request, Business $business)
    {
        $query = Employee::where('business_id', $business->id)
            ->with([
                'user',
                'location',
                'employmentDetails.department',
                'employmentDetails.jobCategory',
                'employeeAllowances.allowance',
                'employeeDeductions.deduction',
                'reliefs',
                'overtimes' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
                'advances' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
                'loans.repayments' => fn($q) => $q->where('start_date', '<=', Carbon::create($request->year, $request->month)->endOfMonth())
                    ->where('end_date', '>=', Carbon::create($request->year, $request->month)->startOfMonth()),
                'attendances' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
            ]);

        if ($request->location_id) {
            if (str_starts_with($request->location_id, 'business_')) {
                $query->whereNull('location_id');
            } else {
                $query->where('location_id', $request->location_id);
            }
        }
        if ($request->department_id) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }
        if ($request->job_category_id) {
            $query->whereHas('employmentDetails', function ($q) use ($request) {
                $q->where('job_category_id', $request->job_category_id);
            });
        }

        return $query->get();
    }

    public function getEmployeeAdjustments(Request $request)
    {
        $employeeId = $request->input('employee_id');
        if (!$employeeId) {
            return RequestResponse::badRequest('Employee ID is required.');
        }

        $employee = Employee::with(['loans.repayments', 'advances', 'overtimes'])
            ->findOrFail($employeeId);

        return RequestResponse::ok('Employee data fetched.', [
            'data' => [
                'loans' => $employee->loans->map(function ($loan) {
                    return [
                        'id' => $loan->id,
                        'amount' => $loan->amount ?? 0,
                        'repayments' => $loan->repayments->map(fn($r) => ['amount' => $r->amount ?? 0])->toArray(),
                    ];
                })->toArray(),
                'advances' => $employee->advances->map(function ($advance) {
                    return [
                        'id' => $advance->id,
                        'date' => $advance->date?->format('Y-m-d'),
                        'amount' => $advance->amount ?? 0,
                    ];
                })->toArray(),
                'overtimes' => $employee->overtimes->map(function ($overtime) {
                    return [
                        'id' => $overtime->id,
                        'date' => $overtime->date?->format('Y-m-d'),
                        'total_pay' => $overtime->total_pay ?? 0,
                    ];
                })->toArray(),
            ]
        ]);
    }

    protected function checkMissingData($employees)
    {
        $warnings = [];
        foreach ($employees as $employee) {
            $employeeWarnings = [];

            if (!$employee->paymentDetails) {
                $employeeWarnings[] = 'Missing payment details';
            }

            if ($employee->paymentDetails) {
                if (
                    $employee->paymentDetails->payment_type === 'salary' &&
                    floatval($employee->paymentDetails->basic_salary) == 0
                ) {
                    $employeeWarnings[] = 'Basic salary is 0';
                }

                if (
                    $employee->paymentDetails->payment_type === 'hourly' &&
                    floatval($employee->paymentDetails->hourly_rate) == 0
                ) {
                    $employeeWarnings[] = 'Hourly rate is 0';
                }
            }

            if (!$employee->tax_no) {
                $employeeWarnings[] = 'Missing KRA PIN';
            }

            if (!$employee->user || !$employee->user->email) {
                $employeeWarnings[] = 'Missing email';
            }

            if (!empty($employeeWarnings)) {
                $warnings[$employee->id] = [
                    'name' => $employee->user?->name ?? 'Unknown',
                    'employee_code' => $employee->employee_code ?? 'N/A',
                    'messages' => $employeeWarnings,
                ];
            }
        }
        return $warnings;
    }

    protected function parseOptions(Request $request)
    {
        $exempted = $request->input('exempted_employees', []);
        if (is_string($exempted)) {
            $exempted = json_decode($exempted, true) ?? [];
        }

        if (!is_array($exempted)) {
            $exempted = [];
        }

        $options = [
            'exempted_employees' => $exempted,
            'recover_advances' => [
                'apply' => $request->input('recover_advances', 'none'),
                'specific' => $request->input('recover_advances_specific', [])
            ],
            'recover_loans' => [
                'apply' => $request->input('recover_loans', 'none'),
                'specific' => $request->input('recover_loans_specific', [])
            ],
            'pay_overtime' => [
                'apply' => $request->input('pay_overtime', 'none'),
                'specific' => $request->input('pay_overtime_specific', [])
            ],
        ];

        foreach (['recover_advances', 'recover_loans', 'pay_overtime'] as $key) {
            if (!isset($options[$key]['apply']) || !is_string($options[$key]['apply'])) {
                $options[$key]['apply'] = 'none';
            }
            if (!isset($options[$key]['specific']) || !is_array($options[$key]['specific'])) {
                $options[$key]['specific'] = [];
            }
        }

        return $options;
    }

    public function preview(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $year = $request->year;
        $month = $request->month;
        $daysInMonth = $request->working_days;

        $employees = $this->getFilteredEmployees($request, $business);
        $options = $this->parseOptions($request);

        $nonExemptedEmployees = $employees->filter(function ($e) use ($options) {
            return !isset($options['exempted_employees'][$e->id]) || $options['exempted_employees'][$e->id] != 1;
        });

        $warnings = $this->checkMissingData($nonExemptedEmployees);

        if (!empty($warnings)) {
            return response()->json([
                'message' => 'Resolve warnings before previewing.',
                'type' => 'warnings',
                'warnings' => $warnings
            ], 400);
        }

        $payrollSettings = PayrollSettings::where('year', $year)
            ->where('month', $month)
            ->whereIn('employee_id', $nonExemptedEmployees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $options['payroll_settings'] = $payrollSettings->mapWithKeys(function ($setting) {
            return [
                $setting->employee_id => [
                    'allowances' => $setting->allowances ?? [],
                    'deductions' => $setting->deductions ?? [],
                    'reliefs' => $setting->reliefs ?? [],
                    'overtime' => $setting->overtime ?? [],
                    'loans' => $setting->loans ?? [],
                    'advances' => $setting->advances ?? [],
                    'absenteeism_charge' => $setting->absenteeism_charge ?? 0,
                ],
            ];
        })->toArray();

        $payrollData = $this->calculatePayroll($nonExemptedEmployees, $year, $month, $options, $daysInMonth);

        session([
            'payroll_preview_data' => [
                'payroll_data' => $payrollData,
                'year' => $year,
                'month' => $month,
                'business_id' => $business->id,
                'location_id' => str_starts_with($request->location_id, 'business_') ? null : $request->location_id,
                'options' => $options,
                'working_days' => $daysInMonth,
                'non_exempted_employee_ids' => $nonExemptedEmployees->pluck('id')->toArray(),
            ]
        ]);

        return RequestResponse::ok('success', [
            'html' => view('payroll._preview', ['payrollData' => array_values($payrollData), 'options' => $options])->render(),
            'options' => $options,
        ]);
    }


    protected function calculatePayroll($employees, $year, $month, $options, $daysInMonth)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || !$business->country) {
            throw new Exception('Business or country not found.');
        }

        $payrollData = [];
        $period = Carbon::create($year, $month);

        foreach ($employees as $employee) {
            $employeeId = $employee->id;
            $settings = $options['payroll_settings'][$employeeId] ?? null;

            $paymentDetail = EmployeePaymentDetail::where('employee_id', $employeeId)->first();

            if (!$paymentDetail) {
                Log::warning("No payment details found for employee {$employeeId}. Skipping.");
                continue;
            }

            // ── Currency setup (needed by BOTH consultant and regular paths) ──
            $payrollDetail    = EmployeePayrollDetail::where('employee_id', $employeeId)->first();
            $taxCurrency      = match (strtoupper(trim($business->country ?? 'KENYA'))) {
                'UGANDA', 'UG' => 'UGX',
                default        => 'KES',
            };
            $employeeCurrency = strtoupper(trim($paymentDetail->currency ?? $taxCurrency));
            $needsConversion  = ($employeeCurrency !== $taxCurrency);
            $exchangeRate     = $needsConversion
                ? app(CurrencyService::class)->getBusinessRate($business, $employeeCurrency, $taxCurrency)
                : 1.0;
            $toTax = fn(float $amount): float => $needsConversion
                ? round($amount * $exchangeRate, 2)
                : $amount;

            $bankName      = $paymentDetail->bank_name      ?? 'N/A';
            $accountNumber = $paymentDetail->account_number ?? 'N/A';

            // ── IS THIS A CONSULTANT? ────────────────────────────────────────
            $isConsultant = (bool) ($paymentDetail->is_consultant ?? false);

            if ($isConsultant) {
                $grossPay  = $toTax(floatval($paymentDetail->basic_salary ?? 0));
                $whtService = app(\App\Services\WhtCalculationService::class);
                $whtResult  = $whtService->calculate($grossPay, $paymentDetail);

                \App\Models\WithholdingPayment::updateOrCreate(
                    [
                        'business_id'  => $business->id,
                        'employee_id'  => $employeeId,
                        'payment_date' => \Carbon\Carbon::create($year, $month)->endOfMonth()->toDateString(),
                    ],
                    [
                        'payment_type'       => $paymentDetail->wht_payment_type ?? 'professional_fees',
                        'residency'          => $paymentDetail->wht_residency    ?? 'resident',
                        'gross_amount'       => $whtResult['gross_amount'],
                        'wht_rate'           => $whtResult['wht_rate'],
                        'wht_amount'         => $whtResult['wht_amount'],
                        'net_amount'         => $whtResult['net_to_consultant'],
                        'shif_company_cost'  => $whtResult['shif_deduction'],  // remitted by company
                        'nssf_company_cost'  => $whtResult['nssf_deduction'],  // remitted by company
                        'total_company_cost' => $whtResult['total_company_cost'],
                        'currency'           => $paymentDetail->currency ?? 'KES',
                    ]
                );

                $payrollData[$employeeId] = [
                    'employee_id'         => $employeeId,
                    'employee'            => $employee,
                    'basic_salary'        => $grossPay,
                    'gross_pay'           => $grossPay,
                    'taxable_gross'       => $grossPay,
                    'taxable_income'      => $grossPay,
                    'overtime'            => 0,
                    'allowances'          => [],
                    'reliefs'             => [],
                    'is_consultant'       => true,
                    'wht_amount'          => $whtResult['wht_amount'],
                    'wht_rate'            => $whtResult['wht_rate'],
                    'wht_payment_type'    => $paymentDetail->wht_payment_type,
                    'is_final_tax'        => $whtResult['is_final_tax'],

                    // WHT replaces PAYE
                    'paye'                => 0,
                    'paye_before_reliefs' => 0,
                    'personal_relief'     => 0,
                    'insurance_relief'    => 0,

                    // SHIF and NSSF deducted FROM consultant (not zero)
                    'shif'                => $whtResult['shif_deduction'],
                    'nssf'                => $whtResult['nssf_deduction'],
                    'nssf_employee'       => $whtResult['nssf_deduction'],
                    'nssf_employer'       => 0,

                    'housing_levy'        => 0,  // not applicable for consultants
                    'helb'                => 0,  // not applicable for consultants
                    'loan_repayment'      => 0,
                    'advance_recovery'    => 0,

                    // Deductions array for payslip display
                    'deductions'          => array_filter([
                        ['name' => 'Withholding Tax', 'amount' => $whtResult['wht_amount']],
                        $whtResult['shif_deduction'] > 0
                            ? ['name' => 'SHIF', 'amount' => $whtResult['shif_deduction']]
                            : null,
                        $whtResult['nssf_deduction'] > 0
                            ? ['name' => 'NSSF', 'amount' => $whtResult['nssf_deduction']]
                            : null,
                    ]),

                    // Net = gross − WHT − SHIF − NSSF
                    'net_pay'             => floor($whtResult['net_to_consultant']),

                    // Company cost tracking (for reports)
                    'shif_company_cost'   => $whtResult['shif_deduction'],
                    'nssf_company_cost'   => $whtResult['nssf_deduction'],
                    'total_company_cost'  => $whtResult['total_company_cost'],

                    'bank_name'           => $bankName,
                    'account_number'      => $accountNumber,
                    'attendance_present'  => $daysInMonth,
                    'attendance_absent'   => 0,
                    'days_in_month'       => $daysInMonth,
                    'currency'            => $paymentDetail->currency ?? 'KES',
                    'payment_mode'        => $paymentDetail->payment_mode ?? 'N/A',
                    'employee_currency'   => $employeeCurrency,
                    'tax_currency'        => $taxCurrency,
                    'exchange_rate'       => $exchangeRate,
                    'needs_conversion'    => $needsConversion,
                    'basic_salary_orig'   => $needsConversion
                        ? round(floatval($paymentDetail->basic_salary ?? 0), 2)
                        : $grossPay,
                    'gross_pay_orig'      => $needsConversion
                        ? round($grossPay / $exchangeRate, 2)
                        : $grossPay,
                    'net_pay_orig'        => $needsConversion
                        ? round($whtResult['net_to_consultant'] / $exchangeRate, 2)
                        : floor($whtResult['net_to_consultant']),
                    'employee_pension'       => 0,
                    'employer_pension'       => 0,
                    'employer_pension_exempt'  => 0,
                    'employer_pension_taxable' => 0,
                    'mortgage_pre_tax'       => 0,
                    'country'                => strtoupper(trim($business->country)),
                    'pwd_exemption_applied'  => false,
                    'pwd_exemption_amount'   => 0,
                ];

                continue;
            }

            // ── END CONSULTANT BRANCH ────────────────────────────────────────

            // Remove the old duplicate lines below (they existed before the consultant check)
            // $payrollDetail = ...   ← already defined above
            // $taxCurrency = ...     ← already defined above
            // $employeeCurrency = .. ← already defined above
            // $needsConversion = ... ← already defined above
            // $exchangeRate = ...    ← already defined above
            // $toTax = ...           ← already defined above
            // if (!$paymentDetail)   ← already handled above
            // $bankName = ...        ← already defined above
            // $accountNumber = ...   ← already defined above

            // $payrollDetail = EmployeePayrollDetail::where('employee_id', $employeeId)->first();
            // $taxCurrency = match (strtoupper(trim($business->country ?? 'KENYA'))) {
            //     'UGANDA', 'UG' => 'UGX',
            //     default         => 'KES',
            // };
            // $employeeCurrency = strtoupper(trim($paymentDetail->currency ?? $taxCurrency));
            // $needsConversion  = ($employeeCurrency !== $taxCurrency);
            // $exchangeRate     = $needsConversion
            //     ? app(CurrencyService::class)->getBusinessRate($business, $employeeCurrency, $taxCurrency)
            //     : 1.0;
            // $toTax = fn(float $amount): float => $needsConversion ? round($amount * $exchangeRate, 2) : $amount;

            // if (!$paymentDetail) {
            //     Log::warning("No payment details found for employee {$employeeId}. Skipping.");
            //     continue;
            // }

            // $bankName = $paymentDetail->bank_name ?? 'N/A';
            // $accountNumber = $paymentDetail->account_number ?? 'N/A';
            // $bankCode = $paymentDetail->bank_code ?? 'Not Set';
            // $bankBranch = $paymentDetail->bank_branch ?? 'Not Set';

            if ($paymentDetail->payment_type === 'hourly') {

                Log::debug("HOURLY payroll branch hit", [
                    'employee_id' => $employeeId,
                    'payment_type' => $paymentDetail->payment_type
                ]);

                $calculator = app(\App\Services\HourlyPayCalculator::class);

                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                try {
                    $hourlyPayData = $calculator->calculateHourlyGrossPay(
                        $employee,
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d')
                    );

                    if (!isset($hourlyPayData['regular_pay']) || !isset($hourlyPayData['overtime_pay'])) {
                        throw new \Exception('Invalid hourly pay data structure returned from calculator');
                    }

                    $proratedBasicSalary = $hourlyPayData['regular_pay'];
                    $actualHourlyRate = floatval($paymentDetail->hourly_rate ?? 0);
                   $overtimePay = $toTax(
    $this->calculateOvertime(
        $employeeId,
        $year,
        $month,
        $settings,
        $options,
        $proratedBasicSalary,
        $actualHourlyRate      // pass hourly_rate directly
    )
);
                    $proratedBasicSalary = $toTax($proratedBasicSalary);
                    $overtimePay         = $toTax($overtimePay);

                    $paymentDetail->update(['basic_salary' => $proratedBasicSalary]);

                    Log::info('Updated basic_salary for hourly employee during calculation', [
                        'employee_id' => $employeeId,
                        'calculated_basic_salary' => $proratedBasicSalary,
                        'hours_worked' => $hourlyPayData['hours_worked'],
                    ]);

                    $allowances = [];

                  $allowances['hourly_breakdown'] = [
    'name'           => 'Hourly Pay Breakdown',
    'hours_worked'   => $hourlyPayData['hours_worked'],
    'hourly_rate'    => $hourlyPayData['hourly_rate'],
    'overtime_hours' => $hourlyPayData['overtime_hours'] ?? 0,
    'overtime_rate'  => $hourlyPayData['overtime_rate'] ?? 1.5,
    'regular_pay'    => $hourlyPayData['regular_pay'],
    'overtime_pay'   => $overtimePay,  // now the real money value
];

                    $attendanceSummary = $calculator->getAttendanceSummary(
                        $employee,
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d')
                    );

                    $presentDays = $attendanceSummary['present_days'];
                    $absentDays = $attendanceSummary['absent_days'];
                    $absenteeismCharge = 0;

                    $additionalAllowances = $this->getEmployeeItems(
                        $employee,
                        'allowances',
                        $settings,
                        Allowance::class,
                        EmployeeAllowance::class,
                        $proratedBasicSalary
                    );

                    $allowances = array_merge($allowances, $additionalAllowances);

                    $totalTaxableAllowances = array_sum(array_map(
                        fn($a) => ($a['is_taxable'] ?? false) ? ($a['amount'] ?? 0) : 0,
                        $allowances
                    ));
                    $totalNonTaxableAllowances = array_sum(array_map(
                        fn($a) => !($a['is_taxable'] ?? false) ? ($a['amount'] ?? 0) : 0,
                        $allowances
                    ));

                    $grossPayBeforeAbsenteeism = $proratedBasicSalary + $overtimePay + $totalTaxableAllowances + $totalNonTaxableAllowances;
                } catch (\Exception $e) {
                    Log::error("Hourly pay calculation failed", [
                        'employee_id' => $employeeId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $proratedBasicSalary = 0;
                    $overtimePay = 0;
                    $presentDays = 0;
                    $absentDays = $daysInMonth;
                    $absenteeismCharge = 0;
                    $allowances = [];
                    $totalTaxableAllowances = 0;
                    $totalNonTaxableAllowances = 0;
                    $grossPayBeforeAbsenteeism = 0;
                }
            } else {
                // ========== SALARY-BASED CALCULATION ==========
                // $basicSalary = floatval($paymentDetail->basic_salary ?? 0);
                $basicSalary = $toTax(floatval($paymentDetail->basic_salary ?? 0));

                $presentDays = $daysInMonth;
                $absentDays = $daysInMonth - $presentDays;
                $proratedBasicSalary = $basicSalary * ($presentDays / $daysInMonth);

                $dailyRate = $basicSalary / $daysInMonth;
                $absenteeismCharge = $settings && isset($settings['absenteeism_charge'])
                    ? floatval($settings['absenteeism_charge'])
                    : ($dailyRate * $absentDays);

                $allowances = $this->getEmployeeItems(
                    $employee,
                    'allowances',
                    $settings,
                    Allowance::class,
                    EmployeeAllowance::class,
                    $proratedBasicSalary
                );

                // Cash taxable allowances only — exclude employer contributions (non-cash).
                // Employer pension is handled separately via $taxableGross for PAYE purposes.
                $totalTaxableAllowances = array_sum(array_map(
                    fn($a) => (($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false))
                        ? floatval($a['amount']) : 0,
                    $allowances
                ));
                // Non-taxable, non-employer-contribution allowances (cash)
                $totalNonTaxableAllowances = array_sum(array_map(
                    fn($a) => (!($a['is_taxable'] ?? false) && !($a['is_employer_contribution'] ?? false))
                        ? floatval($a['amount']) : 0,
                    $allowances
                ));

                $overtimePay = $this->calculateOvertime(
                    $employeeId,
                    $year,
                    $month,
                    $settings,
                    $options,
                    $proratedBasicSalary
                );

                // grossPay is CASH only — employer pension (non-cash) is never included here.
                // $taxableGross (computed later) adds the taxable excess for PAYE purposes only.
                $grossPayBeforeAbsenteeism = $proratedBasicSalary + $totalTaxableAllowances + $totalNonTaxableAllowances + $overtimePay;
            }

            // ========== COMMON CALCULATION (both salary and hourly) ==========
            $grossPay = max(0, $grossPayBeforeAbsenteeism - $absenteeismCharge);

            $country = strtoupper(trim($business->country));
            $statutoryDeductions = $this->getStatutoryDeductions($country, $business->id, $grossPay, $proratedBasicSalary, $employeeId, null);

            $nssfEmployee = $statutoryDeductions['nssf']['employee'] ?? 0;
            $nssfEmployer = $statutoryDeductions['nssf']['employer'] ?? 0;
            $nssfTotal = $statutoryDeductions['nssf']['total'] ?? ($nssfEmployee + $nssfEmployer);

            // ========== PWD defaults (set before country branching) ==========
            $pwdExemptionApplied = false;
            $pwdExemptionAmount  = 0;

            if ($country === 'UGANDA' || $country === 'UG') {
                // ========== UGANDA PAYROLL LOGIC ==========
                $shif = 0;
                $housingLevy = 0;
                $helb = 0;

                $taxableIncome = max(0, $grossPay);

                $reliefs = [];
                $totalReliefs = 0;
                $personalRelief = 0;
                $insuranceRelief = 0;

                // Get deductions for Uganda
                $deductions = $this->getEmployeeItems(
                    $employee,
                    'deductions',
                    $settings,
                    Deduction::class,
                    EmployeeDeduction::class,
                    $grossPay
                );

                Log::debug("Uganda payroll calculation", [
                    'employee_id' => $employeeId,
                    'gross_pay' => $grossPay,
                    'nssf_employee' => $nssfEmployee,
                    'taxable_income' => $taxableIncome,
                ]);
            } else {
                // ========== KENYA PAYROLL LOGIC ==========
                // KRA-confirmed rules (verified against payslip):
                //
                // GROSS PAY for tax = basic + taxable allowances + employer pension (if flagged taxable)
                //   - Employer pension IS added to taxable gross (KRA treats it as a benefit in kind
                //     unless exempt under a registered scheme — flag via is_employer_contribution on allowance)
                //
                // TAXABLE INCOME = Gross Pay
                //   − SHIF (2.75%, no cap)
                //   − Housing Levy (1.5%, no cap)
                //   − HELB (if applicable)
                //   − Retirement relief cap: max 30,000/month TOTAL covering BOTH employee pension
                //     AND NSSF combined (KRA treats them as one retirement bucket)
                //   − Mortgage interest (up to 30,000/month)
                //
                // NOTE: NSSF is NOT separately deducted from taxable income — it is absorbed
                // inside the 30,000 retirement cap above.
                //
                // PWD exemption: first 150,000 of taxable income is exempt (applied after above).
                //
                // POST-TAX deductions (reduce net pay only, not taxable income):
                //   − PAYE (after reliefs)
                //   − NSSF employee share (6,480) — cash deduction from net
                //   − Custom deductions (Sacco, loans, advances, etc.)

                $shif        = $statutoryDeductions['shif'] ?? 0;
                $housingLevy = $statutoryDeductions['housing-levy'] ?? 0;
                $helb        = $statutoryDeductions['helb'] ?? 0;

                // ── STEP 1: Fetch deductions early (pension extraction) ─────────
                $deductions = $this->getEmployeeItems(
                    $employee,
                    'deductions',
                    $settings,
                    Deduction::class,
                    EmployeeDeduction::class,
                    $grossPay
                );

                // ── STEP 2: Fetch reliefs early (mortgage extraction) ──────────
                $reliefs = $this->getEmployeeItems(
                    $employee,
                    'reliefs',
                    $settings,
                    Relief::class,
                    EmployeeRelief::class,
                    $grossPay
                );

                // ── STEP 3: Extract pension from deductions ───────────────────
                // Pension is a Deduction. fraction_to_consider determines if employer
                // also contributes:
                //   'employee_only'        → employee side only
                //   'employee_and_employer' → both sides contribute equally (same rate/amount)
                //
                // computation_method = 'rate'  → employee share = basic * rate/100
                // computation_method = 'fixed' → employee share = fixed amount
                //
                // Employer pension IS a taxable benefit in kind per KRA —
                // it must be added to grossPay for tax purposes.


                // $employeePension = 0;
                // $employerPension = 0;

                // foreach ($deductions as $slug => $deductionData) {
                //     $deductionModel = Deduction::where('business_id', $business->id)
                //         ->where('slug', $slug)->first();
                //     if (!$deductionModel) continue;
                //     if (!str_contains(strtolower($deductionModel->name), 'pension')) continue;

                //     $employeeRate  = floatval($deductionModel->rate ?? 0);
                //     $employeeLimit = $deductionModel->limit !== null
                //         ? floatval($deductionModel->limit)
                //         : PHP_FLOAT_MAX;

                //     if ($employeeRate > 0) {
                //         $rawEmployeeAmount = round($proratedBasicSalary * ($employeeRate / 100), 2);
                //     } else {
                //         $rawEmployeeAmount = floatval($deductionData['amount']);
                //     }

                //     $employeeShare = min($rawEmployeeAmount, $employeeLimit);
                //     $deductions[$slug]['amount'] = $rawEmployeeAmount;
                //     $employeePension += $employeeShare;

                //     if ($deductionModel->hasEmployerContribution()) {
                //         $employerRate = $deductionModel->resolvedEmployerRate();

                //         $rawEmployerAmount = $employerRate > 0
                //             ? round($proratedBasicSalary * ($employerRate / 100), 2)
                //             : $rawEmployeeAmount; // fixed-amount fallback

                //         $employerPension += $rawEmployerAmount;
                //     }

                //     Log::debug('Pension deduction detected', [
                //         'slug'                   => $slug,
                //         'name'                   => $deductionModel->name,
                //         'employee_rate'          => $employeeRate,
                //         'employee_limit'         => $employeeLimit === PHP_FLOAT_MAX ? 'none' : $employeeLimit,
                //         'raw_employee_cash'      => $rawEmployeeAmount,
                //         'employee_share_capped'  => $employeeShare,
                //         'fraction_to_consider'   => $deductionModel->fraction_to_consider,
                //         'employer_rate_db'       => $deductionModel->employer_rate,
                //         'employer_rate_resolved' => $deductionModel->hasEmployerContribution()
                //             ? $deductionModel->resolvedEmployerRate() : 'n/a',
                //         'raw_employer_amount'    => $deductionModel->hasEmployerContribution()
                //             ? $rawEmployerAmount : 0,
                //         'employer_pension_total' => $employerPension,
                //     ]);
                // }
                // ── STEP 3: Extract pension — Use saved value from settings (NO re-calculation from model) ───────────────────
                $employeePension = 0;
                $employerPension = 0;

                foreach ($deductions as $slug => $deductionData) {
                    if (!str_contains(strtolower($deductionData['name'] ?? ''), 'pension')) {
                        continue;
                    }

                    // Use the amount that was already computed in getEmployeeItems()
                    $pensionAmount = floatval($deductionData['amount'] ?? 0);

                    // If we have a saved rate, trust it and recalculate cleanly
                    $savedRate = floatval($deductionData['rate'] ?? 0);
                    if ($savedRate > 0) {
                        $pensionAmount = round($proratedBasicSalary * ($savedRate / 100), 2);
                    }

                    $employeePension += $pensionAmount;

                    // Simple employer contribution (if configured on the deduction model)
                    $deductionModel = Deduction::where('slug', $slug)
                        ->where('business_id', $business->id)
                        ->first();

                    if ($deductionModel && $deductionModel->hasEmployerContribution()) {
                        $employerRate = $deductionModel->resolvedEmployerRate() ?? $savedRate;
                        $employerPension += round($proratedBasicSalary * ($employerRate / 100), 2);
                    }

                    Log::debug('Pension final calculation', [
                        'slug' => $slug,
                        'saved_rate' => $savedRate,
                        'used_amount' => $pensionAmount,
                        'employee_pension' => $employeePension,
                        'employer_pension' => $employerPension,
                    ]);
                }

                // ── STEP 4: Add ONLY the taxable excess of employer pension to gross pay ──
                // KRA rule:
                //   • Employer pension up to 30,000/month is EXEMPT — not added to gross pay
                //   • Employer pension ABOVE 30,000 is a taxable benefit in kind — only that
                //     excess is added to gross pay for tax calculation
                //   • The exempt 30,000 is NOT added to gross and NOT relieved again later
                //     (it simply never enters the tax base)
                //   • SHIF and Housing Levy are on cash gross only — employer pension excess
                //     is non-cash, so we must NOT inflate the SHIF/HL base by it
                //
                // Example: employer pension = 45,000
                //   exempt portion  = min(45,000, 30,000) = 30,000  ← stays out of gross
                //   taxable excess  = 45,000 − 30,000 = 15,000      ← added to gross for tax
                //   SHIF/HL base    = original cash gross (unchanged)

                // Also capture any employer pension that may be stored as an Allowance
                // (is_employer_contribution=true). These were excluded from $grossPay above.
                // Their taxable excess must still inflate $taxableGross for PAYE.
                $employerPensionFromAllowances = array_sum(array_map(
                    fn($a) => ($a['is_employer_contribution'] ?? false) ? floatval($a['amount']) : 0,
                    $allowances
                ));
                // Merge with pension from deductions (fraction_to_consider)
                $employerPension += $employerPensionFromAllowances;

                $employerPensionExempt  = min($employerPension, 30000);
                $employerPensionTaxable = max(0, $employerPension - $employerPensionExempt);

                // $grossPay stays as CASH gross — never modified by non-cash employer pension.
                // $taxableGross is used ONLY for PAYE calculation (includes non-cash taxable excess).
                // SHIF and Housing Levy use $grossPay (cash only) — unaffected.
                // Net pay uses $grossPay directly — no need to subtract the excess back out.
                $taxableGross = $grossPay + $employerPensionTaxable;

                // ── STEP 5: KRA retirement relief ──────────────────────────────
                //
                // BUCKET A — NSSF + Employee pension, capped at 30,000/month
                //   • Reduces taxable income (post-bracket deduction)
                //
                // BUCKET B — Employer pension EXEMPT portion only, already excluded from gross
                //   • The exempt 30k never entered gross pay, so Bucket B = 0 here.
                //   • There is nothing to relieve — it was never taxed.
                //   • Only the taxable excess (above 30k) entered gross, and that excess
                //     has no retirement relief — it is fully taxable.
                //
                // Formula:
                //   Bucket A = min(NSSF + employee_pension, 30,000)
                //   Bucket B = 0  (exempt portion never entered gross)
                //   Total retirement relief = Bucket A only

                // Bucket A: NSSF + employee pension, cap 30k
                $bucketA = min($nssfTotal + $employeePension, 30000);

                // Bucket B: 0 — employer exempt portion never entered gross pay
                $bucketB = 0;

                $totalRetirementRelief = $bucketA + $bucketB;

                // ── STEP 6: Extract mortgage interest (pre-tax) ────────────────
                // Source priority:
                //   1. employee_payroll_details.mortgage_interest — set on employee profile
                //   2. employee_reliefs entry whose Relief name contains 'mortgage'
                $mortgagePreTax = 0;

                // Priority 1: employee_payroll_details
                if ($payrollDetail && $payrollDetail->has_mortgage && floatval($payrollDetail->mortgage_interest ?? 0) > 0) {
                    $mortgagePreTax = floatval($payrollDetail->mortgage_interest);
                }

                // Priority 2: reliefs system (only if not already found above)
                if ($mortgagePreTax == 0) {
                    foreach ($reliefs as $slug => $reliefData) {
                        $reliefModel = Relief::where('business_id', $business->id)
                            ->where('slug', $slug)->first();
                        if (!$reliefModel) continue;
                        if (str_contains(strtolower($reliefModel->name), 'mortgage')) {
                            $mortgagePreTax += floatval($reliefData['amount']);
                        }
                    }
                }

                // KRA cap: 30,000/month
                $mortgagePreTax = min($mortgagePreTax, 30000);

                // ── STEP 7: Correct taxable income ─────────────────────────────
                // grossPay already includes employer pension (added as taxable allowance).
                // Subtract SHIF, Housing Levy, HELB, total retirement relief, mortgage.
                // NSSF is inside both buckets — NOT separately subtracted.
                // No excess is added back — excess is simply not relieved.
                $taxableIncome = max(
                    0,
                    $taxableGross           // cash gross + employer pension taxable excess
                        - $shif
                        - $housingLevy
                        - $helb
                        - $totalRetirementRelief
                        - $mortgagePreTax
                );

                // Alias for logging
                $pensionPreTax = $totalRetirementRelief;

                // ── STEP 6: PWD tax exemption ───────────────────────────────────
                if ($payrollDetail && $payrollDetail->has_disability_exemption) {
                    $pwdMonthlyLimit    = floatval($payrollDetail->pwd_exemption_limit ?? 150000);
                    $pwdExemptionAmount = min($taxableIncome, $pwdMonthlyLimit);
                    $taxableIncome      = max(0, $taxableIncome - $pwdExemptionAmount);
                    $pwdExemptionApplied = true;

                    Log::info('PWD tax exemption applied', [
                        'employee_id'      => $employeeId,
                        'cert_no'          => $payrollDetail->pwd_certificate_no,
                        'exemption_amount' => $pwdExemptionAmount,
                        'taxable_after'    => $taxableIncome,
                    ]);
                }

                // ── STEP 7: Ensure personal relief exists ───────────────────────
                if (!isset($reliefs['personal-relief']) || $reliefs['personal-relief']['amount'] == 0) {
                    $reliefs['personal-relief'] = [
                        'name'            => 'Personal Relief',
                        'amount'          => 2400,
                        'is_taxable'      => false,
                        'tax_application' => 'before_tax',
                    ];
                }

                // ── STEP 8: Process all reliefs ─────────────────────────────────
                // If mortgage came from employee_payroll_details (not reliefs system),
                // inject it into $reliefs for display purposes on the payslip.
                if ($mortgagePreTax > 0 && !collect($reliefs)->contains(fn($r) => str_contains(strtolower($r['name'] ?? ''), 'mortgage'))) {
                    $reliefs['mortgage-interest-relief'] = [
                        'name'           => 'Mortgage Interest Relief',
                        'amount'         => $mortgagePreTax, // shown on payslip (display value)
                        'display_amount' => $mortgagePreTax,
                        'is_taxable'     => false,
                        'is_pre_tax'     => true,            // excluded from totalReliefs — deducted pre-tax
                        'tax_application' => 'before_tax',
                        '_from_db'       => false,           // flag: no Relief model in DB for this
                    ];
                }

                $totalReliefs = 0;

                foreach ($reliefs as $reliefSlug => $reliefData) {
                    // Handle manually injected entries (e.g. mortgage from employee_payroll_details)
                    // These have no Relief model in DB — amount is already set correctly for display.
                    // is_pre_tax = true means it was deducted in taxable income — skip totalReliefs.
                    if (!empty($reliefData['is_pre_tax']) || ($reliefSlug === 'mortgage-interest-relief' && isset($reliefData['_from_db']) && !$reliefData['_from_db'])) {
                        // amount already set — just skip adding to totalReliefs
                        continue;
                    }

                    $reliefModel = Relief::where('business_id', $business->id)
                        ->where('slug', $reliefSlug)->first();
                    if (!$reliefModel) continue;

                    $amount             = floatval($reliefData['amount']);
                    $computationMethod  = $reliefModel->computation_method;
                    $percentageOfAmount = floatval($reliefModel->percentage_of_amount ?? 0);
                    $limit              = floatval($reliefModel->limit ?? PHP_FLOAT_MAX);

                    $baseForPercentage = match ($reliefModel->percentage_of ?? 'total_salary') {
                        'basic_salary' => $proratedBasicSalary,
                        'net_salary'   => $grossPay - ($nssfTotal + $shif + $housingLevy + $helb),
                        'total_salary' => $grossPay,
                        default        => $grossPay,
                    };

                    $computedRelief = match ($computationMethod) {
                        'percentage' => min($baseForPercentage * ($percentageOfAmount / 100), $limit),
                        default      => min($amount, $limit),
                    };

                    // Apply KRA-specific caps / rules per relief type
                    if ($reliefSlug === 'personal-relief') {
                        // Fixed KRA monthly personal relief
                        $computedRelief = 2400;
                    } elseif ($reliefSlug === 'insurance-relief') {
                        // KRA cap: 15% of premium, max 5,000/month
                        $computedRelief = min($computedRelief, 5000);
                    } elseif ($reliefSlug === 'disabled-person-relief') {
                        // KRA: 25,000/month tax CREDIT (not a full exemption)
                        $computedRelief = 25000;
                    } elseif (str_contains(strtolower($reliefModel->name), 'mortgage')) {
                        // Mortgage is deducted pre-tax in taxable income formula.
                        // Keep the display amount intact so the payslip shows it correctly.
                        // Do NOT add to totalReliefs — that would double-count it.
                        $reliefs[$reliefSlug]['amount'] = $computedRelief; // shown on payslip
                        $reliefs[$reliefSlug]['display_amount'] = $computedRelief;
                        // Skip totalReliefs increment for mortgage
                        $reliefs[$reliefSlug]['is_pre_tax'] = true;
                        continue; // <-- skip the totalReliefs += below
                    }

                    $reliefs[$reliefSlug]['display_amount'] = $computedRelief;
                    $reliefs[$reliefSlug]['amount'] = $computedRelief;
                    $totalReliefs += $computedRelief;
                }

                $personalRelief  = $reliefs['personal-relief']['amount']  ?? 2400;
                $insuranceRelief = $reliefs['insurance-relief']['amount'] ?? 0;
            }

            // ========== PAYE CALCULATION ==========
            $payeBeforeReliefs = $this->calculatePAYEByCountry($country, $taxableIncome, $grossPay);

            if ($country === 'UGANDA' || $country === 'UG') {
                $paye = $payeBeforeReliefs;
            } else {
                $paye = max(0, $payeBeforeReliefs - $totalReliefs);
            }

            // ========== CUSTOM DEDUCTIONS, LOANS, ADVANCES ==========
            $totalCustomDeductions = array_sum(array_map(fn($d) => $d['amount'], $deductions));

            $loanRepayment = $this->calculateLoanRepayment($employeeId, $year, $month, $settings, $options);
            $advanceRecovery = $this->calculateAdvanceRecovery($employeeId, $year, $month, $settings, $options);

            // ========== NET PAY ==========
            if ($country === 'UGANDA' || $country === 'UG') {
                $totalDeductions = $nssfEmployee + $paye + $totalCustomDeductions + $loanRepayment + $advanceRecovery + $absenteeismCharge;
                $netPay = floor(max(0, $grossPay - $totalDeductions));

                Log::debug("Uganda Net Pay Calculation", [
                    'gross_pay' => $grossPay,
                    'nssf_employee' => $nssfEmployee,
                    'paye' => $paye,
                    'custom_deductions' => $totalCustomDeductions,
                    'loans' => $loanRepayment,
                    'advances' => $advanceRecovery,
                    'absenteeism' => $absenteeismCharge,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                ]);
            } else {
                // Kenya net pay:
                // Start from gross pay (includes employer pension if taxable benefit).
                // Subtract all CASH deductions:
                //   − NSSF employee share (cash out of pocket, even though it's inside the 30k retirement cap for tax)
                //   − SHIF
                //   − Housing Levy
                //   − HELB
                //   − PAYE (after reliefs)
                //   − Employee pension (cash deduction — already in $totalCustomDeductions via $deductions)
                //   − Other custom deductions (Sacco, loans, advances, absenteeism)
                // NOTE: employer pension is a NON-CASH benefit — it does NOT reduce net pay,
                // but it IS added to gross pay for tax. So we must subtract it back here.
                // Net pay uses $grossPay (pure cash gross — never inflated by employer pension).
                // No need to subtract any non-cash pension amount here.
                $afterTaxDeductions = $totalCustomDeductions + $loanRepayment + $advanceRecovery + $absenteeismCharge;
                $netPay = floor(max(
                    0,
                    $grossPay
                        - $nssfTotal               // NSSF employee share (cash) — formula returns employee-only amount
                        - $shif                    // SHIF (cash)
                        - $housingLevy             // Housing Levy (cash)
                        - $helb                    // HELB (cash)
                        - $paye                    // PAYE after reliefs (calculated on $taxableGross)
                        - $afterTaxDeductions      // pension, sacco, loans, advances, absenteeism
                ));
                $totalDeductions = $nssfEmployee + $shif + $housingLevy + $helb + $paye + $afterTaxDeductions;

                Log::debug("Kenya Net Pay Calculation", [
                    'gross_pay'               => $grossPay,
                    'taxable_gross'           => $taxableGross,
                    'employer_pension_exempt'  => $employerPensionExempt,
                    'employer_pension_taxable' => $employerPensionTaxable,
                    'nssf_total'              => $nssfTotal,
                    'shif'                    => $shif,
                    'housing_levy'            => $housingLevy,
                    'helb'                    => $helb,
                    'bucket_a_nssf_emp_pen'   => $bucketA,   // NSSF + employee pension, cap 30k
                    'bucket_b_er_pen_only'    => $bucketB,   // employer pension only, cap 30k (no NSSF)
                    'total_retirement_relief' => $totalRetirementRelief,
                    'mortgage_pre_tax'        => $mortgagePreTax ?? 0,
                    'taxable_income'          => $taxableIncome,
                    'paye_before_relief'      => $payeBeforeReliefs,
                    'total_reliefs'           => $totalReliefs,
                    'paye_after_reliefs'      => $paye,
                    'after_tax_deductions' => $afterTaxDeductions,
                    'net_pay'            => $netPay,
                ]);
            }

            $payrollData[$employeeId] = [
                'employee_id' => $employeeId,
                'employee' => $employee,
                'basic_salary' => $proratedBasicSalary,
                'gross_pay' => $grossPay,
                'taxable_gross' => $taxableGross ?? $grossPay,   // for payslip taxation column
                'overtime' => $overtimePay,
                'allowances' => $allowances,
                'shif' => $shif,
                'nssf' => $nssfTotal,
                'nssf_employee' => $nssfEmployee,
                'nssf_employer' => $nssfEmployer,
                'paye' => $paye,
                'paye_before_reliefs' => $payeBeforeReliefs,
                'housing_levy' => $housingLevy,
                'helb' => $helb,
                'loan_repayment' => $loanRepayment,
                'advance_recovery' => $advanceRecovery,
                // $deductions contains pension at employee cash amount only (employer share excluded).
                // The employer pension is tracked separately via employer_pension_* fields below.
                'deductions' => array_merge($deductions, [['name' => 'Absenteeism Charge', 'amount' => $absenteeismCharge]]),
                'net_pay' => $netPay,
                'taxable_income' => $taxableIncome,
                'reliefs' => $reliefs,  // each entry now has both 'amount' (for tax) and 'display_amount' (for UI)
                'personal_relief' => $personalRelief,
                'insurance_relief' => $insuranceRelief,
                'mortgage_pre_tax' => $mortgagePreTax ?? 0,   // for payslip display under pre-tax deductions
                'employee_pension' => $employeePension ?? 0,           // cash deduction from employee net pay
                'employer_pension'         => $employerPension ?? 0,        // total employer contribution (non-cash)
                'employer_pension_exempt'  => $employerPensionExempt ?? 0,   // exempt portion (≤30k, never in gross)
                'employer_pension_taxable' => $employerPensionTaxable ?? 0,  // taxable excess (>30k, in taxableGross only)
                'bank_name' => $bankName,
                'account_number' => $accountNumber,
                'currency' => $paymentDetail->currency ?? ($country === 'UGANDA' ? 'UGX' : 'KES'),
                'payment_mode' => $paymentDetail->payment_mode ?? 'N/A',
                'attendance_present' => $presentDays,
                'attendance_absent' => $absentDays,
                'days_in_month' => $daysInMonth,
                'country' => $country,
                'pwd_exemption_applied' => $pwdExemptionApplied,
                'pwd_exemption_amount'  => $pwdExemptionAmount,
                'employee_currency'  => $employeeCurrency,
                'tax_currency'       => $taxCurrency,
                'exchange_rate'      => $exchangeRate,
                'needs_conversion'   => $needsConversion,
                'basic_salary_orig'  => $needsConversion
                    ? round(floatval($paymentDetail->basic_salary ?? 0), 2)
                    : $proratedBasicSalary,
                'gross_pay_orig'     => $needsConversion
                    ? round($grossPay / $exchangeRate, 2)
                    : $grossPay,
                'net_pay_orig'       => $needsConversion
                    ? round($netPay / $exchangeRate, 2)
                    : $netPay,
            ];
        }
        return $payrollData;
    }

    protected function calculateStatutoryDeduction($businessId, $slug, $grossPay, $basicPay, $taxablePay, $employeeId, $payrollId)
    {
        $formula = PayrollFormula::with('brackets')
            ->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->orWhereNull('business_id');
            })
            ->where('slug', $slug)
            ->first();

        if (!$formula) {
            return $this->fallbackStatutoryDeduction($slug, $grossPay, $taxablePay);
        }

        $baseAmount = match ($formula->calculation_basis) {
            'basic_pay' => $basicPay,
            'taxable_pay' => $taxablePay,
            'gross_pay' => $grossPay,
            default => $grossPay,
        };

        $amount = $formula->calculate($baseAmount);

        if ($payrollId) {
            $calculation = $formula->recordCalculation($employeeId, $payrollId, $baseAmount, $amount);
            Log::debug("Statutory deduction calculated", [
                'slug' => $slug,
                'employee_id' => $employeeId,
                'payroll_id' => $payrollId,
                'base_amount' => $baseAmount,
                'result' => $amount,
                'calculation_id' => $calculation->id,
            ]);
        } else {
            Log::debug("Statutory deduction preview", [
                'slug' => $slug,
                'employee_id' => $employeeId,
                'base_amount' => $baseAmount,
                'result' => $amount,
            ]);
        }

        return $amount;
    }

    protected function calculateNSSFContribution($businessId, $grossPay, $employeeId, $payrollId)
    {
        $formula = PayrollFormula::with('brackets')
            ->where(function ($query) use ($businessId) {
                $query->where('business_id', $businessId)->orWhereNull('business_id');
            })
            ->where('slug', 'nssf')
            ->first();

        if (!$formula) {
            Log::warning("No NSSF formula found", ['business_id' => $businessId]);
            return $this->fallbackStatutoryDeduction('nssf', $grossPay, $grossPay);
        }

        $baseAmount = $grossPay;
        $totalContribution = $formula->calculate($baseAmount);

        if ($payrollId) {
            $calculation = $formula->recordCalculation($employeeId, $payrollId, $baseAmount, $totalContribution);
            Log::debug("NSSF contribution calculated", [
                'employee_id' => $employeeId,
                'payroll_id' => $payrollId,
                'gross_pay' => $grossPay,
                'result' => $totalContribution,
                'calculation_id' => $calculation->id,
            ]);
        }
        return $totalContribution;
    }

    protected function fallbackStatutoryDeduction($slug, $grossPay, $taxablePay)
    {
        switch ($slug) {
            case 'nssf':
                if ($grossPay <= 9000) {
                    return 540;
                } else {
                    $tier1 = 540;
                    $tier2 = min($grossPay - 9000, 29000) * 0.06;
                    return min($tier1 + $tier2, 6480);
                }
            case 'shif':
                return max(300, $grossPay * 0.0275);
            case 'housing-levy':
                return $grossPay * 0.015;
            case 'nhif':
                return 0;
            case 'paye':
                $tax = 0;
                if ($taxablePay <= 24000) {
                    $tax = $taxablePay * 0.10;
                } elseif ($taxablePay <= 32333) {
                    $tax = 2400 + (($taxablePay - 24000) * 0.25);
                } elseif ($taxablePay <= 500000) {
                    $tax = 4483.25 + (($taxablePay - 32333) * 0.30);
                } elseif ($taxablePay <= 800000) {
                    $tax = 144783.35 + (($taxablePay - 500000) * 0.325);
                } else {
                    $tax = 242283.35 + (($taxablePay - 800000) * 0.35);
                }
                return round($tax, 2);
            case 'helb':
                return 0;
            default:
                return 0;
        }
    }

  protected function calculateOvertime($employeeId, $year, $month, $settings, $options, $basicSalary, $hourlyRate = null)
{
    // If hourlyRate is explicitly passed (hourly employees), use it directly.
    // Otherwise derive it from basicSalary / 173 (salary employees).
    $hourlyRate = $hourlyRate ?? ($basicSalary > 0 ? ($basicSalary / 173) : 0);

    $totalOvertimePay = 0;

    if (!is_null($settings) && !empty($settings['overtime'])) {
        foreach ($settings['overtime'] as $item) {
            if (!$item['is_active']) continue;

            $overtime = Overtime::find($item['item_id']);
            if (!$overtime) continue;

            $totalOvertimePay += floatval($overtime->total_pay ?? 0) * $hourlyRate;
        }

        return $totalOvertimePay;
    }

    if ($options['pay_overtime']['apply'] === 'none') {
        return 0;
    }

    $overtimes = Overtime::where('employee_id', $employeeId)
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->where('status', 'approved')
        ->get();

    foreach ($overtimes as $overtime) {
        $shouldPay = $overtime->to_be_paid
            || isset($options['pay_overtime']['specific'][$overtime->id]);

        if (!$shouldPay) continue;

        $totalOvertimePay += floatval($overtime->total_pay ?? 0) * $hourlyRate;
    }

    return $totalOvertimePay;
}

    protected function calculateAdvanceRecovery($employeeId, $year, $month, $settings, $options)
    {
        $totalRecovery = 0;

        if (!is_null($settings) && !empty($settings['advances'])) {
            foreach ($settings['advances'] as $item) {
                if ($item['is_active']) {
                    $advance = Advance::find($item['item_id']);
                    if ($advance) {
                        $totalRecovery += min(floatval($item['amount']), floatval($advance->amount));
                    }
                }
            }
        } elseif ($options['recover_advances']['apply'] !== 'none') {
            $advances = Advance::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
            foreach ($advances as $advance) {
                if (isset($options['recover_advances']['specific'][$advance->id])) {
                    $totalRecovery += min($options['recover_advances']['specific'][$advance->id], floatval($advance->amount));
                }
            }
        }

        return $totalRecovery;
    }

    protected function calculateLoanRepayment($employeeId, $year, $month, $settings, $options)
    {
        $totalRepayment = 0;

        if (!is_null($settings) && !empty($settings['loans'])) {
            foreach ($settings['loans'] as $item) {
                if ($item['is_active']) {
                    $loan = Loan::find($item['item_id']);
                    if ($loan) {
                        $repaid = $loan->repayments->sum('amount');
                        $remaining = max(0, $loan->amount - $repaid);
                        $totalRepayment += min(floatval($item['amount']), $remaining);
                    }
                }
            }
        } elseif ($options['recover_loans']['apply'] !== 'none') {
            $loans = Loan::where('employee_id', $employeeId)
                ->where('start_date', '<=', Carbon::create($year, $month)->endOfMonth())
                ->get();
            foreach ($loans as $loan) {
                $repaid = $loan->repayments->sum('amount');
                $remaining = max(0, $loan->amount - $repaid);
                if ($remaining > 0 && isset($options['recover_loans']['specific'][$loan->id])) {
                    $totalRepayment += min($options['recover_loans']['specific'][$loan->id], $remaining);
                }
            }
        }

        return $totalRepayment;
    }

    protected function calculatePAYEByCountry($country, $taxableIncome, $grossPay = 0)
    {
        $countryUpper = strtoupper(trim($country));

        if ($countryUpper === 'UGANDA' || $countryUpper === 'UG') {
            return $this->calculateUgandaPAYE($taxableIncome);
        } elseif ($countryUpper === 'KENYA' || $countryUpper === 'KE') {
            return $this->calculateKenyaPAYE($taxableIncome);
        }

        Log::warning("Unknown country: {$country}, defaulting to Kenya PAYE calculation");
        return $this->calculateKenyaPAYE($taxableIncome);
    }

    protected function calculateUgandaPAYE($taxableIncome)
    {
        $tax = 0;

        if ($taxableIncome <= 235000) {
            $tax = 0;
        } elseif ($taxableIncome <= 335000) {
            $tax = ($taxableIncome - 235000) * 0.10;
        } elseif ($taxableIncome <= 410000) {
            $tax = 10000 + (($taxableIncome - 335000) * 0.20);
        } else {
            $tax = 25000 + (($taxableIncome - 410000) * 0.30);
        }

        return round($tax, 2);
    }

    protected function calculateKenyaPAYE($taxableIncome)
    {
        $tax = 0;
        if ($taxableIncome <= 24000) {
            $tax = $taxableIncome * 0.10;
        } elseif ($taxableIncome <= 32333) {
            $tax = 2400 + (($taxableIncome - 24000) * 0.25);
        } elseif ($taxableIncome <= 500000) {
            $tax = 4483.25 + (($taxableIncome - 32333) * 0.30);
        } elseif ($taxableIncome <= 800000) {
            $tax = 144783.35 + (($taxableIncome - 500000) * 0.325);
        } else {
            $tax = 242283.35 + (($taxableIncome - 800000) * 0.35);
        }
        return round($tax, 2);
    }

    protected function getStatutoryDeductions($country, $businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    {
        $countryUpper = strtoupper(trim($country));

        if ($countryUpper === 'UGANDA' || $countryUpper === 'UG') {
            return $this->getUgandaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId);
        } elseif ($countryUpper === 'KENYA' || $countryUpper === 'KE') {
            return $this->getKenyaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId);
        }

        Log::warning("Unknown country: {$country}, defaulting to Kenya statutory deductions");
        return $this->getKenyaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId);
    }

    protected function getUgandaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    {
        $deductions = [];

        $nssfEmployee = round($grossPay * 0.05, 2);
        $nssfTotal = round($nssfEmployee);

        $deductions['nssf'] = [
            'employee' => $nssfEmployee,
            'total' => $nssfTotal,
        ];

        $deductions['shif'] = 0;
        $deductions['housing-levy'] = 0;
        $deductions['nhif'] = 0;
        $deductions['helb'] = 0;
        $deductions['paye'] = 0;

        Log::debug("Uganda statutory deductions calculated", [
            'employee_id' => $employeeId,
            'gross_pay' => $grossPay,
            'nssf_employee' => $nssfEmployee,
        ]);

        return $deductions;
    }

    protected function getKenyaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    {
        $deductions = [];
        $statutorySlugs = ['nssf', 'shif', 'housing-levy', 'nhif', 'helb'];

        foreach ($statutorySlugs as $slug) {
            $deductions[$slug] = $this->calculateStatutoryDeduction($businessId, $slug, $grossPay, $basicPay, $grossPay, $employeeId, $payrollId);
        }

        $nssfTotal = $this->calculateNSSFContribution($businessId, $grossPay, $employeeId, $payrollId);
        $nssfEmployee = $nssfTotal / 2;
        $nssfEmployer = $nssfTotal / 2;
        $deductions['nssf'] = [
            'employee' => $nssfEmployee,
            'employer' => $nssfEmployer,
            'total' => $nssfTotal,
        ];

        $deductions['paye'] = 0;

        return $deductions;
    }

    protected function calculateHelb($businessId, $slug, $grossPay, $basicPay, $taxablePay, $employeeId, $payrollId)
    {
        $payrollDetail = EmployeePayrollDetail::where('employee_id', $employeeId)->first();
        if (!$payrollDetail || !$payrollDetail->has_helb) {
            return 0;
        }

        return $this->calculateStatutoryDeduction($businessId, 'helb', $grossPay, $basicPay, $taxablePay, $employeeId, $payrollId);
    }

    protected function getEmployeeItems($employee, $type, $settings, $modelClass, $pivotClass, $baseAmount, $taxableIncome = 0)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $items = [];
        $hasSettings = !is_null($settings) && !empty($settings[$type]);

        if ($hasSettings) {
            foreach ($settings[$type] as $itemId => $itemData) {
                if (!$itemData['is_active']) continue;
                $modelItem = $modelClass::find($itemId);
                if (!$modelItem) continue;

                $amount = number_format(floatval($itemData['amount'] ?? $modelItem->amount ?? 0), 3, '.', '');
                $rate = number_format(floatval($itemData['rate'] ?? $modelItem->rate ?? 0), 3, '.', '');

                if ($type === 'allowances') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $baseAmount,
                        default => $baseAmount,
                    };
                    $computedAmount = $modelItem->type === 'fixed'
                        ? $amount
                        : number_format($baseForCalc * ($rate / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name'                     => $modelItem->name,
                        'amount'                   => $computedAmount,
                        'is_taxable'               => $modelItem->is_taxable,
                        'is_employer_contribution' => (bool) ($modelItem->is_employer_contribution ?? false),
                        'tax_application'          => 'before_tax',
                    ];
                } elseif ($type === 'reliefs') {
                    // Priority: if settings/pivot has an explicit amount > 0, use it.
                    $settingsAmount = floatval($itemData['amount'] ?? 0);
                    if ($settingsAmount > 0) {
                        $computedAmount = number_format(min($settingsAmount, floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    } elseif ($modelItem->computation_method === 'fixed') {
                        $computedAmount = number_format(min(floatval($modelItem->amount ?? 0), floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    } else {
                        $computedAmount = number_format($baseAmount * (floatval($modelItem->percentage_of_amount ?? 0) / 100), 3, '.', '');
                    }
                    $items[$modelItem->slug] = [
                        'name'         => $modelItem->name,
                        'amount'       => $computedAmount,
                        'is_taxable'   => false,
                        'tax_application' => 'before_tax',
                    ];
                } elseif ($type === 'deductions') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $baseAmount,
                        'taxable_pay' => $taxableIncome,
                        'cash_pay' => $baseAmount,
                        default => $baseAmount,
                    };
                    switch ($modelItem->computation_method) {
                        case 'fixed':
                            $computedAmount = $amount;
                            break;
                        case 'rate':
                            $computedAmount = number_format($baseForCalc * ($rate / 100), 3, '.', '');
                            break;
                        case 'formula':
                            $computedAmount = $modelItem->actual_amount ? $amount : number_format($baseForCalc * 0.05, 3, '.', '');
                            break;
                        default:
                            $computedAmount = $amount;
                    }
                    $computedAmount = number_format(min($computedAmount, floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    $computedAmount = $modelItem->round_off === 'round_off_up'
                        ? ceil($computedAmount * 1000) / 1000
                        : floor($computedAmount * 1000) / 1000;
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => false,
                        'tax_application' => 'after_tax',
                    ];
                }
            }
        } else {
            $relation = match ($type) {
                'allowances' => 'employeeAllowances',
                'deductions' => 'employeeDeductions',
                'reliefs' => 'reliefs',
            };
            $pivotItems = $employee->$relation;

            foreach ($pivotItems as $pivotItem) {
                $itemId = $type === 'reliefs' ? $pivotItem->relief_id : $pivotItem->{"{$type}_id"};
                $modelItem = $modelClass::find($itemId);
                if (!$modelItem || !$pivotItem->is_active) continue;

                $amount = number_format(floatval($pivotItem->amount ?? $modelItem->amount ?? 0), 3, '.', '');
                $rate = number_format(floatval($pivotItem->rate ?? $modelItem->rate ?? 0), 3, '.', '');

                if ($type === 'allowances') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $baseAmount,
                        default => $baseAmount,
                    };
                    $computedAmount = $modelItem->type === 'fixed'
                        ? $amount
                        : number_format($baseForCalc * ($rate / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name'                     => $modelItem->name,
                        'amount'                   => $computedAmount,
                        'is_taxable'               => $modelItem->is_taxable,
                        'is_employer_contribution' => (bool) ($modelItem->is_employer_contribution ?? false),
                        'tax_application'          => 'before_tax',
                    ];
                } elseif ($type === 'reliefs') {
                    // Priority: if the employee pivot has an explicit amount set (e.g. mortgage 20,000),
                    // always use it regardless of computation_method.
                    // Only fall back to percentage calculation when pivot amount is 0/null.
                    $pivotAmount = floatval($pivotItem->amount ?? 0);
                    if ($pivotAmount > 0) {
                        $computedAmount = number_format(min($pivotAmount, floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    } elseif ($modelItem->computation_method === 'fixed') {
                        $computedAmount = number_format(min(floatval($modelItem->amount ?? 0), floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    } else {
                        $computedAmount = number_format($baseAmount * (floatval($modelItem->percentage_of_amount ?? 0) / 100), 3, '.', '');
                    }
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => false,
                        'tax_application' => 'before_tax',
                    ];
                } elseif ($type === 'deductions') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $baseAmount,
                        'taxable_pay' => $taxableIncome,
                        'cash_pay' => $baseAmount,
                        default => $baseAmount,
                    };
                    switch ($modelItem->computation_method) {
                        case 'fixed':
                            $computedAmount = $amount;
                            break;
                        case 'rate':
                            $computedAmount = number_format($baseForCalc * ($rate / 100), 3, '.', '');
                            break;
                        case 'formula':
                            $computedAmount = $modelItem->actual_amount ? $amount : number_format($baseForCalc * 0.05, 3, '.', '');
                            break;
                        default:
                            $computedAmount = $amount;
                    }
                    $computedAmount = number_format(min($computedAmount, floatval($modelItem->limit ?? PHP_FLOAT_MAX)), 3, '.', '');
                    $computedAmount = $modelItem->round_off === 'round_off_up'
                        ? ceil($computedAmount * 1000) / 1000
                        : floor($computedAmount * 1000) / 1000;
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => false,
                        'tax_application' => 'after_tax',
                    ];
                }
            }

            // Ensure mandatory Kenyan reliefs if not overridden
            if ($type === 'reliefs' && !$hasSettings) {
                $mandatoryReliefs = [
                    'personal-relief' => ['name' => 'Personal Relief', 'amount' => 2400],
                ];
                foreach ($mandatoryReliefs as $slug => $data) {
                    if (!isset($items[$slug])) {
                        $relief = $modelClass::where('business_id', $business->id)->where('slug', $slug)->first();
                        $items[$slug] = [
                            'name' => $relief ? $relief->name : $data['name'],
                            'amount' => number_format(floatval($relief ? ($relief->amount ?? $data['amount']) : $data['amount']), 3, '.', ''),
                            'is_taxable' => false,
                            'tax_application' => 'before_tax',
                        ];
                    }
                }
            }
        }

        Log::debug("Fetched {$type} for employee {$employee->id}", ['items' => $items]);
        return $items;
    }

    protected function updateLoanAndAdvance($payrollData, $year, $month, $options)
    {
        $employeeId = $payrollData['employee_id'];

        if ($payrollData['advance_recovery'] > 0 && $options['recover_advances']['apply'] !== 'none') {
            $advances = Advance::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('amount', '>', 0)
                ->orderBy('date')
                ->get();
            $remainingRecovery = $payrollData['advance_recovery'];
            foreach ($advances as $advance) {
                if ($remainingRecovery <= 0) break;
                $recovery = min($advance->amount, $remainingRecovery);
                $advance->update(['amount' => max(0, $advance->amount - $recovery)]);
                $remainingRecovery -= $recovery;
            }
        }

        if ($payrollData['loan_repayment'] > 0 && $options['recover_loans']['apply'] !== 'none') {
            $period = Carbon::create($year, $month);
            $loans = Loan::where('employee_id', $employeeId)
                ->where('start_date', '<=', $period->endOfMonth())
                ->where('end_date', '>=', $period->startOfMonth())
                ->orderBy('start_date')
                ->get();
            $remainingRepayment = $payrollData['loan_repayment'];
            foreach ($loans as $loan) {
                if ($remainingRepayment <= 0) break;
                $repaid = $loan->repayments->sum('amount');
                $remainingBalance = max(0, $loan->amount - $repaid);
                if ($remainingBalance > 0) {
                    $repayment = min($remainingBalance, $remainingRepayment);
                    LoanRepayment::create([
                        'loan_id' => $loan->id,
                        'amount' => $repayment,
                        'date' => $period->endOfMonth(),
                    ]);
                    $remainingRepayment -= $repayment;
                }
            }
        }
    }

    public function all(Request $request)
    {
        $page = 'All Payrolls';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $years = range(date('Y') - 5, date('Y') + 5);
        $months = range(1, 12);
        $locations = $business->locations->prepend((object) [
            'id' => 'business_' . $business->id,
            'name' => $business->company_name,
        ]);
        $departments = $business->departments ?? collect();
        $jobCategories = $business->job_categories ?? collect();

        $payrolls = Payroll::where('business_id', $business->id)
            ->withCount(['employeePayrolls as no_of_payslips'])
            ->with(['employeePayrolls' => function ($query) {
                $query->select('payroll_id', 'net_pay');
            }, 'location' => function ($query) {
                $query->select('id', 'name');
            }])
            ->get();

        $totalPayroll = $payrolls->sum(function ($payroll) {
            return $payroll->employeePayrolls->sum('net_pay');
        });
        $totalNetPay = $totalPayroll;

        return view('payroll.all', compact('business', 'page', 'years', 'months', 'locations', 'departments', 'jobCategories', 'payrolls', 'totalPayroll', 'totalNetPay'));
    }

    public function deletePayroll(Request $request, $id)
    {
        return $this->handleTransaction(function () use ($id) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $payroll = Payroll::where('business_id', $business->id)->where('id', $id)->firstOrFail();
            $payroll->delete();

            $payrolls = Payroll::where('business_id', $business->id)
                ->with('employeePayrolls')
                ->get();
            $totalPayroll = $payrolls->sum(fn($p) => $p->employeePayrolls->sum('net_pay'));

            return RequestResponse::ok('Payroll deleted successfully.', [
                'payroll_count' => $payrolls->count(),
                'total_payroll' => number_format($totalPayroll, 2),
                'total_net_pay' => number_format($totalPayroll, 2),
            ]);
        });
    }


    public function closeMonth(Request $request, $payrollId)
    {
        return $this->handleTransaction(function () use ($payrollId) {

            $payroll = Payroll::find($payrollId);
            if (!$payroll) {
                return RequestResponse::badRequest('Payroll not found.');
            }

            $business = Business::find($payroll->business_id);
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $payrolls = Payroll::where('business_id', $business->id)
                ->where('payrun_month', $payroll->payrun_month)
                ->where('payrun_year', $payroll->payrun_year)
                ->get();

            if ($payrolls->isEmpty()) {
                return RequestResponse::badRequest('No payrolls found for this period.');
            }

            $updatedCount = 0;
            foreach ($payrolls as $p) {
                if ($p->status !== 'closed') {
                    $p->update(['status' => 'closed']);
                    $updatedCount++;
                }
            }

            $html = view('payroll._past', [
                'payrolls' => $payrolls,
                'business' => $business
            ])->render();

            return RequestResponse::ok(
                "Closed $updatedCount payroll(s) successfully.",
                [
                    'status' => 'closed',
                    'html'   => $html
                ]
            );
        });
    }


    public function emailP9(Request $request, $id)
    {
        return $this->handleTransaction(function () use ($id, $request) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                Log::error('Business not found.', ['slug' => session('active_business_slug')]);
                return RequestResponse::badRequest('Business not found.');
            }

            try {
                $payroll = Payroll::where('business_id', $business->id)
                    ->where('id', $id)
                    ->with(['employeePayrolls.employee.user'])
                    ->firstOrFail();
            } catch (\Exception $e) {
                Log::error('Failed to load payroll', ['id' => $id, 'business_id' => $business->id, 'error' => $e->getMessage()]);
                return RequestResponse::badRequest('Failed to load payroll data.', ['error' => $e->getMessage()]);
            }

            $year = $payroll->payrun_year;
            $month = $payroll->payrun_month;

            $p9Dir = storage_path('app/public/p9/');
            if (!File::exists($p9Dir)) {
                File::makeDirectory($p9Dir, 0777, true);
            }

            $successCount = 0;
            $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)->with(['employee.user'])->get();

            if ($employeePayrolls->isEmpty()) {
                return RequestResponse::badRequest('No payroll records found for the given ID.');
            }

            foreach ($employeePayrolls as $employeePayroll) {
                $employee = $employeePayroll->employee;
                if (!$employee) continue;

                $user = $employee->user;
                if (!$user) {
                    $user = new \stdClass();
                    $user->email = 'no-email-' . $employeePayroll->employee_id . '@example.com';
                }

                $allEmployeePayrolls = EmployeePayroll::where('employee_id', $employeePayroll->employee_id)
                    ->whereHas('payroll', function ($query) use ($business, $year) {
                        $query->where('business_id', $business->id)->where('payrun_year', $year);
                    })
                    ->with('payroll')
                    ->get();

                $monthlyData = array_fill(1, 12, [
                    'basic_salary' => 0.00,
                    'benefits_non_cash' => 0.00,
                    'value_of_quarters' => 0.00,
                    'total_gross_pay' => 0.00,
                    'retirement_e1' => 0.00,
                    'retirement_e2' => 0.00,
                    'retirement_e3' => 0.00,
                    'housing_levy' => 0.00,
                    'shif' => 0.00,
                    'prmf' => 0.00,
                    'owner_occupied_interest' => 0.00,
                    'retirement_contribution' => 0.00,
                    'chargeable_pay' => 0.00,
                    'tax_charged' => 0.00,
                    'personal_relief' => 0.00,
                    'insurance_relief' => 0.00,
                    'paye' => 0.00,
                    'total_deductions' => 0.00,
                ]);

                foreach ($allEmployeePayrolls as $payrollEntry) {
                    $payrollMonth = $payrollEntry->payroll->payrun_month;
                    $deductions = json_decode($payrollEntry->deductions, true) ?: [];
                    $basicSalary = (float) ($payrollEntry->basic_salary ?? 0.00);
                    $grossPay = (float) ($payrollEntry->gross_pay ?? 0.00);
                    $housingLevy = (float) ($payrollEntry->housing_levy ?? 0.00);
                    $shif = (float) ($payrollEntry->shif ?? 0.00);
                    $taxableIncome = (float) ($payrollEntry->taxable_income ?? 0.00);
                    $payeBeforeReliefs = (float) ($payrollEntry->paye_before_reliefs ?? 0.00);
                    $personalRelief = (float) ($payrollEntry->personal_relief ?? 0.00);
                    $insuranceRelief = (float) ($payrollEntry->insurance_relief ?? 0.00);
                    $paye = (float) ($payrollEntry->paye ?? 0.00);

                    $nssfContribution = (float) ($deductions['nssf'] ?? $payrollEntry->nssf ?? 0.00);
                    $pensionContribution = (float) ($deductions['pension'] ?? 0.00);
                    $retirementE2 = ($basicSalary > 0 || $grossPay > 0) ? ($nssfContribution + $pensionContribution) : 0.00;
                    $actualRetirement = $retirementE2;
                    $maxRetirement = min($actualRetirement, $basicSalary * 0.3, 30000.00);
                    $retirementContribution = $maxRetirement;

                    $postRetirementMedical = min((float) ($deductions['post_retirement_medical'] ?? 0.00), 15000.00);
                    $mortgageInterest = min((float) ($deductions['mortgage_interest'] ?? 0.00), 30000.00);

                    $insurancePremium = (float) ($deductions['insurance_premium'] ?? 0.00);
                    $insuranceRelief = ($insuranceRelief > 0) ? $insuranceRelief : min($insurancePremium * 0.15, 60000.00 / 12);

                    $totalDeductions = $retirementContribution + $housingLevy + $shif + $postRetirementMedical + $mortgageInterest;
                    $chargeablePay = ($taxableIncome > 0) ? $taxableIncome : max(0, $grossPay - $totalDeductions);
                    $taxCharged = ($payeBeforeReliefs > 0) ? $payeBeforeReliefs : 0;

                    if ($chargeablePay > 0 && $taxCharged == 0) {
                        if ($chargeablePay <= 24000) {
                            $taxCharged = $chargeablePay * 0.1;
                        } elseif ($chargeablePay <= 32333) {
                            $taxCharged = 2400 + ($chargeablePay - 24000) * 0.25;
                        } elseif ($chargeablePay <= 500000) {
                            $taxCharged = 4483.25 + ($chargeablePay - 32333) * 0.3;
                        } elseif ($chargeablePay <= 800000) {
                            $taxCharged = 144783.35 + ($chargeablePay - 500000) * 0.325;
                        } else {
                            $taxCharged = 242283.35 + ($chargeablePay - 800000) * 0.35;
                        }
                    }

                    $personalRelief = ($personalRelief > 0) ? $personalRelief : (($basicSalary > 0 || $grossPay > 0) ? 2400.00 : 0.00);
                    $paye = ($paye > 0) ? $paye : max(0, $taxCharged - $personalRelief);
                    $retirement_e3 = ($basicSalary > 0 || $grossPay > 0) ? 30000.00 : 0.00;

                    $monthlyData[$payrollMonth] = [
                        'basic_salary' => $basicSalary,
                        'benefits_non_cash' => 0.00,
                        'value_of_quarters' => 0.00,
                        'total_gross_pay' => $grossPay,
                        'retirement_e1' => $basicSalary * 0.3,
                        'retirement_e2' => $retirementE2,
                        'retirement_e3' => $retirement_e3,
                        'housing_levy' => $housingLevy,
                        'shif' => $shif,
                        'prmf' => $postRetirementMedical,
                        'owner_occupied_interest' => $mortgageInterest,
                        'retirement_contribution' => $retirementContribution,
                        'chargeable_pay' => $chargeablePay,
                        'tax_charged' => $taxCharged,
                        'personal_relief' => $personalRelief,
                        'insurance_relief' => $insuranceRelief,
                        'paye' => $paye,
                        'total_deductions' => $totalDeductions,
                    ];
                }

                $totals = [
                    'basic_salary' => 0,
                    'benefits_non_cash' => 0,
                    'value_of_quarters' => 0,
                    'total_gross_pay' => 0,
                    'retirement_e1' => 0,
                    'retirement_e2' => 0,
                    'retirement_e3' => 0,
                    'housing_levy' => 0,
                    'shif' => 0,
                    'prmf' => 0,
                    'owner_occupied_interest' => 0,
                    'retirement_contribution' => 0,
                    'chargeable_pay' => 0,
                    'tax_charged' => 0,
                    'personal_relief' => 0,
                    'insurance_relief' => 0,
                    'paye' => 0,
                    'total_deductions' => 0,
                ];
                foreach ($monthlyData as $monthData) {
                    foreach ($totals as $key => $value) {
                        $totals[$key] += $monthData[$key] ?? 0;
                    }
                }

                $employeeDetails = [
                    'main_name' => $user->name ?? 'N/A',
                    'pin' => $employee->tax_no ?? 'N/A',
                    'nssf' => $employee->nssf_no ?? 'N/A',
                    'shif' => $employee->shif_no ?? $employee->nhif_no ?? 'N/A',
                    'company_name' => $business->company_name ?? $business->name,
                    'tax_no' => $business->tax_pin_no ?? 'N/A',
                ];

                $data = [[
                    'employee_name' => $employeeDetails['company_name'],
                    'tax_no' => $employeeDetails['tax_no'],
                    'main_name' => $employeeDetails['main_name'],
                    'pin' => $employeeDetails['pin'],
                    'nssf' => $employeeDetails['nssf'],
                    'shif' => $employeeDetails['shif'],
                    'monthly_data' => $monthlyData,
                    'totals' => $totals,
                ]];

                if (!view()->exists('payroll.reports.p9')) {
                    return RequestResponse::serverError('P9 view not found.');
                }

                try {
                    $pdf = Pdf::loadView('payroll.reports.p9', array_merge(['business' => $business, 'year' => $year], ['data' => $data]))
                        ->setPaper('A4', 'landscape');
                    $pdfPath = storage_path('app/public/p9/' . $employeePayroll->id . '.pdf');
                    $pdf->save($pdfPath);

                    Mail::to($user->email)->send(new \App\Mail\P9Mail($employeePayroll, $pdfPath, $year, $user));
                    $successCount++;
                } catch (\Exception $e) {
                    Log::error('Failed to generate or email P9', ['employee_id' => $employeePayroll->employee_id, 'error' => $e->getMessage()]);
                    return RequestResponse::badRequest('Failed to generate or email P9.', ['error' => $e->getMessage()]);
                }
            }

            if ($successCount === 0) {
                return RequestResponse::badRequest('No P9 forms were emailed due to invalid data or missing emails.');
            }

            return RequestResponse::ok("Successfully emailed $successCount P9 forms.");
        });
    }

    public function downloadPayroll(Request $request, $id, $format = 'pdf')
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            return response()->json(['error' => 'Business not found.'], 400);
        }

        $id = $request->id;
        $format = $request->format;

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['employeePayrolls.employee.user'])
            ->firstOrFail();

        $entity = $business;
        $entityType = 'business';
        if ($payroll->location_id) {
            $location = Location::where('id', $payroll->location_id)
                ->where('business_id', $business->id)
                ->first();
            if ($location) {
                $entity = $location;
                $entityType = 'location';
            }
        }

        $employeePayrolls = $this->getFilteredEmployeePayrolls($payroll, $request, [
            'employee.user',
        ]);

        $data = $employeePayrolls->map(function ($ep) {
            $deductions = json_decode($ep->deductions, true) ?? [];
            $overtime = json_decode($ep->overtime, true) ?? ['amount' => 0];
            $allowances = json_decode($ep->allowances, true) ?? [];
            $customDeductions = array_filter($deductions, fn($d) => !in_array($d['name'] ?? '', [
                'SHIF',
                'NSSF',
                'PAYE',
                'Housing Levy',
                'HELB',
                'Loan Repayment',
                'Advance Recovery',
                'Absenteeism Charge'
            ]));
            $totalCustomDeductions = array_sum(array_map(fn($d) => $d['amount'] ?? 0, $customDeductions));

            return [
                'employee_name' => $ep->employee->user->name ?? 'N/A',
                'employee_code' => $ep->employee->employee_code ?? 'N/A',
                'tax_no' => $ep->employee->tax_no ?? 'N/A',
                'basic_salary' => (float) ($ep->basic_salary ?? 0),
                'gross_pay' => (float) ($ep->gross_pay ?? 0),
                'overtime' => (float) ($overtime['amount'] ?? 0),
                'shif' => (float) ($ep->shif ?? ($deductions['shif'] ?? 0)),
                'nssf' => (float) ($ep->nssf ?? ($deductions['nssf'] ?? 0)),
                'paye' => (float) ($ep->paye ?? ($deductions['paye'] ?? 0)),
                'paye_before_reliefs' => (float) ($ep->paye_before_reliefs ?? 0),
                'housing_levy' => (float) ($ep->housing_levy ?? ($deductions['housing_levy'] ?? 0)),
                'helb' => (float) ($ep->helb ?? ($deductions['helb'] ?? 0)),
                'taxable_income' => (float) ($ep->taxable_income ?? 0),
                'personal_relief' => (float) ($ep->personal_relief ?? 0),
                'insurance_relief' => (float) ($ep->insurance_relief ?? 0),
                'pay_after_tax' => (float) ($ep->pay_after_tax ?? 0),
                'loan_repayment' => (float) ($ep->loan_repayment ?? ($deductions['loan_repayment'] ?? 0)),
                'advance_recovery' => (float) ($ep->advance_recovery ?? ($deductions['advance_recovery'] ?? 0)),
                'custom_deductions' => (float) $totalCustomDeductions,
                'deductions_after_tax' => (float) ($ep->deductions_after_tax ?? 0),
                'net_pay' => (float) ($ep->net_pay ?? 0),
                'attendance_present' => (int) ($ep->attendance_present ?? 0),
                'attendance_absent' => (int) ($ep->attendance_absent ?? 0),
                'days_in_month' => (int) ($ep->days_in_month ?? 0),
                'bank_name' => $ep->bank_name ?? 'N/A',
                'account_number' => $ep->account_number ?? 'N/A',
            ];
        })->toArray();

        $totals = [
            'totalBasicSalary' => array_sum(array_column($data, 'basic_salary')),
            'totalGrossPay' => array_sum(array_column($data, 'gross_pay')),
            'totalOvertime' => array_sum(array_column($data, 'overtime')),
            'totalShif' => array_sum(array_column($data, 'shif')),
            'totalNssf' => array_sum(array_column($data, 'nssf')),
            'totalPaye' => array_sum(array_column($data, 'paye')),
            'totalPayeBeforeReliefs' => array_sum(array_column($data, 'paye_before_reliefs')),
            'totalHousingLevy' => array_sum(array_column($data, 'housing_levy')),
            'totalHelb' => array_sum(array_column($data, 'helb')),
            'totalTaxableIncome' => array_sum(array_column($data, 'taxable_income')),
            'totalPersonalRelief' => array_sum(array_column($data, 'personal_relief')),
            'totalInsuranceRelief' => array_sum(array_column($data, 'insurance_relief')),
            'totalPayAfterTax' => array_sum(array_column($data, 'pay_after_tax')),
            'totalLoans' => array_sum(array_column($data, 'loan_repayment')),
            'totalAdvances' => array_sum(array_column($data, 'advance_recovery')),
            'totalCustomDeductions' => array_sum(array_column($data, 'custom_deductions')),
            'totalDeductionsAfterTax' => array_sum(array_column($data, 'deductions_after_tax')),
            'totalNetPay' => array_sum(array_column($data, 'net_pay')),
            'totalAttendancePresent' => array_sum(array_column($data, 'attendance_present')),
            'totalAttendanceAbsent' => array_sum(array_column($data, 'attendance_absent')),
            'totalDaysInMonth' => array_sum(array_column($data, 'days_in_month')),
        ];
        $totals = array_map('floatval', $totals);

        $fileName = "payroll-{$id}.{$format}";
        $currency = $payroll->currency ?? 'KES';

        switch ($format) {
            case 'pdf':
                try {
                    $pdf = Pdf::loadView('payroll.reports.company_payslip', [
                        'business'   => $business,
                        'payroll'    => $payroll,
                        'entity'     => $entity,
                        'entityType' => $entityType,
                        'data'       => $data,
                        'totals'     => $totals,
                        'currency'   => $currency,
                    ])
                        ->setOptions(['isHtml5ParserEnabled' => true, 'isCssFloat' => true])
                        ->setPaper('a4', 'landscape');
                    return $pdf->download($fileName);
                } catch (\Exception $e) {
                    Log::error("PDF generation failed for payroll {$id}: " . $e->getMessage());
                    return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
                }

            case 'csv':
                $headers = array_keys($data[0] ?? []);
                $csvData = implode(',', array_map(
                    fn($key) => '"' . ucwords(str_replace('_', ' ', $key))
                        . (in_array($key, ['bank_name', 'account_number', 'employee_name', 'employee_code', 'tax_no']) ? '' : " ({$currency})")
                        . '"',
                    $headers
                )) . "\n";

                foreach ($data as $row) {
                    $csvData .= implode(',', array_map(function ($value, $key) {
                        return ($key !== 'bank_name' && $key !== 'account_number'
                            && $key !== 'employee_name' && $key !== 'employee_code'
                            && $key !== 'tax_no' && is_numeric($value))
                            ? number_format($value, 2)
                            : '"' . str_replace('"', '""', $value) . '"';
                    }, $row, array_keys($row))) . "\n";
                }

                $totalsRow = array_map(function ($key) use ($totals, $currency) {
                    return match ($key) {
                        'employee_name'  => '"TOTALS"',
                        'employee_code'  => '""',
                        'tax_no'         => '""',
                        'bank_name'      => '""',
                        'account_number' => '""',
                        'basic_salary'        => number_format($totals['totalBasicSalary'], 2),
                        'gross_pay'           => number_format($totals['totalGrossPay'], 2),
                        'overtime'            => number_format($totals['totalOvertime'], 2),
                        'shif'                => number_format($totals['totalShif'], 2),
                        'nssf'                => number_format($totals['totalNssf'], 2),
                        'paye'                => number_format($totals['totalPaye'], 2),
                        'paye_before_reliefs' => number_format($totals['totalPayeBeforeReliefs'], 2),
                        'housing_levy'        => number_format($totals['totalHousingLevy'], 2),
                        'helb'                => number_format($totals['totalHelb'], 2),
                        'taxable_income'      => number_format($totals['totalTaxableIncome'], 2),
                        'personal_relief'     => number_format($totals['totalPersonalRelief'], 2),
                        'insurance_relief'    => number_format($totals['totalInsuranceRelief'], 2),
                        'pay_after_tax'       => number_format($totals['totalPayAfterTax'], 2),
                        'loan_repayment'      => number_format($totals['totalLoans'], 2),
                        'advance_recovery'    => number_format($totals['totalAdvances'], 2),
                        'custom_deductions'   => number_format($totals['totalCustomDeductions'], 2),
                        'deductions_after_tax' => number_format($totals['totalDeductionsAfterTax'], 2),
                        'net_pay'             => number_format($totals['totalNetPay'], 2),
                        'attendance_present'  => $totals['totalAttendancePresent'],
                        'attendance_absent'   => $totals['totalAttendanceAbsent'],
                        'days_in_month'       => $totals['totalDaysInMonth'],
                        default               => '""',
                    };
                }, array_keys($data[0] ?? []));
                $csvData .= implode(',', $totalsRow) . "\n";

                return Response::make($csvData, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                ]);

            case 'xlsx':
                try {
                    $totalsRef = $totals;
                    return Excel::download(new class($data, $currency, $totalsRef) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings,
                        \Maatwebsite\Excel\Concerns\WithStyles,
                        \Maatwebsite\Excel\Concerns\ShouldAutoSize {

                        private array $data;
                        private string $currency;
                        private array $totals;

                        public function __construct(array $data, string $currency, array $totals)
                        {
                            $this->data     = $data;
                            $this->currency = $currency;
                            $this->totals   = $totals;
                        }

                        public function array(): array
                        {
                            $rows = $this->data;
                            $keys = array_keys($this->data[0] ?? []);
                            $totalsRow = array_map(fn($key) => match ($key) {
                                'employee_name'       => 'TOTALS',
                                'employee_code', 'tax_no', 'bank_name', 'account_number' => '',
                                'basic_salary'        => $this->totals['totalBasicSalary'],
                                'gross_pay'           => $this->totals['totalGrossPay'],
                                'overtime'            => $this->totals['totalOvertime'],
                                'shif'                => $this->totals['totalShif'],
                                'nssf'                => $this->totals['totalNssf'],
                                'paye'                => $this->totals['totalPaye'],
                                'paye_before_reliefs' => $this->totals['totalPayeBeforeReliefs'],
                                'housing_levy'        => $this->totals['totalHousingLevy'],
                                'helb'                => $this->totals['totalHelb'],
                                'taxable_income'      => $this->totals['totalTaxableIncome'],
                                'personal_relief'     => $this->totals['totalPersonalRelief'],
                                'insurance_relief'    => $this->totals['totalInsuranceRelief'],
                                'pay_after_tax'       => $this->totals['totalPayAfterTax'],
                                'loan_repayment'      => $this->totals['totalLoans'],
                                'advance_recovery'    => $this->totals['totalAdvances'],
                                'custom_deductions'   => $this->totals['totalCustomDeductions'],
                                'deductions_after_tax' => $this->totals['totalDeductionsAfterTax'],
                                'net_pay'             => $this->totals['totalNetPay'],
                                'attendance_present'  => $this->totals['totalAttendancePresent'],
                                'attendance_absent'   => $this->totals['totalAttendanceAbsent'],
                                'days_in_month'       => $this->totals['totalDaysInMonth'],
                                default               => '',
                            }, $keys);
                            $rows[] = array_combine($keys, $totalsRow);
                            return $rows;
                        }

                        public function headings(): array
                        {
                            return array_map(
                                fn($key) => ucwords(str_replace('_', ' ', $key))
                                    . (in_array($key, ['bank_name', 'account_number', 'employee_name', 'employee_code', 'tax_no'])
                                        ? '' : " ({$this->currency})"),
                                array_keys($this->data[0] ?? [])
                            );
                        }

                        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
                        {
                            $lastRow = count($this->data) + 2;
                            return [
                                1        => ['font' => ['bold' => true]],
                                $lastRow => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'E8E8E8']]],
                            ];
                        }
                    }, $fileName);
                } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
                    Log::error("Excel generation failed for payroll {$id}: " . $e->getMessage());
                    return response()->json(['error' => 'Failed to generate Excel file.'], 500);
                }

            default:
                return response()->json(['error' => 'Invalid format requested.'], 400);
        }
    }

    public function viewPayslip(Request $request, $employeeId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $id        = $request->employee_id;
        $payrollId = $request->query('payroll_id');
        if (!$payrollId) {
            return RequestResponse::badRequest('Payroll ID is required to view the payslip.');
        }

        $employeePayroll = EmployeePayroll::with([
            'employee.user',
            'employee.paymentDetails',
            'payroll.business',
            'payroll.location',
        ])
            ->where('employee_id', $id)
            ->where('payroll_id', $payrollId)
            ->firstOrFail();

        $entity     = $business;
        $entityType = 'business';
        if ($employeePayroll->payroll->location_id) {
            $location = Location::where('id', $employeePayroll->payroll->location_id)
                ->where('business_id', $business->id)
                ->first();
            if ($location) {
                $entity     = $location;
                $entityType = 'location';
            }
        }

        // ── Step 1: Business primary currency ────────────────────────────────────
        $primaryRow = \App\Models\BusinessCurrency::where('business_id', $business->id)
            ->where('is_primary', true)
            ->first();

        $primaryCurrency = strtoupper(trim(
            $primaryRow?->currency_code
                ?? $business->currency
                ?? 'KES'
        ));

        // ── Step 2: Employee's configured pay currency ────────────────────────────
        // ONLY from employee_payment_details.currency — this is the admin-configured
        // currency in which the employee is paid. It's the source of truth.
        // If null or empty or same as primary → no FX column.

        $employeePayCurrency = strtoupper(trim(
            $employeePayroll->employee?->paymentDetails?->currency ?? ''
        ));

        // ── Step 3: Decide whether to show the FX column ─────────────────────────
        $showConversion = $employeePayCurrency !== ''
            && $employeePayCurrency !== $primaryCurrency
            && \App\Models\BusinessCurrency::where('business_id', $business->id)
            ->where('currency_code', $employeePayCurrency)
            ->exists(); // only show if the currency is actually configured

        // ── Step 4: Get the exchange rate if needed ───────────────────────────────
        // Rate meaning: 1 unit of employeePayCurrency = X units of primaryCurrency
        // e.g. 1 USD = 100 KES → rate = 100
        // Blade converts: foreignAmount = primaryAmount / rate
        // e.g. 578,150 KES / 100 = 5,781.50 USD

        $exchangeRates  = 1.0;
        $targetCurrency = $primaryCurrency;

        if ($showConversion) {
            // Priority 1: valid rate stored on the payroll row
            $payrollRate = floatval($employeePayroll->exchange_rate ?? 0);

            if ($payrollRate > 0 && abs($payrollRate - 1.0) > 0.0001) {
                $exchangeRates = $payrollRate;
            } else {
                // Priority 2: read from business_currencies table
                $currencyRow = \App\Models\BusinessCurrency::where('business_id', $business->id)
                    ->where('currency_code', $employeePayCurrency)
                    ->first();

                if ($currencyRow) {
                    // effective_rate accessor:
                    //   manual mode → manual_rate  (e.g. 100 for USD)
                    //   auto mode   → auto_rate    (fetched from live API)
                    $tableRate = floatval($currencyRow->effective_rate ?? 0);

                    if ($tableRate > 0 && abs($tableRate - 1.0) > 0.0001) {
                        $exchangeRates = $tableRate;
                    } else {
                        // Priority 3: live API lookup
                        $exchangeRates = app(CurrencyService::class)
                            ->getBusinessRate($business, $employeePayCurrency, $primaryCurrency);
                    }
                } else {
                    $exchangeRates = app(CurrencyService::class)
                        ->getBusinessRate($business, $employeePayCurrency, $primaryCurrency);
                }
            }

            $targetCurrency = $employeePayCurrency;

            // Final safety: if rate still looks wrong (= 1.0 or 0), don't show column
            if ($exchangeRates <= 0 || abs($exchangeRates - 1.0) < 0.0001) {
                $showConversion = false;
                $exchangeRates  = 1.0;
                $targetCurrency = $primaryCurrency;
            }
        }

        Log::debug('viewPayslip FX decision', [
            'employee_id'          => $id,
            'primary_currency'     => $primaryCurrency,
            'employee_pay_currency' => $employeePayCurrency,
            'show_conversion'      => $showConversion,
            'exchange_rate'        => $exchangeRates,
            'target_currency'      => $targetCurrency,
        ]);

        // Passed to blade:
        //   $exchangeRates  = e.g. 100  (1 USD = 100 KES)
        //   $targetCurrency = e.g. 'USD'
        //
        // Blade rule:
        //   if $showConversion (derived in blade from $exchangeRates & $targetCurrency):
        //     foreignAmount = kesAmount / $exchangeRates
        //   header: "USD (R: 100)"
        //   note:   "Exchange rate: 1 USD = 100 KES"

        return view('payroll.reports.payslip', compact(
            'employeePayroll',
            'business',
            'entity',
            'entityType',
            'exchangeRates',
            'targetCurrency'
        ));
    }

    // private function getExchangeRates($baseCurrency, $targetCurrency)
    // {
    //     try {
    //         $response = Http::get("https://api.frankfurter.dev/v1/latest", [
    //             'base' => $baseCurrency,
    //             'symbols' => $targetCurrency
    //         ]);

    //         if ($response->successful()) {
    //             $data = $response->json();
    //             $exchangeRate = $data['rates'][$targetCurrency] ?? null;
    //             if (is_numeric($exchangeRate)) {
    //                 return floatval($exchangeRate);
    //             }
    //             return 1.0;
    //         } else {
    //             Log::error('Frankfurter API Error: ' . $response->body());
    //             return 1.0;
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Frankfurter API Exception: ' . $e->getMessage());
    //         return 1.0;
    //     }
    // }

    public function viewPayroll(Request $request, $payroll_id)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $payroll_id = $request->id;
        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $payroll_id)
            ->with(['employeePayrolls' => function ($q) use ($request) {
                $q->when($request->filled('location'), function ($q2) use ($request) {
                    $locationId = $request->location;
                    $q2->whereHas('employee', function ($emp) use ($locationId) {
                        if (str_starts_with($locationId, 'business_')) {
                            $emp->whereNull('location_id');
                        } else {
                            $emp->where('location_id', $locationId);
                        }
                    });
                });
                $q->when($request->filled('department'), function ($q2) use ($request) {
                    $q2->whereHas('employee.employmentDetails', fn($d) => $d->where('department_id', $request->department));
                });
                $q->when($request->filled('job_category'), function ($q2) use ($request) {
                    $q2->whereHas('employee.employmentDetails', fn($d) => $d->where('job_category_id', $request->job_category));
                });
            }, 'employeePayrolls.employee.user', 'employeePayrolls.employee.paymentDetails', 'employeePayrolls.employee.payrollDetail'])
            ->firstOrFail();

        $payroll->currency = $payroll->currency ?? 'KES';

        $entity = $business;
        $entityType = 'business';
        $page = $entity->company_name . ' Payroll - ' . $payroll->payrun_month . ' - ' . $payroll->payrun_year;

        $locationFilter = $request->query('location');
        $isMainBusinessFilter = $locationFilter && str_starts_with($locationFilter, 'business_');

        if ($payroll->location_id && !$isMainBusinessFilter) {
            $location = Location::where('id', $payroll->location_id)
                ->where('business_id', $business->id)
                ->first();
            if ($location) {
                $entity = $location;
                $entityType = 'location';
                $page = $entity->name . ' Payroll - ' . $payroll->payrun_month . ' - ' . $payroll->payrun_year;
            }
        }

        $totals = [
            'totalBasicSalary' => 0.00,
            'totalGrossPay' => 0.00,
            'totalOvertime' => 0.00,
            'totalAllowances' => 0.00,
            'totalShif' => 0.00,
            'totalNssf' => 0.00,
            'totalPaye' => 0.00,
            'totalHousingLevy' => 0.00,
            'totalHelb' => 0.00,
            'totalLoans' => 0.00,
            'totalAdvances' => 0.00,
            'totalCustomDeductions' => 0.00,
            'totalTaxableIncome' => 0.00,
            'totalPersonalRelief' => 0.00,
            'totalInsuranceRelief' => 0.00,
            'totalReliefs' => 0.00,
            'totalPayAfterTax' => 0.00,
            'totalDeductionsAfterTax' => 0.00,
            'totalNetPay' => 0.00,
            'totalAbsenteeismCharge' => 0.00,
            'totalPayeBeforeReliefs' => 0.00,
            'totalStatutoryDeductions' => 0.00,
        ];

        foreach ($payroll->employeePayrolls as $ep) {
            $overtime = json_decode($ep->overtime, true) ?? ['amount' => 0.00];
            $allowances = json_decode($ep->allowances, true) ?? [];
            $deductions = json_decode($ep->deductions, true) ?? [];
            $reliefs = json_decode($ep->reliefs, true) ?? [];

            $totals['totalBasicSalary'] += (float) ($ep->basic_salary ?? 0);
            $totals['totalGrossPay'] += (float) ($ep->gross_pay ?? 0);
            $totals['totalOvertime'] += (float) ($overtime['amount'] ?? 0);
            $totals['totalAllowances'] += (float) array_sum(array_map(fn($a) => $a['amount'] ?? 0, $allowances));
            $totals['totalShif'] += (float) ($ep->shif ?? ($deductions['shif'] ?? 0));
            $totals['totalNssf'] += (float) ($ep->nssf ?? ($deductions['nssf'] ?? 0));
            $totals['totalPaye'] += (float) ($ep->paye ?? ($deductions['paye'] ?? 0));
            $totals['totalHousingLevy'] += (float) ($ep->housing_levy ?? ($deductions['housing_levy'] ?? 0));
            $totals['totalHelb'] += (float) ($ep->helb ?? ($deductions['helb'] ?? 0));
            $totals['totalLoans'] += (float) ($ep->loan_repayment ?? ($deductions['loan_repayment'] ?? 0));
            $totals['totalAdvances'] += (float) ($ep->advance_recovery ?? ($deductions['advance_recovery'] ?? 0));
            $totals['totalTaxableIncome'] += (float) ($ep->taxable_income ?? 0);
            $totals['totalPersonalRelief'] += (float) ($ep->personal_relief ?? ($reliefs['personal-relief']['amount'] ?? 0));
            $totals['totalInsuranceRelief'] += (float) ($ep->insurance_relief ?? ($reliefs['insurance-relief']['amount'] ?? 0));
            $totals['totalReliefs'] += (float) array_sum(array_map(
                fn($r) => is_array($r) ? floatval($r['display_amount'] ?? $r['amount'] ?? 0) : 0,
                $reliefs
            ));
            $totals['totalPayAfterTax'] += (float) ($ep->pay_after_tax ?? 0);
            $totals['totalDeductionsAfterTax'] += (float) ($ep->deductions_after_tax ?? 0);
            $totals['totalNetPay'] += (float) ($ep->net_pay ?? 0);
            $totals['totalPayeBeforeReliefs'] += (float) ($ep->paye_before_reliefs ?? 0);

            $customDeductions = array_filter($deductions, function ($deduction) {
                if (!is_array($deduction) || !isset($deduction['name'])) return false;
                $name = strtolower($deduction['name']);
                return !in_array($name, ['shif', 'nssf', 'paye', 'housing levy', 'helb', 'loan repayment', 'advance recovery', 'absenteeism charge']);
            });
            $totals['totalCustomDeductions'] += (float) array_sum(array_map(fn($d) => $d['amount'] ?? 0.0, $customDeductions));

            $absenteeism = array_filter($deductions, fn($d) => is_array($d) && stripos($d['name'] ?? '', 'Absenteeism Charge') !== false);
            $totals['totalAbsenteeismCharge'] += (float) array_sum(array_map(fn($d) => $d['amount'] ?? 0.0, $absenteeism));
        }

        $totals['totalStatutoryDeductions'] = $totals['totalShif'] + $totals['totalNssf'] + $totals['totalPaye'] + $totals['totalHousingLevy'] + $totals['totalHelb'];

        return view('payroll.view', compact('business', 'payroll', 'entity', 'entityType', 'page', 'totals'));
    }

    public function downloadColumn(Request $request, $payroll_id, $column, $format)
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);

        $payroll_id = $request->id;
        $column = $request->column;
        $format = $request->format;

        if (!$business) {
            abort(404, 'Business not found.');
        }

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $payroll_id)
            ->with(['employeePayrolls.employee.user', 'employeePayrolls.employee'])
            ->firstOrFail();

        $payrunYear = $payroll->payrun_year;
        $payrunMonth = $payroll->payrun_month;

        $validColumns = [
            'basic_salary',
            'gross_pay',
            'net_pay',
            'meal_allowance',
            'tax_no',
            'overtime',
            'shif',
            'nssf',
            'paye',
            'paye_before_reliefs',
            'housing_levy',
            'helb',
            'taxable_income',
            'personal_relief',
            'insurance_relief',
            'pay_after_tax',
            'loan_repayment',
            'advance_recovery',
            'deductions_after_tax',
            'attendance_present',
            'attendance_absent',
            'days_in_month',
            'bank_name',
            'account_number'
        ];

        $column = strtolower(trim($column));
        $format = strtolower(trim($format));
        if (!in_array($column, $validColumns)) {
            abort(400, 'Invalid column name.');
        }

        $filteredPayrolls = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.user', 'employee']);

        $data = $filteredPayrolls->map(function ($ep) use ($column) {
            $employee = $ep->employee;
            $user = $employee->user;
            $deductions = json_decode($ep->deductions, true) ?? [];
            $allowances = json_decode($ep->allowances, true) ?? [];
            $reliefs = json_decode($ep->reliefs, true) ?? [];
            $overtime = floatval(json_decode($ep->overtime, true)['amount'] ?? 0);

            $getAllowance = function ($name) use ($allowances) {
                foreach ($allowances as $allowance) {
                    if (strtolower($allowance['name'] ?? '') === strtolower($name)) return floatval($allowance['amount'] ?? 0);
                }
                return 0.0;
            };

            $getRelief = function ($key) use ($reliefs, $ep) {
                return isset($reliefs[$key]['amount']) ? floatval($reliefs[$key]['amount']) : floatval($ep->$key ?? 0);
            };

            if ($column === 'paye') {
                $payeValue = floatval($ep->paye ?? 0);
                $personalRelief = $getRelief('personal-relief');
                $insuranceRelief = $getRelief('insurance-relief');

                return [
                    $employee->tax_no ?? '',
                    $user->name ?? '',
                    in_array($employee->resident_status, ['Resident', 'Non-Resident']) ? $employee->resident_status : 'Resident',
                    $employee->kra_employee_status ?? 'Primary Employee',
                    $employee->disability_status ?? 'No',
                    '',
                    floatval($ep->gross_pay ?? 0),
                    floatval($getAllowance('Car Allowance')),
                    floatval($getAllowance('Meal Allowance')),
                    floatval(0),
                    'Benefit Not Given',
                    '',
                    floatval(max(0, ($ep->gross_pay ?? 0) - ($ep->basic_salary ?? 0) - $getAllowance('Housing Allowance') - $getAllowance('Transport Allowance') - $overtime)),
                    '',
                    floatval($ep->shif ?? 0),
                    floatval($ep->nssf ?? 0),
                    floatval($ep->other_pension_contribution ?? 0),
                    floatval(0),
                    floatval($getRelief('mortgage-interest-relief')),
                    floatval($ep->housing_levy ?? 0),
                    '',
                    $personalRelief,
                    $insuranceRelief,
                    '',
                    $payeValue
                ];
            } else {
                $row = ['employee_name' => $user->name ?? 'N/A', 'employee_code' => $employee->employee_code ?? 'N/A'];
                switch ($column) {
                    case 'shif':
                        $nameParts = explode(' ', $user->name ?? 'N/A', 2);
                        $row = [$employee->employee_code ?? 'N/A', $nameParts[0] ?? 'N/A', $nameParts[1] ?? '', $employee->national_id ?? 'N/A', $employee->tax_no ?? 'N/A', $employee->nhif_no ?? 'N/A', floatval($ep->shif ?? 0), $user->phone ?? 'N/A'];
                        break;
                    case 'nssf':
                        $nameParts = explode(' ', $user->name ?? 'N/A', 2);
                        $row = [$employee->employee_code ?? 'N/A', $nameParts[1] ?? '', $nameParts[0] ?? 'N/A', $employee->national_id ?? 'N/A', $employee->tax_no ?? 'N/A', $employee->nssf_no ?? 'N/A', floatval($ep->gross_pay ?? 0), ''];
                        break;
                    case 'housing_levy':
                        $row = [$employee->employee_code ?? 'N/A', $user->name ?? 'N/A', $employee->tax_no ?? 'N/A', floatval($ep->housing_levy ?? 0)];
                        break;
                    default:
                        $value = match ($column) {
                            'basic_salary' => $ep->basic_salary ?? 0,
                            'gross_pay' => $ep->gross_pay ?? 0,
                            'net_pay' => $ep->net_pay ?? 0,
                            'tax_no' => $employee->tax_no ?? 'N/A',
                            'overtime' => $overtime,
                            'helb' => $ep->helb ?? 0,
                            'taxable_income' => $ep->taxable_income ?? 0,
                            'personal_relief' => $getRelief('personal-relief'),
                            'insurance_relief' => $getRelief('insurance-relief'),
                            'pay_after_tax' => $ep->pay_after_tax ?? 0,
                            'loan_repayment' => $ep->loan_repayment ?? 0,
                            'advance_recovery' => $ep->advance_recovery ?? 0,
                            'deductions_after_tax' => $ep->deductions_after_tax ?? 0,
                            'attendance_present' => $ep->attendance_present ?? 0,
                            'attendance_absent' => $ep->attendance_absent ?? 0,
                            'days_in_month' => $ep->days_in_month ?? 0,
                            'bank_name' => $ep->bank_name ?? 'N/A',
                            'account_number' => $employee->account_number ?? 'N/A',
                            'paye_before_reliefs' => $ep->paye_before_reliefs ?? 0,
                            default => 0,
                        };
                        $row = [$user->name ?? 'N/A', $employee->employee_code ?? 'N/A', $employee->tax_no ?? 'N/A', floatval($ep->basic_salary ?? 0), floatval($ep->gross_pay ?? 0), floatval($ep->net_pay ?? 0), is_numeric($value) ? floatval($value) : $value];
                        break;
                }
                return array_values($row);
            }
        })->toArray();

        $monthName = Carbon::createFromFormat('m', $payrunMonth)->format('F');
        $fileName = "payroll-{$payrunYear}-{$monthName}-{$column}.{$format}";
        $currency = $payroll->currency ?? 'KES';

        switch ($format) {
            case 'pdf':
                try {
                    $pdf = Pdf::loadView('payroll.download_column', [
                        'business' => $business,
                        'payroll' => $payroll,
                        'column' => $column,
                        'data' => $data,
                        'currency' => $currency,
                        'headers' => ($column === 'paye') ? ['PIN of Employee', 'Name of Employee', 'Resident Status', 'Type of Employee', 'Payee With Disability PWD', 'Exemption Certificate Number', 'Total Cash Pay (A)', 'Value of Car Benefit (B)', 'Value of Meals (C)', 'Other Non Cash Benefits (D)', 'Type of Housing', 'Housing Benefit (F)', 'Other Benefits (G)', 'Total Gross Pay (Ksh)', 'Social Health Contribution (J)', 'NSSF Contributions (K)', 'Other Pension Contribution (K)', 'Post Retirement Medical Fund/SHIP (L)', 'Mortgage Interest (M)', 'Affordable Housing Levy (N)', 'Taxable Pay (Ksh)', 'Monthly Personal Relief (P)', 'Amount of Insurance Relief', 'PAYE Tax (Ksh)', 'Self Assessed PAYE Tax (Ksh)'] : [],
                    ]);
                    return $pdf->download($fileName);
                } catch (\Exception $e) {
                    abort(500, 'Failed to generate PDF.');
                }

            case 'csv':
                $headers = [];
                if ($column === 'shif') $headers = ['PAYROLL NUMBER', 'FIRSTNAME', 'LASTNAME', 'ID NO', 'KRA PIN', 'SHIF NO', 'CONTRIBUTION AMOUNT', 'PHONE'];
                elseif ($column === 'nssf') $headers = ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'];
                elseif ($column === 'housing_levy') $headers = ['EMP NO', 'FULL NAME', 'TAX_NO', 'HOUSE_LEVY AMOUNT'];

                $csvData = '';
                if (!empty($headers)) {
                    $csvData .= implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers)) . "\n";
                }
                foreach ($data as $row) {
                    $csvData .= implode(',', array_map(fn($v) => is_numeric($v) ? strval($v) : '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
                }
                return Response::make($csvData, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => "attachment; filename=\"{$fileName}\""]);

            case 'xlsx':
                try {
                    $columnType = $column;
                    return Excel::download(new class($data, $columnType) implements
                        \Maatwebsite\Excel\Concerns\FromArray,
                        \Maatwebsite\Excel\Concerns\WithHeadings {
                        private $data;
                        private $columnType;
                        public function __construct(array $data, string $columnType)
                        {
                            $this->data = $data;
                            $this->columnType = $columnType;
                        }
                        public function array(): array
                        {
                            return $this->data;
                        }
                        public function headings(): array
                        {
                            if ($this->columnType === 'paye') return ['PIN of Employee', 'Name of Employee', 'Resident Status', 'Type of Employee', 'Payee With Disability PWD', 'Exemption Certificate Number', 'Total Cash Pay (A)', 'Value of Car Benefit (B)', 'Value of Meals (C)', 'Other Non Cash Benefits (D)', 'Type of Housing', 'Housing Benefit (F)', 'Other Benefits (G)', 'Total Gross Pay (Ksh)', 'Social Health Contribution (J)', 'NSSF Contributions (K)', 'Other Pension Contribution (K)', 'Post Retirement Medical Fund/SHIP (L)', 'Mortgage Interest (M)', 'Affordable Housing Levy (N)', 'Taxable Pay (Ksh)', 'Monthly Personal Relief (P)', 'Amount of Insurance Relief', 'PAYE Tax (Ksh)', 'Self Assessed PAYE Tax (Ksh)'];
                            elseif ($this->columnType === 'shif') return ['PAYROLL NUMBER', 'FIRSTNAME', 'LASTNAME', 'ID NO', 'KRA PIN', 'SHIF NO', 'CONTRIBUTION AMOUNT', 'PHONE'];
                            elseif ($this->columnType === 'nssf') return ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'];
                            elseif ($this->columnType === 'housing_levy') return ['EMP NO', 'FULL NAME', 'TAX_NO', 'HOUSE_LEVY AMOUNT'];
                            return array_map('ucwords', array_keys($this->data[0] ?? []));
                        }
                    }, $fileName);
                } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
                    abort(500, 'Failed to generate Excel file.');
                }

            default:
                abort(400, 'Invalid format.');
        }
    }

    public function downloadReport(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) abort(404, 'Business not found.');

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $request->payroll_id)
            ->with(['employeePayrolls.employee.user'])
            ->firstOrFail();

        $type = strtolower($request->type);
        $validTypes = ['shif', 'nssf', 'paye', 'nhdf', 'tax_filing', 'bank_advice', 'company_payslip'];
        if (!in_array($type, $validTypes)) abort(404, "Invalid report type: {$type}");

        $filteredEmployeePayrolls = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.user']);

        $data = $filteredEmployeePayrolls->map(function ($ep) {
            $deductions = json_decode($ep->deductions, true) ?? [];
            $overtime = json_decode($ep->overtime, true) ?? ['amount' => 0];
            $allowances = json_decode($ep->allowances, true) ?? [];

            return [
                'employee_name' => $ep->employee->user->name ?? 'N/A',
                'employee_code' => $ep->employee->employee_code ?? 'N/A',
                'tax_no' => $ep->employee->tax_no ?? 'N/A',
                'basic_salary' => (float) ($ep->basic_salary ?? 0),
                'gross_pay' => (float) ($ep->gross_pay ?? 0),
                'overtime' => (float) ($overtime['amount'] ?? 0),
                'shif' => (float) ($ep->shif ?? ($deductions['shif'] ?? 0)),
                'nssf' => (float) ($ep->nssf ?? ($deductions['nssf'] ?? 0)),
                'paye' => (float) ($ep->paye ?? ($deductions['paye'] ?? 0)),
                'paye_before_reliefs' => (float) ($ep->paye_before_reliefs ?? 0),
                'housing_levy' => (float) ($ep->housing_levy ?? ($deductions['housing_levy'] ?? 0)),
                'helb' => (float) ($ep->helb ?? ($deductions['helb'] ?? 0)),
                'taxable_income' => (float) ($ep->taxable_income ?? 0),
                'personal_relief' => (float) ($ep->personal_relief ?? 0),
                'insurance_relief' => (float) ($ep->insurance_relief ?? 0),
                'pay_after_tax' => (float) ($ep->pay_after_tax ?? 0),
                'loan_repayment' => (float) ($ep->loan_repayment ?? ($deductions['loan_repayment'] ?? 0)),
                'advance_recovery' => (float) ($ep->advance_recovery ?? ($deductions['advance_recovery'] ?? 0)),
                'deductions_after_tax' => (float) ($ep->deductions_after_tax ?? 0),
                'net_pay' => (float) ($ep->net_pay ?? 0),
                'attendance_present' => (int) ($ep->attendance_present ?? 0),
                'attendance_absent' => (int) ($ep->attendance_absent ?? 0),
                'days_in_month' => (int) ($ep->days_in_month ?? 0),
                'bank_name' => $ep->bank_name ?? 'N/A',
                'account_number' => $ep->account_number ?? 'N/A',
                'allowances' => $allowances,
                'deductions' => $deductions,
            ];
        })->toArray();

        $totals = [
            'totalBasicSalary' => array_sum(array_column($data, 'basic_salary')),
            'totalGrossPay' => array_sum(array_column($data, 'gross_pay')),
            'totalOvertime' => array_sum(array_column($data, 'overtime')),
            'totalShif' => array_sum(array_column($data, 'shif')),
            'totalNssf' => array_sum(array_column($data, 'nssf')),
            'totalPaye' => array_sum(array_column($data, 'paye')),
            'totalHousingLevy' => array_sum(array_column($data, 'housing_levy')),
            'totalHelb' => array_sum(array_column($data, 'helb')),
            'totalLoans' => array_sum(array_column($data, 'loan_repayment')),
            'totalAdvances' => array_sum(array_column($data, 'advance_recovery')),
            'totalNetPay' => array_sum(array_column($data, 'net_pay')),
            'totalTaxableIncome' => array_sum(array_column($data, 'taxable_income')),
            'totalPersonalRelief' => array_sum(array_column($data, 'personal_relief')),
            'totalInsuranceRelief' => array_sum(array_column($data, 'insurance_relief')),
            'totalPayAfterTax' => array_sum(array_column($data, 'pay_after_tax')),
            'totalDeductionsAfterTax' => array_sum(array_column($data, 'deductions_after_tax')),
        ];

        $entity = $payroll->location_id ? ($payroll->location ?? $business) : $business;
        $entityType = $payroll->location_id ? 'location' : 'business';

        if (!view()->exists("payroll.reports.{$type}")) abort(404, "Report view for {$type} not found");

        try {
            $pdf = Pdf::loadView("payroll.reports.{$type}", [
                'business' => $business,
                'payroll' => $payroll,
                'entity' => $entity,
                'entityType' => $entityType,
                'data' => $data,
                'totals' => $totals,
            ])->setPaper('a4', 'landscape');
            return $pdf->download("{$type}_report_{$payroll->payrun_year}_{$payroll->payrun_month}.pdf");
        } catch (\Exception $e) {
            \Log::error("Report generation failed for type {$type}: " . $e->getMessage());
            abort(500, 'Failed to generate report.');
        }
    }

    public function downloadBankAdvice($year, $month, Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) abort(404, 'Business not found.');

        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);
        $format = $request->format;

        $payroll = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->where('payrun_month', $month)
            ->with(['employeePayrolls.employee.paymentDetails', 'business'])
            ->firstOrFail();

        switch (strtolower($format)) {
            case 'pdf':
                try {
                    $filteredEPs = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.paymentDetails']);
                    $payroll->setRelation('employeePayrolls', $filteredEPs);
                    $pdf = Pdf::loadView('payroll.reports.bank_advice', ['payroll' => $payroll])->setPaper('a4', 'landscape');
                    return $pdf->download("bank_advice_{$payroll->payrun_year}_{$payroll->payrun_month}.pdf");
                } catch (\Exception $e) {
                    abort(500, 'Failed to generate PDF.');
                }
            case 'csv':
            case 'xlsx':
                try {
                    $filteredEPs = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.paymentDetails']);
                    $payroll->setRelation('employeePayrolls', $filteredEPs);
                    return Excel::download(new BankAdviceExport($payroll), "bank_advice_{$payroll->payrun_year}_{$payroll->payrun_month}.{$format}");
                } catch (\Exception $e) {
                    abort(500, 'Failed to export data.');
                }
            default:
                abort(400, 'Unsupported format.');
        }
    }

    public function downloadNssfMonthlySummary(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');
        $year = $request->year ?? date('Y');
        try {
            return Excel::download(new NssfMonthlySummaryExport($business->id, $year), "NSSF_Monthly_Summary_{$year}.xlsx");
        } catch (\Exception $e) {
            return RequestResponse::badRequest('Failed to generate NSSF monthly summary: ' . $e->getMessage());
        }
    }

    public function downloadShifMonthlySummary(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');
        $year = $request->year ?? date('Y');
        try {
            return Excel::download(new ShifMonthlySummaryExport($business->id, $year), "SHIF_Monthly_Summary_{$year}.xlsx");
        } catch (\Exception $e) {
            return RequestResponse::badRequest('Failed to generate SHIF monthly summary: ' . $e->getMessage());
        }
    }

    public function downloadNhifMonthlySummary(Request $request)
    {
        return $this->downloadShifMonthlySummary($request);
    }

    public function downloadP9(Request $request, $businessSlug, $year, $format)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) abort(404, 'Business not found.');

        $payrolls = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->with(['employeePayrolls.employee.user'])
            ->get();

        if ($payrolls->isEmpty()) abort(404, 'No payroll data found for the year ' . $year);

        $employees = $payrolls->flatMap->employeePayrolls->pluck('employee')->unique('id');

        $data = $employees->map(function ($employee) use ($payrolls, $year) {
            $monthlyData = array_fill(1, 12, [
                'basic_salary' => 0,
                'benefits_non_cash' => 0,
                'value_of_quarters' => 0,
                'total_gross_pay' => 0,
                'retirement_e1' => 0,
                'retirement_e2' => 0,
                'retirement_e3' => 30000,
                'owner_occupied_interest' => 0,
                'retirement_contribution' => 0,
                'chargeable_pay' => 0,
                'tax_charged' => 0,
                'personal_relief' => 2400,
                'insurance_relief' => 0,
                'paye' => 0,
            ]);

            foreach ($payrolls as $payroll) {
                if (!$employee || !isset($employee->id)) continue;
                $ep = $payroll->employeePayrolls->where('employee_id', $employee->id)->first();
                if ($ep) {
                    $month = (int) $payroll->payrun_month;
                    $deductions = json_decode($ep->deductions, true) ?? [];
                    $basicSalary = (float) ($ep->basic_salary ?? 0);
                    $grossPay = (float) ($ep->gross_pay ?? 0);
                    $taxableIncome = (float) ($ep->taxable_income ?? 0);
                    $paye = (float) ($ep->paye ?? ($deductions['paye'] ?? 0));
                    $personalRelief = (float) ($ep->personal_relief ?? 2400);
                    $insuranceRelief = (float) ($ep->insurance_relief ?? 0);
                    $retirementE1 = $basicSalary * 0.3;
                    $retirementE2 = (float) ($deductions['retirement_contribution'] ?? 0);

                    $monthlyData[$month] = [
                        'basic_salary' => $basicSalary,
                        'benefits_non_cash' => 0,
                        'value_of_quarters' => 0,
                        'total_gross_pay' => $grossPay,
                        'retirement_e1' => $retirementE1,
                        'retirement_e2' => $retirementE2,
                        'retirement_e3' => 30000,
                        'owner_occupied_interest' => 0,
                        'retirement_contribution' => min($retirementE1, $retirementE2, 20000),
                        'chargeable_pay' => $taxableIncome,
                        'tax_charged' => $paye + $personalRelief + $insuranceRelief,
                        'personal_relief' => $personalRelief,
                        'insurance_relief' => $insuranceRelief,
                        'paye' => $paye,
                    ];
                }
            }

            $totals = [];
            foreach (array_keys($monthlyData[1]) as $key) {
                $totals[$key] = array_sum(array_column($monthlyData, $key));
            }

            return ['employee_name' => $employee->user->name ?? 'N/A', 'tax_no' => $employee->tax_no ?? 'N/A', 'monthly_data' => $monthlyData, 'totals' => $totals];
        })->toArray();

        $format = strtolower($format);
        $filename = "P9_{$year}";

        switch ($format) {
            case 'pdf':
                $pdf = Pdf::loadView('payroll.reports.p9', ['business' => $business, 'year' => $year, 'data' => $data])->setPaper('a4', 'landscape');
                return $pdf->download("{$filename}.pdf");
            case 'csv':
                return Excel::download(new P9Export($data), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
                return Excel::download(new P9Export($data), "{$filename}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
            default:
                abort(400, "Unsupported format: {$format}");
        }
    }

    public function downloadSingleP9(Request $request, $businessSlug, $employeeId, $year, $format)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) abort(404, 'Business not found.');

        $payrolls = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->with(['employeePayrolls.employee.user'])
            ->get();

        if ($payrolls->isEmpty()) abort(404, 'No payroll data found for the year ' . $year);

        $employeePayrolls = $payrolls->flatMap->employeePayrolls->where('employee_id', $employeeId);
        if ($employeePayrolls->isEmpty()) abort(404, 'No payroll data found for employee ID ' . $employeeId . ' in year ' . $year);

        $employee = $employeePayrolls->first()->employee;

        $monthlyData = array_fill(1, 12, [
            'basic_salary' => 0,
            'benefits_non_cash' => 0,
            'value_of_quarters' => 0,
            'total_gross_pay' => 0,
            'retirement_e1' => 0,
            'retirement_e2' => 0,
            'retirement_e3' => 30000,
            'owner_occupied_interest' => 0,
            'retirement_contribution' => 0,
            'chargeable_pay' => 0,
            'tax_charged' => 0,
            'personal_relief' => 2400,
            'insurance_relief' => 0,
            'paye' => 0,
        ]);

        foreach ($payrolls as $payroll) {
            $ep = $payroll->employeePayrolls->where('employee_id', $employee->id)->first();
            if ($ep) {
                $month = (int) $payroll->payrun_month;
                $deductions = json_decode($ep->deductions, true) ?? [];
                $reliefs = json_decode($ep->reliefs, true) ?? [];
                $basicSalary = (float) ($ep->basic_salary ?? 0);
                $grossPay = (float) ($ep->gross_pay ?? 0);
                $taxableIncome = (float) ($ep->taxable_income ?? 0);
                $paye = (float) ($ep->paye ?? ($deductions['paye'] ?? 0));
                $personalRelief = (float) ($reliefs['personal-relief']['amount'] ?? ($ep->personal_relief ?? 2400));
                $insuranceRelief = (float) ($reliefs['insurance-relief']['amount'] ?? ($ep->insurance_relief ?? 0));
                $retirementE1 = $basicSalary * 0.3;
                $retirementE2 = (float) ($deductions['retirement_contribution'] ?? 0);

                $monthlyData[$month] = [
                    'basic_salary' => $basicSalary,
                    'benefits_non_cash' => 0,
                    'value_of_quarters' => 0,
                    'total_gross_pay' => $grossPay,
                    'retirement_e1' => $retirementE1,
                    'retirement_e2' => $retirementE2,
                    'retirement_e3' => 30000,
                    'owner_occupied_interest' => 0,
                    'retirement_contribution' => min($retirementE1, $retirementE2, 20000),
                    'chargeable_pay' => $taxableIncome,
                    'tax_charged' => $paye + $personalRelief + $insuranceRelief,
                    'personal_relief' => $personalRelief,
                    'insurance_relief' => $insuranceRelief,
                    'paye' => $paye,
                ];
            }
        }

        $totals = [];
        foreach (array_keys($monthlyData[1]) as $key) {
            $totals[$key] = array_sum(array_column($monthlyData, $key));
        }

        $data = ['employee_name' => $employee->user->name ?? 'N/A', 'tax_no' => $employee->tax_no ?? 'N/A', 'monthly_data' => $monthlyData, 'totals' => $totals];
        $format = strtolower($format);
        $filename = "P9_{$employee->user->name}_{$year}";

        switch ($format) {
            case 'pdf':
                $pdf = Pdf::loadView('payroll.reports.p9', ['business' => $business, 'year' => $year, 'data' => [$data]])->setPaper('a4', 'landscape');
                return $pdf->download("{$filename}.pdf");
            case 'csv':
                return Excel::download(new P9Export([$data]), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);
            case 'xlsx':
                return Excel::download(new P9Export([$data]), "{$filename}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
            default:
                abort(400, "Unsupported format: {$format}");
        }
    }

    public function sendPayslips(Request $request)
    {
        try {
            $request->validate([
                'payroll_id' => 'required_without:employee_payroll_id|exists:payrolls,id',
                'employee_payroll_id' => 'required_without:payroll_id|exists:employee_payrolls,id',
            ]);

            $payrollId = $request->input('payroll_id');
            $employeePayrollId = $request->input('employee_payroll_id');
            $business = Business::findBySlug(session('active_business_slug'));

            if (!$business) return response()->json(['error' => 'Business not found.'], 400);

            if ($employeePayrollId) {
                $employeePayroll = EmployeePayroll::with(['employee.user', 'payroll.business', 'payroll.location'])
                    ->where('id', $employeePayrollId)
                    ->whereHas('payroll', fn($q) => $q->where('business_id', $business->id))
                    ->first();
                if (!$employeePayroll) return response()->json(['error' => 'Employee payroll not found.'], 404);
                $employeePayrolls = collect([$employeePayroll]);
                $payroll = $employeePayroll->payroll;
            } else {
                $payroll = Payroll::where('business_id', $business->id)->where('id', $payrollId)->with(['employeePayrolls.employee.user'])->first();
                if (!$payroll) return response()->json(['error' => 'Payroll not found.'], 404);
                $employeePayrolls = $payroll->employeePayrolls;
            }

            $sentCount = 0;
            foreach ($employeePayrolls as $employeePayroll) {
                $user = $employeePayroll->employee->user;
                if (!$user || !$user->email) continue;

                $entity = $business;
                $entityType = 'business';
                if ($employeePayroll->payroll->location_id) {
                    $location = Location::where('id', $employeePayroll->payroll->location_id)->where('business_id', $business->id)->first();
                    if ($location) {
                        $entity = $location;
                        $entityType = 'location';
                    }
                }

                try {
                    $pdf = Pdf::loadView('payroll.reports.payslip', compact('employeePayroll', 'business', 'entity', 'entityType'));
                    $fileName = 'payslip_' . $employeePayroll->id . '_' . time() . '.pdf';
                    $filePath = storage_path('app/public/payslips/' . $fileName);
                    if (!file_exists(storage_path('app/public/payslips'))) mkdir(storage_path('app/public/payslips'), 0755, true);
                    $pdf->save($filePath);
                } catch (\Exception $e) {
                    Log::error("Failed to generate PDF for employee payroll ID {$employeePayroll->id}: {$e->getMessage()}");
                    continue;
                }

                try {
                    Mail::to($user->email)->send(new PayslipMail($employeePayroll, $filePath, $user->name));
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send email for employee ID {$employeePayroll->employee_id}: {$e->getMessage()}");
                    continue;
                }
            }

            if ($payrollId && !$employeePayrollId && $sentCount > 0) $payroll->update(['emailed' => true]);

            $message = $employeePayrollId ? 'Payslip queued for sending.' : "Payslips queued for sending ($sentCount sent).";
            return response()->json(['success' => true, 'message' => $message, 'data' => ['sent_count' => $sentCount]], 200);
        } catch (\Exception $e) {
            Log::error('Unexpected error in sendPayslips: ' . $e->getMessage());
            return response()->json(['error' => 'Server error occurred.'], 500);
        }
    }

    public function close(Request $request)
    {
        $payroll = Payroll::findOrFail($request->payroll_id);
        $payroll->update(['status' => 'closed']);
        if ($request->carry_forward) $this->carryForward($payroll);
        return RequestResponse::ok('Payroll closed successfully.');
    }

    private function carryForward(Payroll $payroll)
    {
        $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)->get();
        foreach ($employeePayrolls as $ep) {
            if ($ep->loan_repayment > 0) {
                $remaining = Loan::where('employee_id', $ep->employee_id)->sum(DB::raw('amount - (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE loan_id = loans.id)'));
                if ($remaining > 0) {
                    Loan::where('employee_id', $ep->employee_id)
                        ->whereRaw('amount > (SELECT COALESCE(SUM(amount), 0) FROM loan_repayments WHERE loan_id = loans.id)')
                        ->first()
                        ->update(['monthly_repayment' => min($remaining, $ep->loan_repayment)]);
                }
            }
            if ($ep->advance_recovery > 0) {
                $remaining = Advance::where('employee_id', $ep->employee_id)->sum('amount');
                if ($remaining > 0) {
                    Advance::where('employee_id', $ep->employee_id)->where('amount', '>', 0)->first()
                        ->update(['amount' => max(0, $remaining - $ep->advance_recovery)]);
                }
            }
        }
    }

    public function filter(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $month = $request->filled('month') ? (int) $request->month : null;

        $query = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->when($month, fn($q) => $q->where('payrun_month', $month))
            ->withCount(['employeePayrolls as no_of_payslips'])
            ->with(['employeePayrolls' => function ($q) use ($request) {
                $q->select('id', 'payroll_id', 'employee_id', 'net_pay')
                    ->when($request->filled('location'), function ($emp) use ($request) {
                        $locationId = $request->location;
                        $emp->whereHas('employee', function ($e) use ($locationId) {
                            if (str_starts_with($locationId, 'business_')) $e->whereNull('location_id');
                            else $e->where('location_id', $locationId);
                        });
                    })
                    ->when($request->filled('department'), fn($emp) => $emp->whereHas('employee.employmentDetails', fn($d) => $d->where('department_id', $request->department)))
                    ->when($request->filled('job_category'), fn($emp) => $emp->whereHas('employee.employmentDetails', fn($d) => $d->where('job_category_id', $request->job_category)));
            }])
            ->latest('updated_at');

        if ($request->filled('location')) {
            $locationId = $request->location;
            $query->whereHas('employeePayrolls.employee', function ($q) use ($locationId) {
                if (str_starts_with($locationId, 'business_')) $q->whereNull('location_id');
                else $q->where('location_id', $locationId);
            });
        }
        if ($request->filled('department')) $query->whereHas('employeePayrolls.employee.employmentDetails', fn($q) => $q->where('department_id', $request->department));
        if ($request->filled('job_category')) $query->whereHas('employeePayrolls.employee.employmentDetails', fn($q) => $q->where('job_category_id', $request->job_category));
        if ($request->filled('employee')) $query->whereHas('employeePayrolls.employee', fn($q) => $q->where('id', $request->employee));

        $payrolls = $query->get();
        $html = view('payroll._past', compact('payrolls', 'business'))->render();
        return RequestResponse::ok('Payrolls filtered successfully.', ['html' => $html]);
    }

    public function downloadMasterRoll(Request $request)
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);
        if (!$business) return response()->json(['error' => 'Business not found.'], 404);

        $id = $request->id ?? $request->route('id');
        $type    = in_array($request->type, ['detailed', 'summary']) ? $request->type : 'detailed';
        $format  = in_array(strtolower($request->format), ['xlsx', 'csv', 'pdf']) ? strtolower($request->format) : 'xlsx';
        $groupBy = in_array($request->groupBy, ['location', 'department', 'job_category']) ? $request->groupBy : null;

        $payroll = Payroll::where('business_id', $business->id)->where('id', $id)->firstOrFail();
        $monthName = \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F');
        $groupSuffix = $groupBy ? "-by-{$groupBy}" : '';
        $fileName = "master-roll-{$payroll->payrun_year}-{$monthName}-{$type}{$groupSuffix}.{$format}";

        if (in_array($format, ['xlsx', 'csv'])) {
            $filteredEmployeeIds = $this->getFilteredEmployeePayrolls($payroll, $request)->pluck('employee_id')->toArray();
            return Excel::download(new \App\Exports\Masterrollexport($payroll, $business, $type, $groupBy, $filteredEmployeeIds), $fileName);
        }

        if ($format === 'pdf') {
            $employeePayrolls = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.user', 'employee.location', 'employee.employmentDetails.department', 'employee.employmentDetails.jobCategory', 'payroll']);
            $allowanceSlugs = [];
            $deductionSlugs = [];
            $statutoryNames = ['shif', 'nssf', 'paye', 'housing levy', 'absenteeism', 'absenteeism charge'];

            foreach ($employeePayrolls as $ep) {
                foreach ((json_decode($ep->allowances, true) ?? []) as $item) {
                    if (!is_array($item) || empty($item['item_name'])) continue;
                    $iname = trim($item['item_name']);
                    if (strtolower($iname) === 'overtime allowance') continue;
                    $key = strtolower($iname);
                    if (!isset($allowanceSlugs[$key])) $allowanceSlugs[$key] = $iname;
                }
                foreach ((json_decode($ep->deductions, true) ?? []) as $item) {
                    if (!is_array($item) || empty($item['item_name'])) continue;
                    $iname = trim($item['item_name']);
                    if (in_array(strtolower($iname), $statutoryNames)) continue;
                    $key = strtolower($iname);
                    if (!isset($deductionSlugs[$key])) $deductionSlugs[$key] = $iname;
                }
            }

            $employeeIds = $employeePayrolls->pluck('employee_id')->filter()->unique()->values();
            if ($employeeIds->isNotEmpty()) {
                $settings = \Illuminate\Support\Facades\DB::table('payroll_settings')
                    ->where('year', $payroll->payrun_year)->where('month', $payroll->payrun_month)
                    ->whereIn('employee_id', $employeeIds)->get(['allowances', 'deductions']);
                foreach ($settings as $ps) {
                    foreach ((json_decode($ps->allowances ?? '[]', true) ?? []) as $item) {
                        if (!is_array($item) || empty($item['item_name'])) continue;
                        $iname = trim($item['item_name']);
                        if (strtolower($iname) === 'overtime allowance') continue;
                        $key = strtolower($iname);
                        if (!isset($allowanceSlugs[$key])) $allowanceSlugs[$key] = $iname;
                    }
                    foreach ((json_decode($ps->deductions ?? '[]', true) ?? []) as $item) {
                        if (!is_array($item) || empty($item['item_name'])) continue;
                        $iname = trim($item['item_name']);
                        if (in_array(strtolower($iname), $statutoryNames)) continue;
                        $key = strtolower($iname);
                        if (!isset($deductionSlugs[$key])) $deductionSlugs[$key] = $iname;
                    }
                }
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.master_roll_pdf', [
                'business' => $business,
                'payroll' => $payroll,
                'employeePayrolls' => $employeePayrolls,
                'allowanceSlugs' => $allowanceSlugs,
                'deductionSlugs' => $deductionSlugs,
                'currency' => $payroll->currency ?? 'KES',
            ])->setPaper('a3', 'landscape');
            return $pdf->download($fileName);
        }

        return response()->json(['error' => 'Unsupported format.'], 400);
    }

    public function downloadNssf(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) abort(404, 'Business not found.');

        $payroll = Payroll::where('business_id', $business->id)->where('id', $request->payroll_id)->firstOrFail();
        $filteredEPs = $this->getFilteredEmployeePayrolls($payroll, $request, ['employee.user', 'employee.location', 'employee.employmentDetails.department', 'employee.employmentDetails.jobCategory']);
        $payroll->setRelation('employeePayrolls', $filteredEPs);

        $formatType = $request->format_type;
        $fileFormat = strtolower($request->format ?? 'xlsx');
        $groupBy    = $request->group_by ?? 'department';
        $monthName  = \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F');
        $baseName   = "NSSF_{$payroll->payrun_year}_{$monthName}";

        switch ($formatType) {
            case 'new_remittance':
                if ($fileFormat === 'pdf') abort(400, 'PDF not supported for this format.');
                return Excel::download(new \App\Exports\Nssfnewremittanceexport($payroll), "{$baseName}_New_Remittance.{$fileFormat}");
            case 'pre_2018':
                if ($fileFormat === 'pdf') abort(400, 'PDF not supported for this format.');
                return Excel::download(new \App\Exports\Nssfpre2018export($payroll), "{$baseName}_Pre2018.{$fileFormat}");
            case 'old_format':
                if ($fileFormat === 'pdf') abort(400, 'PDF not supported for this format.');
                return Excel::download(new \App\Exports\Nssfoldformatexport($payroll), "{$baseName}_Old_Format.{$fileFormat}");
            case 'grouped':
                $groupLabel = ucfirst(str_replace('_', ' ', $groupBy));
                $fileName   = "{$baseName}_Grouped_by_{$groupLabel}.{$fileFormat}";
                if ($fileFormat === 'pdf') {
                    $data = $this->buildNssfGroupedPdfData($payroll, $groupBy);
                    $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_grouped_pdf', ['business' => $business, 'payroll' => $payroll, 'data' => $data, 'groupBy' => $groupLabel])->setPaper('a4', 'landscape');
                    return $pdf->download($fileName);
                }
                return Excel::download(new \App\Exports\Nssfgroupedexport($payroll, $groupBy), $fileName);
            case 'schedule':
                $data = $this->buildNssfScheduleData($payroll);
                $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_schedule_pdf', ['business' => $business, 'payroll' => $payroll, 'data' => $data])->setPaper('a4', 'landscape');
                return $pdf->download("{$baseName}_Schedule.pdf");
            default:
                abort(400, "Unknown NSSF format type: {$formatType}");
        }
    }

    public function downloadNssfMonthlySummaryWithFormat(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $year = $request->year ?? date('Y');
        $fileFormat = strtolower($request->format ?? 'xlsx');
        $fileName = "NSSF_Monthly_Summary_{$year}.{$fileFormat}";

        try {
            if ($fileFormat === 'pdf') {
                $rows = $this->buildNssfMonthlySummaryRows($business, $year);
                $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_monthly_summary_pdf', ['business' => $business, 'year' => $year, 'rows' => $rows])->setPaper('a3', 'landscape');
                return $pdf->download($fileName);
            }
            return Excel::download(new \App\Exports\NssfMonthlySummaryExport($business, $year), $fileName);
        } catch (\Exception $e) {
            return RequestResponse::badRequest('Failed to generate NSSF monthly summary: ' . $e->getMessage());
        }
    }

    private function buildNssfScheduleData(Payroll $payroll): array
    {
        return EmployeePayroll::where('payroll_id', $payroll->id)->with(['employee.user'])->get()
            ->map(function ($ep) {
                $nssfTotal  = floatval($ep->nssf ?? 0);
                $employee   = $ep->employee;
                $user       = $employee->user ?? null;
                return [
                    'payroll_no' => $employee->employee_code ?? 'N/A',
                    'name'       => $user->name ?? 'N/A',
                    'id_no'      => $employee->national_id ?? 'N/A',
                    'nssf_no'    => $employee->nssf_no ?? 'N/A',
                    'gross_pay'  => floatval($ep->gross_pay ?? 0),
                    'employee'   => round($nssfTotal / 2, 2),
                    'employer'   => round($nssfTotal / 2, 2),
                    'total'      => $nssfTotal,
                ];
            })->toArray();
    }

    private function buildNssfGroupedPdfData(Payroll $payroll, string $groupBy): array
    {
        $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)
            ->with(['employee.user', 'employee.location', 'employee.employmentDetails.department', 'employee.employmentDetails.jobCategory'])
            ->get();

        $grouped = $employeePayrolls->groupBy(function ($ep) use ($groupBy) {
            $employee = $ep->employee;
            return match ($groupBy) {
                'department'   => $employee->employmentDetails?->department?->name ?? 'No Department',
                'location'     => $employee->location?->name ?? 'Head Office',
                'job_category' => $employee->employmentDetails?->jobCategory?->name ?? 'No Category',
                default        => 'All',
            };
        });

        $result = [];
        foreach ($grouped as $groupName => $eps) {
            $employees = $eps->map(function ($ep) {
                $nssfTotal = floatval($ep->nssf ?? 0);
                $employee  = $ep->employee;
                $user      = $employee->user ?? null;
                return [
                    'payroll_no' => $employee->employee_code ?? 'N/A',
                    'name'       => $user->name ?? 'N/A',
                    'id_no'      => $employee->national_id ?? 'N/A',
                    'nssf_no'    => $employee->nssf_no ?? 'N/A',
                    'gross_pay'  => floatval($ep->gross_pay ?? 0),
                    'employee'   => round($nssfTotal / 2, 2),
                    'employer'   => round($nssfTotal / 2, 2),
                    'total'      => $nssfTotal,
                ];
            })->toArray();

            $result[] = [
                'group_name' => $groupName,
                'employees'  => $employees,
                'subtotal'   => [
                    'gross_pay' => array_sum(array_column($employees, 'gross_pay')),
                    'employee'  => array_sum(array_column($employees, 'employee')),
                    'employer'  => array_sum(array_column($employees, 'employer')),
                    'total'     => array_sum(array_column($employees, 'total')),
                ],
            ];
        }
        return $result;
    }

    protected function buildNssfMonthlySummaryRows(Business $business, int $year): array
    {
        $months = range(1, 12);
        $payrolls = \App\Models\Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)->whereIn('payrun_month', $months)->get()->keyBy('payrun_month');

        $allEmployeeIds = \App\Models\EmployeePayroll::whereIn('payroll_id', $payrolls->pluck('id'))->distinct()->pluck('employee_id');
        $employees = \App\Models\Employee::whereIn('id', $allEmployeeIds)->with('user')->get()->keyBy('id');

        $nssfByEmployeeMonth = [];
        foreach ($payrolls as $month => $payroll) {
            $eps = \App\Models\EmployeePayroll::where('payroll_id', $payroll->id)->get(['employee_id', 'nssf', 'deductions']);
            foreach ($eps as $ep) {
                $nssf = floatval($ep->nssf ?? 0);
                if ($nssf == 0) {
                    $deductions = json_decode($ep->deductions, true) ?? [];
                    $nssf = floatval($deductions['nssf'] ?? 0);
                }
                $nssfByEmployeeMonth[$ep->employee_id][$month] = $nssf;
            }
        }

        $rows = [];
        $monthTotals = array_fill(1, 12, 0.0);

        foreach ($employees as $empId => $employee) {
            $name = $employee->user->name ?? 'N/A';
            $rowTotal = 0;
            $rowMonths = [];
            foreach ($months as $m) {
                $amount = $nssfByEmployeeMonth[$empId][$m] ?? 0;
                $rowMonths[$m] = $amount;
                $rowTotal += $amount;
                if ($amount > 0) $monthTotals[$m] += $amount;
            }
            if ($rowTotal == 0) continue;
            $rows[] = array_merge(['name' => $name], array_map(fn($v) => $v > 0 ? $v : null, $rowMonths), ['total' => $rowTotal]);
        }

        $grandTotal = array_sum($monthTotals);
        $rows[] = array_merge(['name' => 'Total'], array_map(fn($v) => $v > 0 ? $v : null, $monthTotals), ['total' => $grandTotal]);
        return $rows;
    }

    public function variancePage(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) abort(404);
        $years = \App\Models\Payroll::where('business_id', $business->id)->distinct()->orderByDesc('payrun_year')->pluck('payrun_year')->take(5)->toArray();
        return view('payroll.variance', compact('business', 'years'));
    }

    public function downloadVarianceReport(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $mode   = $request->get('mode', 'year');
        $format = strtolower($request->get('format', 'xlsx'));
        $reasons = [];
        if ($request->has('reasons')) {
            $decoded = json_decode($request->get('reasons'), true);
            if (is_array($decoded)) $reasons = $decoded;
        }

        $params = ['mode' => $mode];
        if ($mode === 'year') {
            $params['year1'] = intval($request->get('year1', date('Y') - 1));
            $params['year2'] = intval($request->get('year2', date('Y')));
            $label = "{$params['year1']}_vs_{$params['year2']}";
        } else {
            $params['year1']  = intval($request->get('year1', date('Y')));
            $params['month1'] = intval($request->get('month1', 1));
            $params['year2']  = intval($request->get('year2', date('Y')));
            $params['month2'] = intval($request->get('month2', 2));
            $mn = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
            $label = "{$mn[$params['month1']]}{$params['year1']}_vs_{$mn[$params['month2']]}{$params['year2']}";
        }

        $fileName = "Payroll_Variance_{$label}.{$format}";

        try {
            if ($format === 'pdf') {
                $data = $this->buildVarianceData($business, $params);
                $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.payroll_variance_pdf', compact('business', 'params', 'data', 'reasons'))->setPaper('a4', 'landscape');
                return $pdf->download($fileName);
            }
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\Payrollvarianceexport($business, $params, $reasons), $fileName);
        } catch (\Exception $e) {
            return RequestResponse::badRequest('Failed to generate report: ' . $e->getMessage());
        }
    }

    public function varianceData(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return response()->json(['error' => 'Business not found'], 404);

        $mode   = $request->get('mode', 'year');
        $params = ['mode' => $mode];

        if ($mode === 'year') {
            $params['year1'] = intval($request->get('year1', date('Y') - 1));
            $params['year2'] = intval($request->get('year2', date('Y')));
        } else {
            $params['year1']  = intval($request->get('year1', date('Y')));
            $params['month1'] = intval($request->get('month1', 1));
            $params['year2']  = intval($request->get('year2', date('Y')));
            $params['month2'] = intval($request->get('month2', 2));
        }

        $data = $this->buildVarianceData($business, $params);
        return response()->json(['business' => $business->company_name ?? $business->name, 'currency' => $business->currency ?? 'KES', 'params' => $params, 'data' => $data]);
    }

    private function buildVarianceData(Business $business, array $params): array
    {
        $monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        $fields = ['gross' => 'Gross Pay', 'net' => 'Net Pay', 'paye' => 'PAYE', 'nssf' => 'NSSF', 'shif' => 'SHIF', 'hl' => 'Housing Levy'];

        $fetch = function (int $year, ?int $month = null) use ($business): array {
            $q = \App\Models\Payroll::where('business_id', $business->id)->where('payrun_year', $year)->with('employeePayrolls');
            if ($month) $q->where('payrun_month', $month);
            $payrolls = $q->get();
            $agg = [];
            foreach ($payrolls as $p) {
                $m = $month ?? intval($p->payrun_month);
                $agg[$m] = [
                    'gross' => floatval($p->employeePayrolls->sum('gross_pay')),
                    'net'   => floatval($p->employeePayrolls->sum('net_pay')),
                    'paye'  => floatval($p->employeePayrolls->sum('paye')),
                    'nssf'  => floatval($p->employeePayrolls->sum('nssf')),
                    'shif'  => floatval($p->employeePayrolls->sum('shif')),
                    'hl'    => floatval($p->employeePayrolls->sum('housing_levy')),
                    'count' => $p->employeePayrolls->count(),
                ];
            }
            return $agg;
        };

        $vPct = fn($base, $cmp) => $base != 0 ? round((($cmp - $base) / abs($base)) * 100, 2) : 0;

        if ($params['mode'] === 'year') {
            $d1  = $fetch($params['year1']);
            $d2  = $fetch($params['year2']);
            $sum = fn($d, $k) => array_sum(array_column($d, $k));

            $summary = [];
            foreach ($fields as $key => $label) {
                $v1 = $sum($d1, $key);
                $v2 = $sum($d2, $key);
                $summary[] = ['metric' => $label, 'period1' => $v1, 'period2' => $v2, 'variance' => $v2 - $v1, 'variance_pct' => $vPct($v1, $v2)];
            }

            $monthly = [];
            foreach ($monthNames as $m => $name) {
                $g1 = $d1[$m]['gross'] ?? 0;
                $g2 = $d2[$m]['gross'] ?? 0;
                $monthly[] = ['month' => $name, 'period1' => $g1, 'period2' => $g2, 'variance' => $g2 - $g1, 'var_pct' => $vPct($g1, $g2), 'count1' => $d1[$m]['count'] ?? 0, 'count2' => $d2[$m]['count'] ?? 0];
            }
            return compact('summary', 'monthly');
        } else {
            $r1 = $fetch($params['year1'], $params['month1'])[$params['month1']] ?? array_fill_keys(['gross', 'net', 'paye', 'nssf', 'shif', 'hl', 'count'], 0);
            $r2 = $fetch($params['year2'], $params['month2'])[$params['month2']] ?? array_fill_keys(['gross', 'net', 'paye', 'nssf', 'shif', 'hl', 'count'], 0);
            $summary = [];
            foreach ($fields as $key => $label) {
                $summary[] = ['metric' => $label, 'period1' => $r1[$key], 'period2' => $r2[$key], 'variance' => $r2[$key] - $r1[$key], 'variance_pct' => $vPct($r1[$key], $r2[$key])];
            }
            return ['summary' => $summary, 'monthly' => []];
        }
    }

    private function getFilteredEmployeePayrolls(
        \App\Models\Payroll $payroll,
        \Illuminate\Http\Request $request,
        array $with = ['employee.user']
    ): \Illuminate\Support\Collection {

        $query = \App\Models\EmployeePayroll::where('payroll_id', $payroll->id)->with($with);

        if ($request->filled('location')) {
            $locationId = $request->location;
            $query->whereHas('employee', function ($q) use ($locationId) {
                if (str_starts_with($locationId, 'business_')) $q->whereNull('location_id');
                else $q->where('location_id', $locationId);
            });
        }
        if ($request->filled('department')) {
            $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }
        if ($request->filled('job_category')) {
            $query->whereHas('employee.employmentDetails', function ($q) use ($request) {
                $q->where('job_category_id', $request->job_category);
            });
        }

        return $query->get();
    }
}
