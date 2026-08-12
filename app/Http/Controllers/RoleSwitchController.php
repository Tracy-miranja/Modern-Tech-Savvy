<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class RoleSwitchController extends Controller
{
    public function switchRole(Request $request)
    {
        Log::info('Reached switchRole method', ['request' => $request->all()]);

        $user = Auth::user();
        $newRole = $request->input('role');

        // Log request and session data
        Log::info('Switch role request:', $request->all());
        Log::info('User roles:', $user->roles->pluck('name')->toArray());
        Log::info('Session values:', session()->all());

        // Validate role input
        if (!$newRole) {
            Log::error('No role specified');
            return response()->json(['error' => 'No role specified'], 400);
        }

        // Check business context
        $slug = session('active_business_slug');
        $business = Business::where('slug', $slug)->first();

        if (!$business) {
            Log::error('No business context found for slug: ' . $slug);
            return response()->json(['error' => 'No business context found'], 400);
        }

        // Check if user has the role
        if ($user->hasRole($newRole)) {
            // super-admin/krest-admin only ever operate against krest
            // itself (see RoleOrImpersonation/EnsureCorrectRole's "krest
            // home business" bypass) - force the business back to krest
            // regardless of whatever was active before switching, and
            // land on business.index directly rather than through the
            // generic 'dashboard' fallback. That fallback
            // (BusinessController::redirectToDashboard()) re-derives the
            // active role from the user's OWN held roles via its own
            // hardcoded business-admin-first priority chain, completely
            // ignoring which role was just switched TO - an account that
            // also holds business-admin (common for a super-admin testing
            // account) would immediately get bounced back to
            // active_role=business-admin, undoing the switch entirely.
            if (in_array($newRole, ['super-admin', 'krest-admin'], true)) {
                $krest = Business::where('slug', 'krest')->first();
                if (!$krest) {
                    return response()->json(['error' => 'krest business not found'], 500);
                }

                session(['active_business_slug' => $krest->slug, 'active_role' => $newRole]);
                $redirect = route('business.index', $krest->slug);
                Log::info('Role switched successfully to: ' . $newRole, ['redirect' => $redirect]);

                if ($request->ajax()) {
                    return response()->json(['success' => true, 'redirect' => $redirect]);
                }

                return redirect($redirect);
            }

            $redirectRoute = $this->getRedirectRoute($newRole);
            $requiredPermission = $this->getRequiredPermissionForRoute($redirectRoute);

            if ($requiredPermission && !$user->hasPermissionTo($requiredPermission, 'web')) {
                Log::warning('User lacks permission for redirect route', [
                    'user_id' => $user->id,
                    'role' => $newRole,
                    'permission' => $requiredPermission,
                ]);
                return response()->json(['error' => 'You do not have permission to access this route'], 403);
            }

            session(['active_role' => $newRole]);
            $redirect = route($redirectRoute, $business->slug);
            Log::info('Role switched successfully to: ' . $newRole, ['redirect' => $redirect]);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'redirect' => $redirect]);
            }

            return redirect($redirect);
        }

        Log::warning('Unauthorized role switch attempt', ['user_id' => $user->id, 'role' => $newRole]);
        return response()->json(['error' => 'You do not have permission to switch to this role'], 403);
    }

    private function getRedirectRoute($role)
    {
        return match ($role) {
            'business-admin' => 'business.index',
            'business-hr' => 'business.employees.index',
            'business-finance' => 'business.index',
            'business-employee' => 'myaccount.index',
            'general-hr' => 'business.index',
            'restricted-hr' => 'business.employees.index',
            'head-of-department' => 'business.leave.index',
            'chief-of-staff' => 'business.leave.index',
            default => 'dashboard',
        };
    }

    private function getRequiredPermissionForRoute($route)
    {
        $permissionMap = [
            'business.index' => 'access.dashboard',
            'business.employees.index' => 'access.employees',
            'business.payroll.index' => 'access.payroll',
            'business.leave.index' => 'access.leave',
            'business.roles.index' => 'access.roles',
        ];

        return $permissionMap[$route] ?? null;
    }
}
