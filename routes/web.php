<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\JobPostController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\PayrollFormulaController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\KPIsController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\PublicSurveyController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\LeavePeriodController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveEntitlementController;
use App\Http\Controllers\OrganogramController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\LeaveDelegationController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\PerformanceFeedbackController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\MandatoryLeavePeriodController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\BusinessCurrencyController;
use App\Http\Controllers\InterviewController;

use App\Models\Business;

Route::get('api/jobs/openings', [JobPostController::class, 'fetchPublic'])->name('jobs.openings');

Route::get('/business/{businessSlug}/api-token', [BusinessController::class, 'showApiTokenForm'])
    ->middleware('auth')
    ->name('business.api-token');

Route::middleware(['auth', \App\Http\Middleware\VerifyBusiness::class, \App\Http\Middleware\EnsureTwoFactorAuthenticated::class])
    ->post('/ajax/leave/remaining-days', [LeaveTypeController::class, 'getRemainingDaysAjax'])
    ->name('ajax.leave.remaining-days');
Route::middleware(['auth', \App\Http\Middleware\VerifyBusiness::class, \App\Http\Middleware\EnsureTwoFactorAuthenticated::class])->group(function () {

    Route::post('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('switch.role');
    Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');
    //setup busines & modules
    Route::name('setup.')->prefix('setup')->group(function () {
        Route::get('business', [BusinessController::class, 'create'])->name('business');
        Route::get('modules', [ModuleController::class, 'create'])->name('modules');
        Route::post('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('business.attendance.settings.update');
        Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');
    });

    Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff'])
        ->name('location.')
        ->prefix('location/{location:slug}')
        ->group(function () {
            Route::get('/payroll/{id}/download-column/{column}/{format}', [PayrollController::class, 'downloadColumn'])->name('payroll.download_column');
        });

    Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {

            Route::post('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');
            Route::post('/locations/{location}/coords', [AttendanceController::class, 'updateLocationCoords'])->name('business.locations.coords.update');
            Route::post('/employees/{employee}/mac', [AttendanceController::class, 'updateEmployeeMac'])->name('employees.mac.update');
            //Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');

            Route::get('/locations', [DashboardController::class, 'locations'])->name('locations.index');
            Route::get('/organization-setup', [BusinessController::class, 'setup'])->name('organization-setup');

            Route::prefix('organization-structure')->name('organization-structure.')->group(function () {
                Route::get('/', [OrganizationStructureController::class, 'index'])->name('index');
                Route::get('/roles', [OrganizationStructureController::class, 'fetchRoles'])->name('roles.fetch');
                Route::post('/roles', [OrganizationStructureController::class, 'storeRole'])->name('roles.store');
                Route::post('/roles/{role}', [OrganizationStructureController::class, 'updateRole'])->name('roles.update');
                Route::delete('/roles/{role}', [OrganizationStructureController::class, 'destroyRole'])->name('roles.destroy');
                Route::get('/spatie-roles', [OrganizationStructureController::class, 'fetchAvailableSpatieRoles'])->name('spatie-roles.fetch');
                Route::get('/teams', [OrganizationStructureController::class, 'fetchTeams'])->name('teams.fetch');
                Route::post('/teams', [OrganizationStructureController::class, 'storeTeam'])->name('teams.store');
                Route::delete('/teams/{team}', [OrganizationStructureController::class, 'destroyTeam'])->name('teams.destroy');
                Route::post('/sync', [OrganizationStructureController::class, 'syncAll'])->name('sync');
                Route::post('/bulk-assign-role', [OrganizationStructureController::class, 'bulkAssignRole'])->name('bulk-assign-role');
                Route::get('/assignments', [OrganizationStructureController::class, 'fetchAssignments'])->name('assignments.fetch');
                Route::post('/positions', [OrganizationStructureController::class, 'storePosition'])->name('positions.store');
                Route::delete('/positions/{position}', [OrganizationStructureController::class, 'destroyPosition'])->name('positions.destroy');
            });
            Route::get('/pay-schedule', [DashboardController::class, 'paySchedule'])->name('pay-schedule');

            Route::get('/departments', [DashboardController::class, 'departments'])->name('departments.index');

            Route::prefix('organogram')->name('organogram.')->group(function () {
                Route::get('/', [OrganogramController::class, 'index'])->name('index');
                Route::get('/fetch', [OrganogramController::class, 'fetch'])->name('fetch');
                Route::get('/employee-options', [OrganogramController::class, 'employeeOptions'])->name('employee-options');
                Route::post('/assign-manager', [OrganogramController::class, 'assignManager'])->name('assign-manager');
                Route::post('/reset-manager', [OrganogramController::class, 'resetManagerToTemplate'])->name('reset-manager');
            });

            Route::prefix('performance')->name('performance.')->group(function () {
                Route::get('/cycles', [PerformanceController::class, 'cyclesIndex'])->name('cycles.index');
                Route::get('/cycles/fetch', [PerformanceController::class, 'fetchCycles'])->name('cycles.fetch');
                Route::post('/cycles', [PerformanceController::class, 'storeCycle'])->name('cycles.store');
                Route::post('/cycles/{cycle}/status', [PerformanceController::class, 'updateCycleStatus'])->name('cycles.status');

                Route::get('/employees/{employee}', [PerformanceController::class, 'employeePerformance'])->name('employee');
                Route::get('/employees/{employee}/objectives', [PerformanceController::class, 'fetchObjectives'])->name('objectives.fetch');
                Route::post('/employees/{employee}/objectives', [PerformanceController::class, 'storeObjective'])->name('objectives.store');
                Route::get('/employees/{employee}/kpis', [PerformanceController::class, 'fetchKpisForEmployee'])->name('kpis.for-employee');
                Route::get('/objectives/cascade', [PerformanceController::class, 'fetchCascadeObjectives'])->name('objectives.cascade');
                Route::get('/objectives/critical', [PerformanceController::class, 'fetchCriticalObjectives'])->name('objectives.critical');
                Route::post('/objectives/{objective}/approve-alignment', [PerformanceController::class, 'approveAlignment'])->name('objectives.approve-alignment');
                Route::post('/objectives/{objective}/decline-alignment', [PerformanceController::class, 'declineAlignment'])->name('objectives.decline-alignment');
                Route::post('/objectives/{objective}/key-results', [PerformanceController::class, 'storeKeyResult'])->name('key-results.store');
                Route::post('/key-results/{keyResult}/progress', [PerformanceController::class, 'updateKeyResultProgress'])->name('key-results.progress');

                Route::get('/cycles/{cycle}/employees/{employee}/review', [PerformanceController::class, 'fetchReview'])->name('review.fetch');
                Route::post('/reviews/{review}/self-assessment', [PerformanceController::class, 'submitSelfAssessment'])->name('review.self');
                Route::post('/reviews/{review}/manager-assessment', [PerformanceController::class, 'submitManagerAssessment'])->name('review.manager');

                Route::get('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'fetchForSubject'])->name('feedback.fetch');
                Route::post('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'store'])->name('feedback.store');
                Route::get('/feedback/inbox', [PerformanceFeedbackController::class, 'fetchMyInbox'])->name('feedback.inbox');
                Route::post('/feedback/{feedbackRequest}/decline', [PerformanceFeedbackController::class, 'decline'])->name('feedback.decline');
                Route::post('/feedback/{feedbackRequest}/response', [PerformanceFeedbackController::class, 'submitResponse'])->name('feedback.respond');
            });

    Route::post('roles/update-departments', [RoleController::class, 'updateDepartments'])->name('roles.update-departments');
            Route::get('/employees', [DashboardController::class, 'employees'])->name('employees.index');
            Route::get('/employees/import', [DashboardController::class, 'importEmployees'])->name('employees.import');
            Route::get('/employees/warning', [DashboardController::class, 'warning'])->name('employees.warning');
    Route::prefix('warnings')->name('warnings.')->group(function () {
    Route::post('/fetch', [\App\Http\Controllers\WarningController::class, 'fetch'])->name('fetch');
    Route::post('/store', [\App\Http\Controllers\WarningController::class, 'store'])->name('store');
    Route::post('/edit', [\App\Http\Controllers\WarningController::class, 'edit'])->name('edit');
    Route::get('/{id}/show', [\App\Http\Controllers\WarningController::class, 'show'])->name('show');
    Route::post('/{id}/update', [\App\Http\Controllers\WarningController::class, 'update'])->name('update');
    Route::post('/{id}/delete', [\App\Http\Controllers\WarningController::class, 'destroy'])->name('delete');
});
            Route::get('/employees/contracts', [DashboardController::class, 'contracts'])->name('employees.contracts');

            Route::get('/employees/download-csv-template', [EmployeeController::class, 'downloadCsvTemplate'])->name('employees.downloadCsvTemplate');
            Route::get('/employees/download-xlsx-template', [EmployeeController::class, 'downloadXlsxTemplate'])->name('employees.downloadXlsxTemplate');

            Route::get('/job-categories', [DashboardController::class, 'jobCategories'])->name('job-categories.index');
            Route::get('/shifts', [DashboardController::class, 'shifts'])->name('shifts.index');
            Route::get('/roster', [DashboardController::class, 'roster'])->name('roster.index');

            Route::get('/payroll-formulas', [DashboardController::class, 'payrollFormulas'])->name('payroll-formulas.index');
            Route::get('/payroll-formulas/bracket-template', [PayrollFormulaController::class, 'bracketTemplate'])->name('payroll-formulas.bracket-template');

            Route::get('/deductions', [DashboardController::class, 'deductions'])->name('deductions');

            Route::get('/payroll', [DashboardController::class, 'payroll'])->name('payroll.index');
            Route::get('/payroll/all', [DashboardController::class, 'payrollAll'])->name('payroll.all');
            // variance report
            Route::get('/payroll/variance', [PayrollController::class, 'variancePage'])
                ->name('payroll.variance');
            Route::get('/payroll/variance/download', [PayrollController::class, 'downloadVarianceReport'])
                ->name('payroll.variance.download');
            Route::get('/payroll/variance/data', [PayrollController::class, 'varianceData'])
                ->name('payroll.variance.data');

            Route::get('/payroll/{id}', [DashboardController::class, 'viewPayroll'])->name('payroll.view');
            Route::get('/payroll/{id}/download/{format}', [DashboardController::class, 'downloadPayroll'])->name('payroll.reports');
            Route::get('/payroll/{id}/download-column/{column}/{format}', [DashboardController::class, 'downloadColumn'])->name('payroll.download_column');
            Route::get('/payroll/{id}/print-all-payslips', [DashboardController::class, 'printAllPayslips'])->name('payroll.print_all_payslips');

            Route::get('/payslips', [PayrollController::class, 'viewPayslips'])->name('payslips');
            Route::get('/payroll/payslip/{employee_id}', [PayrollController::class, 'viewPayslip'])->name('payroll.payslip');

            Route::get('/payroll/download-p9/{year}/{format}', [PayrollController::class, 'downloadP9'])->name('payroll.download_p9');
            Route::get('/payroll/download-bank-advice/{year}/{month}/{format}', [PayrollController::class, 'downloadBankAdvice'])->name('payroll.download_bank_advice');
            Route::get('/payroll/p9/{employeeId}/{year}/{format}', [PayrollController::class, 'downloadSingleP9'])->name('payroll.download_single_p9');

            Route::post('/payroll/send-payslips', [PayrollController::class, 'sendPayslips'])->name('payroll.send_payslips');
            Route::get(
                '/payroll/{id}/master-roll',
                [PayrollController::class, 'downloadMasterRoll']
            )
                ->name('payroll.master-roll');


            // Monthly Summary Downloads
            Route::get('/download-nssf-summary', [PayrollController::class, 'downloadNssfMonthlySummary'])
                ->name('download-nssf-summary');

            Route::get('/download-shif-summary', [PayrollController::class, 'downloadShifMonthlySummary'])
                ->name('download-shif-summary');

            Route::get('/download-nhif-summary', [PayrollController::class, 'downloadNhifMonthlySummary'])
                ->name('download-nhif-summary');

            // ─── NSSF per-payroll format downloads ───────────────────────────────
            // Handles: new_remittance | pre_2018 | old_format | schedule | grouped
            Route::get('/payroll/nssf/download', [PayrollController::class, 'downloadNssf'])
                ->name('payroll.nssf.download');

            // ─── NSSF month-by-month summary (full year, xlsx + pdf) ─────────────
            Route::get('/payroll/nssf/monthly-summary', [PayrollController::class, 'downloadNssfMonthlySummaryWithFormat'])
                ->name('payroll.nssf.monthly_summary');

            Route::get('reliefs', [DashboardController::class, 'reliefs'])->name('reliefs.index');
            Route::get('employee-reliefs', [DashboardController::class, 'employeeReliefs'])->name('employee-reliefs.index');

            Route::get('/allowances', [DashboardController::class, 'allowances'])->name('allowances.index');

            Route::get('/advances', [DashboardController::class, 'advances'])->name('advances.index');
            Route::get('/loans', [DashboardController::class, 'loans'])->name('loans.index');

            // Leave area (business views + a few actions)
            Route::prefix('leave')->name('leave.')->group(function () {
                Route::get('/calendar', [LeaveCalendarController::class, 'businessCalendar'])->name('calendar');
                Route::get('/calendar/events', [LeaveCalendarController::class, 'businessEvents'])->name('calendar.events');
                Route::get('/requests', [DashboardController::class, 'leaveApplications'])->name('index');
                Route::get('/requests/create', [DashboardController::class, 'requestLeave'])->name('create');
                Route::get('/view/{leave}', [DashboardController::class, 'leaveApplication'])->name('show');
                Route::post('/requests/export', [LeaveRequestController::class, 'export'])->name('requests.export');
                Route::get('/types', [DashboardController::class, 'leaveTypes'])->name('types');
                Route::get('/periods', [DashboardController::class, 'leavePeriods'])->name('periods');
                Route::get('/entitlements', [DashboardController::class, 'leaveEntitlements'])->name('entitlements.index');
                Route::get('/entitlements/set', [DashboardController::class, 'setLeaveEntitlements'])->name('entitlements.create');
                Route::get('/settings', [DashboardController::class, 'leaveSettings'])->name('settings');
                Route::post('/settings', [DashboardController::class, 'updateLeaveSettings'])->name('settings.update');

                // Leave types
                Route::post('/revoke', [LeaveRequestController::class, 'revoke'])->name('revoke');
                Route::get('/{reference}/download', [LeaveRequestController::class, 'downloadPdf'])->name('download')->where('reference', '[A-Za-z0-9\-]+');
                Route::get('/leave-types/{slug}/edit', [LeaveTypeController::class, 'edit'])->name('leave-types.edit');
                Route::delete('/leave-types/delete', [LeaveTypeController::class, 'destroy'])->name('leave-types.delete');
                Route::post('/leave-types/remaining-days', [LeaveTypeController::class, 'getRemainingDays'])->name('leave-types.remaining-days');
                Route::post('/leave-types/update', [LeaveTypeController::class, 'update'])->name('leave-types.update');
                Route::post('/upload-document', [LeaveRequestController::class, 'uploadDocument'])->name('upload-document');
                Route::post('/status', [LeaveRequestController::class, 'status'])->name('status');
            });

            // Company-mandated leave days (e.g. a Dec 24-28 shutdown) - auto-deducts
            // from affected employees' balances for a chosen leave type.
            Route::prefix('mandatory-leave-days')->name('leave.mandatory-leave-days.')->group(function () {
                Route::post('/fetch', [MandatoryLeavePeriodController::class, 'fetch'])->name('fetch');
                Route::post('/create', [MandatoryLeavePeriodController::class, 'create'])->name('create');
                Route::post('/edit', [MandatoryLeavePeriodController::class, 'edit'])->name('edit');
                Route::post('/store', [MandatoryLeavePeriodController::class, 'store'])->name('store');
                Route::post('/update', [MandatoryLeavePeriodController::class, 'update'])->name('update');
                Route::post('/delete', [MandatoryLeavePeriodController::class, 'destroy'])->name('delete');
            });

            Route::prefix('leave-periods')->name('leave-periods.')->group(function () {
                Route::get('/fetch', [LeavePeriodController::class, 'fetch'])->name('fetch');
                Route::post('/store', [LeavePeriodController::class, 'store'])->name('store');
                Route::get('/{leavePeriod}/details', [LeavePeriodController::class, 'showDetails'])->name('details');
                Route::get('/{leavePeriod}/json', [LeavePeriodController::class, 'fetchJson'])->name('json');
                Route::post('/update', [LeavePeriodController::class, 'update'])->name('update');
                Route::get('/{leavePeriod}/edit', [LeavePeriodController::class, 'edit'])->name('edit');
                Route::post('/delete', [LeavePeriodController::class, 'destroy'])->name('delete');
            });


            Route::prefix('leave-entitlements')->group(function () {
                Route::post('/fetch',  [LeaveEntitlementController::class, 'fetch'])->name('leave-entitlements.fetch');
                Route::post('/store',  [LeaveEntitlementController::class, 'store'])->name('leave-entitlements.store');
                Route::post('/show', [LeaveEntitlementController::class, 'show'])->name('leave-entitlements.show');
                Route::post('/edit',   [LeaveEntitlementController::class, 'edit'])->name('leave-entitlements.edit');
                Route::post('/update', [LeaveEntitlementController::class, 'update'])->name('leave-entitlements.update');
                Route::post('/delete', [LeaveEntitlementController::class, 'delete'])->name('leave-entitlements.delete');
                Route::post('/by-period', [LeaveEntitlementController::class, 'getByPeriod'])
                    ->name('leave-entitlements.by-period');
                Route::post('/by-period', [LeaveEntitlementController::class, 'getByPeriod'])->name('leave-entitlements.by-period');
                Route::post('/employees/filter', [EmployeeController::class, 'filter'])->name('leave-entitlements.employees.filter');
                Route::post('/adjust', [LeaveEntitlementController::class, 'adjust'])->name('leave-entitlements.adjust');
                Route::post('/process-carryover', [LeaveEntitlementController::class, 'processCarryover'])->name('leave-entitlements.process-carryover');
                Route::get('/export-pdf', [LeaveEntitlementController::class, 'exportPdf'])->name('leave-entitlements.export-pdf');
            });


            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/job-posts', [JobPostController::class, 'index'])->name('jobs.index');
                Route::get('/job-posts/create', [JobPostController::class, 'create'])->name('jobs.create');
                Route::get('/job-posts/{jobpost}', [JobPostController::class, 'show'])->name('jobs.show');
                Route::get('/job-posts/{jobpost}/edit', [JobPostController::class, 'editView'])->name('jobs.edit');
                Route::get('/interviews', [DashboardController::class, 'interviews'])->name('interviews');
                Route::get('/reports', [ApplicationController::class, 'reports'])->name('reports');
            });

            Route::prefix('interviews')->name('interviews.')->group(function () {
    Route::get('/', [InterviewController::class, 'index'])->name('index');
    Route::post('/fetch', [InterviewController::class, 'fetch'])->name('fetch');
    Route::post('/store', [InterviewController::class, 'store'])->name('store');
    Route::get('/{id}/show', [InterviewController::class, 'show'])->name('show');
    Route::post('/edit', [InterviewController::class, 'edit'])->name('edit');
    Route::post('/update', [InterviewController::class, 'update'])->name('update');
    Route::post('/reschedule', [InterviewController::class, 'reschedule'])->name('reschedule');
    Route::post('/cancel', [InterviewController::class, 'cancel'])->name('cancel');
    Route::post('/destroy', [InterviewController::class, 'destroy'])->name('destroy');
});

           Route::prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::get('/create', [ApplicationController::class, 'create'])->name('create');
    Route::get('/kpis', [ApplicationController::class, 'kpis'])->name('kpis'); // add this — before {application}!
    Route::get('/{application}', [ApplicationController::class, 'view'])->name('view');
});

            Route::prefix('applicants')->name('applicants.')->group(function () {
                Route::get('/', [ApplicantController::class, 'index'])->name('index');
                Route::get('/create', [ApplicantController::class, 'create'])->name('create');
                Route::get('/{applicant}', [ApplicantController::class, 'view'])->name('view');
                Route::get('/{applicant}/download-document/{mediaId}', [ApplicantController::class, 'downloadDocument'])->name('download-document');
            });

            Route::prefix('performance')->name('performance.')->group(function () {
                Route::prefix('tasks')->name('tasks.')->group(function () {
                    Route::get('/', [DashboardController::class, 'tasks'])->name('index');
                    Route::get('/create', [DashboardController::class, 'create'])->name('create');
                    Route::get('/progress/{task}', [DashboardController::class, 'progress'])->name('progress');
                    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
                    Route::get('/{task}', [DashboardController::class, 'show'])->name('show');
                });
                Route::get('/reviews', [DashboardController::class, 'reviews'])->name('reviews');
                Route::prefix('kpis')->name('kpis.')->group(function () {
                    Route::get('/', [KPIsController::class, 'index'])->name('index');
                    Route::get('/create', [KPIsController::class, 'create'])->name('create');
                    Route::get('/results', [KPIsController::class, 'results'])->name('results');
                    Route::get('/edit', [KPIsController::class, 'edit'])->name('edit');
                });
            });

            Route::prefix('attendances')->name('attendances.')->group(function () {
                Route::get('/', [DashboardController::class, 'attendances'])->name('index');
                Route::get('/monthly', [DashboardController::class, 'monthlyAttendances'])->name('monthly');
                Route::get('/clock-in', [DashboardController::class, 'clockIn'])->name('clock-in');
                Route::get('/clock-out', [DashboardController::class, 'clockOut'])->name('clock-out');
            });

            Route::prefix('downloads')->name('downloads.')->group(function () {
                Route::get('/', [DashboardController::class, 'attendances'])->name('index');
            });

            Route::prefix('overtime')->name('overtime.')->group(function () {
                Route::get('/', [DashboardController::class, 'overtime'])->name('index');
                Route::get('/rates', [DashboardController::class, 'overtimeRates'])->name('rates');
            });

            Route::get('/work-schedules', [DashboardController::class, 'workSchedules'])->name('work-schedules.index');
            // Work Schedules Routes
            Route::prefix('work-schedules')->group(function () {
                Route::post('/fetch', [WorkScheduleController::class, 'fetch'])->name('work-schedules.fetch');
                Route::post('/store', [WorkScheduleController::class, 'store'])->name('work-schedules.store');
                Route::post('/edit', [WorkScheduleController::class, 'edit'])->name('work-schedules.edit');
                Route::post('/update', [WorkScheduleController::class, 'update'])->name('work-schedules.update');
                Route::post('/delete', [WorkScheduleController::class, 'destroy'])->name('work-schedules.destroy');
                Route::post('/schedule-info', [WorkScheduleController::class, 'getScheduleInfo'])->name('work-schedules.info');
                Route::post('/create-form', [WorkScheduleController::class, 'createForm'])->name('work-schedules.create-form');
                Route::post('/activate', [WorkScheduleController::class, 'activate'])->name('work-schedules.activate');
                Route::post('/bulk-store', [WorkScheduleController::class, 'bulkStore'])->name('work-schedules.bulk-store');
                Route::post('/timeline', [WorkScheduleController::class, 'timeline'])->name('work-schedules.timeline');
            });

            Route::get('/holidays', [DashboardController::class, 'holidays'])->name('holidays.index');

            // Holidays Routes
            Route::prefix('holidays')->group(function () {
                Route::post('/fetch', [HolidayController::class, 'fetch'])->name('holidays.fetch');
                Route::post('/store', [HolidayController::class, 'store'])->name('holidays.store');
                Route::post('/edit', [HolidayController::class, 'edit'])->name('holidays.edit');
                Route::post('/update', [HolidayController::class, 'update'])->name('holidays.update');
                Route::post('/delete', [HolidayController::class, 'destroy'])->name('holidays.destroy');
                Route::post('/check', [HolidayController::class, 'checkHoliday'])->name('holidays.check');
                Route::get('/countries', [HolidayController::class, 'availableCountries'])->name('holidays.countries');
                Route::post('/import', [HolidayController::class, 'importFromApi'])->name('holidays.import');
            });

            // Enhanced Attendance Routes
            Route::prefix('attendances')->group(function () {
                Route::get('/settings', [AttendanceController::class, 'settingsPage'])->name('attendances.settings.index');
                Route::post('/fetch', [AttendanceController::class, 'fetch'])->name('attendances.fetch');
                Route::post('/monthly', [AttendanceController::class, 'monthly'])->name('attendances.monthly');
                Route::post('/clockin', [AttendanceController::class, 'clockIn'])->name('attendances.clockin');
                Route::post('/clockout', [AttendanceController::class, 'clockOut'])->name('attendances.clockout');
                Route::post('/clockins', [AttendanceController::class, 'clockIns'])->name('attendances.clockins');
                Route::post('/employee-summary', [AttendanceController::class, 'getEmployeeSummary'])->name('attendances.summary');
                Route::post('/edit', [AttendanceController::class, 'edit'])->name('attendances.edit');
                Route::post('/view', [AttendanceController::class, 'view'])->name('attendances.view');
                Route::post('/update', [AttendanceController::class, 'update'])->name('attendances.update');
                Route::post('/delete', [AttendanceController::class, 'destroy'])->name('attendances.delete');
                Route::post('/{slug}/settings', [AttendanceController::class, 'updateSettings'])->name('attendances.settings');
                Route::post('/{slug}/locations/{locationId}/coords', [AttendanceController::class, 'updateLocationCoords'])->name('attendances.location.coords');
                Route::post('/employees/{employeeId}/mac', [AttendanceController::class, 'updateEmployeeMac'])->name('attendances.employee.mac');
                Route::get('/geocode', [AttendanceController::class, 'geocode'])->name('attendances.geocode');
                Route::post('/payroll-summary', [AttendanceController::class, 'payrollSummary'])->name('attendances.payroll-summary');
                Route::get('/payroll-hours',[AttendanceController::class, 'payrollHoursPage'])->name('attendances.payroll-hours');
                Route::post('/payroll-details', [AttendanceController::class, 'payrollEmployeeDetails'])->name('attendances.payroll-details');
                Route::get('/payroll-summary-export', [AttendanceController::class, 'payrollSummaryExport'])->name('attendances.payroll-summary-export');
            });

            // Enhanced Overtime Routes (replace existing overtime routes)
            Route::prefix('overtime')->group(function () {
                Route::post('/fetch', [OvertimeController::class, 'fetch'])->name('overtime.fetch');
                Route::post('/store', [OvertimeController::class, 'store'])->name('overtime.store');
                Route::post('/edit', [OvertimeController::class, 'edit'])->name('overtime.edit');
                Route::post('/update', [OvertimeController::class, 'update'])->name('overtime.update');
                Route::post('/destroy', [OvertimeController::class, 'destroy'])->name('overtime.destroy');
                Route::post('/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
                Route::post('/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');
                Route::post('/bulk-approve', [OvertimeController::class, 'bulkApprove'])->name('overtime.bulk-approve');
                Route::post('/summary', [OvertimeController::class, 'getSummary'])->name('overtime.summary');
            });


            Route::get('clock-in-out', [DashboardController::class, 'clockInOut'])->name('clock-in-out.index');

            Route::get('reports', [DashboardController::class, 'attendanceReport'])->name('reports.index');

            Route::get('profile', [ProfileController::class, 'edit'])->name('profile.index');

            Route::get('pay-grades', [DashboardController::class, 'payGrades'])->name('pay-grades.index');

            // roles
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
            Route::get('/roles/{role}/edit', [RoleController::class, 'editView'])->name('roles.edit');
      Route::prefix('settings/currencies')->name('currencies.')->group(function () {
    Route::get('/',              [BusinessCurrencyController::class, 'index'])->name('index');
    Route::get('/list',          [BusinessCurrencyController::class, 'list'])->name('list');
    Route::get('/known',         [BusinessCurrencyController::class, 'knownCurrencies'])->name('known');
    Route::post('/refresh-all',  [BusinessCurrencyController::class, 'refreshAllRates'])->name('refresh-all');
    Route::post('/',             [BusinessCurrencyController::class, 'store'])->name('store');
    Route::delete('/bulk',       [BusinessCurrencyController::class, 'bulkDestroy'])->name('bulk-destroy'); // ← BEFORE /{id}
    Route::get('/{id}',          [BusinessCurrencyController::class, 'show'])->name('show');
    Route::put('/{id}',          [BusinessCurrencyController::class, 'update'])->name('update');
    Route::delete('/{id}',       [BusinessCurrencyController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/refresh', [BusinessCurrencyController::class, 'refreshRate'])->name('refresh');
});

            // 🔹 CRM (PAGE ROUTES) — fixes Route [business.crm.contacts.index] not defined
            Route::prefix('crm')->name('crm.')->group(function () {
                // Contacts pages
                Route::get('/contacts', [CrmController::class, 'contacts'])->name('contacts.index');
                Route::get('/contacts/create', [CrmController::class, 'createContact'])->name('contacts.create');
                Route::get('/contacts/{submission}', [CrmController::class, 'viewContact'])->name('contacts.view');

                // Campaigns pages
                Route::get('/campaigns', [CrmController::class, 'campaigns'])->name('campaigns.index');
                Route::get('/campaigns/create', [CrmController::class, 'createCampaign'])->name('campaigns.create');
                Route::get('/campaigns/{campaign}', [CrmController::class, 'viewCampaign'])->name('campaigns.view');
                Route::get('/campaigns/{campaign}/analytics', [CrmController::class, 'analytics'])->name('campaigns.analytics');

                // Surveys under campaigns
                Route::get('/campaigns/{campaign}/surveys/create', [CrmController::class, 'createSurvey'])->name('campaigns.surveys.create');
                Route::post('/campaigns/{campaign}/surveys/store', [CrmController::class, 'storeSurvey'])->name('campaigns.surveys.store');
                Route::get('/campaigns/{campaign}/surveys/export', [CrmController::class, 'exportSurveys'])->name('campaigns.surveys.export');

                // Leads pages
                Route::get('/leads', [CrmController::class, 'leads'])->name('leads.index');
                Route::get('/leads/create', [CrmController::class, 'createLead'])->name('leads.create');
                Route::get('/leads/{lead}', [CrmController::class, 'viewLead'])->name('leads.view');

                // Reports export
                Route::get('reports/export/{type}/{format}', [CrmController::class, 'exportReport'])
                    ->name('reports.export')
                    ->where(['type' => 'leads|campaigns|contacts', 'format' => 'xlsx|csv|pdf']);
            });

            // Quick session debug
            Route::get('/debug-session', function () {
                return response()->json([
                    'active_business_slug' => session('active_business_slug'),
                    'active_role' => session('active_role'),
                ]);
            });
        });

    // The dashboard landing page ("business.index") needs to be reachable
    // by EVERY business role, not just super-admin/krest-admin - it's each
    // role's actual home route after login (getRedirectUrlForRole(),
    // RoleHomeRouteService). It used to be registered ONLY under
    // role:super-admin|krest-admin (split out from the business-admin|
    // business-hr|... group below to fix super-admin/krest-admin getting
    // 403'd here - see git history) which silently swapped the bug for
    // its mirror image: every OTHER role got 403'd here instead, and once
    // 403s started redirecting to each role's own home (also
    // business.index) that became an infinite redirect loop. One
    // combined role list covers everyone who's actually meant to land
    // here; EnsureCorrectRole's own access.dashboard permission check
    // still correctly turns business-hr/restricted-hr/head-of-department/
    // chief-of-staff away toward their real home instead.
    Route::middleware(['ensure_role', 'role:super-admin|krest-admin|business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
        });

    // Clients management stays platform-governance only - NOT part of the
    // wider group above, unlike the dashboard landing page.
    Route::middleware(['ensure_role', 'role:super-admin|krest-admin'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/{clientBusiness:slug}', [ClientController::class, 'view'])->name('clients.view');
        });

    // Granting platform-operator access is itself a governance action
    // (same tier as verify/deactivate) - super-admin only, not krest-admin.
      Route::middleware(['ensure_role', 'role:super-admin'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/platform-admins', [PlatformAdminController::class, 'index'])->name('platform-admins.index');
            Route::post('/platform-admins', [PlatformAdminController::class, 'store'])->name('platform-admins.store');
            Route::post('/platform-admins/{userId}/revoke', [PlatformAdminController::class, 'destroy'])->name('platform-admins.revoke');
        });
});

Route::middleware(['ensure_role', 'role:business-employee'])
    ->name('myaccount.')
    ->prefix('myaccount/{business:slug}')
    ->group(function () {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('index');

        Route::get('update-details', [EmployeeDashboardController::class, 'updateDetails'])->name('update');
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
        Route::get('my-team', [OrganogramController::class, 'myTeam'])->name('my-team');

        Route::prefix('performance')->name('performance.')->group(function () {
            Route::get('/', [PerformanceController::class, 'myPerformance'])->name('index');
            Route::get('/cycles/active', [PerformanceController::class, 'fetchActiveCycles'])->name('cycles.active');
            Route::get('/employees/{employee}', [PerformanceController::class, 'employeePerformance'])->name('employee');
            Route::get('/employees/{employee}/objectives', [PerformanceController::class, 'fetchObjectives'])->name('objectives.fetch');
            Route::post('/employees/{employee}/objectives', [PerformanceController::class, 'storeObjective'])->name('objectives.store');
            Route::get('/employees/{employee}/kpis', [PerformanceController::class, 'fetchKpisForEmployee'])->name('kpis.for-employee');
            Route::get('/objectives/cascade', [PerformanceController::class, 'fetchCascadeObjectives'])->name('objectives.cascade');
            Route::get('/objectives/critical', [PerformanceController::class, 'fetchCriticalObjectives'])->name('objectives.critical');
            Route::post('/objectives/{objective}/approve-alignment', [PerformanceController::class, 'approveAlignment'])->name('objectives.approve-alignment');
            Route::post('/objectives/{objective}/decline-alignment', [PerformanceController::class, 'declineAlignment'])->name('objectives.decline-alignment');
            Route::post('/objectives/{objective}/key-results', [PerformanceController::class, 'storeKeyResult'])->name('key-results.store');
            Route::post('/key-results/{keyResult}/progress', [PerformanceController::class, 'updateKeyResultProgress'])->name('key-results.progress');

            Route::get('/cycles/{cycle}/employees/{employee}/review', [PerformanceController::class, 'fetchReview'])->name('review.fetch');
            Route::post('/reviews/{review}/self-assessment', [PerformanceController::class, 'submitSelfAssessment'])->name('review.self');
            Route::post('/reviews/{review}/manager-assessment', [PerformanceController::class, 'submitManagerAssessment'])->name('review.manager');

            Route::get('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'fetchForSubject'])->name('feedback.fetch');
            Route::post('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'store'])->name('feedback.store');
            Route::get('/feedback/inbox', [PerformanceFeedbackController::class, 'fetchMyInbox'])->name('feedback.inbox');
            Route::post('/feedback/{feedbackRequest}/decline', [PerformanceFeedbackController::class, 'decline'])->name('feedback.decline');
            Route::post('/feedback/{feedbackRequest}/response', [PerformanceFeedbackController::class, 'submitResponse'])->name('feedback.respond');
        });

        Route::post('/disciplinary/{id}/acknowledge', [\App\Http\Controllers\WarningController::class, 'acknowledge'])->name('disciplinary.acknowledge');

        Route::prefix('delegations')->name('delegations.')->group(function () {
            Route::get('/', [LeaveDelegationController::class, 'myDelegations'])->name('index');
            Route::post('/{delegation}/accept', [LeaveDelegationController::class, 'accept'])->name('accept');
            Route::post('/{delegation}/decline', [LeaveDelegationController::class, 'decline'])->name('decline');
        });

        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/calendar', [LeaveCalendarController::class, 'employeeCalendar'])->name('calendar');
            Route::get('/calendar/events', [LeaveCalendarController::class, 'employeeEvents'])->name('calendar.events');
            Route::get('/requests', [EmployeeDashboardController::class, 'viewLeaves'])->name('requests.index');
            Route::get('/requests/create', [EmployeeDashboardController::class, 'requestLeave'])->name('requests.create');
            Route::get('/view/{leave}', [EmployeeDashboardController::class, 'leaveApplication'])->name('show');
            Route::post('/upload-document', [LeaveRequestController::class, 'uploadDocument'])->name('upload-document');
        });

        Route::prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/', [EmployeeDashboardController::class, 'attendances'])->name('index');
            Route::get('clock-in-out', [EmployeeDashboardController::class, 'clockInOut'])->name('clock-in-out.index');
        });

        Route::prefix('overtime')->name('overtime.')->group(function () {
            Route::get('/', [DashboardController::class, 'overtime'])->name('index');
            Route::get('/rates', [DashboardController::class, 'overtimeRates'])->name('rates');
        });

        Route::get('absenteeism', [DashboardController::class, 'absenteeism'])->name('absenteeism.index');

        Route::get('/attendance', [EmployeeDashboardController::class, 'checkIn'])->name('attendance');

        Route::get('/p9', [EmployeeDashboardController::class, 'viewP9Forms'])->name('p9.index');
        Route::get('/p9/download', [EmployeeDashboardController::class, 'downloadP9'])->name('p9');

        Route::get('/payslips', [EmployeeDashboardController::class, 'viewPayslips'])->name('payslips');

        Route::get('/payslips/download/{id}', [EmployeeDashboardController::class, 'downloadPayslip'])->name('payslips.download');

        Route::middleware('auth')->group(function () {
            Route::get('/account-settings', [EmployeeDashboardController::class, 'accountSettings'])->name('account.settings');
        });

        Route::get('/notifications', [EmployeeDashboardController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{notification}/read', [EmployeeDashboardController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [EmployeeDashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
    });

Route::get('/test-log', function () {
    Log::info('✅ Test log working.');
    return 'Logged!';
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::name('setup.')->prefix('setup')->group(function () {
        // business-admin only: this is also the "Add Business" entry
        // point for accounts that already own a business (see
        // navbar.blade.php). RegisteredUserController::store() grants
        // business-admin at signup, before a first-time user ever
        // reaches here, so this doesn't block onboarding.
        Route::middleware(['role:business-admin'])->get('business', [BusinessController::class, 'create'])->name('business');
        Route::get('modules', [ModuleController::class, 'create'])->name('modules');
    });

    Route::get('/payroll-template/csv', [PayrollController::class, 'downloadCsvTemplate'])->name('payroll-template.csv');
    Route::get('/payroll-template/xlsx', [PayrollController::class, 'downloadXlsxTemplate'])->name('payroll-template.xlsx');

    Route::middleware('auth')->get('/dashboard', [BusinessController::class, 'redirectToDashboard'])->name('dashboard');
});

// Support (common)
Route::middleware(['auth'])->group(function () {
    Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance|business-employee'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/support', [SupportController::class, 'index'])->name('support.index');
            Route::post('/support/fetch', [SupportController::class, 'fetch'])->name('support.fetch');
            Route::post('/support/store', [SupportController::class, 'store'])->name('support.store');
            Route::post('/support/{issueId}/mark-solved', [SupportController::class, 'markSolved'])->name('support.mark-solved');
        });
});

// auth only - deliberately NOT VerifyBusiness (that's what redirects HERE
// when a business isn't verified; adding it back would loop). Without
// auth, this crashed for anyone who reached it without a live session
// (auth()->user()->id on a null user in the shared app layout) instead of
// bouncing them to login first.
Route::middleware('auth')
    ->get('business/{business:slug}/activate', [BusinessController::class, 'activate'])
    ->name('business.activate');

// Short link routes
Route::get('/campaign/{slug}', [CrmController::class, 'handleShortLink'])->name('short.link');
Route::post('/campaign/{slug}/submit', [CrmController::class, 'submitSurvey'])->name('short.link.submit');
Route::get('/campaign/{slug}/skip', [CrmController::class, 'skipShortLink'])->name('short.link.skip');

// Public survey routes
Route::prefix('surveys')->name('surveys.public.')->group(function () {
    Route::get('/{survey}', [PublicSurveyController::class, 'show'])->name('show');
    Route::post('/{survey}/submit', [PublicSurveyController::class, 'submit'])->name('submit');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/requests.php';

// Temporary route for testing leave type edit page
Route::get('/test-leave-types/{slug}/edit', function ($slug) {
    return "Edit page for $slug";
});

Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance'])
    ->name('business.')
    ->prefix('business/{business:slug}')
    ->group(function () {

        Route::get('/employees/{employee}/payment-details', [EmployeeController::class, 'editPaymentDetails'])
            ->name('employees.payment-details.edit');
        Route::post('/employees/{employee}/payment-details', [EmployeeController::class, 'storePaymentDetails'])
            ->name('employees.payment-details.store');
    });
