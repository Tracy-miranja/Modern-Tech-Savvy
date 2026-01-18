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
    $routeBusiness = $request->route('business');

    // Handle route-model binding OR session slug
    if ($routeBusiness instanceof Business) {
        $business = $routeBusiness;
    } else {
        $slug = $routeBusiness ?? session('active_business_slug');
        $business = Business::where('slug', $slug)->first();
    }

    Log::info('VerifyBusiness middleware: Checking business', [
        'slug' => $business?->slug
    ]);

    if (!$business) {
        Log::error('Business not found');
        return redirect()->route('dashboard')
            ->with('error', 'Business not found.');
    }

    if (!$business->verified && $business->company_name !== 'krest') {
        Log::warning('Business not verified', ['slug' => $business->slug]);
        return redirect()
            ->route('business.activate', $business->slug)
            ->with('message', 'Your business is not verified.');
    }

    // Always sync session
    session(['active_business_slug' => $business->slug]);

    Log::info('Business verified', ['slug' => $business->slug]);

    return $next($request);
}

}
