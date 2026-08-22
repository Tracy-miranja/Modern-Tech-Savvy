<?php

namespace App\Http\Controllers;

use App\Models\AttendancePolicy;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\RequestResponse;

/**
 * CRUD for Expected Working Hours policies (GUIDE plan, Phase 1c) - the
 * same specificity-scoped shape as leave policies: a business-wide
 * default, optionally overridden per department, per job category, or per
 * individual employee. Resolution itself lives in AttendancePolicyService.
 */
class AttendancePolicyController extends Controller
{
    public function fetch(Business $business)
    {
        $policies = AttendancePolicy::where('business_id', $business->id)
            ->with(['department:id,name', 'jobCategory:id,name', 'employee.user:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (AttendancePolicy $p) => [
                'id' => $p->id,
                'scope_label' => $p->scopeLabel(),
                'department_id' => $p->department_id,
                'job_category_id' => $p->job_category_id,
                'employee_id' => $p->employee_id,
                'expected_hours_per_day' => $p->expected_hours_per_day,
                'is_active' => $p->is_active,
            ]);

        return RequestResponse::ok('Attendance policies fetched successfully.', $policies);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'job_category_id' => 'nullable|integer|exists:job_categories,id',
            'employee_id' => 'nullable|integer|exists:employees,id',
            'expected_hours_per_day' => 'required|numeric|min:0|max:24',
        ]);

        $policy = AttendancePolicy::create([
            'business_id' => $business->id,
            'department_id' => $validated['department_id'] ?? null,
            'job_category_id' => $validated['job_category_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'expected_hours_per_day' => $validated['expected_hours_per_day'],
            'is_active' => true,
        ]);

        return RequestResponse::created('Policy saved successfully.', $policy);
    }

    public function destroy(Business $business, AttendancePolicy $policy)
    {
        if ((int) $policy->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Policy not found for this business.', 404);
        }

        $policy->delete();

        return RequestResponse::ok('Policy removed successfully.');
    }
}
