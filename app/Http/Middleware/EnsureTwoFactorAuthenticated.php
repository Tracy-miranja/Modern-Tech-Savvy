<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {

        if ($request->routeIs('2fa.verify') || $request->routeIs('2fa.resend')) {
            return $next($request);
        }

        if ($request->session()->has('2fa_user_id')) {
            return redirect()->route('2fa.verify');
        }

        if (Auth::check() && Auth::user()->requiresTwoFactorAuthentication()) {
            if (!session('2fa_verified', false)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['email' => 'Two-factor authentication required.']);
            }
        }

        return $next($request);
    }
}
