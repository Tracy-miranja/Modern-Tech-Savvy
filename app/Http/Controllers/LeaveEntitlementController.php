<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\LeavePeriod;
use Illuminate\Http\Request;
use App\Http\RequestResponse;
use App\Models\LeaveEntitlement;
use App\Traits\HandleTransactions;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;

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
    public function store(Request $request)
    {
        Log::debug('LeaveEntitlement store payload', $request->all());

        $validated = $request->validate([
            'leave_period_id'           => 'required|exists:leave_periods,id',

            //
            'employees'                 => 'nullable|array',
            'employees.*'               => 'nullable|integer|exists:employees,id',


            'leave_type_ids'            => 'required|array|min:1',
            'leave_type_ids.*'          => 'required|integer|exists:leave_types,id',

            'entitled_days'             => 'required|array|min:1',
            'entitled_days.*'           => 'required|numeric|min:0',
        ]);

        return $this->handleTransaction(function () use ($validated) {
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


            $employeeIds = $validated['employees'] ?? Employee::where('business_id', $business->id)->pluck('id')->toArray();


            $typeIds = $validated['leave_type_ids'];
            $daysArr = $validated['entitled_days'];
            if (count($typeIds) !== count($daysArr)) {
                return RequestResponse::badRequest('leave_type_ids and entitled_days must be the same length.', 422);
            }

            $now = now();
            $bulkInsert = [];

            foreach ($employeeIds as $employeeId) {
                foreach ($typeIds as $idx => $leaveTypeId) {
                    $entitledDays = (float)($daysArr[$idx] ?? 0);


                    $existing = LeaveEntitlement::where([
                        'business_id'    => $business->id,
                        'employee_id'    => $employeeId,
                        'leave_type_id'  => $leaveTypeId,
                        'leave_period_id'=> $leavePeriod->id,
                    ])->first();

                    if ($existing) {

                        $existing->update([
                            'entitled_days'   => $entitledDays,
                            'total_days'      => $entitledDays + (float)($existing->accrued_days ?? 0),
                        ]);

                        $existing->calculateRemainingDays();
                    } else {
                        $bulkInsert[] = [
                            'business_id'     => $business->id,
                            'employee_id'     => $employeeId,
                            'leave_type_id'   => $leaveTypeId,
                            'leave_period_id' => $leavePeriod->id,
                            'entitled_days'   => $entitledDays,
                            'accrued_days'    => 0,
                            'total_days'      => $entitledDays,
                            'days_remaining'  => $entitledDays,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ];
                    }
                }
            }

            if (!empty($bulkInsert)) {
                LeaveEntitlement::insert($bulkInsert);
            }

            return RequestResponse::created('Leave entitlements assigned successfully.', [
                'leave_period_slug' => $leavePeriod->slug,
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
            'leave_period_slug' => 'required|exists:leave_periods,slug',
            'location_id'       => 'nullable|integer|exists:locations,id',
        ]);

        $business = Business::findBySlug(session('active_business_slug'));
        if (!$business) {
            return RequestResponse::badRequest('Business not found.', 404);
        }

        $leavePeriod = LeavePeriod::where('slug', $validated['leave_period_slug'])
            ->where('business_id', $business->id)
            ->first();

        if (!$leavePeriod) {
            return RequestResponse::badRequest('Leave period not found.', 404);
        }

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


public function show(Request $request, string $slug)
{
    $business = Business::findBySlug(session('active_business_slug'));
    if (!$business) return RequestResponse::badRequest('Business not found.', 404);

    $decoded = base64_decode(strtr($slug, '-_', '+/'));
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

    // returns read-only details modal
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

    // recompute
    $entitlement->total_days     = (float)$entitlement->entitled_days + (float)$entitlement->accrued_days;
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
}