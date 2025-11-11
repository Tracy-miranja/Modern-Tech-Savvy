<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeavePeriod;
use Illuminate\Http\Request;
use App\Services\LeavePolicyService;
use Illuminate\Support\Carbon;
use App\Http\RequestResponse;
use App\Models\LeaveEntitlement;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\LeaveRequest;

use Illuminate\Support\Facades\Schema;


class LeaveEntitlementController extends Controller
{
    use HandleTransactions;

public function index()
{
    $business = Business::findBySlug(session('active_business_slug'));
    if (!$business) {
        return RequestResponse::badRequest('Business not found.', 404);
    }

    $leavePeriods = LeavePeriod::where('business_id', $business->id)->get();
    return view('leave.index', compact('leavePeriods'));
}

    /**
     * Create or update leave entitlements for one or many employees over one or many leave types.
     * - If "employees" is omitted, all employees in the active business are targeted.
     * - "leave_type_ids" and "entitled_days" are parallel arrays (index-aligned).
     */
   public function store(Request $request, LeavePolicyService $policyService)
{
    Log::debug('LeaveEntitlement store payload', $request->all());

    $validated = $request->validate([
        'leave_period_id'   => 'nullable|exists:leave_periods,id',
        'leave_period_slug' => 'nullable|exists:leave_periods,slug',
        'employees'         => 'nullable|array',
        'employees.*'       => 'nullable|integer|exists:employees,id',
        'leave_type_ids'    => 'required|array|min:1',
        'leave_type_ids.*'  => 'required|integer|exists:leave_types,id',
        'entitled_days'     => 'nullable|array',
        'entitled_days.*'   => 'nullable|numeric|min:0',
        'override_policy'   => 'nullable|boolean',
    ]);

    return $this->handleTransaction(function () use ($validated, $policyService) {
        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

if (empty($validated['leave_period_id']) && empty($validated['leave_period_slug'])) {
    return RequestResponse::badRequest('Leave period id or slug is required.', 422);
}

$business = Business::findBySlug(session('active_business_slug'));
$leavePeriod = LeavePeriod::where('business_id', $business->id)
    ->when(!empty($validated['leave_period_id']), fn($q)=>$q->where('id', $validated['leave_period_id']))
    ->when(!empty($validated['leave_period_slug']), fn($q)=>$q->where('slug', $validated['leave_period_slug']))
    ->first();

        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found.', 404);
        }

        // Get employees: use provided list or all "active" employees (guard schema)
        $employeeIds = $validated['employees'] ??
            Employee::where('business_id', $business->id)
                ->when(Schema::hasColumn('employees', 'is_active'), fn ($q) => $q->where('is_active', 1))
                ->when(Schema::hasColumn('employees', 'status'), fn ($q) => $q->where('status', 'active'))
                ->when(Schema::hasColumn('employees', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->pluck('id')
                ->toArray();

        $typeIds = $validated['leave_type_ids'];
        $daysArr = $validated['entitled_days'] ?? [];
        $overridePolicy = $validated['override_policy'] ?? false;
        //$overridePolicy = (bool)($validated['override_policy'] ?? (!empty($validated['entitled_days'])));


        // Validate parallel arrays
        if (!empty($daysArr) && count($daysArr) !== count($typeIds)) {
            return RequestResponse::badRequest(
                'leave_type_ids and entitled_days must be the same length when entitled_days is provided.',
                422
            );
        }

        $onDate = Carbon::parse($leavePeriod->start_date);
        $entitled = 0;
        $skipped = 0;
        $errors = [];

        foreach ($employeeIds as $employeeId) {
            // load user for name (avoid N+1 for name access)
            $employee = Employee::with(['department', 'jobCategory', 'employmentDetail', 'user'])->find($employeeId);
            if (!$employee) {
                $errors[] = "Employee {$employeeId} not found.";
                $skipped++;
                continue;
            }

            // Precompute a safe display name (no ?? in interpolation)
            $employeeName = $employee->user?->name ?: 'Unknown';

            foreach ($typeIds as $idx => $leaveTypeId) {
                try {
                    $leaveType = LeaveType::find($leaveTypeId);
                    // Only enforce is_active if column exists
                    if (
                        !$leaveType ||
                        (Schema::hasColumn('leave_types', 'is_active') && !$leaveType->is_active)
                    ) {
                        $errors[] = "Leave type {$leaveTypeId} not found" . (Schema::hasColumn('leave_types','is_active') ? ' or inactive' : '') . '.';
                        $skipped++;
                        continue;
                    }

                    // Resolve policy with full eligibility check
                    $policy = $policyService->resolvePolicy($leaveTypeId, $employee, $onDate);

                    if (!$policy) {
                        if ($overridePolicy) {
                            // Try to get any policy for this leave type as template
                            $tpl = LeavePolicy::query()
                                ->where('leave_type_id', $leaveTypeId);
                            if (Schema::hasColumn('leave_policies', 'is_active')) {
                                $tpl->where('is_active', 1);
                            }
                            $policy = $tpl->first();

                            if (!$policy) {
                                $errors[] = "No policy template found for leave type {$leaveTypeId}.";
                                $skipped++;
                                continue;
                            }

                            Log::warning("Using policy override for employee {$employee->id}, leave type {$leaveTypeId}");
                        } else {
                            $errors[] = "Employee {$employee->id} ({$employeeName}) not eligible for {$leaveType->name}. "
                                . "Reasons: gender/department/job category mismatch or insufficient service days.";
                            $skipped++;
                            continue;
                        }
                    }

                    // Check eligibility (unless overriding)
                    if (!$overridePolicy && !$policyService->isEmployeeEligible($employee, $leaveType, $onDate)) {
                        $errors[] = "Employee {$employee->id} ({$employeeName}) does not meet eligibility criteria for {$leaveType->name}.";
                        $skipped++;
                        continue;
                    }

                    // Determine entitled days
                    $manualDays = isset($daysArr[$idx]) ? (float)$daysArr[$idx] : null;

                    if ($overridePolicy && $manualDays !== null) {
                        // Override: use manual days directly
                        $entitledDays = $manualDays;
                    } else {
                        // Compute from policy (includes proration and service checks)
                        $entitledDays = $policyService->computeEntitledDays($policy, $employee, $leavePeriod);

                        // If manual days provided without override, ensure not exceeding policy default+carryover cap
                        if ($manualDays !== null) {
                            $maxAllowed = $entitledDays + (float)($policy->max_carryover_days ?? 0);
                            if ($manualDays > $maxAllowed) {
                                $errors[] = "Requested {$manualDays} days for employee {$employee->id} exceeds policy maximum {$maxAllowed}.";
                                $skipped++;
                                continue;
                            }
                            $entitledDays = $manualDays;
                        }
                    }

                    if ($entitledDays <= 0) {
                        $errors[] = "Employee {$employee->id} entitled to 0 days for {$leaveType->name} "
                            . "(may not meet service requirements or joined late in period).";
                        $skipped++;
                        continue;
                    }

                    // Calculate carryover
                    $carryover = $policyService->calculateCarryover($employee, $leaveType, $leavePeriod, $policy);

                    // Create or update entitlement
                    $existing = LeaveEntitlement::where([
                        'business_id'     => $business->id,
                        'employee_id'     => $employee->id,
                        'leave_type_id'   => $leaveTypeId,
                        'leave_period_id' => $leavePeriod->id,
                    ])->first();

                    if ($existing) {
                        // Update existing
                        $existing->entitled_days   = $entitledDays;
                        $existing->carryover_days  = $carryover;
                        $existing->total_days      = (float)$existing->carryover_days + (float)($existing->accrued_days ?? 0);
                        $existing->days_remaining  = max(0, $existing->total_days - (float)($existing->days_taken ?? 0));
                        $existing->save();

                        Log::info("Updated entitlement {$existing->id} for employee {$employee->id}");
                    } else {
                        // Create new
                        LeaveEntitlement::create([
                            'business_id'     => $business->id,
                            'employee_id'     => $employee->id,
                            'leave_type_id'   => $leaveTypeId,
                            'leave_period_id' => $leavePeriod->id,
                            'entitled_days'   => $entitledDays,
                            'carryover_days'  => (float)$carryover,
                            'accrued_days'    => 0,
                            'total_days'      => (float)$carryover + 0,
                            'days_taken'      => 0,
                            'days_remaining'  => (float)$carryover,                            
                            'last_accrued_at' => $leavePeriod->start_date,
                        ]);

                        Log::info("Created entitlement for employee {$employee->id}, leave type {$leaveTypeId}");
                    }

                    $entitled++;

                } catch (\Exception $e) {
                    $errors[] = "Error for employee {$employee->id}, leave type {$leaveTypeId}: {$e->getMessage()}";
                    Log::error("Entitlement creation error", [
                        'employee_id'  => $employee->id,
                        'leave_type_id'=> $leaveTypeId,
                        'error'        => $e->getMessage(),
                        'trace'        => $e->getTraceAsString()
                    ]);
                    $skipped++;
                }
            }
        }

        return RequestResponse::created('Leave entitlements processed.', [
            'entitled'        => $entitled,
            'skipped'         => $skipped,
            'errors'          => $errors,
            'total_attempted' => count($employeeIds) * count($typeIds),
        ]);
    });
}

    /**
     * Fetch entitlements table for a given leave period (scoped to active business).
     * Optional filter: location_id. If omitted, no location filter is applied.
     */
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'leave_period_slug' => 'nullable|exists:leave_periods,slug',
            'leave_period_id' => 'nullable|exists:leave_periods,id',
            'location_id'       => 'nullable|integer|exists:locations,id',
        ]);
        if (empty($validated['leave_period_slug']) && empty($validated['leave_period_id'])) {
            return RequestResponse::badRequest('Leave period id or slug is required.', 422);
        }
        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = LeavePeriod::where('business_id', $business->id)
        ->when(!empty($validated['leave_period_slug']), fn($q)=>$q->where('slug', $validated['leave_period_slug']))
        ->when(!empty($validated['leave_period_id']), fn($q)=>$q->where('id', $validated['leave_period_id']))
        ->first();
        if (!$leavePeriod) return RequestResponse::badRequest('Leave period not found.', 404);

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id);

        // Optional location filter (only apply if provided)
        if (!empty($validated['location_id'])) {
            $locationId = (int)$validated['location_id'];
            $query->whereHas('employee', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        $leaveEntitlements = $query->with(['employee.user', 'leaveType', 'leavePeriod'])->get();

        $leaveEntitlementsTable = view('leave._leave_entitlements_table', compact('leaveEntitlements'))->render();

        return RequestResponse::ok('Leave entitlements fetched successfully.', $leaveEntitlementsTable);
    }

        public function getByPeriod(Request $request)
    {
        $validated = $request->validate([
            'leave_period_slug' => 'required_without:leave_period_id',
        'leave_period_id' => 'required_without:leave_period_slug',
            'location_id'       => 'nullable|integer|exists:locations,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

        $leavePeriod = LeavePeriod::where('slug', $request->leave_period_slug)
        ->orWhere('id', $request->leave_period_id)
        ->first();


        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found.', 404);
        }

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id);

        if (!empty($validated['location_id'])) {
            $locationId = (int)$validated['location_id'];
            $query->whereHas('employee', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        $leaveEntitlements = $query->with(['employee.user', 'leaveType', 'leavePeriod'])->get();
        $leaveEntitlementsTable = view('leave._leave_entitlements_table', compact('leaveEntitlements'))->render();

        return RequestResponse::ok('Leave entitlements fetched successfully.', $leaveEntitlementsTable);
    }


    public function show(Request $request)
    {
        $validated = $request->validate(['slug' => 'required|string']);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.', 404);

        $decoded = base64_decode(strtr($validated['slug'], '-_', '+/'));
        if (!$decoded || substr_count($decoded, ':') !== 3) {
            return RequestResponse::badRequest('Invalid entitlement slug.', 422);
        }

        [$business_id, $employee_id, $leave_type_id, $leave_period_id] = explode(':', $decoded);

        if ((int)$business_id !== $business->id) {
            return RequestResponse::badRequest('Invalid business for this entitlement.', 403);
        }

        $entitlement = LeaveEntitlement::where([
            'business_id'    => (int)$business_id,
            'employee_id'    => (int)$employee_id,
            'leave_type_id'  => (int)$leave_type_id,
            'leave_period_id'=> (int)$leave_period_id,
        ])->with(['employee.user','leaveType','leavePeriod'])->first();

        if (!$entitlement) return RequestResponse::badRequest('Leave entitlement not found.', 404);

        return view('leave._leave_entitlement_details', compact('entitlement'));
    }


    /**
     * Fetch a leave entitlement for editing by slug.
     */
    public function edit(Request $request)
    {
        $validated = $request->validate(['slug' => 'required|string']);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) return RequestResponse::badRequest('Business not found.', 404);

        $decoded = base64_decode(strtr($validated['slug'], '-_', '+/'));
        if (!$decoded || substr_count($decoded, ':') !== 3) {
            return RequestResponse::badRequest('Invalid entitlement slug.', 422);
        }

        [$business_id, $employee_id, $leave_type_id, $leave_period_id] = explode(':', $decoded);

        if ((int)$business_id !== $business->id) {
            return RequestResponse::badRequest('Invalid business for this entitlement.', 403);
        }

        $entitlement = LeaveEntitlement::where([
            'business_id' => (int)$business_id,
            'employee_id' => (int)$employee_id,
            'leave_type_id' => (int)$leave_type_id,
            'leave_period_id' => (int)$leave_period_id,
        ])->with(['employee.user','leaveType','leavePeriod'])->first();

        if (!$entitlement) return RequestResponse::badRequest('Leave entitlement not found.', 404);

        // returns EDIT FORM modal (new partial below)
        return view('leave._leave_entitlement_edit', compact('entitlement'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'slug'          => 'required|string',
            'entitled_days' => 'required|numeric|min:0',
            'accrued_days'  => 'nullable|numeric|min:0',
            'days_taken'    => 'required|numeric|min:0',
        ]);

        $decoded = base64_decode(strtr($data['slug'], '-_', '+/'));
        if (!$decoded || substr_count($decoded, ':') !== 3) {
            return RequestResponse::badRequest('Invalid entitlement slug.', 422);
        }

        [$business_id, $employee_id, $leave_type_id, $leave_period_id] = explode(':', $decoded);

        $entitlement = LeaveEntitlement::where([
            'business_id'    => (int)$business_id,
            'employee_id'    => (int)$employee_id,
            'leave_type_id'  => (int)$leave_type_id,
            'leave_period_id'=> (int)$leave_period_id,
        ])->firstOrFail();

        // assign
        $entitlement->entitled_days = (float)$data['entitled_days'];
        $entitlement->accrued_days  = isset($data['accrued_days']) ? (float)$data['accrued_days'] : (float)$entitlement->accrued_days;
        $entitlement->days_taken    = (float)$data['days_taken'];
        //$entitlement->carryover_days = (float)$entitlement->carryover_days;

        // recompute
        $entitlement->total_days     = (float)$entitlement->carryover_days + (float)$entitlement->accrued_days;
        $entitlement->days_remaining = max(0.0, $entitlement->total_days - $entitlement->days_taken);

        // optional hard guard: prevent taken > total (comment out if you allow negative balance)
        if ($entitlement->days_taken > $entitlement->total_days) {
            return RequestResponse::badRequest('Days taken cannot exceed total entitlement.', 422);
        }

        $entitlement->save();

        return response()->json([
            'message' => 'Entitlement updated successfully.',
            'entitlement' => $entitlement->fresh(['employee.user','leaveType','leavePeriod']),
        ]);
    }

    /**
     * Delete a leave entitlement by slug.
     */
    public function delete(Request $request)
    {
        return $this->handleTransaction(function () use ($request) {
            $validated = $request->validate([
                'slug' => 'required|string',
            ]);

            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.', 404);
            }

            $decoded = base64_decode(strtr($validated['slug'], '-_', '+/'));
            if (!$decoded || substr_count($decoded, ':') !== 3) {
                return RequestResponse::badRequest('Invalid entitlement slug.', 422);
            }

            [$business_id, $employee_id, $leave_type_id, $leave_period_id] = explode(':', $decoded);

            if ((int)$business_id !== $business->id) {
                return RequestResponse::badRequest('Invalid business for this entitlement.', 403);
            }

            $entitlement = LeaveEntitlement::where([
                'business_id' => (int)$business_id,
                'employee_id' => (int)$employee_id,
                'leave_type_id' => (int)$leave_type_id,
                'leave_period_id' => (int)$leave_period_id,
            ])->first();

            if (!$entitlement) {
                return RequestResponse::badRequest('Leave entitlement not found.', 404);
            }

            $entitlement->delete();

            return RequestResponse::ok('Leave entitlement deleted successfully.');
        });
    }

   public function autoEntitleForPeriod(Request $request, LeavePolicyService $policyService)
    {
        $validated = $request->validate([
            'leave_period_id' => 'required|exists:leave_periods,id',
            'force' => 'nullable|boolean', // Re-create even if exists
        ]);

        return $this->handleTransaction(function () use ($validated, $policyService) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.', 404);
            }

            $period = LeavePeriod::where('id', $validated['leave_period_id'])
                ->where('business_id', $business->id)
                ->first();

            if (!$period) {
                return RequestResponse::badRequest('Leave period not found.', 404);
            }

            $force = $validated['force'] ?? false;

            $entitled = 0;
            $skipped = 0;
            $errors = [];

            // Get all active employees
            $employees = Employee::where('business_id', $business->id)
                ->where('is_active', true)
                ->get();

            // Get all active leave types
            $leaveTypes = LeaveType::where('business_id', $business->id)
                ->where('is_active', true)
                ->get();

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    try {
                        // Check if already exists
                        $exists = LeaveEntitlement::where([
                            'business_id' => $business->id,
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'leave_period_id' => $period->id,
                        ])->exists();

                        if ($exists && !$force) {
                            $skipped++;
                            continue;
                        }

                        // Resolve policy
                        $policy = $policyService->resolvePolicy(
                            $leaveType->id,
                            $employee,
                            Carbon::parse($period->start_date)
                        );

                        if (!$policy) {
                            $skipped++;
                            continue;
                        }

                        // Create or update entitlement with full policy enforcement
                        $entitlement = $policyService->createOrUpdateEntitlement(
                            $employee,
                            $leaveType,
                            $period,
                            $policy
                        );

                        if ($entitlement) {
                            $entitled++;
                        } else {
                            $skipped++;
                        }

                    } catch (\Exception $e) {
                        $errors[] = "Employee {$employee->id}, Leave Type {$leaveType->id}: {$e->getMessage()}";
                        Log::error("Entitlement error: {$e->getMessage()}", [
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                        ]);
                    }
                }
            }

            return RequestResponse::ok('Employees entitled successfully.', [
                'entitled' => $entitled,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        });
    }

    /**
     * Process accruals for a specific period.
     */
    public function processAccruals(Request $request, LeavePolicyService $policyService)
    {
        $validated = $request->validate([
            'leave_period_id' => 'required|exists:leave_periods,id',
            'as_of_date' => 'nullable|date',
        ]);

        return $this->handleTransaction(function () use ($validated, $policyService) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.', 404);
            }

            $period = LeavePeriod::where('id', $validated['leave_period_id'])
                ->where('business_id', $business->id)
                ->first();

            if (!$period) {
                return RequestResponse::badRequest('Leave period not found.', 404);
            }

            $asOfDate = isset($validated['as_of_date']) 
                ? Carbon::parse($validated['as_of_date'])
                : now();

            $processed = $policyService->processAccruals($period, $asOfDate);

            return RequestResponse::ok('Accruals processed successfully.', [
                'processed' => $processed,
                'as_of_date' => $asOfDate->toDateString(),
            ]);
        });
    }




 }

