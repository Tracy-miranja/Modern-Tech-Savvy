<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use App\Models\ProjectTaskStatus;
use App\Http\RequestResponse;
use App\Traits\HandleTransactions;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    use HandleTransactions;

    public function board(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $statuses = ProjectTaskStatus::where('business_id', $business->id)
            ->where('is_active', true)
            ->ordered()
            ->with(['tasks' => function ($query) use ($project) {
                $query->where('project_id', $project->id)
                    ->with(['assignee.user:id,name', 'category:id,name,color'])
                    ->orderBy('position');
            }])
            ->get();

        return RequestResponse::ok('Board fetched.', $statuses);
    }

    public function store(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_task_status_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('project_task_statuses', 'id')->where('business_id', $business->id)],
            'project_task_category_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('project_task_categories', 'id')->where('business_id', $business->id)],
            'assignee_employee_id' => 'nullable|integer|exists:employees,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $statusId = $validated['project_task_status_id']
            ?? ProjectTaskStatus::where('business_id', $business->id)->where('is_active', true)->ordered()->value('id');

        if (!$statusId) {
            return RequestResponse::badRequest('This business has no active task statuses configured.');
        }

        $nextPosition = (int) ProjectTask::where('project_task_status_id', $statusId)->max('position') + 1;

        $task = ProjectTask::create($validated + [
            'project_id' => $project->id,
            'business_id' => $business->id,
            'project_task_status_id' => $statusId,
            'priority' => $validated['priority'] ?? 'medium',
            'position' => $nextPosition,
        ]);

        return RequestResponse::created('Task created.', $task->load('assignee.user', 'category'));
    }

    public function update(Request $request, Business $business, ProjectTask $task)
    {
        if ((int) $task->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Task not found for this business.', 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'project_task_category_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('project_task_categories', 'id')->where('business_id', $business->id)],
            'assignee_employee_id' => 'nullable|integer|exists:employees,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $task->update($validated);

        return RequestResponse::ok('Task updated.', $task->fresh(['assignee.user', 'category']));
    }

    public function destroy(Request $request, Business $business, ProjectTask $task)
    {
        if ((int) $task->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Task not found for this business.', 404);
        }

        $task->delete();

        return RequestResponse::ok('Task deleted.');
    }

    public function reorder(Request $request, Business $business, Project $project)
    {
        if ((int) $project->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Project not found for this business.', 404);
        }

        $validated = $request->validate([
            'columns' => 'required|array|min:1',
            'columns.*.status_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('project_task_statuses', 'id')->where('business_id', $business->id)],
            'columns.*.task_ids' => 'present|array',
            'columns.*.task_ids.*' => 'integer',
        ]);

        return $this->handleTransaction(function () use ($validated, $project, $business) {
            $statusIds = collect($validated['columns'])->pluck('status_id');
            $isDoneByStatus = ProjectTaskStatus::whereIn('id', $statusIds)->pluck('is_done', 'id');

            foreach ($validated['columns'] as $column) {
                $isDone = (bool) ($isDoneByStatus[$column['status_id']] ?? false);

                $tasks = ProjectTask::where('project_id', $project->id)
                    ->where('business_id', $business->id)
                    ->whereIn('id', $column['task_ids'])
                    ->get()
                    ->keyBy('id');

                foreach ($column['task_ids'] as $index => $taskId) {
                    $task = $tasks->get($taskId);
                    if (!$task) {
                        continue;
                    }

                    $task->update([
                        'project_task_status_id' => $column['status_id'],
                        'position' => $index + 1,
                        'completed_at' => $isDone ? ($task->completed_at ?? now()) : null,
                    ]);
                }
            }

            return RequestResponse::ok('Board updated.');
        });
    }

    // ---- Comments (Team Collaboration) --------------------------------

    public function fetchComments(Request $request, Business $business, ProjectTask $task)
    {
        if ((int) $task->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Task not found for this business.', 404);
        }

        return RequestResponse::ok('Comments fetched.', $task->comments()->with('employee.user:id,name')->get());
    }

    public function storeComment(Request $request, Business $business, ProjectTask $task)
    {
        if ((int) $task->business_id !== (int) $business->id) {
            return RequestResponse::badRequest('Task not found for this business.', 404);
        }

        $validated = $request->validate(['comment' => 'required|string']);

        $employee = Employee::where('business_id', $business->id)->where('user_id', $request->user()?->id)->first();
        if (!$employee) {
            return RequestResponse::badRequest('No employee record found for the current user in this business.');
        }

        $comment = ProjectTaskComment::create([
            'project_task_id' => $task->id,
            'business_id' => $business->id,
            'employee_id' => $employee->id,
            'comment' => $validated['comment'],
        ]);

        return RequestResponse::created('Comment added.', $comment->load('employee.user'));
    }
}
