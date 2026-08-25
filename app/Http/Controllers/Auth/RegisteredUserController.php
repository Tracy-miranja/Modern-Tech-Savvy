<?php

namespace App\Http\Controllers\Auth;

use App\Enum\Status;
use App\Http\RequestResponse;
use App\Mail\TeamInvitation;
use App\Models\AccessRequest;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Module;
use App\Models\User;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Rules\Turnstile;

class RegisteredUserController extends Controller
{
    use HandleTransactions;
    public function create(Request $request,  $registration_token = null)
    {
        if (auth()->check() && !empty($registration_token)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $page = "Create an Account";
        $description = "Register to access your personalized dashboard and services.";
        return view('auth.register', compact('page', 'description', 'registration_token'));
    }

    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|max:15',
            'code' => 'required|string|max:15',
            'country' => 'required|string',
            'registration_token' => 'nullable|string:exists:access_requests,registration_token',

        ]);

        log::info($request);

        return $this->handleTransaction(function () use ($request, $validatedData) {

            $countryCode = $request->code;
            $phoneNumber = "+{$countryCode}{$request->phone}";

            $validator = Validator::make(['phone' => $phoneNumber], [
                'phone' => 'unique:users,phone',
            ]);

            throw_if($validator->fails(), ValidationException::class, $validator);

            if (!empty($validatedData['registration_token'])) {
                $invitation = AccessRequest::where('registration_token', $validatedData['registration_token'])->firstOrFail();
                $managing_business = Business::find($invitation->business_id);
                session(['managing_business' => $managing_business->id]);
                session(['employee_id' => $invitation->requester_id]);
            }

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $phoneNumber,
                'code' => $validatedData['code'],
                'country' => $validatedData['country'],
            ]);

            $user->assignRole('business-admin');
            $user->setStatus(Status::SETUP);

            $platformBusiness = Business::where('slug', config('business.main_slug'))->first();
            if (!$platformBusiness) {
                throw new \Exception('Platform business not found');
            }

            $leadData = [
                'business_id' => $platformBusiness->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'source' => 'business_account',
                'status' => 'new',
                'label' => 'Business Admin',
            ];

            try {
                $lead = Lead::create($leadData);
                if (!$lead || !$lead->id) {
                    throw new \Exception('Lead creation failed');
                }

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'activity_type' => 'note',
                    'description' => 'Lead created from user registration with business-admin role.',
                ]);
            } catch (\Exception $e) {
                throw $e;
            }

            $request->hasFile('image')
                ? $user->addMediaFromRequest('image')->toMediaCollection('avatars')
                : $user->addMediaFromBase64(createAvatarImageFromName($request->name))->toMediaCollection('avatars');

            $user->sendEmailVerificationNotification();

            Auth::login($user);

            $redirect_url = route('setup.business');
            $redirect_url = $this->getRedirectUrlForRole($user);

            return RequestResponse::created('Account created successfully.', ['redirect_url' => $redirect_url]);
        });
    }

    private function getRedirectUrlForRole($user)
    {
        if ($user->hasRole('business-admin')) {
            session(['active_role' => 'business-admin']);
            if ($user->status === "setup") {
                return route('setup.business');
            } elseif ($user->status === "module") {
                return route('setup.modules');
            } else {
                return route('login');
            }
        }
    }

    public function setupModules(Request $request)
    {
        $validatedData = $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'exists:modules,id'
        ]);

        $business = auth()->user()->business;

        foreach ($validatedData['modules'] as $moduleId) {
            $business->modules()->attach($moduleId, [
                'is_active' => true,
                'subscription_ends_at' => now()->addDays(14)
            ]);
        }

        return redirect()->route('dashboard');
    }

    public function inviteTeamMember(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string',
            'role' => 'required|exists:roles,name',
            'department' => 'required|string',
            'position' => 'required|string'
        ]);

        $business = auth()->user()->business;

        $token = Str::random(32);

        $invitation = $business->invitations()->create([
            'email' => $validatedData['email'],
            'name' => $validatedData['name'],
            'role' => $validatedData['role'],
            'token' => $token,
            'department' => $validatedData['department'],
            'position' => $validatedData['position'],
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7)
        ]);

        Mail::to($validatedData['email'])->send(new TeamInvitation($invitation));

        return back()->with('success', 'Invitation sent successfully.');
    }
}
