<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use App\Models\Module;
use App\Models\JobPost;
use App\Models\Payroll;
use App\Models\Business;
use App\Models\Employee;
use App\Models\Industry;
use App\Models\Applicant;
use App\Models\Deduction;
use App\Models\Warning;
use App\Models\Department;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Models\PayrollFormula;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Models\Location;
use App\Models\Shift;
use App\Models\LeaveType;

class DashboardController extends Controller
{
    function index(Request $request)
    {
        $page = 'HR/Finance Dashboard';
        $business = Business::findBySlug(session('active_business_slug'));

        $business_employees = $business->employees->count();
        $on_leave_employees = $business->employees()->onLeave()->count();
        $locations = $business->locations()->count();
        $clients = $business->managedBusinesses()->count();
        $active_loans_count = $business->activeLoanCount();
        $pending_leave_requests = $business->leaveRequestsByStatus('pending')->count();
        $active_advances = $business->advancesByStatus('approved')->count();
        $employee_turnover = $business->employeesByStatus('resigned')->count();

        $cards = [
            [
                'title' => 'Business Employees',
                'icon' => 'fa-solid fa-users', // or fa-solid fa-user-tie
                'value' => number_format($business_employees),
                'trend_class' => 'text-success', // Green
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+10%',
                'time_period' => 'Year',
            ],
            [
                'title' => 'On Leave Employees',
                'icon' => 'fa-solid fa-user-slash', // or fa-solid fa-bed
                'value' => number_format($on_leave_employees),
                'trend_class' => 'text-danger', // Red
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+2.15%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Locations',
                'icon' => 'fa-solid fa-location-dot',
                'value' => number_format($locations),
                'trend_class' => 'text-success',
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+5.15%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Total Clients',
                'icon' => 'fa-solid fa-users-gear', // or fa-solid fa-user-group
                'value' => number_format($clients),
                'trend_class' => 'text-success',
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+2.15%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Active Loans',
                'icon' => 'fa-solid fa-hand-holding-dollar', // or fa-solid fa-money-bill-transfer
                'value' => number_format($active_loans_count),
                'trend_class' => 'text-danger',
                'trend_icon' => 'fa-solid fa-arrow-down',
                'trend_value' => '-5.5%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Active Advances',
                'icon' => 'fa-solid fa-arrow-trend-up', // or fa-solid fa-coins
                'value' => number_format($active_advances),
                'trend_class' => 'text-success',
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+2.15%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Leave Requests',
                'icon' => 'fa-solid fa-calendar-plus', // or fa-solid fa-file-pen
                'value' => number_format($pending_leave_requests),
                'trend_class' => 'text-success',
                'trend_icon' => 'fa-solid fa-arrow-up',
                'trend_value' => '+2.15%',
                'time_period' => 'Month',
            ],
            [
                'title' => 'Employee Turnover',
                'icon' => 'fa-solid fa-right-left', // or fa-solid fa-user-minus
                'value' => number_format($employee_turnover),
                'trend_class' => 'text-danger',
                'trend_icon' => 'fa-solid fa-arrow-down',
                'trend_value' => '-3%',
                'time_period' => 'Year',
            ],
        ];

        return view('business.index', compact('cards', 'page'));
    }

    function requestAccess(Request $request)
    {
        $page = 'Request Access';
        $description = 'Choose this option if there is another krest account you would like to manage. A request email will be sent to the email address you provide, allowing the account owner to grant access to the system.';
        return view('clients.access', compact('page', 'description'));
    }
    function grantAccess(Request $request)
    {
        $page = 'Grant Access';
        $description = 'Select this option if you wish to grant access to your krest account to another user. You will need to confirm their email address, and they will receive an email with access details.';
        $modules = Module::all();
        return view('clients.access', compact('page', 'description', 'modules'));
    }

    function locations(Request $request)
    {
        $page = 'Locations';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        return view('locations.index', compact('page', 'description'));
    }

    function departments(Request $request)
    {
        $page = 'Departments';
        $description = 'Manage and organize all departments within your business. View, create, and update departmental information to streamline operations.';
        return view('departments.index', compact('page', 'description'));
    }
    function jobCategories(Request $request)
    {
        $page = 'Job Categories';
        $description = '';
        return view('job-categories.index', compact('page', 'description'));
    }
    function paySchedule(Request $request)
    {
        $page = 'Pay Schedule';
        $description = '';
        return view('payroll.pay-schedule', compact('page', 'description'));
    }
    public function createEmployees(Request $request)
    {
        $page = 'Create New Employee';
        $description = 'Fill out the form below to create a new employee record.';
        $business = Business::findBySlug(session('active_business_slug'));
        $departments = $business->departments;
        $job_categories = $business->job_categories;
        $shifts = $business->shifts;
        $roles = Role::where('name', '!=', 'admin')->businessAssignable()->get();
        $locations = $business->locations;

        $employee = new Employee();

        return view('employees.create', compact('page', 'description', 'departments', 'job_categories', 'shifts', 'roles', 'locations', 'employee'));
    }

    public function editEmployees(Request $request, User $user)
    {
        $page = 'Update Employee - ' . $user->name;
        $business = Business::findBySlug(session('active_business_slug'));
        $description = 'Fill out the form below to update employee record.';
        $departments = auth()->user()->business->departments;
        $roles = Role::where('name', '!=', 'admin')->businessAssignable()->get();
        $locations = $business->locations;
        return view('employees.create', compact('page', 'description', 'departments', 'roles', 'locations'));
    }
    public function employeeDetails(Request $request, $business_slug, $user_id)
    {

        $user = User::find($user_id);
        $page = 'About - ' . $user->name;
        $description = '';
        return view('employees.show', compact('page', 'user'));
    }
    public function importEmployees(Request $request)
    {
        $page = 'Import Employees';
        $description = '';
        return view('employees.import', compact('page', 'description'));
    }
    public function employees(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new EmployeeController())->index($request);
    }
    public function shifts(Request $request)
    {
        $page = 'Shifts';
        $description = '';
        return view('shifts.index', compact('page', 'description'));
    }
    public function viewPayslip(Request $request, $id)
    {
        $businessSlug = $request->route('business');
        session(['active_business_slug' => $businessSlug]);
        return (new PayrollController())->viewPayslip($request, $id);
    }

    public function payroll(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollController())->index($request);
    }
    public function payrollAll(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollController())->all($request);
    }

    public function viewPayroll(Request $request, $id)
    {
        $businessSlug = $request->route('business');
        session(['active_business_slug' => $businessSlug]);
        return (new PayrollController())->viewPayroll($request, $request, $id);
    }

    public function downloadColumn(Request $request, $id, $column, $format)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollController())->downloadColumn($request, $id, $column, $format);
    }

    public function downloadPayroll(Request $request, $id, $format)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollController())->downloadPayroll($request, $id, $format);
    }

    public function printAllPayslips(Request $request, $id)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollController())->printAllPayslips($request, $id);
    }

    public function payslips(Request $request, string $business_slug, Payroll $payroll)
    {
        $page = 'Payslips';
        $description = '';
        return view('payroll.slips', compact('page', 'description', 'payroll'));
    }

    public function payrollImport(Request $request)
    {
        $page = 'Import Payrolls';
        $description = '';
        return view('payroll.import', compact('page', 'description'));
    }

    public function processPayrolls(Request $request)
    {
        $page = 'Process Payroll';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriods = $business->leavePeriods;
        $departments = $business->departments;
        $jobCategories = $business->job_categories;
        $locations = $business->locations;
        return view('payroll.process', compact('page', 'description', 'leavePeriods', 'departments', 'jobCategories', 'locations'));
    }

    public function payrollFormula(Request $request)
    {
        $page = 'Payroll Formula';
        $description = '';
        $deductions['nhif'] = PayrollFormula::where('slug', 'nhif')->with('brackets')->first();
        $deductions['nssf'] = PayrollFormula::where('slug', 'nssf')->first();
        $deductions['housing_levy'] = PayrollFormula::where('slug', 'housing_levy')->first();

        return view('payroll.formula', compact('page', 'description', 'deductions'));
    }

    public function createPayrollFormula(Request $request)
    {
        $page = 'Create Payroll Formula';
        $description = '';
        return view('payroll.create-formula', compact('page', 'description'));
    }

    public function downloads(Request $request)
    {
        $page = 'Downloads';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $locations = $business->locations;
        $payrolls = $business->payrolls()->latest()->get();
        return view('downloads.index', compact('page', 'description', 'payrolls', 'locations'));
    }

    public function payrollFormulas(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayrollFormulaController())->index($request);
    }

    public function warning(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new WarningController())->index($request);
    }

    public function payGrades(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new PayGradesController())->index($request);
    }

    public function deductions(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new DeductionController())->index($request);
    }

    public function reliefs(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new ReliefsController())->index($request);
    }

    public function employeeReliefs(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new EmployeeReliefsController())->index($request);
    }

    public function createWarning(Request $request)
    {
        $page = 'Create Warning';
        $description = '';
        return view('employees.warning-create', compact('page', 'description'));
    }

    public function payrollDeductions(Request $request)
    {
        $page = 'Payroll Deductions';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));

        // Fetch system-wide deductions
        $deductions = Deduction::all();
        $employees = $business->employees;
        $locations = $business->locations;
        return view('payroll.employee-deductions', compact('page', 'description', 'deductions', 'employees', 'locations'));
    }

    public function createDeductions(Request $request)
    {
        $page = 'Register Payroll Deductions';
        $description = '';
        $formulas = PayrollFormula::all();
        return view('deductions.create', compact('page', 'description', 'formulas'));
    }

    public function allowances(Request $request)
    {
        session(['active_business_slug' => $request->route('business')]);
        return (new AllowanceController())->index($request);
    }

    public function advances(Request $request)
    {
        $page = 'Advances';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        $locations = $business->locations;
        return view('advances.index', compact('page', 'description', 'employees'));
    }

    public function loans(Request $request)
    {
        $page = 'Loans';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        $locations = $business->locations;
        return view('loans.index', compact('page', 'description', 'employees'));
    }

    public function requestLeave(Request $request)
    {
        $page        = 'Leave Applications';
        $description = '';
        $business    = Business::findBySlug(session('active_business_slug'));

        $locations  = $business->locations;
        $leaveTypes = $business->leaveTypes;
        $employees  = $business->employees;
        $colleagues = $employees;

        return view('leave.create', compact('page', 'description', 'leaveTypes', 'employees', 'colleagues', 'locations'));
    }

    public function leaveApplication(Request $request, string $business_slug, string $reference_number)
    {
        $business = Business::findBySlug($business_slug);

        $leave = LeaveRequest::with(['employee.user', 'leaveType', 'approvedBy'])
            ->where('reference_number', $reference_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $page        = 'Leave - #' . $reference_number;
        $description = '';

        // Remove: $leave->statuses()->...
        // The show view renders status timeline from approval_history / approved_* / rejection_reason
        return view('leave.show', ['page' => $page, 'description' => $description, 'leave' => $leave, 'business' => $business]);
    }

    public function leaveApplications(Request $request)
    {
        $page        = 'Leave Applications';
        $description = '';

        $business = Business::findBySlug(session('active_business_slug'));

        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $locations = Location::where('business_id', $business->id)->orderBy('name')->get(['id', 'name', 'country']);
        $leaveTypes = LeaveType::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $leavePeriods = $business->leavePeriods()->orderByDesc('start_date')->get(['id', 'name']);

        return view('leave.index', compact('page', 'description', 'departments', 'locations', 'leaveTypes', 'leavePeriods'));
    }


    public function leaveTypes(Request $request)
    {
        $page = 'Leave Types';
        $description = '';
        $departments = Department::all();
        $job_categories = JobCategory::all();
        return view('leave.types', compact('page', 'description', 'departments', 'job_categories'));
    }

    public function leavePeriods(Request $request)
    {
        $page = 'Leave Periods';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriods = $business
            ? $business->leavePeriods()->orderByDesc('is_active')->orderByDesc('start_date')->get(['id', 'name', 'slug', 'is_active'])
            : collect();
        return view('leave.periods', compact('page', 'description', 'leavePeriods'));
    }

    public function leaveEntitlements(Request $request)
    {
    $page = 'Leave Entitlements';
    $description = '';
    $currentBusiness = $request->route('business');
    $business = Business::findBySlug(session('active_business_slug'));
    // Active period first (then most recent) so both the default tab and
    // the export modal's default selection land on the CURRENT period,
    // not whichever one happens to have the lowest id.
    $leave_periods = $business->leavePeriods()
        ->orderByDesc('is_active')
        ->orderByDesc('start_date')
        ->get();
    $initialLeavePeriodSlug = $leave_periods->first()->slug ?? null;
    $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
    $leaveTypes = LeaveType::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
    $employees = Employee::where('business_id', $business->id)->with('user')->get(['id', 'user_id', 'department_id']);
    return view('leave.entitlements', compact('page', 'description', 'leave_periods', 'initialLeavePeriodSlug', 'departments', 'leaveTypes', 'employees'));
    }



    public function setLeaveEntitlements(Request $request)
    {
    $page = 'Set Leave Entitlements';
    $business = Business::findBySlug(session('active_business_slug'));
    $description = '';
    $employees = $business->employees()->with('leaveEntitlements')->get();
    $leaveTypes = $business->leaveTypes;
    $leavePeriods = $business->leavePeriods()
        ->orderByDesc('is_active')
        ->orderByDesc('start_date')
        ->get();
    $departments = $business->departments;
    $jobCategories = $business->job_categories;
    $locations = $business->locations;
    return view('leave.entitlement', compact('page', 'description', 'employees', 'leaveTypes', 'leavePeriods', 'departments', 'jobCategories', 'locations'));
    }

    public function holidays(Business $business)
    {
        $page = 'Holidays';
        $locations = Location::where('business_id', $business->id)->orderBy('name')->get(['id', 'name', 'country']);
        return view('attendances.holidays_index', compact('page', 'business', 'locations'));
    }


    public function workSchedules(Business $business)
    {
        $page = "Work Schedules";
        return view('attendances.work_schedules_index', compact('page'))
            ->with('currentBusiness', $business);
    }

    // public function setLeaveEntitlements(Request $request)
    // {
    //     $page = 'Set Leave Entitlements';
    //     $business = Business::findBySlug(session('active_business_slug'));
    //     $description = '';
    //     $employees = $business->employees;
    //     $leaveTypes = $business->leaveTypes;
    //     $leavePeriods = $business->leavePeriods;
    //     $departments = $business->departments;
    //     $jobCategories = $business->job_categories;
    //     $locations = $business->locations;
    //     return view('leave.entitlement', compact('page', 'description', 'employees', 'leaveTypes', 'leavePeriods', 'departments', 'jobCategories', 'locations'));
    // }

    public function leaveSettings(Request $request)
    {
        $page = 'Leave Settings';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        return view('leave.settings', compact('page', 'description', 'business'));
    }

    /**
     * The business-wide weekly rest days (e.g. Saturday/Sunday) - shown on
     * the leave calendar and excluded from every leave-day calculation.
     */
    public function updateLeaveSettings(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Active business not found in session.');
        }

        $validated = $request->validate([
            'non_working_days' => 'nullable|array',
            'non_working_days.*' => 'integer|min:0|max:6',
        ]);

        $business->update(['non_working_days' => array_values($validated['non_working_days'] ?? [])]);

        return RequestResponse::ok('Leave settings updated successfully.', $business->fresh());
    }

    public function applicants(Request $request)
    {
        $page = 'Applicants';
        $description = 'Manage and review all job applicants.';
        return view('job-applications.applicants', compact('page', 'description'));
    }

    public function jobPosts(Request $request)
    {
        $page = 'Job Posts';
        $description = 'Manage job postings and vacancies.';
        return view('job-posts.index', compact('page', 'description'));
    }

    public function tasks(Request $request)
    {
        $page = 'Tasks';
        $description = 'Create and assign tasks to employees';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        return view('tasks.index', compact('page', 'description', 'employees', 'business'));
    }

    public function createTask(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        return view('tasks.create', compact('employees'));
    }

    public function showTask(Request $request, Task $task)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if ($task->business_id !== $business->id) {
            abort(403, 'Unauthorized');
        }
        return view('tasks.show', compact('task'));
    }

    public function progress(Request $request, Task $task)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if ($task->business_id !== $business->id) {
            abort(403, 'Unauthorized');
        }
        return view('tasks.progress', compact('task'));
    }

    public function createJobPosts(Request $request)
    {
        $page = 'Add Job Posts';
        $description = 'Manage job postings and vacancies.';
        return view('job-posts.create', compact('page', 'description'));
    }

    public function editJobPosts(Request $request, string $business_slug, string $job_post_slug)
    {
        $page = 'Edit Job Posts';
        $description = 'Manage job postings and vacancies.';
        $job_post = JobPost::findBySlug($job_post_slug);
        return view('job-posts.create', compact('page', 'description', 'job_post'));
    }

    public function jobApplicants(Request $request)
    {
        $page = 'Job Applicants';
        $description = 'Track job applications and their statuses.';
        return view('job-applications.applicants', compact('page', 'description'));
    }

    public function createJobApplicants(Request $request)
    {
        $page = 'Create Job Applicants';
        $description = 'Track job applications and their statuses.';
        return view('job-applications.create-applicant', compact('page', 'description'));
    }

    public function createJobApplications(Request $request)
    {
        $page = 'Create Job Applications';
        $description = 'Track job applications and their statuses.';
        $applicants = Applicant::with('user')->get();
        $business = Business::findBySlug(session('active_business_slug'));
        $job_posts = $business->jobPosts;
        return view('job-applications.create', compact('page', 'description', 'applicants', 'job_posts'));
    }

    public function jobApplications(Request $request)
    {
        $page = 'Job Applications';
        $description = 'Track job applications and their statuses.';
        return view('job-applications.index', compact('page', 'description'));
    }

    public function interviews(Request $request)
    {
        $page = 'Interview Scheduling';
        $description = 'Schedule and manage job interviews.';
        return view('interviews.index', compact('page', 'description'));
    }

    public function recruitmentReports(Request $request)
    {
        $page = 'Recruitment Reports';
        $description = 'Analyze recruitment trends and performance.';
        return view('job-applications.reports', compact('page', 'description'));
    }

    public function attendances(Request $request)
    {
        $page = 'Manage Attendances';
        $description = '';
        return view('attendances.index', compact('page', 'description'));
    }

    public function monthlyAttendances(Request $request)
    {
        $page = 'Manage Attendances';
        $description = '';
        return view('attendances.monthly', compact('page', 'description'));
    }

    public function clockIn(Request $request)
    {
        $page = 'Clock In';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        return view('attendances.clockin', compact('page', 'description', 'employees'));
    }

    public function clockOut(Request $request)
    {
        $page = 'Clock Out';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        return view('attendances.clockout', compact('page', 'description', 'employees'));
    }

    public function overtime(Request $request)
    {
        $page = 'Overtime';
        $description = '';
        $business = Business::findBySlug(session('active_business_slug'));
        $employees = $business->employees;
        return view('attendances.overtime', compact('page', 'description', 'employees'));
    }

    public function kpis(Request $request)
    {
        $page = 'KPIs';
        $description = '';
        return view('kpis.index', compact('page', 'description'));
    }

    public function createKpis(Request $request)
    {
        $page = 'Create KPIs';
        $description = '';
        return view('kpis.create', compact('page', 'description'));
    }

public function roster(Request $request)
{
    $page = 'Roster';
    $description = 'Manage your staff rota and attendance schedule.';

    $employees = Employee::with('user')->get(); // Load related user name for each employee
    $departments = Department::all();
    $jobCategories = JobCategory::all(); // ✅ Include this
    $locations = Location::all(); // ✅ Include this
    $shifts = Shift::all(); // ✅ Include this
    $leaveTypes = LeaveType::all(); // ✅ Include this

    return view('roster.index', compact(
        'page',
        'description',
        'employees',
        'departments',
        'jobCategories',
        'locations',
        'shifts',
        'leaveTypes'
    ));
}


    public function contracts()
    {
        return app(EmployeeController::class)->contracts(request());
    }
}
