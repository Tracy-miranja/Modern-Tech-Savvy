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
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
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

            $leavePeriod = LeavePeriod::where('business_id', $business->id)
                ->when(!empty($validated['leave_period_id']), fn ($q) => $q->where('id', $validated['leave_period_id']))
                ->when(!empty($validated['leave_period_slug']), fn ($q) => $q->where('slug', $validated['leave_period_slug']))
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

            $typeIds       = $validated['leave_type_ids'];
            $daysArr       = $validated['entitled_days'] ?? [];
            $overridePolicy = $validated['override_policy'] ?? false;

            // Validate parallel arrays
            if (!empty($daysArr) && count($daysArr) !== count($typeIds)) {
                return RequestResponse::badRequest(
                    'leave_type_ids and entitled_days must be the same length when entitled_days is provided.',
                    422
                );
            }

            $onDate   = Carbon::parse($leavePeriod->start_date);
            $asOfDate = now(); // snapshot date for accrual calculation

            $entitled = 0;
            $skipped  = 0;
            $errors   = [];

            foreach ($employeeIds as $employeeId) {
                // load user for name (avoid N+1 for name access)
                $employee = Employee::with(['department', 'jobCategory', 'employmentDetails', 'user'])->find($employeeId);
                if (!$employee) {
                    $errors[] = "Employee {$employeeId} not found.";
                    $skipped++;
                    continue;
                }

                $employeeName = $employee->user?->name ?: 'Unknown';

                foreach ($typeIds as $idx => $leaveTypeId) {
                    try {
                        $leaveType = LeaveType::find($leaveTypeId);
                        // Only enforce is_active if column exists
                        if (
                            !$leaveType ||
                            (Schema::hasColumn('leave_types', 'is_active') && !$leaveType->is_active)
                        ) {
                            $errors[] = "Leave type {$leaveTypeId} not found" . (Schema::hasColumn('leave_types', 'is_active') ? ' or inactive' : '') . '.';
                            $skipped++;
                            continue;
                        }

                        // Resolve policy
                        $policy = $policyService->resolvePolicy($leaveTypeId, $employee, $onDate);

                        if (!$policy) {
                            if ($overridePolicy) {
                                // Try to get any policy for this leave type as template
                                $tpl = LeavePolicy::query()->where('leave_type_id', $leaveTypeId);
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
                        $manualDays   = isset($daysArr[$idx]) ? (float) $daysArr[$idx] : null;
                        $entitledDays = 0.0;

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

                        // Check if this leave type should accrue.
                        // allowance_accruable and the policy's own
                        // accrual_frequency/accrual_amount are set
                        // independently in the leave type form - a policy
                        // with a real accrual_amount configured but the
                        // separate allowance_accruable toggle left off was
                        // silently front-loading the FULL entitlement
                        // immediately instead of accruing it, which reads
                        // as "accrual isn't working" even though the rate
                        // was configured correctly. A positive accrual
                        // amount is a clear enough signal on its own.
                        $isAccruable = !Schema::hasColumn('leave_types', 'allowance_accruable')
                            ? true
                            : ((bool) $leaveType->allowance_accruable || (float) ($policy->accrual_amount ?? 0) > 0);

                        // Prepare entitlement used for accrual calculation (transient)
                        $calcEntitlement = new LeaveEntitlement([
                            'employee_id'     => $employee->id,
                            'leave_type_id'   => $leaveTypeId,
                            'leave_period_id' => $leavePeriod->id,
                            'accrued_days'    => 0,
                            'last_accrued_at' => $leavePeriod->start_date,
                        ]);
                        $calcEntitlement->setRelation('leaveType', $leaveType);
                        $calcEntitlement->setRelation('leavePeriod', $leavePeriod);
                        $calcEntitlement->setRelation('employee', $employee);

                        // Compute accrued days snapshot as of now:
                        // - Non-accruable => front-load full entitlement.
                        // - Accruable     => use policyService->calculateAccruedDays().
                        $accrued = $isAccruable
                            ? (float) $policyService->calculateAccruedDays($calcEntitlement, $policy, $asOfDate)
                            : (float) $entitledDays;

                        // Total = carryover + accrued (new rule)
                        $total = (float) $carryover + (float) $accrued;

                        // Create or update entitlement
                        $existing = LeaveEntitlement::where([
                            'business_id'     => $business->id,
                            'employee_id'     => $employee->id,
                            'leave_type_id'   => $leaveTypeId,
                            'leave_period_id' => $leavePeriod->id,
                        ])->first();

                        if ($existing) {
                            // Update existing using the same snapshot rule
                            $existing->entitled_days  = $entitledDays;
                            $existing->carryover_days = $carryover;
                            $existing->accrued_days   = $accrued;
                            $existing->total_days     = $total;
                            $existing->days_remaining = max(0, $total - (float) ($existing->days_taken ?? 0));
                            $existing->last_accrued_at = $existing->last_accrued_at ?: $leavePeriod->start_date;
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
                                'carryover_days'  => $carryover,
                                'accrued_days'    => $accrued,
                                'total_days'      => $total,
                                'days_taken'      => 0,
                                'days_remaining'  => $total,
                                'last_accrued_at' => $leavePeriod->start_date,
                            ]);

                            Log::info("Created entitlement for employee {$employee->id}, leave type {$leaveTypeId}");
                        }

                        $entitled++;

                    } catch (\Exception $e) {
                        $errors[] = "Error for employee {$employee->id}, leave type {$leaveTypeId}: {$e->getMessage()}";
                        Log::error("Entitlement creation error", [
                            'employee_id'   => $employee->id,
                            'leave_type_id' => $leaveTypeId,
                            'error'         => $e->getMessage(),
                            'trace'         => $e->getTraceAsString()
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
            'leave_period_id'   => 'nullable|exists:leave_periods,id',
            'location_id'       => 'nullable|integer|exists:locations,id',
        ]);

        if (empty($validated['leave_period_slug']) && empty($validated['leave_period_id'])) {
            return RequestResponse::badRequest('Leave period id or slug is required.', 422);
        }

        $business = Business::findBySlug(session('active_business_slug'));
        $leavePeriod = LeavePeriod::where('business_id', $business->id)
            ->when(!empty($validated['leave_period_slug']), fn ($q) => $q->where('slug', $validated['leave_period_slug']))
            ->when(!empty($validated['leave_period_id']), fn ($q) => $q->where('id', $validated['leave_period_id']))
            ->first();

        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found.', 404);
        }

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id);

        // Optional location filter (only apply if provided)
        if (!empty($validated['location_id'])) {
            $locationId = (int) $validated['location_id'];
            $query->whereHas('employee', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        $leaveEntitlements = $query->with(['employee.user', 'leaveType', 'leavePeriod'])->get();

        $leaveEntitlementsTable = view('leave._leave_entitlements_table', compact('leaveEntitlements'))->render();

        return RequestResponse::ok('Leave entitlements fetched successfully.', $leaveEntitlementsTable);
    }

    /**
     * Raw JSON entitlements for a leave period - used by the "Set Entitlements"
     * form to badge already-entitled employees as the period selection changes.
     */
    public function getByPeriod(Request $request)
    {
        $validated = $request->validate([
            'leave_period_id' => 'required|integer|exists:leave_periods,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

        $leavePeriod = LeavePeriod::where('id', $validated['leave_period_id'])
            ->where('business_id', $business->id)
            ->first();

        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found.', 404);
        }

        $entitlements = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id)
            ->get(['id', 'employee_id', 'leave_type_id', 'leave_period_id', 'entitled_days', 'accrued_days', 'carryover_days', 'total_days', 'days_taken', 'days_remaining']);

        return RequestResponse::ok('Leave entitlements fetched successfully.', $entitlements);
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

        return view('leave._leave_entitlement_edit', compact('entitlement'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'slug'           => 'required|string',
            'entitled_days'  => 'required|numeric|min:0',
            'accrued_days'   => 'nullable|numeric|min:0',
            'carryover_days' => 'nullable|numeric|min:0',
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

        // assign the grant-facing fields; days_taken/days_pending are
        // NEVER manually settable - they are always derived fresh from
        // live LeaveRequest data by recalculateTotals() below, which is
        // also what actually computes total_days/days_remaining (adjustment_days
        // is a separate correction layer - see applyAdjustment() - and isn't
        // touched by this raw-numbers overwrite).
        $entitlement->entitled_days   = (float)$data['entitled_days'];
        $entitlement->accrued_days    = isset($data['accrued_days']) ? (float)$data['accrued_days'] : (float)$entitlement->accrued_days;
        $entitlement->carryover_days  = isset($data['carryover_days']) ? (float)$data['carryover_days'] : (float)$entitlement->carryover_days;

        // For a non-accruable entitlement that has already been through the
        // accrual pipeline (last_accrued_at set), recalculateTotals() reads
        // accrued_days (which the pipeline mirrors to entitled_days) as the
        // usable pool, not entitled_days directly - so editing entitled_days
        // alone here would silently have no visible effect on the balance.
        // Keep the mirror in sync so a manual correction actually takes
        // effect, exactly like the pipeline itself does.
        $leaveType = $entitlement->leaveType;
        $isAccruable = (bool) ($leaveType->allowance_accruable ?? false);
        if ($entitlement->last_accrued_at !== null && !$isAccruable && !isset($data['accrued_days'])) {
            $entitlement->accrued_days = $entitlement->entitled_days;
        }

        $entitlement->recalculateTotals();

        return response()->json([
            'message' => 'Entitlement updated successfully.',
            'entitlement' => $entitlement->fresh(['employee.user','leaveType','leavePeriod']),
        ]);
    }

    /**
     * Applies an incremental correction to an entitlement (+/- days with a
     * required reason) instead of overwriting every field - a data-entry fix
     * or a goodwill grant stays visible and distinct from the policy-driven
     * entitled_days. Cumulative: calling this twice adds both deltas.
     */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string',
            'adjustment_days' => 'required|numeric',
            'reason' => 'required|string|max:1000',
        ]);

        if ((float) $data['adjustment_days'] === 0.0) {
            return RequestResponse::badRequest('Adjustment must be a non-zero number of days.', 422);
        }

        $decoded = base64_decode(strtr($data['slug'], '-_', '+/'));
        if (!$decoded || substr_count($decoded, ':') !== 3) {
            return RequestResponse::badRequest('Invalid entitlement slug.', 422);
        }

        [$business_id, $employee_id, $leave_type_id, $leave_period_id] = explode(':', $decoded);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business || (int) $business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Invalid business for this entitlement.', 403);
        }

        $entitlement = LeaveEntitlement::where([
            'business_id' => (int) $business_id,
            'employee_id' => (int) $employee_id,
            'leave_type_id' => (int) $leave_type_id,
            'leave_period_id' => (int) $leave_period_id,
        ])->firstOrFail();

        $entitlement->applyAdjustment((float) $data['adjustment_days'], $data['reason']);

        return RequestResponse::ok('Entitlement adjusted successfully.', $entitlement->fresh(['employee.user', 'leaveType', 'leavePeriod']));
    }

    /**
     * Rolls unused balances from one leave period into another for every
     * matching entitlement, capped by each policy's max_carryover_days
     * (LeavePolicyService::computeCarryover(), already correctly implemented
     * but never previously called anywhere). Only updates entitlements that
     * already exist in the destination period - it doesn't create new ones,
     * since that requires full policy resolution best left to the normal
     * "Set Entitlements" flow. Employees skipped for that reason are
     * reported back so HR can create them first if needed.
     */
    public function processCarryover(Request $request, LeavePolicyService $policyService)
    {
        $validated = $request->validate([
            'from_period_id' => 'required|integer|exists:leave_periods,id',
            'to_period_id' => 'required|integer|different:from_period_id|exists:leave_periods,id',
        ]);

        return $this->handleTransaction(function () use ($validated, $policyService) {
            $business = Business::findBySlug(session('active_business_slug'));
            if (!$business) {
                return RequestResponse::badRequest('Business not found.', 404);
            }

            $fromPeriod = LeavePeriod::where('business_id', $business->id)->find($validated['from_period_id']);
            $toPeriod = LeavePeriod::where('business_id', $business->id)->find($validated['to_period_id']);
            if (!$fromPeriod || !$toPeriod) {
                return RequestResponse::badRequest('One or both leave periods were not found for this business.', 404);
            }

            $sourceEntitlements = LeaveEntitlement::where('business_id', $business->id)
                ->where('leave_period_id', $fromPeriod->id)
                ->with('employee')
                ->get();

            $carried = 0;
            $skippedNoDestination = [];
            $skippedNoPolicy = [];

            foreach ($sourceEntitlements as $source) {
                $remaining = $source->getRemainingDays();
                if ($remaining <= 0) {
                    continue;
                }

                $policy = $source->employee
                    ? $policyService->resolvePolicy($source->leave_type_id, $source->employee, $toPeriod->start_date)
                    : null;

                if (!$policy) {
                    $skippedNoPolicy[] = $source->employee_id;
                    continue;
                }

                $destination = LeaveEntitlement::where('business_id', $business->id)
                    ->where('employee_id', $source->employee_id)
                    ->where('leave_type_id', $source->leave_type_id)
                    ->where('leave_period_id', $toPeriod->id)
                    ->first();

                if (!$destination) {
                    // Carrying over shouldn't require HR to have already
                    // run "Set Entitlements" for every employee in the
                    // destination period first - create the baseline row
                    // (entitled/accrued per the destination period's own
                    // policy) the same way autoEntitleAll() would, then
                    // apply this explicit carryover on top of it.
                    $destination = $policyService->createOrUpdateEntitlement(
                        $source->employee,
                        $source->leaveType,
                        $toPeriod,
                        $policy
                    );

                    if (!$destination) {
                        $skippedNoDestination[] = $source->employee_id;
                        continue;
                    }
                }

                $carryoverDays = $policyService->computeCarryover($policy, $remaining);
                if ($carryoverDays <= 0) {
                    continue;
                }

                $destination->carryover_days = $carryoverDays;
                $destination->calculateRemainingDays();
                $carried++;
            }

            $message = "Carried over balances for {$carried} entitlement(s).";
            if ($skippedNoDestination) {
                $message .= ' ' . count($skippedNoDestination) . ' skipped (no entitlement exists yet in the destination period).';
            }
            if ($skippedNoPolicy) {
                $message .= ' ' . count($skippedNoPolicy) . ' skipped (no applicable policy found).';
            }

            return RequestResponse::ok($message, [
                'carried' => $carried,
                'skipped_no_destination' => array_values(array_unique($skippedNoDestination)),
                'skipped_no_policy' => array_values(array_unique($skippedNoPolicy)),
            ]);
        });
    }

    /**
     * Landscape PDF report of leave entitlements for a period, pivoted so each
     * row is one employee and each column is a leave type showing their
     * remaining days - optionally narrowed by department, leave type(s), and/or
     * specific employees.
     */
    public function exportPdf(Request $request, Business $business)
    {
        $validated = $request->validate([
            'leave_period_id' => 'required|integer|exists:leave_periods,id',
            'department_id'   => 'nullable|integer|exists:departments,id',
            'leave_type_ids'  => 'nullable|array',
            'leave_type_ids.*' => 'integer|exists:leave_types,id',
            'employee_ids'    => 'nullable|array',
            'employee_ids.*'  => 'integer|exists:employees,id',
        ]);

        $leavePeriod = LeavePeriod::where('business_id', $business->id)->find($validated['leave_period_id']);
        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found for this business.', 404);
        }

        $query = LeaveEntitlement::where('business_id', $business->id)
            ->where('leave_period_id', $leavePeriod->id)
            // Guards against orphaned entitlement rows whose employee_id no
            // longer resolves to a real Employee (e.g. the employee was later
            // deleted) - without this, an unfiltered export can 500 trying to
            // read ->user off a null employee, while filtering by specific
            // employee ids naturally excludes them anyway.
            ->whereHas('employee');

        if (!empty($validated['department_id'])) {
            $departmentId = (int) $validated['department_id'];
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        if (!empty($validated['leave_type_ids'])) {
            $query->whereIn('leave_type_id', $validated['leave_type_ids']);
        }

        if (!empty($validated['employee_ids'])) {
            $query->whereIn('employee_id', $validated['employee_ids']);
        }

        $entitlements = $query->with(['employee.user', 'employee.department', 'leaveType'])->get();

        $leaveTypeNames = $entitlements->pluck('leaveType.name')->filter()->unique()->sort()->values();

        $rows = $entitlements->groupBy('employee_id')->map(function ($employeeEntitlements) use ($leaveTypeNames) {
            $employee = $employeeEntitlements->first()->employee;
            $remainingByType = [];
            foreach ($leaveTypeNames as $typeName) {
                $match = $employeeEntitlements->first(fn ($e) => optional($e->leaveType)->name === $typeName);
                $remainingByType[$typeName] = $match ? (float) $match->days_remaining : null;
            }
            return [
                'employee' => $employee,
                'remaining' => $remainingByType,
            ];
        })->sortBy(fn ($row) => optional(optional($row['employee'])->user)->name)->values();

        $pdf = Pdf::loadView('leave.reports.entitlements_pdf', [
            'business' => $business,
            'leavePeriod' => $leavePeriod,
            'leaveTypeNames' => $leaveTypeNames,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("leave_entitlements_{$business->slug}_{$leavePeriod->slug}.pdf");
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

    /**
     * Bulk-creates (or, with force=true, re-creates) entitlements for every
     * active employee x every active leave type in a period, via
     * LeavePolicyService::createOrUpdateEntitlement() - a policy-driven
     * alternative to store()'s explicit employee/leave-type selection, for
     * "just entitle everyone per policy" use. Not currently wired to a
     * route.
     */
    public function autoEntitleAll(Request $request, LeavePolicyService $policyService)
    {
        $validated = $request->validate([
            'leave_period_id' => 'required|exists:leave_periods,id',
            'force' => 'nullable|boolean',
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

            $employees = Employee::where('business_id', $business->id)
                ->when(Schema::hasColumn('employees', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->get();

            $leaveTypes = LeaveType::where('business_id', $business->id)
                ->when(Schema::hasColumn('leave_types', 'is_active'), fn ($q) => $q->where('is_active', true))
                ->get();

            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    try {
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

                        $policy = $policyService->resolvePolicy(
                            $leaveType->id,
                            $employee,
                            Carbon::parse($period->start_date)
                        );

                        if (!$policy) {
                            $skipped++;
                            continue;
                        }

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
