<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
    private function getRedirectUrlForRole($user)
    {
        if ($user->hasRole('business_owner')) {

            $business = $user->business;

            if($user->status === "setup") {
                return route('setup.business', absolute: false).'?verified=1';
            }elseif($user->status === "module") {
                return route('setup.modules', absolute: false).'?verified=1';
            }else{
                return route('business.index', $business->slug, absolute: false).'?verified=1';
            }

        } else {
            return route('myaccount.index');
        }
    }
}
