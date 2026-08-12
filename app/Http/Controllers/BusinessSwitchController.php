<?php

namespace App\Http\Controllers;

use App\Http\RequestResponse;
use App\Services\RoleHomeRouteService;
use Illuminate\Http\Request;

/**
 * Lets a user move between businesses their OWN account is legitimately
 * attached to - as an owner (business-admin) or via an Employee record
 * (HR, department head, etc. can genuinely work at more than one business
 * under the same login). Deliberately separate from ClientController's
 * krest-admin impersonation: that lets a platform operator step into ANY
 * client business and is audited/session-tracked as "impersonating";
 * this only ever surfaces businesses User::switchableBusinesses() already
 * says the account belongs to, and is a peer switch, not an impersonation
 * (no "original business"/"return to" state - there's no privileged side
 * to return to, both businesses are equally "theirs").
 */
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

        // Stay on the same KIND of page (dashboard, employees list, my
        // account, etc.) for whichever role is currently active - roles
        // are global on the account (not per-business), so the active
        // role stays valid; only which business's data it operates on
        // changes.
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
