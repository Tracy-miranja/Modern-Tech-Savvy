<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Business;
use Illuminate\Support\Facades\View;

class SetCurrentBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = null;

        // 1️⃣ Use impersonated business if present
        if (session()->has('active_business_slug')) {
            $business = Business::findBySlug(session('active_business_slug'));
        }

        // 2️⃣ Fallback to user's own business
        if (!$business && $request->user()) {
            $business = $request->user()->business;
        }

        // 3️⃣ Share globally
        if ($business) {
            $request->attributes->set('activeBusiness', $business);
            View::share('currentBusiness', $business);
        }

        return $next($request);
    }
}
