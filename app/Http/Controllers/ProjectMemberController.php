<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Http\RequestResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function fetch(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $members = $project->members()->with('employee.user:id,name')->orderByDesc('id')->get();

        return RequestResponse::ok('Members fetched.', $members);
    }

    public function store(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'role_on_project' => 'nullable|string|max:150',
            'allocation_percentage' => 'nullable|integer|min:1|max:100',
        ]);

        $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
        if (!$employee) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        $existing = ProjectMember::where('project_id', $project->id)->where('employee_id', $employee->id)->first();
        if ($existing) {
            if (!$existing->left_at) {
                return RequestResponse::badRequest('This employee is already a member of this project.');
            }

            $existing->update([
                'role_on_project' => $validated['role_on_project'] ?? $existing->role_on_project,
                'allocation_percentage' => $validated['allocation_percentage'] ?? $existing->allocation_percentage,
                'joined_at' => now(),
                'left_at' => null,
            ]);

            return RequestResponse::created('Member re-added.', $existing->fresh()->load('employee.user'));
        }

        $member = ProjectMember::create($validated + [
            'project_id' => $project->id,
            'business_id' => $business->id,
            'joined_at' => now(),
        ]);

        return RequestResponse::created('Member added.', $member->load('employee.user'));
    }

    public function update(Request $request, Business $business, ProjectMember $member)
    {
        if ((int) $member->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Member not found for this business.', 404);
        }

        $validated = $request->validate([
            'role_on_project' => 'nullable|string|max:150',
            'allocation_percentage' => 'nullable|integer|min:1|max:100',
        ]);

        $member->update($validated);

        return RequestResponse::ok('Member updated.', $member->fresh()->load('employee.user'));
    }

    public function destroy(Request $request, Business $business, ProjectMember $member)
    {
        if ((int) $member->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Member not found for this business.', 404);
        }

        $member->update(['left_at' => now()]);

        return RequestResponse::ok('Member removed from project.');
    }
}
