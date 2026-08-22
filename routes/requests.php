<?php
ini_set('memory_limit', '256M');


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TrendController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\ReliefsController;
use App\Http\Controllers\AdvanceController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessSwitchController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\AllowanceController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\DeductionController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\LeavePeriodController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeListController;
use App\Http\Controllers\PayrollFormulaController;
use App\Http\Controllers\LeaveEntitlementController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\KPIsController;
use App\Http\Controllers\WarningController;
use App\Http\Controllers\DisciplinaryStageTypeController;
use App\Http\Controllers\DisciplinaryInvestigationController;
use App\Http\Controllers\DisciplinaryMinutesController;
use App\Http\Controllers\PayGradesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\EmployeeReliefsController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\RosterController;

Route::post('/business/{businessSlug}/generate-token', [BusinessController::class, 'generateApiToken'])
    ->middleware('auth')
    ->name('api.business.generate-token');

Route::middleware(['auth'])->group(function () {

    Route::name('businesses.')->prefix('businesses')->group(function () {
        // business-admin only: closes a self-elevation gap (this endpoint
        // used to be reachable, unguarded, by any authenticated user, who
        // would then be unconditionally granted business-admin by
        // BusinessController::store()). Real first-time signups already
        // hold business-admin from RegisteredUserController::store()
        // before they ever reach the setup-business form that posts here.
        Route::middleware(['role:business-admin'])->post('store', [BusinessController::class, 'store'])->name('store');
        Route::post('fetch', [BusinessController::class, 'fetch'])->name('fetch');
        Route::post('destroy', [BusinessController::class, 'destroy'])->name('destroy');
        Route::post('update', [BusinessController::class, 'update'])->name('update');
        Route::post('modules/store', [BusinessController::class, 'saveModules'])->name('modules.store');
        Route::post('/{business}/switch-back', [BusinessController::class, 'switchBackToAdmin'])->name('business.switch-back');
        // Peer "Switch Business" (own account's other businesses) - not
        // super-admin impersonation, see BusinessSwitchController.
        Route::post('/switch/{business_slug}', [BusinessSwitchController::class, 'switchTo'])->name('switch');
    });

    Route::prefix('businesses/{business_slug}')->name('business.clients.')->group(function () {
        // Day-to-day client operation: viewing the client list and
        // switching into (impersonating) a client business to work in it.
        // (kept for symmetry with the impersonation bypass).
        Route::prefix('clients')->middleware(['role:super-admin'])->group(function () {
            Route::post('fetch', [ClientController::class, 'fetch'])->name('fetch');
            Route::post('managed-businesses', [ClientController::class, 'managedBusinessesList'])->name('managed-businesses');
            Route::post('{client_business_slug}/impersonate', [ClientController::class, 'impersonateManagedBusiness'])->name('impersonate');
        });

        // Platform-governance actions (approving/suspending a tenant,
        // controlling which paid modules it has) - super-admin only.
        Route::prefix('clients')->middleware(['role:super-admin'])->group(function () {
            Route::post('{client_business_slug}/verify', [ClientController::class, 'verifyBusiness'])->name('verify');
            Route::post('{client_business_slug}/deactivate', [ClientController::class, 'deactivateBusiness'])->name('deactivate');
            Route::post('{client_business_slug}/modules/assign', [ClientController::class, 'assignModules'])->name('modules.assign');

            // Manual client payment ledger - same governance tier as
            // verify/deactivate/modules (financial/subscription control).
            Route::get('{client_business_slug}/payments/fetch', [\App\Http\Controllers\ClientPaymentController::class, 'fetch'])->name('payments.fetch');
            Route::post('{client_business_slug}/payments/store', [\App\Http\Controllers\ClientPaymentController::class, 'store'])->name('payments.store');
        });

        // Account Sharing actions (invite a colleague / approve their
        // request) - reads the business from session('active_business_slug')
        // rather than the URL, matching the page views in web.php. Named
        // distinctly from business.clients.request-access/grant-access
        // (the GET page routes) to avoid a route-name collision.
        Route::prefix('access')->middleware(['role:super-admin|business-admin'])->group(function () {
            Route::post('request', [ClientController::class, 'requestAccess'])->name('access.request');
            Route::post('grant', [ClientController::class, 'grantAccess'])->name('access.grant');
        });
    });

    // Creating/revoking platform-admin (super-admin) accounts - super-admin only.
    Route::prefix('businesses/{business_slug}/platform-admins')
        ->name('business.platform-admins.')
        ->middleware(['role:super-admin'])
        ->group(function () {
            Route::post('/', [PlatformAdminController::class, 'store'])->name('store');
            Route::post('{userId}/revoke', [PlatformAdminController::class, 'destroy'])->name('revoke');
        });



    Route::name('job-categories.')->prefix('job-categories')->group(function () {
        Route::post('edit', [JobCategoryController::class, 'edit'])->name('edit');
        Route::post('store', [JobCategoryController::class, 'store'])->name('store');
        Route::post('fetch', [JobCategoryController::class, 'fetch'])->name('fetch');
        Route::post('delete', [JobCategoryController::class, 'destroy'])->name('delete');
        Route::post('update', [JobCategoryController::class, 'update'])->name('update');
    });

    Route::name('departments.')->prefix('departments')->group(function () {
        Route::post('edit', [DepartmentController::class, 'edit'])->name('edit');
        Route::post('store', [DepartmentController::class, 'store'])->name('store');
        Route::post('fetch', [DepartmentController::class, 'fetch'])->name('fetch');
        Route::post('delete', [DepartmentController::class, 'destroy'])->name('delete');
        Route::post('update', [DepartmentController::class, 'update'])->name('update');
    });

    Route::name('shifts.')->prefix('shifts')->group(function () {
        Route::post('edit', [ShiftController::class, 'edit'])->name('edit');
        Route::post('store', [ShiftController::class, 'store'])->name('store');
        Route::post('fetch', [ShiftController::class, 'fetch'])->name('fetch');
        Route::post('delete', [ShiftController::class, 'destroy'])->name('delete');
        Route::post('update', [ShiftController::class, 'update'])->name('update');
    });

    Route::name('roster.')->prefix('roster')->group(function () {
        Route::post('edit', [RosterController::class, 'edit'])->name('edit');
        Route::post('store', [RosterController::class, 'store'])->name('store');
        Route::post('fetch', [RosterController::class, 'fetch'])->name('fetch');
        Route::post('delete', [RosterController::class, 'destroy'])->name('delete');
        Route::post('update', [RosterController::class, 'update'])->name('update');
        Route::post('notify', [RosterController::class, 'notify'])->name('notify');
        Route::post('reports', [RosterController::class, 'reports'])->name('reports');
        Route::post('export', [RosterController::class, 'export'])->name('export');
        Route::post('calendar', [RosterController::class, 'calendar'])->name('calendar');
    });

    Route::name('payroll-formulas.')->prefix('payroll-formulas')->group(function () {
        Route::post('/store', [PayrollFormulaController::class, 'store'])->name('store');
        Route::post('/fetch', [PayrollFormulaController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [PayrollFormulaController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [PayrollFormulaController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [PayrollFormulaController::class, 'destroy'])->name('destroy');
    });

    Route::name('deductions.')->prefix('deductions')->group(function () {
        Route::post('/store', [DeductionController::class, 'store'])->name('store');
        Route::post('/fetch', [DeductionController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [DeductionController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [DeductionController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [DeductionController::class, 'destroy'])->name('destroy');
    });

    Route::name('employee-deductions.')->prefix('employee-deductions')->group(function () {
        Route::post('create', [EmployeeDeductionController::class, 'create'])->name('create');
        Route::post('edit', [EmployeeDeductionController::class, 'edit'])->name('edit');
        Route::post('store', [EmployeeDeductionController::class, 'store'])->name('store');
        Route::post('fetch', [EmployeeDeductionController::class, 'fetch'])->name('fetch');
        Route::post('delete', [EmployeeDeductionController::class, 'destroy'])->name('delete');
        Route::post('update', [EmployeeDeductionController::class, 'update'])->name('update');
        Route::post('show', [EmployeeDeductionController::class, 'show'])->name('show');
    });

    Route::name('employees.')->prefix('employees')->group(function () {
        Route::post('/store', [EmployeeController::class, 'store'])->name('store');
        Route::post('/fetch', [EmployeeController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [EmployeeController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [EmployeeController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/send-welcome-email', [EmployeeController::class, 'sendWelcomeEmail'])->name('send-welcome-email');
        Route::post('/{id}/login-as', [EmployeeController::class, 'loginAsEmployee'])->name('login-as');
        Route::post('/stop-impersonating', [EmployeeController::class, 'stopImpersonatingEmployee'])->name('stop-impersonating');
        Route::post('/view', [EmployeeController::class, 'view'])->name('view');
        Route::post('/export', [EmployeeController::class, 'export'])->name('employees.export');
        Route::post('/{employeeId}/documents/upload', [EmployeeController::class, 'uploadDocument'])->name('documents.upload');
        Route::post('/{employeeId}/documents/{documentId}/delete', [EmployeeController::class, 'deleteDocument'])->name('documents.delete');
        Route::get('/{employeeId}/documents/{documentId}/download', [EmployeeController::class, 'downloadDocument'])->name('documents.download');
    });

    Route::name('contracts.')->prefix('contracts')->group(function () {
        Route::post('/store', [EmployeeController::class, 'storeContractAction'])->name('store');
        Route::post('/fetch', [EmployeeController::class, 'fetchContracts'])->name('fetch');
        Route::post('/edit', [EmployeeController::class, 'editContractAction'])->name('edit');
        Route::post('/{id}/update', [EmployeeController::class, 'updateContractAction'])->name('update');
        Route::post('/{id}/destroy', [EmployeeController::class, 'destroyContractAction'])->name('destroy');
        Route::post('/remind', [EmployeeController::class, 'sendContractReminder'])->name('remind');
    });

    Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');


        // Leave Types (AJAX)
    Route::name('leave-types.')->prefix('leave-types')->group(function () {
        Route::post('edit',   [LeaveTypeController::class, 'edit'])->name('edit');   // single edit route
        Route::post('store',  [LeaveTypeController::class, 'store'])->name('store');
        Route::post('fetch',  [LeaveTypeController::class, 'fetch'])->name('fetch');
        Route::post('show',   [LeaveTypeController::class, 'show'])->name('show');
        Route::post('delete', [LeaveTypeController::class, 'destroy'])->name('delete');
        Route::post('update', [LeaveTypeController::class, 'update'])->name('update');
        Route::post('suggestions', [LeaveTypeListController::class, 'suggestions'])->name('suggestions');

        Route::post('remaining-days', [LeaveTypeController::class, 'getRemainingDays'])->name('remaining-days');
    });

    // Leave Policies (AJAX) - consolidated read-only view of every policy
    // applied across the business, shown on the Leave Settings "Leave
    // Policies" tab. Policies themselves are still edited from within each
    // Leave Type's own form (leave-types.update above).
    Route::name('leave-policies.')->prefix('leave-policies')->group(function () {
        Route::post('fetch', [\App\Http\Controllers\LeavePolicyController::class, 'fetch'])->name('fetch');
    });


    // Leave Requests (AJAX)
    Route::name('leave.')->prefix('leave')->group(function () {
        Route::post('edit', [LeaveRequestController::class, 'edit'])->name('edit');
        Route::post('store', [LeaveRequestController::class, 'store'])->name('store');
        Route::post('fetch', [LeaveRequestController::class, 'fetch'])->name('fetch');
        Route::post('delete', [LeaveRequestController::class, 'destroy'])->name('delete');
        Route::post('update', [LeaveRequestController::class, 'update'])->name('update');
        Route::post('status', [LeaveRequestController::class, 'status'])->name('status');
        Route::post('cancel', [LeaveRequestController::class, 'cancel'])->name('cancel');

        //  Allow employees to upload required docs later
        Route::post('upload-document', [LeaveRequestController::class, 'uploadDocument'])->name('upload-document');
    });

    // Leave Periods (AJAX)
    Route::name('leave-periods.')->prefix('leave-periods')->group(function () {
        Route::post('create', [LeavePeriodController::class, 'create'])->name('create');
        Route::post('edit', [LeavePeriodController::class, 'edit'])->name('edit');
        Route::post('store', [LeavePeriodController::class, 'store'])->name('store');
        Route::post('fetch', [LeavePeriodController::class, 'fetch'])->name('fetch');
        Route::post('show', [LeavePeriodController::class, 'show'])->name('show');
        Route::post('delete', [LeavePeriodController::class, 'destroy'])->name('delete');
        Route::post('update', [LeavePeriodController::class, 'update'])->name('update');
        Route::post('close', [LeavePeriodController::class, 'close'])->name('close');
        // Reopening undoes a lock without undoing the carryover already
        // computed when it was closed - restricted to super-admin as a
        // rare, support-only action (see LeavePeriodController::reopen()).
        Route::middleware(['role:super-admin'])->post('reopen', [LeavePeriodController::class, 'reopen'])->name('reopen');
    });

    // Leave Entitlements (AJAX)
    Route::name('leave-entitlements.')->prefix('leave-entitlements')->group(function () {
        Route::post('edit', [LeaveEntitlementController::class, 'edit'])->name('edit');
        Route::post('store', [LeaveEntitlementController::class, 'store'])->name('store');
        Route::post('fetch', [LeaveEntitlementController::class, 'fetch'])->name('fetch');
        Route::post('show', [LeaveEntitlementController::class, 'show'])->name('show');
        Route::post('delete', [LeaveEntitlementController::class, 'delete'])->name('delete');
        Route::post('update', [LeaveEntitlementController::class, 'update'])->name('update');
        Route::post('adjust', [LeaveEntitlementController::class, 'adjust'])->name('adjust');
        Route::post('process-carryover', [LeaveEntitlementController::class, 'processCarryover'])->name('process-carryover');
    });

    Route::name('reliefs.')->prefix('reliefs')->group(function () {
        Route::post('/store', [ReliefsController::class, 'store'])->name('store');
        Route::post('/fetch', [ReliefsController::class, 'fetch'])->name('fetch');
        Route::post('/{slug}/edit', [ReliefsController::class, 'edit'])->name('edit');
        Route::post('/{slug}/show', [ReliefsController::class, 'show'])->name('show');
        Route::post('/{slug}/update', [ReliefsController::class, 'update'])->name('update');
        Route::post('/{slug}/destroy', [ReliefsController::class, 'destroy'])->name('destroy');
    });

    Route::name('employee-reliefs.')->prefix('employee-reliefs')->group(function () {
        Route::post('/store', [EmployeeReliefsController::class, 'store'])->name('store');
        Route::post('/fetch', [EmployeeReliefsController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [EmployeeReliefsController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [EmployeeReliefsController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [EmployeeReliefsController::class, 'destroy'])->name('destroy');
    });

    Route::name('locations.')->prefix('locations')->group(function () {
        Route::post('edit', [LocationController::class, 'edit'])->name('edit');
        Route::post('store', [LocationController::class, 'store'])->name('store');
        Route::post('fetch', [LocationController::class, 'fetch'])->name('fetch');
        Route::post('show', [LocationController::class, 'show'])->name('show');
        Route::post('delete', [LocationController::class, 'destroy'])->name('delete');
        Route::post('update', [LocationController::class, 'update'])->name('update');
    });

    Route::name('payroll.')->prefix('payroll')->group(function () {
        Route::post('/fetch', [PayrollController::class, 'fetch'])->name('fetch');
        Route::post('/filter', [PayrollController::class, 'filter'])->name('filter');
        Route::post('/adjust', [PayrollController::class, 'addAdjustment'])->name('adjust');
        Route::post('/employee-adjustments', [PayrollController::class, 'getEmployeeAdjustments'])->name('employee.adjustments');
        Route::post('/preview', [PayrollController::class, 'preview'])->name('preview');
        Route::post('/store', [PayrollController::class, 'store'])->name('store');

        Route::post('/close', [PayrollController::class, 'close'])->name('close'); // (global close)
        Route::post('/fetch-employees-for-settings', [PayrollController::class, 'fetchEmployeesForSettings'])->name('fetch-employees-for-settings');
        Route::post('/save-settings', [PayrollController::class, 'saveSettings'])->name('save-settings');
        Route::get('/available-items', [PayrollController::class, 'availableItems'])->name('available-items');
        Route::get('/default-amount/{type}/{itemId}', [PayrollController::class, 'defaultAmount'])->name('default-amount');

        Route::post('/{id}/process', [PayrollController::class, 'processPayroll'])->name('process');
        Route::post('/{id}/email-p9', [PayrollController::class, 'emailP9'])->name('email_p9');
        Route::post('/{id}/close', [PayrollController::class, 'closeMonth'])->name('close'); // (per-id close)
        Route::post('/{id}/delete', [PayrollController::class, 'deletePayroll'])->name('delete');
    });

    Route::name('advances.')->prefix('advances')->group(function () {
        Route::post('edit', [AdvanceController::class, 'edit'])->name('edit');
        Route::post('store', [AdvanceController::class, 'store'])->name('store');
        Route::post('fetch', [AdvanceController::class, 'fetch'])->name('fetch');
        Route::post('delete', [AdvanceController::class, 'destroy'])->name('delete');
        Route::post('update', [AdvanceController::class, 'update'])->name('update');
    });

    Route::name('allowances.')->prefix('allowances')->group(function () {
        Route::post('/store', [AllowanceController::class, 'store'])->name('store');
        Route::post('/fetch', [AllowanceController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [AllowanceController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [AllowanceController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [AllowanceController::class, 'destroy'])->name('destroy');
    });

    Route::name('loans.')->prefix('loans')->group(function () {
        Route::post('edit', [LoanController::class, 'edit'])->name('edit');
        Route::post('store', [LoanController::class, 'store'])->name('store');
        Route::post('fetch', [LoanController::class, 'fetch'])->name('fetch');
        Route::post('delete', [LoanController::class, 'destroy'])->name('delete');
        Route::post('update', [LoanController::class, 'update'])->name('update');
    });

    Route::name('job-posts.')->prefix('job-posts')->group(function () {
        Route::post('edit', [JobPostController::class, 'edit'])->name('edit');
        Route::post('store', [JobPostController::class, 'store'])->name('store');
        Route::post('fetch', [JobPostController::class, 'fetch'])->name('fetch');
        Route::post('show', [JobPostController::class, 'show'])->name('show');
        Route::post('destroy', [JobPostController::class, 'destroy'])->name('destroy');
        Route::post('update', [JobPostController::class, 'update'])->name('update');
        Route::post('toggle-public', [JobPostController::class, 'togglePublic'])->name('toggle-public');
    });

    Route::post('/generate-job-description', [JobPostController::class, 'generateDescription'])->middleware('auth');

    Route::name('applications.')->prefix('applications')->group(function () {
        Route::post('edit', [ApplicationController::class, 'edit'])->name('edit');
        Route::post('store', [ApplicationController::class, 'store'])->name('store');
        Route::post('fetch', [ApplicationController::class, 'fetch'])->name('fetch');
        Route::post('destroy', [ApplicationController::class, 'destroy'])->name('destroy');
        Route::post('update', [ApplicationController::class, 'update'])->name('update');
        Route::post('export', [ApplicationController::class, 'export'])->name('export');
        Route::post('update-stage', [ApplicationController::class, 'updateStage'])->name('update-stage');
        Route::post('shortlist', [ApplicationController::class, 'shortlist'])->name('shortlist');
        Route::post('schedule-interview', [ApplicationController::class, 'scheduleInterview'])->name('schedule-interview');
    });

    Route::name('interviews.')->prefix('interviews')->group(function () {
        Route::post('edit', [InterviewController::class, 'edit'])->name('edit');
        Route::post('store', [InterviewController::class, 'store'])->name('store');
        Route::post('fetch', [InterviewController::class, 'fetch'])->name('fetch');
        Route::post('show', [InterviewController::class, 'show'])->name('show');
        Route::post('destroy', [InterviewController::class, 'destroy'])->name('destroy');
        Route::post('update', [InterviewController::class, 'update'])->name('update');
        Route::post('reschedule', [InterviewController::class, 'reschedule'])->name('reschedule');
        Route::post('cancel', [InterviewController::class, 'cancel'])->name('cancel');
    });

    Route::name('applicants.')->prefix('applicants')->group(function () {
        Route::post('edit', [ApplicantController::class, 'edit'])->name('edit');
        Route::post('store', [ApplicantController::class, 'store'])->name('store');
        Route::post('fetch', [ApplicantController::class, 'fetch'])->name('fetch');
        Route::post('show', [ApplicantController::class, 'show'])->name('show');
        Route::post('destroy', [ApplicantController::class, 'destroy'])->name('destroy');
        Route::post('update', [ApplicantController::class, 'update'])->name('update');
        Route::post('download-document', [ApplicantController::class, 'downloadDocument'])->name('download-document');
        Route::post('filter', [ApplicantController::class, 'filter'])->name('filter');
        Route::post('export', [ApplicantController::class, 'export'])->name('export');
    });

    Route::name('tasks.')->prefix('tasks')->group(function () {
        Route::post('fetch', [TaskController::class, 'fetch'])->name('fetch');
        Route::post('edit', [TaskController::class, 'edit'])->name('edit');
        Route::post('store', [TaskController::class, 'store'])->name('store');
        Route::post('update/{task}', [TaskController::class, 'update'])->name('update');
        Route::post('destroy', [TaskController::class, 'destroy'])->name('destroy');
        Route::post('progress', [TaskController::class, 'progress'])->name('progress');
        Route::post('timelines', [TaskController::class, 'timelines'])->name('timelines');
    });

    Route::name('attendances.')->prefix('attendances')->group(function () {
        Route::post('clockin', [AttendanceController::class, 'clockIn'])->name('clockin');
        Route::post('clockout', [AttendanceController::class, 'clockOut'])->name('clockout');
        Route::post('fetch', [AttendanceController::class, 'fetch'])->name('fetch');
        Route::post('show', [AttendanceController::class, 'show'])->name('show');
        Route::post('destroy', [AttendanceController::class, 'destroy'])->name('destroy');
        Route::post('update', [AttendanceController::class, 'update'])->name('update');
        Route::post('clockins', [AttendanceController::class, 'clockIns'])->name('clockins');
        Route::post('monthly', [AttendanceController::class, 'monthly'])->name('monthly');
    });

    Route::name('overtime.')->prefix('overtime')->group(function () {
        Route::post('store', [OvertimeController::class, 'store'])->name('store');
        Route::post('destroy', [OvertimeController::class, 'destroy'])->name('destroy');
        Route::post('edit', [OvertimeController::class, 'edit'])->name('edit');
        Route::post('fetch', [OvertimeController::class, 'fetch'])->name('fetch');
    });

    Route::name('profile.')->prefix('profile')->group(function () {
        Route::post('store', [ProfileController::class, 'store'])->name('store');
        Route::post('destroy', [ProfileController::class, 'destroy'])->name('destroy');
        Route::post('password', [ProfileController::class, 'password'])->name('password');
    });

    Route::name('trends.')->prefix('trends')->group(function () {
        Route::post('payroll', [TrendController::class, 'payroll'])->name('payroll');
        Route::post('attendance', [TrendController::class, 'attendance'])->name('attendance');
        Route::post('leave', [TrendController::class, 'leave'])->name('leave');
        Route::post('loans', [TrendController::class, 'loans'])->name('loans');
    });

    Route::name('downloads.')->prefix('downloads')->group(function () {
        Route::post('/', [DownloadController::class, 'downloads'])->name('downloads');
        Route::post('payroll', [DownloadController::class, 'payroll'])->name('payroll');
        Route::post('attendance', [DownloadController::class, 'attendance'])->name('attendance');
        Route::post('leave', [DownloadController::class, 'leave'])->name('leave');
        Route::post('loans', [DownloadController::class, 'loans'])->name('loans');
    });

    Route::name('activities.')->prefix('activities')->group(function () {
        Route::post('fetch', [ActivityLogController::class, 'index'])->name('fetch');
    });

    Route::name('kpis.')->prefix('kpis')->group(function () {
        Route::post('/fetch', [KPIsController::class, 'fetch'])->name('fetch');
        Route::post('/fetch-cards', [KPIsController::class, 'fetchCards'])->name('fetch.cards');
        Route::post('/store', [KPIsController::class, 'store'])->name('store');
        Route::post('/edit', [KPIsController::class, 'edit'])->name('edit');
        Route::post('/update', [KPIsController::class, 'update'])->name('update');
        Route::post('/destroy', [KPIsController::class, 'destroy'])->name('destroy');
        Route::post('/results', [KPIsController::class, 'results'])->name('results');
        Route::post('/review', [KPIsController::class, 'review'])->name('review');
        Route::post('/update-review', [KPIsController::class, 'review'])->name('update.review');
        Route::post('/delete-review', [KPIsController::class, 'deleteReview'])->name('delete.review');
    });

    Route::name('warning.')->prefix('warning')->group(function () {
        Route::post('/store', [WarningController::class, 'store'])->name('store');
        Route::post('/fetch', [WarningController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [WarningController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [WarningController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [WarningController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/escalate', [WarningController::class, 'escalate'])->name('escalate');

        Route::name('investigations.')->prefix('{warningId}/investigations')->group(function () {
            Route::post('/store', [DisciplinaryInvestigationController::class, 'store'])->name('store');
            Route::post('/{investigation}/update', [DisciplinaryInvestigationController::class, 'update'])->name('update');
            Route::post('/{investigation}/destroy', [DisciplinaryInvestigationController::class, 'destroy'])->name('destroy');
        });

        Route::name('minutes.')->prefix('{warningId}/minutes')->group(function () {
            Route::post('/store', [DisciplinaryMinutesController::class, 'store'])->name('store');
            Route::post('/{minute}/update', [DisciplinaryMinutesController::class, 'update'])->name('update');
            Route::post('/{minute}/destroy', [DisciplinaryMinutesController::class, 'destroy'])->name('destroy');
        });
    });

    // "Configure" - fully business-configurable disciplinary stages (GUIDE plan Phase 3).
    Route::name('disciplinary-stage-types.')->prefix('disciplinary-stage-types')->group(function () {
        Route::get('/fetch', [DisciplinaryStageTypeController::class, 'fetch'])->name('fetch');
        Route::post('/store', [DisciplinaryStageTypeController::class, 'store'])->name('store');
        Route::post('/{stageType}/update', [DisciplinaryStageTypeController::class, 'update'])->name('update');
        Route::delete('/{stageType}/destroy', [DisciplinaryStageTypeController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [DisciplinaryStageTypeController::class, 'reorder'])->name('reorder');
    });

    Route::name('pay-grades.')->prefix('pay-grades')->group(function () {
        Route::post('/store', [PayGradesController::class, 'store'])->name('store');
        Route::post('/fetch', [PayGradesController::class, 'fetch'])->name('fetch');
        Route::post('/edit', [PayGradesController::class, 'edit'])->name('edit');
        Route::post('/{id}/update', [PayGradesController::class, 'update'])->name('update');
        Route::post('/{id}/destroy', [PayGradesController::class, 'destroy'])->name('destroy');
    });

    // roles
    Route::name('roles.')->prefix('roles')->group(function () {
        Route::get('modules', [RoleController::class, 'modulesForMatrix'])->name('modules');
        Route::post('fetch', [RoleController::class, 'fetch'])->name('fetch');
        Route::post('store', [RoleController::class, 'store'])->name('store');
        Route::post('edit', [RoleController::class, 'edit'])->name('edit');
        Route::post('update', [RoleController::class, 'update'])->name('update');
        Route::post('destroy', [RoleController::class, 'destroy'])->name('destroy');
        Route::post('assign', [RoleController::class, 'assign'])->name('assign');
    });

    // surveys
    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::post('/fetch', [SurveyController::class, 'fetch'])->name('fetch');
        Route::post('/', [SurveyController::class, 'store'])->name('store');
        Route::post('/{survey}/update', [SurveyController::class, 'update'])->name('update');
        Route::post('/{survey}/destroy', [SurveyController::class, 'destroy'])->name('destroy');
    });

    // CRM (AJAX)
    Route::name('crm.')->prefix('crm')->group(function () {
        Route::post('/contacts/fetch', [CrmController::class, 'fetchContacts'])->name('contacts.fetch');
        Route::post('/contacts/store', [CrmController::class, 'storeContact'])->name('contacts.store');
        Route::post('/contacts/update', [CrmController::class, 'updateContact'])->name('contacts.update');
        Route::post('/contacts/destroy', [CrmController::class, 'destroyContact'])->name('contacts.destroy');

        Route::post('/campaigns/fetch', [CrmController::class, 'fetchCampaigns'])->name('campaigns.fetch');
        Route::post('/campaigns/store', [CrmController::class, 'storeCampaign'])->name('campaigns.store');
        Route::post('/campaigns/update', [CrmController::class, 'updateCampaign'])->name('campaigns.update');
        Route::post('/campaigns/destroy', [CrmController::class, 'destroyCampaign'])->name('campaigns.destroy');
        Route::post('/campaigns/analytics/fetch', [CrmController::class, 'fetchAnalytics'])->name('campaigns.analytics.fetch');

        Route::post('/leads/fetch', [CrmController::class, 'fetchLeads'])->name('leads.fetch');
        Route::post('/leads/store', [CrmController::class, 'storeLead'])->name('leads.store');
        Route::post('/leads/update', [CrmController::class, 'updateLead'])->name('leads.update');
        Route::post('/leads/destroy', [CrmController::class, 'destroyLead'])->name('leads.destroy');
        Route::post('/leads/label', [CrmController::class, 'labelLead'])->name('leads.label');

        Route::post('/lead-activities/store', [CrmController::class, 'storeLeadActivity'])->name('lead-activities.store');
    });
});
