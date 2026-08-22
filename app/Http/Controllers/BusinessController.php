<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Client;
use App\Models\Module;
use App\Models\Business;
use App\Models\Industry;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use App\Notifications\BusinessChangedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class BusinessController extends Controller
{
    use HandleTransactions;

    public function create(Request $request)
    {
        $page = "Business Setup";
        $description = "Fill in your business details to get started with your account.";
        $industries = Industry::all();
        $user = auth()->user();
        // Only prefill when the user's very first setup wizard is still
        // in progress (status still 'setup') - that's a resume, not a
        // fresh business. Once status has moved past 'setup' (they've
        // completed at least one business), this page is reached via
        // "Add Business" instead, which must start from a blank form -
        // otherwise it'd silently reload the OLD business's data,
        // including several unique-constrained fields (company_name,
        // registration_no, tax_pin_no, business_license_no, phone) that
        // would then fail validation unless every one of them is edited.
        $business = $user->status === Status::SETUP
            ? Business::where('user_id', $user->id)->first()
            : null;

        return view('auth.business-setup', compact('page', 'description', 'industries', 'business'));
    }

    public function redirectToDashboard(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('business-admin')) {
            // businesses() (hasMany), not the naive business() hasOne -
            // an admin who has added more than one business (see the Add
            // Business feature) still needs a deterministic "first/default"
            // one to land on; Switch Business is how they reach the rest.
            $business = $user->businesses()->first();
            if ($user->status === 'setup' || !$business) {
                return redirect()->route('setup.business');
            } elseif ($user->status === 'module') {
                return redirect()->route('setup.modules');
            }
            session(['active_business_slug' => $business->slug, 'active_role' => 'business-admin']);
            return redirect()->route('business.index', ['business' => $business->slug]);
        } elseif ($user->hasRole('business-hr') || $user->hasRole('business-finance')) {
            $business = $user->activeEmployee()?->business;
            if (!$business) {
                return redirect()->route('setup.business');
            }
            $isHr = $user->hasRole('business-hr');
            session(['active_business_slug' => $business->slug, 'active_role' => $isHr ? 'business-hr' : 'business-finance']);
            // business-hr's permission set deliberately excludes
            // access.dashboard (PermissionSeeder, "aligned with
            // restricted-hr") - business.index would 403 them immediately.
            // Matches the same fix already applied to
            // AuthenticatedSessionController::getRedirectUrlForRole().
            return redirect()->route($isHr ? 'business.employees.index' : 'business.index', ['business' => $business->slug]);
        } elseif ($user->hasRole('business-employee')) {
            $business = $user->activeEmployee()?->business;
            if (!$business) {
                return redirect()->route('setup.business');
            }
            session(['active_business_slug' => $business->slug, 'active_role' => 'business-employee']);
            return redirect()->route('myaccount.index', ['business' => $business->slug]);
        }
        return redirect()->route('setup.business');
    }

    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'name' => 'required|string|max:255|unique:businesses,company_name',
    //         'company_size' => 'required|string|max:255',
    //         'industry' => 'required|string|max:255',
    //         'phone' => 'required|string|max:15',
    //         'country' => 'required|string|max:255',
    //         'code' => 'required|string|max:4',
    //         'registration_no' => 'required|string|max:255|unique:businesses,registration_no',
    //         'tax_pin_no' => 'required|string|max:255|unique:businesses,tax_pin_no',
    //         'business_license_no' => 'required|string|max:255|unique:businesses,business_license_no',
    //         'physical_address' => 'required|string|max:255',
    //         'logo' => 'required|file|image|max:1024',
    //         'registration_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
    //         'tax_pin_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
    //         'business_license_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
    //     ]);

    //     return $this->handleTransaction(function () use ($request, $validatedData) {
    //         try {
    //             $countryCode = $validatedData['code'];
    //             $phoneNumber = "+{$countryCode}{$validatedData['phone']}";
    //             $validator = Validator::make(['phone' => $phoneNumber], [
    //                 'phone' => 'unique:businesses,phone',
    //             ]);

    //             throw_if($validator->fails(), ValidationException::withMessages($validator->errors()->all()));

    //             $user = auth()->user();

    //             // Create new business
    //             $business = Business::create([
    //                 'user_id' => $user->id,
    //                 'company_name' => $validatedData['name'],
    //                 'company_size' => $validatedData['company_size'],
    //                 'industry' => $validatedData['industry'],
    //                 'phone' => $phoneNumber,
    //                 'code' => $validatedData['code'],
    //                 'country' => $validatedData['country'],
    //                 'registration_no' => $validatedData['registration_no'],
    //                 'tax_pin_no' => $validatedData['tax_pin_no'],
    //                 'business_license_no' => $validatedData['business_license_no'],
    //                 'physical_address' => $validatedData['physical_address'],
    //                 'verified' => false,
    //             ]);

    //             // Handle logo upload
    //             $business->clearMediaCollection('businesses');
    //             $business->addMediaFromRequest('logo')->toMediaCollection('businesses');

    //             // Handle other file uploads
    //             if ($request->hasFile('registration_certificate')) {
    //                 $business->clearMediaCollection('registration_certificates');
    //                 $business->addMediaFromRequest('registration_certificate')->toMediaCollection('registration_certificates');
    //             }
    //             if ($request->hasFile('tax_pin_certificate')) {
    //                 $business->clearMediaCollection('tax_pin_certificates');
    //                 $business->addMediaFromRequest('tax_pin_certificate')->toMediaCollection('tax_pin_certificates');
    //             }
    //             if ($request->hasFile('business_license_certificate')) {
    //                 $business->clearMediaCollection('business_license_certificates');
    //                 $business->addMediaFromRequest('business_license_certificate')->toMediaCollection('business_license_certificates');
    //             }

    //             $business->setStatus(Status::MODULE);

    //             if (session()->has('managing_business') && session()->has('employee_id')) {
    //                 $business_id = session('managing_business');
    //                 $employee_id = session('employee_id');
    //                 Client::create([
    //                     'business_id' => $business_id,
    //                     'client_business' => $business->id,
    //                     'employee_id' => $employee_id,
    //                 ])->setStatus(Status::ACTIVE);
    //             }

    //             $user->setStatus(Status::MODULE);
    //             $this->notifyBusinessOwner($business, $user, 'created');

    //             $redirect_url = route('setup.modules');

    //             return RequestResponse::created('Business registered successfully.', [
    //                 'redirect_url' => $redirect_url,
    //                 'business' => $business->fresh(['media'])
    //             ]);
    //         } catch (\Exception $e) {
    //             Log::error('Business store failed: ' . $e->getMessage(), ['exception' => $e]);
    //             throw $e;
    //         }
    //     });
    // }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:businesses,company_name',
            'company_size' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'code' => 'required|string|max:4',
            'registration_no' => 'required|string|max:255|unique:businesses,registration_no',
            'tax_pin_no' => 'required|string|max:255|unique:businesses,tax_pin_no',
            'business_license_no' => 'required|string|max:255|unique:businesses,business_license_no',
            'physical_address' => 'required|string|max:255',
            'logo' => 'required|file|image|max:1024',
            'registration_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
            'tax_pin_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
            'business_license_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
        ]);

        return $this->handleTransaction(function () use ($request, $validatedData) {
            try {
                $countryCode = $validatedData['code'];
                $phoneNumber = "+{$countryCode}{$validatedData['phone']}";
                $validator = Validator::make(['phone' => $phoneNumber], [
                    'phone' => 'unique:businesses,phone',
                ]);

                throw_if($validator->fails(), ValidationException::withMessages($validator->errors()->all()));

                $user = auth()->user();

                // Create new business
                $business = Business::create([
                    'user_id' => $user->id,
                    'company_name' => $validatedData['name'],
                    'company_size' => $validatedData['company_size'],
                    'industry' => $validatedData['industry'],
                    'phone' => $phoneNumber,
                    'code' => $validatedData['code'],
                    'country' => $validatedData['country'],
                    'registration_no' => $validatedData['registration_no'],
                    'tax_pin_no' => $validatedData['tax_pin_no'],
                    'business_license_no' => $validatedData['business_license_no'],
                    'physical_address' => $validatedData['physical_address'],
                    'verified' => false,
                ]);

                // Assign business-admin role
                $user->assignRole('business-admin');

                // Set session values
                session(['active_role' => 'business-admin', 'active_business_slug' => $business->slug]);

                // Handle logo upload
                $business->clearMediaCollection('businesses');
                $business->addMediaFromRequest('logo')->toMediaCollection('businesses');

                // Handle other file uploads
                if ($request->hasFile('registration_certificate')) {
                    $business->clearMediaCollection('registration_certificates');
                    $business->addMediaFromRequest('registration_certificate')->toMediaCollection('registration_certificates');
                }
                if ($request->hasFile('tax_pin_certificate')) {
                    $business->clearMediaCollection('tax_pin_certificates');
                    $business->addMediaFromRequest('tax_pin_certificate')->toMediaCollection('tax_pin_certificates');
                }
                if ($request->hasFile('business_license_certificate')) {
                    $business->clearMediaCollection('business_license_certificates');
                    $business->addMediaFromRequest('business_license_certificate')->toMediaCollection('business_license_certificates');
                }

                $business->setStatus(Status::MODULE);

                if (session()->has('managing_business') && session()->has('employee_id')) {
                    $business_id = session('managing_business');
                    $employee_id = session('employee_id');
                    Client::create([
                        'business_id' => $business_id,
                        'client_business' => $business->id,
                        'employee_id' => $employee_id,
                    ])->setStatus(Status::ACTIVE);
                }

                $user->setStatus(Status::MODULE);
                $this->notifyBusinessOwner($business, $user, 'created');

                $redirect_url = route('setup.modules');

                return RequestResponse::created('Business registered successfully.', [
                    'redirect_url' => $redirect_url,
                    'business' => $business->fresh(['media'])
                ]);
            } catch (\Exception $e) {
                Log::error('Business store failed: ' . $e->getMessage(), ['exception' => $e]);
                throw $e;
            }
        });
    }

    public function saveModules(Request $request)
    {
        $validatedData = $request->validate([
            'business_slug' => 'required|exists:businesses,slug',
            'modules' => 'required|array',
            'modules.*' => 'exists:modules,slug',
        ]);

        return $this->handleTransaction(function () use ($validatedData, $request) {
            $user = $request->user();
            $business = Business::findBySlug($validatedData['business_slug']);

            $moduleIds = Module::whereIn('slug', $validatedData['modules'])->pluck('id');
            $business->modules()->sync($moduleIds);

            session()->forget(['managing_business', 'employee_id']);
            session(['active_business_slug' => $business->slug]);

            $user->setStatus(Status::ACTIVE);
            $business->setStatus(Status::ACTIVE);

            $redirect_url = $business->verified ? route('business.index', $business) : route('business.activate', $business->slug);

            return RequestResponse::ok('Modules saved successfully.', ['redirect_url' => $redirect_url, 'business' => $business]);
        });
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'business_slug' => 'required|string|exists:businesses,slug',
            'name' => 'required|string|max:255|unique:businesses,company_name,' . Business::findBySlug($request->business_slug)->id,
            'company_size' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'country' => 'required|string|max:255',
            'code' => 'required|string|max:4',
            'registration_no' => 'required|string|max:255|unique:businesses,registration_no,' . Business::findBySlug($request->business_slug)->id,
            'tax_pin_no' => 'required|string|max:255|unique:businesses,tax_pin_no,' . Business::findBySlug($request->business_slug)->id,
            'business_license_no' => 'required|string|max:255|unique:businesses,business_license_no,' . Business::findBySlug($request->business_slug)->id,
            'physical_address' => 'required|string|max:255',
            'logo' => 'nullable|file|image|max:1024',
            'registration_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
            'tax_pin_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
            'business_license_certificate' => 'nullable|file|mimes:pdf,image,docx|max:2048',
        ]);

        return $this->handleTransaction(function () use ($request, $validatedData) {
            $countryCode = $validatedData['code'];
            $phoneNumber = "+{$countryCode}{$validatedData['phone']}";
            $business = Business::findBySlug($validatedData['business_slug']);

            $validator = Validator::make(['phone' => $phoneNumber], [
                'phone' => 'unique:businesses,phone,' . $business->id,
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $business->update([
                'company_name' => $validatedData['name'],
                'company_size' => $validatedData['company_size'],
                'industry' => $validatedData['industry'],
                'phone' => $phoneNumber,
                'code' => $validatedData['code'],
                'country' => $validatedData['country'],
                'registration_no' => $validatedData['registration_no'],
                'tax_pin_no' => $validatedData['tax_pin_no'],
                'business_license_no' => $validatedData['business_license_no'],
                'physical_address' => $validatedData['physical_address'],
            ]);

            if ($request->hasFile('logo')) {
                $business->clearMediaCollection('businesses');
                $business->addMediaFromRequest('logo')->toMediaCollection('businesses');
            }
            if ($request->hasFile('registration_certificate')) {
                $business->clearMediaCollection('registration_certificates');
                $business->addMediaFromRequest('registration_certificate')->toMediaCollection('registration_certificates');
            }
            if ($request->hasFile('tax_pin_certificate')) {
                $business->clearMediaCollection('tax_pin_certificates');
                $business->addMediaFromRequest('tax_pin_certificate')->toMediaCollection('tax_pin_certificates');
            }
            if ($request->hasFile('business_license_certificate')) {
                $business->clearMediaCollection('business_license_certificates');
                $business->addMediaFromRequest('business_license_certificate')->toMediaCollection('business_license_certificates');
            }

            $this->notifyBusinessOwner($business, auth()->user(), 'updated');

            $redirect_url = route('business.organization-setup', $business->slug);

            return RequestResponse::ok('Business updated successfully.', [
                'success' => true,
                'redirect_url' => $redirect_url,
                'business' => $business->fresh(['media'])
            ]);
        });
    }

    public function activate(Request $request, $slug)
    {
        $business = Business::findBySlug($slug);
        if (!$business) {
            abort(404, 'Business not found');
        }

        $page = "Activate Your Business";
        $description = "Your business is pending activation. Please ensure all required documents are uploaded.";
        $industries = Industry::all();

        return view('business.activate', compact('business', 'page', 'description', 'industries'));
    }

    public function setup($slug)
    {
        $business = Business::findBySlug($slug);
        if (!$business) {
            abort(404, 'Business not found');
        }

        $page = "Business Setup";
        $description = "Update your business details here.";
        $industries = Industry::all();

        return view('business.setup', compact('business', 'page', 'description', 'industries'));
    }

    public function fetch(Request $request)
    {
        $businesses = Business::where('user_id', auth()->id())->with('media')->get();
        return RequestResponse::ok('Businesses fetched successfully.', $businesses);
    }

    public function destroy(Request $request)
    {
        $request->validate(['slug' => 'required|exists:businesses,slug']);

        return $this->handleTransaction(function () use ($request) {
            $business = Business::findBySlug($request->slug);
            if ($business->user_id !== auth()->id()) {
                return RequestResponse::forbidden('Unauthorized to delete this business.');
            }

            $business->delete();
            return RequestResponse::ok('Business deleted successfully.');
        });
    }

    protected function notifyBusinessOwner($business, $user, $action = 'updated')
    {
        try {
            $user->notify(new BusinessChangedNotification($business, $user, $action));
            return RequestResponse::ok('Notification sent successfully.');
        } catch (TransportException $e) {
            return RequestResponse::badRequest('Failed to send notification email.', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return RequestResponse::badRequest('An unexpected error occurred while sending the notification.', [
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ], 500);
        }
    }

    public function generateApiToken(Request $request, $businessSlug)
    {
        return $this->handleTransaction(function () use ($businessSlug) {
            $business = Business::where('slug', $businessSlug)->firstOrFail();
            if ($business->user_id !== auth()->id()) {
                return RequestResponse::badRequest('Business not found or unauthorized.');
            }
            if ($businessSlug !== config('business.main_slug')) {
                return RequestResponse::badRequest('API token generation is restricted to the platform business.');
            }
            do {
                $apiToken = Str::random(60);
            } while (Business::where('api_token', Hash::make($apiToken))->exists());

            $business->update([
                'api_token' => Hash::make($apiToken),
                'updated_at' => now(),
            ]);

            Log::info('API token generated', [
                'business_id' => $business->id,
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);

            session()->flash('api_token', $apiToken);
            session()->flash('api_token_warning', 'Previous API token is now invalid.');

            return redirect()->route('business.api-token', $businessSlug)
                ->with('message', 'API token generated successfully.');
        }, function ($exception) {
            Log::error('API token generation failed: ' . $exception->getMessage(), [
                'business_slug' => $businessSlug,
                'user_id' => auth()->id(),
            ]);
            return RequestResponse::badRequest('Failed to generate API token.');
        });
    }

    public function showApiTokenForm($businessSlug)
    {
        $business = Business::findBySlug($businessSlug);
        if (!$business || $business->user_id !== auth()->id()) {
            abort(403, 'Unauthorized.');
        }
        return view('business.api-token', compact('business'));
    }
    public function switchBackToAdmin(Request $request, $business)
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
}
