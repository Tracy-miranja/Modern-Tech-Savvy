<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Exports\ApplicantsExport;
use App\Http\RequestResponse;
use App\Models\Applicant;
use App\Models\Business;
use App\Models\JobPost;
use App\Models\User;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ApplicantController extends Controller
{
    use HandleTransactions;

    public function index()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $jobPosts = JobPost::where('business_id', $business->id)->get();

        return view('applicants.index', ['page' => 'Job Applicants', 'jobPosts' => $jobPosts]);
    }

    public function fetch(Request $request)
    {
        try {
            $business = Business::findBySlug(session('active_business_slug'));

            $query = Applicant::query()
                ->with(['user', 'applications.jobPost'])
                ->where(function ($q) use ($business) {
                    $q->whereHas('applications', function ($aq) use ($business) {
                        $aq->where('business_id', $business->id);
                    })->orWhere(function ($sub) use ($business) {
                        $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                            $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                        })->orWhere('created_by', $business->user_id);
                    });
                });

            $this->applyFilters($query, $request, $business);

            $applicants = $query->latest()->paginate(10);
            $table = view('applicants._table', compact('applicants'))->render();

            return RequestResponse::ok('Ok', $table);
        } catch (\Exception $e) {
            Log::error('Error fetching applicants: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return RequestResponse::badRequest('Failed to fetch applicants: '.$e->getMessage());
        }
    }

    public function filter(Request $request)
    {
        return $this->fetch($request);
    }

    private function applyFilters($query, Request $request, Business $business)
    {
        if ($request->filled('filter')) {
            $filter = trim($request->input('filter'));

            $query->where(function ($q) use ($filter) {
                $q->where('fullname', 'like', "%{$filter}%")
                    ->orWhere('idnumber', 'like', "%{$filter}%")
                    ->orWhere('phone', 'like', "%{$filter}%")
                    ->orWhere('country', 'like', "%{$filter}%")
                    ->orWhere('city', 'like', "%{$filter}%")
                    ->orWhere('home_county', 'like', "%{$filter}%")
                    ->orWhereHas('user', function ($uq) use ($filter) {
                        $uq->where('name', 'like', "%{$filter}%")
                            ->orWhere('email', 'like', "%{$filter}%")
                            ->orWhere('phone', 'like', "%{$filter}%");
                    });
            });
        }

        if ($request->filled('job_post_id')) {
            $jobId = (int) $request->job_post_id;
            $query->whereHas('applications', function ($aq) use ($jobId, $business) {
                $aq->where('business_id', $business->id)
                    ->where('job_post_id', $jobId);
            });
        }

        if ($request->filled('location')) {
            $location = trim($request->location);
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', "%{$location}%")
                    ->orWhere('home_county', 'like', "%{$location}%")
                    ->orWhere('country', 'like', "%{$location}%");
            });
        }
    }

    public function create()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $users = User::whereDoesntHave('applicant')->get();
        $jobPosts = JobPost::where('business_id', $business->id)->get();

        $applicant = null;
        return view('applicants.create', compact('users', 'jobPosts', 'applicant'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'linkedin_profile' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'experience_level' => 'nullable|string|in:Entry-level,Mid-level,Senior',
            'education_level' => 'nullable|string|in:High School,Bachelor\'s,Master\'s,PhD',
            'desired_salary' => 'nullable|numeric',
            'job_preferences' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:100',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $business = Business::findBySlug(session('active_business_slug'));

            $countryCode = $request->code;
            $phoneNumber = "+{$countryCode}{$request->phone}";

            $validator = Validator::make(['phone' => $phoneNumber], [
                'phone' => 'unique:users,phone',
            ]);
            throw_if($validator->fails(), ValidationException::class, $validator);

            $name = trim("{$request->first_name} {$request->middle_name} {$request->last_name}");

            $user = User::create([
                'name' => $name,
                'email' => $request->email,
                'phone' => $phoneNumber,
                'password' => Hash::make($request->password),
                'country' => $request->country,
            ]);

            $user->assignRole('applicant');
            $user->setStatus(Status::ACTIVE);

            $request->hasFile('image')
                ? $user->addMediaFromRequest('image')->toMediaCollection('avatars')
                : $user->addMediaFromBase64(createAvatarImageFromName($name))->toMediaCollection('avatars');

            $applicant = Applicant::create([
                'user_id' => $user->id,
                'fullname' => $name,
                'phone' => $phoneNumber,
                'country' => $request->country,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'linkedin_profile' => $request->linkedin_profile,
                'portfolio_url' => $request->portfolio_url,
                'current_job_title' => $request->current_job_title,
                'current_company' => $request->current_company,
                'experience_level' => $request->experience_level,
                'education_level' => $request->education_level,
                'desired_salary' => $request->desired_salary,
                'job_preferences' => $request->job_preferences,
                'source' => $request->source,
                'created_by' => auth()->id(),
            ]);

            $applicant->setStatus(Status::ACTIVE);
            return RequestResponse::created('Applicant created successfully');
        });
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $applicant = Applicant::with('user')
            ->where('id', (int)$validated['applicant_id'])
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            })
            ->firstOrFail();

        $users = User::whereDoesntHave('applicant')
            ->when($applicant->user_id, fn($q) => $q->orWhere('id', $applicant->user_id))
            ->get();

        $jobPosts = JobPost::where('business_id', $business->id)->get();
        $form = view('applicants._form', compact('applicant', 'users', 'jobPosts'))->render();

        return RequestResponse::ok('Ok', $form);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:applicants,id',

            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',

            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',

            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'home_county' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',

            'linkedin_profile' => 'nullable|url',
            'portfolio_url' => 'nullable|url',
            'summary' => 'nullable|string',

            'current_job_title' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'experience_level' => 'nullable|string|in:Entry-level,Mid-level,Senior',
            'education_level' => 'nullable|string|in:High School,Bachelor\'s,Master\'s,PhD',
            'desired_salary' => 'nullable|string',
            'job_preferences' => 'nullable|string',
            'source' => 'nullable|string|max:255',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            $applicant = Applicant::with('user')
                ->where('id', (int)$validated['applicant_id'])
                ->where(function ($q) use ($business) {
                    $q->whereHas('applications', function ($aq) use ($business) {
                        $aq->where('business_id', $business->id);
                    })->orWhere(function ($sub) use ($business) {
                        $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                            $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                        })->orWhere('created_by', $business->user_id);
                    });
                })
                ->firstOrFail();

            if ($applicant->user) {
                $user = $applicant->user;

                $nameParts = array_filter([
                    $validated['first_name'] ?? null,
                    $validated['middle_name'] ?? null,
                    $validated['last_name'] ?? null,
                ]);

                if (!empty($nameParts)) {
                    $user->name = trim(implode(' ', $nameParts));

                    $applicant->fullname = $user->name;
                }

                if (!empty($validated['email'])) {

                    if ($validated['email'] !== $user->email) {
                        $requestValidator = Validator::make(
                            ['email' => $validated['email']],
                            ['email' => 'unique:users,email,' . $user->id]
                        );
                        throw_if($requestValidator->fails(), ValidationException::class, $requestValidator);
                    }
                    $user->email = $validated['email'];
                }

                if (!empty($validated['phone'])) {
                    $user->phone = $validated['phone'];
                    $applicant->phone = $validated['phone'];
                }

                $user->save();
            } else {

                $nameParts = array_filter([
                    $validated['first_name'] ?? null,
                    $validated['middle_name'] ?? null,
                    $validated['last_name'] ?? null,
                ]);
                if (!empty($nameParts)) {
                    $applicant->fullname = trim(implode(' ', $nameParts));
                }
                if (!empty($validated['phone'])) {
                    $applicant->phone = $validated['phone'];
                }
            }

            $applicant->fill(array_filter([
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'home_county' => $validated['home_county'] ?? null,
                'state' => $validated['state'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'country' => $validated['country'] ?? null,
                'linkedin_profile' => $validated['linkedin_profile'] ?? null,
                'portfolio_url' => $validated['portfolio_url'] ?? null,
                'summary' => $validated['summary'] ?? null,
                'current_job_title' => $validated['current_job_title'] ?? null,
                'current_company' => $validated['current_company'] ?? null,
                'experience_level' => $validated['experience_level'] ?? null,
                'education_level' => $validated['education_level'] ?? null,
                'desired_salary' => $validated['desired_salary'] ?? null,
                'job_preferences' => $validated['job_preferences'] ?? null,
                'source' => $validated['source'] ?? null,
            ]));

            $applicant->save();
            $applicant->setStatus(Status::ACTIVE);

            return RequestResponse::ok('Applicant updated successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'applicant_ids' => 'required|array',
            'applicant_ids.*' => 'exists:applicants,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = Business::findBySlug(session('active_business_slug'));

            $applicants = Applicant::with(['applications', 'skills', 'user'])
                ->whereIn('id', $validated['applicant_ids'])
                ->where(function ($q) use ($business) {
                    $q->whereHas('applications', function ($aq) use ($business) {
                        $aq->where('business_id', $business->id);
                    })->orWhere(function ($sub) use ($business) {
                        $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                            $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                        })->orWhere('created_by', $business->user_id);
                    });
                })
                ->get();

            if ($applicants->isEmpty()) {
                return RequestResponse::badRequest('No applicants found for this business.');
            }

            foreach ($applicants as $applicant) {

                $applicant->applications()->delete();
                $applicant->skills()->detach();
                $user = $applicant->user;
                $applicant->delete();

                if ($user) $user->delete();
            }

            return RequestResponse::ok('Selected applicants deleted successfully.');
        });
    }

    public function view($business, Applicant $applicant)
    {
        $businessModel = Business::findBySlug($business);

        $applicant = Applicant::with(['user', 'applications.jobPost'])
            ->where('id', $applicant->id)
            ->where(function ($q) use ($businessModel) {
                $q->whereHas('applications', function ($aq) use ($businessModel) {
                    $aq->where('business_id', $businessModel->id);
                })->orWhere(function ($sub) use ($businessModel) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($businessModel) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $businessModel->id);
                    })->orWhere('created_by', $businessModel->user_id);
                });
            })
            ->firstOrFail();

        $applications = $applicant->applications()->with('jobPost')
            ->where('business_id', $businessModel->id)
            ->get();

        return view('applicants._view', compact('applicant', 'applications'));
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $applicant = Applicant::with(['user', 'applications.jobPost'])
            ->where('id', (int)$validated['applicant_id'])
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            })
            ->firstOrFail();

        return RequestResponse::ok('Ok', $applicant->toArray());
    }

    public function downloadDocument(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'required|exists:applicants,id',
            'media_id' => 'required|exists:media,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $applicant = Applicant::with('applications')
            ->where('id', (int)$validated['applicant_id'])
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            })
            ->firstOrFail();

        $media = $applicant->applications
            ->flatMap->getMedia('applications')
            ->firstWhere('id', (int)$validated['media_id']);

        if (!$media) {
            return RequestResponse::badRequest('Document not found.');
        }

        $fileStream = response()->streamDownload(function () use ($media) {
            echo file_get_contents($media->getPath());
        }, $media->file_name, ['Content-Type' => $media->mime_type]);

        $fileStream->headers->set('X-Filename', $media->file_name);
        return $fileStream;
    }

    public function export(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $query = Applicant::query()
            ->with(['user', 'applications.jobPost'])
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            });

        $this->applyFilters($query, $request, $business);

        return Excel::download(new ApplicantsExport($query->get()), 'applicants_' . now()->format('Ymd_His') . '.xlsx');
    }
}