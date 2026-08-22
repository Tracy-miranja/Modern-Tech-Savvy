<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSessionRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $sessionRole = session('active_role');
        $user = auth()->user();

        if (!$user || !$user->hasRole($role) || $sessionRole !== $role) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
