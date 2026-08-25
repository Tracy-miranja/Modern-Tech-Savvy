

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureCorrectRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $activeRole = session('active_role');

        Log::info('EnsureCorrectRole Middleware', [
            'user_id' => $user?->id,
            'active_role' => $activeRole,
            'active_role_type' => gettype($activeRole),
            'route' => $request->path(),
            'user_roles' => $user ? $user->getRoleNames() : null,
        ]);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized: No authenticated user'], 403);
        }

        if (!$activeRole && $user->hasRole('business-admin')) {
            $activeRole = 'business-admin';
            session(['active_role' => $activeRole]);
            Log::info('Set default active_role to business-admin', ['user_id' => $user->id]);
        }

        if (!$activeRole || !is_string($activeRole)) {
            return response()->json(['message' => 'Unauthorized: Invalid or missing role'], 403);
        }

        if (!$user->hasRole($activeRole)) {
            return response()->json(['message' => 'Unauthorized: User does not have the required role'], 403);
        }

        return $next($request);
    }
}
