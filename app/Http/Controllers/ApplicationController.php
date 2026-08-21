<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Exports\ApplicationExport;
use App\Http\RequestResponse;
use App\Mail\ApplicationReceived;
use App\Mail\ApplicationStageUpdated;
use App\Mail\InterviewScheduled;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Business;
use App\Models\Interview;
use App\Models\JobPost;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Traits\HandleTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationController extends Controller
{
    use HandleTransactions;

    public function index()
    {
        $page = 'Job Applications';
        $business = Business::findBySlug(session('active_business_slug'));
        $jobPosts = JobPost::where('business_id', $business->id)->get();

        return view('applications.index', compact('page', 'jobPosts'));
    }

    public function create()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        // show applicants for this business (including externals with null user)
        $applicants = Applicant::query()
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            })
            ->with('user')
            ->orderByDesc('id')
            ->get();

        $job_posts = JobPost::where('business_id', $business->id)->get();

        return view('applications.create', compact('applicants', 'job_posts'));
    }

    public function view($business, Application $application)
    {
        $businessModel = Business::findBySlug($business);

        $application = Application::query()
            ->where('id', $application->id)
            ->where('business_id', $businessModel->id)
            ->with(['applicant.user', 'jobPost', 'interviews', 'createdBy'])
            ->firstOrFail();

        // load external parts (no models required)
        $academics = DB::table('application_academics')
            ->where('application_id', $application->id)->orderBy('id')->get();

        $workExperiences = DB::table('application_work_experiences')
            ->where('application_id', $application->id)->orderBy('id')->get();

        $memberships = DB::table('application_memberships')
            ->where('application_id', $application->id)->orderBy('id')->get();

        $documents = DB::table('application_documents')
            ->where('application_id', $application->id)->orderBy('id')->get();

        return view('applications._view', compact(
            'application', 'academics', 'workExperiences', 'memberships', 'documents'
        ));
    }

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $query = Application::query()
            ->where('business_id', $business->id)
            ->with(['applicant.user', 'jobPost', 'createdBy', 'interviews']);

        // general filter (works with OR without user)
        if ($request->filled('filter')) {
            $filter = trim($request->input('filter'));

            $query->where(function ($q) use ($filter) {
                $q->whereHas('applicant', function ($aq) use ($filter) {
                    $aq->where('fullname', 'like', "%{$filter}%")
                        ->orWhere('idnumber', 'like', "%{$filter}%")
                        ->orWhere('phone', 'like', "%{$filter}%")
                        ->orWhere('country', 'like', "%{$filter}%")
                        ->orWhere('city', 'like', "%{$filter}%")
                        ->orWhere('home_county', 'like', "%{$filter}%");
                })
                ->orWhereHas('applicant.user', function ($uq) use ($filter) {
                    $uq->where('name', 'like', "%{$filter}%")
                        ->orWhere('email', 'like', "%{$filter}%")
                        ->orWhere('phone', 'like', "%{$filter}%");
                })
                ->orWhereHas('jobPost', function ($jq) use ($filter) {
                    $jq->where('title', 'like', "%{$filter}%");
                })
                ->orWhere('stage', 'like', "%{$filter}%");
            });
        }

        if ($request->filled('job_post_id')) {
            // UI uses job id; applications.job_post_id stores FK id
            $query->where('job_post_id', (int) $request->job_post_id);
        }

        if ($request->filled('location')) {
            $location = trim($request->location);
            $query->whereHas('applicant', function ($aq) use ($location) {
                $aq->where('city', 'like', "%{$location}%")
                    ->orWhere('home_county', 'like', "%{$location}%")
                    ->orWhere('country', 'like', "%{$location}%");
            });
        }

        $applications = $query->latest()->paginate(10);

        $view = view('applications._table', compact('applications'))->render();
        return RequestResponse::ok('Ok', $view);
    }

    public function edit(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $application = Application::query()
            ->where('id', (int) $validated['application_id'])
            ->where('business_id', $business->id)
            ->with('applicant.user', 'jobPost')
            ->firstOrFail();

        $applicants = Applicant::query()
            ->where(function ($q) use ($business) {
                $q->whereHas('applications', function ($aq) use ($business) {
                    $aq->where('business_id', $business->id);
                })->orWhere(function ($sub) use ($business) {
                    $sub->whereIn('created_by', function ($employeeQ) use ($business) {
                        $employeeQ->select('user_id')->from('employees')->where('business_id', $business->id);
                    })->orWhere('created_by', $business->user_id);
                });
            })
            ->with('user')
            ->orderByDesc('id')
            ->get();

        $job_posts = JobPost::where('business_id', $business->id)->get();

        $application_form = view('applications._form', compact('application', 'applicants', 'job_posts'))->render();
        return RequestResponse::ok('Ok', $application_form);
    }

    public function store(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'applicant_id' => [
                'required',
                'exists:applicants,id',
                function ($attribute, $value, $fail) use ($request, $business) {
                    $jobPost = JobPost::where('slug', $request->job_post_id)
                        ->where('business_id', $business->id)->first();

                    if (!$jobPost) {
                        $fail('Invalid job post for this business.');
                        return;
                    }

                    $exists = Application::where('applicant_id', (int)$value)
                        ->where('job_post_id', $jobPost->id)
                        ->exists();

                    if ($exists) $fail('You have already applied to this job.');
                },
            ],
            'job_post_id' => 'required|exists:job_posts,slug',
            'cover_letter' => 'nullable|string',
            'attachments.*' => 'file|mimes:pdf,doc,docx|max:5120',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            $jobPost = JobPost::where('slug', $request->job_post_id)
                ->where('business_id', $business->id)
                ->firstOrFail();

            $application = Application::create([
                'business_id' => $business->id,
                'location_id' => $jobPost->location_id ?? null,
                'applicant_id' => (int) $request->applicant_id,
                'job_post_id' => $jobPost->id,
                'cover_letter' => $request->cover_letter,
                'stage' => 'applied',
                'created_by' => Auth::id(),
            ]);

            $application->setStatus(Status::APPLIED);

            // attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $application->addMedia($file)->toMediaCollection('applications');
                }
            } else {
                Log::info('Application created for applicant without user', ['applicant_id' => $applicant->id]);
            }

            // send email only if applicant has a user + email
            $applicant = Applicant::with('user')->findOrFail((int)$request->applicant_id);
            if ($applicant->user && $applicant->user->email) {
                Mail::to($applicant->user->email)->send(new ApplicationReceived($application));
            }

            return RequestResponse::created('Application submitted successfully');
        });
    }

    /**
     * EXTERNAL STORE (Option B): Applicant may have NO user
     * - We store applicant info in applicants table (user_id nullable)
     * - We store academics/work/memberships/docs in separate tables (DB::table)
     * - We prevent duplicates per job by applicant idnumber + job_post_id
     */
    public function externalStore(Request $request)
    {
        try {
            $validated = $request->validate([
                // 'api_token' => 'required|string',
                'business_slug' => 'required|string|exists:businesses,slug',
'api_token'      => 'required|string',
                'jobId'     => ['required', 'exists:job_posts,slug'],

                // Part 1
                'full_name'   => ['required','string','max:255','regex:/^[\p{L}\s\'.-]+$/u'],
                'id_number'   => ['required','string','max:50'],
                'email'       => ['required','email','max:255'],
                'phone'       => ['required','string','max:30','regex:/^\+?[1-9]\d{1,14}$/'],
                'gender'      => ['required','string','in:male,female,other,prefer_not_say'],
                'dob'         => ['required','date','before:today'],
                'nationality' => ['required','string','max:100'],
                'plwd'        => ['required','boolean'],
                'home_county' => ['nullable','string','max:100'],
                'city'        => ['nullable','string','max:100'],

                // Part 2
                'academics'                           => ['required','array','min:1'],
                'academics.*.qualification_level'     => ['required','string','max:80'],
                'academics.*.institution_name'        => ['required','string','max:255'],
                'academics.*.institution_country'     => ['nullable','string','max:100'],
                'academics.*.qualification_name'      => ['required','string','max:255'],
                'academics.*.certificate_number'      => ['nullable','string','max:100'],
                'academics.*.year_completed'          => ['nullable','integer','min:1900','max:2100'],

                'academics_attachments'               => ['required','array'],
                'academics_attachments.*'             => ['required','array','min:1'],
                'academics_attachments.*.*'           => ['file','mimes:pdf,jpg,jpeg,png','max:5120'],

                // Part 3
                'work_experiences'                    => ['required','array','min:1'],
                'work_experiences.*.employer_name'    => ['required','string','max:255'],
                'work_experiences.*.employer_contact' => ['nullable','string','max:255'],
                'work_experiences.*.location'         => ['nullable','string','max:255'],
                'work_experiences.*.job_title'        => ['required','string','max:255'],
                'work_experiences.*.start_date'       => ['required','date'],
                'work_experiences.*.end_date'         => ['nullable','date'],
                'work_experiences.*.is_current'       => ['nullable','boolean'],
                'work_experiences.*.achievements'     => ['nullable','string'],

                // Part 4
                'memberships'                         => ['nullable','array'],
                'memberships.*.organization_name'     => ['required_with:memberships','string','max:255'],
                'memberships.*.membership_number'     => ['required_with:memberships','string','max:120'],
                'memberships.*.membership_type'       => ['nullable','string','max:100'],
                'memberships.*.year_joined'           => ['nullable','integer','min:1900','max:2100'],

                'membership_certificate'              => ['nullable','array'],
                'membership_certificate.*'            => ['file','mimes:pdf,jpg,jpeg,png','max:5120'],

                // Part 5
                'cv'           => ['required','file','mimes:pdf,doc,docx','max:5120'],
                'national_id'  => ['required','file','mimes:pdf,jpg,jpeg,png','max:5120'],
                'other_documents'   => ['nullable','array'],
                'other_documents.*' => ['file','mimes:pdf,doc,docx,jpg,jpeg,png','max:5120'],

                'cover_letter' => ['nullable','string','max:10000'],
            ], [
                'full_name.regex' => 'Full name should only contain letters, spaces, apostrophes, hyphens or dots.',
                'academics_attachments.required' => 'Attach at least one academic certificate/transcript per qualification.',
                'cv.required' => 'CV is required.',
                'national_id.required' => 'National ID is required.',
            ]);

            // Authorize API token (krest)
            // $business = Business::where('slug', 'krest')->first();
            // if (!$business || !$business->api_token || !password_verify($validated['api_token'], $business->api_token)) {
            //     return RequestResponse::unauthorized('Invalid or unauthorized API token.');
            // }

            // Resolve business from the slug the client sent, then verify THEIR token
$business = Business::where('slug', $validated['business_slug'])->first();
if (!$business || !$business->api_token || !password_verify($validated['api_token'], $business->api_token)) {
    return RequestResponse::unauthorized('Invalid or unauthorized API token.');
}
if (!$business->verified) {
    return RequestResponse::unauthorized('This business is not yet verified.');
}

            // Validate job post
            $jobPost = JobPost::where('slug', $validated['jobId'])
                ->where('business_id', $business->id)
                ->where('is_public', true)
                ->where('status', 'open')
                ->first();

            if (!$jobPost) {
                return RequestResponse::badRequest('Invalid or unavailable job post.');
            }
            if ($jobPost->closing_date && now()->gt($jobPost->closing_date)) {
                return RequestResponse::badRequest('This job post is closed.');
            }

            $nationality = trim((string)$validated['nationality']);
            $isKenya = strcasecmp($nationality, 'kenya') === 0;

            // Kenya / city rule (strict)
            if ($isKenya) {
                if (!$request->filled('home_county')) {
                    return RequestResponse::badRequest('Home county is required for Kenya applicants.');
                }
                if (!preg_match('/^\d+$/', (string)$validated['id_number'])) {
                    return RequestResponse::badRequest('For Kenya nationality, ID Number must be numeric.');
                }
            } else {
                if (!$request->filled('city')) {
                    return RequestResponse::badRequest('City is required for non-Kenya applicants.');
                }
            }

            $dob = Carbon::parse($validated['dob']);
            $age = $dob->age;
            if ($age < 18 || $age > 100) {
                return RequestResponse::badRequest('Date of birth results in invalid age. Applicant must be between 18 and 100.');
            }

            // Work experience date validation
            foreach ($validated['work_experiences'] as $i => $wx) {
                $isCurrent = (bool)($wx['is_current'] ?? false);
                $start = Carbon::parse($wx['start_date']);
                $end = !empty($wx['end_date']) ? Carbon::parse($wx['end_date']) : null;

                if ($isCurrent && $end) {
                    return RequestResponse::badRequest("Work experience #".($i+1).": end_date must be empty if marked current.");
                }
                if (!$isCurrent && !$end) {
                    return RequestResponse::badRequest("Work experience #".($i+1).": end_date is required if not marked current.");
                }
                if ($end && $end->lt($start)) {
                    return RequestResponse::badRequest("Work experience #".($i+1).": end_date cannot be before start_date.");
                }
            }

            // Membership cert required if memberships exist
            $memberships = $validated['memberships'] ?? [];
            if (!empty($memberships)) {
                foreach ($memberships as $idx => $m) {
                    if (!$request->hasFile("membership_certificate.$idx")) {
                        return RequestResponse::badRequest("Membership #".($idx+1).": membership certificate is required.");
                    }
                }
            }

            return DB::transaction(function () use ($validated, $request, $business, $jobPost, $age, $isKenya) {

                // 1) Upsert applicant WITHOUT creating user
                // Use idnumber as primary natural identifier (best in your use-case).
                // If you want also to factor email, add it here.
                $applicant = Applicant::query()->updateOrCreate(
                    ['idnumber' => $validated['id_number']],
                    [
                        'user_id'     => null,
                        'fullname'    => trim($validated['full_name']),
                        'phone'       => $validated['phone'],
                        'age'         => $age,
                        'dob'         => $validated['dob'],
                        'gender'      => $validated['gender'],
                        'plwd'        => (bool)$validated['plwd'],
                        'country'     => $validated['nationality'], // nationality stored in country column (your convention)
                        'home_county' => $isKenya ? $request->input('home_county') : null,
                        'city'        => $isKenya ? null : $request->input('city'),
                        'created_by'  => null,
                    ]
                );
                $applicant->setStatus(Status::ACTIVE);

                // 2) Prevent duplicate application per job (using applicant_id + job_post_id)
                $alreadyApplied = Application::where('applicant_id', $applicant->id)
                    ->where('job_post_id', $jobPost->id)
                    ->exists();

                if ($alreadyApplied) {
                    return RequestResponse::badRequest('You have already applied to this job.');
                }

                // 3) Create application
                $application = Application::create([
                    'business_id'  => $business->id,
                    'location_id'  => $jobPost->location_id ?? null,
                    'applicant_id' => $applicant->id,
                    'job_post_id'  => $jobPost->id,
                    'cover_letter' => $request->input('cover_letter'),
                    'stage'        => 'applied',
                    'created_by'   => null,
                ]);
                $application->setStatus(Status::APPLIED);

                // helper: record documents in application_documents
                $insertDoc = function ($docType, $label, $mediaOrNull, $fileOrNull) use ($application) {
                    $mediaId = $mediaOrNull?->id;
                    $fileName = $mediaOrNull?->file_name ?? $fileOrNull?->getClientOriginalName();
                    $mime = $mediaOrNull?->mime_type ?? $fileOrNull?->getMimeType();
                    $size = $mediaOrNull?->size ?? $fileOrNull?->getSize();

                    DB::table('application_documents')->insert([
                        'application_id' => $application->id,
                        'doc_type'       => $docType,
                        'label'          => $label,
                        'media_id'       => $mediaId,
                        'file_name'      => $fileName,
                        'mime_type'      => $mime,
                        'file_size'      => $size,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                };

                // 4) Academics + attachments
                foreach ($validated['academics'] as $idx => $acad) {
                    DB::table('application_academics')->insert([
                        'application_id'      => $application->id,
                        'qualification_level' => $acad['qualification_level'],
                        'institution_name'    => $acad['institution_name'],
                        'institution_country' => $acad['institution_country'] ?? null,
                        'qualification_name'  => $acad['qualification_name'],
                        'certificate_number'  => $acad['certificate_number'] ?? null,
                        'year_completed'      => $acad['year_completed'] ?? null,
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);

                    $files = $request->file("academics_attachments.$idx", []);
                    foreach ($files as $f) {
                        $media = $application->addMedia($f)->toMediaCollection('applications');
                        $insertDoc(
                            'academic_attachment',
                            ($acad['qualification_level'] ?? 'Academic') . ' - ' . ($acad['qualification_name'] ?? 'Attachment'),
                            $media,
                            $f
                        );
                    }
                }

                // 5) Work experiences
                foreach ($validated['work_experiences'] as $wx) {
                    DB::table('application_work_experiences')->insert([
                        'application_id'   => $application->id,
                        'employer_name'    => $wx['employer_name'],
                        'employer_contact' => $wx['employer_contact'] ?? null,
                        'location'         => $wx['location'] ?? null,
                        'job_title'        => $wx['job_title'],
                        'start_date'       => $wx['start_date'],
                        'end_date'         => $wx['end_date'] ?? null,
                        'is_current'       => (bool)($wx['is_current'] ?? false),
                        'achievements'     => $wx['achievements'] ?? null,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }

                // 6) Memberships + certs
                if (!empty($memberships)) {
                    foreach ($memberships as $idx => $m) {
                        DB::table('application_memberships')->insert([
                            'application_id'    => $application->id,
                            'organization_name' => $m['organization_name'],
                            'membership_number' => $m['membership_number'],
                            'membership_type'   => $m['membership_type'] ?? null,
                            'year_joined'       => $m['year_joined'] ?? null,
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ]);

                        $certFile = $request->file("membership_certificate.$idx");
                        if ($certFile) {
                            $media = $application->addMedia($certFile)->toMediaCollection('applications');
                            $insertDoc(
                                'membership_certificate',
                                $m['organization_name'].' ('.$m['membership_number'].')',
                                $media,
                                $certFile
                            );
                        }
                    }
                }

                // 7) Required docs: CV + National ID + others
                if ($request->hasFile('cv')) {
                    $f = $request->file('cv');
                    $media = $application->addMedia($f)->toMediaCollection('applications');
                    $insertDoc('cv', 'Curriculum Vitae', $media, $f);
                }

                if ($request->hasFile('national_id')) {
                    $f = $request->file('national_id');
                    $media = $application->addMedia($f)->toMediaCollection('applications');
                    $insertDoc('national_id', 'National ID', $media, $f);
                }

                $otherDocs = $request->file('other_documents', []);
                foreach ($otherDocs as $f) {
                    $media = $application->addMedia($f)->toMediaCollection('applications');
                    $insertDoc('other', $f->getClientOriginalName(), $media, $f);
                }

                // 8) Leads: only if you have a user. Here we don't.
                // If later you decide to create a user after review, create lead then.

                return RequestResponse::ok('Application submitted successfully', [
                    'application_id' => $application->id,
                    'job_title'      => $jobPost->title,
                    'status'         => $application->stage,
                ]);
            });

        } catch (\Illuminate\Validation\ValidationException $e) {
            return RequestResponse::badRequest('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('External application submission failed', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);
            return RequestResponse::badRequest('An unexpected error occurred: '.$e->getMessage());
        }
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));

        $application = Application::query()
            ->where('id', (int)$validated['application_id'])
            ->where('business_id', $business->id)
            ->with(['applicant.user', 'jobPost', 'interviews'])
            ->firstOrFail();

        // include extra parts
        $payload = $application->toArray();
        $payload['academics'] = DB::table('application_academics')->where('application_id', $application->id)->get();
        $payload['work_experiences'] = DB::table('application_work_experiences')->where('application_id', $application->id)->get();
        $payload['memberships'] = DB::table('application_memberships')->where('application_id', $application->id)->get();
        $payload['documents'] = DB::table('application_documents')->where('application_id', $application->id)->get();

        return RequestResponse::ok('Ok', $payload);
    }

    public function reports()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $applications = Application::where('business_id', $business->id)
            ->with('applicant.user', 'jobPost')
            ->latest()
            ->get();

        return view('applications.reports', compact('applications'));
    }

    public function update(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'cover_letter' => 'nullable|string',
            'stage' => 'required|string|in:applied,shortlisted,in_progress,rejected,finished',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            $application = Application::where('id', (int)$request->application_id)
                ->where('business_id', $business->id)
                ->with('applicant.user')
                ->firstOrFail();

            $oldStage = $application->stage;

            $application->update($request->only(['cover_letter', 'stage']));

            // email only if applicant has user
            if ($oldStage !== $request->stage && $application->applicant && $application->applicant->user && $application->applicant->user->email) {
                Mail::to($application->applicant->user->email)->send(new ApplicationStageUpdated($application));
            }

            return RequestResponse::ok('Application updated successfully', $application);
        });
    }

    public function updateStage(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
            'stage' => 'required|string|in:applied,shortlisted,in_progress,rejected,finished',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            $apps = Application::whereIn('id', $request->application_ids)
                ->where('business_id', $business->id)
                ->with('applicant.user')
                ->get();

            foreach ($apps as $app) {
                $oldStage = $app->stage;
                $app->update(['stage' => $request->stage]);

                if ($oldStage !== $request->stage && $app->applicant && $app->applicant->user && $app->applicant->user->email) {
                    Mail::to($app->applicant->user->email)->send(new ApplicationStageUpdated($app));
                }
            }

            return RequestResponse::ok("Stage updated to {$request->stage} for selected applications.");
        });
    }

    public function shortlist(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            $apps = Application::whereIn('id', $request->application_ids)
                ->where('business_id', $business->id)
                ->with('applicant.user')
                ->get();

            foreach ($apps as $app) {
                $oldStage = $app->stage;
                $app->update(['stage' => 'shortlisted']);

                if ($oldStage !== 'shortlisted' && $app->applicant && $app->applicant->user && $app->applicant->user->email) {
                    Mail::to($app->applicant->user->email)->send(new ApplicationStageUpdated($app));
                }
            }

            return RequestResponse::ok('Selected applications shortlisted successfully.');
        });
    }

    public function scheduleInterview(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'interview_date' => 'required|date',
            'interview_time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'interviewer_id' => 'required|exists:users,id',
            'type' => 'required|in:phone,video,in-person',
            'meeting_link' => 'nullable|url',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            $application = Application::where('id', (int)$request->application_id)
                ->where('business_id', $business->id)
                ->with('applicant.user')
                ->firstOrFail();

            $scheduledAt = Carbon::parse("{$request->interview_date} {$request->interview_time}")->toDateTimeString();

            $interview = Interview::create([
                'application_id' => $application->id,
                'scheduled_at' => $scheduledAt,
                'location' => $request->location,
                'interviewer_id' => $request->interviewer_id,
                'type' => $request->type,
                'meeting_link' => $request->meeting_link,
                'status' => 'scheduled',
                'created_by' => Auth::id(),
            ]);

            $application->update(['stage' => 'in_progress']);

            // Only email if applicant has user
            if ($application->applicant && $application->applicant->user && $application->applicant->user->email) {
                Mail::to($application->applicant->user->email)->send(new InterviewScheduled($application, $interview));
            }

            return RequestResponse::ok('Interview scheduled successfully.');
        });
    }

    public function destroy(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
        ]);

        return $this->handleTransaction(function () use ($request, $business) {
            Application::whereIn('id', $request->application_ids)
                ->where('business_id', $business->id)
                ->delete();

            return RequestResponse::ok('Selected applications deleted successfully');
        });
    }

    public function kpis()
{
    $business = Business::findBySlug(session('active_business_slug'));

    $base = Application::where('business_id', $business->id);

    return response()->json([
        'total'       => (clone $base)->count(),
        'pending'     => (clone $base)->where('stage', 'pending')->count(),
        'under_review'=> (clone $base)->where('stage', 'under_review')->count(),
        'shortlisted' => (clone $base)->where('stage', 'shortlisted')->count(),
        'rejected'    => (clone $base)->where('stage', 'rejected')->count(),
        'interviewed' => (clone $base)->whereHas('interviews')->count(),
    ]);
}

    public function export(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));

        $applications = Application::where('business_id', $business->id)
            ->with('applicant.user', 'jobPost')
            ->get();

        if ($applications->isEmpty()) {
            return response()->json(['message' => 'No applications available to export'], 400);
        }

        return Excel::download(new ApplicationExport($applications), 'applications_' . now()->format('Y-m-d_His') . '.xlsx');
    }


}
