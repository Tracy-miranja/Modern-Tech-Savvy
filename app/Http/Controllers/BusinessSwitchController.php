<?php

namespace App\Http\Controllers;

use App\Http\RequestResponse;
use App\Services\RoleHomeRouteService;
use Illuminate\Http\Request;

class BusinessSwitchController extends Controller
{
    public function switchTo(Request $request, string $business_slug)
    {
        $user = $request->user();

        $target = $user->switchableBusinesses()->firstWhere('slug', $business_slug);
        if (!$target) {
            return RequestResponse::forbidden('You do not have access to that business.');
        }

        session(['active_business_slug' => $target->slug]);

        $activeRole = session('active_role');
        $routeName = $activeRole
            ? app(RoleHomeRouteService::class)->routeNameForRole($activeRole)
            : null;
        $routeName ??= 'business.index';

        return RequestResponse::ok("Switched to {$target->company_name}.", [
            'redirect_url' => route($routeName, $target->slug),
        ]);
    }
}
