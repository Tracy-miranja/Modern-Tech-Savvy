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
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\OrganogramController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\HolidayController;

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

    Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance|head-of-department'])
        ->name('location.')
        ->prefix('location/{location:slug}')
        ->group(function () {
            Route::get('/payroll/{id}/download-column/{column}/{format}', [PayrollController::class, 'downloadColumn'])->name('payroll.download_column');
        });

    Route::middleware(['ensure_role', 'role:business-admin|business-hr|business-finance|head-of-department'])
        ->name('business.')
        ->prefix('business/{business:slug}')
        ->group(function () {

            Route::post('/attendance/settings', [AttendanceController::class, 'updateSettings'])->name('attendance.settings.update');
            Route::post('/locations/{location}/coords', [AttendanceController::class, 'updateLocationCoords'])->name('business.locations.coords.update');
            Route::post('/employees/{employee}/mac', [AttendanceController::class, 'updateEmployeeMac'])->name('employees.mac.update');
            //Route::get('/attendance/geocode', [AttendanceController::class, 'geocode'])->name('attendance.geocode');
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/{clientBusiness:slug}', [ClientController::class, 'view'])->name('clients.view');
            Route::get('/locations', [DashboardController::class, 'locations'])->name('locations.index');
            Route::get('/organization-setup', [BusinessController::class, 'setup'])->name('organization-setup');
            Route::get('/pay-schedule', [DashboardController::class, 'paySchedule'])->name('pay-schedule');
            Route::prefix('/organogram')->name('organogram.')->group(function () {
    Route::get('',                 [OrganogramController::class, 'index'])  ->name('index');
    Route::get('/create',           [OrganogramController::class, 'create']) ->name('create');
    Route::post('/',                [OrganogramController::class, 'store'])  ->name('store');
    Route::get('/{position}/edit',  [OrganogramController::class, 'edit'])   ->name('edit');
    Route::put('/{position}',       [OrganogramController::class, 'update']) ->name('update');
    Route::delete('/{position}',    [OrganogramController::class, 'destroy'])->name('destroy');

    // Optional tree JSON endpoint for frontend library
   Route::get('/tree', [OrganogramController::class, 'treeJson'])
    ->name('tree');

});

            Route::get('/departments', [DashboardController::class, 'departments'])->name('departments.index');

    Route::post('roles/update-departments', [RoleController::class, 'updateDepartments'])->name('roles.update-departments');
            Route::get('/employees', [DashboardController::class, 'employees'])->name('employees.index');
            Route::get('/employees/import', [DashboardController::class, 'importEmployees'])->name('employees.import');
            Route::get('/employees/warning', [DashboardController::class, 'warning'])->name('employees.warning');
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

            Route::get('reliefs', [DashboardController::class, 'reliefs'])->name('reliefs.index');
            Route::get('employee-reliefs', [DashboardController::class, 'employeeReliefs'])->name('employee-reliefs.index');

            Route::get('/allowances', [DashboardController::class, 'allowances'])->name('allowances.index');

            Route::get('/advances', [DashboardController::class, 'advances'])->name('advances.index');
            Route::get('/loans', [DashboardController::class, 'loans'])->name('loans.index');

            // Leave area (business views + a few actions)
            Route::prefix('leave')->name('leave.')->group(function () {
                Route::get('/requests', [DashboardController::class, 'leaveApplications'])->name('index');
                Route::get('/requests/create', [DashboardController::class, 'requestLeave'])->name('create');
                Route::get('/view/{leave}', [DashboardController::class, 'leaveApplication'])->name('show');
                Route::post('/requests/export', [LeaveRequestController::class, 'export'])->name('requests.export');
                Route::get('/types', [DashboardController::class, 'leaveTypes'])->name('types');
                Route::get('/periods', [DashboardController::class, 'leavePeriods'])->name('periods');
                Route::get('/entitlements', [DashboardController::class, 'leaveEntitlements'])->name('entitlements.index');
                Route::get('/entitlements/set', [DashboardController::class, 'setLeaveEntitlements'])->name('entitlements.create');
                Route::post('/revoke', [LeaveRequestController::class, 'revoke'])->name('revoke');
                Route::get('/{reference}/download', [LeaveRequestController::class, 'downloadPdf'])->name('download')->where('reference', '[A-Za-z0-9\-]+');
                Route::get('/leave-types/{slug}/edit', [LeaveTypeController::class, 'edit'])->name('leave-types.edit');
                Route::delete('/leave-types/delete', [LeaveTypeController::class, 'destroy'])->name('leave-types.delete');
                Route::post('/leave-types/remaining-days', [LeaveTypeController::class, 'getRemainingDays'])->name('leave-types.remaining-days');
                Route::post('/leave-types/update', [LeaveTypeController::class, 'update'])->name('leave-types.update');
                Route::post('/upload-document', [LeaveRequestController::class, 'uploadDocument'])->name('upload-document');
                Route::post('/status', [LeaveRequestController::class, 'status'])->name('status');
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
                Route::post('/by-period', [LeaveEntitlementController::class, 'getByPeriod'])->name('leave-entitlements.by-period');
                Route::post('/employees/filter', [EmployeeController::class, 'filter'])->name('leave-entitlements.employees.filter');
                //Route::post('/recalculate', [LeaveEntitlementController::class, 'recalculate'])->name('leave-entitlements.recalculate');
                //Route::post('/entitled-types', [LeaveEntitlementController::class, 'EntitledTypes'])->name('leave-entitlements.entitled-types');
          });


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
            });

            // Enhanced Attendance Routes
            Route::prefix('attendances')->group(function () {
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
});

Route::middleware(['ensure_role', 'role:business-employee'])
    ->name('myaccount.')
    ->prefix('myaccount/{business:slug}')
    ->group(function () {
        Route::get('/', [EmployeeDashboardController::class, 'index'])->name('index');

        Route::get('update-details', [EmployeeDashboardController::class, 'updateDetails'])->name('update');
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile');

        Route::prefix('leave')->name('leave.')->group(function () {
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

        Route::get('/p9', [EmployeeDashboardController::class, 'downloadP9'])->name('p9');

        Route::get('/payslips', [EmployeeDashboardController::class, 'viewPayslips'])->name('payslips');

        Route::get('/payslips/download/{id}', [EmployeeDashboardController::class, 'downloadPayslip'])->name('payslips.download');

        Route::middleware('auth')->group(function () {
            Route::get('/account-settings', [EmployeeDashboardController::class, 'accountSettings'])->name('account.settings');
        });

        Route::get('/notifications', function () {
            return view('employee.notifications');
        })->name('notifications');
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
        Route::get('business', [BusinessController::class, 'create'])->name('business');
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

Route::get('business/{business:slug}/activate', [BusinessController::class, 'activate'])->name('business.activate');

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
Route::get('/test-leave-types/{slug}/edit', function($slug) {
    return "Edit page for $slug";
});
