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
use App\Http\Controllers\LeaveEncashmentController;
use App\Http\Controllers\LeaveEntitlementController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\OrganogramController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\LeaveDelegationController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\PerformanceFeedbackController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\DisciplinaryReportController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OffboardingReportController;
use App\Http\Controllers\PerformanceReportController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\CourseMandateController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningReportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectReportController;
use App\Http\Controllers\ProjectTaskCategoryController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectTaskStatusController;
use App\Http\Controllers\ProjectTimeLogController;
use App\Http\Controllers\AttendancePolicyController;
use App\Http\Controllers\BiometricDeviceController;
use App\Http\Controllers\DevicePushController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\MandatoryLeavePeriodController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\BusinessCurrencyController;

use App\Models\Business;

Route::get('api/jobs/openings', [JobPostController::class, 'fetchPublic'])->name('jobs.openings');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    $modules = \App\Models\Module::orderBy('id')->get();
    $coreHrIndex = $modules->search(fn ($m) => $m->slug === 'core-hr-management');
    $leaveManagement = (object) ['id' => 'leave-management', 'slug' => 'leave-management', 'name' => 'Leave Management'];
    if ($coreHrIndex === false) {
        $modules->push($leaveManagement);
    } else {
        $modules->splice($coreHrIndex + 1, 0, [$leaveManagement]);
    }

    return view('welcome', ['modules' => $modules->values()]);
})->name('welcome');

Route::any('device-webhook/{vendor}/{token}', [DevicePushController::class, 'handle'])->name('device.webhook');

Route::any('iclock/cdata', [DevicePushController::class, 'zktecoCdata'])->name('device.zkteco.cdata');
Route::any('iclock/getrequest', [DevicePushController::class, 'zktecoGetRequest'])->name('device.zkteco.getrequest');
Route::any('iclock/devicecmd', [DevicePushController::class, 'zktecoDeviceCmd'])->name('device.zkteco.devicecmd');

Route::get('/business/{businessSlug}/api-token', [BusinessController::class, 'showApiTokenForm'])
    ->middleware('auth')
    ->name('business.api-token');

Route::middleware(['auth', \App\Http\Middleware\VerifyBusiness::class, \App\Http\Middleware\EnsureTwoFactorAuthenticated::class])
    ->post('/ajax/leave/remaining-days', [LeaveTypeController::class, 'getRemainingDaysAjax'])
    ->name('ajax.leave.remaining-days');
Route::middleware(['auth', \App\Http\Middleware\VerifyBusiness::class, \App\Http\Middleware\EnsureTwoFactorAuthenticated::class])->group(function () {

    Route::post('/switch-role', [RoleSwitchController::class, 'switchRole'])->name('switch.role');
    Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');

    Route::name('setup.')->prefix('setup')->group(function () {
        Route::get('business', [BusinessController::class, 'create'])->name('business');
        Route::get('modules', [ModuleController::class, 'create'])->name('modules');
        Route::post('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('business.attendance.settings.update');
        Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');
    });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff|module.payroll-management.view'])
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

            Route::get('/locations', [DashboardController::class, 'locations'])->name('locations.index');
            Route::get('/organization-setup', [BusinessController::class, 'setup'])->name('organization-setup');

            Route::get('/pay-schedule', [DashboardController::class, 'paySchedule'])->name('pay-schedule');

            Route::get('/departments', [DashboardController::class, 'departments'])->name('departments.index');

    Route::post('roles/update-departments', [RoleController::class, 'updateDepartments'])->name('roles.update-departments');

            Route::get('/disciplinary/reports/cases/preview', [DisciplinaryReportController::class, 'casesPreview'])->name('disciplinary.reports.cases.preview');
            Route::get('/disciplinary/reports/cases/download', [DisciplinaryReportController::class, 'casesDownload'])->name('disciplinary.reports.cases.download');
            Route::get('/disciplinary/reports/{warning}/escalation-trail/preview', [DisciplinaryReportController::class, 'escalationTrailPreview'])->name('disciplinary.reports.escalation-trail.preview');
            Route::get('/disciplinary/reports/{warning}/escalation-trail/download', [DisciplinaryReportController::class, 'escalationTrailDownload'])->name('disciplinary.reports.escalation-trail.download');
            Route::get('/job-categories', [DashboardController::class, 'jobCategories'])->name('job-categories.index');
            Route::get('/shifts', [DashboardController::class, 'shifts'])->name('shifts.index');
            Route::get('/roster', [DashboardController::class, 'roster'])->name('roster.index');

            Route::get('profile', [ProfileController::class, 'edit'])->name('profile.index');

      Route::prefix('settings/currencies')->name('currencies.')->group(function () {
    Route::get('/',              [BusinessCurrencyController::class, 'index'])->name('index');
    Route::get('/list',          [BusinessCurrencyController::class, 'list'])->name('list');
    Route::get('/known',         [BusinessCurrencyController::class, 'knownCurrencies'])->name('known');
    Route::post('/refresh-all',  [BusinessCurrencyController::class, 'refreshAllRates'])->name('refresh-all');
    Route::post('/',             [BusinessCurrencyController::class, 'store'])->name('store');
    Route::delete('/bulk',       [BusinessCurrencyController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/{id}',          [BusinessCurrencyController::class, 'show'])->name('show');
    Route::put('/{id}',          [BusinessCurrencyController::class, 'update'])->name('update');
    Route::delete('/{id}',       [BusinessCurrencyController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/refresh', [BusinessCurrencyController::class, 'refreshRate'])->name('refresh');
});

            Route::get('/debug-session', function () {
                return response()->json([
                    'active_business_slug' => session('active_business_slug'),
                    'active_role' => session('active_role'),
                ]);
            });
        });

    Route::middleware(['ensure_role', 'role:business-admin|business-hr'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff|module.leave-management.view|module.leave-management.create|module.leave-management.edit|module.leave-management.delete|module.leave-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {

            Route::prefix('leave')->name('leave.')->group(function () {
                Route::get('/calendar', [LeaveCalendarController::class, 'businessCalendar'])->name('calendar');
                Route::get('/calendar/events', [LeaveCalendarController::class, 'businessEvents'])->name('calendar.events');
                Route::get('/calendar/team-headcount', [LeaveCalendarController::class, 'teamHeadcount'])->name('calendar.team-headcount');
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

                Route::post('/revoke', [LeaveRequestController::class, 'revoke'])->name('revoke');
                Route::get('/{reference}/download', [LeaveRequestController::class, 'downloadPdf'])->name('download')->where('reference', '[A-Za-z0-9\-]+');
                Route::get('/leave-types/{slug}/edit', [LeaveTypeController::class, 'edit'])->name('leave-types.edit');
                Route::delete('/leave-types/delete', [LeaveTypeController::class, 'destroy'])->name('leave-types.delete');
                Route::post('/leave-types/remaining-days', [LeaveTypeController::class, 'getRemainingDays'])->name('leave-types.remaining-days');
                Route::post('/leave-types/update', [LeaveTypeController::class, 'update'])->name('leave-types.update');
                Route::post('/upload-document', [LeaveRequestController::class, 'uploadDocument'])->name('upload-document');
                Route::post('/status', [LeaveRequestController::class, 'status'])->name('status');
            });

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
                Route::post('/employees/filter', [EmployeeController::class, 'filter'])->name('leave-entitlements.employees.filter');
                Route::post('/adjust', [LeaveEntitlementController::class, 'adjust'])->name('leave-entitlements.adjust');
                Route::post('/process-carryover', [LeaveEntitlementController::class, 'processCarryover'])->name('leave-entitlements.process-carryover');
                Route::get('/export-pdf', [LeaveEntitlementController::class, 'exportPdf'])->name('leave-entitlements.export-pdf');
            });

            Route::prefix('leave')->name('leave.')->group(function () {
                Route::get('/reports', [LeaveReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/balance/preview', [LeaveReportController::class, 'balancePreview'])->name('reports.balance.preview');
                Route::get('/reports/balance/download', [LeaveReportController::class, 'balanceDownload'])->name('reports.balance.download');
                Route::get('/reports/full/preview', [LeaveReportController::class, 'fullPreview'])->name('reports.full.preview');
                Route::get('/reports/full/download', [LeaveReportController::class, 'fullDownload'])->name('reports.full.download');
                Route::get('/reports/types/preview', [LeaveReportController::class, 'typesPreview'])->name('reports.types.preview');
                Route::get('/reports/types/download', [LeaveReportController::class, 'typesDownload'])->name('reports.types.download');
                Route::get('/reports/per-member/preview', [LeaveReportController::class, 'perMemberPreview'])->name('reports.per-member.preview');
                Route::get('/reports/per-member/download', [LeaveReportController::class, 'perMemberDownload'])->name('reports.per-member.download');
                Route::get('/reports/master/preview', [LeaveReportController::class, 'masterPreview'])->name('reports.master.preview');
                Route::get('/reports/master/download', [LeaveReportController::class, 'masterDownload'])->name('reports.master.download');

                Route::get('/encashments/fetch', [LeaveEncashmentController::class, 'fetch'])->name('encashments.fetch');
                Route::post('/encashments/store', [LeaveEncashmentController::class, 'store'])->name('encashments.store');
                Route::middleware(['role_or_permission_or_impersonation:business-admin|business-hr|restricted-hr|module.leave-management.approve'])->group(function () {
                    Route::post('/encashments/{encashment}/approve', [LeaveEncashmentController::class, 'approve'])->name('encashments.approve');
                    Route::post('/encashments/{encashment}/reject', [LeaveEncashmentController::class, 'reject'])->name('encashments.reject');
                    Route::post('/encashments/{encashment}/mark-disbursed', [LeaveEncashmentController::class, 'markDisbursed'])->name('encashments.mark-disbursed');
                });
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.organization-structure.view|module.organization-structure.create|module.organization-structure.edit|module.organization-structure.delete|module.organization-structure.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::prefix('organization-structure')->name('organization-structure.')->group(function () {
                Route::get('/', [OrganizationStructureController::class, 'index'])->name('index');
                Route::get('/overview', [OrganizationStructureController::class, 'fetchOverview'])->name('overview.fetch');
                Route::get('/roles', [OrganizationStructureController::class, 'fetchRoles'])->name('roles.fetch');
                Route::get('/role-tree', [OrganizationStructureController::class, 'fetchRoleTree'])->name('role-tree.fetch');
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

                Route::get('/canvas', [OrganizationStructureController::class, 'canvasGraph'])->name('canvas.graph');
                Route::post('/canvas/nodes/{node}/position', [OrganizationStructureController::class, 'updateCanvasNodePosition'])->name('canvas.nodes.position');
                Route::post('/canvas/edges', [OrganizationStructureController::class, 'storeCanvasEdge'])->name('canvas.edges.store');
                Route::delete('/canvas/edges/{edgeId}', [OrganizationStructureController::class, 'destroyCanvasEdge'])->name('canvas.edges.destroy');
            });

            Route::prefix('organogram')->name('organogram.')->group(function () {
                Route::get('/', [OrganogramController::class, 'index'])->name('index');
                Route::get('/fetch', [OrganogramController::class, 'fetch'])->name('fetch');
                Route::get('/table', [OrganogramController::class, 'table'])->name('table');
                Route::get('/search', [OrganogramController::class, 'search'])->name('search');
                Route::get('/filter-options', [OrganogramController::class, 'filterOptions'])->name('filter-options');
                Route::get('/employee-options', [OrganogramController::class, 'employeeOptions'])->name('employee-options');
                Route::post('/assign-manager', [OrganogramController::class, 'assignManager'])->name('assign-manager');
                Route::post('/reset-manager', [OrganogramController::class, 'resetManagerToTemplate'])->name('reset-manager');
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff|module.employee-management.view|module.employee-management.create|module.employee-management.edit|module.employee-management.delete|module.employee-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/employees', [DashboardController::class, 'employees'])->name('employees.index');
            Route::get('/employees/import', [DashboardController::class, 'importEmployees'])->name('employees.import');
            Route::get('/employees/warning', [DashboardController::class, 'warning'])->name('employees.warning');
            Route::get('/employees/warning/{warning}', [\App\Http\Controllers\WarningController::class, 'show'])->name('employees.warning.show');
            Route::get('/employees/contracts', [DashboardController::class, 'contracts'])->name('employees.contracts');
            Route::get('/employees/download-csv-template', [EmployeeController::class, 'downloadCsvTemplate'])->name('employees.downloadCsvTemplate');
            Route::get('/employees/download-xlsx-template', [EmployeeController::class, 'downloadXlsxTemplate'])->name('employees.downloadXlsxTemplate');

            Route::prefix('employees/{employee}/career-events')->name('employees.career-events.')->group(function () {
                Route::get('/fetch', [\App\Http\Controllers\EmployeeCareerEventController::class, 'fetch'])->name('fetch');
                Route::post('/store', [\App\Http\Controllers\EmployeeCareerEventController::class, 'store'])->name('store');
                Route::delete('/{careerEvent}', [\App\Http\Controllers\EmployeeCareerEventController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('career-growth')->name('career-growth.')->group(function () {
                Route::get('/', [\App\Http\Controllers\EmployeeCareerEventController::class, 'index'])->name('index');
                Route::get('/fetch', [\App\Http\Controllers\EmployeeCareerEventController::class, 'businessFetch'])->name('fetch');
            });

            Route::prefix('offboarding')->name('offboarding.')->group(function () {
                Route::get('/', [OffboardingController::class, 'index'])->name('index');
                Route::post('/{checklistId}/tasks', [OffboardingController::class, 'storeTask'])->name('tasks.store');
                Route::post('/{checklistId}/tasks/{task}', [OffboardingController::class, 'updateTask'])->name('tasks.update');
                Route::delete('/{checklistId}/tasks/{task}', [OffboardingController::class, 'destroyTask'])->name('tasks.destroy');

                Route::get('/reports/status/preview', [OffboardingReportController::class, 'statusPreview'])->name('reports.status.preview');
                Route::get('/reports/status/download', [OffboardingReportController::class, 'statusDownload'])->name('reports.status.download');
                Route::get('/{checklist}/clearance-summary/preview', [OffboardingReportController::class, 'clearanceSummaryPreview'])->name('reports.clearance-summary.preview');
                Route::get('/{checklist}/clearance-summary/download', [OffboardingReportController::class, 'clearanceSummaryDownload'])->name('reports.clearance-summary.download');
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff|module.attendance.view|module.attendance.create|module.attendance.edit|module.attendance.delete|module.attendance.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
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

                Route::get('/reports', [AttendanceReportController::class, 'index'])->name('attendances.reports.index');
                Route::get('/reports/daily/preview', [AttendanceReportController::class, 'dailyPreview'])->name('attendances.reports.daily.preview');
                Route::get('/reports/daily/download', [AttendanceReportController::class, 'dailyDownload'])->name('attendances.reports.daily.download');
                Route::get('/reports/full/preview', [AttendanceReportController::class, 'fullPreview'])->name('attendances.reports.full.preview');
                Route::get('/reports/full/download', [AttendanceReportController::class, 'fullDownload'])->name('attendances.reports.full.download');
                Route::get('/reports/summary/preview', [AttendanceReportController::class, 'summaryPreview'])->name('attendances.reports.summary.preview');
                Route::get('/reports/summary/download', [AttendanceReportController::class, 'summaryDownload'])->name('attendances.reports.summary.download');
                Route::get('/reports/monthly/preview', [AttendanceReportController::class, 'monthlyPreview'])->name('attendances.reports.monthly.preview');
                Route::get('/reports/monthly/download', [AttendanceReportController::class, 'monthlyDownload'])->name('attendances.reports.monthly.download');
                Route::get('/reports/lateness/preview', [AttendanceReportController::class, 'latenessPreview'])->name('attendances.reports.lateness.preview');
                Route::get('/reports/lateness/download', [AttendanceReportController::class, 'latenessDownload'])->name('attendances.reports.lateness.download');
                Route::get('/reports/absent/preview', [AttendanceReportController::class, 'absentPreview'])->name('attendances.reports.absent.preview');
                Route::get('/reports/absent/download', [AttendanceReportController::class, 'absentDownload'])->name('attendances.reports.absent.download');
                Route::get('/reports/overtime/preview', [AttendanceReportController::class, 'overtimePreview'])->name('attendances.reports.overtime.preview');
                Route::get('/reports/overtime/download', [AttendanceReportController::class, 'overtimeDownload'])->name('attendances.reports.overtime.download');
                Route::get('/reports/per-member/preview', [AttendanceReportController::class, 'perMemberPreview'])->name('attendances.reports.per-member.preview');
                Route::get('/reports/per-member/download', [AttendanceReportController::class, 'perMemberDownload'])->name('attendances.reports.per-member.download');

                Route::get('/policies', [AttendancePolicyController::class, 'fetch'])->name('attendances.policies.fetch');
                Route::post('/policies', [AttendancePolicyController::class, 'store'])->name('attendances.policies.store');
                Route::delete('/policies/{policy}', [AttendancePolicyController::class, 'destroy'])->name('attendances.policies.destroy');

                Route::get('/devices', [BiometricDeviceController::class, 'fetch'])->name('attendances.devices.fetch');
                Route::post('/devices', [BiometricDeviceController::class, 'store'])->name('attendances.devices.store');
                Route::post('/devices/{device}', [BiometricDeviceController::class, 'update'])->name('attendances.devices.update');
                Route::post('/devices/{device}/toggle', [BiometricDeviceController::class, 'toggleActive'])->name('attendances.devices.toggle');
                Route::post('/devices/{device}/regenerate-token', [BiometricDeviceController::class, 'regenerateToken'])->name('attendances.devices.regenerate-token');
                Route::delete('/devices/{device}', [BiometricDeviceController::class, 'destroy'])->name('attendances.devices.destroy');
                Route::get('/devices/{device}/enrollments', [BiometricDeviceController::class, 'enrollments'])->name('attendances.devices.enrollments.fetch');
                Route::post('/devices/{device}/enrollments', [BiometricDeviceController::class, 'storeEnrollment'])->name('attendances.devices.enrollments.store');
                Route::delete('/devices/{device}/enrollments/{enrollment}', [BiometricDeviceController::class, 'destroyEnrollment'])->name('attendances.devices.enrollments.destroy');
            });

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
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.performance-management.view|module.performance-management.create|module.performance-management.edit|module.performance-management.delete|module.performance-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::prefix('performance')->name('performance.')->group(function () {
                Route::get('/setup', [PerformanceController::class, 'setupIndex'])->name('setup.index');
                Route::get('/cycles', [PerformanceController::class, 'cyclesIndex'])->name('cycles.index');
                Route::get('/cycles/fetch', [PerformanceController::class, 'fetchCycles'])->name('cycles.fetch');
                Route::post('/cycles', [PerformanceController::class, 'storeCycle'])->name('cycles.store');
                Route::post('/cycles/{cycle}/status', [PerformanceController::class, 'updateCycleStatus'])->name('cycles.status');

                Route::get('/objectives', [PerformanceController::class, 'objectivesOverview'])->name('objectives.index');
                Route::get('/objectives/overview-fetch', [PerformanceController::class, 'fetchObjectivesOverview'])->name('objectives.overview-fetch');

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

                Route::get('/reports', [PerformanceReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/cycle/preview', [PerformanceReportController::class, 'cyclePreview'])->name('reports.cycle.preview');
                Route::get('/reports/cycle/download', [PerformanceReportController::class, 'cycleDownload'])->name('reports.cycle.download');
                Route::get('/reports/three-sixty/preview', [PerformanceReportController::class, 'threeSixtyPreview'])->name('reports.three-sixty.preview');
                Route::get('/reports/three-sixty/download', [PerformanceReportController::class, 'threeSixtyDownload'])->name('reports.three-sixty.download');

                Route::get('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'fetchForSubject'])->name('feedback.fetch');
                Route::post('/employees/{employee}/feedback', [PerformanceFeedbackController::class, 'store'])->name('feedback.store');
                Route::get('/feedback/inbox', [PerformanceFeedbackController::class, 'fetchMyInbox'])->name('feedback.inbox');
                Route::post('/feedback/{feedbackRequest}/decline', [PerformanceFeedbackController::class, 'decline'])->name('feedback.decline');
                Route::post('/feedback/{feedbackRequest}/response', [PerformanceFeedbackController::class, 'submitResponse'])->name('feedback.respond');

                Route::prefix('tasks')->name('tasks.')->group(function () {
                    Route::get('/', [DashboardController::class, 'tasks'])->name('index');
                    Route::get('/progress/{task}', [DashboardController::class, 'progress'])->name('progress');
                });
                Route::prefix('kpis')->name('kpis.')->group(function () {
                    Route::get('/', [KPIsController::class, 'index'])->name('index');
                    Route::get('/create', [KPIsController::class, 'create'])->name('create');
                    Route::get('/results', [KPIsController::class, 'results'])->name('results');
                    Route::get('/edit', [KPIsController::class, 'edit'])->name('edit');
                });
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.asset-management.view|module.asset-management.create|module.asset-management.edit|module.asset-management.delete|module.asset-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::middleware('ensure_module:asset-management')->prefix('assets')->name('assets.')->group(function () {
                Route::get('/', [AssetController::class, 'index'])->name('index');
                Route::get('/fetch', [AssetController::class, 'fetch'])->name('fetch');
                Route::post('/store', [AssetController::class, 'store'])->name('store');
                Route::post('/{asset}/update', [AssetController::class, 'update'])->name('update');
                Route::delete('/{asset}', [AssetController::class, 'destroy'])->name('destroy');
                Route::post('/{asset}/assign', [AssetController::class, 'assign'])->name('assign');
                Route::post('/{asset}/return', [AssetController::class, 'returnAsset'])->name('return');
                Route::get('/employees/{employee}/assigned', [AssetController::class, 'employeeAssets'])->name('employee-assets');

                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/', [AssetReportController::class, 'index'])->name('index');
                    Route::get('/register/preview', [AssetReportController::class, 'registerPreview'])->name('register.preview');
                    Route::get('/register/download', [AssetReportController::class, 'registerDownload'])->name('register.download');
                });
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.learning-management.view|module.learning-management.create|module.learning-management.edit|module.learning-management.delete|module.learning-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::middleware('ensure_module:learning-management')->prefix('learning')->name('learning.')->group(function () {
                Route::get('/', [LearningController::class, 'index'])->name('index');
                Route::get('/courses/fetch', [LearningController::class, 'fetchCourses'])->name('courses.fetch');
                Route::get('/courses/options', [LearningController::class, 'courseOptions'])->name('courses.options');
                Route::post('/courses/store', [LearningController::class, 'storeCourse'])->name('courses.store');
                Route::post('/courses/{course}/update', [LearningController::class, 'updateCourse'])->name('courses.update');
                Route::delete('/courses/{course}', [LearningController::class, 'destroyCourse'])->name('courses.destroy');

                Route::get('/courses/{course}/sessions/fetch', [LearningController::class, 'fetchSessions'])->name('sessions.fetch');
                Route::post('/courses/{course}/sessions/store', [LearningController::class, 'storeSession'])->name('sessions.store');
                Route::post('/sessions/{courseSession}/update', [LearningController::class, 'updateSession'])->name('sessions.update');
                Route::delete('/sessions/{courseSession}', [LearningController::class, 'destroySession'])->name('sessions.destroy');

                Route::get('/enrollments/fetch', [LearningController::class, 'fetchEnrollments'])->name('enrollments.fetch');
                Route::post('/courses/{course}/enrollments/store', [LearningController::class, 'storeEnrollment'])->name('enrollments.store');
                Route::post('/enrollments/{enrollment}/update', [LearningController::class, 'updateEnrollment'])->name('enrollments.update');
                Route::delete('/enrollments/{enrollment}', [LearningController::class, 'destroyEnrollment'])->name('enrollments.destroy');

                Route::get('/reports', [LearningReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/completions/preview', [LearningReportController::class, 'completionsPreview'])->name('reports.completions.preview');
                Route::get('/reports/completions/download', [LearningReportController::class, 'completionsDownload'])->name('reports.completions.download');

                Route::get('/categories/fetch', [CourseCategoryController::class, 'fetch'])->name('categories.fetch');
                Route::post('/categories/store', [CourseCategoryController::class, 'store'])->name('categories.store');
                Route::post('/categories/{category}/update', [CourseCategoryController::class, 'update'])->name('categories.update');
                Route::delete('/categories/{category}', [CourseCategoryController::class, 'destroy'])->name('categories.destroy');

                Route::get('/mandates/fetch', [CourseMandateController::class, 'fetch'])->name('mandates.fetch');
                Route::post('/mandates/store', [CourseMandateController::class, 'store'])->name('mandates.store');
                Route::post('/mandates/{mandate}/update', [CourseMandateController::class, 'update'])->name('mandates.update');
                Route::delete('/mandates/{mandate}', [CourseMandateController::class, 'destroy'])->name('mandates.destroy');

                Route::post('/settings/update', [LearningController::class, 'updateSettings'])->name('settings.update');
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.project-management.view|module.project-management.create|module.project-management.edit|module.project-management.delete|module.project-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::middleware('ensure_module:project-management')->prefix('projects')->name('projects.')->group(function () {
                Route::get('/', [ProjectController::class, 'index'])->name('index');
                Route::get('/fetch', [ProjectController::class, 'fetch'])->name('fetch');
                Route::get('/options', [ProjectController::class, 'options'])->name('options');
                Route::post('/store', [ProjectController::class, 'store'])->name('store');
                Route::post('/{project}/update', [ProjectController::class, 'update'])->name('update');
                Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
                Route::post('/settings/update', [ProjectController::class, 'updateSettings'])->name('settings.update');

                Route::get('/{project}/board', [ProjectController::class, 'showBoard'])->name('board');
                Route::get('/{project}/board/fetch', [ProjectTaskController::class, 'board'])->name('board.fetch');
                Route::post('/{project}/board/reorder', [ProjectTaskController::class, 'reorder'])->name('board.reorder');

                Route::post('/{project}/tasks/store', [ProjectTaskController::class, 'store'])->name('tasks.store');
                Route::post('/tasks/{task}/update', [ProjectTaskController::class, 'update'])->name('tasks.update');
                Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');
                Route::get('/tasks/{task}/comments/fetch', [ProjectTaskController::class, 'fetchComments'])->name('tasks.comments.fetch');
                Route::post('/tasks/{task}/comments/store', [ProjectTaskController::class, 'storeComment'])->name('tasks.comments.store');

                Route::get('/{project}/members/fetch', [ProjectMemberController::class, 'fetch'])->name('members.fetch');
                Route::post('/{project}/members/store', [ProjectMemberController::class, 'store'])->name('members.store');
                Route::post('/members/{member}/update', [ProjectMemberController::class, 'update'])->name('members.update');
                Route::delete('/members/{member}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

                Route::get('/{project}/time-logs/fetch', [ProjectTimeLogController::class, 'fetch'])->name('time-logs.fetch');
                Route::post('/{project}/time-logs/store', [ProjectTimeLogController::class, 'store'])->name('time-logs.store');
                Route::delete('/time-logs/{timeLog}', [ProjectTimeLogController::class, 'destroy'])->name('time-logs.destroy');

                Route::get('/reports', [ProjectReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/task-status/preview', [ProjectReportController::class, 'taskStatusPreview'])->name('reports.task-status.preview');
                Route::get('/reports/task-status/download', [ProjectReportController::class, 'taskStatusDownload'])->name('reports.task-status.download');
                Route::get('/reports/time-tracking/preview', [ProjectReportController::class, 'timeTrackingPreview'])->name('reports.time-tracking.preview');
                Route::get('/reports/time-tracking/download', [ProjectReportController::class, 'timeTrackingDownload'])->name('reports.time-tracking.download');

                Route::get('/statuses/fetch', [ProjectTaskStatusController::class, 'fetch'])->name('statuses.fetch');
                Route::post('/statuses/store', [ProjectTaskStatusController::class, 'store'])->name('statuses.store');
                Route::post('/statuses/{status}/update', [ProjectTaskStatusController::class, 'update'])->name('statuses.update');
                Route::delete('/statuses/{status}', [ProjectTaskStatusController::class, 'destroy'])->name('statuses.destroy');
                Route::post('/statuses/reorder', [ProjectTaskStatusController::class, 'reorder'])->name('statuses.reorder');

                Route::get('/task-categories/fetch', [ProjectTaskCategoryController::class, 'fetch'])->name('task-categories.fetch');
                Route::post('/task-categories/store', [ProjectTaskCategoryController::class, 'store'])->name('task-categories.store');
                Route::post('/task-categories/{category}/update', [ProjectTaskCategoryController::class, 'update'])->name('task-categories.update');
                Route::delete('/task-categories/{category}', [ProjectTaskCategoryController::class, 'destroy'])->name('task-categories.destroy');
                Route::post('/task-categories/reorder', [ProjectTaskCategoryController::class, 'reorder'])->name('task-categories.reorder');
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.recruitment-onboarding.view|module.recruitment-onboarding.create|module.recruitment-onboarding.edit|module.recruitment-onboarding.delete|module.recruitment-onboarding.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/job-posts', [JobPostController::class, 'index'])->name('jobs.index');
                Route::get('/job-posts/create', [JobPostController::class, 'create'])->name('jobs.create');
                Route::get('/job-posts/{jobpost}', [JobPostController::class, 'show'])->name('jobs.show');
                Route::get('/job-posts/{jobpost}/edit', [JobPostController::class, 'editView'])->name('jobs.edit');
                Route::get('/interviews', [DashboardController::class, 'interviews'])->name('interviews');
                Route::get('/reports', [ApplicationController::class, 'reports'])->name('reports');
            });

            Route::prefix('applications')->name('applications.')->group(function () {
                Route::get('/', [ApplicationController::class, 'index'])->name('index');
                Route::get('/create', [ApplicationController::class, 'create'])->name('create');
                Route::get('/{application}', [ApplicationController::class, 'view'])->name('view');
            });

            Route::prefix('applicants')->name('applicants.')->group(function () {
                Route::get('/', [ApplicantController::class, 'index'])->name('index');
                Route::get('/create', [ApplicantController::class, 'create'])->name('create');
                Route::get('/{applicant}', [ApplicantController::class, 'view'])->name('view');
                Route::get('/{applicant}/download-document/{mediaId}', [ApplicantController::class, 'downloadDocument'])->name('download-document');
            });
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff|module.payroll-management.view|module.payroll-management.create|module.payroll-management.edit|module.payroll-management.delete|module.payroll-management.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/payroll-formulas', [DashboardController::class, 'payrollFormulas'])->name('payroll-formulas.index');
            Route::get('/payroll-formulas/bracket-template', [PayrollFormulaController::class, 'bracketTemplate'])->name('payroll-formulas.bracket-template');

            Route::get('/deductions', [DashboardController::class, 'deductions'])->name('deductions');

            Route::get('/payroll', [DashboardController::class, 'payroll'])->name('payroll.index');
            Route::get('/payroll/all', [DashboardController::class, 'payrollAll'])->name('payroll.all');

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

            Route::get('/download-nssf-summary', [PayrollController::class, 'downloadNssfMonthlySummary'])
                ->name('download-nssf-summary');

            Route::get('/download-shif-summary', [PayrollController::class, 'downloadShifMonthlySummary'])
                ->name('download-shif-summary');

            Route::get('/download-nhif-summary', [PayrollController::class, 'downloadNhifMonthlySummary'])
                ->name('download-nhif-summary');

            Route::get('/payroll/nssf/download', [PayrollController::class, 'downloadNssf'])
                ->name('payroll.nssf.download');

            Route::get('/payroll/nssf/monthly-summary', [PayrollController::class, 'downloadNssfMonthlySummaryWithFormat'])
                ->name('payroll.nssf.monthly_summary');

            Route::get('reliefs', [DashboardController::class, 'reliefs'])->name('reliefs.index');
            Route::get('employee-reliefs', [DashboardController::class, 'employeeReliefs'])->name('employee-reliefs.index');

            Route::get('/allowances', [DashboardController::class, 'allowances'])->name('allowances.index');

            Route::get('/advances', [DashboardController::class, 'advances'])->name('advances.index');
            Route::get('/loans', [DashboardController::class, 'loans'])->name('loans.index');

            Route::get('pay-grades', [DashboardController::class, 'payGrades'])->name('pay-grades.index');
        });

    Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|head-of-department|restricted-hr|chief-of-staff|module.crm-integration.view|module.crm-integration.create|module.crm-integration.edit|module.crm-integration.delete|module.crm-integration.approve'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {

            Route::prefix('crm')->name('crm.')->group(function () {

                Route::get('/contacts', [CrmController::class, 'contacts'])->name('contacts.index');
                Route::get('/contacts/create', [CrmController::class, 'createContact'])->name('contacts.create');
                Route::get('/contacts/{submission}', [CrmController::class, 'viewContact'])->name('contacts.view');

                Route::get('/campaigns', [CrmController::class, 'campaigns'])->name('campaigns.index');
                Route::get('/campaigns/create', [CrmController::class, 'createCampaign'])->name('campaigns.create');
                Route::get('/campaigns/{campaign}', [CrmController::class, 'viewCampaign'])->name('campaigns.view');
                Route::get('/campaigns/{campaign}/analytics', [CrmController::class, 'analytics'])->name('campaigns.analytics');

                Route::get('/campaigns/{campaign}/surveys/create', [CrmController::class, 'createSurvey'])->name('campaigns.surveys.create');
                Route::post('/campaigns/{campaign}/surveys/store', [CrmController::class, 'storeSurvey'])->name('campaigns.surveys.store');
                Route::get('/campaigns/{campaign}/surveys/export', [CrmController::class, 'exportSurveys'])->name('campaigns.surveys.export');

                Route::get('/leads', [CrmController::class, 'leads'])->name('leads.index');
                Route::get('/leads/create', [CrmController::class, 'createLead'])->name('leads.create');
                Route::get('/leads/{lead}', [CrmController::class, 'viewLead'])->name('leads.view');

                Route::get('reports/export/{type}/{format}', [CrmController::class, 'exportReport'])
                    ->name('reports.export')
                    ->where(['type' => 'leads|campaigns|contacts', 'format' => 'xlsx|csv|pdf']);
            });
        });

    Route::middleware(['ensure_role', 'role:super-admin|business-admin|business-hr|business-finance|head-of-department|restricted-hr|chief-of-staff'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/setup-progress', [DashboardController::class, 'fetchSetupProgress'])->name('setup-progress.fetch');
            Route::post('/setup-progress/dismiss', [DashboardController::class, 'dismissSetupGuide'])->name('setup-progress.dismiss');
            Route::post('/setup-progress/reopen', [DashboardController::class, 'reopenSetupGuide'])->name('setup-progress.reopen');
        });

    Route::middleware(['ensure_role', 'role:super-admin'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/{clientBusiness:slug}', [ClientController::class, 'view'])->name('clients.view');

        });

    Route::middleware(['ensure_role', 'role:super-admin'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/platform-admins', [PlatformAdminController::class, 'index'])->name('platform-admins.index');
            Route::get('/system-health', [\App\Http\Controllers\SystemHealthController::class, 'index'])->name('system-health.index');
        });

    Route::middleware(['ensure_role', 'role:super-admin|business-admin'])
        ->name('business.clients.')
        ->prefix('business/{business:slug}')
        ->group(function () {
            Route::get('/access/request', [ClientController::class, 'showRequestAccess'])->name('request-access');
            Route::get('/access/grant', [ClientController::class, 'showGrantAccess'])->name('grant-access');
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

        Route::prefix('organogram')->name('organogram.')->group(function () {
            Route::get('employee-options', [OrganogramController::class, 'employeeOptions'])->name('employee-options');
        });

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

        Route::get('/disciplinary', [\App\Http\Controllers\WarningController::class, 'myIndex'])->name('disciplinary.index');
        Route::post('/disciplinary/{id}/acknowledge', [\App\Http\Controllers\WarningController::class, 'acknowledge'])->name('disciplinary.acknowledge');
        Route::post('/disciplinary/{id}/respond', [\App\Http\Controllers\WarningController::class, 'submitResponse'])->name('disciplinary.respond');

        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'myProjects'])->name('index');
            Route::get('/task-categories/fetch', [ProjectTaskCategoryController::class, 'fetch'])->name('task-categories.fetch');

            Route::middleware('ensure_project_member')->group(function () {
                Route::get('/{project}/board', [ProjectController::class, 'showBoard'])->name('board');
                Route::get('/{project}/board/fetch', [ProjectTaskController::class, 'board'])->name('board.fetch');
                Route::post('/{project}/board/reorder', [ProjectTaskController::class, 'reorder'])->name('board.reorder');

                Route::post('/{project}/tasks/store', [ProjectTaskController::class, 'store'])->name('tasks.store');
                Route::post('/tasks/{task}/update', [ProjectTaskController::class, 'update'])->name('tasks.update');
                Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');
                Route::get('/tasks/{task}/comments/fetch', [ProjectTaskController::class, 'fetchComments'])->name('tasks.comments.fetch');
                Route::post('/tasks/{task}/comments/store', [ProjectTaskController::class, 'storeComment'])->name('tasks.comments.store');

                Route::get('/{project}/members/fetch', [ProjectMemberController::class, 'fetch'])->name('members.fetch');
                Route::post('/{project}/members/store', [ProjectMemberController::class, 'store'])->name('members.store');
                Route::delete('/members/{member}', [ProjectMemberController::class, 'destroy'])->name('members.destroy');

                Route::get('/{project}/time-logs/fetch', [ProjectTimeLogController::class, 'fetch'])->name('time-logs.fetch');
                Route::post('/{project}/time-logs/store', [ProjectTimeLogController::class, 'store'])->name('time-logs.store');
                Route::delete('/time-logs/{timeLog}', [ProjectTimeLogController::class, 'destroy'])->name('time-logs.destroy');
            });
        });

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

            Route::get('/reports/daily/preview', [AttendanceReportController::class, 'myDailyPreview'])->name('reports.daily.preview');
            Route::get('/reports/daily/download', [AttendanceReportController::class, 'myDailyDownload'])->name('reports.daily.download');
            Route::get('/reports/monthly/preview', [AttendanceReportController::class, 'myMonthlyPreview'])->name('reports.monthly.preview');
            Route::get('/reports/monthly/download', [AttendanceReportController::class, 'myMonthlyDownload'])->name('reports.monthly.download');
            Route::get('/reports/summary/preview', [AttendanceReportController::class, 'mySummaryPreview'])->name('reports.summary.preview');
            Route::get('/reports/summary/download', [AttendanceReportController::class, 'mySummaryDownload'])->name('reports.summary.download');
        });

        Route::prefix('overtime')->name('overtime.')->group(function () {
            Route::get('/', [DashboardController::class, 'overtime'])->name('index');
            Route::get('/rates', [DashboardController::class, 'overtimeRates'])->name('rates');
        });

        Route::get('absenteeism', [DashboardController::class, 'absenteeism'])->name('absenteeism.index');

        Route::get('/attendance', [EmployeeDashboardController::class, 'clockInOut'])->name('attendance');

        Route::get('/p9', [EmployeeDashboardController::class, 'viewP9Forms'])->name('p9.index');
        Route::get('/p9/download', [EmployeeDashboardController::class, 'downloadP9'])->name('p9');

        Route::get('/payslips', [EmployeeDashboardController::class, 'viewPayslips'])->name('payslips');

        Route::get('/payslips/download/{id}', [EmployeeDashboardController::class, 'downloadPayslip'])->name('payslips.download');

        Route::get('/assets', [EmployeeDashboardController::class, 'myAssets'])->name('assets.index');

        Route::get('/learning', [EmployeeDashboardController::class, 'myLearning'])->name('learning.index');

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

        Route::middleware(['role:business-admin'])->get('business', [BusinessController::class, 'create'])->name('business');
        Route::get('modules', [ModuleController::class, 'create'])->name('modules');
    });

    Route::get('/payroll-template/csv', [PayrollController::class, 'downloadCsvTemplate'])->name('payroll-template.csv');
    Route::get('/payroll-template/xlsx', [PayrollController::class, 'downloadXlsxTemplate'])->name('payroll-template.xlsx');

    Route::middleware('auth')->get('/dashboard', [BusinessController::class, 'redirectToDashboard'])->name('dashboard');
});

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

Route::middleware('auth')
    ->get('business/{business:slug}/activate', [BusinessController::class, 'activate'])
    ->name('business.activate');

Route::get('/campaign/{slug}', [CrmController::class, 'handleShortLink'])->name('short.link');
Route::post('/campaign/{slug}/submit', [CrmController::class, 'submitSurvey'])->name('short.link.submit');
Route::get('/campaign/{slug}/skip', [CrmController::class, 'skipShortLink'])->name('short.link.skip');

Route::prefix('surveys')->name('surveys.public.')->group(function () {
    Route::get('/{survey}', [PublicSurveyController::class, 'show'])->name('show');
    Route::post('/{survey}/submit', [PublicSurveyController::class, 'submit'])->name('submit');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/requests.php';

Route::get('/test-leave-types/{slug}/edit', function ($slug) {
    return "Edit page for $slug";
});

Route::middleware(['ensure_role', 'role_or_permission_or_impersonation:business-admin|business-hr|business-finance|module.employee-management.edit|module.payroll-management.edit'])
    ->name('business.')
    ->prefix('business/{business:slug}')
    ->group(function () {

        Route::get('/employees/{employee}/payment-details', [EmployeeController::class, 'editPaymentDetails'])
            ->name('employees.payment-details.edit');
        Route::post('/employees/{employee}/payment-details', [EmployeeController::class, 'storePaymentDetails'])
            ->name('employees.payment-details.store');
    });
