<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Guard;

/**
 * Drop-in replacement for Spatie's own RoleMiddleware (bound to the 'role'
 * alias in bootstrap/app.php), extended with one exception: while a
 * super-admin is impersonating a client business -
 * session('original_business_slug') is set by
 * ClientController::impersonateManagedBusiness() - they're let through
 * regardless of which specific role the route requires, so they can carry
 * out the exact same operations that business's own business-admin/HR/etc
 * would, without permanently holding those roles on their own account.
 * This bypass only ever applies during an active impersonation session -
 * outside of one, a super-admin without the target role is still 403'd
 * exactly as before.
 *
 * Crucially, the bypass must NOT apply to routes that themselves require
 * super-admin (verify/deactivate/modules.assign, the Clients list itself,
 * etc.) - those are platform-governance actions that must always check the
 * user's REAL roles, impersonation or not.
 */
class RoleOrImpersonation
{
    public function handle($request, Closure $next, $role, $guard = null)
    {
        $authGuard = Auth::guard($guard);
        $user = $authGuard->user();

        if (!$user && $request->bearerToken() && config('permission.use_passport_client_credentials')) {
            $user = Guard::getPassportClient($guard);
        }

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        if (!method_exists($user, 'hasAnyRole')) {
            throw UnauthorizedException::missingTraitHasRoles($user);
        }

        $roles = explode('|', is_array($role) ? implode('|', $role) : (string) $role);

        $routeIsPlatformGoverned = in_array('super-admin', $roles, true);

        if (!$routeIsPlatformGoverned) {
            if (session()->has('original_business_slug') && $user->hasRole('super-admin')) {
                return $next($request);
            }

            if ($user->hasRole('super-admin') && $this->routeBusinessSlug($request) === config('business.main_slug')) {
                return $next($request);
            }
        }

        if (!$user->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }

    /**
     * Business-scoped routes use either {business:slug} (model-bound,
     * param name "business") or the plain string param {business_slug} -
     * both conventions exist side by side across web.php/requests.php.
     */
    private function routeBusinessSlug($request): ?string
    {
        $param = $request->route('business') ?? $request->route('business_slug');
        return is_object($param) ? ($param->slug ?? null) : $param;
    }
}
