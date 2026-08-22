<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTimeLog;
use App\Http\RequestResponse;
use Illuminate\Http\Request;

/**
 * Time Tracking - a manual daily hours log against a project, optionally a
 * specific task. See the create_project_time_logs_table migration's
 * docblock for why this is a simple log, not a start/stop timer.
 */
class ProjectTimeLogController extends Controller
{
    public function fetch(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $logs = $project->timeLogs()
            ->with(['employee.user:id,name', 'task:id,title'])
            ->orderByDesc('date')
            ->get();

        return RequestResponse::ok('Time logs fetched.', $logs);
    }

    public function store(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'project_task_id' => 'nullable|integer|exists:project_tasks,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.25|max:24',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::where('business_id', $business->id)->find($validated['employee_id']);
        if (!$employee) {
            return RequestResponse::badRequest('Employee not found for this business.', 404);
        }

        if (!empty($validated['project_task_id'])) {
            $task = ProjectTask::where('business_id', $business->id)->where('project_id', $project->id)->find($validated['project_task_id']);
            if (!$task) {
                return RequestResponse::badRequest('Task not found for this project.', 404);
            }
        }

        $log = ProjectTimeLog::create($validated + [
            'project_id' => $project->id,
            'business_id' => $business->id,
        ]);

        return RequestResponse::created('Time logged.', $log->load('employee.user', 'task'));
    }

    public function destroy(Request $request, Business $business, ProjectTimeLog $timeLog)
    {
        if ((int) $timeLog->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Time log not found for this business.', 404);
        }

        $timeLog->delete();

        return RequestResponse::ok('Time log removed.');
    }
}
