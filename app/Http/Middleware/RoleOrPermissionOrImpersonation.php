<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Guard;

class RoleOrPermissionOrImpersonation
{
    public function handle($request, Closure $next, $roleOrPermission, $guard = null)
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

        $rolesOrPermissions = explode('|', is_array($roleOrPermission) ? implode('|', $roleOrPermission) : (string) $roleOrPermission);

        $routeIsPlatformGoverned = in_array('super-admin', $rolesOrPermissions, true);

        if (!$routeIsPlatformGoverned) {
            if (session()->has('original_business_slug') && $user->hasRole('super-admin')) {
                return $next($request);
            }

            if ($user->hasRole('super-admin') && $this->routeBusinessSlug($request) === config('business.main_slug')) {
                return $next($request);
            }
        }

        if (!$user->hasAnyRole($rolesOrPermissions) && !$user->hasAnyPermission($rolesOrPermissions)) {
            throw UnauthorizedException::forRolesOrPermissions($rolesOrPermissions);
        }

        return $next($request);
    }

    private function routeBusinessSlug($request): ?string
    {
        $param = $request->route('business') ?? $request->route('business_slug');
        return is_object($param) ? ($param->slug ?? null) : $param;
    }
}
