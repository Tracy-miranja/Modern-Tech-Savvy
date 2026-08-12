<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use App\Notifications\WelcomeEmployeeNotification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

/**
 * Lets a super-admin create/revoke krest-admin accounts from the UI,
 * instead of requiring shell access to `php artisan make:super-admin` or
 * tinker. Deliberately super-admin-only (role:super-admin, not
 * role:super-admin|krest-admin like Clients) - granting platform-operator
 * access is itself a governance action, same tier as verify/deactivate.
 */
class PlatformAdminController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
{
    $business = Business::findBySlug(session('active_business_slug'));
    if (!$business || $business->slug !== 'krest') {
        return redirect()->route('business.index', $business->slug)->with('error', 'Only krest can manage platform admins.');
    }

    Role::firstOrCreate(['name' => 'krest-admin', 'guard_name' => 'web']);

    $krestAdmins = User::role('krest-admin')->orderBy('name')->get();
    $superAdmins = User::role('super-admin')->orderBy('name')->get();

    return view('platform-admins.index', compact('business', 'krestAdmins', 'superAdmins'));
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || $business->slug !== 'krest') {
            return RequestResponse::forbidden('Only krest can create platform admin accounts.');
        }

        return $this->handleTransaction(function () use ($validated, $request) {
            Role::firstOrCreate(['name' => 'krest-admin', 'guard_name' => 'web']);

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

            if ($user->hasRole('krest-admin')) {
                return RequestResponse::badRequest("{$user->email} already holds the krest-admin role.");
            }

            $user->assignRole('krest-admin');

            activity()
                ->causedBy($request->user())
                ->performedOn($user)
                ->log('Granted krest-admin access');

            // New accounts get a "set your password" email (same pattern as
            // EmployeeController::store()) - no plaintext password is ever
            // generated or shown in the browser. An existing user just
            // keeps their current password; they're notified by role name
            // only, not sent another password-setup email.
            $message = "{$user->email} now holds the krest-admin role.";
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
        if (!$business || $business->slug !== 'krest') {
            return RequestResponse::forbidden('Only krest can revoke platform admin accounts.');
        }

        $user = User::find($userId);
        if (!$user || !$user->hasRole('krest-admin')) {
            return RequestResponse::badRequest('That user is not an krest-admin.');
        }

        $user->removeRole('krest-admin');

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->log('Revoked krest-admin access');

        return RequestResponse::ok("Revoked krest-admin access for {$user->email}.");
    }
}
