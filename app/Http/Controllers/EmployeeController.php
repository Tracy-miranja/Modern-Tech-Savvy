<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Location;
use App\Models\EmployeePaymentDetail;
use App\Models\JobCategory;
use App\Models\EmployeeDocument;
use App\Imports\EmployeesImport;
use App\Enums\Status;
use App\Services\NotificationService;
use App\Notifications\SystemAlertNotification;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\EmployeeContractAction;
use App\Notifications\ContractReminderNotification;
use App\Notifications\TerminationNotification;
use App\Notifications\SuspensionNotification;
use App\Notifications\ReinstatementNotification;
use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Password;
use App\Notifications\WelcomeEmployeeNotification;
use App\Models\Role;

use function Laravel\Prompts\error;

class EmployeeController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $page = 'Employees';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->back()->with('error', 'Business not found.');
        }

        $employees = Employee::where('business_id', $business->id)
            ->with([
                'user',
                'business',
                'location',
                'department',
                'employmentDetails.jobCategory',
                'paymentDetails'
            ])
            ->get();

        $departments = Department::where('business_id', $business->id)->get();
        $locations = Location::where('business_id', $business->id)->get();
        $jobCategories = JobCategory::where('business_id', $business->id)->get();

        return view('employees.index', compact('employees', 'departments', 'locations', 'jobCategories', 'business', 'page'));
    }

    /**
     * Show edit form for employee payment details
     */
    public function editPaymentDetails(Request $request, $businessSlug, $employeeId)
    {
        try {
            $business = Business::findBySlug($businessSlug);
            if (!$business) {
                return response()->json([
                    'message' => 'Business not found.',
                    'data' => null
                ], 404);
            }

            $employee = Employee::with(['user', 'paymentDetails'])
                ->where('business_id', $business->id)
                ->findOrFail($employeeId);

            $form = view('employees._payment_details_form', compact('employee', 'business'))->render();

            return response()->json([
                'message' => 'Payment details form loaded successfully.',
                'data' => $form
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error loading payment details form: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load payment details form: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Store or update employee payment details (including hourly rate)
     */
    public function storePaymentDetails(Request $request, $businessSlug, $employeeId)
    {
        $validated = $request->validate([
            'payment_type' => 'required|in:salary,hourly',
            'basic_salary' => 'required_if:payment_type,salary|nullable|numeric|min:0',
            'hourly_rate' => 'required_if:payment_type,hourly|nullable|numeric|min:0',
            'currency' => 'required|string|size:3|in:KES,USD,UGX',
            'payment_mode' => 'required|string|in:bank,cash,cheque,mpesa',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'bank_branch_code' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

    // DataTables pagination params
    $start  = $request->input('start', 0);
    $length = $request->input('length', 10);

    $recordsTotal    = Employee::where('business_id', $business->id)->count();
    $recordsFiltered = $query->count();

    $employees = $query->skip($start)->take($length)->get();

    $data = $employees->map(function ($employee) use ($business) {
        return [
            'id' => $employee->id,
            'name' => $employee->user->name,
            'employee_code' => $employee->employee_code,
            'department' => $employee->department ? $employee->department->name : 'N/A',
            'job_category' => optional($employee->employmentDetails)->jobCategory ? $employee->employmentDetails->jobCategory->name : 'N/A',
            'location' => $employee->location ? $employee->location->name : $employee->business->company_name,
            'basic_salary' => number_format((float) (optional($employee->paymentDetails)->basic_salary ?? 0), 2) . ' ' . (optional($employee->paymentDetails)->currency ?? 'N/A'),
            'actions' => '<div class="btn-group">' .
                '<button class="btn btn-sm btn-outline-primary" onclick="viewEmployee(' . $employee->id . ')"><i class="fa fa-eye"></i> View</button>' .
                '<button class="btn btn-sm btn-outline-warning" onclick="editEmployee(' . $employee->id . ')"><i class="fa fa-edit"></i> Edit</button>' .
                '<a href="' . route('business.performance.employee', ['business' => $business->slug, 'employee' => $employee->id]) . '" class="btn btn-sm btn-outline-info"><i class="fa fa-chart-line"></i> Performance</a>' .
                '<button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(' . $employee->id . ')"><i class="fa fa-trash"></i> Delete</button>' .
                '</div>'
        ];
    });

            $employee = Employee::where('business_id', $business->id)->findOrFail($employeeId);

            // Prepare payment data
            $paymentData = [
                'payment_type' => $validated['payment_type'],
                'currency' => $validated['currency'],
                'payment_mode' => $validated['payment_mode'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'bank_name' => $validated['bank_name'],
                'bank_code' => $validated['bank_code'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'bank_branch_code' => $validated['bank_branch_code'] ?? null,
            ];

            // Set salary or hourly rate based on payment type
            if ($validated['payment_type'] === 'hourly') {
                $paymentData['hourly_rate'] = $validated['hourly_rate'];
                $paymentData['basic_salary'] = 0; // Set salary to 0 for hourly employees

                Log::info('Storing hourly payment details', [
                    'employee_id' => $employeeId,
                    'hourly_rate' => $validated['hourly_rate']
                ]);
            } else {
                $paymentData['basic_salary'] = $validated['basic_salary'];
                $paymentData['hourly_rate'] = 0; // Set hourly rate to 0 for salaried employees

                Log::info('Storing salary payment details', [
                    'employee_id' => $employeeId,
                    'basic_salary' => $validated['basic_salary']
                ]);
            }

            // Check for duplicate account number (excluding current employee)
            $duplicateAccount = EmployeePaymentDetail::where('account_number', $validated['account_number'])
                ->where('employee_id', '!=', $employeeId)
                ->first();

            if ($duplicateAccount) {
                return RequestResponse::badRequest('Account number already exists for another employee.');
            }

            // Update or create payment details
            $employee->paymentDetails()->updateOrCreate(
                ['employee_id' => $employee->id],
                $paymentData
            );

            DB::commit();

            $message = $validated['payment_type'] === 'hourly'
                ? 'Hourly payment details updated successfully.'
                : 'Salary payment details updated successfully.';

            Log::info('Payment details updated successfully', [
                'employee_id' => $employeeId,
                'payment_type' => $validated['payment_type']
            ]);

            return RequestResponse::ok($message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payment details', [
                'employee_id' => $employeeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return RequestResponse::badRequest('Failed to update payment details: ' . $e->getMessage());
        }
    }

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $query = Employee::where('business_id', $business->id)
            ->with([
                'user',
                'department',
                'location',
                'employmentDetails.jobCategory',
                'paymentDetails'  // already correct
            ]);

        // Your filters...
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }
        if ($department = $request->input('department')) {
            $query->where('department_id', $department);
        }
        if ($location = $request->input('location')) {
            $query->where('location_id', $location);
        }
        if ($jobCategory = $request->input('job_category')) {
            $query->whereHas('employmentDetails', function ($q) use ($jobCategory) {
                $q->where('job_category_id', $jobCategory);
            });
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        $recordsTotal    = Employee::where('business_id', $business->id)->count();
        $recordsFiltered = $query->count();

        $employees = $query->skip($start)->take($length)->get();

        $data = $employees->map(function ($employee) {
            $payment = $employee->paymentDetails;
            $currency = $payment?->currency ?? 'KES';

            $hourly  = '—';
            $monthly = '—';

            if ($payment) {
                if ($payment->payment_type === 'hourly') {
                    $hourly = number_format((float) ($payment->hourly_rate ?? 0), 2) . ' ' . $currency . '/hr';
                } else {
                    $monthly = number_format((float) ($payment->basic_salary ?? 0), 2) . ' ' . $currency;
                }
            }

            return [
                'name'           => $employee->user?->name ?? 'N/A',
                'employee_code'  => $employee->employee_code ?? 'N/A',
                'department'     => $employee->department?->name ?? 'N/A',
                'job_category'   => optional($employee->employmentDetails?->jobCategory)->name ?? 'N/A',
                'location'       => $employee->location?->name ?? ($employee->business?->company_name ?? 'Main'),
                'monthly_salary' => $monthly,   // for Monthly Salary column
                'hourly_rate'    => $hourly,    // for Hourly Rate column
                'actions'        => '<div class="btn-group">'
                    . '<button class="btn btn-sm btn-outline-primary" onclick="viewEmployee(' . $employee->id . ')"><i class="fa fa-eye"></i> View</button>'
                    . '<button class="btn btn-sm btn-outline-warning" onclick="editEmployee(' . $employee->id . ')"><i class="fa fa-edit"></i> Edit</button>'
                    . '<button class="btn btn-sm btn-outline-danger" onclick="deleteEmployee(' . $employee->id . ')"><i class="fa fa-trash"></i> Delete</button>'
                    . '</div>'
            ];
        })->toArray();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    //added this for dedicated leave entitlements
    public function fetchForEntitlements(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));

            $query = $business->employees()->with([
                'department',
                'location',
                'jobCategory',
                'employmentTerm',
                'user'
            ]);

            // Filter: Locations
            $locations = $request->input('locations');
            if ($locations) {
                $locations = is_string($locations) ? json_decode($locations, true) : $locations;
                if (!empty($locations) && !in_array('all', $locations)) {
                    $query->whereHas('location', function ($q) use ($locations) {
                        $q->whereIn('slug', $locations)->orWhereIn('id', $locations);
                    });
                }
            }

            // Filter: Departments
            $departments = $request->input('departments');
            if ($departments) {
                $departments = is_string($departments) ? json_decode($departments, true) : $departments;
                if (!empty($departments) && !in_array('all', $departments)) {
                    $query->whereHas('department', function ($q) use ($departments) {
                        $q->whereIn('slug', $departments)->orWhereIn('id', $departments);
                    });
                }
            }

            // Filter: Job Categories
            $jobCategories = $request->input('job_categories');
            if ($jobCategories) {
                $jobCategories = is_string($jobCategories) ? json_decode($jobCategories, true) : $jobCategories;
                if (!empty($jobCategories) && !in_array('all', $jobCategories)) {
                    $query->whereHas('jobCategory', function ($q) use ($jobCategories) {
                        $q->whereIn('slug', $jobCategories)->orWhereIn('id', $jobCategories);
                    });
                }
            }

            // Filter: Employment Terms
            $employmentTerms = $request->input('employment_terms');
            if ($employmentTerms) {
                $employmentTerms = is_string($employmentTerms) ? json_decode($employmentTerms, true) : $employmentTerms;
                if (!empty($employmentTerms) && !in_array('all', $employmentTerms)) {
                    $query->whereHas('employmentTerm', function ($q) use ($employmentTerms) {
                        $q->whereIn('slug', $employmentTerms)->orWhereIn('id', $employmentTerms);
                    });
                }
            }

            $employees = $query->get();

            return response()->json([
                'success' => true,
                'employees' => $employees
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching employees'
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        // Identity fields (employee_code/national_id/tax_no/nhif_no/nssf_no/
        // passport_no) are unique per business, not across the whole
        // system - the same real person can legitimately be an employee at
        // more than one business under the same account (see the email
        // reuse handling below), so their national ID/tax PIN must be
        // allowed to repeat across businesses, just not within one.
        $uniquePerBusiness = fn (string $column) => \Illuminate\Validation\Rule::unique('employees', $column)
            ->where(fn ($q) => $q->where('business_id', $business->id));

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            // No unique:users,email here - an email already belonging to a
            // User is handled below (reuse the existing account for a
            // second business) rather than rejected outright.
            'email' => 'required|email',
            'gender' => 'required|string|max:20',
            'employee_code' => ['required', 'string', 'max:50', $uniquePerBusiness('employee_code')],
            'department_id' => 'nullable|exists:departments,id',
            'job_category_id' => 'nullable|exists:job_categories,id',
            'organogram_role_id' => 'nullable|exists:organogram_roles,id',
            'location_id' => 'nullable|exists:locations,id',
            // 'basic_salary' => 'required|numeric|min:0',
            'payment_type' => 'required|in:salary,hourly',
            'basic_salary' => 'required_if:payment_type,salary|nullable|numeric|min:0',
            'hourly_rate' => 'required_if:payment_type,hourly|nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_mode' => 'required|string|in:bank,cash,cheque,mpesa',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:employee_payment_details,account_number|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'bank_branch_code' => 'nullable|string|max:50',
            'national_id' => ['nullable', 'string', 'max:255', $uniquePerBusiness('national_id')],
            'tax_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('tax_no')],
            'date_of_birth' => 'required|date|before:today',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'nhif_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nhif_no')],
            'nssf_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nssf_no')],
            'passport_no' => ['nullable', 'string', 'max:255', $uniquePerBusiness('passport_no')],
            'passport_issue_date' => 'nullable|date|before:today',
            'passport_expiry_date' => 'nullable|date|after:passport_issue_date',
            'place_of_birth' => 'nullable|string|max:255',
            'place_of_issue' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'permanent_address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:255',
            'is_exempt_from_payroll' => 'nullable|boolean',
            'resident_status' => 'nullable|string|max:255',
            'kra_employee_status' => 'nullable|in:Primary Employee,Secondary Employee',
            'profile_picture' => 'nullable|file|image|max:2048',
            'employment_date' => 'nullable|date|before_or_equal:today',
            'employment_term' => 'required|in:permanent,contract,temporary,fulltime,internship,consultant,locum',
            'probation_end_date' => 'nullable|date|after:employment_date',
            'contract_end_date' => 'nullable|date|after:employment_date',
            'retirement_date' => 'nullable|date|after:employment_date',
            'license_reg_number' => 'nullable|string|max:255',
            'license_expiry_date' => 'nullable|date',
            'second_probation_end_date' => 'nullable|date|after_or_equal:probation_end_date',
            'job_description' => 'nullable|string|max:1000',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'document_types.*' => 'nullable|string|max:255',
            'has_disability_exemption'  => 'nullable|boolean',
            'pwd_certificate_no'        => 'required_if:has_disability_exemption,1|nullable|string|max:255',
            'pwd_ncpwd_membership_no'   => 'required_if:has_disability_exemption,1|nullable|string|max:255',
            'pwd_exemption_limit'       => 'nullable|numeric|min:0|max:150000',
        ]);

        // An email already belonging to a User is reused rather than
        // rejected - the same real person can legitimately be an employee
        // at more than one business under one account (mirrors
        // PlatformAdminController::store()'s krest-admin-grant pattern).
        // Guard against the one thing that must still be blocked: being
        // added as an employee TWICE at the SAME business.
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            $alreadyEmployeeHere = Employee::where('user_id', $existingUser->id)
                ->where('business_id', $business->id)
                ->exists();
            if ($alreadyEmployeeHere) {
                return RequestResponse::badRequest('This email already belongs to an employee at this business.');
            }
        }
        $isNewUser = !$existingUser;

        try {
            DB::beginTransaction();

            $user = $existingUser ?: User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => null,
            ]);

            if ($isNewUser) {
                $token = Password::createToken($user);
                $user->sendPasswordResetNotification($token);
                $user->notify(new WelcomeEmployeeNotification($user, $token));
            } else {
                // Existing account - no password reset, just a plain
                // notice that they've been added here too.
                $user->notify(new WelcomeEmployeeNotification($user, null, $business->company_name));
            }

            // 'business-employee' is a global role (not per-business) -
            // most roles in this table have a null business_id, but a
            // handful of legacy rows (from before multi-tenancy) were
            // pinned to business_id=1 (krest). Filtering by business_id
            // here meant every OTHER business's new employees silently
            // got no role at all, since no matching row existed for them.
            // firstOrCreate is a self-healing fallback in case the role
            // row is ever missing entirely.
            $role = Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']);
            $user->assignRole($role);

            $employee = Employee::create([
                'user_id' => $user->id,
                'business_id' => $business->id,
                'employee_code' => $validated['employee_code'],
                'department_id' => $validated['department_id'] ?? null,
                'job_category_id' => $validated['job_category_id'] ?? null,
                'organogram_role_id' => $validated['organogram_role_id'] ?? null,
                'location_id' => $validated['location_id'] ?? null,
                'national_id' => $validated['national_id'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'nhif_no' => $validated['nhif_no'] ?? null,
                'nssf_no' => $validated['nssf_no'] ?? null,
                'tax_no' => $validated['tax_no'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'alternate_phone' => $validated['alternate_phone'] ?? null,
                'passport_no' => $validated['passport_no'] ?? null,
                'passport_issue_date' => $validated['passport_issue_date'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'place_of_birth' => $validated['place_of_birth'] ?? null,
                'place_of_issue' => $validated['place_of_issue'] ?? null,
                'address' => $validated['address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'is_exempt_from_payroll' => $validated['is_exempt_from_payroll'] ?? false,
                'resident_status' => $validated['resident_status'] ?? null,
                'kra_employee_status' => $validated['kra_employee_status'] ?? null,

            ]);

            $employee->employmentDetails()->create([
                'job_category_id' => $validated['job_category_id'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'employment_date' => $validated['employment_date'] ?? now(),
                'employment_term' => $validated['employment_term'],
                'probation_end_date' => $validated['probation_end_date'] ?? null,
                'contract_end_date' => $validated['contract_end_date'] ?? null,
                'retirement_date' => $validated['retirement_date'] ?? null,
                'job_description' => $validated['job_description'] ?? null,
                'license_reg_number' => $validated['license_reg_number'] ?? null,
                'license_expiry_date' => $validated['license_expiry_date'] ?? null,
                'second_probation_end_date' => $request->second_probation_end_date,
            ]);

            // $employee->paymentDetails()->create([
            //     'basic_salary' => $validated['basic_salary'],
            //     'currency' => $validated['currency'],
            //     'payment_mode' => $validated['payment_mode'],
            //     'account_name' => $validated['account_name'],
            //     'account_number' => $validated['account_number'],
            //     'bank_name' => $validated['bank_name'],
            //     'bank_code' => $validated['bank_code'] ?? null,
            //     'bank_branch' => $validated['bank_branch'] ?? null,
            //     'bank_branch_code' => $validated['bank_branch_code'] ?? null,
            // ]);

            $paymentDetailsData = [
                'payment_type' => $validated['payment_type'],
                'currency' => $validated['currency'],
                'payment_mode' => $validated['payment_mode'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'bank_name' => $validated['bank_name'],
                'bank_code' => $validated['bank_code'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'bank_branch_code' => $validated['bank_branch_code'] ?? null,
            ];

            if ($validated['payment_type'] === 'hourly') {
                $paymentDetailsData['hourly_rate'] = $validated['hourly_rate'];
                $paymentDetailsData['basic_salary'] = 0;
            } else {
                $paymentDetailsData['basic_salary'] = $validated['basic_salary'];
                $paymentDetailsData['hourly_rate'] = 0;
            }
            // ── WHT fields ──────────────────────────────────────────────
            $isConsultant = in_array($validated['employment_term'], ['consultant', 'locum']);
            $paymentDetailsData['is_consultant']               = $isConsultant;
            $paymentDetailsData['wht_payment_type']            = $isConsultant ? ($request->wht_payment_type ?? 'professional_fees') : null;
            $paymentDetailsData['wht_residency']               = $isConsultant ? ($request->wht_residency ?? 'resident') : 'resident';
            $paymentDetailsData['wht_pin']                     = $isConsultant ? $request->wht_pin : null;
            $paymentDetailsData['consultant_shif_covered']     = $isConsultant && $request->has('consultant_shif_covered');
            $paymentDetailsData['consultant_shif_basis']       = $isConsultant ? $request->consultant_shif_basis : null;
            $paymentDetailsData['consultant_shif_fixed_amount'] = $isConsultant ? $request->consultant_shif_fixed_amount : null;
            $paymentDetailsData['consultant_nssf_covered']     = $isConsultant && $request->has('consultant_nssf_covered');
            $paymentDetailsData['consultant_nssf_basis']       = $isConsultant ? $request->consultant_nssf_basis : null;
            $paymentDetailsData['consultant_nssf_fixed_amount'] = $isConsultant ? $request->consultant_nssf_fixed_amount : null;
            // ────────────────────────────────────────────────────────────
            $employee->paymentDetails()->create($paymentDetailsData);
            $employee->payrollDetail()->updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'business_id'              => $business->id,
                    'has_disability_exemption' => $validated['has_disability_exemption'] ?? false,
                    'pwd_certificate_no'       => $validated['pwd_certificate_no'] ?? null,
                    'pwd_ncpwd_membership_no'  => $validated['pwd_ncpwd_membership_no'] ?? null,
                    'pwd_exemption_limit'      => $validated['pwd_exemption_limit'] ?? 150000.00,
                ]
            );

            if ($request->hasFile('profile_picture')) {
                $employee->addMedia($request->file('profile_picture'))->toMediaCollection('avatars');
                Log::info('Profile picture uploaded for new employee ID: ' . $employee->id);
            }

            // Handle document upload (optional)
            $documents = $request->file('documents');
            if ($documents && is_array($documents)) {
                $documentTypes = $request->input('document_types', []);

                try {
                    foreach ($documents as $index => $file) {
                        if ($file && $file->isValid()) {
                            $documentType = $documentTypes[$index] ?? 'Unknown';
                            $document = EmployeeDocument::create([
                                'employee_id' => $employee->id,
                                'document_type' => $documentType,
                            ]);

                            $document->addMedia($file)->toMediaCollection('employeeDocuments');
                        }
                    }
                    Log::info('Documents uploaded successfully for employee ID: ' . $employee->id);
                } catch (\Exception $e) {
                    Log::error('Failed to upload documents: ' . $e->getMessage());
                    // Notify via toastr but continue with employee creation
                    return RequestResponse::created('Employee created successfully, but some documents failed to upload.', $employee->id)
                        ->withHeaders(['X-Toastr-Message' => 'Some documents failed to upload. Please try again.']);
                }
            }

            DB::commit();
            Log::debug('Employee created successfully.', ['employee_id' => $employee->id]);

            // Populate this employee's default reporting line from the org-structure template.
            $employee->fresh()->syncManagerFromTemplate();

            return RequestResponse::created('Employee created successfully.', $employee->id);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create employee.', ['error' => $e->getMessage()]);
            return RequestResponse::badRequest('Failed to create employee: ' . $e->getMessage());
        }
    }

    public function edit(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            $employee = $request->employee_id ? Employee::with('user', 'paymentDetails', 'employmentDetails')->findOrFail($request->employee_id) : null;
            $departments = Department::where('business_id', $business->id)->get();
            $locations = Location::where('business_id', $business->id)->get();
            $jobCategories = JobCategory::where('business_id', $business->id)->get();
            $organogramRoles = \App\Models\OrganogramRole::where('business_id', $business->id)->orderBy('level')->get();

            $form = view('employees._form', compact('employee', 'departments', 'locations', 'jobCategories', 'organogramRoles', 'business'))->render();
            return response()->json([
                'message' => 'Form loaded successfully.',
                'data' => $form
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error loading employee edit form: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load form: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Same business_id/employee_code/national_id/tax_no/nhif_no/nssf_no/
        // passport_no scoping as store() - two DIFFERENT businesses can
        // legitimately reuse the same employee_code/national_id, so
        // uniqueness must only ever be checked within the ONE business this
        // employee actually belongs to, never across all of them. ignore($id)
        // excludes the row being edited itself, same as the old inline
        // "unique:employees,column,{id},id" rules did.
        $employee = Employee::findOrFail($id);
        $uniquePerBusiness = fn (string $column) => \Illuminate\Validation\Rule::unique('employees', $column)
            ->where(fn ($q) => $q->where('business_id', $employee->business_id))
            ->ignore($id);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($request->employee_id ? Employee::find($request->employee_id)->user_id : null),
            'gender' => 'required|string|max:20',
            'employee_code' => ['required', 'string', 'max:50', $uniquePerBusiness('employee_code')],
            'department_id' => 'nullable|exists:departments,id',
            'job_category_id' => 'nullable|exists:job_categories,id',
            'organogram_role_id' => 'nullable|exists:organogram_roles,id',
            'location_id' => 'nullable|exists:locations,id',
            'payment_type' => 'required|in:salary,hourly',
            'basic_salary' => 'required_if:payment_type,salary|nullable|numeric|min:0',
            'hourly_rate' => 'required_if:payment_type,hourly|nullable|numeric|min:0',
            // 'basic_salary' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_mode' => 'required|string|in:bank,cash,cheque,mpesa',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|unique:employee_payment_details,account_number,' . $id . ',employee_id|max:50',
            'bank_name' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:255',
            'bank_branch_code' => 'nullable|string|max:50',
            'national_id' => ['nullable', 'string', 'max:255', $uniquePerBusiness('national_id')],
            'tax_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('tax_no')],
            'date_of_birth' => 'required|date|before:today',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'nhif_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nhif_no')],
            'nssf_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nssf_no')],
            'passport_no' => ['nullable', 'string', 'max:255', $uniquePerBusiness('passport_no')],
            'passport_issue_date' => 'nullable|date|before:today',
            'passport_expiry_date' => 'nullable|date|after:passport_issue_date',
            'place_of_birth' => 'nullable|string|max:255',
            'place_of_issue' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'permanent_address' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'blood_group' => 'nullable|string|max:255',
            'is_exempt_from_payroll' => 'nullable|boolean',
            'resident_status' => 'nullable|string|max:255',
            'kra_employee_status' => 'nullable|in:Primary Employee,Secondary Employee',
            'profile_picture' => 'nullable|file|image|max:2048',
            'employment_date' => 'nullable|date|before_or_equal:today',
            'employment_term' => 'required|in:permanent,contract,temporary,fulltime,internship,consultant,locum',
            'probation_end_date' => 'nullable|date|after:employment_date',
            'contract_end_date' => 'nullable|date|after:employment_date',
            'retirement_date' => 'nullable|date|after:employment_date',
            'license_reg_number' => 'nullable|string|max:255',
            'license_expiry_date' => 'nullable|date',
            'job_description' => 'nullable|string|max:1000',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'document_types.*' => 'nullable|string|max:255',
            'second_probation_end_date' => 'nullable|date|after:probation_end_date',
            'has_disability_exemption'  => 'nullable|boolean',
            'pwd_certificate_no'        => 'required_if:has_disability_exemption,1|nullable|string|max:255',
            'pwd_ncpwd_membership_no'   => 'required_if:has_disability_exemption,1|nullable|string|max:255',
            'pwd_exemption_limit'       => 'nullable|numeric|min:0|max:150000',

        ]);

        Log::info('Updating employee ID: ' . $id, ['validated_data' => $validated]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($id);

            Log::info('Before Update - Employee Data', ['employee' => $employee->toArray()]);

            $employee->update([
                'employee_code' => $validated['employee_code'],
                'department_id' => $validated['department_id'] ?? null,
                'job_category_id' => $validated['job_category_id'] ?? null,
                'organogram_role_id' => $validated['organogram_role_id'] ?? null,
                'location_id' => $validated['location_id'] ?? null,
                'national_id' => $validated['national_id'] ?? null,
                'tax_no' => $validated['tax_no'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'nhif_no' => $validated['nhif_no'] ?? null,
                'nssf_no' => $validated['nssf_no'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'alternate_phone' => $validated['alternate_phone'] ?? null,
                'passport_no' => $validated['passport_no'] ?? null,
                'passport_issue_date' => $validated['passport_issue_date'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'place_of_birth' => $validated['place_of_birth'] ?? null,
                'place_of_issue' => $validated['place_of_issue'] ?? null,
                'address' => $validated['address'] ?? null,
                'permanent_address' => $validated['permanent_address'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'is_exempt_from_payroll' => $validated['is_exempt_from_payroll'] ?? false,
                'resident_status' => $validated['resident_status'] ?? null,
                'kra_employee_status' => $validated['kra_employee_status'] ?? null,

            ]);

            $employee->user->update([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            $employmentDetails = $employee->employmentDetails()->updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'job_category_id' => $validated['job_category_id'] ?? null,
                    'department_id' => $validated['department_id'] ?? null,
                    'employment_term' => $validated['employment_term'],
                    'employment_date' => $validated['employment_date'] ?? now(),
                    'probation_end_date' => $validated['probation_end_date'] ?? null,
                    'second_probation_end_date' => $validated['second_probation_end_date'] ?? null,
                    'contract_end_date' => $validated['contract_end_date'] ?? null,
                    'retirement_date' => $validated['retirement_date'] ?? null,
                    'job_description' => $validated['job_description'] ?? null,
                    'license_reg_number' => $validated['license_reg_number'] ?? null,
                    'license_expiry_date' => $validated['license_expiry_date'] ?? null
                ]
            );

            $paymentDetailsData = [
                'payment_type' => $validated['payment_type'],
                'currency' => $validated['currency'],
                'payment_mode' => $validated['payment_mode'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'bank_name' => $validated['bank_name'],
                'bank_code' => $validated['bank_code'] ?? null,
                'bank_branch' => $validated['bank_branch'] ?? null,
                'bank_branch_code' => $validated['bank_branch_code'] ?? null,
            ];

            if ($validated['payment_type'] === 'hourly') {
                $paymentDetailsData['hourly_rate'] = $validated['hourly_rate'];
                $paymentDetailsData['basic_salary'] = 0;
            } else {
                $paymentDetailsData['basic_salary'] = $validated['basic_salary'];
                $paymentDetailsData['hourly_rate'] = 0;
            }
            // ── WHT fields — MUST be set before updateOrCreate ──────────
            $isConsultant = in_array($validated['employment_term'], ['consultant', 'locum']);
            $paymentDetailsData['is_consultant']                = $isConsultant;
            $paymentDetailsData['wht_payment_type']             = $isConsultant ? ($request->wht_payment_type ?? 'professional_fees') : null;
            $paymentDetailsData['wht_residency']                = $isConsultant ? ($request->wht_residency ?? 'resident') : 'resident';
            $paymentDetailsData['wht_pin']                      = $isConsultant ? $request->wht_pin : null;
            $paymentDetailsData['consultant_shif_covered']      = $isConsultant && $request->has('consultant_shif_covered');
            $paymentDetailsData['consultant_shif_basis']        = $isConsultant ? $request->consultant_shif_basis : null;
            $paymentDetailsData['consultant_shif_fixed_amount'] = $isConsultant ? $request->consultant_shif_fixed_amount : null;
            $paymentDetailsData['consultant_nssf_covered']      = $isConsultant && $request->has('consultant_nssf_covered');
            $paymentDetailsData['consultant_nssf_basis']        = $isConsultant ? $request->consultant_nssf_basis : null;
            $paymentDetailsData['consultant_nssf_fixed_amount'] = $isConsultant ? $request->consultant_nssf_fixed_amount : null;


            $employee->paymentDetails()->updateOrCreate(
                ['employee_id' => $employee->id],
                $paymentDetailsData
            );

            $employee->payrollDetail()->updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    // 'business_id'              => $business->id,
                    'has_disability_exemption' => $validated['has_disability_exemption'] ?? false,
                    'pwd_certificate_no'       => $validated['pwd_certificate_no'] ?? null,
                    'pwd_ncpwd_membership_no'  => $validated['pwd_ncpwd_membership_no'] ?? null,
                    'pwd_exemption_limit'      => $validated['pwd_exemption_limit'] ?? 150000.00,
                ]
            );

            if ($request->hasFile('profile_picture')) {
                $employee->clearMediaCollection('avatars');
                $employee->addMedia($request->file('profile_picture'))->toMediaCollection('avatars');
                Log::info('Profile picture updated for employee ID: ' . $id);
            }

            // Handle document upload (optional)
            $documents = $request->file('documents');
            if ($documents && is_array($documents)) {
                $documentTypes = $request->input('document_types', []);

                try {
                    foreach ($documents as $index => $file) {
                        if ($file && $file->isValid()) {
                            $documentType = $documentTypes[$index] ?? 'Unknown';
                            $document = EmployeeDocument::create([
                                'employee_id' => $employee->id,
                                'document_type' => $documentType,
                            ]);

                            $document->addMedia($file)->toMediaCollection('employeeDocuments');
                        }
                    }
                    Log::info('Documents uploaded successfully for employee ID: ' . $id);
                } catch (\Exception $e) {
                    Log::error('Failed to upload documents: ' . $e->getMessage());
                    // Notify via toastr but continue with update
                    return RequestResponse::ok('Employee updated successfully, but some documents failed to upload.')
                        ->withHeaders(['X-Toastr-Message' => 'Some documents failed to upload. Please try again.']);
                }
            }

            DB::commit();
            Log::info('Employee update transaction committed.', ['employee_id' => $id]);

            // Re-sync this employee's default reporting line (no-op if manually overridden).
            $employee->fresh()->syncManagerFromTemplate();

            return RequestResponse::ok('Employee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update employee: ' . $e->getMessage(), [
                'employee_id' => $id,
                'request_data' => $request->all()
            ]);

            return RequestResponse::badRequest('Failed to update employee: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            DB::table('leave_entitlements')->where('employee_id', $employee->id)->delete();

            $employee->clearMediaCollection('avatars');
            $employee->paymentDetails()->delete();
            $employee->user->delete();
            $employee->delete();

            return RequestResponse::ok('Employee deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to delete employee: ' . $e->getMessage());
        }
    }

    public function view(Request $request)
    {
        try {
            $employee = Employee::with([
                'user',
                'business',
                'department',
                'location',
                'paymentDetails',
                'payrolls.payroll',
                'employeeAllowances.allowance',
                'employeeDeductions.deduction',
                'attendances',
                'leaveRequests',
                'loans',
                'overtimes',
                'familyMembers',
                'emergencyContacts',
                'documents',
                'advances',
                'academicDetails',
                'previousEmployment',
                'employmentDetails',
                'jobCategory',
                'spouse',
                'contractActions' => fn ($q) => $q->where('status', 'active')->latest('action_date'),
            ])->findOrFail($request->employee_id);

            $view = view('employees._view', compact('employee'))->render();
            return response()->json([
                'message' => 'Employee details loaded successfully.',
                'data' => $view
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error loading employee view: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load employee details: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function filter(Request $request)
    {
        Log::debug($request->all());

        $business_slug = session('active_business_slug');
        $business = Business::findBySlug($business_slug);

        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

        $validatedData = $request->validate([
            'locations' => 'array|nullable',
            'locations.*' => [
                function ($attribute, $value, $fail) use ($business_slug) {
                    if ($value !== 'all' && $value !== $business_slug && !Location::where('slug', $value)->exists()) {
                        $fail("The selected location '$value' is invalid.");
                    }
                },
            ],
            'departments' => 'array|nullable',
            'departments.*' => [
                function ($attribute, $value, $fail) {
                    if ($value !== 'all' && !Department::where('slug', $value)->exists()) {
                        $fail("The selected department '$value' is invalid.");
                    }
                },
            ],
            'job_categories' => 'array|nullable',
            'job_categories.*' => [
                function ($attribute, $value, $fail) {
                    if ($value !== 'all' && !JobCategory::where('slug', $value)->exists()) {
                        $fail("The selected job category '$value' is invalid.");
                    }
                },
            ],
            'employment_terms' => 'array|nullable',
            'employment_terms.*' => [
                function ($attribute, $value, $fail) {
                    $validTerms = ['permanent', 'contract', 'temporary', 'fulltime', 'internship', 'consultant', 'locum'];
                    if ($value !== 'all' && !in_array($value, $validTerms)) {
                        $fail("The selected employment term '$value' is invalid.");
                    }
                },
            ],
        ]);

        $query = $business->employees()->with(['user:id,name', 'department:id,name']);

        if (empty($validatedData['locations']) || in_array($business_slug, $validatedData['locations'])) {
            $query->whereNull('location_id');
        }

        if (!empty($validatedData)) {
            if (!empty($validatedData['departments']) && !in_array('all', $validatedData['departments'])) {
                $query->whereHas('department', function ($q) use ($validatedData) {
                    $q->whereIn('slug', $validatedData['departments']);
                });
            }

            if (!empty($validatedData['job_categories']) && !in_array('all', $validatedData['job_categories'])) {
                $jobCategoryIds = JobCategory::whereIn('slug', $validatedData['job_categories'])->pluck('id');
                $query->whereHas('employmentDetails', function ($q) use ($jobCategoryIds) {
                    $q->whereIn('job_category_id', $jobCategoryIds);
                });
            }

            if (!empty($validatedData['locations']) && !in_array('all', $validatedData['locations']) && !in_array($business_slug, $validatedData['locations'])) {
                $locationIds = Location::whereIn('slug', $validatedData['locations'])->pluck('id');
                $query->whereHas('location', function ($q) use ($locationIds) {
                    $q->whereIn('id', $locationIds);
                });
            }

            if (!empty($validatedData['employment_terms']) && !in_array('all', $validatedData['employment_terms'])) {
                $query->whereHas('employmentDetails', function ($q) use ($validatedData) {
                    $q->whereIn('employment_term', $validatedData['employment_terms']);
                });
            }
        }

        $filteredEmployees = $query->get();

        $employeesData = $filteredEmployees->map(function ($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->user->name ?? 'N/A',
                'department' => $employee->department->name ?? 'N/A',
                'checked' => true,
            ];
        });

        return RequestResponse::ok('Filtered employees retrieved successfully.', $employeesData);
    }

    public function sendAlert(NotificationService $notificationService)
    {
        $user = User::find(1);
        $preferences = $notificationService->getUserNotificationPreferences($user);
        $channels = $notificationService->filterChannelsByUserPreferences($user, ['mail', 'database', 'slack']);

        $notificationService->sendNotification(
            $user,
            SystemAlertNotification::class,
            ['System maintenance scheduled.', ['details' => 'Server will be down for 2 hours.']],
            [],
            $channels
        );

        return response()->json(['message' => 'Notification sent.']);
    }

    public function uploadDocument(Request $request, $employeeId)
    {
        $request->validate([
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'document_types.*' => 'nullable|string|max:255',
        ]);

        try {
            $employee = Employee::findOrFail($employeeId);
            $documents = $request->file('documents');
            $documentTypes = $request->input('document_types');

            DB::beginTransaction();

            foreach ($documents as $index => $file) {
                $document = EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'document_type' => $documentTypes[$index],
                ]);

                // Store the file using Spatie Media Library
                $document->addMedia($file)->toMediaCollection('employeeDocuments');
            }

            DB::commit();

            return RequestResponse::ok('Documents uploaded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to upload documents: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to upload documents: ' . $e->getMessage());
        }
    }

    public function deleteDocument(Request $request, $employeeId, $documentId)
    {
        try {
            $document = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($documentId);

            // Delete associated media
            $document->clearMediaCollection('employeeDocuments');
            $document->delete();

            return RequestResponse::ok('Document deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete document: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to delete document: ' . $e->getMessage());
        }
    }

    public function downloadDocument(Request $request, $employeeId, $documentId)
    {
        try {
            $document = EmployeeDocument::where('employee_id', $employeeId)->findOrFail($documentId);
            $media = $document->getFirstMedia('employeeDocuments');

            if (!$media) {
                return RequestResponse::badRequest('Document file not found.');
            }

            return response()->file($media->getPath(), [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve document: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to retrieve document: ' . $e->getMessage());
        }
    }

    public function setUserPreferences(NotificationService $notificationService)
    {
        $user = auth()->user();
        $notificationService->setUserNotificationPreferences($user, ['email' => false, 'database' => true]);
        return response()->json(['message' => 'User preferences updated.']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

        $file = $request->file('file');
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            Log::error('Business not found during import.', ['slug' => session('active_business_slug')]);
            return response()->json([
                'success' => false,
                'message' => 'Business not found.',
                'successful' => 0,
                'errors' => ['Business not found.'],
            ], 400);
        }

        $errors = [];
        $successful = 0;

        // Same per-business scoping as store()/update() - employee_code/
        // national_id/tax_no/nhif_no/nssf_no/passport_no only need to be
        // unique WITHIN this business, never across all of them.
        $uniquePerBusiness = fn (string $column) => \Illuminate\Validation\Rule::unique('employees', $column)
            ->where(fn ($q) => $q->where('business_id', $business->id));

        $departments = $business->departments()->pluck('id', 'name')->toArray();
        $locations = $business->locations()->pluck('id', 'name')->toArray();
        $locations[$business->company_name] = null; // Add main business as a location (nullable ID)
        $jobCategories = $business->job_categories()->pluck('id', 'name')->toArray();

        try {
            DB::beginTransaction();

            Log::info('Starting import process.', ['file' => $file->getClientOriginalName()]);
            $records = Excel::toArray(new EmployeesImport, $file)[0];
            Log::info('Excel parsed.', ['row_count' => count($records)]);

            if (empty($records)) {
                $errorMsg = 'The uploaded XLSX file is empty or invalid.';
                Log::warning($errorMsg, ['file' => $file->getClientOriginalName()]);
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'successful' => 0,
                    'errors' => ['No data found in the file.'],
                ], 400);
            }

            $headers = array_shift($records);
            Log::info('Headers extracted.', ['headers' => $headers]);
            $records = array_map(function ($row) use ($headers) {
                return array_combine($headers, array_map('trim', $row));
            }, $records);
            Log::info('Rows mapped.', ['record_count' => count($records)]);

            foreach ($records as $index => $row) {
                try {
                    $validator = Validator::make($row, [
                        'first_name' => 'required|string|max:255',
                        'last_name' => 'required|string|max:255',
                        // No unique:users,email - an email already belonging
                        // to a User is reused (new Employee row at this
                        // business) rather than rejected, same as store().
                        'email' => 'required|email|max:255',
                        'phone' => 'required|string|max:20',
                        'gender' => 'required|string|max:20',
                        'employee_code' => ['required', 'string', 'max:50', $uniquePerBusiness('employee_code')],
                        'department' => 'nullable|string|exists:departments,name',
                        'job_category' => 'nullable|string|exists:job_categories,name',
                        'location' => 'nullable|string|in:' . implode(',', array_keys($locations)),
                        'basic_salary' => 'required|numeric|min:0',
                        'currency' => 'required|string|size:3',
                        'payment_mode' => 'required|string|in:bank,cash,cheque,mpesa',
                        'account_name' => 'required|string|max:255',
                        'account_number' => 'required|string|unique:employee_payment_details,account_number|max:50',
                        'bank_name' => 'required|string|max:255',
                        'bank_code' => 'nullable|string|max:50',
                        'bank_branch' => 'nullable|string|max:255',
                        'bank_branch_code' => 'nullable|string|max:50',
                        'national_id' => ['nullable', 'string', 'max:255', $uniquePerBusiness('national_id')],
                        'tax_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('tax_no')],
                        'date_of_birth' => 'nullable|date|before:today',
                        'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
                        'nhif_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nhif_no')],
                        'nssf_no' => ['nullable', 'string', 'max:20', $uniquePerBusiness('nssf_no')],
                        'passport_no' => ['nullable', 'string', 'max:255', $uniquePerBusiness('passport_no')],
                        'passport_issue_date' => 'nullable|date|before:today',
                        'passport_expiry_date' => 'nullable|date|after:passport_issue_date',
                        'place_of_birth' => 'nullable|string|max:255',
                        'place_of_issue' => 'nullable|string|max:255',
                        'address' => 'nullable|string|max:255',
                        'permanent_address' => 'nullable|string|max:255',
                        'alternate_phone' => 'nullable|string|max:20',
                        'blood_group' => 'nullable|string|max:255',
                        'is_exempt_from_payroll' => 'nullable|boolean',
                        'resident_status' => 'nullable|string|max:255',
                        'kra_employee_status' => 'nullable|in:Primary Employee,Secondary Employee',
                        'employment_date' => 'nullable|date|before_or_equal:today',
                        'employment_term' => 'required|in:permanent,contract,temporary,internship,consultant',
                        'probation_end_date' => 'nullable|date|after:employment_date',
                        'contract_end_date' => 'nullable|date|after:employment_date',
                        'retirement_date' => 'nullable|date|after:employment_date',
                        'job_description' => 'nullable|string|max:1000',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                        continue;
                    }

                    $departmentId = !empty($row['department']) ? ($departments[$row['department']] ?? null) : null;
                    $jobCategoryId = !empty($row['job_category']) ? ($jobCategories[$row['job_category']] ?? null) : null;
                    $locationId = !empty($row['location']) && isset($locations[$row['location']]) ? $locations[$row['location']] : null;

                    if (!empty($row['department']) && !$departmentId) {
                        $errors[] = "Row " . ($index + 2) . ": Department '{$row['department']}' not found.";
                        continue;
                    }
                    if (!empty($row['job_category']) && !$jobCategoryId) {
                        $errors[] = "Row " . ($index + 2) . ": Job Category '{$row['job_category']}' not found.";
                        continue;
                    }
                    if (!empty($row['location']) && !array_key_exists($row['location'], $locations)) {
                        $errors[] = "Row " . ($index + 2) . ": Location '{$row['location']}' not found.";
                        continue;
                    }

                    // Same existing-user reuse as store(): an email already
                    // belonging to a User is added as an employee at THIS
                    // business rather than rejected, unless they're already
                    // an employee here too.
                    $existingUser = User::where('email', $row['email'])->first();
                    if ($existingUser && Employee::where('user_id', $existingUser->id)->where('business_id', $business->id)->exists()) {
                        $errors[] = "Row " . ($index + 2) . ": {$row['email']} already belongs to an employee at this business.";
                        continue;
                    }
                    $isNewUser = !$existingUser;

                    $user = $existingUser ?: User::create([
                        'name' => trim("{$row['first_name']} {$row['last_name']}"),
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'password' => null,
                    ]);

                    if ($isNewUser) {
                        $token = Password::createToken($user);
                        $user->sendPasswordResetNotification($token);
                        $user->notify(new WelcomeEmployeeNotification($user, $token));
                    } else {
                        $user->notify(new WelcomeEmployeeNotification($user, null, $business->company_name));
                    }

                    // See EmployeeController::store() for why this isn't
                    // filtered by business_id - 'business-employee' is a
                    // global role, not per-business.
                    $role = Role::firstOrCreate(['name' => 'business-employee', 'guard_name' => 'web']);
                    $user->assignRole($role);

                    $employee = $business->employees()->create([
                        'user_id' => $user->id,
                        'employee_code' => $row['employee_code'],
                        'department_id' => $departmentId,
                        'job_category_id' => $jobCategoryId,
                        'location_id' => $locationId,
                        'national_id' => $row['national_id'] ?? null,
                        'marital_status' => $row['marital_status'] ?? null,
                        'nhif_no' => $row['nhif_no'] ?? null,
                        'nssf_no' => $row['nssf_no'] ?? null,
                        'tax_no' => $row['tax_no'] ?? null,
                        'date_of_birth' => $row['date_of_birth'] ?? null,
                        'gender' => $row['gender'],
                        'phone' => $row['phone'],
                        'alternate_phone' => $row['alternate_phone'] ?? null,
                        'passport_no' => $row['passport_no'] ?? null,
                        'passport_issue_date' => $row['passport_issue_date'] ?? null,
                        'passport_expiry_date' => $row['passport_expiry_date'] ?? null,
                        'place_of_birth' => $row['place_of_birth'] ?? null,
                        'place_of_issue' => $row['place_of_issue'] ?? null,
                        'address' => $row['address'] ?? null,
                        'permanent_address' => $row['permanent_address'] ?? null,
                        'blood_group' => $row['blood_group'] ?? null,
                        'is_exempt_from_payroll' => $row['is_exempt_from_payroll'] ?? false,
                        'resident_status' => $row['resident_status'] ?? null,
                        'kra_employee_status' => $row['kra_employee_status'] ?? null,
                    ]);

                    $employee->employmentDetails()->create([
                        'job_category_id' => $jobCategoryId,
                        'department_id' => $departmentId,
                        'employment_date' => $row['employment_date'] ?? now(),
                        'employment_term' => $row['employment_term'],
                        'probation_end_date' => $row['probation_end_date'] ?? null,
                        'contract_end_date' => $row['contract_end_date'] ?? null,
                        'retirement_date' => $row['retirement_date'] ?? null,
                        'job_description' => $row['job_description'] ?? null,
                    ]);

                    $employee->paymentDetails()->create([
                        'basic_salary' => $row['basic_salary'],
                        'currency' => $row['currency'],
                        'payment_mode' => $row['payment_mode'],
                        'account_name' => $row['account_name'],
                        'account_number' => $row['account_number'],
                        'bank_name' => $row['bank_name'],
                        'bank_code' => $row['bank_code'] ?? null,
                        'bank_branch' => $row['bank_branch'] ?? null,
                        'bank_branch_code' => $row['bank_branch_code'] ?? null,
                    ]);

                    $successful++; // Ensure this is incremented after all creations
                    Log::info('Employee imported successfully.', ['row' => $index + 2, 'employee_id' => $employee->id]);
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    Log::error('Error importing row.', ['row' => $index + 2, 'error' => $e->getMessage()]);
                }
            }

            DB::commit();
            Log::info('Import process completed.', ['successful' => $successful, 'errors' => count($errors)]);

            $responseData = [
                'successful' => $successful,
                'errors' => $errors,
            ];

            if ($successful > 0 && count($errors) === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'All employees imported successfully.',
                    'successful' => $successful,
                    'errors' => [],
                ], 200);
            }

            if ($successful > 0 && count($errors) > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Some employees were imported, but there were errors.',
                    'successful' => $successful,
                    'errors' => $errors,
                ], 200);
            }

            if ($successful === 0 && count($errors) > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed. No employees were added.',
                    'successful' => 0,
                    'errors' => $errors,
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'No employees were added. Please check the file format or data.',
                'successful' => 0,
                'errors' => [],
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to import employees.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to import employees.',
                'successful' => 0,
                'errors' => [$e->getMessage()],
            ], 400);
        }
    }
    public function downloadXlsxTemplate()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.');
        }

        $departments = $business->departments()->pluck('name')->toArray();
        $locations = $business->locations()->pluck('name')->toArray();
        $locations[] = $business->company_name; // Add main business as a location
        $jobCategories = $business->job_categories()->pluck('name')->toArray();

        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'employee_code',
            'department',
            'job_category',
            'location',
            'basic_salary',
            'currency',
            'payment_mode',
            'account_name',
            'account_number',
            'bank_name',
            'bank_code',
            'bank_branch',
            'bank_branch_code',
            'national_id',
            'tax_no',
            'date_of_birth',
            'marital_status',
            'nhif_no',
            'nssf_no',
            'passport_no',
            'passport_issue_date',
            'passport_expiry_date',
            'place_of_birth',
            'place_of_issue',
            'address',
            'permanent_address',
            'alternate_phone',
            'blood_group',
            'is_exempt_from_payroll',
            'resident_status',
            'kra_employee_status',
            'employment_date',
            'employment_term',
            'probation_end_date',
            'contract_end_date',
            'retirement_date',
            'job_description',
        ];

        $sampleData = [
            [
                'John',
                'Smith',
                'john.smith@example.com',
                '+254712345678',
                'male',
                'EMP001',
                $departments[0] ?? 'HR',
                $jobCategories[0] ?? 'Manager',
                $locations[0] ?? 'Nairobi',
                '50000.00',
                'KES',
                'bank',
                'John Smith',
                '1234567890',
                'Equity Bank',
                'EQ123',
                'Nairobi Main',
                '001',
                '12345678',
                'KRA12345',
                '1990-05-15',
                'single',
                'NHIF56789',
                'NSSF98765',
                'PP123456',
                '2020-01-01',
                '2030-01-01',
                'Nairobi',
                'Nairobi Passport Office',
                'P.O. Box 123, Nairobi',
                'P.O. Box 456, Nairobi',
                '+254723456789',
                'A+',
                '0', // False
                'Resident',
                'Primary Employee',
                '2023-01-01',
                'permanent',
                '', // Nullable
                '', // Nullable
                '', // Nullable
                'Manage HR operations',
            ]
        ];

        return Excel::download(new class($headers, $sampleData, $departments, $locations, $jobCategories) implements
            \Maatwebsite\Excel\Concerns\FromArray,
            \Maatwebsite\Excel\Concerns\WithValidation,
            \Maatwebsite\Excel\Concerns\WithEvents
        {
            private $headers;
            private $sampleData;
            private $departments;
            private $locations;
            private $jobCategories;

            public function __construct(array $headers, array $sampleData, array $departments, array $locations, array $jobCategories)
            {
                $this->headers = $headers;
                $this->sampleData = $sampleData;
                $this->departments = $departments;
                $this->locations = $locations;
                $this->jobCategories = $jobCategories;
            }

            public function array(): array
            {
                return [$this->headers, ...$this->sampleData];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();

                        // Style headers: bold and background color
                        $sheet->getStyle('A1:AR1')->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFCCCCCC'],
                            ],
                        ]);

                        // Set column widths for readability
                        $startColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString('A');
                        $endColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString('AR');
                        for ($colIndex = $startColIndex; $colIndex <= $endColIndex; $colIndex++) {
                            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                            $sheet->getColumnDimension($colLetter)->setWidth(15);
                        }
                        $sheet->getColumnDimension('C')->setWidth(25); // Email
                        $sheet->getColumnDimension('M')->setWidth(20); // Account Name
                        $sheet->getColumnDimension('V')->setWidth(25); // Permanent Address
                        $sheet->getColumnDimension('AR')->setWidth(25); // Job Description

                        // Define helper function to apply validation to a range
                        $applyValidation = function ($range, $options) use ($sheet) {
                            $validation = $sheet->getDataValidation($range);
                            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                            $validation->setAllowBlank($options['allowBlank'] ?? false);
                            $validation->setShowInputMessage(true);
                            $validation->setShowErrorMessage(true);
                            $validation->setShowDropDown(true);
                            $validation->setPromptTitle($options['promptTitle']);
                            $validation->setPrompt($options['prompt']);
                            $validation->setErrorTitle($options['errorTitle']);
                            $validation->setError($options['error']);
                            $validation->setFormula1($options['formula']);
                        };

                        // Apply validations to ranges (rows 2 to 1000)
                        $applyValidation('E2:E1000', [
                            'promptTitle' => 'Gender',
                            'prompt' => 'Select gender',
                            'errorTitle' => 'Invalid Gender',
                            'error' => 'Please select a valid gender from the list.',
                            'formula' => '"male,female"',
                            'allowBlank' => false,
                        ]);

                        $applyValidation('G2:G1000', [
                            'promptTitle' => 'Department',
                            'prompt' => 'Select a department (optional)',
                            'errorTitle' => 'Invalid Department',
                            'error' => 'Please select a valid department from the list.',
                            'formula' => '"' . implode(',', $this->departments) . '"',
                            'allowBlank' => true,
                        ]);

                        $applyValidation('H2:H1000', [
                            'promptTitle' => 'Job Category',
                            'prompt' => 'Select a job category (optional)',
                            'errorTitle' => 'Invalid Job Category',
                            'error' => 'Please select a valid job category from the list.',
                            'formula' => '"' . implode(',', $this->jobCategories) . '"',
                            'allowBlank' => true,
                        ]);

                        $applyValidation('I2:I1000', [
                            'promptTitle' => 'Location',
                            'prompt' => 'Select a location (optional)',
                            'errorTitle' => 'Invalid Location',
                            'error' => 'Please select a valid location from the list.',
                            'formula' => '"' . implode(',', $this->locations) . '"',
                            'allowBlank' => true,
                        ]);

                        $applyValidation('L2:L1000', [
                            'promptTitle' => 'Payment Mode',
                            'prompt' => 'Select a payment mode',
                            'errorTitle' => 'Invalid Payment Mode',
                            'error' => 'Please select a valid payment mode from the list.',
                            'formula' => '"bank,cash,cheque,mpesa"',
                            'allowBlank' => false,
                        ]);

                        $applyValidation('V2:V1000', [
                            'promptTitle' => 'Marital Status',
                            'prompt' => 'Select marital status',
                            'errorTitle' => 'Invalid Marital Status',
                            'error' => 'Please select a valid marital status from the list.',
                            'formula' => '"single,married,divorced,widowed"',
                            'allowBlank' => false,
                        ]);

                        $applyValidation('AE2:AE1000', [
                            'promptTitle' => 'Is Exempt From Payroll',
                            'prompt' => 'Select 1 for yes, 0 for no (optional)',
                            'errorTitle' => 'Invalid Value',
                            'error' => 'Please select 0 or 1.',
                            'formula' => '"0,1"',
                            'allowBlank' => true,
                        ]);

                        $applyValidation('AG2:AG1000', [
                            'promptTitle' => 'KRA Employee Status',
                            'prompt' => 'Select KRA employee status (optional)',
                            'errorTitle' => 'Invalid KRA Status',
                            'error' => 'Please select a valid KRA employee status from the list.',
                            'formula' => '"Primary Employee,Secondary Employee"',
                            'allowBlank' => true,
                        ]);

                        $applyValidation('AI2:AI1000', [
                            'promptTitle' => 'Employment Term',
                            'prompt' => 'Select employment term',
                            'errorTitle' => 'Invalid Employment Term',
                            'error' => 'Please select a valid employment term from the list.',
                            'formula' => '"permanent,contract,temporary,internship"',
                            'allowBlank' => false,
                        ]);
                    }
                ];
            }

            public function rules(): array
            {
                return [
                    'E' => 'required|in:male,female', // Column E (gender)
                    'G' => 'nullable|in:' . implode(',', $this->departments), // Column G (department)
                    'H' => 'nullable|in:' . implode(',', $this->jobCategories), // Column H (job_category)
                    'I' => 'nullable|in:' . implode(',', $this->locations), // Column I (location)
                    'L' => 'required|in:bank,cash,cheque,mpesa', // Column L (payment_mode)
                    'V' => 'required|in:single,married,divorced,widowed', // Column V (marital_status)
                    'AE' => 'nullable|in:0,1', // Column AE (is_exempt_from_payroll)
                    'AG' => 'nullable|in:Primary Employee,Secondary Employee', // Column AG (kra_employee_status)
                    'AI' => 'required|in:permanent,contract,temporary,internship', // Column AI (employment_term)
                ];
            }
        }, 'employees_template.xlsx');
    }

    public function export(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            // Eager-load all necessary relationships
            $query = Employee::where('business_id', $business->id)
                ->with(['user', 'department', 'location', 'paymentDetails', 'employmentDetails.jobCategory']);

            // Apply filters
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhere('employee_code', 'like', "%{$search}%");
                });
            }
            if ($department = $request->input('department')) {
                $query->where('department_id', $department);
            }
            if ($location = $request->input('location')) {
                $query->where('location_id', $location);
            }
            if ($jobCategory = $request->input('job_category')) {
                $query->whereHas('employmentDetails', function ($q) use ($jobCategory) {
                    $q->where('job_category_id', $jobCategory);
                });
            }

            $employees = $query->get();

            if ($employees->isEmpty()) {
                return RequestResponse::badRequest('No employees found with the applied filters.');
            }

            // Define headers for all fields from validation rules
            $headers = [
                'Name',
                'Employee Code',
                'Email',
                'Phone',
                'Alternate Phone',
                'Gender',
                'National ID',
                'Tax Number',
                'NHIF Number',
                'NSSF Number',
                'Passport Number',
                'Passport Issue Date',
                'Passport Expiry Date',
                'Place of Birth',
                'Place of Issue',
                'Date of Birth',
                'Marital Status',
                'Address',
                'Permanent Address',
                'Blood Group',
                'Resident Status',
                'KRA Employee Status',
                'Department',
                'Job Category',
                'Location',
                'Basic Salary',
                'Currency',
                'Payment Mode',
                'Account Name',
                'Account Number',
                'Bank Name',
                'Bank Code',
                'Bank Branch',
                'Bank Branch Code',
                'Employment Date',
                'Employment Term',
                'Probation End Date',
                'Contract End Date',
                'Retirement Date',
                'Job Description',
                'Is Exempt from Payroll',
            ];

            // Map employee data to include all fields
            $data = $employees->map(function ($employee) {
                return [
                    $employee->user->name ?? 'N/A',
                    $employee->employee_code ?? 'N/A',
                    $employee->user->email ?? 'N/A',
                    $employee->user->phone ?? 'N/A',
                    $employee->alternate_phone ?? 'N/A',
                    $employee->gender ?? 'N/A',
                    $employee->national_id ?? 'N/A',
                    $employee->tax_no ?? 'N/A',
                    $employee->nhif_no ?? 'N/A',
                    $employee->nssf_no ?? 'N/A',
                    $employee->passport_no ?? 'N/A',
                    $employee->passport_issue_date ?? 'N/A',
                    $employee->passport_expiry_date ?? 'N/A',
                    $employee->place_of_birth ?? 'N/A',
                    $employee->place_of_issue ?? 'N/A',
                    $employee->date_of_birth ?? 'N/A',
                    $employee->marital_status ?? 'N/A',
                    $employee->address ?? 'N/A',
                    $employee->permanent_address ?? 'N/A',
                    $employee->blood_group ?? 'N/A',
                    $employee->resident_status ?? 'N/A',
                    $employee->kra_employee_status ?? 'N/A',
                    $employee->department->name ?? 'N/A',
                    optional($employee->employmentDetails)->jobCategory->name ?? 'N/A',
                    $employee->location ? $employee->location->name : $employee->business->company_name,
                    number_format((float) (optional($employee->paymentDetails)->basic_salary ?? 0), 2),
                    optional($employee->paymentDetails)->currency ?? 'N/A',
                    optional($employee->paymentDetails)->payment_mode ?? 'N/A',
                    optional($employee->paymentDetails)->account_name ?? 'N/A',
                    optional($employee->paymentDetails)->account_number ?? 'N/A',
                    optional($employee->paymentDetails)->bank_name ?? 'N/A',
                    optional($employee->paymentDetails)->bank_code ?? 'N/A',
                    optional($employee->paymentDetails)->bank_branch ?? 'N/A',
                    optional($employee->paymentDetails)->bank_branch_code ?? 'N/A',
                    optional($employee->employmentDetails)->employment_date ?? 'N/A',
                    optional($employee->employmentDetails)->employment_term ?? 'N/A',
                    optional($employee->employmentDetails)->probation_end_date ?? 'N/A',
                    optional($employee->employmentDetails)->contract_end_date ?? 'N/A',
                    optional($employee->employmentDetails)->retirement_date ?? 'N/A',
                    optional($employee->employmentDetails)->job_description ?? 'N/A',
                    optional($employee->employmentDetails)->is_exempt_from_payroll ? 'Yes' : 'No',
                ];
            })->toArray();

            return Excel::download(new class($headers, $data) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings,
                \Maatwebsite\Excel\Concerns\WithEvents
            {
                private $headers;
                private $data;

                public function __construct(array $headers, array $data)
                {
                    $this->headers = $headers;
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return $this->headers;
                }

                public function registerEvents(): array
                {
                    return [
                        AfterSheet::class => function (AfterSheet $event) {
                            $sheet = $event->sheet->getDelegate();
                            $highestColumn = $event->sheet->getHighestColumn();
                            $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFCCCCCC'],
                                ],
                            ]);

                              $endColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
                            for ($colIndex = 1; $colIndex <= $endColIndex; $colIndex++) {
                                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
                                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                            }
                        }
                    ];
                }
            }, 'employees_export_' . now()->format('Ymd_His') . '.xlsx');
        } catch (\Exception $e) {
            \Log::error('Failed to export employees: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to export employees: ' . $e->getMessage());
        }
    }

    // public function export(Request $request)
    // {
    //     try {
    //         $business = Business::findBySlug(session('active_business_slug'));
    //         if (!$business) {
    //             return RequestResponse::badRequest('Business not found.');
    //         }

    //         $query = Employee::where('business_id', $business->id)
    //             ->with(['user', 'department', 'location', 'paymentDetails', 'employmentDetails.jobCategory']);

    //         if ($search = $request->input('search')) {
    //             $query->where(function ($q) use ($search) {
    //                 $q->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
    //                     ->orWhere('employee_code', 'like', "%{$search}%");
    //             });
    //         }
    //         if ($department = $request->input('department')) {
    //             $query->where('department_id', $department);
    //         }
    //         if ($location = $request->input('location')) {
    //             $query->where('location_id', $location);
    //         }
    //         if ($jobCategory = $request->input('job_category')) {
    //             $query->whereHas('employmentDetails', function ($q) use ($jobCategory) {
    //                 $q->where('job_category_id', $jobCategory);
    //             });
    //         }

    //         $employees = $query->get();

    //         if ($employees->isEmpty()) {
    //             return RequestResponse::badRequest('No employees found with the applied filters.');
    //         }

    //         $headers = [
    //             'Name',
    //             'Employee Code',
    //             'Email',
    //             'Phone',
    //             'Gender',
    //             'National ID',
    //             'Date of Birth',
    //             'Marital Status',
    //             'Department',
    //             'Job Category',
    //             'Location',
    //             'Basic Salary',
    //             'Currency',
    //             'Payment Mode',
    //         ];

    //         $data = $employees->map(function ($employee) {
    //             return [
    //                 $employee->user->name ?? 'N/A',
    //                 $employee->employee_code ?? 'N/A',
    //                 $employee->user->email ?? 'N/A',
    //                 $employee->user->phone ?? 'N/A',
    //                 $employee->gender ?? 'N/A',
    //                 $employee->national_id ?? 'N/A',
    //                 $employee->date_of_birth ?? 'N/A',
    //                 $employee->marital_status ?? 'N/A',
    //                 $employee->department->name ?? 'N/A',
    //                 optional($employee->employmentDetails)->jobCategory->name ?? 'N/A',
    //                 $employee->location ? $employee->location->name : $employee->business->company_name,
    //                 number_format((float) (optional($employee->paymentDetails)->basic_salary ?? 0), 2),
    //                 optional($employee->paymentDetails)->currency ?? 'N/A',
    //                 optional($employee->paymentDetails)->payment_mode ?? 'N/A',
    //             ];
    //         })->toArray();

    //         return Excel::download(new class($headers, $data) implements
    //             \Maatwebsite\Excel\Concerns\FromArray,
    //             \Maatwebsite\Excel\Concerns\WithHeadings,
    //             \Maatwebsite\Excel\Concerns\WithEvents
    //         {
    //             private $headers;
    //             private $data;

    //             public function __construct(array $headers, array $data)
    //             {
    //                 $this->headers = $headers;
    //                 $this->data = $data;
    //             }

    //             public function array(): array
    //             {
    //                 return $this->data; // Only return data, not headers
    //             }

    //             public function headings(): array
    //             {
    //                 return $this->headers;
    //             }

    //             public function registerEvents(): array
    //             {
    //                 return [
    //                     AfterSheet::class => function (AfterSheet $event) {
    //                         $sheet = $event->sheet->getDelegate();
    //                         $sheet->getStyle('A1:N1')->applyFromArray([
    //                             'font' => ['bold' => true],
    //                             'fill' => [
    //                                 'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    //                                 'startColor' => ['argb' => 'FFCCCCCC'],
    //                             ],
    //                         ]);

    //                         foreach (range('A', 'N') as $col) {
    //                             $sheet->getColumnDimension($col)->setAutoSize(true);
    //                         }
    //                     }
    //                 ];
    //             }
    //         }, 'employees_export_' . now()->format('Ymd_His') . '.xlsx');
    //     } catch (\Exception $e) {
    //         Log::error('Failed to export employees: ' . $e->getMessage());
    //         return RequestResponse::badRequest('Failed to export employees: ' . $e->getMessage());
    //     }
    // }

    public function contracts(Request $request)
    {
        $page = 'Information Management';
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->back()->with('error', 'Business not found.');
        }

        // Employees nearing contract or license expiry (within 30 days)
        $employees = Employee::where('business_id', $business->id)
            ->whereHas('employmentDetails', function ($query) {
                $query->where(function ($q) {
                    $q->where('employment_term', 'contract')
                        ->whereNotNull('contract_end_date')
                        ->where('contract_end_date', '<=', now()->addDays(30))
                        ->where('contract_end_date', '>=', now())
                        ->where('status', '!=', 'terminated');
                })->orWhere(function ($q) {
                    $q->whereNotNull('license_expiry_date')
                        ->where('license_expiry_date', '<=', now()->addDays(30))
                        ->where('license_expiry_date', '>=', now())
                        ->where('status', '!=', 'terminated');
                });
            })
            ->with([
                'user:id,name',
                'employmentDetails:employee_id,contract_end_date,license_expiry_date,license_reg_number'
            ])
            ->get();

        // Paginated employees for termination section
        $terminationEmployees = Employee::where('business_id', $business->id)
            ->whereHas('employmentDetails', function ($query) {
                $query->where('status', '!=', 'terminated'); // Exclude already terminated
            })
            ->with([
                'user:id,name',
                'employmentDetails:employee_id,status,employment_term'
            ])
            ->select('id', 'user_id')
            ->paginate(25);

        $contractActions = EmployeeContractAction::where('business_id', $business->id)
            ->with(['employee.user', 'issuedBy'])
            ->get();

        return view('employees.contracts.index', compact('employees', 'terminationEmployees', 'contractActions', 'business', 'page'));
    }

    public function fetchContracts(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $query = EmployeeContractAction::where('business_id', $business->id)
                ->with(['employee.user', 'issuedBy']);

            $contractActions = $query->paginate(10);

            $data = $contractActions->map(function ($action) {
                try {
                    return [
                        'id' => $action->id,
                        'employee' => optional($action->employee)->user->name ?? 'N/A',
                        'action_type' => ucfirst($action->action_type),
                        'reason' => $action->reason,
                        'action_date' => $action->action_date->format('M d, Y'),
                        'status' => ucfirst($action->status),
                        'issued_by' => optional($action->issuedBy)->name ?? 'N/A',
                        'actions' => '<div class="btn-group">' .
                            (in_array($action->action_type, ['termination', 'suspension'], true) ?
                                '<button class="btn btn-sm btn-outline-warning" onclick="editContractAction(' . $action->id . ')"><i class="fa fa-edit"></i> Edit</button>' .
                                '<button class="btn btn-sm btn-outline-danger" onclick="deleteContractAction(' . $action->id . ')"><i class="fa fa-trash"></i> Delete</button>'
                                : '') .
                            '</div>'
                    ];
                } catch (\Exception $e) {
                    \Log::error('Error mapping contract action: ' . $e->getMessage(), [
                        'action_id' => $action->id,
                        'employee_id' => $action->employee_id,
                        'issued_by_id' => $action->issued_by_id,
                    ]);
                    return null;
                }
            })->filter();

            $html = view('employees.contracts._cards', ['contractActions' => $contractActions->items()])->render();

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => EmployeeContractAction::where('business_id', $business->id)->count(),
                'recordsFiltered' => $contractActions->total(),
                'data' => $data->toArray(),
                'html' => $html,
                'count' => $contractActions->total(),
                'message' => 'Contract actions fetched successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch contract actions: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function storeContractAction(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required_without:employee_id|array',
            'employee_ids.*' => 'exists:employees,id',
            'employee_id' => 'required_without:employee_ids|exists:employees,id',
            'action_type' => 'required|in:termination,reminder,suspension',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'action_date' => 'required|date',
            'reminder_type' => 'required_if:action_type,reminder|in:contract,license',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            $employeeIds = $validated['employee_ids'] ?? [$validated['employee_id']];
            $processedCount = 0;

            foreach ($employeeIds as $employeeId) {
                $employee = Employee::with('user', 'employmentDetails')->find($employeeId);
                if (!$employee || !$employee->user) {
                    \Log::warning('Employee or user not found for termination.', ['employee_id' => $employeeId]);
                    continue;
                }

                $targetStatus = $validated['action_type'] === 'termination' ? 'terminated' : 'suspended';
                if (
                    in_array($validated['action_type'], ['termination', 'suspension'], true) &&
                    $employee->employmentDetails &&
                    $employee->employmentDetails->status === $targetStatus
                ) {
                    \Log::warning('Attempted to ' . $validated['action_type'] . ' an employee already in that status.', ['employee_id' => $employeeId]);
                    continue;
                }

                $contractAction = EmployeeContractAction::create([
                    'business_id' => $business->id,
                    'employee_id' => $employeeId,
                    'action_type' => $validated['action_type'],
                    'reason' => $validated['reason'],
                    'description' => $validated['description'],
                    'action_date' => $validated['action_date'],
                    'status' => in_array($validated['action_type'], ['termination', 'suspension'], true) ? 'active' : 'sent',
                    'issued_by_id' => auth()->id(),
                ]);

                if ($validated['action_type'] === 'termination') {
                    $employee->employmentDetails()->update(['status' => 'terminated']);
                    $employee->update(['is_exempt_from_payroll' => true]);

                    try {
                        $pdfContent = \Pdf::loadView('employees.termination_letter', [
                            'employee' => $employee,
                            'business' => $business,
                            'reason' => $validated['reason'],
                            'description' => $validated['description'],
                            'action_date' => \Carbon\Carbon::parse($validated['action_date']),
                        ])->output();
                    } catch (\Exception $e) {
                        \Log::error('Failed to generate termination letter PDF: ' . $e->getMessage(), [
                            'employee_id' => $employeeId,
                        ]);
                        continue;
                    }

                    $document = EmployeeDocument::create([
                        'employee_id' => $employeeId,
                        'document_type' => 'Termination Letter',
                    ]);

                    try {
                        $document
                            ->addMediaFromString($pdfContent)
                            ->usingFileName("termination_letter_{$employeeId}_" . time() . ".pdf")
                            ->usingName("Termination Letter")
                            ->toMediaCollection('employeeDocuments');
                    } catch (\Exception $e) {
                        \Log::error('Failed to store termination letter in media library: ' . $e->getMessage(), [
                            'employee_id' => $employeeId,
                        ]);
                    }

                    try {
                        Notification::send($employee->user, new TerminationNotification($contractAction, $pdfContent));
                        \Log::info('Termination action completed.', ['employee_id' => $employeeId]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send termination notification: ' . $e->getMessage(), [
                            'employee_id' => $employeeId,
                        ]);
                        continue;
                    }
                } elseif ($validated['action_type'] === 'suspension') {
                    $employee->employmentDetails()->update(['status' => 'suspended']);
                    $employee->update(['is_exempt_from_payroll' => true]);

                    try {
                        Notification::send($employee->user, new SuspensionNotification($contractAction));
                        \Log::info('Suspension action completed.', ['employee_id' => $employeeId]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send suspension notification: ' . $e->getMessage(), [
                            'employee_id' => $employeeId,
                        ]);
                        continue;
                    }
                } else {
                    try {
                        Notification::send($employee->user, new ContractReminderNotification($contractAction));
                        \Log::info('Reminder action completed.', ['employee_id' => $employeeId]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send reminder notification: ' . $e->getMessage(), [
                            'employee_id' => $employeeId,
                        ]);
                        continue;
                    }
                }

                $processedCount++;
            }

            if ($processedCount === 0) {
                return RequestResponse::badRequest('No valid employees processed.');
            }

            return RequestResponse::created("Contract action(s) recorded successfully.", ['processed' => $processedCount]);
        }, function ($e) {
            \Log::error('Failed to process contract action: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to store contract action: ' . $e->getMessage());
        });
    }

    public function editContractAction(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));
            $contractAction = $request->contract_action_id ? EmployeeContractAction::findOrFail($request->contract_action_id) : null;
            $employees = Employee::where('business_id', $business->id)->with('user')->get();

            $form = view('employees.contracts._form', compact('contractAction', 'employees', 'business'))->render();
            return response()->json([
                'message' => 'Form loaded successfully.',
                'data' => $form
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error loading contract action edit form: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load form: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function updateContractAction(Request $request, $id)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'action_date' => 'required|date',
            'status' => 'required|in:active,reversed',
        ]);

        try {
            $contractAction = EmployeeContractAction::findOrFail($id);
            if (!in_array($contractAction->action_type, ['termination', 'suspension'], true)) {
                return RequestResponse::badRequest('Only termination or suspension actions can be updated.');
            }

            DB::beginTransaction();

            $contractAction->update([
                'employee_id' => $validated['employee_id'],
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'action_date' => $validated['action_date'],
                'status' => $validated['status'],
            ]);

            $employee = Employee::findOrFail($validated['employee_id']);
            $inEffectStatus = $contractAction->action_type === 'termination' ? 'terminated' : 'suspended';
            $newStatus = $validated['status'] === 'active' ? $inEffectStatus : 'active';
            $isExempt = $validated['status'] === 'active';
            $employee->employmentDetails()->update(['status' => $newStatus]);
            $employee->update(['is_exempt_from_payroll' => $isExempt]);

            if ($validated['status'] === 'active') {
                $notification = $contractAction->action_type === 'termination'
                    ? new TerminationNotification($contractAction)
                    : new SuspensionNotification($contractAction);
                Notification::send($employee->user, $notification);
            } else {
                Notification::send($employee->user, new ReinstatementNotification($contractAction));
            }

            DB::commit();
            Log::info('Contract action updated.', ['action_id' => $id, 'employee_id' => $employee->id]);
            return RequestResponse::ok('Contract action updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update contract action: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to update contract action: ' . $e->getMessage());
        }
    }

    public function destroyContractAction(Request $request, $id)
    {
        try {
            $contractAction = EmployeeContractAction::findOrFail($id);
            if (!in_array($contractAction->action_type, ['termination', 'suspension'], true)) {
                return RequestResponse::badRequest('Only termination or suspension actions can be deleted.');
            }

            DB::beginTransaction();

            $employee = Employee::findOrFail($contractAction->employee_id);
            $employee->employmentDetails()->update(['status' => 'active']);
            $employee->update(['is_exempt_from_payroll' => false]);

            $contractAction->delete();

            DB::commit();
            Log::info('Contract action deleted.', ['action_id' => $id, 'employee_id' => $employee->id]);
            return RequestResponse::ok('Contract action deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete contract action: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to delete contract action: ' . $e->getMessage());
        }
    }

    public function sendContractReminder(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $business = Business::findBySlug(session('active_business_slug'));
            $employee = Employee::findOrFail($validated['employee_id']);

            $contractAction = EmployeeContractAction::create([
                'business_id' => $business->id,
                'employee_id' => $employee->id,
                'action_type' => 'reminder',
                'reason' => 'Contract expiry reminder',
                'description' => 'Your contract is nearing its end date.',
                'action_date' => now(),
                'status' => 'sent',
                'issued_by_id' => auth()->id(),
            ]);

            Notification::send($employee->user, new ContractReminderNotification($contractAction));

            return RequestResponse::ok('Reminder sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send contract reminder: ' . $e->getMessage());
            return RequestResponse::badRequest('Failed to send contract reminder: ' . $e->getMessage());
        }
    }
}
