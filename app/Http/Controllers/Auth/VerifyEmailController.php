<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{

    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $redirectUrl = $this->getRedirectUrlForRole($request->user());
            return redirect()->intended($redirectUrl);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $redirectUrl = $this->getRedirectUrlForRole($request->user());
        return redirect()->intended($redirectUrl);
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

