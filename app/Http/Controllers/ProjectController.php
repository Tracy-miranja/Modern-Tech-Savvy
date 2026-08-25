<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\Project;
use App\Http\RequestResponse;
use App\Services\ProjectTaskStatusService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Business $business, ProjectTaskStatusService $statusService)
    {
        $page = 'Project Management';
        $statusService->ensureSeeded($business);
        $departments = Department::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);
        $jobCategories = JobCategory::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return view('projects.index', compact('page', 'business', 'departments', 'jobCategories'));
    }

    public function showBoard(Business $business, Project $project, ProjectTaskStatusService $statusService)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            abort(404);
        }

        $statusService->ensureSeeded($business);
        $page = $project->name;

        $routePrefix = str_starts_with((string) request()->route()?->getName(), 'myaccount.') ? 'myaccount.' : 'business.';

        return view('projects.board', compact('page', 'business', 'project', 'routePrefix'));
    }

    public function myProjects(Business $business)
    {
        $page = 'My Projects';
        $employee = auth()->user()->activeEmployee();

        $projects = $employee && (int) $employee->business_id === (int) $business->id
            ? Project::where('business_id', $business->id)
                ->where(function ($q) use ($employee) {
                    $q->where('manager_employee_id', $employee->id)
                        ->orWhereHas('members', function ($m) use ($employee) {
                            $m->where('employee_id', $employee->id)->whereNull('left_at');
                        });
                })
                ->withCount('tasks')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('projects.portal-index', compact('page', 'business', 'projects'));
    }

    public function fetch(Request $request, Business $business)
    {
        $query = Project::where('business_id', $business->id)
            ->with(['department:id,name', 'manager.user:id,name'])
            ->withCount('tasks');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $projects = $query->orderByDesc('id')->get();

        return RequestResponse::ok('Projects fetched.', $projects);
    }

    public function options(Request $request, Business $business)
    {
        $projects = Project::where('business_id', $business->id)->orderBy('name')->get(['id', 'name']);

        return RequestResponse::ok('Projects fetched.', $projects);
    }

    public function store(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|integer|exists:departments,id',
            'manager_employee_id' => 'nullable|integer|exists:employees,id',
            'status' => 'nullable|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project = Project::create($validated + ['business_id' => $business->id, 'status' => $validated['status'] ?? 'planning']);

        return RequestResponse::created('Project created.', $project);
    }

    public function update(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|integer|exists:departments,id',
            'manager_employee_id' => 'nullable|integer|exists:employees,id',
            'status' => 'nullable|in:planning,active,on_hold,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $project->update($validated);

        return RequestResponse::ok('Project updated.', $project->fresh());
    }

    public function destroy(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        if ($project->tasks()->exists()) {
            return RequestResponse::badRequest('This project has tasks - archive it (set status to cancelled) instead of deleting.');
        }

        $project->delete();

        return RequestResponse::ok('Project deleted.');
    }

    // ---- Settings: task due-date reminder days -----------------------

    public function updateSettings(Request $request, Business $business)
    {
        $validated = $request->validate([
            'project_task_due_reminder_days' => 'required|integer|min:0|max:60',
        ]);

        $business->update($validated);

        return RequestResponse::ok('Project settings updated.', $business->fresh());
    }
}
