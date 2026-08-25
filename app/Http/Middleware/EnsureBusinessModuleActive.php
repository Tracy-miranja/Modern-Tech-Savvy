<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessModuleActive
{
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $user = Auth::user();
        if ($business && $user && $business->slug === config('business.main_slug')
            && $user->hasAnyRole(['super-admin', 'business-admin'])) {
            return $next($request);
        }

        if ($business && !$business->hasModule($moduleSlug)) {
            $moduleName = ucwords(str_replace('-', ' ', $moduleSlug));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Your business does not have the \"{$moduleName}\" module active.",
                ], 403);
            }

            return redirect()->route('business.index', $business->slug)
                ->with('error', "Your business does not have the \"{$moduleName}\" module active. Contact your administrator to enable it.");
        }

        return $next($request);
    }
}
