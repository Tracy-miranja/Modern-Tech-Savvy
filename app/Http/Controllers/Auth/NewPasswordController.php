<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{

    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    // public function store(Request $request): RedirectResponse
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
{
    $request->validate([
        'token' => ['required'],
        'email' => ['required', 'email', 'exists:users,email'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user) use ($request) {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60),
            ])->save();

            Log::info('Password reset successful.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            event(new PasswordReset($user));
        }
    );

    if ($request->wantsJson()) {
        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => __('Your password has been reset successfully.'),
                'data' => [
                    'redirect_url' => route('login'),
                ]
            ], 200);
        }

        return response()->json([
            'message' => $this->getErrorMessage($status),
        ], 422);
    }

    if ($status === Password::PASSWORD_RESET) {
        return redirect()->route('login')->with('status', __('Your password has been reset successfully. Please log in.'));
    }

    Log::warning('Password reset failed.', [
        'email' => $request->email,
        'status' => $status,
    ]);

    return back()
        ->withInput($request->only('email'))
        ->withErrors(['email' => $this->getErrorMessage($status)]);
}

    protected function getErrorMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => __('This password reset token is invalid or has expired. Please request a new reset link.'),
            Password::INVALID_USER => __('We couldn’t find a user with that email address.'),
            default => __('An error occurred while resetting your password. Please try again.'),
        };
    }
}
