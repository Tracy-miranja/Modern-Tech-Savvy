<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Department;
use App\Models\JobCategory;
use App\Models\Project;
use App\Http\RequestResponse;
use App\Services\ProjectTaskStatusService;
use Illuminate\Http\Request;

/**
 * Project Management - the last of the originally-unimplemented modules
 * (see Asset/Learning Management for the sibling precedent this follows).
 * One list page (this controller) + a per-project Kanban board
 * (ProjectTaskController) - a full drag-drop board is genuinely too large
 * for a modal, same exception this app already makes for Disciplinary case
 * detail and the employee profile page.
 */
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

    /**
     * The Kanban board page for one project - a full drag-drop board is
     * genuinely too large for a modal (see class docblock).
     */
    public function showBoard(Business $business, Project $project, ProjectTaskStatusService $statusService)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            abort(404);
        }

        $statusService->ensureSeeded($business);
        $page = $project->name;

        // Same view/JS reused for the employee-portal board (see
        // EnsureProjectMember + ProjectController::myProjects) - only the
        // route names it calls differ, resolved from which named route
        // got us here rather than a second near-duplicate controller/view.
        $routePrefix = str_starts_with((string) request()->route()?->getName(), 'myaccount.') ? 'myaccount.' : 'business.';

        return view('projects.board', compact('page', 'business', 'project', 'routePrefix'));
    }

    /**
     * Employee-portal "My Projects" - projects the logged-in employee is
     * either the manager of or an active (not left_at) member of. Reuses
     * the exact same board/task/comment/time-log controllers and views as
     * the business-admin side (see EnsureProjectMember, which is what
     * makes that reuse safe for a plain business-employee), just its own
     * scoped list page.
     */
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

    /**
     * Lightweight id/name list for the shared report modal's project filter.
     */
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
