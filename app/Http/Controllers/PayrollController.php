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
            'overtimes' => fn($q) => $q->whereYear('date', $year)->whereMonth('date', $month),
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

                return $hasSettings && $settingsData ? array_merge($sourceData, array_map(function ($item) {
                    return array_merge($item, [
                        'amount' => floatval($item['amount'] ?? 0),
                        'rate' => floatval($item['rate'] ?? 0),
                    ]);
                }, $settingsData)) : $sourceData;
            };

            $mapOvertime = function ($settingsData, $employeeOvertimes) use ($employee, $hasSettings) {
                $sourceData = $employeeOvertimes->mapWithKeys(function ($overtime) use ($employee) {
                    return [$overtime->id => [
                        'user_id' => $employee->user_id,
                        'employee_code' => $employee->employee_code,
                        'name' => $employee->user?->name ?? 'N/A',
                        'item_name' => "Overtime on {$overtime->date?->format('Y-m-d')}",
                        'item_id' => $overtime->id,
                        'amount' => floatval($overtime->overtime_hours ?? 0),
                        'is_active' => $overtime->to_be_paid ?? false,
                    ]];
                })->all();

                return $hasSettings && $settingsData ? array_merge($sourceData, array_map(function ($item) {
                    return array_merge($item, ['amount' => floatval($item['amount'] ?? 0)]);
                }, $settingsData)) : $sourceData;
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

        // Process all new data (active and inactive items)
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
                // ========== NEW: Update basic_salary for hourly employees ==========
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
                    'reliefs' => json_encode($data['reliefs']), // Store all reliefs as JSON
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
                ];

                if ($employeePayroll) {
                    $employeePayroll->update($payrollAttributes);
                } else {
                    EmployeePayroll::create($payrollAttributes);
                }

                $this->updateLoanAndAdvance($data, $year, $month, $options);
            }

            // Clear session data after successful storage
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
                'reliefs', // Remove whereNotNull('relief_id')
                'overtimes' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
                'advances' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
                'loans' => fn($q) => $q->where('start_date', '<=', Carbon::create($request->year, $request->month)->endOfMonth()),
'loans.repayments',
                'attendances' => fn($q) => $q->whereYear('date', $request->year)->whereMonth('date', $request->month),
            ]);

        // Location, department, job category filters remain unchanged
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

            // ========== FIX: Skip basic salary check for hourly employees ==========
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
            // ========================================================================

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

        // Load payroll settings for non-exempted employees
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

        // Store payroll data and metadata in session
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

            // ========== CRITICAL FIX: MUST fetch $paymentDetail FIRST ==========
            $paymentDetail = EmployeePaymentDetail::where('employee_id', $employeeId)->first();
            $payrollDetail = EmployeePayrollDetail::where('employee_id', $employeeId)->first();

            // Check if payment details exist - skip employee if missing
            if (!$paymentDetail) {
                Log::warning("No payment details found for employee {$employeeId}. Skipping.");
                continue;
            }

            // Now we can safely access payment detail properties
            $bankName = $paymentDetail->bank_name ?? 'N/A';
            $accountNumber = $paymentDetail->account_number ?? 'N/A';
            $bankCode = $paymentDetail->bank_code ?? 'Not Set';
            $bankBranch = $paymentDetail->bank_branch ?? 'Not Set';

            if ($paymentDetail->payment_type === 'hourly') {

                Log::debug("HOURLY payroll branch hit", [
                    'employee_id' => $employeeId,
                    'payment_type' => $paymentDetail->payment_type
                ]);

                // Use HourlyPayCalculator for hourly employees
                $calculator = app(\App\Services\HourlyPayCalculator::class);

                $startDate = Carbon::create($year, $month, 1)->startOfMonth();
                $endDate = Carbon::create($year, $month, 1)->endOfMonth();

                try {
                    // Calculate hourly pay from attendance
                    $hourlyPayData = $calculator->calculateHourlyGrossPay(
                        $employee,
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d')
                    );

                    // CRITICAL: Check if calculation was successful
                    if (!isset($hourlyPayData['regular_pay']) || !isset($hourlyPayData['overtime_pay'])) {
                        throw new \Exception('Invalid hourly pay data structure returned from calculator');
                    }

                    // Set salary values from hourly calculation
                    $proratedBasicSalary = $hourlyPayData['regular_pay'];
                    $overtimePay = $hourlyPayData['overtime_pay'];

                    // ========== NEW: Update basic_salary in database ==========

                    $paymentDetail->update(['basic_salary' => $proratedBasicSalary]);

                    Log::info('Updated basic_salary for hourly employee during calculation', [
                        'employee_id' => $employeeId,
                        'calculated_basic_salary' => $proratedBasicSalary,
                        'hours_worked' => $hourlyPayData['hours_worked'],
                    ]);
                    // ===========================================================

                    // Initialize allowances array
                    $allowances = [];

                    // Store hourly breakdown in allowances for payslip display
                    $allowances['hourly_breakdown'] = [
                        'name' => 'Hourly Pay Breakdown',
                        'hours_worked' => $hourlyPayData['hours_worked'],
                        'overtime_hours' => $hourlyPayData['overtime_hours'],
                        'hourly_rate' => $hourlyPayData['hourly_rate'],
                        'overtime_rate' => $hourlyPayData['overtime_rate'],
                        'regular_pay' => $hourlyPayData['regular_pay'],
                        'overtime_pay' => $hourlyPayData['overtime_pay'],
                    ];


                    // Get attendance summary for hourly employees
                    $attendanceSummary = $calculator->getAttendanceSummary(
                        $employee,
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d')
                    );

                    $presentDays = $attendanceSummary['present_days'];
                    $absentDays = $attendanceSummary['absent_days'];
                    $absenteeismCharge = 0; // Already factored into hours worked

                    // Get additional allowances
                    $additionalAllowances = $this->getEmployeeItems(
                        $employee,
                        'allowances',
                        $settings,
                        Allowance::class,
                        EmployeeAllowance::class,
                        $proratedBasicSalary
                    );

                    // Merge with hourly breakdown
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
                    // Fallback to zero pay if hourly calculation fails
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
                $basicSalary = floatval($paymentDetail->basic_salary ?? 0);

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

                $totalTaxableAllowances = array_sum(array_map(
                    fn($a) => $a['is_taxable'] ? $a['amount'] : 0,
                    $allowances
                ));
                $totalNonTaxableAllowances = array_sum(array_map(
                    fn($a) => !$a['is_taxable'] ? $a['amount'] : 0,
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

                $grossPayBeforeAbsenteeism = $proratedBasicSalary + $totalTaxableAllowances + $totalNonTaxableAllowances + $overtimePay;
            }

            // ========== COMMON CALCULATION (both salary and hourly) ==========
            $grossPay = max(0, $grossPayBeforeAbsenteeism - $absenteeismCharge);

            // Get country and statutory deductions
            $country = strtoupper(trim($business->country));
            $statutoryDeductions = $this->getStatutoryDeductions($country, $business->id, $grossPay, $proratedBasicSalary, $employeeId, null);

            $nssfEmployee = $statutoryDeductions['nssf']['employee'] ?? 0;
            $nssfEmployer = $statutoryDeductions['nssf']['employer'] ?? 0;
            $nssfTotal = $statutoryDeductions['nssf']['total'] ?? ($nssfEmployee + $nssfEmployer);

            // Country-specific deductions and reliefs
            if ($country === 'UGANDA' || $country === 'UG') {
                // ========== UGANDA PAYROLL LOGIC ==========

                // Uganda: Only NSSF deduction (no SHIF, Housing Levy, HELB)
                $shif = 0;
                $housingLevy = 0;
                $helb = 0;

                // Taxable income for Uganda = Gross Pay - NSSF Employee contribution only
                $taxableIncome = max(0, $grossPay);

                // Uganda has NO tax reliefs (no personal relief, no insurance relief)
                $reliefs = [];
                $totalReliefs = 0;
                $personalRelief = 0;
                $insuranceRelief = 0;
                $isDisabilityExempt = false;

                Log::debug("Uganda payroll calculation", [
                    'employee_id' => $employeeId,
                    'gross_pay' => $grossPay,
                    'nssf_employee' => $nssfEmployee,
                    'taxable_income' => $taxableIncome,
                ]);
            } else {
                // ========== KENYA PAYROLL LOGIC (UNCHANGED) ==========

                // Kenya: All statutory deductions
                $shif = $statutoryDeductions['shif'] ?? 0;
                $housingLevy = $statutoryDeductions['housing-levy'] ?? 0;
                $helb = $statutoryDeductions['helb'] ?? 0;

                // Taxable income for Kenya
                $taxableIncome = max(0, $grossPay - $nssfTotal - $shif - $housingLevy - $helb);
                // ========== PWD TAX EXEMPTION ==========
                $pwdExemptionApplied = false;
                $pwdExemptionAmount  = 0;

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


                // Kenya has reliefs - keep all your existing relief calculation logic
                $reliefs = $this->getEmployeeItems($employee, 'reliefs', $settings, Relief::class, EmployeeRelief::class, $grossPay, $taxableIncome);
                $totalReliefs = 0;
                $isDisabilityExempt = false;

                // Ensure default personal relief for Kenya (unless explicitly disabled)
                if (!isset($reliefs['personal-relief']) || $reliefs['personal-relief']['amount'] == 0) {
                    $reliefs['personal-relief'] = [
                        'name' => 'Personal Relief',
                        'amount' => 2400, // Standard Kenyan personal relief
                        'is_taxable' => false,
                        'tax_application' => 'before_tax',
                    ];
                }

                // Process all reliefs (keep your existing relief processing code)
                foreach ($reliefs as $reliefSlug => $reliefData) {
                    $reliefModel = Relief::where('business_id', $business->id)->where('slug', $reliefSlug)->first();
                    if (!$reliefModel) continue;

                    $amount = floatval($reliefData['amount']);
                    $computationMethod = $reliefModel->computation_method;
                    $percentageOfAmount = floatval($reliefModel->percentage_of_amount ?? 0);
                    $limit = floatval($reliefModel->limit ?? PHP_FLOAT_MAX);

                    $baseForPercentage = match ($reliefModel->percentage_of ?? 'total_salary') {
                        'basic_salary' => $proratedBasicSalary,
                        'net_salary' => $grossPay - ($nssfTotal + $shif + $housingLevy + $helb),
                        'total_salary' => $grossPay,
                        default => $grossPay,
                    };

                    switch ($computationMethod) {
                        case 'fixed':
                            $computedRelief = min($amount, $limit);
                            break;
                        case 'percentage':
                            $computedRelief = min($baseForPercentage * ($percentageOfAmount / 100), $limit);
                            break;
                        default:
                            $computedRelief = min($amount, $limit);
                    }

                    if ($reliefSlug === 'personal-relief') {
                        $computedRelief = $amount > 0 ? min(2400, $computedRelief) : 2400;
                    } elseif ($reliefSlug === 'insurance-relief') {
                        $computedRelief = min(5000, $computedRelief);
                    } elseif ($reliefSlug === 'disabled-person-relief' && $amount > 0) {
                        $isDisabilityExempt = true;
                        $computedRelief = 0;
                    }

                    $reliefs[$reliefSlug]['amount'] = $computedRelief;
                    $totalReliefs += $computedRelief;
                }

                if ($isDisabilityExempt) {
                    $taxableIncome = 0;
                    $totalReliefs = 0;
                    $personalRelief = 0;
                    $insuranceRelief = 0;
                } else {
                    $personalRelief = $reliefs['personal-relief']['amount'] ?? 0;
                    $insuranceRelief = $reliefs['insurance-relief']['amount'] ?? 0;
                }
            }

            // Calculate PAYE based on country
            $payeBeforeReliefs = $this->calculatePAYEByCountry($country, $taxableIncome, $grossPay);

            if ($country === 'UGANDA' || $country === 'UG') {
                // Uganda: No reliefs applied, PAYE is the final tax
                $paye = $payeBeforeReliefs;
            } else {
                // Kenya: Apply reliefs to reduce PAYE
                $paye = max(0, $payeBeforeReliefs - $totalReliefs);
            }

            // Continue with rest of your existing code for deductions, loans, advances, etc.
            $deductions = $this->getEmployeeItems($employee, 'deductions', $settings, Deduction::class, EmployeeDeduction::class, $grossPay);
            $totalCustomDeductions = array_sum(array_map(fn($d) => $d['amount'], $deductions));

            $loanRepayment = $this->calculateLoanRepayment($employeeId, $year, $month, $settings, $options);
            $advanceRecovery = $this->calculateAdvanceRecovery($employeeId, $year, $month, $settings, $options);

            // Calculate total deductions (country-aware)
            if ($country === 'UGANDA' || $country === 'UG') {
                // Uganda: Only NSSF, PAYE, custom deductions, loans, advances, absenteeism
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
                // Kenya Net Pay = Taxable Income - PAYE (after reliefs) - After-tax deductions
                // After-tax deductions include: custom deductions, loans, advances, absenteeism
                $afterTaxDeductions = $totalCustomDeductions + $loanRepayment + $advanceRecovery + $absenteeismCharge;
                $netPay = floor(max(0, $taxableIncome - $paye - $afterTaxDeductions));

                // Calculate total deductions for Kenya (for reporting purposes)
                $totalDeductions = $nssfEmployee + $shif + $housingLevy + $helb + $paye + $afterTaxDeductions;

                Log::debug("Kenya Net Pay Calculation", [
                    'taxable_income' => $taxableIncome,
                    'paye_after_reliefs' => $paye,
                    'after_tax_deductions' => $afterTaxDeductions,
                    'net_pay' => $netPay,
                    'total_deductions_for_reporting' => $totalDeductions,
                ]);
            }

            // $netPay = floor(max(0, $grossPay - $totalDeductions));

            // Continue with your existing payrollData array population...
            $payrollData[$employeeId] = [
                'employee_id' => $employeeId,
                'employee' => $employee,
                'basic_salary' => $proratedBasicSalary,
                'gross_pay' => $grossPay,
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
                'deductions' => array_merge($deductions, [['name' => 'Absenteeism Charge', 'amount' => $absenteeismCharge]]),
                'net_pay' => $netPay,
                'taxable_income' => $taxableIncome,
                'reliefs' => $reliefs,
                'personal_relief' => $personalRelief,
                'insurance_relief' => $insuranceRelief,
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
            ];
        }
        return $payrollData;
    }

    // protected function getStatutoryDeductions($country, $businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    //     {
    //         $deductions = [];

    //         if (strtoupper($country) === 'KENYA' || strtoupper($country) === 'KE') {
    //             $statutorySlugs = ['nssf', 'shif', 'housing-levy', 'nhif', 'helb'];

    //             foreach ($statutorySlugs as $slug) {
    //                 $deductions[$slug] = $this->calculateStatutoryDeduction($businessId, $slug, $grossPay, $basicPay, $grossPay, $employeeId, $payrollId);
    //             }

    //             // NSSF split into employee and employer contributions
    //             $nssfTotal = $this->calculateNSSFContribution($businessId, $grossPay, $employeeId, $payrollId);
    //             $nssfEmployee = $nssfTotal / 2; // Employee pays half
    //             $nssfEmployer = $nssfTotal / 2; // Employer matches
    //             $deductions['nssf'] = [
    //                 'employee' => $nssfEmployee,
    //                 'employer' => $nssfEmployer,
    //                 'total' => $nssfTotal,
    //             ];

    //             $deductions['paye'] = 0;
    //         } else {
    //             $deductions = [
    //                 'nssf' => ['employee' => 0, 'employer' => 0, 'total' => 0],
    //                 'shif' => 0,
    //                 'housing-levy' => 0,
    //                 'nhif' => 0,
    //                 'helb' => 0,
    //                 'paye' => 0,
    //             ];
    //         }

    //         return $deductions;
    //     }

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

        $baseAmount = $grossPay; // NSSF uses gross_pay
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
        return $grossPay * 0.06; // Tier 1 only, up to 540
    } else {
        $tier1 = 540; // 6% of 9,000 (Tier 1 cap)
        $tier2 = min($grossPay - 9000, 99000) * 0.06; // 6% of earnings between 9,000 and 108,000
        return min($tier1 + $tier2, 5940); // capped at 6,480 employee contribution
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

    protected function calculateOvertime($employeeId, $year, $month, $settings, $options, $basicSalary)
    {
        $hourlyRate = $basicSalary / 173; // Standard 173 hours/month
        $totalOvertimePay = 0;

        if (!is_null($settings) && !empty($settings['overtime'])) {
            foreach ($settings['overtime'] as $item) {
                if ($item['is_active']) {
                    $overtime = Overtime::find($item['item_id']);
                    if ($overtime) {
                        $hours = floatval($item['amount']);
                        $rate = $overtime->rate ?? 1.5; // Default 1.5x rate
                        $totalOvertimePay += $hours * $hourlyRate * $rate;
                    }
                }
            }
        } elseif ($options['pay_overtime']['apply'] !== 'none') {
            $overtimes = Overtime::where('employee_id', $employeeId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
            foreach ($overtimes as $overtime) {
                if ($overtime->to_be_paid || isset($options['pay_overtime']['specific'][$overtime->id])) {
                    $hours = $overtime->overtime_hours;
                    $rate = $overtime->rate ?? 1.5;
                    $totalOvertimePay += $hours * $hourlyRate * $rate;
                }
            }
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

    //     protected function calculateNSSF($grossPay)
    //     {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             throw new Exception('Business not found.');
    //         }

    //         $totalContribution = $this->calculateNSSFContribution($business->id, $grossPay, null, null);

    //         return [
    //             'employee' => $totalContribution / 2, // Employee pays half
    //             'employer' => $totalContribution / 2, // Employer matches
    //             'total' => $totalContribution,
    //         ];
    //     }

    //     protected function calculatePAYE($taxableIncome)
    //     {
    //         $tax = 0;
    //         if ($taxableIncome <= 24000) {
    //             $tax = $taxableIncome * 0.10; // 10% on first 24,000
    //         } elseif ($taxableIncome <= 32333) {
    //             $tax = 2400 + (($taxableIncome - 24000) * 0.25); // 25% on next 8,333
    //         } elseif ($taxableIncome <= 500000) {
    //             $tax = 4482.25 + (($taxableIncome - 32333) * 0.30); // 30% on next 467,667
    //         } elseif ($taxableIncome <= 800000) {
    //             $tax = 144682.15 + (($taxableIncome - 500000) * 0.325); // 32.5% on next 300,000
    //         } else {
    //             $tax = 242182.15 + (($taxableIncome - 800000) * 0.35); // 35% above 800,000
    //         }
    //         return $tax;
    //     }

    protected function calculatePAYEByCountry($country, $taxableIncome, $grossPay = 0)
    {
        $countryUpper = strtoupper(trim($country));

        if ($countryUpper === 'UGANDA' || $countryUpper === 'UG') {
            return $this->calculateUgandaPAYE($taxableIncome);
        } elseif ($countryUpper === 'KENYA' || $countryUpper === 'KE') {
            return $this->calculateKenyaPAYE($taxableIncome);
        }

        // Default to Kenya if country not recognized
        Log::warning("Unknown country: {$country}, defaulting to Kenya PAYE calculation");
        return $this->calculateKenyaPAYE($taxableIncome);
    }

    protected function calculateUgandaPAYE($taxableIncome)
    {
        $tax = 0;

        if ($taxableIncome <= 235000) {
            // First bracket: 0% tax
            $tax = 0;
        } elseif ($taxableIncome <= 335000) {
            // Second bracket: 10% on amount above 235,000
            $tax = ($taxableIncome - 235000) * 0.10;
        } elseif ($taxableIncome <= 410000) {
            // Third bracket: Fixed 10,000 + 20% on amount above 335,000
            $tax = 10000 + (($taxableIncome - 335000) * 0.20);
        } else {
            // Fourth bracket: Fixed 25,000 + 30% on amount above 410,000
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
        $tax = 144783.35 + (($taxableIncome - 500000) * 0.325); // ← FIXED
    } else {
        $tax = 242283.35 + (($taxableIncome - 800000) * 0.35);  // ← FIXED
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

        // Default to Kenya
        Log::warning("Unknown country: {$country}, defaulting to Kenya statutory deductions");
        return $this->getKenyaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId);
    }

    protected function getUgandaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    {
        $deductions = [];

        // NSSF: 5% employee + 5% employer = 10% total
        $nssfEmployee = round($grossPay * 0.05, 2);
        $nssfTotal = round($nssfEmployee);

        $deductions['nssf'] = [
            'employee' => $nssfEmployee,
            'total' => $nssfTotal,
        ];

        // Uganda does not have these deductions
        $deductions['shif'] = 0;
        $deductions['housing-levy'] = 0;
        $deductions['nhif'] = 0;
        $deductions['helb'] = 0;
        $deductions['paye'] = 0; // Calculated separately

        Log::debug("Uganda statutory deductions calculated", [
            'employee_id' => $employeeId,
            'gross_pay' => $grossPay,
            'nssf_employee' => $nssfEmployee,
        ]);

        return $deductions;
    }

    /**
     * Kenya statutory deductions
     * This contains all your existing Kenya NSSF/SHIF/Housing Levy logic
     */
    protected function getKenyaStatutoryDeductions($businessId, $grossPay, $basicPay, $employeeId, $payrollId)
    {
        $deductions = [];
        $statutorySlugs = ['nssf', 'shif', 'housing-levy', 'nhif', 'helb'];

        foreach ($statutorySlugs as $slug) {
            $deductions[$slug] = $this->calculateStatutoryDeduction($businessId, $slug, $grossPay, $basicPay, $grossPay, $employeeId, $payrollId);
        }

        // NSSF split into employee and employer contributions
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
    //end
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
                        'gross_pay' => $grossPay ?? $baseAmount, // Gross pay may not be set yet, use base as proxy
                        default => $baseAmount,
                    };
                    $computedAmount = $modelItem->type === 'fixed'
                        ? $amount
                        : number_format($baseForCalc * ($rate / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => $modelItem->is_taxable,
                        'tax_application' => 'before_tax', // Default for allowances
                    ];
                } elseif ($type === 'reliefs') {
                    $baseForPercentage = match ($modelItem->percentage_of ?? 'total_salary') {
                        'basic_salary' => $baseAmount,
                        'net_salary' => $grossPay - $totalDeductions ?? $baseAmount, // Approximate
                        'total_salary' => $grossPay ?? $baseAmount,
                        default => $grossPay ?? $baseAmount,
                    };
                    $computedAmount = $modelItem->computation_method === 'fixed'
                        ? min($amount, floatval($modelItem->limit ?? PHP_FLOAT_MAX))
                        : number_format($baseForPercentage * (floatval($modelItem->percentage_of_amount ?? 0) / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => false,
                        'tax_application' => 'before_tax',
                    ];
                } elseif ($type === 'deductions') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $grossPay ?? $baseAmount,
                        'taxable_pay' => $taxableIncome,
                        'cash_pay' => $grossPay - $nssfEmployee ?? $baseAmount, // Approximate
                        default => $grossPay ?? $baseAmount,
                    };
                    switch ($modelItem->computation_method) {
                        case 'fixed':
                            $computedAmount = $amount;
                            break;
                        case 'rate':
                            $computedAmount = number_format($baseForCalc * ($rate / 100), 3, '.', '');
                            break;
                        case 'formula':
                            // Simplified formula handling (extend as needed)
                            $computedAmount = $modelItem->actual_amount ? $amount : number_format($baseForCalc * 0.05, 3, '.', ''); // Example for FringeBenefit(5%)
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
                        'gross_pay' => $grossPay ?? $baseAmount,
                        default => $baseAmount,
                    };
                    $computedAmount = $modelItem->type === 'fixed'
                        ? $amount
                        : number_format($baseForCalc * ($rate / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => $modelItem->is_taxable,
                        'tax_application' => 'before_tax',
                    ];
                } elseif ($type === 'reliefs') {
                    $baseForPercentage = match ($modelItem->percentage_of ?? 'total_salary') {
                        'basic_salary' => $baseAmount,
                        'net_salary' => $grossPay - $totalDeductions ?? $baseAmount,
                        'total_salary' => $grossPay ?? $baseAmount,
                        default => $grossPay ?? $baseAmount,
                    };
                    $computedAmount = $modelItem->computation_method === 'fixed'
                        ? min($amount, floatval($modelItem->limit ?? PHP_FLOAT_MAX))
                        : number_format($baseForPercentage * (floatval($modelItem->percentage_of_amount ?? 0) / 100), 3, '.', '');
                    $items[$modelItem->slug] = [
                        'name' => $modelItem->name,
                        'amount' => $computedAmount,
                        'is_taxable' => false,
                        'tax_application' => 'before_tax',
                    ];
                } elseif ($type === 'deductions') {
                    $baseForCalc = match ($modelItem->calculation_basis) {
                        'basic_pay' => $baseAmount,
                        'gross_pay' => $grossPay ?? $baseAmount,
                        'taxable_pay' => $taxableIncome,
                        'cash_pay' => $grossPay - $nssfEmployee ?? $baseAmount,
                        default => $grossPay ?? $baseAmount,
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
        $locations = $business->locations ?? collect();
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

            // Calculate updated totals after deletion
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
            Log::info('Parsed payrun_month', ['year' => $year, 'month' => $month, 'payroll_id' => $payroll->id]);
            Log::info('Payroll record', ['id' => $payroll->id, 'business_id' => $payroll->business_id, 'payrun_month' => $payroll->payrun_month]);
            Log::info('EmployeePayrolls raw', ['count' => $payroll->employeePayrolls->count(), 'ids' => $payroll->employeePayrolls->pluck('id')->toArray()]);

            $p9Dir = storage_path('app/public/p9/');
            if (!File::exists($p9Dir)) {
                File::makeDirectory($p9Dir, 0777, true);
                Log::info('Created p9 storage directory', ['path' => $p9Dir]);
            }

            $successCount = 0;
            Log::info('Before manual fetch', ['payroll_id' => $payroll->id, 'employeePayrolls_count' => $payroll->employeePayrolls->count()]);
            $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)->with(['employee.user'])->get();
            Log::info('Fetched EmployeePayrolls', ['count' => $employeePayrolls->count(), 'ids' => $employeePayrolls->pluck('id')->toArray(), 'employee_ids' => $employeePayrolls->pluck('employee_id')->toArray()]);
            if ($employeePayrolls->isEmpty()) {
                Log::error('Manual fetch returned no records', ['payroll_id' => $payroll->id]);
                return RequestResponse::badRequest('No payroll records found for the given ID.');
            }

            foreach ($employeePayrolls as $employeePayroll) {
                Log::info('Processing employeePayroll', ['id' => $employeePayroll->id, 'employee_id' => $employeePayroll->employee_id, 'employee' => $employeePayroll->employee ? 'exists' : 'null']);
                $employee = $employeePayroll->employee;
                if (!$employee) {
                    Log::warning('Skipping employeePayroll with null employee', ['employeePayroll_id' => $employeePayroll->id, 'employee_id' => $employeePayroll->employee_id]);
                    continue;
                }

                $user = $employee->user;
                Log::info('Employee user', ['employee_id' => $employeePayroll->employee_id, 'user' => $user ? 'exists' : 'null', 'user_id' => $employee->user_id, 'email' => $user->email ?? 'null']);
                if (!$user) {
                    Log::warning('No user found for employee, using fallback', ['employee_id' => $employeePayroll->employee_id, 'user_id' => $employee->user_id]);
                    $user = new \stdClass();
                    $user->email = 'no-email-' . $employeePayroll->employee_id . '@example.com';
                }

                // Fetch all payroll data for the employee for the year
                $allEmployeePayrolls = EmployeePayroll::where('employee_id', $employeePayroll->employee_id)
                    ->whereHas('payroll', function ($query) use ($business, $year) {
                        $query->where('business_id', $business->id)->where('payrun_year', $year);
                    })
                    ->with('payroll')
                    ->get();

                // Initialize 12-month data with defaults
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

                // Populate monthlyData with actual data from all payrolls
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

                    // Calculate actual NSSF contribution (from deductions or specific field)
                    $nssfContribution = (float) ($deductions['nssf'] ?? $payrollEntry->nssf ?? 0.00);

                    // Calculate other pension contribution (from deductions)
                    $pensionContribution = (float) ($deductions['pension'] ?? 0.00);

                    // retirement_e2: Defined Contribution (Actual) = NSSF + other pension contribution
                    $retirementE2 = ($basicSalary > 0 || $grossPay > 0) ? ($nssfContribution + $pensionContribution) : 0.00;

                    // Calculate allowable retirement contribution
                    $actualRetirement = $retirementE2; // Total actual contribution
                    $maxRetirement = min($actualRetirement, $basicSalary * 0.3, 30000.00);
                    $retirementContribution = $maxRetirement;

                    // Post-Retirement Medical Fund (assume from deductions if available, capped at 15,000)
                    $postRetirementMedical = min((float) ($deductions['post_retirement_medical'] ?? 0.00), 15000.00);

                    // Mortgage Interest (assume from deductions if available, capped at 30,000)
                    $mortgageInterest = min((float) ($deductions['mortgage_interest'] ?? 0.00), 30000.00);

                    // Insurance Relief (override with stored value if available, else calculate)
                    $insurancePremium = (float) ($deductions['insurance_premium'] ?? 0.00);
                    $insuranceRelief = ($insuranceRelief > 0) ? $insuranceRelief : min($insurancePremium * 0.15, 60000.00 / 12);

                    // Total deductions (using stored values where available)
                    $totalDeductions = $retirementContribution + $housingLevy + $shif + $postRetirementMedical + $mortgageInterest;

                    // Taxable income (use stored value if available)
                    $chargeablePay = ($taxableIncome > 0) ? $taxableIncome : max(0, $grossPay - $totalDeductions);

                    // Tax charged (use stored value if available)
                    $taxCharged = ($payeBeforeReliefs > 0) ? $payeBeforeReliefs : 0;
                    if ($chargeablePay > 0 && $taxCharged == 0) {
                        if ($chargeablePay <= 24000) {
                            $taxCharged = $chargeablePay * 0.1;
                        } elseif ($chargeablePay <= 32333) {
                            $taxCharged = 2400 + ($chargeablePay - 24000) * 0.15;
                        } elseif ($chargeablePay <= 500000) {
                            $taxCharged = 3724 + ($chargeablePay - 32333) * 0.2;
                        } elseif ($chargeablePay <= 800000) {
                            $taxCharged = 97224 + ($chargeablePay - 500000) * 0.25;
                        } else {
                            $taxCharged = 172224 + ($chargeablePay - 800000) * 0.3;
                        }
                    }

                    // Apply personal relief (use stored value if available)
                    $personalRelief = ($personalRelief > 0) ? $personalRelief : (($basicSalary > 0 || $grossPay > 0) ? 2400.00 : 0.00);
                    $paye = ($paye > 0) ? $paye : max(0, $taxCharged - $personalRelief);

                    // Apply retirement_e3 only if data exists
                    $retirement_e3 = ($basicSalary > 0 || $grossPay > 0) ? 30000.00 : 0.00;

                    $monthlyData[$payrollMonth] = [
                        'basic_salary' => $basicSalary,
                        'benefits_non_cash' => 0.00,
                        'value_of_quarters' => 0.00,
                        'total_gross_pay' => $grossPay,
                        'retirement_e1' => $basicSalary * 0.3, // For reference
                        'retirement_e2' => $retirementE2, // Defined Contribution (Actual)
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
                Log::info('Populated monthly data', ['employee_id' => $employeePayroll->employee_id, 'monthly_data' => $monthlyData]);

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

                $data = [
                    [
                        'employee_name' => $employeeDetails['company_name'],
                        'tax_no' => $employeeDetails['tax_no'],
                        'main_name' => $employeeDetails['main_name'],
                        'pin' => $employeeDetails['pin'],
                        'nssf' => $employeeDetails['nssf'],
                        'shif' => $employeeDetails['shif'],
                        'monthly_data' => $monthlyData,
                        'totals' => $totals,
                    ],
                ];

                Log::info('Generated P9 data', ['data' => $data]);

                if (!view()->exists('payroll.reports.p9')) {
                    Log::error('View payroll.reports.p9 does not exist');
                    return RequestResponse::serverError('P9 view not found.');
                }

                try {
                    $pdf = Pdf::loadView('payroll.reports.p9', array_merge(['business' => $business, 'year' => $year], ['data' => $data]))
                        ->setPaper('A4', 'landscape');
                    $pdfPath = storage_path('app/public/p9/' . $employeePayroll->id . '.pdf');
                    $pdf->save($pdfPath);

                    Log::info('Attempting to send email', ['email' => $user->email, 'pdfPath' => $pdfPath]);
                    Mail::to($user->email)->send(new \App\Mail\P9Mail($employeePayroll, $pdfPath, $year, $user));
                    $successCount++;
                    Log::info('Email sent successfully', ['email' => $user->email]);
                } catch (\Exception $e) {
                    Log::error('Failed to generate or email P9', ['employee_id' => $employeePayroll->employee_id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'email' => $user->email ?? 'null']);
                    return RequestResponse::badRequest('Failed to generate or email P9.', ['error' => $e->getMessage()]);
                }
            }

            if ($successCount === 0) {
                return RequestResponse::badRequest('No P9 forms were emailed due to invalid data or missing emails.');
            }

            return RequestResponse::ok("Successfully emailed $successCount P9 forms.");
        });
    }


    // public function emailP9(Request $request, $id)
    // {
    //     return $this->handleTransaction(function () use ($id, $request) {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             Log::error('Business not found.', ['slug' => session('active_business_slug')]);
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         try {
    //             $payroll = Payroll::where('business_id', $business->id)
    //                 ->where('id', $id)
    //                 ->with(['employeePayrolls.employee.user'])
    //                 ->firstOrFail();
    //         } catch (\Exception $e) {
    //             Log::error('Failed to load payroll', ['id' => $id, 'business_id' => $business->id, 'error' => $e->getMessage()]);
    //             return RequestResponse::badRequest('Failed to load payroll data.', ['error' => $e->getMessage()]);
    //         }

    //         $year = $payroll->payrun_year;
    //         $month = $payroll->payrun_month;
    //         Log::info('Parsed payrun_month', ['year' => $year, 'month' => $month, 'payroll_id' => $payroll->id]);
    //         Log::info('Payroll record', ['id' => $payroll->id, 'business_id' => $payroll->business_id, 'payrun_month' => $payroll->payrun_month]);
    //         Log::info('EmployeePayrolls raw', ['count' => $payroll->employeePayrolls->count(), 'ids' => $payroll->employeePayrolls->pluck('id')->toArray()]);

    //         $p9Dir = storage_path('app/public/p9/');
    //         if (!File::exists($p9Dir)) {
    //             File::makeDirectory($p9Dir, 0777, true);
    //             Log::info('Created p9 storage directory', ['path' => $p9Dir]);
    //         }

    //         $successCount = 0;
    //         Log::info('Before manual fetch', ['payroll_id' => $payroll->id, 'employeePayrolls_count' => $payroll->employeePayrolls->count()]);
    //         $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)->with(['employee.user'])->get();
    //         Log::info('Fetched EmployeePayrolls', ['count' => $employeePayrolls->count(), 'ids' => $employeePayrolls->pluck('id')->toArray(), 'employee_ids' => $employeePayrolls->pluck('employee_id')->toArray()]);
    //         if ($employeePayrolls->isEmpty()) {
    //             Log::error('Manual fetch returned no records', ['payroll_id' => $payroll->id]);
    //             return RequestResponse::badRequest('No payroll records found for the given ID.');
    //         }

    //         foreach ($employeePayrolls as $employeePayroll) {
    //             Log::info('Processing employeePayroll', ['id' => $employeePayroll->id, 'employee_id' => $employeePayroll->employee_id, 'employee' => $employeePayroll->employee ? 'exists' : 'null']);
    //             $employee = $employeePayroll->employee;
    //             if (!$employee) {
    //                 Log::warning('Skipping employeePayroll with null employee', ['employeePayroll_id' => $employeePayroll->id, 'employee_id' => $employeePayroll->employee_id]);
    //                 continue;
    //             }

    //             $user = $employee->user;
    //             Log::info('Employee user', ['employee_id' => $employeePayroll->employee_id, 'user' => $user ? 'exists' : 'null', 'user_id' => $employee->user_id, 'email' => $user->email ?? 'null']);
    //             if (!$user) {
    //                 Log::warning('No user found for employee, using fallback', ['employee_id' => $employeePayroll->employee_id, 'user_id' => $employee->user_id]);
    //                 $user = new \stdClass();
    //                 $user->email = 'no-email-' . $employeePayroll->employee_id . '@example.com';
    //             }

    //             // Fetch all payroll data for the employee for the year
    //             $allEmployeePayrolls = EmployeePayroll::where('employee_id', $employeePayroll->employee_id)
    //                 ->whereHas('payroll', function ($query) use ($business, $year) {
    //                     $query->where('business_id', $business->id)->where('payrun_year', $year);
    //                 })
    //                 ->with('payroll')
    //                 ->get();

    //             // Initialize 12-month data with defaults
    //             $monthlyData = array_fill(1, 12, [
    //                 'basic_salary' => 0.00, 'benefits_non_cash' => 0.00, 'value_of_quarters' => 0.00,
    //                 'total_gross_pay' => 0.00, 'retirement_e1' => 0.00, 'retirement_e2' => 0.00,
    //                 'retirement_e3' => 0.00, 'housing_levy' => 0.00, 'shif' => 0.00, 'prmf' => 0.00,
    //                 'owner_occupied_interest' => 0.00, 'retirement_contribution' => 0.00,
    //                 'chargeable_pay' => 0.00, 'tax_charged' => 0.00, 'personal_relief' => 0.00,
    //                 'insurance_relief' => 0.00, 'paye' => 0.00, 'total_deductions' => 0.00,
    //             ]);

    //             // Populate monthlyData with actual data from all payrolls
    //             foreach ($allEmployeePayrolls as $payrollEntry) {
    //                 $payrollMonth = $payrollEntry->payroll->payrun_month;
    //                 $deductions = json_decode($payrollEntry->deductions, true) ?: [];
    //                 $basicSalary = (float) ($payrollEntry->basic_salary ?? 0.00);
    //                 $grossPay = (float) ($payrollEntry->gross_pay ?? 0.00);

    //                 // Calculate NSSF (approximate employee portion, e.g., 3,000 KES as per example, adjust if specific data exists)
    //                 $nssfContribution = min(3000.00, $grossPay * 0.06); // Assuming 6% up to a cap, adjust if needed

    //                 // Calculate other pension contribution (from deductions)
    //                 $pensionContribution = (float) ($deductions['pension'] ?? 0.00);

    //                 // retirement_e2: Defined Contribution (Actual) = NSSF + other pension contribution
    //                 $retirementE2 = ($basicSalary > 0 || $grossPay > 0) ? ($nssfContribution + $pensionContribution) : 0.00;

    //                 // Calculate allowable retirement contribution
    //                 $actualRetirement = $retirementE2; // Total actual contribution
    //                 $maxRetirement = min($actualRetirement, $basicSalary * 0.3, 30000.00);
    //                 $retirementContribution = $maxRetirement;

    //                 // SHIF: 2.75% of gross salary
    //                 $shif = $grossPay * 0.0275;

    //                 // Affordable Housing Levy: 1.5% of gross salary
    //                 $housingLevy = $grossPay * 0.015;

    //                 // Post-Retirement Medical Fund (assume from deductions if available, capped at 15,000)
    //                 $postRetirementMedical = min((float) ($deductions['post_retirement_medical'] ?? 0.00), 15000.00);

    //                 // Mortgage Interest (assume from deductions if available, capped at 30,000)
    //                 $mortgageInterest = min((float) ($deductions['mortgage_interest'] ?? 0.00), 30000.00);

    //                 // Insurance Relief (assume from deductions if available, 15% capped at 60,000 annually)
    //                 $insurancePremium = (float) ($deductions['insurance_premium'] ?? 0.00);
    //                 $insuranceRelief = min($insurancePremium * 0.15, 60000.00 / 12); // Monthly cap from annual 60,000

    //                 // Total deductions
    //                 $totalDeductions = $retirementContribution + $housingLevy + $shif + $postRetirementMedical + $mortgageInterest;

    //                 // Taxable income
    //                 $chargeablePay = max(0, $grossPay - $totalDeductions);

    //                 // PAYE calculation (2025 Kenya tax bands, assuming no change unless specified)
    //                 $taxCharged = 0;
    //                 if ($chargeablePay > 0) {
    //                     if ($chargeablePay <= 24000) {
    //                         $taxCharged = $chargeablePay * 0.1;
    //                     } elseif ($chargeablePay <= 32333) {
    //                         $taxCharged = 2400 + ($chargeablePay - 24000) * 0.15;
    //                     } elseif ($chargeablePay <= 500000) {
    //                         $taxCharged = 3724 + ($chargeablePay - 32333) * 0.2;
    //                     } elseif ($chargeablePay <= 800000) {
    //                         $taxCharged = 97224 + ($chargeablePay - 500000) * 0.25;
    //                     } else {
    //                         $taxCharged = 172224 + ($chargeablePay - 800000) * 0.3;
    //                     }
    //                 }

    //                 // Apply personal relief only if data exists
    //                 $personalRelief = ($basicSalary > 0 || $grossPay > 0) ? 2400.00 : 0.00;
    //                 $paye = max(0, $taxCharged - $personalRelief);
    //                 $retirement_e3=($basicSalary > 0 || $grossPay > 0) ? 30000.00 : 0.00;

    //                 $monthlyData[$payrollMonth] = [
    //                     'basic_salary' => $basicSalary,
    //                     'benefits_non_cash' => 0.00,
    //                     'value_of_quarters' => 0.00,
    //                     'total_gross_pay' => $grossPay,
    //                     'retirement_e1' => $basicSalary * 0.3, // For reference
    //                     'retirement_e2' => $retirementE2, // Defined Contribution (Actual)
    //                     'retirement_e3' => $retirement_e3,
    //                     'housing_levy' => $housingLevy,
    //                     'shif' => $shif,
    //                     'prmf' => $postRetirementMedical,
    //                     'owner_occupied_interest' => $mortgageInterest,
    //                     'retirement_contribution' => $retirementContribution,
    //                     'chargeable_pay' => $chargeablePay,
    //                     'tax_charged' => $taxCharged,
    //                     'personal_relief' => $personalRelief,
    //                     'insurance_relief' => $insuranceRelief,
    //                     'paye' => $paye,
    //                     'total_deductions' => $totalDeductions,
    //                 ];
    //             }
    //             Log::info('Populated monthly data', ['employee_id' => $employeePayroll->employee_id, 'monthly_data' => $monthlyData]);

    //             $totals = [
    //                 'basic_salary' => 0, 'benefits_non_cash' => 0, 'value_of_quarters' => 0,
    //                 'total_gross_pay' => 0, 'retirement_e1' => 0, 'retirement_e2' => 0,
    //                 'retirement_e3' => 0, 'housing_levy' => 0, 'shif' => 0, 'prmf' => 0,
    //                 'owner_occupied_interest' => 0, 'retirement_contribution' => 0,
    //                 'chargeable_pay' => 0, 'tax_charged' => 0, 'personal_relief' => 0,
    //                 'insurance_relief' => 0, 'paye' => 0, 'total_deductions' => 0,
    //             ];
    //             foreach ($monthlyData as $monthData) {
    //                 foreach ($totals as $key => $value) {
    //                     $totals[$key] += $monthData[$key] ?? 0;
    //                 }
    //             }

    //             $employeeDetails = [
    //                 'main_name' => $user->name ?? 'N/A',
    //                 'pin' => $employee->tax_no ?? 'N/A',
    //                 'nssf' => $employee->nssf_no ?? 'N/A',
    //                 'shif' => $employee->shif_no ?? 'N/A',
    //                 'company_name' => $business->company_name ?? $business->name,
    //                 'tax_no' => $business->tax_pin_no ?? 'N/A',
    //             ];

    //             $data = [
    //                 [
    //                     'employee_name' => $employeeDetails['company_name'],
    //                     'tax_no' => $employeeDetails['tax_no'],
    //                     'main_name' => $employeeDetails['main_name'],
    //                     'pin' => $employeeDetails['pin'],
    //                     'nssf' => $employeeDetails['nssf'],
    //                     'shif' => $employeeDetails['shif'],
    //                     'monthly_data' => $monthlyData,
    //                     'totals' => $totals,
    //                 ],
    //             ];

    //             Log::info('Generated P9 data', ['data' => $data]);

    //             if (!view()->exists('payroll.reports.p9')) {
    //                 Log::error('View payroll.reports.p9 does not exist');
    //                 return RequestResponse::serverError('P9 view not found.');
    //             }

    //             try {
    //                 $pdf = Pdf::loadView('payroll.reports.p9', array_merge(['business' => $business, 'year' => $year], ['data' => $data]))
    //                     ->setPaper('A4', 'landscape');
    //                 $pdfPath = storage_path('app/public/p9/' . $employeePayroll->id . '.pdf');
    //                 $pdf->save($pdfPath);

    //                 Log::info('Attempting to send email', ['email' => $user->email, 'pdfPath' => $pdfPath]);
    //                 Mail::to($user->email)->send(new \App\Mail\P9Mail($employeePayroll, $pdfPath, $year, $user));
    //                 $successCount++;
    //                 Log::info('Email sent successfully', ['email' => $user->email]);
    //             } catch (\Exception $e) {
    //                 Log::error('Failed to generate or email P9', ['employee_id' => $employeePayroll->employee_id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'email' => $user->email ?? 'null']);
    //                 return RequestResponse::badRequest('Failed to generate or email P9.', ['error' => $e->getMessage()]);
    //             }
    //         }

    //         if ($successCount === 0) {
    //             return RequestResponse::badRequest('No P9 forms were emailed due to invalid data or missing emails.');
    //         }

    //         return RequestResponse::ok("Successfully emailed $successCount P9 forms.");
    //     });
    // }

    // public function emailP9(Request $request, $id)
    // {
    //     return $this->handleTransaction(function () use ($id) {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         $payroll = Payroll::where('business_id', $business->id)
    //             ->where('id', $id)
    //             ->with(['employeePayrolls.employee.user'])
    //             ->firstOrFail();

    //         foreach ($payroll->employeePayrolls as $employeePayroll) {
    //             $employee = $employeePayroll->employee;
    //             $user = $employee->user;

    //             if ($user && $user->email) {
    //                 $pdf = Pdf::loadView('payroll.p9', compact('employeePayroll', 'employee', 'user'));
    //                 $pdfPath = storage_path('app/public/p9/' . $employeePayroll->id . '.pdf');
    //                 $pdf->save($pdfPath);

    //                 Mail::to($user->email)->send(new \App\Mail\P9Mail($employeePayroll, $pdfPath));
    //             }
    //         }

    //         return RequestResponse::ok('P9 forms emailed successfully.');
    //     });
    // }

    public function downloadPayroll(Request $request, $id, $format = 'pdf')
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            Log::error("Business not found for slug: " . ($businessSlug ?? 'Not set'));
            return response()->json(['error' => 'Business not found.'], 400);
        }

        $id = $request->id;
        $format = $request->format;

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $id)
            ->with(['employeePayrolls.employee.user'])
            ->firstOrFail();

        // Determine entity (Business or Location)
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

        // Prepare comprehensive payroll data
        $data = $payroll->employeePayrolls->map(function ($ep) {
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

        // Calculate totals
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
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isCssFloat'           => true,
                        ])
                        ->setPaper('a4', 'landscape');

                    return $pdf->download($fileName);
                } catch (\Exception $e) {
                    Log::error("PDF generation failed for payroll {$id}: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
                    return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
                }

            case 'csv':
                $headers = array_keys($data[0] ?? []);
                $csvData = implode(',', array_map(
                    fn($key) => '"' . ucwords(str_replace('_', ' ', $key))
                        . (in_array($key, ['bank_name', 'account_number', 'employee_name', 'employee_code', 'tax_no'])
                            ? '' : " ({$currency})")
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

                // ── TOTALS ROW (Fix: was missing) ──────────────────────────────────
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
                // ── END TOTALS ROW ─────────────────────────────────────────────────

                return Response::make($csvData, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                ]);

            case 'xlsx':
                try {
                    $totalsRef = $totals; // capture for closure

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

                            // ── TOTALS ROW (Fix: was missing) ───────────────────────
                            $keys = array_keys($this->data[0] ?? []);
                            $totalsRow = array_map(fn($key) => match ($key) {
                                'employee_name'       => 'TOTALS',
                                'employee_code',
                                'tax_no',
                                'bank_name',
                                'account_number'      => '',
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
                            // ── END TOTALS ROW ──────────────────────────────────────

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
                            $lastRow = count($this->data) + 2; // +1 heading +1 totals
                            return [
                                1           => ['font' => ['bold' => true]],           // heading row
                                $lastRow    => [
                                    'font' => ['bold' => true],             // totals row bold
                                    'fill' => [
                                        'fillType' => 'solid',
                                        'color' => ['rgb' => 'E8E8E8']
                                    ]
                                ],
                            ];
                        }
                    }, $fileName);
                } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
                    Log::error("Excel generation failed for payroll {$id}: " . $e->getMessage());
                    return response()->json(['error' => 'Failed to generate Excel file.'], 500);
                }

            default:
                Log::warning("Invalid format requested for payroll {$id}: {$format}");
                return response()->json(['error' => 'Invalid format requested.'], 400);
        }
    }

    public function viewPayslip(Request $request, $employeeId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }
        $id = $request->employee_id;

        $payrollId = $request->query('payroll_id');
        if (!$payrollId) {
            return RequestResponse::badRequest('Payroll ID is required to view the payslip.');
        }

        $employeePayroll = EmployeePayroll::with([
            'employee.user',
            'payroll.business',
            'payroll.location'
        ])
            ->where('employee_id', $id)
            ->where('payroll_id', $payrollId)
            ->firstOrFail();

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

        Log::info('Viewing payslip', [
            'employee_id' => $id,
            'payroll_id' => $payrollId,
            'employee_payroll' => $employeePayroll->toArray()
        ]);

        // Currency Conversion Logic
        $targetCurrency = strtoupper($employeePayroll->employee->user->country ?? 'USD');
        $baseCurrency = $employeePayroll->payroll->currency;
        $exchangeRatesData = $this->getExchangeRates($baseCurrency, $targetCurrency);

        // Extract the rate as a float, with fallback to 1.0
        $exchangeRates = is_array($exchangeRatesData) && isset($exchangeRatesData['rate']) && is_numeric($exchangeRatesData['rate'])
            ? floatval($exchangeRatesData['rate'])
            : 1.0;

        // Log the exchange rate for debugging
        Log::info('Exchange Rate Used', [
            'base_currency' => $baseCurrency,
            'target_currency' => $targetCurrency,
            'exchange_rate' => $exchangeRates
        ]);

        return view('payroll.reports.payslip', compact(
            'employeePayroll',
            'business',
            'entity',
            'entityType',
            'exchangeRates',
            'targetCurrency'
        ));
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

    public function viewPayroll(Request $request, $payroll_id)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            Log::error('viewPayroll: Business not found for slug: ' . (session('active_business_slug') ?? 'Not set'));
            return RequestResponse::badRequest('Business not found.');
        }

        $businessSlug = $request->route('business');
        $payroll_id = $request->id;
        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $payroll_id)
            ->with(['employeePayrolls' => function ($q) use ($request) {
                $q->when($request->filled('location'), function ($q2) use ($request) {
                    $q2->whereHas('employee', fn($emp) => $emp->where('location_id', $request->location));
                });
                $q->when($request->filled('department'), function ($q2) use ($request) {
                    $q2->whereHas('employee.employmentDetails', fn($d) => $d->where('department_id', $request->department));
                });
                $q->when($request->filled('job_category'), function ($q2) use ($request) {
                    $q2->whereHas('employee.employmentDetails', fn($d) => $d->where('job_category_id', $request->job_category));
                });
            }, 'employeePayrolls.employee.user', 'employeePayrolls.employee.paymentDetails', 'employeePayrolls.employee.payrollDetail'])
            ->firstOrFail();


        // $payroll = Payroll::where('business_id', $business->id)
        //     ->where('id', $payroll_id)
        //     ->with([
        //         'employeePayrolls.employee.user',
        //         'employeePayrolls.employee.paymentDetails',
        //         'employeePayrolls.employee.payrollDetail'
        //     ])
        //     ->firstOrFail();

        // Set default currency
        $payroll->currency = $payroll->currency ?? 'KES';

        // Determine the entity (Business or Location)
        $entity = $business;
        $entityType = 'business';
        $page = $entity->company_name . ' Payroll - ' . $payroll->payrun_month . ' - ' . $payroll->payrun_year;
        if ($payroll->location_id) {
            $location = Location::where('id', $payroll->location_id)
                ->where('business_id', $business->id)
                ->first();
            if ($location) {
                $entity = $location;
                $entityType = 'location';
                $page = $entity->name . ' Payroll - ' . $payroll->payrun_month . ' - ' . $payroll->payrun_year;
            } else {
                Log::warning('viewPayroll: Location not found for payroll ID: ' . $payroll_id);
            }
        }

        // Calculate totals for each column
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
            'totalPersonalRelief' => 0.00, // Keep for backward compatibility
            'totalInsuranceRelief' => 0.00, // Keep for backward compatibility
            'totalReliefs' => 0.00, // Total for all reliefs
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
            $reliefs = json_decode($ep->reliefs, true) ?? []; // Read from employee_payrolls.reliefs

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
            $totals['totalPersonalRelief'] += (float) ($ep->personal_relief ?? ($reliefs['personal-relief']['amount'] ?? 0)); // Compatibility
            $totals['totalInsuranceRelief'] += (float) ($ep->insurance_relief ?? ($reliefs['insurance-relief']['amount'] ?? 0)); // Compatibility
            $totals['totalReliefs'] += (float) array_sum(array_map(fn($r) => $r['amount'] ?? 0, $reliefs)); // Sum all reliefs
            $totals['totalPayAfterTax'] += (float) ($ep->pay_after_tax ?? 0);
            $totals['totalDeductionsAfterTax'] += (float) ($ep->deductions_after_tax ?? 0);
            $totals['totalNetPay'] += (float) ($ep->net_pay ?? 0);
            $totals['totalPayeBeforeReliefs'] += (float) ($ep->paye_before_reliefs ?? 0);

            // Custom deductions
            $customDeductions = array_filter($deductions, function ($deduction) {
                if (!is_array($deduction) || !isset($deduction['name'])) {
                    return false;
                }
                $name = strtolower($deduction['name']);
                return !in_array($name, [
                    'shif',
                    'nssf',
                    'paye',
                    'housing levy',
                    'helb',
                    'loan repayment',
                    'advance recovery',
                    'absenteeism charge'
                ]);
            });
            $totals['totalCustomDeductions'] += (float) array_sum(array_map(fn($d) => $d['amount'] ?? 0.0, $customDeductions));

            // Absenteeism Charge
            $absenteeism = array_filter($deductions, fn($d) => is_array($d) && stripos($d['name'] ?? '', 'Absenteeism Charge') !== false);
            $totals['totalAbsenteeismCharge'] += (float) array_sum(array_map(fn($d) => $d['amount'] ?? 0.0, $absenteeism));
        }

        // Calculate total statutory deductions
        $totals['totalStatutoryDeductions'] = $totals['totalShif'] + $totals['totalNssf'] + $totals['totalPaye'] + $totals['totalHousingLevy'] + $totals['totalHelb'];

        return view('payroll.view', compact('business', 'payroll', 'entity', 'entityType', 'page', 'totals'));
    }

    // public function downloadColumn(Request $request, $payroll_id, $column, $format)
    // {
    //     $businessSlug = $request->route('business') ?? session('active_business_slug');
    //     $business = Business::findBySlug($businessSlug);

    //     $payroll_id = $request->id;
    //     $column = $request->column;
    //     $format = $request->format;

    //     if (!$business) {
    //         Log::error("Business not found for slug: " . ($businessSlug ?? 'Not set'));
    //         abort(404, 'Business not found.');
    //     }

    //     $payroll = Payroll::where('business_id', $business->id)
    //         ->where('id', $payroll_id)
    //         ->with(['employeePayrolls.employee.user', 'employeePayrolls.employee'])
    //         ->firstOrFail();

    //     $payrunYear = $payroll->payrun_year;
    //     $payrunMonth = $payroll->payrun_month;

    //     $validColumns = [
    //         'basic_salary',
    //         'gross_pay',
    //         'net_pay',
    //         'meal_allowance',
    //         'tax_no',
    //         'overtime',
    //         'shif',
    //         'nssf',
    //         'paye',
    //         'paye_before_reliefs',
    //         'housing_levy',
    //         'helb',
    //         'taxable_income',
    //         'personal_relief',
    //         'insurance_relief',
    //         'pay_after_tax',
    //         'loan_repayment',
    //         'advance_recovery',
    //         'deductions_after_tax',
    //         'attendance_present',
    //         'attendance_absent',
    //         'days_in_month',
    //         'bank_name',
    //         'account_number'
    //     ];

    //     $column = strtolower(trim($column));
    //     $format = strtolower(trim($format));
    //     if (!in_array($column, $validColumns)) {
    //         Log::warning("Invalid column name requested: {$column}");
    //         abort(400, 'Invalid column name.');
    //     }

    //     $data = $payroll->employeePayrolls->map(function ($ep) use ($column) {
    //         $employee = $ep->employee;
    //         $user = $employee->user;
    //         $deductions = json_decode($ep->deductions, true) ?? [];
    //         $allowances = json_decode($ep->allowances, true) ?? [];
    //         $reliefs = json_decode($ep->reliefs, true) ?? [];
    //         $overtime = floatval(json_decode($ep->overtime, true)['amount'] ?? 0);

    //         $getAllowance = function ($name) use ($allowances) {
    //             foreach ($allowances as $allowance) {
    //                 if (strtolower($allowance['name'] ?? '') === strtolower($name)) return floatval($allowance['amount'] ?? 0);
    //             }
    //             return 0.0;
    //         };

    //         $getRelief = function ($key) use ($reliefs, $ep) {
    //             return isset($reliefs[$key]['amount']) ? floatval($reliefs[$key]['amount']) : floatval($ep->$key ?? 0);
    //         };

    //         // Base row with common fields for all columns
    //         $row = [
    //             'employee_name' => $user->name ?? 'N/A',
    //             'employee_code' => $employee->employee_code ?? 'N/A',
    //             'tax_no' => $employee->tax_no ?? 'N/A',
    //             'basic_salary' => number_format($ep->basic_salary ?? 0, 2),
    //             'gross_pay' => number_format($ep->gross_pay ?? 0, 2),
    //             'net_pay' => number_format($ep->net_pay ?? 0, 2),
    //         ];

    //         if ($column === 'paye') {
    //             // Use direct values from employee_payrolls for PAYE and PAYE before reliefs
    //             $payeValue = floatval($ep->paye ?? 0); // Direct PAYE from table
    //             $payeBeforeReliefs = floatval($ep->paye_before_reliefs ?? 0); // Direct value from table
    //             $personalRelief = $getRelief('personal-relief');
    //             $insuranceRelief = $getRelief('insurance-relief');

    //             // Strict order for PAYE as requested
    //             $payeRow = [
    //                 $employee->tax_no ?? 'N/A', // PIN of Employee
    //                 $user->name ?? 'N/A', // Name of Employee
    //                 $employee->resident_status ?? 'Resident', // Resident Status
    //                 $employee->kra_employee_status ?? 'Primary Employee', // Type of Employee
    //                 number_format($ep->basic_salary ?? 0, 2), // Basic Salary (from table)
    //                 number_format($getAllowance('Housing Allowance'), 2), // Housing Allowance
    //                 number_format($getAllowance('Transport Allowance'), 2), // Transport Allowance
    //                 number_format($getAllowance('Leave Allowance'), 2), // Leave Pay
    //                 number_format($overtime, 2), // Overtime
    //                 number_format(0, 2), // Director's Fee
    //                 number_format(0, 2), // Lump Sum Payment
    //                 number_format(max(0, ($ep->gross_pay ?? 0) - ($ep->basic_salary ?? 0) - $getAllowance('Housing Allowance') - $getAllowance('Transport Allowance') - $overtime), 2), // Other Allowance
    //                 '', // Total Cash Pay (leave blank)
    //                 number_format($getAllowance('Car Allowance'), 2), // Value of Car Benefit
    //                 number_format(0, 2), // Other Non Cash Benefits
    //                 '', // Total Non Cash Benefits
    //                 number_format($getAllowance('Meal Allowance'), 2), // Value of Meals or Meal Allowance
    //                 'Benefit Not Given', // Type of housing
    //                 '', // Rent of House/Market Value
    //                 '', // Computed Rent of House
    //                 '', // Rent Recovered from Employee
    //                 '', // Net Value of Housing
    //                 '', // Total Gross Pay
    //                 number_format($ep->shif ?? 0, 2), // SHIF
    //                 number_format($ep->nssf ?? 0, 2), // Actual Pension Contribution
    //                 number_format(0, 2), // Post Retirement Medical Fund
    //                 number_format($getRelief('mortgage-interest-relief'), 2), // Mortgage Interest
    //                 number_format($ep->housing_levy ?? 0, 2), // Housing Levy
    //                 '', // Amount of Benefit
    //                 '', // Taxable Pay
    //                 '', // Taxable Pay * Slab Rate
    //                 number_format($personalRelief, 2), // Monthly Personal Relief
    //                 number_format($insuranceRelief, 2), // Insurance Relief
    //                 '', // PAYE (computed by kra)
    //                 number_format($payeValue, 2) // Self Assessed PAYE (direct from table)
    //             ];

    //             return $payeRow;
    //         } else {
    //             switch ($column) {
    //                 case 'shif':
    //                     $fullName = $user->name ?? 'N/A';
    //                     $nameParts = explode(' ', $fullName, 2);
    //                     $firstName = $nameParts[0] ?? 'N/A';
    //                     $lastName = $nameParts[1] ?? '';
    //                     $row = [
    //                         $employee->employee_code ?? 'N/A', // PAYROLL NUMBER
    //                         $firstName, // FIRSTNAME
    //                         $lastName, // LASTNAME
    //                         $employee->national_id ?? 'N/A', // ID NO
    //                         $employee->tax_no ?? 'N/A', // KRA PIN
    //                         $employee->nhif_no ?? 'N/A', // SHIF NO
    //                         number_format($ep->shif ?? 0, 2), // CONTRIBUTION AMOUNT
    //                         $user->phone ?? 'N/A', // PHONE
    //                     ];
    //                     break;

    //                 case 'nssf':
    //                     $fullName = $user->name ?? 'N/A';
    //                     $nameParts = explode(' ', $fullName, 2);
    //                     $surname = $nameParts[1] ?? '';
    //                     $otherNames = $nameParts[0] ?? $fullName;
    //                     $row = [
    //                         $employee->employee_code ?? 'N/A', // PAYROLL NUMBER
    //                         $surname, // SURNAME
    //                         $otherNames, // OTHER NAMES
    //                         $employee->national_id ?? 'N/A', // ID NO
    //                         $employee->tax_no ?? 'N/A', // KRA PIN
    //                         $employee->nssf_no ?? 'N/A', // NSSF NO
    //                         number_format($ep->gross_pay ?? 0, 2), // GROSS PAY
    //                         '', // VOLUNTARY
    //                     ];
    //                     break;

    //                 case 'housing_levy':
    //                     $row = [
    //                         $employee->employee_code ?? 'N/A', // EMP NO
    //                         $user->name ?? 'N/A', // FULL NAME
    //                         $employee->tax_no ?? 'N/A', // TAX_NO
    //                         number_format($ep->housing_levy ?? 0, 2), // HOUSE_LEVY AMOUNT
    //                     ];
    //                     break;

    //                 default:
    //                     $value = match ($column) {
    //                         'basic_salary' => $ep->basic_salary ?? 0,
    //                         'gross_pay' => $ep->gross_pay ?? 0,
    //                         'net_pay' => $ep->net_pay ?? 0,
    //                         'tax_no' => $employee->tax_no ?? 'N/A',
    //                         'overtime' => $overtime,
    //                         'helb' => $ep->helb ?? 0,
    //                         'taxable_income' => $ep->taxable_income ?? 0,
    //                         'personal_relief' => $getRelief('personal-relief'),
    //                         'insurance_relief' => $getRelief('insurance-relief'),
    //                         'pay_after_tax' => $ep->pay_after_tax ?? 0,
    //                         'loan_repayment' => $ep->loan_repayment ?? 0,
    //                         'advance_recovery' => $ep->advance_recovery ?? 0,
    //                         'deductions_after_tax' => $ep->deductions_after_tax ?? 0,
    //                         'attendance_present' => $ep->attendance_present ?? 0,
    //                         'attendance_absent' => $ep->attendance_absent ?? 0,
    //                         'days_in_month' => $ep->days_in_month ?? 0,
    //                         'bank_name' => $ep->bank_name ?? 'N/A',
    //                         'account_number' => $employee->account_number ?? 'N/A',
    //                         'paye_before_reliefs' => $ep->paye_before_reliefs ?? 0,
    //                         default => 0,
    //                     };
    //                     $row[$column] = is_numeric($value) ? number_format($value, 2) : $value;
    //                     break;
    //             }
    //             return array_values($row);
    //         }

    //         return $row;
    //     })->toArray();

    //     $monthName = Carbon::createFromFormat('m', $payrunMonth)->format('F');
    //     $fileName = "payroll-{$payrunYear}-{$monthName}-{$column}.{$format}";
    //     $currency = $payroll->currency ?? 'KES';

    //     switch ($format) {
    //         case 'pdf':
    //             try {
    //                 $pdf = Pdf::loadView('payroll.download_column', [
    //                     'business' => $business,
    //                     'payroll' => $payroll,
    //                     'column' => $column,
    //                     'data' => $data,
    //                     'currency' => $currency,
    //                     'headers' => ($column === 'paye') ? [
    //                         'PIN of Employee',
    //                         'Name of Employee',
    //                         'Resident Status',
    //                         'Type of Employee',
    //                         'Basic Salary',
    //                         'Housing Allowance',
    //                         'Transport Allowance',
    //                         'Leave Pay',
    //                         'Over Time Allowance',
    //                         "Director's Fee",
    //                         'Lump Sum Payment',
    //                         'Other Allowance',
    //                         'Total Cash Pay',
    //                         'Value of Car Benefit',
    //                         'Other Non Cash Benefits',
    //                         'Total Non Cash Benefits',
    //                         'Value of Meals or Meal Allowance',
    //                         'Type of housing',
    //                         'Rent of House/Market Value',
    //                         'Computed Rent of House',
    //                         'Rent Recovered from Employee',
    //                         'Net Value of Housing',
    //                         'Total Gross Pay',
    //                         'SHIF',
    //                         'Actual Pension Contribution',
    //                         'Post Retirement Medical Fund',
    //                         'Mortgage Interest',
    //                         'Housing Levy',
    //                         'Amount of Benefit',
    //                         'Taxable Pay',
    //                         'Taxable Pay * Slab Rate',
    //                         'Monthly Personal Relief',
    //                         'Insurance Relief',
    //                         'PAYE',
    //                         'Self Assessed PAYE'
    //                     ] : [],
    //                 ]);
    //                 return $pdf->download($fileName);
    //             } catch (\Exception $e) {
    //                 Log::error("PDF generation failed for payroll {$payroll_id}, column {$column}: " . $e->getMessage());
    //                 abort(500, 'Failed to generate PDF.');
    //             }

    //         case 'csv':
    //             $headers = [];
    //             if ($column === 'paye') {
    //                 $headers = [];
    //             } elseif ($column === 'shif') {
    //                 $headers = [
    //                     'PAYROLL NUMBER',
    //                     'FIRSTNAME',
    //                     'LASTNAME',
    //                     'ID NO',
    //                     'KRA PIN',
    //                     'SHIF NO',
    //                     'CONTRIBUTION AMOUNT',
    //                     'PHONE',
    //                 ];
    //             } elseif ($column === 'nssf') {
    //                 $headers = [
    //                     'PAYROLL NUMBER',
    //                     'SURNAME',
    //                     'OTHER NAMES',
    //                     'ID NO',
    //                     'KRA PIN',
    //                     'NSSF NO',
    //                     'GROSS PAY',
    //                     'VOLUNTARY',
    //                 ];
    //             } elseif ($column === 'housing_levy') {
    //                 $headers = [
    //                     'EMP NO',
    //                     'FULL NAME',
    //                     'TAX_NO',
    //                     'HOUSE_LEVY AMOUNT',
    //                 ];
    //             }

    //             $csvData = '';
    //             if (!empty($headers)) {
    //                 $csvData .= implode(',', array_map(function ($header) {
    //                     return '"' . str_replace('"', '""', $header) . '"';
    //                 }, $headers)) . "\n";
    //             }

    //             foreach ($data as $row) {
    //                 $csvData .= implode(',', array_map(function ($value) {
    //                     return is_numeric($value) ? $value : '"' . str_replace('"', '""', $value) . '"';
    //                 }, $row)) . "\n";
    //             }

    //             return Response::make($csvData, 200, [
    //                 'Content-Type' => 'text/csv',
    //                 'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
    //             ]);

    //         case 'xlsx':
    //             try {
    //                 return Excel::download(new class($data, $column) implements
    //                     \Maatwebsite\Excel\Concerns\FromArray,
    //                     \Maatwebsite\Excel\Concerns\WithHeadings {
    //                     private $data;
    //                     private $column;

    //                     public function __construct(array $data, string $column)
    //                     {
    //                         $this->data = $data;
    //                         $this->column = $column;
    //                     }

    //                     public function array(): array
    //                     {
    //                         return $this->data;
    //                     }

    //                     public function headings(): array
    //                     {
    //                         if ($this->column === 'paye') {
    //                             return [
    //                                 'PIN of Employee',
    //                                 'Name of Employee',
    //                                 'Resident Status',
    //                                 'Type of Employee',
    //                                 'Basic Salary',
    //                                 'Housing Allowance',
    //                                 'Transport Allowance',
    //                                 'Leave Pay',
    //                                 'Over Time Allowance',
    //                                 "Director's Fee",
    //                                 'Lump Sum Payment',
    //                                 'Other Allowance',
    //                                 'Total Cash Pay',
    //                                 'Value of Car Benefit',
    //                                 'Other Non Cash Benefits',
    //                                 'Total Non Cash Benefits',
    //                                 'Value of Meals or Meal Allowance',
    //                                 'Type of housing',
    //                                 'Rent of House/Market Value',
    //                                 'Computed Rent of House',
    //                                 'Rent Recovered from Employee',
    //                                 'Net Value of Housing',
    //                                 'Total Gross Pay',
    //                                 'SHIF',
    //                                 'Actual Pension Contribution',
    //                                 'Post Retirement Medical Fund',
    //                                 'Mortgage Interest',
    //                                 'Housing Levy',
    //                                 'Amount of Benefit',
    //                                 'Taxable Pay',
    //                                 'Taxable Pay * Slab Rate',
    //                                 'Monthly Personal Relief',
    //                                 'Insurance Relief',
    //                                 'PAYE',
    //                                 'Self Assessed PAYE'
    //                             ];
    //                         } elseif ($this->column === 'shif') {
    //                             return [
    //                                 'PAYROLL NUMBER',
    //                                 'FIRSTNAME',
    //                                 'LASTNAME',
    //                                 'ID NO',
    //                                 'KRA PIN',
    //                                 'SHIF NO',
    //                                 'CONTRIBUTION AMOUNT',
    //                                 'PHONE',
    //                             ];
    //                         } elseif ($this->column === 'nssf') {
    //                             return [
    //                                 'PAYROLL NUMBER',
    //                                 'SURNAME',
    //                                 'OTHER NAMES',
    //                                 'ID NO',
    //                                 'KRA PIN',
    //                                 'NSSF NO',
    //                                 'GROSS PAY',
    //                                 'VOLUNTARY',
    //                             ];
    //                         } elseif ($this->column === 'housing_levy') {
    //                             return [
    //                                 'EMP NO',
    //                                 'FULL NAME',
    //                                 'TAX_NO',
    //                                 'HOUSE_LEVY AMOUNT',
    //                             ];
    //                         }
    //                         return array_map('ucwords', array_keys($this->data[0] ?? []));
    //                     }
    //                 }, $fileName);
    //             } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
    //                 Log::error("Excel generation failed for payroll {$payroll_id}, column {$column}: " . $e->getMessage());
    //                 abort(500, 'Failed to generate Excel file.');
    //             }

    //         default:
    //             Log::warning("Invalid format requested: {$format}");
    //             abort(400, 'Invalid format.');
    //     }
    // }

    //modified to latest paye
    public function downloadColumn(Request $request, $payroll_id, $column, $format)
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);

        $payroll_id = $request->id;
        $column = $request->column;
        $format = $request->format;

        if (!$business) {
            Log::error("Business not found for slug: " . ($businessSlug ?? 'Not set'));
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
            Log::warning("Invalid column name requested: {$column}");
            abort(400, 'Invalid column name.');
        }

        $data = $payroll->employeePayrolls->map(function ($ep) use ($column) {
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

                $payeRow = [
                    $employee->tax_no ?? '', // PIN of Employee (A)
                    $user->name ?? '', // Name of Employee (B)
                    in_array($employee->resident_status, ['Resident', 'Non-Resident']) ? $employee->resident_status : 'Resident', // Resident Status (C)
                    $employee->kra_employee_status ?? 'Primary Employee', // Type of Employee (D)
                    $employee->disability_status ?? 'No', // Payee With Disability PWD (E)
                    '', // Exemption Certificate Number (F)
                    floatval($ep->gross_pay ?? 0), // Total Cash Pay (A) (G)
                    floatval($getAllowance('Car Allowance')), // Value of Car Benefit (B) (H)
                    floatval($getAllowance('Meal Allowance')), // Value of Meals (C) (I)
                    floatval(0), // Other Non Cash Benefits (D) (J)
                    'Benefit Not Given', // Type of Housing (K)
                    '', // Housing Benefit (F) (L)
                    floatval(max(0, ($ep->gross_pay ?? 0) - ($ep->basic_salary ?? 0) - $getAllowance('Housing Allowance') - $getAllowance('Transport Allowance') - $overtime)), // Other Benefits (G) (M)
                    '', // Total Gross Pay (Ksh) (N)
                    floatval($ep->shif ?? 0), // Social Health Contribution (J) (O)
                    floatval($ep->nssf ?? 0), // NSSF Contributions (K) (P)
                    floatval($ep->other_pension_contribution ?? 0), // Other Pension Contribution (K) (Q)
                    floatval(0), // Post Retirement Medical Fund/SHIP (L) (R)
                    floatval($getRelief('mortgage-interest-relief')), // Mortgage Interest (M) (S)
                    floatval($ep->housing_levy ?? 0), // Affordable Housing Levy (N) (T)
                    '', // Taxable Pay (Ksh) (U)
                    $personalRelief, // Monthly Personal Relief (P) (V)
                    $insuranceRelief, // Amount of Insurance Relief (W)
                    '', // PAYE Tax (Ksh) (X)
                    $payeValue // Self Assessed PAYE Tax (Ksh) (Y)
                ];

                return $payeRow;
            } else {
                // Other column logic
                $row = ['employee_name' => $user->name ?? 'N/A', 'employee_code' => $employee->employee_code ?? 'N/A'];
                switch ($column) {
                    case 'shif':
                        $fullName = $user->name ?? 'N/A';
                        $nameParts = explode(' ', $fullName, 2);
                        $firstName = $nameParts[0] ?? 'N/A';
                        $lastName = $nameParts[1] ?? '';
                        $row = [
                            $employee->employee_code ?? 'N/A',
                            $firstName,
                            $lastName,
                            $employee->national_id ?? 'N/A',
                            $employee->tax_no ?? 'N/A',
                            $employee->nhif_no ?? 'N/A',
                            floatval($ep->shif ?? 0),
                            $user->phone ?? 'N/A',
                        ];
                        break;
                    case 'nssf':
                        $fullName = $user->name ?? 'N/A';
                        $nameParts = explode(' ', $fullName, 2);
                        $surname = $nameParts[1] ?? '';
                        $otherNames = $nameParts[0] ?? $fullName;
                        $row = [
                            $employee->employee_code ?? 'N/A',
                            $surname,
                            $otherNames,
                            $employee->national_id ?? 'N/A',
                            $employee->tax_no ?? 'N/A',
                            $employee->nssf_no ?? 'N/A',
                            floatval($ep->gross_pay ?? 0),
                            '',
                        ];
                        break;
                    case 'housing_levy':
                        $row = [
                            $employee->employee_code ?? 'N/A',
                            $user->name ?? 'N/A',
                            $employee->tax_no ?? 'N/A',
                            floatval($ep->housing_levy ?? 0),
                        ];
                        break;
                    default:
                        // For all other columns, create consistent structure
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

                        // Return consistent array structure: [name, code, tax_no, basic, gross, net, column_value]
                        $row = [
                            $user->name ?? 'N/A',                    // 0: employee_name
                            $employee->employee_code ?? 'N/A',       // 1: employee_code
                            $employee->tax_no ?? 'N/A',              // 2: tax_no
                            floatval($ep->basic_salary ?? 0),        // 3: basic_salary
                            floatval($ep->gross_pay ?? 0),           // 4: gross_pay
                            floatval($ep->net_pay ?? 0),             // 5: net_pay
                            is_numeric($value) ? floatval($value) : $value  // 6: column_value
                        ];
                        break;
                }
                return array_values($row);
            }

            return $row;
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
                        'headers' => ($column === 'paye') ? [
                            'PIN of Employee',
                            'Name of Employee',
                            'Resident Status',
                            'Type of Employee',
                            'Payee With Disability PWD',
                            'Exemption Certificate Number',
                            'Total Cash Pay (A)',
                            'Value of Car Benefit (B)',
                            'Value of Meals (C)',
                            'Other Non Cash Benefits (D)',
                            'Type of Housing',
                            'Housing Benefit (F)',
                            'Other Benefits (G)',
                            'Total Gross Pay (Ksh)',
                            'Social Health Contribution (J)',
                            'NSSF Contributions (K)',
                            'Other Pension Contribution (K)',
                            'Post Retirement Medical Fund/SHIP (L)',
                            'Mortgage Interest (M)',
                            'Affordable Housing Levy (N)',
                            'Taxable Pay (Ksh)',
                            'Monthly Personal Relief (P)',
                            'Amount of Insurance Relief',
                            'PAYE Tax (Ksh)',
                            'Self Assessed PAYE Tax (Ksh)'
                        ] : [],
                    ]);
                    return $pdf->download($fileName);
                } catch (\Exception $e) {
                    Log::error("PDF generation failed for payroll {$payroll_id}, column {$column}: " . $e->getMessage());
                    abort(500, 'Failed to generate PDF.');
                }

            case 'csv':
                $headers = [];
                if ($column === 'paye') {
                    $headers = [
                        // 'PIN of Employee',
                        // 'Name of Employee',
                        // 'Resident Status',
                        // 'Type of Employee',
                        // 'Payee With Disability PWD',
                        // 'Exemption Certificate Number',
                        // 'Total Cash Pay (A)',
                        // 'Value of Car Benefit (B)',
                        // 'Value of Meals (C)',
                        // 'Other Non Cash Benefits (D)',
                        // 'Type of Housing',
                        // 'Housing Benefit (F)',
                        // 'Other Benefits (G)',
                        // 'Total Gross Pay (Ksh)',
                        // 'Social Health Contribution (J)',
                        // 'NSSF Contributions (K)',
                        // 'Other Pension Contribution (K)',
                        // 'Post Retirement Medical Fund/SHIP (L)',
                        // 'Mortgage Interest (M)',
                        // 'Affordable Housing Levy (N)',
                        // 'Taxable Pay (Ksh)',
                        // 'Monthly Personal Relief (P)',
                        // 'Amount of Insurance Relief',
                        // 'PAYE Tax (Ksh)',
                        // 'Self Assessed PAYE Tax (Ksh)'
                    ];
                } elseif ($column === 'shif') {
                    $headers = ['PAYROLL NUMBER', 'FIRSTNAME', 'LASTNAME', 'ID NO', 'KRA PIN', 'SHIF NO', 'CONTRIBUTION AMOUNT', 'PHONE'];
                } elseif ($column === 'nssf') {
                    $headers = ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'];
                } elseif ($column === 'housing_levy') {
                    $headers = ['EMP NO', 'FULL NAME', 'TAX_NO', 'HOUSE_LEVY AMOUNT'];
                }

                $csvData = '';
                if (!empty($headers)) {
                    $csvData .= implode(',', array_map(function ($header) {
                        return '"' . str_replace('"', '""', $header) . '"';
                    }, $headers)) . "\n";
                }

                foreach ($data as $row) {
                    $csvData .= implode(',', array_map(function ($value) {
                        return is_numeric($value) ? strval($value) : '"' . str_replace('"', '""', $value) . '"';
                    }, $row)) . "\n";
                }

                return Response::make($csvData, 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                ]);

            case 'xlsx':
                try {
                    // FIX: Store column in a separate variable to avoid shadowing issues
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
                            // FIX: Use $this->columnType instead of local $column variable
                            if ($this->columnType === 'paye') {
                                return [
                                    'PIN of Employee',
                                    'Name of Employee',
                                    'Resident Status',
                                    'Type of Employee',
                                    'Payee With Disability PWD',
                                    'Exemption Certificate Number',
                                    'Total Cash Pay (A)',
                                    'Value of Car Benefit (B)',
                                    'Value of Meals (C)',
                                    'Other Non Cash Benefits (D)',
                                    'Type of Housing',
                                    'Housing Benefit (F)',
                                    'Other Benefits (G)',
                                    'Total Gross Pay (Ksh)',
                                    'Social Health Contribution (J)',
                                    'NSSF Contributions (K)',
                                    'Other Pension Contribution (K)',
                                    'Post Retirement Medical Fund/SHIP (L)',
                                    'Mortgage Interest (M)',
                                    'Affordable Housing Levy (N)',
                                    'Taxable Pay (Ksh)',
                                    'Monthly Personal Relief (P)',
                                    'Amount of Insurance Relief',
                                    'PAYE Tax (Ksh)',
                                    'Self Assessed PAYE Tax (Ksh)'
                                ];
                            } elseif ($this->columnType === 'shif') {
                                return ['PAYROLL NUMBER', 'FIRSTNAME', 'LASTNAME', 'ID NO', 'KRA PIN', 'SHIF NO', 'CONTRIBUTION AMOUNT', 'PHONE'];
                            } elseif ($this->columnType === 'nssf') {
                                return ['PAYROLL NUMBER', 'SURNAME', 'OTHER NAMES', 'ID NO', 'KRA PIN', 'NSSF NO', 'GROSS PAY', 'VOLUNTARY'];
                            } elseif ($this->columnType === 'housing_levy') {
                                return ['EMP NO', 'FULL NAME', 'TAX_NO', 'HOUSE_LEVY AMOUNT'];
                            }
                            return array_map('ucwords', array_keys($this->data[0] ?? []));
                        }
                    }, $fileName);
                } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
                    Log::error("Excel generation failed for payroll {$payroll_id}, column {$column}: " . $e->getMessage());
                    abort(500, 'Failed to generate Excel file.');
                }

            default:
                Log::warning("Invalid format requested: {$format}");
                abort(400, 'Invalid format.');
        }
    }

    public function downloadReport(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            abort(404, 'Business not found.');
        }

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $request->payroll_id)
            ->with(['employeePayrolls.employee.user'])
            ->firstOrFail();

        $type = strtolower($request->type);
        $validTypes = ['shif', 'nssf', 'paye', 'nhdf', 'tax_filing', 'bank_advice', 'company_payslip'];

        if (!in_array($type, $validTypes)) {
            abort(404, "Invalid report type: {$type}");
        }

        // Prepare comprehensive payroll data
        $data = $payroll->employeePayrolls->map(function ($ep) {
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

        if (!view()->exists("payroll.reports.{$type}")) {
            abort(404, "Report view for {$type} not found");
        }

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
        \Log::info("downloadBankAdvice called: business_slug=" . session('active_business_slug') . ", year=$request->year, month=$request->month, format=" . $request->format);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            \Log::error("Business not found for slug: " . session('active_business_slug'));
            abort(404, 'Business not found.');
        }

        $month = str_pad((int)$month, 2, '0', STR_PAD_LEFT);

        $format = $request->format;
        $payroll = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->where('payrun_month', $month)
            ->with(['employeePayrolls.employee.paymentDetails', 'business'])
            ->firstOrFail();

        switch (strtolower($format)) {
            case 'pdf':
                if (!view()->exists("payroll.reports.bank_advice")) {
                    \Log::error("Bank advice view not found: payroll.reports.bank_advice");
                    abort(404, 'Bank advice view not found.');
                }

                try {
                    $pdf = Pdf::loadView('payroll.reports.bank_advice', [
                        'payroll' => $payroll,
                    ])->setPaper('a4', 'landscape');

                    return $pdf->download("bank_advice_{$payroll->payrun_year}_{$payroll->payrun_month}.pdf");
                } catch (\Exception $e) {
                    \Log::error("PDF generation failed: " . $e->getMessage());
                    abort(500, 'Failed to generate PDF.');
                }

            case 'csv':
            case 'xlsx':
                try {
                    return Excel::download(new BankAdviceExport($payroll), "bank_advice_{$payroll->payrun_year}_{$payroll->payrun_month}.{$format}");
                } catch (\Exception $e) {
                    \Log::error("Excel/CSV export failed: " . $e->getMessage());
                    abort(500, 'Failed to export data.');
                }

            default:
                \Log::error("Unsupported format: $format");
                abort(400, 'Unsupported format.');
        }
    }

    /**
     * Download NSSF Monthly Summary for a year
     */
    public function downloadNssfMonthlySummary(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $year = $request->year ?? date('Y');

        try {
            $fileName = "NSSF_Monthly_Summary_{$year}.xlsx";
            return Excel::download(
                new NssfMonthlySummaryExport($business->id, $year),
                $fileName
            );
        } catch (\Exception $e) {
            \Log::error("NSSF Monthly Summary export failed: " . $e->getMessage());
            return RequestResponse::badRequest('Failed to generate NSSF monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Download SHIF Monthly Summary for a year
     */
    public function downloadShifMonthlySummary(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $year = $request->year ?? date('Y');

        try {
            $fileName = "SHIF_Monthly_Summary_{$year}.xlsx";
            return Excel::download(
                new ShifMonthlySummaryExport($business->id, $year),
                $fileName
            );
        } catch (\Exception $e) {
            \Log::error("SHIF Monthly Summary export failed: " . $e->getMessage());
            return RequestResponse::badRequest('Failed to generate SHIF monthly summary: ' . $e->getMessage());
        }
    }

    /**
     * Download NHIF Monthly Summary (alias for SHIF for backward compatibility)
     */
    public function downloadNhifMonthlySummary(Request $request)
    {
        return $this->downloadShifMonthlySummary($request);
    }

    public function downloadP9(Request $request, $businessSlug, $year, $format)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business) {
            abort(404, 'Business not found.');
        }

        $payrolls = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->with(['employeePayrolls.employee.user'])
            ->get();

        if ($payrolls->isEmpty()) {
            abort(404, 'No payroll data found for the year ' . $year);
        }

        $employees = $payrolls->flatMap->employeePayrolls->pluck('employee')->unique('id');

        $data = $employees->map(function ($employee) use ($payrolls, $year) {
            $monthlyData = array_fill(1, 12, [
                'basic_salary' => 0,              // A
                'benefits_non_cash' => 0,         // B
                'value_of_quarters' => 0,         // C
                'total_gross_pay' => 0,           // D
                'retirement_e1' => 0,             // E1 (30% of A)
                'retirement_e2' => 0,             // E2 (Actual)
                'retirement_e3' => 30000,         // E3 (Fixed, KRA limit)
                'owner_occupied_interest' => 0,   // F
                'retirement_contribution' => 0,   // G (Lowest of E + F)
                'chargeable_pay' => 0,            // H
                'tax_charged' => 0,               // J
                'personal_relief' => 2400,        // K (KRA standard, monthly max)
                'insurance_relief' => 0,          // K
                'paye' => 0,                      // J-K
            ]);

            foreach ($payrolls as $payroll) {
                if (!$employee || !isset($employee->id)) {
                    continue; // Skip if $employee is null or has no id
                }
                $ep = $payroll->employeePayrolls->where('employee_id', $employee->id)->first();
                if ($ep) {
                    $month = (int) date('n', strtotime($payroll->payrun_month)); // 1-12
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
                        'retirement_contribution' => min($retirementE1, $retirementE2, 20000), // Lowest of E1, E2, E3
                        'chargeable_pay' => $taxableIncome,
                        'tax_charged' => $paye + $personalRelief + $insuranceRelief, // Reverse calculate J
                        'personal_relief' => $personalRelief,
                        'insurance_relief' => $insuranceRelief,
                        'paye' => $paye,
                    ];
                }
            }

            $totals = [
                'basic_salary' => array_sum(array_column($monthlyData, 'basic_salary')),
                'benefits_non_cash' => array_sum(array_column($monthlyData, 'benefits_non_cash')),
                'value_of_quarters' => array_sum(array_column($monthlyData, 'value_of_quarters')),
                'total_gross_pay' => array_sum(array_column($monthlyData, 'total_gross_pay')),
                'retirement_e1' => array_sum(array_column($monthlyData, 'retirement_e1')),
                'retirement_e2' => array_sum(array_column($monthlyData, 'retirement_e2')),
                'retirement_e3' => array_sum(array_column($monthlyData, 'retirement_e3')),
                'owner_occupied_interest' => array_sum(array_column($monthlyData, 'owner_occupied_interest')),
                'retirement_contribution' => array_sum(array_column($monthlyData, 'retirement_contribution')),
                'chargeable_pay' => array_sum(array_column($monthlyData, 'chargeable_pay')),
                'tax_charged' => array_sum(array_column($monthlyData, 'tax_charged')),
                'personal_relief' => array_sum(array_column($monthlyData, 'personal_relief')),
                'insurance_relief' => array_sum(array_column($monthlyData, 'insurance_relief')),
                'paye' => array_sum(array_column($monthlyData, 'paye')),
            ];

            return [
                'employee_name' => $employee->user->name ?? 'N/A',
                'tax_no' => $employee->tax_no ?? 'N/A',
                'monthly_data' => $monthlyData,
                'totals' => $totals,
            ];
        })->toArray();

        $format = strtolower($format);
        $filename = "P9_{$year}";

        switch ($format) {
            case 'pdf':
                $pdf = Pdf::loadView('payroll.reports.p9', [
                    'business' => $business,
                    'year' => $year,
                    'data' => $data,
                ])->setPaper('a4', 'landscape'); // Changed to landscape
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
        if (!$business) {
            abort(404, 'Business not found.');
        }

        $payrolls = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->with(['employeePayrolls.employee.user'])
            ->get();

        if ($payrolls->isEmpty()) {
            abort(404, 'No payroll data found for the year ' . $year);
        }

        // Filter for the specific employee
        $employeePayrolls = $payrolls->flatMap->employeePayrolls->where('employee_id', $employeeId);
        if ($employeePayrolls->isEmpty()) {
            abort(404, 'No payroll data found for employee ID ' . $employeeId . ' in year ' . $year);
        }

        $employee = $employeePayrolls->first()->employee;

        // Calculate data for the single employee
        $monthlyData = array_fill(1, 12, [
            'basic_salary' => 0,              // A
            'benefits_non_cash' => 0,         // B
            'value_of_quarters' => 0,         // C
            'total_gross_pay' => 0,           // D
            'retirement_e1' => 0,             // E1 (30% of A)
            'retirement_e2' => 0,             // E2 (Actual)
            'retirement_e3' => 30000,         // E3 (Fixed, KRA limit)
            'owner_occupied_interest' => 0,   // F
            'retirement_contribution' => 0,   // G (Lowest of E + F)
            'chargeable_pay' => 0,            // H
            'tax_charged' => 0,               // J
            'personal_relief' => 2400,        // K (KRA standard, monthly max)
            'insurance_relief' => 0,          // K
            'paye' => 0,                      // J-K
        ]);

        foreach ($payrolls as $payroll) {
            $ep = $payroll->employeePayrolls->where('employee_id', $employee->id)->first();
            if ($ep) {
                $month = (int) date('n', strtotime($payroll->payrun_month)); // 1-12
                $deductions = json_decode($ep->deductions, true) ?? [];
                $reliefs = json_decode($ep->reliefs, true) ?? []; // Use reliefs JSON
                $basicSalary = (float) ($ep->basic_salary ?? 0);
                $grossPay = (float) ($ep->gross_pay ?? 0);
                $taxableIncome = (float) ($ep->taxable_income ?? 0);
                $paye = (float) ($ep->paye ?? ($deductions['paye'] ?? 0));
                $personalRelief = (float) ($reliefs['personal-relief']['amount'] ?? ($ep->personal_relief ?? 2400)); // Default KRA relief
                $insuranceRelief = (float) ($reliefs['insurance-relief']['amount'] ?? ($ep->insurance_relief ?? 0));
                $retirementE1 = $basicSalary * 0.3; // 30% of basic salary
                $retirementE2 = (float) ($deductions['retirement_contribution'] ?? 0); // Actual contribution

                $monthlyData[$month] = [
                    'basic_salary' => $basicSalary,
                    'benefits_non_cash' => 0, // Adjust if your data includes this
                    'value_of_quarters' => 0, // Adjust if applicable
                    'total_gross_pay' => $grossPay,
                    'retirement_e1' => $retirementE1,
                    'retirement_e2' => $retirementE2,
                    'retirement_e3' => 30000, // KRA fixed limit
                    'owner_occupied_interest' => 0, // Add logic if available
                    'retirement_contribution' => min($retirementE1, $retirementE2, 20000), // Lowest of E1, E2, E3
                    'chargeable_pay' => $taxableIncome,
                    'tax_charged' => $paye + $personalRelief + $insuranceRelief, // Reverse calculate J
                    'personal_relief' => $personalRelief,
                    'insurance_relief' => $insuranceRelief,
                    'paye' => $paye,
                ];
            }
        }

        $totals = [
            'basic_salary' => array_sum(array_column($monthlyData, 'basic_salary')),
            'benefits_non_cash' => array_sum(array_column($monthlyData, 'benefits_non_cash')),
            'value_of_quarters' => array_sum(array_column($monthlyData, 'value_of_quarters')),
            'total_gross_pay' => array_sum(array_column($monthlyData, 'total_gross_pay')),
            'retirement_e1' => array_sum(array_column($monthlyData, 'retirement_e1')),
            'retirement_e2' => array_sum(array_column($monthlyData, 'retirement_e2')),
            'retirement_e3' => array_sum(array_column($monthlyData, 'retirement_e3')),
            'owner_occupied_interest' => array_sum(array_column($monthlyData, 'owner_occupied_interest')),
            'retirement_contribution' => array_sum(array_column($monthlyData, 'retirement_contribution')),
            'chargeable_pay' => array_sum(array_column($monthlyData, 'chargeable_pay')),
            'tax_charged' => array_sum(array_column($monthlyData, 'tax_charged')),
            'personal_relief' => array_sum(array_column($monthlyData, 'personal_relief')),
            'insurance_relief' => array_sum(array_column($monthlyData, 'insurance_relief')),
            'paye' => array_sum(array_column($monthlyData, 'paye')),
        ];

        // Single employee data, no array wrapping needed here
        $data = [
            'employee_name' => $employee->user->name ?? 'N/A',
            'tax_no' => $employee->tax_no ?? 'N/A',
            'monthly_data' => $monthlyData,
            'totals' => $totals,
        ];

        $format = strtolower($format);
        $filename = "P9_{$employee->user->name}_{$year}"; // Personalized filename

        switch ($format) {
            case 'pdf':
                $pdf = Pdf::loadView('payroll.reports.p9', [
                    'business' => $business,
                    'year' => $year,
                    'data' => [$data],
                ])->setPaper('a4', 'landscape');
                return $pdf->download("{$filename}.pdf");

            case 'csv':
                return Excel::download(new P9Export([$data]), "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV);

            case 'xlsx':
                return Excel::download(new P9Export([$data]), "{$filename}.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            default:
                abort(400, "Unsupported format: {$format}");
        }
    }

    // public function sendPayslips(Request $request)
    // {
    //     $payrollId = $request->input('payroll_id');
    //     $employeePayrollId = $request->input('employee_payroll_id');

    //     if (!$payrollId && !$employeePayrollId) {
    //         return RequestResponse::badRequest('Either payroll_id or employee_payroll_id is required.');
    //     }

    //     return $this->handleTransaction(function () use ($request, $payrollId, $employeePayrollId) {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         if ($employeePayrollId) {
    //             $employeePayroll = EmployeePayroll::with(['employee.user', 'payroll.business', 'payroll.location'])
    //                 ->where('id', $employeePayrollId)
    //                 ->whereHas('payroll', fn($q) => $q->where('business_id', $business->id))
    //                 ->firstOrFail();

    //             $employeePayrolls = collect([$employeePayroll]);
    //             $payroll = $employeePayroll->payroll;
    //         } else {
    //             $payroll = Payroll::where('business_id', $business->id)
    //                 ->where('id', $payrollId)
    //                 ->with(['employeePayrolls.employee.user'])
    //                 ->firstOrFail();

    //             $employeePayrolls = $payroll->employeePayrolls;
    //         }

    //         $sentCount = 0;
    //         foreach ($employeePayrolls as $employeePayroll) {
    //             $user = $employeePayroll->employee->user;
    //             if (!$user || !$user->email) {
    //                 Log::warning("No email found for employee ID: {$employeePayroll->employee_id}, skipping payslip.");
    //                 continue;
    //             }

    //             $entity = $business;
    //             $entityType = 'business';
    //             if ($employeePayroll->payroll->location_id) {
    //                 $location = Location::where('id', $employeePayroll->payroll->location_id)
    //                     ->where('business_id', $business->id)
    //                     ->first();
    //                 if ($location) {
    //                     $entity = $location;
    //                     $entityType = 'location';
    //                 }
    //             }

    //             $pdf = Pdf::loadView('payroll.reports.payslip', compact('employeePayroll', 'business', 'entity', 'entityType'));
    //             $fileName = 'payslip_' . $employeePayroll->id . '_' . time() . '.pdf';
    //             $filePath = storage_path('app/public/payslips/' . $fileName);

    //             if (!file_exists(storage_path('app/public/payslips'))) {
    //                 mkdir(storage_path('app/public/payslips'), 0755, true);
    //             }
    //             $pdf->save($filePath);

    //             Mail::to($user->email)->send(new PayslipMail($employeePayroll, $filePath, $user->name));
    //             $sentCount++;
    //         }

    //         if ($payrollId && !$employeePayrollId) {
    //             $payroll->update(['emailed' => true]);
    //         }

    //         $message = $employeePayrollId
    //             ? 'Payslip queued for sending.'
    //             : "Payslips queued for sending ($sentCount sent).";
    //         return RequestResponse::ok($message, ['sent_count' => $sentCount]);
    //     }, function ($e) {
    //         return RequestResponse::badRequest('Failed to send payslips: ' . $e->getMessage());
    //     });
    // }



    public function sendPayslips(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'payroll_id' => 'required_without:employee_payroll_id|exists:payrolls,id',
                'employee_payroll_id' => 'required_without:payroll_id|exists:employee_payrolls,id',
            ]);

            $payrollId = $request->input('payroll_id');
            $employeePayrollId = $request->input('employee_payroll_id');
            $business = Business::findBySlug(session('active_business_slug'));

            if (!$business) {
                return response()->json(['error' => 'Business not found.'], 400);
            }

            if ($employeePayrollId) {
                $employeePayroll = EmployeePayroll::with(['employee.user', 'payroll.business', 'payroll.location'])
                    ->where('id', $employeePayrollId)
                    ->whereHas('payroll', fn($q) => $q->where('business_id', $business->id))
                    ->first();

                if (!$employeePayroll) {
                    return response()->json(['error' => 'Employee payroll not found.'], 404);
                }

                $employeePayrolls = collect([$employeePayroll]);
                $payroll = $employeePayroll->payroll;
            } else {
                $payroll = Payroll::where('business_id', $business->id)
                    ->where('id', $payrollId)
                    ->with(['employeePayrolls.employee.user'])
                    ->first();

                if (!$payroll) {
                    return response()->json(['error' => 'Payroll not found.'], 404);
                }

                $employeePayrolls = $payroll->employeePayrolls;
            }

            $sentCount = 0;
            foreach ($employeePayrolls as $employeePayroll) {
                $user = $employeePayroll->employee->user;
                if (!$user || !$user->email) {
                    Log::warning("No email found for employee ID: {$employeePayroll->employee_id}, skipping payslip.");
                    continue;
                }

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

                // Generate PDF
                try {
                    $pdf = Pdf::loadView('payroll.reports.payslip', compact('employeePayroll', 'business', 'entity', 'entityType'));
                    $fileName = 'payslip_' . $employeePayroll->id . '_' . time() . '.pdf';
                    $filePath = storage_path('app/public/payslips/' . $fileName);

                    if (!file_exists(storage_path('app/public/payslips'))) {
                        mkdir(storage_path('app/public/payslips'), 0755, true);
                    }
                    $pdf->save($filePath);
                } catch (\Exception $e) {
                    Log::error("Failed to generate/save PDF for employee payroll ID {$employeePayroll->id}: {$e->getMessage()}");
                    continue; // Skip this payslip but continue with others
                }

                // Send email
                try {
                    Mail::to($user->email)->send(new PayslipMail($employeePayroll, $filePath, $user->name));
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send email for employee ID {$employeePayroll->employee_id}: {$e->getMessage()}");
                    continue; // Skip this email but continue with others
                }
            }

            if ($payrollId && !$employeePayrollId && $sentCount > 0) {
                $payroll->update(['emailed' => true]);
            }

            $message = $employeePayrollId
                ? 'Payslip queued for sending.'
                : "Payslips queued for sending ($sentCount sent).";
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['sent_count' => $sentCount]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Unexpected error in sendPayslips: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Server error occurred.'], 500);
        }
    }


    public function close(Request $request)
    {
        $payroll = Payroll::findOrFail($request->payroll_id);
        $payroll->update(['status' => 'closed']);
        if ($request->carry_forward) {
            $this->carryForward($payroll);
        }
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
                    Advance::where('employee_id', $ep->employee_id)
                        ->where('amount', '>', 0)
                        ->first()
                        ->update(['amount' => max(0, $remaining - $ep->advance_recovery)]);
                }
            }
        }
    }

    public function filter(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        // Proper defaults
        $year = $request->filled('year') ? (int) $request->year : now()->year;
        $month = $request->filled('month') ? (int) $request->month : null;


        $query = Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->when($month, fn($q) => $q->where('payrun_month', $month))
            ->withCount(['employeePayrolls as no_of_payslips'])
            ->with(['employeePayrolls' => function ($q) use ($request) {
                $q->select('id', 'payroll_id', 'employee_id', 'net_pay')
                    ->when($request->filled('location'), fn($emp) => $emp->whereHas('employee', fn($e) => $e->where('location_id', $request->location)))
                    ->when($request->filled('department'), fn($emp) => $emp->whereHas('employee.employmentDetails', fn($d) => $d->where('department_id', $request->department)))
                    ->when($request->filled('job_category'), fn($emp) => $emp->whereHas('employee.employmentDetails', fn($d) => $d->where('job_category_id', $request->job_category)));
            }])
            ->latest('updated_at');

        // Parent payroll filters
        if ($request->filled('location')) {
            $query->whereHas('employeePayrolls.employee', fn($q) => $q->where('location_id', $request->location));
        }
        if ($request->filled('department')) {
            $query->whereHas('employeePayrolls.employee.employmentDetails', fn($q) => $q->where('department_id', $request->department));
        }
        if ($request->filled('job_category')) {
            $query->whereHas('employeePayrolls.employee.employmentDetails', fn($q) => $q->where('job_category_id', $request->job_category));
        }
        if ($request->filled('employee')) {
            $query->whereHas('employeePayrolls.employee', fn($q) => $q->where('id', $request->employee));
        }

        $payrolls = $query->get();

        $html = view('payroll._past', compact('payrolls', 'business'))->render();

        return RequestResponse::ok('Payrolls filtered successfully.', ['html' => $html]);
    }

    // master payroll
    public function downloadMasterRoll(Request $request)
    {
        $businessSlug = $request->route('business') ?? session('active_business_slug');
        $business = Business::findBySlug($businessSlug);

        if (!$business) {
            return response()->json(['error' => 'Business not found.'], 404);
        }

        $id = $request->id ?? $request->route('id');
        $type    = in_array($request->type, ['detailed', 'summary']) ? $request->type : 'detailed';
        $format  = in_array(strtolower($request->format), ['xlsx', 'csv', 'pdf']) ? strtolower($request->format) : 'xlsx';
        $groupBy = in_array($request->groupBy, ['location', 'department', 'job_category']) ? $request->groupBy : null;

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $monthName = \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F');
        $groupSuffix = $groupBy ? "-by-{$groupBy}" : '';
        $fileName = "master-roll-{$payroll->payrun_year}-{$monthName}-{$type}{$groupSuffix}.{$format}";

        // ── XLSX / CSV ───────────────────────────────────────────────────────
        if (in_array($format, ['xlsx', 'csv'])) {
            return Excel::download(
                new \App\Exports\Masterrollexport($payroll, $business, $type, $groupBy),
                $fileName
            );
        }

        // ── PDF ──────────────────────────────────────────────────────────────
        if ($format === 'pdf') {
            $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)
                ->with([
                    'employee.user',
                    'employee.location',
                    'employee.employmentDetails.department',
                    'employee.employmentDetails.jobCategory',
                    'payroll'
                ])
                ->get();

            // Collect dynamic columns — loop over ALL employee payrolls
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

            // Fallback from payroll_settings
            $employeeIds = $employeePayrolls->pluck('employee_id')->filter()->unique()->values();
            if ($employeeIds->isNotEmpty()) {
                $settings = \Illuminate\Support\Facades\DB::table('payroll_settings')
                    ->where('year',  $payroll->payrun_year)
                    ->where('month', $payroll->payrun_month)
                    ->whereIn('employee_id', $employeeIds)
                    ->get(['allowances', 'deductions']);

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
                'business'        => $business,
                'payroll'         => $payroll,
                'employeePayrolls' => $employeePayrolls,
                'allowanceSlugs'  => $allowanceSlugs,
                'deductionSlugs'  => $deductionSlugs,
                'currency'        => $payroll->currency ?? 'KES',
            ])->setPaper('a3', 'landscape');

            return $pdf->download($fileName);
        }

        return response()->json(['error' => 'Unsupported format.'], 400);
    }

    /**
     * Main NSSF download dispatcher
     * Handles: new_remittance | pre_2018 | old_format | grouped | schedule
     *
     * Route example:
     *   GET /business/{business}/payroll/nssf/download
     *   ?format_type=new_remittance&payroll_id=5&format=xlsx
     *   ?format_type=grouped&payroll_id=5&format=xlsx&group_by=department
     *   ?format_type=schedule&payroll_id=5&format=pdf
     */
    public function downloadNssf(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            abort(404, 'Business not found.');
        }

        $payroll = Payroll::where('business_id', $business->id)
            ->where('id', $request->payroll_id)
            ->with([
                'employeePayrolls.employee.user',
                'employeePayrolls.employee.location',
                'employeePayrolls.employee.employmentDetails.department',
                'employeePayrolls.employee.employmentDetails.jobCategory'
            ])
            ->firstOrFail();

        $formatType = $request->format_type; // new_remittance | pre_2018 | old_format | grouped | schedule
        $fileFormat = strtolower($request->format ?? 'xlsx'); // xlsx | csv | pdf
        $groupBy    = $request->group_by ?? 'department';     // department | location | job_category

        $monthName  = \Carbon\Carbon::createFromFormat('m', $payroll->payrun_month)->format('F');
        $baseName   = "NSSF_{$payroll->payrun_year}_{$monthName}";

        switch ($formatType) {

            // ── New NSSF Return/Remittance Format ──────────────────────────
            case 'new_remittance':
                $fileName = "{$baseName}_New_Remittance.{$fileFormat}";
                if ($fileFormat === 'pdf') {
                    abort(400, 'PDF not supported for this format. Use XLSX or CSV.');
                }
                return Excel::download(new \App\Exports\Nssfnewremittanceexport($payroll), $fileName);

                // ── Pre-2018 Format ────────────────────────────────────────────
            case 'pre_2018':
                $fileName = "{$baseName}_Pre2018.{$fileFormat}";
                if ($fileFormat === 'pdf') {
                    abort(400, 'PDF not supported for this format. Use XLSX or CSV.');
                }
                return Excel::download(new \App\Exports\Nssfpre2018export($payroll), $fileName);

                // ── Old NSSF Format ────────────────────────────────────────────
            case 'old_format':
                $fileName = "{$baseName}_Old_Format.{$fileFormat}";
                if ($fileFormat === 'pdf') {
                    abort(400, 'PDF not supported for this format. Use XLSX or CSV.');
                }
                return Excel::download(new \App\Exports\Nssfoldformatexport($payroll), $fileName);

                // ── Grouped by Department / Location / Job Category ────────────
            case 'grouped':
                $groupLabel = ucfirst(str_replace('_', ' ', $groupBy));
                $fileName   = "{$baseName}_Grouped_by_{$groupLabel}.{$fileFormat}";

                if ($fileFormat === 'pdf') {
                    $data = $this->buildNssfGroupedPdfData($payroll, $groupBy);
                    $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_grouped_pdf', [
                        'business'   => $business,
                        'payroll'    => $payroll,
                        'data'       => $data,
                        'groupBy'    => $groupLabel,
                    ])->setPaper('a4', 'landscape');
                    return $pdf->download($fileName);
                }

                return Excel::download(
                    new \App\Exports\Nssfgroupedexport($payroll, $groupBy),
                    $fileName
                );

                // ── Schedule PDF ───────────────────────────────────────────────
            case 'schedule':
                $fileName = "{$baseName}_Schedule.pdf";
                $data     = $this->buildNssfScheduleData($payroll);
                $pdf      = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_schedule_pdf', [
                    'business' => $business,
                    'payroll'  => $payroll,
                    'data'     => $data,
                ])->setPaper('a4', 'landscape');
                return $pdf->download($fileName);

            default:
                abort(400, "Unknown NSSF format type: {$formatType}");
        }
    }

    /**
     * Download NSSF Monthly Summary (month-by-month for a full year)
     * Supports XLSX (existing export) and PDF
     *
     * Route example:
     *   GET /business/{business}/payroll/nssf/monthly-summary?year=2025&format=xlsx
     */
    public function downloadNssfMonthlySummaryWithFormat(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $year       = $request->year ?? date('Y');
        $fileFormat = strtolower($request->format ?? 'xlsx');
        $fileName   = "NSSF_Monthly_Summary_{$year}.{$fileFormat}";

        try {
            if ($fileFormat === 'pdf') {
                // Build raw data for the PDF view
                $rows = $this->buildNssfMonthlySummaryRows($business, $year);
                $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.reports.nssf_monthly_summary_pdf', [
                    'business' => $business,
                    'year'     => $year,
                    'rows'     => $rows,
                ])->setPaper('a3', 'landscape');
                return $pdf->download($fileName);
            }

            // Default: XLSX via existing export class
            return Excel::download(
                new \App\Exports\NssfMonthlySummaryExport($business, $year),
                $fileName
            );
        } catch (\Exception $e) {
            \Log::error("NSSF Monthly Summary export failed: " . $e->getMessage());
            return RequestResponse::badRequest('Failed to generate NSSF monthly summary: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build flat array of NSSF data for the Schedule PDF view.
     */
    private function buildNssfScheduleData(Payroll $payroll): array
    {
        return EmployeePayroll::where('payroll_id', $payroll->id)
            ->with(['employee.user'])
            ->get()
            ->map(function ($ep) {
                $nssfTotal   = floatval($ep->nssf ?? 0);
                $empContrib  = round($nssfTotal / 2, 2);
                $erContrib   = round($nssfTotal / 2, 2);
                $employee    = $ep->employee;
                $user        = $employee->user ?? null;
                return [
                    'payroll_no' => $employee->employee_code ?? 'N/A',
                    'name'       => $user->name ?? 'N/A',
                    'id_no'      => $employee->national_id ?? 'N/A',
                    'nssf_no'    => $employee->nssf_no ?? 'N/A',
                    'gross_pay'  => floatval($ep->gross_pay ?? 0),
                    'employee'   => $empContrib,
                    'employer'   => $erContrib,
                    'total'      => $nssfTotal,
                ];
            })->toArray();
    }

    /**
     * Build grouped data array for the NSSF Grouped PDF view.
     */
    private function buildNssfGroupedPdfData(Payroll $payroll, string $groupBy): array
    {
        $employeePayrolls = EmployeePayroll::where('payroll_id', $payroll->id)
            ->with([
                'employee.user',
                'employee.location',
                'employee.employmentDetails.department',
                'employee.employmentDetails.jobCategory',
            ])
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

    /**
     * Build raw month-by-month NSSF rows for PDF rendering.
     * Returns array of ['name' => ..., 'months' => [1..12 => amount], 'total' => ...]
     */
    protected function buildNssfMonthlySummaryRows(Business $business, int $year): array
    {
        $months = range(1, 12);

        // Fetch ALL payrolls for this business/year in one query
        $payrolls = \App\Models\Payroll::where('business_id', $business->id)
            ->where('payrun_year', $year)
            ->whereIn('payrun_month', $months)
            ->get()
            ->keyBy('payrun_month');   // keyed by month number 1–12

        // Collect every unique employee who appears in any of those payrolls
        $allEmployeeIds = \App\Models\EmployeePayroll::whereIn(
            'payroll_id',
            $payrolls->pluck('id')
        )->distinct()->pluck('employee_id');

        $employees = \App\Models\Employee::whereIn('id', $allEmployeeIds)
            ->with('user')
            ->get()
            ->keyBy('id');

        // Build a lookup: [employee_id][month] => nssf_amount
        $nssfByEmployeeMonth = [];
        foreach ($payrolls as $month => $payroll) {
            $eps = \App\Models\EmployeePayroll::where('payroll_id', $payroll->id)
                ->get(['employee_id', 'nssf', 'deductions']);

            foreach ($eps as $ep) {
                $nssf = floatval($ep->nssf ?? 0);
                if ($nssf == 0) {
                    $deductions = json_decode($ep->deductions, true) ?? [];
                    $nssf = floatval($deductions['nssf'] ?? 0);
                }
                $nssfByEmployeeMonth[$ep->employee_id][$month] = $nssf;
            }
        }

        // ── Build rows ────────────────────────────────────────────────────────
        $rows         = [];
        $monthTotals  = array_fill(1, 12, 0.0);  // per-month running totals

        foreach ($employees as $empId => $employee) {
            $name       = $employee->user->name ?? 'N/A';
            $rowTotal   = 0;
            $rowMonths  = [];

            foreach ($months as $m) {
                $amount        = $nssfByEmployeeMonth[$empId][$m] ?? 0;
                $rowMonths[$m] = $amount;
                $rowTotal     += $amount;

                // Accumulate into the per-month column total
                if ($amount > 0) {
                    $monthTotals[$m] += $amount;
                }
            }

            // Only include employees who have NSSF in at least one month
            if ($rowTotal == 0) {
                continue;
            }

            $rows[] = array_merge(
                ['name' => $name],
                array_map(fn($v) => $v > 0 ? $v : null, $rowMonths),
                ['total' => $rowTotal]
            );
        }

        // ── Totals row — each cell = sum of that month column ─────────────────
        $grandTotal = array_sum($monthTotals);

        $totalsRow = array_merge(
            ['name' => 'Total'],
            array_map(fn($v) => $v > 0 ? $v : null, $monthTotals),
            ['total' => $grandTotal]
        );

        $rows[] = $totalsRow;   // append as the last row

        return $rows;
    }
    /**
     * Show the variance report selection page
     * GET /business/{business}/payroll/variance
     */

    public function variancePage(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) abort(404);

        $years = \App\Models\Payroll::where('business_id', $business->id)
            ->distinct()->orderByDesc('payrun_year')
            ->pluck('payrun_year')->take(5)->toArray();

        return view('payroll.variance', compact('business', 'years'));
    }

    /**
     * Download variance report — now accepts optional ?reasons={"Gross Pay":"..."} JSON
     */
    public function downloadVarianceReport(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.');

        $mode   = $request->get('mode', 'year');
        $format = strtolower($request->get('format', 'xlsx'));

        // Decode AI reasons if passed from the frontend
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
            $mn = [
                1 => 'Jan',
                2 => 'Feb',
                3 => 'Mar',
                4 => 'Apr',
                5 => 'May',
                6 => 'Jun',
                7 => 'Jul',
                8 => 'Aug',
                9 => 'Sep',
                10 => 'Oct',
                11 => 'Nov',
                12 => 'Dec'
            ];
            $label = "{$mn[$params['month1']]}{$params['year1']}_vs_{$mn[$params['month2']]}{$params['year2']}";
        }

        $fileName = "Payroll_Variance_{$label}.{$format}";

        try {
            if ($format === 'pdf') {
                $data = $this->buildVarianceData($business, $params);
                $pdf  = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                    'payroll.reports.payroll_variance_pdf',
                    compact('business', 'params', 'data', 'reasons')
                )->setPaper('a4', 'landscape');
                return $pdf->download($fileName);
            }

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\Payrollvarianceexport($business, $params, $reasons),
                $fileName
            );
        } catch (\Exception $e) {
            \Log::error("Variance export failed: " . $e->getMessage());
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

        return response()->json([
            'business' => $business->company_name ?? $business->name,
            'currency' => $business->currency ?? 'KES',
            'params'   => $params,
            'data'     => $data,
        ]);
    }

    private function buildVarianceData(Business $business, array $params): array
    {
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $fields = [
            'gross' => 'Gross Pay',
            'net' => 'Net Pay',
            'paye' => 'PAYE',
            'nssf' => 'NSSF',
            'shif' => 'SHIF',
            'hl' => 'Housing Levy'
        ];

        $fetch = function (int $year, ?int $month = null) use ($business): array {
            $q = \App\Models\Payroll::where('business_id', $business->id)
                ->where('payrun_year', $year)
                ->with('employeePayrolls');
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
                $summary[] = [
                    'metric' => $label,
                    'period1' => $v1,
                    'period2' => $v2,
                    'variance' => $v2 - $v1,
                    'variance_pct' => $vPct($v1, $v2)
                ];
            }

            $monthly = [];
            foreach ($monthNames as $m => $name) {
                $g1 = $d1[$m]['gross'] ?? 0;
                $g2 = $d2[$m]['gross'] ?? 0;
                $monthly[] = [
                    'month' => $name,
                    'period1' => $g1,
                    'period2' => $g2,
                    'variance' => $g2 - $g1,
                    'var_pct' => $vPct($g1, $g2),
                    'count1' => $d1[$m]['count'] ?? 0,
                    'count2' => $d2[$m]['count'] ?? 0
                ];
            }

            return compact('summary', 'monthly');
        } else {
            $r1 = $fetch($params['year1'], $params['month1'])[$params['month1']] ?? array_fill_keys(['gross', 'net', 'paye', 'nssf', 'shif', 'hl', 'count'], 0);
            $r2 = $fetch($params['year2'], $params['month2'])[$params['month2']] ?? array_fill_keys(['gross', 'net', 'paye', 'nssf', 'shif', 'hl', 'count'], 0);

            $summary = [];
            foreach ($fields as $key => $label) {
                $summary[] = [
                    'metric' => $label,
                    'period1' => $r1[$key],
                    'period2' => $r2[$key],
                    'variance' => $r2[$key] - $r1[$key],
                    'variance_pct' => $vPct($r1[$key], $r2[$key])
                ];
            }
            return ['summary' => $summary, 'monthly' => []];
        }
    }
}
