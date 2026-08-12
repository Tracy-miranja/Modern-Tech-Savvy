<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Guard;

/**
 * Drop-in replacement for Spatie's own RoleMiddleware (bound to the 'role'
 * alias in bootstrap/app.php), extended with one exception: while an
 * krest admin (super-admin or krest-admin) is impersonating a client
 * business - session('original_business_slug') is set by
 * ClientController::impersonateManagedBusiness() - they're let through
 * regardless of which specific role the route requires, so they can carry
 * out the exact same operations that business's own business-admin/HR/etc
 * would, without permanently holding those roles on their own account.
 * This bypass only ever applies during an active impersonation session -
 * outside of one, an krest admin without the target role is still 403'd
 * exactly as before.
 *
 * Crucially, the bypass must NOT apply to routes that themselves require
 * super-admin/krest-admin (verify/deactivate/modules.assign, the Clients
 * list itself, etc.) - those are platform-governance actions that must
 * always check the user's REAL roles, impersonation or not. Without this
 * carve-out, an krest-admin who has impersonated any client could call
 * super-admin-only governance routes simply by virtue of impersonating
 * something, which defeats the "krest-admin doesn't get the action routes"
 * restriction entirely.
 *
 * Separately, krest-admin operates krest's OWN business (slug 'krest')
 * exactly like that business's own business-admin would - full nav, full
 * operations - without needing to "impersonate" their own employer. That
 * bypass is scoped to the literal 'krest' business only: browsing any
 * OTHER business still requires actually impersonating it first (see
 * ClientController::impersonateManagedBusiness()), preserving the
 * "impersonation-scoped only" restriction for client businesses.
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

        $routeIsPlatformGoverned = !empty(array_intersect($roles, ['super-admin', 'krest-admin']));

        if (!$routeIsPlatformGoverned) {
            if (session()->has('original_business_slug') && $user->hasAnyRole(['super-admin', 'krest-admin'])) {
                return $next($request);
            }

            if ($user->hasRole('krest-admin') && $this->routeBusinessSlug($request) === 'krest') {
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
