<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $param = $request->route('business'); // may be string slug or Business model
        $slug  = is_object($param) ? ($param->slug ?? null) : $param;

        if (!$slug) {
            $slug = session('active_business_slug');
        }

        // Business::findBySlug() throws (not returns null) when missing -
        // reuse it (also picks up its request-scoped memoization) instead
        // of a second, separate uncached lookup for the same slug.
        try {
            $business = Business::findBySlug($slug);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Business not found for slug', ['slug' => $slug]);
            return redirect()->route('dashboard')->with('error', 'Business not found.');
        }

        if (!$business->verified && $business->slug !== config('business.main_slug')) {
            Log::warning('Business not verified', ['slug' => $business->slug]);
            return redirect()->route('business.activate', $business->slug)
                ->with('message', 'Your business is not verified. Please contact support.');
        }

        session(['active_business_slug' => $business->slug]);

        return $next($request);
    }

}
