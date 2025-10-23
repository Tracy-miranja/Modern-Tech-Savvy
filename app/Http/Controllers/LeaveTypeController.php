<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeaveType;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\LeavePolicy;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class LeaveTypeController extends Controller
{
    use HandleTransactions;

    public function fetch(Request $request)
    {
        $business = Business::findBySlug(session('active_business_slug'));
        $leaveTypes = $business->leaveTypes()->with('leavePolicies')->get();

        $leaveTypesTable = view('leave._leave_types_table', compact('leaveTypes'))->render();

        return RequestResponse::ok('Leave types fetched successfully.', $leaveTypesTable);
    }

    public function store(Request $request)
    {
        Log::debug('LeaveType store payload', $request->all());

        $business = Business::findBySlug(session('active_business_slug'));

        $validated = $request->validate([
            'name'                              => [
                'required','string','max:255',
                Rule::unique('leave_types','name')->where(fn($q)=>$q->where('business_id', optional($business)->id))
            ],
            'description'                       => 'nullable|string',
            'requires_approval'                 => 'required|boolean',
            'is_paid'                           => 'required|boolean',
            'allowance_accruable'               => 'required|boolean',
            'allows_half_day'                   => 'required|boolean',
            'requires_attachment'               => 'required|boolean',
            'max_continuous_days'               => 'nullable|integer|min:0',
            'min_notice_days'                   => 'required|integer|min:0',

            'department'                        => 'required|string',
            'job_category'                      => 'required|string',
            'gender_applicable'                 => 'required|string|in:all,male,female',
            'prorated_for_new_employees'        => 'required|boolean',
            'default_days'                      => 'required|integer|min:0',
            'accrual_frequency'                 => 'required|string|in:monthly,quarterly,yearly',
            'accrual_amount'                    => 'required|numeric|min:0',
            'max_carryover_days'                => 'required|integer|min:0',
            'minimum_service_days_required'     => 'required|integer|min:0',
            'effective_date'                    => 'required|date',
            'end_date'                          => 'nullable|date|after_or_equal:effective_date',

            // governance/flow
            'allows_backdating'                 => 'required|boolean',
            'approval_levels'                   => 'required|integer|min:0',
            'excluded_days'                     => 'nullable|array',
            'excluded_days.*'                   => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'excluded_dates'                   => 'nullable|array',
            'excluded_dates.*'                 => 'date_format:Y-m-d',
            'is_stepwise'                       => 'required|boolean',
            'stepwise_rules'                    => 'nullable|array',
        ]);

        return $this->handleTransaction(function () use ($validated, $business) {
            $leaveType = $business->leaveTypes()->create([
                'name'                => $validated['name'],
                'description'         => $validated['description'] ?? null,
                'requires_approval'   => $validated['requires_approval'],
                'is_paid'             => $validated['is_paid'],
                'allowance_accruable' => $validated['allowance_accruable'],
                'allows_half_day'     => $validated['allows_half_day'],
                'requires_attachment' => $validated['requires_attachment'],
                'max_continuous_days' => $validated['max_continuous_days'] ?? null,
                'min_notice_days'     => $validated['min_notice_days'],
                'is_active'           => true,
                'allows_backdating'   => $validated['allows_backdating'],
                'approval_levels'     => $validated['approval_levels'],
                'excluded_days'       => $validated['excluded_days'] ?? [],
                'excluded_dates'      => array_values(array_unique($validated['excluded_dates'] ?? [])),
                'is_stepwise'         => $validated['is_stepwise'],
                'stepwise_rules'      => $validated['stepwise_rules'] ?? [],
            ]);

            $businessId = $business->id;

            $departmentIds = ($validated['department'] === 'all')
                ? Department::where('business_id', $businessId)->pluck('id')->toArray()
                : [Department::where('business_id', $businessId)->where('slug', $validated['department'])->firstOrFail()->id];

            $jobCategoryIds = ($validated['job_category'] === 'all')
                ? JobCategory::where('business_id', $businessId)->pluck('id')->toArray()
                : [JobCategory::where('business_id', $businessId)->where('slug', $validated['job_category'])->firstOrFail()->id];

            $gender = $validated['gender_applicable'];

            foreach ($departmentIds as $departmentId) {
                foreach ($jobCategoryIds as $jobCategoryId) {
                    LeavePolicy::firstOrCreate(
                        [
                            'leave_type_id'     => $leaveType->id,
                            'department_id'     => $departmentId,
                            'job_category_id'   => $jobCategoryId,
                            'gender_applicable' => $gender,
                        ],
                        [
                            'prorated_for_new_employees'    => $validated['prorated_for_new_employees'],
                            'default_days'                  => $validated['default_days'],
                            'accrual_frequency'             => $validated['accrual_frequency'],
                            'accrual_amount'                => $validated['accrual_amount'],
                            'max_carryover_days'            => $validated['max_carryover_days'],
                            'minimum_service_days_required' => $validated['minimum_service_days_required'],
                            'effective_date'                => $validated['effective_date'],
                            'end_date'                      => $validated['end_date'] ?? null,
                        ]
                    );
                }
            }

            return RequestResponse::created('Leave type and policies created successfully.');
        });
    }

    /**
     * Unified edit:
     * - POST /leave-types/edit (AJAX) -> returns HTML fragment wrapped in JSON
     * - GET  /business/{business}/leave-types/{slug}/edit -> full page
     */
    public function edit(Request $request, Business $business = null, $slug = null)
    {
        // AJAX branch
        if ($request->isMethod('post')) {
            $slugFromRequest = $request->input('slug')
                ?? $request->input('leave')
                ?? $request->input('leave_type_slug');

            $request->merge(['_slug' => $slugFromRequest]);

            $request->validate([
                '_slug' => 'required|string|exists:leave_types,slug',
            ]);

            $leaveType = LeaveType::with(['leavePolicies.department', 'leavePolicies.jobCategory', 'business'])
                ->where('slug', $slugFromRequest)
                ->firstOrFail();

            $biz           = $leaveType->business;
            $departments   = $biz ? $biz->departments : Department::where('business_id', $leaveType->business_id)->get();
            $jobCategories = $biz ? $biz->jobCategories : JobCategory::where('business_id', $leaveType->business_id)->get();

            $html = view('leave.edit', [
                'leaveType'     => $leaveType,
                'departments'   => $departments,
                'jobCategories' => $jobCategories,
                'isAjax'        => true,
            ])->render();

            return RequestResponse::ok('Edit form loaded.', $html);
        }

        // Full-page branch
        $leaveType = LeaveType::where('slug', $slug)
            ->where('business_id', $business->id)
            ->with('leavePolicies')
            ->firstOrFail();

        return view('leave.edit', [
            'leaveType'     => $leaveType,
            'businessSlug'  => $business->slug,
            'departments'   => $business->departments,
            'jobCategories' => $business->jobCategories,
            'isAjax'        => false,
        ]);
    }

    public function show(Request $request)
    {
        $validated = $request->validate([
            'leave_type_slug' => 'required|string|exists:leave_types,slug',
        ]);

        $leaveType = LeaveType::where('slug', $validated['leave_type_slug'])
            ->with('leavePolicies.department', 'leavePolicies.jobCategory')
            ->firstOrFail();

        $leaveTypeDetails = view('leave._leave_type_details', compact('leaveType'))->render();

        return RequestResponse::ok('Leave type fetched successfully.', $leaveTypeDetails);
    }

    public function update(Request $request)
{
    $slug = $request->input('leave_type_slug')
        ?? $request->input('slug')
        ?? $request->input('leave');

    if (!$slug) {
        return RequestResponse::badRequest('Missing leave type identifier.');
    }

    $leaveType = LeaveType::where('slug', $slug)->first();
    if (!$leaveType) {
        return RequestResponse::badRequest('Leave type not found.');
    }

    $businessId = $leaveType->business_id;

    // PATCH semantics: only validate provided fields
    $rules = [
        'name'   => [
            'sometimes','filled','string','max:190',
            Rule::unique('leave_types','name')
                ->where(fn($q)=>$q->where('business_id',$businessId))
                ->ignore($leaveType->id),
        ],
        'description' => ['sometimes','nullable','string'],
        'requires_approval' => ['sometimes','in:0,1,true,false'],
        'is_paid' => ['sometimes','in:0,1,true,false'],
        'allowance_accruable' => ['sometimes','in:0,1,true,false'],
        'allows_half_day' => ['sometimes','in:0,1,true,false'],
        'requires_attachment' => ['sometimes','in:0,1,true,false'],
        'max_continuous_days' => ['sometimes','nullable','numeric','min:0'],
        'min_notice_days'     => ['sometimes','nullable','integer','min:0'],
        'allows_backdating'   => ['sometimes','in:0,1,true,false'],
        'approval_levels'     => ['sometimes','nullable','integer','min:0'],
        'is_stepwise'         => ['sometimes','in:0,1,true,false'],
        'excluded_days'       => ['sometimes','array'],
        'excluded_days.*'     => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
        'excluded_dates'      => ['sometimes','array'],
        'excluded_dates.*'    => ['date_format:Y-m-d'],

        // Policy bits
        'department'     => ['sometimes','filled','string'],
        'job_category'   => ['sometimes','filled','string'],
        'gender_applicable' => ['sometimes','in:all,male,female'],
        'prorated_for_new_employees' => ['sometimes','in:0,1,true,false'],
        'default_days'   => ['sometimes','nullable','numeric','min:0'],
        'accrual_frequency' => ['sometimes','in:monthly,quarterly,yearly'],
        'accrual_amount' => ['sometimes','nullable','numeric','min:0'],
        'max_carryover_days' => ['sometimes','nullable','numeric','min:0'],
        'minimum_service_days_required' => ['sometimes','nullable','integer','min:0'],
        'effective_date' => ['sometimes','nullable','date'],
        'end_date'       => ['sometimes','nullable','date','after_or_equal:effective_date'],

        // Optional flag to control pruning
        'sync_policies'  => ['sometimes','in:0,1,true,false'],
    ];

    $data = $request->validate($rules);

    // If name changed, update slug (unique per business)
    if (array_key_exists('name', $data) && $data['name'] !== $leaveType->name) {
        $newSlug = Str::slug($data['name']);
        $exists = LeaveType::where('business_id',$businessId)
            ->where('slug',$newSlug)
            ->where('id','!=',$leaveType->id)
            ->exists();
        if ($exists) {
            return RequestResponse::badRequest('Another leave type with a similar name already exists.');
        }
        $data['slug'] = $newSlug;
    }

    DB::beginTransaction();
    try {
        // Save LeaveType
        $leaveType->fill($data);

        if (array_key_exists('excluded_days', $data)) {
            $leaveType->excluded_days = array_values(array_unique(
                array_map('strtolower', $data['excluded_days'] ?? [])
            ));
        }
        
        // Use exists() so an intentionally empty array clears the field
        if ($request->exists('excluded_dates')) {
            $dates = collect((array)$request->input('excluded_dates', []))
                ->filter()
                ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
                ->unique()
                ->values()
                ->all();

            $leaveType->excluded_dates = $dates; // [] will clear it
        }



        if ($leaveType->isDirty()) {
            $leaveType->save();
        }

        // Policy upsert/sync only if relevant keys appeared
        $policyKeysPresent = collect([
            'department','job_category','gender_applicable',
            'prorated_for_new_employees','default_days','accrual_frequency','accrual_amount',
            'max_carryover_days','minimum_service_days_required','effective_date','end_date',
            'sync_policies',
        ])->some(fn($k) => $request->has($k));

        if ($policyKeysPresent) {
            $deptParam = $request->input('department', 'all');
            $jobcParam = $request->input('job_category', 'all');
            $gender    = $request->input('gender_applicable', 'all');
            if (!in_array($gender, ['all','male','female'], true)) {
                DB::rollBack();
                return RequestResponse::badRequest('Invalid gender_applicable value.');
            }

            $deptIds = $deptParam === 'all'
                ? Department::where('business_id', $businessId)->pluck('id')->toArray()
                : (function() use ($businessId,$deptParam) {
                    $d = Department::where('business_id',$businessId)->where('slug',$deptParam)->first();
                    if (!$d) throw new \RuntimeException('Selected department not found for this business.');
                    return [$d->id];
                })();

            $jobcIds = $jobcParam === 'all'
                ? JobCategory::where('business_id', $businessId)->pluck('id')->toArray()
                : (function() use ($businessId,$jobcParam) {
                    $j = JobCategory::where('business_id',$businessId)->where('slug',$jobcParam)->first();
                    if (!$j) throw new \RuntimeException('Selected job category not found for this business.');
                    return [$j->id];
                })();

            // Fields that may be provided (override baseline)
            $policyFill = [];
            foreach ([
                'prorated_for_new_employees','default_days','accrual_frequency','accrual_amount',
                'max_carryover_days','minimum_service_days_required','effective_date','end_date'
            ] as $f) {
                if ($request->has($f)) {
                    $policyFill[$f] = $request->input($f);
                }
            }

            // Baseline from any existing policy
            $template = LeavePolicy::where('leave_type_id', $leaveType->id)->first();

            $baseline = [
                'prorated_for_new_employees'    => $template->prorated_for_new_employees ?? false,
                'default_days'                  => $template->default_days ?? 0,
                'accrual_frequency'             => $template->accrual_frequency ?? 'monthly',
                'accrual_amount'                => $template->accrual_amount ?? 0,
                'max_carryover_days'            => $template->max_carryover_days ?? 0,
                'minimum_service_days_required' => $template->minimum_service_days_required ?? 0,
                'effective_date'                => $template->effective_date ?? now()->toDateString(),
                'end_date'                      => $template->end_date ?? null,
            ];

            // === UPSERT + SYNC ===
            $targetKeys = [];
            foreach ($deptIds as $dId) {
                foreach ($jobcIds as $jId) {
                    $key = [
                        'leave_type_id'     => $leaveType->id,
                        'department_id'     => $dId,
                        'job_category_id'   => $jId,
                        'gender_applicable' => $gender,
                    ];
                    $targetKeys[] = $key;

                    $attrs = array_merge($baseline, $policyFill);
                    LeavePolicy::updateOrCreate($key, $attrs);
                }
            }

            // Sync policies (prune out-of-scope rows)
            $doSync = filter_var($request->input('sync_policies', '1'), FILTER_VALIDATE_BOOLEAN);
            if ($doSync) {
                $tuples = collect($targetKeys)->map(fn($k) => implode(':', [
                    $k['department_id'] ?? 'null',
                    $k['job_category_id'] ?? 'null',
                    $k['gender_applicable'],
                ]))->toArray();

                LeavePolicy::where('leave_type_id', $leaveType->id)
                    ->get()
                    ->each(function($p) use ($tuples) {
                        $t = implode(':', [
                            $p->department_id ?? 'null',
                            $p->job_category_id ?? 'null',
                            $p->gender_applicable,
                        ]);
                        if (!in_array($t, $tuples, true)) {
                            $p->delete();
                        }
                    });
            }
        }

        DB::commit();
    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('LeaveType update failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return RequestResponse::badRequest('Failed to update leave type. Please try again.');
    }

    if (!$leaveType->wasChanged() && !$policyKeysPresent) {
        return RequestResponse::ok('No changes were made.');
    }

    return RequestResponse::ok('Leave type updated successfully.');
}


    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'leave_type_slug' => 'required|string|exists:leave_types,slug',
        ]);

        return $this->handleTransaction(function () use ($validated) {
            $leaveType = LeaveType::where('slug', $validated['leave_type_slug'])->firstOrFail();

            $leaveType->leavePolicies()->delete();
            $leaveType->delete();

            return RequestResponse::ok('Leave type and policies deleted successfully.');
        });
    }

    public function requests(Request $request, $slug = null)
    {
        $slug = $slug ?? $request->leave_type_slug;
        if (!$slug) abort(404, 'Leave type slug missing.');

        $leaveType = LeaveType::where('slug', $slug)
            ->with(['leavePolicies', 'leaveRequests' => fn($q) => $q->with(['employee.user'])])
            ->firstOrFail();

        return view('leave.leave_type_requests', compact('leaveType'));
    }

    public function getRemainingDays(Request $request)
    {
        $employeeId  = $request->input('employee_id', auth()->user()->employee->id ?? null);
        $leaveTypeId = $request->input('leave_type_id');

        $entitlement = \App\Models\LeaveEntitlement::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->first();

        $remaining = $entitlement ? $entitlement->getRemainingDays() : 0;

        return response()->json(['remaining_days' => $remaining]);
    }
}
