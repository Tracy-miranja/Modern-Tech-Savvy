<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates a route behind Business::hasModule($moduleSlug) - see the grandfather
 * clause docblock on that method for why this can never silently lock out a
 * business that never went through module selection at all. Applied
 * per-route via `ensure_module:<slug>`, e.g. `ensure_module:asset-management`.
 */
class EnsureBusinessModuleActive
{
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $business = Business::findBySlug(session('active_business_slug'));

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
