<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single source of truth for "where does this role belong" and "what
 * permission does that page need" - used by the new 403 redirect-to-
 * somewhere-useful behavior. Three OTHER places in this codebase already
 * answer a similar question independently (AuthenticatedSessionController
 * ::getRedirectUrlForRole() at login, RoleSwitchController
 * ::getRedirectRoute() when switching active_role, BusinessController
 * ::redirectToDashboard() as the generic /dashboard fallback) and have
 * drifted out of sync with each other before (business-hr correctly sent
 * to business.employees.index in one, still to business.index - which
 * 403s, since business-hr lacks access.dashboard - in another). This
 * class is deliberately NOT a fourth copy of that same login/switch
 * logic; it's scoped to the 403 fallback only, which didn't exist before.
 */
class RoleHomeRouteService
{
    /**
     * Route name -> Spatie permission required to view it. Mirrors
     * EnsureCorrectRole's own permission map.
     */
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

    /**
     * Role -> home route name, once a business context is known. Kept in
     * sync with AuthenticatedSessionController::getRedirectUrlForRole()
     * by hand for now (see class docblock).
     */
    private const ROLE_ROUTES = [
        'super-admin' => 'business.index',
        'business-admin' => 'business.index',
        'business-finance' => 'business.index',
        'general-hr' => 'business.index',
        'business-hr' => 'business.employees.index',
        'restricted-hr' => 'business.employees.index',
        'head-of-department' => 'business.leave.index',
        'chief-of-staff' => 'business.leave.index',
        'business-employee' => 'myaccount.index',
    ];

    /**
     * The route name a role lands on once a business is known - exposed
     * for BusinessSwitchController, which needs "stay on the same kind of
     * page, just for the newly-active business" rather than the full
     * homeFor() fallback chain.
     */
    public function routeNameForRole(string $role): ?string
    {
        return self::ROLE_ROUTES[$role] ?? null;
    }

    /**
     * Best-effort "somewhere this user can actually land" - tries their
     * current active_role first, then falls back through every other
     * role they hold. Returns null only if nothing works (caller should
     * fall back to the login page or a static message).
     *
     * @return array{route: string, business: Business}|null
     */
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

    /**
     * Turns a 403 into either a JSON error (AJAX/XHR - a redirect would be
     * meaningless there) or, for a normal page load, a redirect to
     * somewhere the user CAN go via homeFor() - only falling back to a
     * bare 403 page when no accessible role/business could be resolved at
     * all (e.g. a role with no matching business record).
     */
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
