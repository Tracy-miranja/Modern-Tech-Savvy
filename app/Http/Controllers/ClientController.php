<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\User;
use App\Models\Client;
use App\Models\Business;
use App\Mail\InviteUserMail;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Models\AccessRequest;
use App\Mail\AccessRequestMail;
use App\Mail\BusinessStatusMail;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    use HandleTransactions;

    public function index(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || $business->slug !== config('business.main_slug')) {
            return redirect()->route('business.index', $business->slug)->with('error', 'Only the platform business can manage clients.');
        }

        $businesses = Business::where('slug', '!=', config('business.main_slug'))
            ->with(['media', 'user'])
            ->paginate(10);

        return view('clients.index', compact('businesses', 'business'));
    }

    public function fetch(Request $request)
    {
        $business_slug = session('active_business_slug');
        $business = Business::findBySlug($business_slug);

        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can fetch clients.');
        }

        $businesses = Business::where('slug', '!=', config('business.main_slug'))
            ->with(['media', 'user'])
            ->paginate(10);

        $clients_table = view('clients._clients_table', compact('businesses'))->render();

        return RequestResponse::ok('Clients fetched successfully.', $clients_table);
    }

    public function view(Request $request, $business_slug, $client_business_slug)
    {
        $business = Business::findBySlug($business_slug);
        if (!$business || $business->slug !== config('business.main_slug')) {
            return redirect()->route('business.index', $business->slug)->with('error', 'Only the platform business can view client details.');
        }

        $clientBusiness = Business::findBySlug($client_business_slug);

        $modules = \App\Models\Module::all();

        return view('clients.view', compact('clientBusiness', 'modules'));
    }

    public function showRequestAccess(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->route('business.index', $business->slug)->with('error', 'Business not found.');
        }
        return view('clients.request-access', compact('business'));
    }

    public function showGrantAccess(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return redirect()->route('business.index', $business->slug)->with('error', 'Business not found.');
        }
        $requests = AccessRequest::where('business_id', $business->id)
            ->currentStatus(Status::PENDING)
            ->with('requester')
            ->get();

        return view('clients.grant-access', compact('business', 'requests'));
    }

    public function requestAccess(Request $request)
    {
        $request->validate([
            'email' => 'required|email|not_in:' . $request->user()->email,
        ]);

        return $this->handleTransaction(function () use ($request) {
            $email = $request->input('email');
            $requester = $request->user();
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.');
            }

            if ($business->slug !== config('business.main_slug')) {
                $platformBusiness = Business::where('slug', config('business.main_slug'))->first();
                if ($platformBusiness && $platformBusiness->managingBusinesses()->where('client_business', $business->id)->exists()) {
                    return RequestResponse::forbidden('Clients cannot share accounts.');
                }
            }

            if (AccessRequest::where('business_id', $business->id)->where('email', $email)->currentStatus(Status::PENDING)->exists()) {
                return RequestResponse::badRequest('An access request is already pending for this email.');
            }

            $existingUser = User::where('email', $email)->first();
            $registrationToken = generateRegistrationToken($requester->id, $business->id);

            $accessRequest = AccessRequest::create([
                'requester_id' => $requester->id,
                'business_id' => $business->id,
                'email' => $email,
                'registration_token' => $registrationToken,
            ]);

            $accessRequest->setStatus(Status::PENDING);

            if ($existingUser) {
                if (Client::where('business_id', $business->id)->where('employee_id', $existingUser->id)->exists()) {
                    return RequestResponse::badRequest('User already has access to this business.');
                }
                $tempPassword = Str::random(12);
                $existingUser->update(['password' => bcrypt($tempPassword)]);
                Mail::to($email)->send(new AccessRequestMail($accessRequest, $tempPassword));
            } else {
                Mail::to($email)->send(new InviteUserMail($accessRequest));
            }

            return RequestResponse::created('Access request sent successfully.');
        });
    }

    public function grantAccess(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:access_requests,id',
            'role' => 'required|in:business-admin,business-employee',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $accessRequest = AccessRequest::findOrFail($request->request_id);
            $business = Business::findOrFail($accessRequest->business_id);

            if ($business->slug !== config('business.main_slug')) {
                $platformBusiness = Business::where('slug', config('business.main_slug'))->first();
                if ($platformBusiness && $platformBusiness->managingBusinesses()->where('client_business', $business->id)->exists()) {
                    return RequestResponse::forbidden('Clients cannot grant access.');
                }
            }

            $user = User::where('email', $accessRequest->email)->first();
            if (!$user) {
                return RequestResponse::badRequest('User not found.');
            }

            // The Spatie role, not just the pivot's own 'role' column below,
            // is what every permission check in the app actually reads
            // (hasRole()) - without this, a platform-granted client admin
            // could never actually use the business-admin features they
            // were just granted.
            if (!$user->hasRole($request->role)) {
                $user->assignRole($request->role);
            }

            Client::create([
                'business_id' => $business->id,
                'client_business' => $business->id,
                'employee_id' => $user->id,
                'role' => $request->role,
            ])->setStatus(Status::ACTIVE);

            $accessRequest->setStatus(Status::APPROVED);

            activity()
                ->causedBy($request->user())
                ->performedOn($business)
                ->log("Granted {$request->role} access to {$user->email}");

            Mail::to($user->email)->send(new BusinessStatusMail($business, 'access_granted', 'Your access request has been approved.'));

            return RequestResponse::ok('Access granted successfully.');
        });
    }

//    public function impersonateManagedBusiness(Request $request, $business_slug, $client_business_slug)
// {
//     $business = Business::findBySlug($business_slug);
//     if (!$business || $business->slug !== config('business.main_slug')) {
//         return RequestResponse::forbidden('Only the platform business can impersonate businesses.');
//     }

//     $managedBusiness = Business::findBySlug($client_business_slug);
//     if (!$managedBusiness) {
//         return RequestResponse::badRequest('Managed business not found.');
//     }

//     // Store the original business slug before switching
//     session(['original_business_slug' => $business_slug]);
//     session(['active_business_slug' => $managedBusiness->slug]);

//     activity()
//         ->causedBy($request->user())
//         ->performedOn($managedBusiness)
//         ->log('Impersonated business');

//     return RequestResponse::ok(
//         message: "Welcome to {$managedBusiness->company_name} dashboard",
//         data: ['redirect_url' => route('business.index', ['business' => $managedBusiness->slug])]
//     );
// }
public function impersonateManagedBusiness(Request $request, $business_slug, $client_business_slug)
{
    $currentlyImpersonating = session()->has('original_business_slug');
    $isPlatformBusiness = $business_slug === config('business.main_slug');

    $allowed = $isPlatformBusiness || ($currentlyImpersonating && $business_slug === session('active_business_slug'));

    if (!$allowed) {
        return RequestResponse::forbidden('Only the platform business can impersonate businesses.');
    }

    $managedBusiness = Business::findBySlug($client_business_slug);
    if (!$managedBusiness) {
        return RequestResponse::badRequest('Managed business not found.');
    }

    if (!$currentlyImpersonating) {
        session([
            'original_business_slug' => $business_slug,
            'original_active_role' => session('active_role'),
        ]);
    }

    session([
        'active_business_slug' => $managedBusiness->slug,
        'active_role' => 'business-admin',
    ]);

    activity()
        ->causedBy($request->user())
        ->performedOn($managedBusiness)
        ->log('Impersonated business');

    return RequestResponse::ok(
        message: "Welcome to {$managedBusiness->company_name} dashboard",
        data: ['redirect_url' => route('business.index', ['business' => $managedBusiness->slug])]
    );
}

public function managedBusinessesList(Request $request)
{
    if (!session()->has('original_business_slug')) {
        return RequestResponse::forbidden('Not currently impersonating.');
    }

    $businesses = Business::where('slug', '!=', config('business.main_slug'))
        ->where('slug', '!=', session('active_business_slug'))
        ->get(['id', 'company_name', 'slug']);

    return RequestResponse::ok('OK', $businesses);
}

public function switchBackToAdmin(Request $request, $business_slug)
{
    $originalBusinessSlug = session('original_business_slug', config('business.main_slug'));
    $originalActiveRole = session('original_active_role');

    session(['active_business_slug' => $originalBusinessSlug]);
    if ($originalActiveRole) {
        session(['active_role' => $originalActiveRole]);
    }
    session()->forget(['original_business_slug', 'original_active_role']);

    activity()
        ->causedBy($request->user())
        ->log('Switched back from impersonated business');

    return RequestResponse::ok(
        message: "Switched back to admin dashboard",
        data: ['redirect_url' => route('business.index', ['business' => $originalBusinessSlug])]
    );
}

    public function verifyBusiness(Request $request, $business_slug, $client_business_slug)
    {
        $business = Business::findBySlug($business_slug);
        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can verify clients.');
        }

        $clientBusiness = Business::findBySlug($client_business_slug);
        if (!$clientBusiness) {
            return RequestResponse::badRequest('Client business not found.');
        }

        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        if ($clientBusiness->verified) {
            return RequestResponse::ok('Business is already verified.');
        }

        return $this->handleTransaction(function () use ($clientBusiness, $request) {
            $clientBusiness->update(['verified' => true]);
            $clientBusiness->setStatus(Status::VERIFIED);
            activity()
                ->causedBy($request->user())
                ->performedOn($clientBusiness)
                ->withProperties(['remarks' => $request->remarks])
                ->log('Business verified');

            Mail::to($clientBusiness->user->email)->send(new BusinessStatusMail($clientBusiness, 'verified', $request->remarks));

            return RequestResponse::ok('Business verified successfully.');
        });
    }

    public function deactivateBusiness(Request $request, $business_slug, $client_business_slug)
    {
        $business = Business::findBySlug($business_slug);
        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can deactivate clients.');
        }

        $clientBusiness = Business::findBySlug($client_business_slug);
        if (!$clientBusiness) {
            return RequestResponse::badRequest('Client business not found.');
        }

        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        if (!$clientBusiness->verified && $clientBusiness->status === Status::DEACTIVATED) {
            return RequestResponse::ok('Business is already deactivated.');
        }

        return $this->handleTransaction(function () use ($clientBusiness, $request) {
            $clientBusiness->update(['verified' => false]);
            $clientBusiness->setStatus(Status::DEACTIVATED);
            activity()
                ->causedBy($request->user())
                ->performedOn($clientBusiness)
                ->withProperties(['remarks' => $request->remarks])
                ->log('Business deactivated');

            Mail::to($clientBusiness->user->email)->send(new BusinessStatusMail($clientBusiness, 'deactivated', $request->remarks));

            return RequestResponse::ok('Business deactivated successfully.');
        });
    }

    public function assignModules(Request $request, $business_slug, $client_business_slug)
    {
        $business = Business::findBySlug($business_slug);
        if (!$business || $business->slug !== config('business.main_slug')) {
            return RequestResponse::forbidden('Only the platform business can manage modules.');
        }

        $clientBusiness = Business::findBySlug($client_business_slug);
        if (!$clientBusiness) {
            return RequestResponse::badRequest('Client business not found.');
        }

        $request->validate([
            'modules' => 'array',
            'modules.*' => 'exists:modules,id',
        ]);

        return $this->handleTransaction(function () use ($clientBusiness, $request) {
            $selectedIds = array_map('intval', $request->input('modules', []));

            // A bare sync($ids) recreates every pivot row from column
            // defaults, silently wiping subscription_ends_at (no default,
            // nullable) back to null even for a module that stays checked
            // and already had a payment-extended expiry. Preserve it
            // instead, and soft-disable (is_active=false) rather than
            // detach anything unchecked, so payment history recorded
            // against that module_id stays meaningful.
            $existing = $clientBusiness->modules()->withPivot('subscription_ends_at')->get()->keyBy('id');

            foreach ($selectedIds as $moduleId) {
                $clientBusiness->modules()->syncWithoutDetaching([
                    $moduleId => [
                        'is_active' => true,
                        'subscription_ends_at' => $existing->get($moduleId)?->pivot?->subscription_ends_at,
                    ],
                ]);
            }

            $toDisable = $existing->keys()->diff($selectedIds);
            if ($toDisable->isNotEmpty()) {
                $clientBusiness->modules()->updateExistingPivot($toDisable->all(), ['is_active' => false]);
            }

            activity()
                ->causedBy($request->user())
                ->performedOn($clientBusiness)
                ->log('Modules updated');

            return RequestResponse::ok('Modules assigned successfully.');
        });
    }
}
