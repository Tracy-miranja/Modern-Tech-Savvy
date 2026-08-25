<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Campaign;
use App\Models\LeadActivity;
use App\Models\Business;
use App\Models\ContactSubmission;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LeadExport;
use App\Exports\ContactsExport;
use App\Exports\CampaignsExport;
use App\Exports\SurveysExport;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Models\ShortLink;
use App\Models\ShortLinkVisit;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\SurveyConfirmation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\RateLimiter;

use Illuminate\Support\Str;

class CrmController extends Controller
{
    use HandleTransactions;

    private $validLabels = [
        'High Priority',
        'Low Priority',
        'Follow Up',
        'Hot Lead',
        'Cold Lead',
    ];

    // Contact Submissions
    public function contacts()
    {
        return view('crm.contacts.index', ['page' => 'Contact Submissions']);
    }

    public function viewContact($business, ContactSubmission $submission)
    {
        return view('crm.contacts.show', compact('submission'));
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'inquiry_type' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|in:new,contacted,qualified,closed',
            'utm_source' => 'nullable|string',
            'utm_medium' => 'nullable|string',
            'utm_campaign' => 'nullable|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $submission = ContactSubmission::create([
                'business_id' => $validated['business_id'],
                'first_name' => $validated['first_name'] ?? '',
                'last_name' => $validated['last_name'] ?? '',
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'country' => $validated['country'] ?? null,
                'inquiry_type' => $validated['inquiry_type'],
                'message' => $validated['message'],
                'source' => 'manual',
                'utm_source' => $validated['utm_source'] ?? null,
                'utm_medium' => $validated['utm_medium'] ?? null,
                'utm_campaign' => $validated['utm_campaign'] ?? null,
                'status' => $validated['status'],
            ]);

            Lead::create([
                'contact_submission_id' => $submission->id,
                'name' => trim($submission->first_name . ' ' . $submission->last_name) ?: 'Unknown',
                'email' => $submission->email,
                'phone' => $submission->phone,
                'source' => 'manual',
                'status' => $submission->status,
                'user_id' => auth()->id(),
            ]);

            return RequestResponse::created('Contact created successfully.', ['submission' => $submission]);
        });
    }

    public function fetchContacts(Request $request)
    {
        $businessSlug = session('active_business_slug');
        $query = ContactSubmission::query()->orderBy('created_at', 'desc');

        if ($businessSlug) {
            $business = \App\Models\Business::findBySlug($businessSlug);
            if (!$business) {
                Log::warning('Invalid business slug', ['slug' => $businessSlug]);
                return response()->json(['error' => 'Invalid business slug'], 404);
            }
            $query->where('business_id', $business->id);
        } else {
            Log::warning('No active business slug in session');
            return response()->json(['error' => 'No active business selected'], 400);
        }

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $query->where(function ($q) use ($filter) {
                $q->where('first_name', 'like', "%$filter%")
                    ->orWhere('last_name', 'like', "%$filter%")
                    ->orWhere('email', 'like', "%$filter%")
                    ->orWhere('message', 'like', "%$filter%")
                    ->orWhere('company_name', 'like', "%$filter%")
                    ->orWhere('inquiry_type', 'like', "%$filter%");
            });
        }

        $submissions = $query->get();
        $table = view('crm.contacts._table', compact('submissions'))->render();
        Log::info('Rendered table HTML', ['table' => $table]);
        return response()->json(['data' => $table]);
    }

    public function createContact()
    {
        return view('crm.contacts.create', ['page' => 'Submit Contact']);
    }

    public function updateContact(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:contact_submissions,id',
            'status' => 'required|in:new,contacted,qualified,closed',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $submission = ContactSubmission::findOrFail($validated['id']);
            $submission->update(['status' => $validated['status']]);
            return RequestResponse::ok('Contact updated successfully.', ['submission' => $submission]);
        });
    }

    public function destroyContact(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:contact_submissions,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $submission = ContactSubmission::findOrFail($validated['id']);
            $submission->delete();
            return RequestResponse::ok('Contact deleted successfully.');
        });
    }

    // Campaigns
    public function campaigns()
    {
        return view('crm.campaigns.index', ['page' => 'Campaigns']);
    }

    public function createCampaign()
    {
        return view('crm.campaigns.create', ['page' => 'Create Campaign']);
    }

    public function viewCampaign($business, Campaign $campaign)
    {
        return view('crm.campaigns.show', compact('campaign'));
    }

    public function fetchCampaigns(Request $request)
    {
        $businessSlug = session('active_business_slug');
        $query = Campaign::query()->orderBy('created_at', 'desc');

        if ($businessSlug) {
            $business = \App\Models\Business::findBySlug($businessSlug);
            $query->where('business_id', $business->id);
        }

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $query->where(fn($q) => $q->where('name', 'like', "%$filter%")
                ->orWhere('utm_campaign', 'like', "%$filter%"));
        }

        $campaigns = $query->get();
        $table = view('crm.campaigns._table', compact('campaigns'))->render();
        return RequestResponse::ok('Ok', $table);
    }

    public function storeCampaign(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'utm_source' => 'required|string|max:255',
            'utm_medium' => 'required|string|max:255',
            'utm_campaign' => 'required|string|max:255',
            'target_url' => 'required|url|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:active,inactive,completed',
            'has_survey' => 'boolean',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $business = \App\Models\Business::findBySlug(session('active_business_slug'));
            $campaign = $business->campaigns()->create($validated);

            $baseSlug = Str::slug($campaign->name);
            $slug = $baseSlug;
            $counter = 1;
            while (ShortLink::where('slug', $slug)->exists()) {
                $slug = "$baseSlug-$counter";
                $counter++;
            }

            $shortLink = ShortLink::create([
                'campaign_id' => $campaign->id,
                'slug' => $slug,
                'visits' => 0,
            ]);

            return RequestResponse::created('Campaign created successfully.', ['campaign' => $campaign, 'short_link' => $shortLink]);
        });
    }

    public function destroyCampaign(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:campaigns,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $campaign = Campaign::findOrFail($validated['id']);
            $campaign->delete();
            return RequestResponse::ok('Campaign deleted successfully.');
        });
    }

    public function createSurvey($business, Campaign $campaign)
    {
        $currentBusiness = Business::findBySlug($business);
        if ($campaign->business_id !== $currentBusiness->id) {
            abort(403);
        }
        return view('crm.surveys.create', compact('campaign', 'currentBusiness'));
    }

    public function storeSurvey(Request $request, $business, Campaign $campaign)
    {
        $currentBusiness = Business::findBySlug($business);
        if ($campaign->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'fields' => 'required|array|min:1',
            'fields.*.id' => 'required|string|distinct',
            'fields.*.type' => 'required|in:text,textarea,star,multiple_choice',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.options' => 'nullable|array|min:2',
            'fields.*.options.*' => 'nullable|string|max:255',
        ], [
            'fields.*.id.distinct' => 'Field IDs must be unique.',
            'fields.min' => 'At least one field is required.',
        ]);

        $labels = array_column($request->fields, 'label');
        if (count($labels) !== count(array_unique($labels))) {
            return response()->json([
                'message' => 'Each field must have a unique label.',
            ], 422);
        }

        return $this->handleTransaction(function () use ($campaign, $validated, $currentBusiness) {
            $fields = array_map(function ($field) {
                $fieldData = [
                    'id' => $field['id'],
                    'type' => $field['type'],
                    'label' => $field['label'],
                    'required' => isset($field['required']) && $field['required'] == 1,
                ];
                if ($field['type'] === 'multiple_choice' && !empty($field['options'])) {
                    $fieldData['options'] = array_filter($field['options'], fn($option) => !empty(trim($option)));
                }
                return $fieldData;
            }, $validated['fields']);

            $isUpdate = $campaign->has_survey;

            $campaign->update([
                'has_survey' => true,
                'survey_config' => ['fields' => $fields],
            ]);

            return RequestResponse::ok($isUpdate ? 'Survey updated successfully.' : 'Survey created successfully.', [
                'redirect_url' => url("/{$currentBusiness->slug}/crm/campaigns/{$campaign->id}"),
            ]);
        });
    }

    // handle short link
    public function handleShortLink($slug)
    {
        $shortLink = ShortLink::where('slug', $slug)->firstOrFail();
        $campaign = $shortLink->campaign;

        $ip = request()->ip();
        $userAgent = request()->userAgent();

        $existingVisit = ShortLinkVisit::where('short_link_id', $shortLink->id)
            ->where('ip_address', $ip)
            ->first();

        if (!$existingVisit) {
            $country = null;

            if ($ip === '127.0.0.1' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $country = 'Development';
            } else {
                try {
                    $response = Http::timeout(5)->get("https://ipinfo.io/{$ip}/json?token=a876c4d470b426");

                    if ($response->successful()) {
                        if (isset($response['bogon']) && $response['bogon'] === true) {
                            $country = 'Unknown';
                        } else {
                            $country = $response['country'] ?? null;
                        }
                    } else {
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to fetch country for IP {$ip}: {$e->getMessage()}");
                }
            }

            $agent = new Agent();

            ShortLinkVisit::create([
                'short_link_id' => $shortLink->id,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
                'country' => $country,
            ]);

            $shortLink->increment('visits');
        }

        if ($campaign->has_survey) {
            return view('crm.surveys.form', compact('campaign', 'shortLink'));
        }

        return redirect()->away($campaign->target_url);
    }

    public function skipShortLink($slug)
    {
        $shortLink = ShortLink::where('slug', $slug)->firstOrFail();
        $ip = request()->ip();

        ShortLinkVisit::where('short_link_id', $shortLink->id)
            ->where('ip_address', $ip)
            ->update(['skipped' => true]);

        return redirect()->away($shortLink->campaign->target_url);
    }

    public function submitSurvey(Request $request, $slug)
    {
        $ip = $request->ip();
        $rateLimitKey = "survey-submit:{$ip}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
            ], 429);
        }

        try {
            $shortLink = ShortLink::where('slug', $slug)->firstOrFail();
            $campaign = $shortLink->campaign;

            $validationRules = [
                'name' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'country' => ['nullable', 'string', 'max:255'],
            ];
            $surveyResponses = [];

            foreach ($campaign->survey_config['fields'] ?? [] as $field) {
                $rules = [];
                if ($field['required']) {
                    $rules[] = 'required';
                }
                if ($field['type'] === 'text' || $field['type'] === 'textarea') {
                    $rules[] = 'string';
                    $rules[] = 'max:255';
                } elseif ($field['type'] === 'star') {
                    $rules[] = 'integer';
                    $rules[] = 'min:1';
                    $rules[] = 'max:5';
                } elseif ($field['type'] === 'multiple_choice') {
                    $rules[] = 'string';
                    $rules[] = 'max:255';
                    if (!empty($field['options'])) {
                        $rules[] = Rule::in($field['options']);
                    }
                }
                $validationRules[$field['id']] = $rules;

                $value = $request->input($field['id']);
                if ($value !== null) {
                    $value = (string) Str::of($value)->trim()->stripTags();
                }
                $surveyResponses[$field['id']] = [
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'value' => $value,
                    'options' => $field['options'] ?? null,
                ];
            }

            $validated = $request->validate($validationRules);

            $existingLead = Lead::where('campaign_id', $campaign->id)
                ->where('email', $request->email)
                ->first();

            if ($existingLead) {
                RateLimiter::increment($rateLimitKey);
                return response()->json([
                    'message' => 'You have already submitted feedback for this campaign.',
                    'redirect_url' => $campaign->target_url,
                ], 409);
            }

            return $this->handleTransaction(function () use ($request, $campaign, $surveyResponses, $rateLimitKey) {

                $email = null;
                $name = null;
                foreach ($surveyResponses as $response) {
                    $labelLower = Str::lower($response['label']);
                    if (Str::contains($labelLower, ['email', 'e-mail']) && $response['value']) {
                        try {
                            $request->validate(['survey_email' => 'email:rfc,dns'], ['survey_email' => $response['value']]);
                            $email = (string) Str::of($response['value'])->trim();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Log::warning("Invalid email found in survey responses: {$response['value']}");
                        }
                    }
                    if (Str::contains($labelLower, ['name', 'full name', "what's your name"]) && $response['value']) {
                        try {
                            $request->validate(['survey_name' => 'string|max:255'], ['survey_name' => $response['value']]);
                            $name = (string) Str::of($response['value'])->trim();
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            Log::warning("Invalid name found in survey responses: {$response['value']}");
                        }
                    }
                }

                if (!$email && $request->email) {
                    try {
                        $request->validate(['request_email' => 'email:rfc,dns'], ['request_email' => $request->email]);
                        $email = (string) Str::of($request->email)->trim();
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        Log::warning("Invalid email in request: {$request->email}");
                    }
                }

                if (!$name && $request->name) {
                    try {
                        $request->validate(['request_name' => 'string|max:255'], ['request_name' => $request->name]);
                        $name = (string) Str::of($request->name)->trim();
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        Log::warning("Invalid name in request: {$request->name}");
                    }
                }

                $lead = Lead::create([
                    'business_id' => $campaign->business_id,
                    'campaign_id' => $campaign->id,
                    'name' => $name ?: 'Anonymous',
                    'email' => $email,
                    'country' => $request->country ? (string) Str::of($request->country)->trim()->stripTags() : null,
                    'survey_responses' => $surveyResponses,
                    'status' => 'new',
                ]);

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => null,
                    'activity_type' => 'note',
                    'description' => 'Survey submitted via campaign.',
                ]);

                $emailSent = false;
                if ($email) {
                    try {
                        Mail::to($email)->send(new SurveyConfirmation($campaign, $lead));
                        Log::info("Survey confirmation email sent to: {$email}");
                        $emailSent = true;
                    } catch (\Exception $e) {
                        Log::error("Failed to send survey confirmation email to {$email}: {$e->getMessage()}");
                    }
                } else {
                    Log::info("No valid email provided for survey submission; skipping email send.");
                }

                RateLimiter::increment($rateLimitKey);

                return response()->json([
                    'message' => $emailSent
                        ? 'Thank you for your feedback! A confirmation has been sent to your email.'
                        : 'Thank you for your feedback!',
                    'redirect_url' => $campaign->target_url,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            RateLimiter::increment($rateLimitKey);
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            RateLimiter::increment($rateLimitKey);
            Log::error("Survey submission failed for slug {$slug}: {$e->getMessage()}");
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    public function exportSurveys($slug, Campaign $campaign)
    {
        $currentBusiness = Business::findBySlug(session('active_business_slug'));
        if ($campaign->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $leads = $campaign->leads()->latest()->get();
        return Excel::download(new SurveysExport($leads), "survey_results_{$campaign->slug}.xlsx");
    }

    public function analytics(Request $request, $slug, Campaign $campaign)
    {
        $currentBusiness = Business::findBySlug(session('active_business_slug'));
        if ($campaign->business_id !== $currentBusiness->id) {
            abort(403);
        }

        return view('crm.campaigns.analytics', compact('currentBusiness', 'campaign'));
    }

    public function fetchAnalytics(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'type' => 'required|in:visits,survey',
        ]);

        $campaign = Campaign::findOrFail($request->campaign_id);
        $business = Business::findBySlug(session('active_business_slug'));
        if ($campaign->business_id !== $business->id) {
            abort(403);
        }

        if ($request->type === 'visits') {
            $visits = $campaign->shortLink ? $campaign->shortLink->visits()->latest()->get() : collect([]);
            return response()->json([
                'data' => view('crm.campaigns.partials.visits_table', compact('visits'))->render(),
            ]);
        } else {
            $leads = $campaign->leads()->latest()->get();
            return response()->json([
                'data' => view('crm.campaigns.partials.survey_table', compact('leads'))->render(),
            ]);
        }
    }

    // Leads
    public function leads()
    {
        return view('crm.leads.index', [
            'page' => 'Leads',
            'leadLabels' => config('crm.lead_labels', [])
        ]);
    }

    public function createLead()
    {
        $businessSlug = session('active_business_slug');
        if (!$businessSlug) {
            return redirect()->route('business.select')->with('error', 'Please select a business.');
        }
        $business = \App\Models\Business::findBySlug($businessSlug);
        if (!$business) {
            return redirect()->route('business.select')->with('error', 'Invalid business selected.');
        }
        $campaigns = \App\Models\Campaign::where('business_id', $business->id)->get();
        return view('crm.leads.create', [
            'page' => 'Create Lead',
            'campaigns' => $campaigns,
            'currentBusiness' => $business,
            'leadLabels' => config('crm.lead_labels', [])
        ]);
    }

    public function viewLead($business, Lead $lead)
    {
        $activities = $lead->activities()->orderBy('created_at', 'desc')->get();
        return view('crm.leads.show', compact('lead', 'activities'));
    }

    public function fetchLeads(Request $request)
    {
        $businessSlug = session('active_business_slug');

        if (!$businessSlug) {
            return response()->json([
                'error' => 'No active business selected',
                'data' => view('crm.leads._table', ['leads' => collect([])])->render()
            ], 400);
        }

        $business = \App\Models\Business::findBySlug($businessSlug);
        if (!$business) {
            return response()->json([
                'error' => 'Invalid business slug',
                'data' => view('crm.leads._table', ['leads' => collect([])])->render()
            ], 404);
        }

        $query = Lead::query()->orderBy('created_at', 'desc');

        $query->where(function ($q) use ($business) {
            $q->where('business_id', $business->id)
                ->orWhereHas('contactSubmission', fn($q) => $q->where('business_id', $business->id))
                ->orWhereHas('campaign', fn($q) => $q->where('business_id', $business->id))
                ->orWhereHas('user', fn($q) => $q->whereHas('business', fn($b) => $b->where('id', $business->id)));
        });

        if ($request->has('filter')) {
            $filter = $request->input('filter');
            $query->where(fn($q) => $q->where('name', 'like', "%$filter%")
                ->orWhere('email', 'like', "%$filter%")
                ->orWhere('label', 'like', "%$filter%"));
        }

        $leads = $query->get();

        $table = view('crm.leads._table', compact('leads'))->render();

        return response()->json([
            'data' => $table,
            'count' => $leads->count(),
            'message' => $leads->isEmpty() ? 'No leads found for this business.' : null
        ]);
    }

    public function storeLead(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:new,contacted,qualified,converted,lost',
            'label' => 'nullable|string|max:255',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $lead = Lead::create(array_merge($validated, ['user_id' => auth()->id()]));
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'note',
                'description' => 'Lead created.',
            ]);
            return RequestResponse::created('Lead created successfully.', ['lead' => $lead]);
        });
    }

    public function labelLead(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'label' => 'required|string|max:255',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $lead = Lead::findOrFail($validated['lead_id']);
            $lead->update(['label' => $validated['label']]);
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'status_change',
                'description' => "Lead labeled as '{$validated['label']}'.",
            ]);
            return RequestResponse::ok('Lead labeled successfully.', ['lead' => $lead]);
        });
    }

    public function updateLead(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:leads,id',
            'status' => 'required|in:new,contacted,qualified,converted,lost',
            'label' => 'nullable|in:' . implode(',', $this->validLabels),
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $lead = Lead::findOrFail($validated['id']);
            $changes = [];

            if ($lead->status !== $validated['status']) {
                $changes[] = "Status changed to '{$validated['status']}'";
                $lead->status = $validated['status'];
            }

            if (isset($validated['label']) && $lead->label !== $validated['label']) {
                $changes[] = "Label changed to '{$validated['label']}'";
                $lead->label = $validated['label'];
            }

            $lead->save();

            if (!empty($changes)) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => auth()->id(),
                    'activity_type' => 'status_change',
                    'description' => implode(', ', $changes),
                ]);
            }

            return RequestResponse::ok('Lead updated successfully.', ['lead' => $lead]);
        });
    }

    public function storeLeadActivity(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'activity_type' => 'required|in:call,email,meeting,note',
            'description' => 'required|string',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $activity = LeadActivity::create(array_merge($validated, ['user_id' => auth()->id()]));
            return RequestResponse::created('Activity logged successfully.', ['activity' => $activity]);
        });
    }

    public function destroyLead(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:leads,id',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $lead = Lead::findOrFail($validated['id']);
            $lead->delete();
            return response()->json(['message' => 'Lead deleted successfully.']);
        });
    }

    public function exportReport(Request $request)
    {
        try {
            $businessSlug = session('active_business_slug');
            $routeParameters = $request->route()->parameters();

            $type = $routeParameters['type'] ?? null;
            $format = $routeParameters['format'] ?? null;

            if (!$type || !$format) {
                return response()->json(['error' => 'Missing report type or format'], 400);
            }

            $business = Business::findBySlug($businessSlug);

            if (!$business) {
                return response()->json(['error' => 'Business not found'], 404);
            }

            if ($type === 'contacts') {
                $contacts = ContactSubmission::where('business_id', $business->id)->get();

                if ($format === 'pdf') {
                    $pdf = Pdf::loadView('crm.reports.contacts_pdf', ['contacts' => $contacts, 'business' => $business])
                        ->setPaper('a4', 'landscape');
                    return $pdf->download("contacts_report_{$businessSlug}.pdf");
                } elseif ($format === 'csv') {
                    return Excel::download(new ContactsExport($contacts), "contacts_report_{$businessSlug}.csv", \Maatwebsite\Excel\Excel::CSV);
                } elseif ($format === 'xlsx') {
                    return Excel::download(new ContactsExport($contacts), "contacts_report_{$businessSlug}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
                } else {
                    return response()->json(['error' => 'Invalid report format specified'], 400);
                }
            } elseif ($type === 'leads') {
                $leads = Lead::where(function ($q) use ($business) {
                    $q->where('business_id', $business->id)
                        ->orWhereHas('contactSubmission', fn($q) => $q->where('business_id', $business->id))
                        ->orWhereHas('campaign', fn($q) => $q->where('business_id', $business->id))
                        ->orWhereHas('user', fn($q) => $q->whereHas('business', fn($b) => $b->where('id', $business->id)));
                })->with(['campaign', 'user', 'contactSubmission'])->get();

                if ($format === 'pdf') {
                    $pdf = Pdf::loadView('crm.reports.leads_pdf', ['leads' => $leads, 'business' => $business])
                        ->setPaper('a4', 'landscape');
                    return $pdf->download("leads_report_{$businessSlug}.pdf");
                } elseif ($format === 'csv') {
                    return Excel::download(new LeadExport($leads), "leads_report_{$businessSlug}.csv", \Maatwebsite\Excel\Excel::CSV);
                } elseif ($format === 'xlsx') {
                    return Excel::download(new LeadExport($leads), "leads_report_{$businessSlug}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
                } else {
                    return response()->json(['error' => 'Invalid report format specified'], 400);
                }
            } elseif ($type === 'campaigns') {
                $campaigns = Campaign::where('business_id', $business->id)
                    ->withCount('leads')
                    ->get();

                if ($format === 'pdf') {
                    $pdf = Pdf::loadView('crm.reports.campaigns_pdf', ['campaigns' => $campaigns, 'business' => $business])
                        ->setPaper('a4', 'landscape');
                    return $pdf->download("campaigns_report_{$businessSlug}.pdf");
                } elseif ($format === 'csv') {
                    return Excel::download(new CampaignsExport($campaigns), "campaigns_report_{$businessSlug}.csv", \Maatwebsite\Excel\Excel::CSV);
                } elseif ($format === 'xlsx') {
                    return Excel::download(new CampaignsExport($campaigns), "campaigns_report_{$businessSlug}.xlsx", \Maatwebsite\Excel\Excel::XLSX);
                } else {
                    return response()->json(['error' => 'Invalid report format specified'], 400);
                }
            }
            return response()->json(['error' => 'Invalid report type specified'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while exporting the report: ' . $e->getMessage()], 500);
        }
    }
}