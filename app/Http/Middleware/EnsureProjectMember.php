<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectTask;
use App\Models\ProjectTimeLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user()?->activeEmployee();
        if (!$employee) {
            abort(403, 'No employee record for this business.');
        }

        $project = $this->resolveProject($request);
        if (!$project) {
            abort(404);
        }

        $isManager = (int) $project->manager_employee_id === (int) $employee->id;
        $isMember = ProjectMember::where('project_id', $project->id)
            ->where('employee_id', $employee->id)
            ->whereNull('left_at')
            ->exists();

        if (!$isManager && !$isMember) {
            abort(403, 'You are not a member of this project.');
        }

        return $next($request);
    }

    private function resolveProject(Request $request): ?Project
    {
        $project = $request->route('project');
        if ($project instanceof Project) {
            return $project;
        }

        $task = $request->route('task');
        if ($task instanceof ProjectTask) {
            return $task->project;
        }

        $member = $request->route('member');
        if ($member instanceof ProjectMember) {
            return $member->project;
        }

        $timeLog = $request->route('timeLog');
        if ($timeLog instanceof ProjectTimeLog) {
            return $timeLog->project;
        }

        return null;
    }
}
