<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RoleHomeRouteService
{

    public const ROUTE_PERMISSIONS = [
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
    ];

    private const ROLE_ROUTES = [
        'super-admin' => 'business.index',
        'business-admin' => 'business.index',
        'business-finance' => 'business.index',
        'business-hr' => 'business.employees.index',
        'restricted-hr' => 'business.employees.index',
        'head-of-department' => 'business.leave.index',
        'chief-of-staff' => 'business.leave.index',
        'business-employee' => 'myaccount.index',
    ];

    public function routeNameForRole(string $role): ?string
    {
        return self::ROLE_ROUTES[$role] ?? null;
    }

    public function homeFor(User $user): ?array
    {
        $activeRole = session('active_role');
        $candidateRoles = array_values(array_unique(array_filter([
            $activeRole,
            ...$user->getRoleNames()->all(),
        ])));

        foreach ($candidateRoles as $role) {
            $result = $this->resolveForRole($user, $role);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    public function forbiddenResponse(Request $request, ?User $user, string $message = "You don't have permission to view that page."): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return new JsonResponse(['message' => $message], 403);
        }

        if ($user) {
            $home = $this->homeFor($user);
            if ($home) {
                return redirect()->route($home['route'], $home['business']->slug)
                    ->with('error', $message);
            }
        }

        return response()->view('errors.403', ['message' => $message], 403);
    }

    private function resolveForRole(User $user, string $role): ?array
    {
        $routeName = self::ROLE_ROUTES[$role] ?? null;

        if (!$routeName) {

            foreach (\Database\Seeders\ModuleActionPermissionSeeder::MODULE_HOME_ROUTES as $module => $candidateRoute) {
                if ($user->hasPermissionTo("module.{$module}.view", 'web')) {
                    $routeName = $candidateRoute;
                    break;
                }
            }
        }

        if (!$routeName) {
            return null;
        }

        $permission = self::ROUTE_PERMISSIONS[$routeName] ?? null;
        if ($permission && !$user->hasPermissionTo($permission, 'web')) {
            return null;
        }

        if ($role === 'super-admin') {
            $business = Business::where('slug', config('business.main_slug'))->first();
        } elseif ($role === 'business-admin') {
            $business = $user->businesses()->first();
        } else {
            $business = optional($user->activeEmployee())->business
                ?? optional($user->employees()->first())->business;
        }

        if (!$business) {
            return null;
        }

        return ['route' => $routeName, 'business' => $business];
    }
}
