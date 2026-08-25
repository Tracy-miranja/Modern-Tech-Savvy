<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use Illuminate\Validation\Rule;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    use HandleTransactions;

    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }
    public function store(ProfileUpdateRequest $request)
    {
        return $this->handleTransaction(function () use ($request) {

            $user = auth()->user();

            $countryCode = $request->code;
            $phoneNumber = "+{$countryCode}{$request->phone}";

            $validator = Validator::make(['phone' => $phoneNumber], [
                'phone' => Rule::unique(User::class)->ignore(auth()->user()->id)
            ]);

            throw_if($validator->fails(), ValidationException::class, $validator);

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $phoneNumber,
                'code' => $request->code,
                'country' => $request->country,
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
                $user->save();
            }

            return RequestResponse::created('Account updated successfully.');
        });
    }
    public function password(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = auth()->user();
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return RequestResponse::ok('Password updated successfully. Please log in again.', [
                'redirect_url' => route('login')
            ]);
        });
    }
    public function destroy(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $request->validateWithBag('userDeletion', [
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $redirect_url = route('login');

            return RequestResponse::ok('Account updated successfully.', ['redirect_url' => $redirect_url]);
        });
    }
}
