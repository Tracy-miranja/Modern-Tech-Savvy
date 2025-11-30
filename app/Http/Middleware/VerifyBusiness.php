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
        // Try to get business slug from route parameter or session
        $slug = $request->route('business') ?? session('active_business_slug');

        Log::info('VerifyBusiness middleware: Checking business', ['slug' => $slug]);

        // Query the business by slug
        $business = Business::where('slug', $slug)->first();

        if (!$business) {
            Log::error('Business not found for slug', ['slug' => $slug]);
            return redirect()->route('dashboard')->with('error', 'Business not found.');
        }

        if (!$business->verified && $business->company_name !== 'krest') {
            Log::warning('Business not verified', ['slug' => $business->slug]);
            return redirect()->route('business.activate', $business->slug)
                ->with('message', 'Your business is not verified. Please contact krest support.');
        }

        // Store business slug in session for consistency
        session(['active_business_slug' => $business->slug]);
        Log::info('Business verified', ['slug' => $business->slug]);

        return $next($request);
    }
}
