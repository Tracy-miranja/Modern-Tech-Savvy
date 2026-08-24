<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use App\Services\RoleHomeRouteService;

class EnsureCorrectRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: No authenticated user'], 401);
        }

        // Set default role if none exists
        if (!$activeRole) {
            if ($user->hasRole('business-admin')) {
                $activeRole = 'business-admin';
            } elseif ($user->hasRole('restricted-hr')) {
                $activeRole = 'restricted-hr';
            } elseif ($user->hasRole('head-of-department')) {
                $activeRole = 'head-of-department';
            } elseif ($user->hasRole('chief-of-staff')) {
                $activeRole = 'chief-of-staff';
            } elseif ($user->hasRole('business-hr')) {
                $activeRole = 'business-hr';
            } elseif ($user->hasRole('business-employee')) {
                $activeRole = 'business-employee';
            } else {
                // Fallback to the first role the user has
                $userRoles = $user->getRoleNames()->toArray();
                $activeRole = !empty($userRoles) ? $userRoles[0] : null;
            }

            if ($activeRole) {
                session(['active_role' => $activeRole]);
                Log::info('Set default active_role', [
                    'user_id' => $user->id,
                    'active_role' => $activeRole,
                ]);
            }
        }

        // Validate active role
        if (!$activeRole || !is_string($activeRole)) {
            return app(RoleHomeRouteService::class)->forbiddenResponse($request, $user, 'Unauthorized: Invalid or missing role');
        }

        // While a super-admin is impersonating a client business,
        // active_role is set to the impersonated role (e.g. business-admin)
        // which they don't personally hold - let them through anyway so
        // they can operate exactly as that business's own admin would.
        // Only applies during an active impersonation session (see
        // ClientController::impersonateManagedBusiness()).
        $isImpersonating = session()->has('original_business_slug');
        $isSuperAdmin = $user->hasRole('super-admin');

        // Mirrors RoleOrImpersonation's "super-admin operates the platform
        // business" bypass - without this, switching active_role to
        // something the super-admin doesn't personally hold (e.g.
        // previewing the employee portal via Switch Account) would 403
        // here before RoleOrImpersonation's own equivalent check is ever
        // reached, even though the business in play is the platform
        // business itself, not a client.
        $isOperatingOwnPlatformBusiness = $isSuperAdmin && session('active_business_slug') === config('business.main_slug');

        if (!$user->hasRole($activeRole) && !($isImpersonating && $isSuperAdmin) && !$isOperatingOwnPlatformBusiness) {
            return app(RoleHomeRouteService::class)->forbiddenResponse($request, $user, 'Unauthorized: User does not have the required role');
        }

        // Restrict `restricted-hr`, `head-of-department`, `chief-of-staff`, and `business-hr` for specific conditions
        if (in_array($activeRole, ['restricted-hr', 'head-of-department', 'chief-of-staff', 'business-hr'])) {
            $restrictedRoutes = [
                // 'business.payroll.index' alone only matches the "Run
                // Payroll" page (str_contains substring match below) -
                // every other payroll-family route name (advances, loans,
                // payroll-settings sub-pages, pay grades) needs its own
                // explicit entry or it silently stays reachable.
                'restricted-hr' => [
                    'business.payroll.index',
                    'business.payroll.all',
                    'business.advances.index',
                    'business.loans.index',
                    'business.employee-reliefs.index',
                    'business.payroll-formulas.index',
                    'business.reliefs.index',
                    'business.deductions',
                    'business.allowances.index',
                    'business.pay-grades.index',
                ],
                'head-of-department' => [
                    'business.index',
                    // 'business.clients.index',
                    'business.locations.index',
                    'business.organization-setup',
                    'business.employees.index',
                    'business.payroll.index',
                    'business.payroll-settings',
                    'business.attendances.index',
                    'business.performance.tasks.index',
                    'business.performance.kpis.index',
                    'business.crm.contacts.index',
                    'business.crm.leads.index',
                    'business.crm.campaigns.index',
                    'business.recruitment.jobs.index',
                    'business.applicants.index',
                    'business.applications.index',
                    'business.profile.index',
                    'business.support.index',
                    'business.roles.index',
                    'business.departments.index',
                    'business.job-categories.index',
                    'business.shifts.index',
                    'business.roster.index',
                    'business.pay-grades.index',
                    'business.deductions',
                    'business.reliefs.index',
                    'business.employee-reliefs.index',
                    'business.allowances.index',
                ],
                'chief-of-staff' => [
                    'business.index',
                    // 'business.clients.index',
                    'business.locations.index',
                    'business.organization-setup',
                    'business.employees.index',
                    'business.payroll.index',
                    'business.payroll-settings',
                    'business.attendances.index',
                    'business.performance.tasks.index',
                    'business.performance.kpis.index',
                    'business.crm.contacts.index',
                    'business.crm.leads.index',
                    'business.crm.campaigns.index',
                    'business.recruitment.jobs.index',
                    'business.applicants.index',
                    'business.applications.index',
                    'business.profile.index',
                    'business.support.index',
                    'business.roles.index',
                    'business.departments.index',
                    'business.job-categories.index',
                    'business.shifts.index',
                    'business.roster.index',
                    'business.pay-grades.index',
                    'business.deductions',
                    'business.reliefs.index',
                    'business.employee-reliefs.index',
                    'business.allowances.index',
                ],
                'business-hr' => [
                ],
            ];

            $currentRoute = $request->route()->getName() ?? $request->path();

            foreach ($restrictedRoutes[$activeRole] ?? [] as $restrictedRoute) {
                if (str_contains($currentRoute, $restrictedRoute)) {
                    return app(RoleHomeRouteService::class)->forbiddenResponse($request, $user, "Unauthorized: $activeRole cannot access this route");
                }
            }

            // Check permissions
            $requiredPermission = $this->getRequiredPermissionForRoute($currentRoute);
            if ($requiredPermission && !$user->hasPermissionTo($requiredPermission, 'web')) {
                return app(RoleHomeRouteService::class)->forbiddenResponse($request, $user, 'Unauthorized: Missing required permission');
            }
        }

        // Verify business account scoping
        if ($user->business_id && str_contains($request->path(), 'business')) {
            $businessId = $request->route('business_id') ?? $request->input('business_id');
            if ($businessId && $businessId != $user->business_id) {
                return app(RoleHomeRouteService::class)->forbiddenResponse($request, $user, 'Unauthorized: Access to this business is restricted');
            }
        }

        return $next($request);
    }

    private function getRequiredPermissionForRoute(string $route): ?string
    {
        $permissionMap = [
            'business.index' => 'access.dashboard',
            'business.employees.index' => 'access.employees',
            'business.payroll.index' => 'access.payroll',
            'business.payroll-settings' => 'access.payroll-settings',
            'business.clients.index' => 'access.clients',
            'business.locations.index' => 'access.locations',
            'business.organization-setup' => 'access.organization',
            'business.departments.index' => 'access.organization',
            'business.job-categories.index' => 'access.organization',
            'business.shifts.index' => 'access.organization',
            'business.roster.index' => 'access.organization',
            'business.pay-grades.index' => 'access.organization',
            'business.deductions' => 'access.payroll-settings',
            'business.reliefs.index' => 'access.payroll-settings',
            'business.employee-reliefs.index' => 'access.payroll-settings',
            'business.allowances.index' => 'access.payroll-settings',
            'business.leave.index' => 'access.leave',
            'business.attendances.index' => 'access.attendance',
            'business.performance.tasks.index' => 'access.performance',
            'business.performance.kpis.index' => 'access.performance',
            'business.crm.contacts.index' => 'access.crm',
            'business.crm.leads.index' => 'access.crm',
            'business.crm.campaigns.index' => 'access.crm',
            'business.recruitment.jobs.index' => 'access.recruitment',
            'business.applicants.index' => 'access.recruitment',
            'business.applications.index' => 'access.recruitment',
            'business.profile.index' => 'access.profile',
            'business.support.index' => 'access.support',
            'business.roles.index' => 'access.roles',
            'payroll-formulas.index' => 'access.payroll-settings',
            'payroll-formulas.bracket-template' => 'access.payroll-settings',
            'deductions' => 'access.payroll-settings',
            'payroll.index' => 'access.payroll',
            'payroll.all' => 'access.payroll',
            'payroll.view' => 'access.payroll',
            'payroll.reports' => 'access.payroll',
            'payroll.download_column' => 'access.payroll',
            'payroll.print_all_payslips' => 'access.payroll',
            'payslips' => 'access.payroll',
            'payroll.payslip' => 'access.payroll',
            'payroll.download_p9' => 'access.payroll',
            'payroll.download_bank_advice' => 'access.payroll',
            'payroll.download_single_p9' => 'access.payroll',
            'payroll.send_payslips' => 'access.payroll',
        ];

        foreach ($permissionMap as $routePrefix => $permission) {
            if (str_contains($route, $routePrefix)) {
                return $permission;
            }
        }

        return null;
    }
}
