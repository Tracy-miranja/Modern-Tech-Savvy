<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Business;

class EnsureCorrectRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        // Log for debugging
        Log::info('EnsureCorrectRole Middleware', [
            'user_id' => $user?->id,
            'active_role' => $activeRole,
            'active_role_type' => gettype($activeRole),
            'route' => $request->path(),
            'user_roles' => $user ? $user->getRoleNames()->toArray() : null,
        ]);

        // Check if user is authenticated
        if (!$user) {
            return response()->json(['message' => 'Unauthorized: No authenticated user'], 401);
        }

        // Set default role if none exists
        if (!$activeRole) {
            if ($user->hasRole('business-admin')) {
                $activeRole = 'business-admin';
            } elseif ($user->hasRole('business-head')) {
                $activeRole = 'business-head';
            } elseif ($user->hasRole('general-hr')) {
                $activeRole = 'general-hr';
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
            return response()->json(['message' => 'Unauthorized: Invalid or missing role'], 403);
        }

        if (!$user->hasRole($activeRole)) {
            return response()->json(['message' => 'Unauthorized: User does not have the required role'], 403);
        }

        // Restrict `restricted-hr`, `head-of-department`, `chief-of-staff`, and `business-hr` for specific conditions
        if (in_array($activeRole, ['restricted-hr', 'head-of-department', 'chief-of-staff', 'business-hr'])) {
            $restrictedRoutes = [
                'restricted-hr' => [
                    'business.index',
                    'business.payroll.index',
                    'business.payroll-settings',
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

            // Payroll restricted to third-park hospital business-hr
            $business = Business::findBySlug(session('active_business_slug'));
            $businessSlug = $business->slug ?? null;
            if ($activeRole === 'business-hr' && $businessSlug === '3rd-park-hospital-ltd') {
                $restrictedRoutes['business-hr'] = array_merge($restrictedRoutes['business-hr'], [
                    'payroll-formulas.index',
                    'payroll-formulas.bracket-template',
                    'deductions',
                    'payroll.index',
                    'payroll.all',
                    'payroll.view',
                    'payroll.reports',
                    'payroll.download_column',
                    'payroll.print_all_payslips',
                    'payslips',
                    'payroll.payslip',
                    'payroll.download_p9',
                    'payroll.download_bank_advice',
                    'payroll.download_single_p9',
                    'payroll.send_payslips',
                ]);
            }

            $currentRoute = $request->route()->getName() ?? $request->path();

            foreach ($restrictedRoutes[$activeRole] ?? [] as $restrictedRoute) {
                if (str_contains($currentRoute, $restrictedRoute)) {
                    return response()->json(['message' => "Unauthorized: $activeRole cannot access this route"], 403);
                }
            }

            // Check permissions
            $requiredPermission = $this->getRequiredPermissionForRoute($currentRoute);
            if ($requiredPermission && !$user->hasPermissionTo($requiredPermission, 'web')) {
                return response()->json(['message' => 'Unauthorized: Missing required permission'], 403);
            }
        }

        // Verify business account scoping
        if ($user->business_id && str_contains($request->path(), 'business')) {
            $businessId = $request->route('business_id') ?? $request->input('business_id');
            if ($businessId && $businessId != $user->business_id) {
                return response()->json(['message' => 'Unauthorized: Access to this business is restricted'], 403);
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
