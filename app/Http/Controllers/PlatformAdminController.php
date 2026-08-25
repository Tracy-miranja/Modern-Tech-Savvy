<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Support\Facades\Password;

class PlatformAdminController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || $business->slug !== config('business.main_slug')) {
            return redirect()->route('business.index', $business->slug)->with('error', 'Only the platform business can manage platform admins.');
        }

        $platformAdmins = User::role('super-admin')->orderBy('name')->get();

        return view('platform-admins.index', compact('business', 'platformAdmins'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can create platform admin accounts.');
        }

        return $this->handleTransaction(function () use ($validated, $request) {
            $user = User::where('email', $validated['email'])->first();
            $isNewUser = !$user;

            if ($isNewUser) {
                $user = User::create([
                    'name'              => $validated['name'],
                    'email'             => $validated['email'],
                    'password'          => null,
                    'email_verified_at' => now(),
                ]);
            }

            if ($user->hasRole('super-admin')) {
                return RequestResponse::badRequest("{$user->email} already holds the super-admin role.");
            }

            $user->assignRole('super-admin');

            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->log('Granted super-admin access');

            $message = "{$user->email} now holds the super-admin role.";
            if ($isNewUser) {
                $token = Password::createToken($user);
                $user->notify(new WelcomeEmployeeNotification($user, $token));
                $message = "Account created for {$user->email}. They'll receive an email to set their password.";
            }

            return RequestResponse::created($message, [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        });
    }

    public function destroy(Request $request, $business_slug, $userId)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can revoke platform admin accounts.');
        }

        $user = User::find($userId);
        if (!$user || !$user->hasRole('super-admin')) {
            return RequestResponse::badRequest('That user is not a platform admin.');
        }

        $user->removeRole('super-admin');

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Revoked super-admin access');

        return RequestResponse::ok("Revoked platform admin access for {$user->email}.");
    }
}
