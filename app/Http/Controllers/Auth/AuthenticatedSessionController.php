<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\LoginLog;
use App\Models\LoginAttempt;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LoginNotification;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;

class AuthenticatedSessionController extends Controller
{
    use HandleTransactions;

    public function create(): View
    {
        $page = "Welcome Back!";
        $description = "Enter your credentials to log into your account";
        return view('auth.login', compact('page', 'description'));
    }

    public function store(LoginRequest $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $ip = $request->ip();
            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            $attempt = LoginAttempt::firstOrCreate(['ip_address' => $ip], ['attempts' => 0]);

            if ($attempt->isBanned()) {
                throw ValidationException::withMessages([
                    'email' => ['Your IP is banned due to multiple failed login attempts. Try again later.'],
                ]);
            }

            if (Auth::attempt($credentials, $remember)) {
                $user = Auth::user();

                if ($user->requiresTwoFactorAuthentication()) {

                    $user->generateTwoFactorCode();

                    $request->session()->put('2fa_user_id', $user->id);

                    Auth::logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    $request->session()->put('2fa_user_id', $user->id);

                    return RequestResponse::ok('A verification code has been sent to your email.', [
                        'redirect_url' => route('2fa.verify'),
                    ]);
                }

                $request->session()->regenerate();

                $attempt->update(['attempts' => 0, 'banned_until' => null]);

                $loginLog = $this->logLoginDetails($user, $request);

                $redirectUrl = $this->getRedirectUrlForRole($user);

                $request->session()->put('2fa_verified', true);

                return RequestResponse::ok('Welcome back.', ['redirect_url' => $redirectUrl]);
            }

            $attempt->increment('attempts');

            if ($attempt->attempts >= 4) {
                $attempt->update(['banned_until' => now()->addHours(3)]);
                throw ValidationException::withMessages([
                    'email' => ['Too many failed login attempts. Your IP is banned for 3 hours.'],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        });
    }

    public function showTwoFactorForm(): View
    {
        $page = "Two-Factor Authentication";
        $description = "Please enter the verification code sent to your email.";
        return view('auth.two-factor-verify', compact('page', 'description'));
    }

    public function verifyTwoFactorCode(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);

            $userId = $request->session()->get('2fa_user_id');

            if (!$userId) {
                return RequestResponse::error('No 2FA session found.', ['redirect_url' => route('login')]);
            }

            $user = User::find($userId);

            if (!$user) {
                $request->session()->forget('2fa_user_id');
                return RequestResponse::error('User not found.', ['redirect_url' => route('login')]);
            }

            if ($user->verifyTwoFactorCode($request->code)) {

                Auth::login($user);
                $request->session()->regenerate();

                $request->session()->put('2fa_verified', true);
                $request->session()->forget('2fa_user_id');

                $loginLog = $this->logLoginDetails($user, $request);

                activity()
                    ->causedBy($user)
                    ->performedOn($user)
                    ->log('Completed two-factor authentication');

                $redirectUrl = $this->getRedirectUrlForRole($user);

                return RequestResponse::ok('Verification successful.', ['redirect_url' => $redirectUrl]);
            }

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('Failed two-factor authentication attempt');

            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        });
    }

    public function resendTwoFactorCode(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $userId = $request->session()->get('2fa_user_id');

            if (!$userId) {
                return RequestResponse::error('No 2FA session found.', ['redirect_url' => route('login')]);
            }

            $user = User::find($userId);

            if (!$user) {
                $request->session()->forget('2fa_user_id');
                return RequestResponse::error('User not found.', ['redirect_url' => route('login')]);
            }

            $user->generateTwoFactorCode();

            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->log('Resent two-factor authentication code');

            return RequestResponse::ok('A new verification code has been sent to your email.');
        });
    }

    protected function logLoginDetails($user, $request)
    {
        $agent = new Agent();
        $ip = $request->ip();

        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return LoginLog::create([
                'user_id' => $user->id,
                'ip_address' => $ip,
                'browser' => $agent->browser() ?: 'Unknown',
                'device' => $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop'),
                'location' => 'Localhost',
                'network' => 'Local Network',
                'login_at' => now(),
            ]);
        }

        $geo = Http::timeout(5)->get("http://ip-api.com/json/{$ip}")->json();

        $location = 'Unknown Location';
        if (!empty($geo) && isset($geo['status']) && $geo['status'] === 'success') {
            if (isset($geo['city'], $geo['country'])) {
                $location = "{$geo['city']}, {$geo['country']}";
            } elseif (isset($geo['regionName'], $geo['country'])) {
                $location = "{$geo['regionName']}, {$geo['country']}";
            } elseif (isset($geo['country'])) {
                $location = $geo['country'];
            }
        }

        $network = $geo['isp'] ?? ($geo['org'] ?? 'Unknown Network');

        return LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $ip,
            'browser' => $agent->browser() ?: 'Unknown',
            'device' => $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablet' : 'Desktop'),
            'location' => $location,
            'network' => $network,
            'login_at' => now(),
        ]);
    }

    private function getRedirectUrlForRole($user)
    {
        if ($user->hasRole('super-admin')) {

            $business = \App\Models\Business::where('slug', config('business.main_slug'))->first();
            if ($business) {
                session(['active_business_slug' => $business->slug]);
                session(['active_role' => 'super-admin']);
                return route('business.index', $business->slug);
            }
        }

        if ($user->hasRole('business-admin')) {

            $business = $user->business;
            if ($user->status === "setup" || !$business) {
                return route('setup.business');
            } elseif ($user->status === "module") {
                return route('setup.modules');
            }

            session(['active_business_slug' => $business->slug]);
            session(['active_role' => 'business-admin']);
            return route('business.index', $business->slug);
        } elseif ($user->hasRole('business-hr')) {

            $business = $user->employee->business;
            session(['active_business_slug' => $business->slug]);
            session(['active_role' => 'business-hr']);
            return route('business.employees.index', $business->slug);
        } elseif ($user->hasRole('business-finance')) {
            $business = $user->employee->business;
            session(['active_business_slug' => $business->slug]);
            session(['active_role' => 'business-finance']);
            return route('business.index', $business->slug);
        } elseif ($user->hasRole('business-employee')) {
            $business = $user->employee->business;
            session(['active_business_slug' => $business->slug]);
            session(['active_role' => 'business-employee']);
            return route('myaccount.index', $business->slug);
        } elseif ($user->hasRole('restricted-hr')) {

            $business = $user->employee->business;
            session(['active_business_slug' => $business->slug]);
            session(['active_role' => 'restricted-hr']);
            return route('business.employees.index', $business->slug);
        } elseif ($user->hasRole('head-of-department') || $user->hasRole('chief-of-staff')) {

            $business = $user->employee->business;
            session(['active_business_slug' => $business->slug]);
            session(['active_role' => $user->hasRole('head-of-department') ? 'head-of-department' : 'chief-of-staff']);
            return route('business.leave.index', $business->slug);
        }

        $employee = $user->employee;
        if ($employee && $employee->business) {
            foreach (\Database\Seeders\ModuleActionPermissionSeeder::MODULE_HOME_ROUTES as $module => $routeName) {
                if ($user->hasPermissionTo("module.{$module}.view", 'web')) {
                    $customRole = $user->roles()->where('business_id', $employee->business_id)->where('is_custom', true)->first();
                    session(['active_business_slug' => $employee->business->slug]);
                    session(['active_role' => $customRole?->name ?? $user->getRoleNames()->first()]);
                    return route($routeName, $employee->business->slug);
                }
            }
        }

        return route('login');
    }

    public function destroy(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $name = explode(" ", $request->user()->name)[0];
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $redirect_url = route('login');

            return RequestResponse::ok('Come back soon ' . $name, ['redirect_url' => $redirect_url]);
        });
    }
}
