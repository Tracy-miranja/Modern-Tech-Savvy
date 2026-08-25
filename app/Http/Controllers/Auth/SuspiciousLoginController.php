<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class SuspiciousLoginController extends Controller
{
    public function handle(Request $request, $userId, $loginId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        $user = User::findOrFail($userId);
        $loginLog = LoginLog::findOrFail($loginId);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Password::sendResetLink(['email' => $user->email]);

        return view('auth.suspicious-login', [
            'message' => 'Your account has been logged out, and a password reset link has been sent to your email.',
        ]);
    }
}
