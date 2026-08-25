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

        Log::info('Switch role request:', $request->all());
        Log::info('User roles:', $user->roles->pluck('name')->toArray());
        Log::info('Session values:', session()->all());

        if (!$newRole) {
            Log::error('No role specified');
            return response()->json(['error' => 'No role specified'], 400);
        }

        $slug = session('active_business_slug');
        $business = Business::where('slug', $slug)->first();

        if (!$business) {
            Log::error('No business context found for slug: ' . $slug);
            return response()->json(['error' => 'No business context found'], 400);
        }

        if ($user->hasRole($newRole)) {

            if ($newRole === 'super-admin') {
                $platformBusiness = Business::where('slug', config('business.main_slug'))->first();
                if (!$platformBusiness) {
                    return response()->json(['error' => 'Platform business not found'], 500);
                }

                session(['active_business_slug' => $platformBusiness->slug, 'active_role' => $newRole]);
                $redirect = route('business.index', $platformBusiness->slug);
                Log::info('Role switched successfully to: ' . $newRole, ['redirect' => $redirect]);

                if ($request->ajax()) {
                    return response()->json(['success' => true, 'redirect' => $redirect]);
                }

                return redirect($redirect);
            }

            $redirectRoute = $this->getRedirectRoute($newRole, $user);
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

    private function getRedirectRoute($role, $user = null)
    {
        return match ($role) {
            'business-admin' => 'business.index',
            'business-hr' => 'business.employees.index',
            'business-finance' => 'business.index',
            'business-employee' => 'myaccount.index',
            'restricted-hr' => 'business.employees.index',
            'head-of-department' => 'business.leave.index',
            'chief-of-staff' => 'business.leave.index',
            default => $this->customRoleHomeRoute($user) ?? 'dashboard',
        };
    }

    private function customRoleHomeRoute($user): ?string
    {
        if (!$user) {
            return null;
        }

        foreach (\Database\Seeders\ModuleActionPermissionSeeder::MODULE_HOME_ROUTES as $module => $routeName) {
            if ($user->hasPermissionTo("module.{$module}.view", 'web')) {
                return $routeName;
            }
        }

        return null;
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
