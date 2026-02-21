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

        Log::info('VerifyBusiness middleware: Checking business', ['slug' => $slug]);

        $business = Business::where('slug', $slug)->first();

        if (!$business) {
            Log::error('Business not found for slug', ['slug' => $slug]);
            return redirect()->route('dashboard')->with('error', 'Business not found.');
        }

        if (!$business->verified && $business->company_name !== 'amsol') {
            Log::warning('Business not verified', ['slug' => $business->slug]);
            return redirect()->route('business.activate', $business->slug)
                ->with('message', 'Your business is not verified. Please contact Amsol support.');
        }

        session(['active_business_slug' => $business->slug]);
        Log::info('Business verified', ['slug' => $business->slug]);

        return $next($request);
    }

}
