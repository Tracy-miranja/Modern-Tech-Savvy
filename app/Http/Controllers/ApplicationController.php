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
use App\Models\User;
use App\Traits\HandleTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    use HandleTransactions;

    public function index()
    {
        $page = 'Job Applications';
        $jobPosts = JobPost::all();
        return view('applications.index', compact('page', 'jobPosts'));
    }

    public function create()
    {
        $applicants = Applicant::with('user')->get();
        $job_posts = JobPost::all();
        return view('applications.create', compact('applicants', 'job_posts'));
    }

    public function view($business, Application $application)
    {
        $application->load('applicant.user', 'jobPost', 'interviews', 'createdBy');
        return view('applications.view', compact('application'));
    }

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $query = $business->applications()->with(['applicant.user', 'jobPost', 'createdBy', 'interviews']);

        if ($request->has('filter') && !empty($request->input('filter'))) {
            $filter = $request->input('filter');
            $query->where(function ($q) use ($filter) {
                $q->whereHas('applicant.user', function ($q) use ($filter) {
                    $q->where('name', 'like', "%$filter%")
                        ->orWhere('email', 'like', "%$filter%");
                })->orWhereHas('jobPost', function ($q) use ($filter) {
                    $q->where('title', 'like', "%$filter%");
                })->orWhere('stage', 'like', "%$filter%");
            });
        }

        if ($request->has('job_post_id') && !empty($request->input('job_post_id'))) {
            $query->where('job_post_id', $request->job_post_id);
        }

        if ($request->has('location') && !empty($request->input('location'))) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->location}%");
            });
        }

        $applications = $query->paginate(10);
        $view = view('applications._table', compact('applications'))->render();
        return RequestResponse::ok('Ok', $view);
    }

    public function edit(Request $request)
    {
        $validatedData = $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $application = Application::with('applicant.user', 'jobPost')->findOrFail($validatedData['application_id']);
        $applicants = Applicant::with('user')->get();
        $job_posts = JobPost::all();
        $application_form = view('applications._form', compact('application', 'applicants', 'job_posts'))->render();
        return RequestResponse::ok('Ok', $application_form);
    }

    public function store(Request $request)
{
    $request->validate([
        'applicant_id' => [
            'required',
            'exists:applicants,id',
            function ($attribute, $value, $fail) use ($request) {
                $job_post = JobPost::findBySlug($request->job_post_id);
                if (Application::where('applicant_id', $value)
                    ->where('job_post_id', $job_post->id)
                    ->exists()
                ) {
                    $fail('You have already applied to this job.');
                }
            },
        ],
        'job_post_id' => 'required|exists:job_posts,slug',
        'cover_letter' => 'nullable|string',
        'attachments.*' => 'file|mimes:pdf,doc,docx|max:2048',
    ]);

    return $this->handleTransaction(function () use ($request) {
        $job_post = JobPost::findBySlug($request->job_post_id);
        $business = $job_post->business;
        $location = $job_post->location;

        $application = Application::create([
            'business_id' => $business?->id,
            'location_id' => $location?->id,
            'applicant_id' => $request->applicant_id,
            'job_post_id' => $job_post->id,
            'cover_letter' => $request->cover_letter,
            'stage' => 'applied',
            'created_by' => Auth::id(),
        ]);

        $application->setStatus(Status::APPLIED);

        $applicant = Applicant::findOrFail($request->applicant_id);
        $user = $applicant->user;

        // Only create lead if applicant has an associated user
        if ($user) {
            $amsol = Business::where('slug', 'amsol')->first();
            if (!$amsol) {
                throw new \Exception('Amsol business not found');
            }

            $leadExists = Lead::where('user_id', $user->id)->exists();
            if (!$leadExists) {
                $leadData = [
                    'business_id' => $amsol->id,
                    'user_id' => $user->id,
                    'name' => $user->name ?? 'Unknown',
                    'email' => $user->email ?? 'unknown@example.com',
                    'phone' => $user->phone,
                    'source' => 'job_application',
                    'status' => 'new',
                    'label' => 'Applicant',
                ];
                Log::debug('Lead data prepared', ['lead_data' => $leadData]);

                try {
                    $lead = Lead::create($leadData);
                    if (!$lead || !$lead->id) {
                        Log::error('Failed to create lead or lead ID missing', ['user_id' => $user->id, 'lead_data' => $leadData]);
                        throw new \Exception('Lead creation failed');
                    }
                    Log::debug('Lead created successfully', ['lead_id' => $lead->id, 'user_id' => $user->id]);

                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $user->id,
                        'activity_type' => 'note',
                        'description' => 'Lead created from internal job application for job post ID: ' . $job_post->id,
                    ]);
                    Log::debug('Lead activity created', ['lead_id' => $lead->id, 'user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::error('Lead creation error', ['user_id' => $user->id, 'error' => $e->getMessage(), 'lead_data' => $leadData]);
                    throw $e;
                }
            }
        } else {
            Log::info('Application created for applicant without user', ['applicant_id' => $applicant->id]);
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $application->addMedia($file)->toMediaCollection('applications');
            }
        }

        // Only send email if applicant has a user with email
        if ($user && $user->email) {
            Mail::to($user->email)->send(new ApplicationReceived($application));
        }

        return RequestResponse::created('Application submitted successfully');
    });
}

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'applicant_id' => [
    //             'required',
    //             'exists:applicants,id',
    //             function ($attribute, $value, $fail) use ($request) {
    //                 $job_post = JobPost::findBySlug($request->job_post_id);
    //                 if (Application::where('applicant_id', $value)
    //                     ->where('job_post_id', $job_post->id)
    //                     ->exists()
    //                 ) {
    //                     $fail('You have already applied to this job.');
    //                 }
    //             },
    //         ],
    //         'job_post_id' => 'required|exists:job_posts,slug',
    //         'cover_letter' => 'nullable|string',
    //         'attachments.*' => 'file|mimes:pdf,doc,docx|max:2048',
    //     ]);

    //     return $this->handleTransaction(function () use ($request) {
    //         $job_post = JobPost::findBySlug($request->job_post_id);
    //         $business = $job_post->business;
    //         $location = $job_post->location;

    //         $application = Application::create([
    //             'business_id' => $business?->id,
    //             'location_id' => $location?->id,
    //             'applicant_id' => $request->applicant_id,
    //             'job_post_id' => $job_post->id,
    //             'cover_letter' => $request->cover_letter,
    //             'stage' => 'applied',
    //             'created_by' => Auth::id(),
    //         ]);

    //         $application->setStatus(Status::APPLIED);

    //         $amsol = Business::where('slug', 'amsol')->first();
    //         if (!$amsol) {
    //             throw new \Exception('Amsol business not found');
    //         }

    //         $applicant = Applicant::findOrFail($request->applicant_id);
    //         $user = $applicant->user;
    //         if (!$user) {
    //             throw new \Exception('Applicant has no associated user');
    //         }

    //         $leadExists = Lead::where('user_id', $user->id)->exists();
    //         if ($leadExists) {
    //             throw new \Exception('Lead already exists.');
    //         } else {
    //             $leadData = [
    //                 'business_id' => $amsol->id,
    //                 'user_id' => $user->id,
    //                 'name' => $user->name ?? 'Unknown',
    //                 'email' => $user->email ?? 'unknown@example.com',
    //                 'phone' => $user->phone,
    //                 'source' => 'job_application',
    //                 'status' => 'new',
    //                 'label' => 'Applicant',
    //             ];
    //             Log::debug('Lead data prepared', ['lead_data' => $leadData]);

    //             try {
    //                 $lead = Lead::create($leadData);
    //                 if (!$lead || !$lead->id) {
    //                     Log::error('Failed to create lead or lead ID missing', ['user_id' => $user->id, 'lead_data' => $leadData]);
    //                     throw new \Exception('Lead creation failed');
    //                 }
    //                 Log::debug('Lead created successfully', ['lead_id' => $lead->id, 'user_id' => $user->id]);

    //                 LeadActivity::create([
    //                     'lead_id' => $lead->id,
    //                     'user_id' => $user->id,
    //                     'activity_type' => 'note',
    //                     'description' => 'Lead created from internal job application for job post ID: ' . $job_post->id,
    //                 ]);
    //                 Log::debug('Lead activity created', ['lead_id' => $lead->id, 'user_id' => $user->id]);
    //             } catch (\Exception $e) {
    //                 Log::error('Lead creation error', ['user_id' => $user->id, 'error' => $e->getMessage(), 'lead_data' => $leadData]);
    //                 throw $e;
    //             }
    //         }

    //         if ($request->hasFile('attachments')) {
    //             foreach ($request->file('attachments') as $file) {
    //                 $application->addMedia($file)->toMediaCollection('applications');
    //             }
    //         }

    //         Mail::to($application->applicant->user->email)->send(new ApplicationReceived($application));

    //         return RequestResponse::created('Application submitted successfully');
    //     });
    // }

    public function externalStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_token' => 'required|string',
                // Personal Details
                'fullname' => 'required|string|max:100|regex:/^[\p{L}\s-]+$/u',
                'idnumber' => 'required|string|max:50|unique:applicants,idnumber',
                'whatappno' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
                'phone' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/|different:whatappno',
                'email' => 'required|email|max:255|unique:users,email',
                'age' => 'required|integer|min:18|max:100',
                'country' => 'required|string|max:100|regex:/^[\p{L}\s-]+$/u',
                'location' => 'required|string|max:255',
                // Institution Details
                'specialization' => 'required|string|max:255',
                'academicLevel' => 'required|in:certificate,diploma,bachelor,master,phd',
                'company1' => 'nullable|string|max:255',
                'company2' => 'nullable|string|max:255',
                'company3' => 'nullable|string|max:255',
                'salary' => 'required|numeric|min:0',
                // Upload Section
                'cv' => 'required|file|mimes:pdf,docx|max:2048',
                'jobId' => [
                    'required',
                    'exists:job_posts,slug',
                ],
                'cover_letter' => 'nullable|string|max:5000|regex:/^[\p{L}\p{N}\p{P}\s]*$/u',
            ], [
                'fullname.regex' => 'Full name should only contain letters, spaces, or hyphens.',
                'idnumber.unique' => 'This ID number is already registered.',
                'whatappno.regex' => 'Please provide a valid WhatsApp number.',
                'phone.regex' => 'Please provide a valid phone number.',
                'phone.different' => 'Phone number must be different from WhatsApp number.',
                'email.unique' => 'This email is already registered.',
                'age.integer' => 'Age must be a valid number.',
                'age.min' => 'You must be at least 18 years old.',
                'age.max' => 'Age cannot exceed 100 years.',
                'country.regex' => 'Country should only contain letters, spaces, or hyphens.',
                'academicLevel.in' => 'Academic level must be one of: certificate, diploma, bachelor, master, phd.',
                'salary.numeric' => 'Salary must be a valid number.',
                'cv.required' => 'CV upload is required.',
                'cv.mimes' => 'CV must be a PDF or DOCX file.',
                'cv.max' => 'CV file size must not exceed 2MB.',
                'jobId.exists' => 'The specified job post does not exist.',
            ]);

            $business = Business::where('slug', 'amsol')->first();

            if (!$business || !$business->api_token || !password_verify($validated['api_token'], $business->api_token)) {
                return RequestResponse::unauthorized('Invalid or unauthorized API token.');
            }

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

            return DB::transaction(function () use ($validated, $business, $jobPost, $request) {
                $user = User::firstOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name' => trim($validated['fullname']),
                        'phone' => $validated['phone'],
                        'password' => bcrypt(Str::random(12)),
                        'country' => $validated['country'],
                    ]
                );
                $user->assignRole('applicant');
                $user->setStatus(Status::ACTIVE);

                $applicant = Applicant::where('user_id', $user->id)->first();
                if (
                    $applicant && Application::where('applicant_id', $applicant->id)
                    ->where('job_post_id', $jobPost->id)
                    ->exists()
                ) {
                    return RequestResponse::badRequest('You have already applied to this job.');
                }

                $applicant = Applicant::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'idnumber' => $validated['idnumber'],
                        'whatsapp_no' => $validated['whatappno'],
                        'age' => $validated['age'],
                        'country' => $validated['country'],
                        'address' => $validated['location'],
                        'specialization' => $validated['specialization'],
                        'academic_level' => $validated['academicLevel'],
                        'company1' => $validated['company1'],
                        'company2' => $validated['company2'],
                        'company3' => $validated['company3'],
                        'salary_expectation' => $validated['salary'],
                        'created_by' => null,
                    ]
                );

                $application = Application::create([
                    'business_id' => $business->id,
                    'location_id' => $jobPost->location_id ?? null,
                    'applicant_id' => $applicant->id,
                    'job_post_id' => $jobPost->id,
                    'cover_letter' => $validated['cover_letter'],
                    'stage' => 'applied',
                    'created_by' => null,
                ]);
                $application->setStatus(Status::APPLIED);

                if ($request->hasFile('cv')) {
                    $application->addMedia($request->file('cv'))->toMediaCollection('applications');
                }

                if (!Lead::where('user_id', $user->id)->exists()) {
                    $leadData = [
                        'business_id' => $business->id,
                        'user_id' => $user->id,
                        'name' => $user->name ?? 'Unknown',
                        'email' => $user->email ?? 'unknown@example.com',
                        'phone' => $user->phone,
                        'source' => 'job_application',
                        'status' => 'new',
                        'label' => 'Applicant',
                    ];

                    $lead = Lead::create($leadData);
                    if (!$lead || !$lead->id) {
                        throw new \Exception('Lead creation failed');
                    }
                    Log::debug('Lead created successfully', ['lead_id' => $lead->id, 'user_id' => $user->id]);

                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $user->id,
                        'activity_type' => 'note',
                        'description' => 'Lead created from external job application for job post ID: ' . $jobPost->id,
                    ]);
                }

                Mail::to($user->email)->queue(new ApplicationReceived($application));

                return RequestResponse::ok('Application submitted successfully', [
                    'application_id' => $application->id,
                    'job_title' => $jobPost->title,
                    'status' => $application->stage,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return RequestResponse::badRequest('Validation failed', $e->errors());
        } catch (\Exception $e) {
            Log::error('Application submission failed', ['error' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);
            return RequestResponse::badRequest('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function show(Request $request)
    {
        $validatedData = $request->validate([
            'application_id' => 'required|exists:applications,id',
        ]);

        $application = Application::with('applicant.user', 'jobPost', 'interviews')->findOrFail($validatedData['application_id']);
        return RequestResponse::ok('Ok', $application->toArray());
    }

    public function reports()
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $applications = $business->applications()
            ->with('applicant.user', 'jobPost')
            ->latest()
            ->get();

        return view('applications.reports', compact('applications'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'cover_letter' => 'nullable|string',
            'stage' => 'required|string|in:applied,shortlisted,in_progress,rejected,finished',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $application = Application::findOrFail($request->application_id);
            $oldStage = $application->stage;
            $application->update($request->only(['cover_letter', 'stage']));

            if ($oldStage !== $request->stage) {
                Mail::to($application->applicant->user->email)->send(new ApplicationStageUpdated($application));
            }

            return RequestResponse::ok('Application updated successfully', $application);
        });
    }

    public function updateStage(Request $request)
    {
        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
            'stage' => 'required|string|in:applied,shortlisted,in_progress,rejected,finished',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $applications = Application::whereIn('id', $request->application_ids)->get();
            foreach ($applications as $application) {
                $oldStage = $application->stage;
                $application->update(['stage' => $request->stage]);
                if ($oldStage !== $request->stage) {
                    Mail::to($application->applicant->user->email)->send(new ApplicationStageUpdated($application));
                }
            }
            return RequestResponse::ok("Stage updated to {$request->stage} for selected applications and emails sent.");
        });
    }

    public function shortlist(Request $request)
    {
        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $applications = Application::whereIn('id', $request->application_ids)->get();
            foreach ($applications as $application) {
                $application->update(['stage' => 'shortlisted']);
                Mail::to($application->applicant->user->email)->send(new ApplicationStageUpdated($application));
            }
            return RequestResponse::ok('Selected applications shortlisted successfully.');
        });
    }

    public function scheduleInterview(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'interview_date' => 'required|date',
            'interview_time' => 'required|date_format:H:i',
            'location' => 'required|string|max:255',
            'interviewer_id' => 'required|exists:users,id',
            'type' => 'required|in:phone,video,in-person',
            'meeting_link' => 'nullable|url',
        ]);

        return $this->handleTransaction(function () use ($request) {
            $application = Application::findOrFail($request->application_id);
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
            Mail::to($application->applicant->user->email)->send(new InterviewScheduled($application, $interview));
            return RequestResponse::ok('Interview scheduled and email sent.');
        });
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:applications,id',
        ]);

        return $this->handleTransaction(function () use ($request) {
            Application::whereIn('id', $request->application_ids)->delete();
            return RequestResponse::ok('Selected applications deleted successfully');
        });
    }

    public function export(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $query = $business->applications()->with('applicant.user', 'jobPost');
        $applications = $query->get();

        if ($applications->isEmpty()) {
            return response()->json(['message' => 'No applications available to export'], 400);
        }

        return Excel::download(new ApplicationExport($applications), 'applications_' . now()->format('Y-m-d_His') . '.xlsx');
    }
}
