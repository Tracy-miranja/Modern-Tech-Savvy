<?php

namespace App\Services\Projects;

use App\Mail\ProjectTaskDueReminderMail;
use App\Mail\ProjectTaskOverdueMail;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\Mail;

/**
 * Daily housekeeping for Project Management (see project:sync command):
 * sends the "due soon" reminder N days before due_date (N = per-business
 * Business::project_task_due_reminder_days) and the "overdue" reminder
 * exactly once the day it first crosses into overdue - each guarded by its
 * own *_reminder_sent_at timestamp on project_tasks, same pattern as
 * Learning Management's course_enrollments reminders.
 */
class ProjectSchedulerService
{
    public function sendDueReminders(): int
    {
        $sent = 0;

        ProjectTask::whereNotNull('due_date')
            ->whereNull('completed_at')
            ->whereNull('due_reminder_sent_at')
            ->whereNotNull('assignee_employee_id')
            ->with(['assignee.user', 'project', 'business'])
            ->get()
            ->each(function (ProjectTask $task) use (&$sent) {
                $business = $task->business;
                if (!$business) {
                    return;
                }

                $reminderDays = $business->project_task_due_reminder_days ?? 2;
                if (!now()->addDays($reminderDays)->isSameDay($task->due_date)) {
                    return;
                }

                $email = optional($task->assignee->user)->email;
                if ($email) {
                    Mail::to($email)->send(new ProjectTaskDueReminderMail($task));
                    $sent++;
                }

                $task->update(['due_reminder_sent_at' => now()]);
            });

        return $sent;
    }

    public function sendOverdueReminders(): int
    {
        $sent = 0;

        ProjectTask::whereNotNull('due_date')
            ->whereNull('completed_at')
            ->whereNull('overdue_reminder_sent_at')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotNull('assignee_employee_id')
            ->with(['assignee.user', 'project'])
            ->get()
            ->each(function (ProjectTask $task) use (&$sent) {
                $email = optional($task->assignee->user)->email;
                if ($email) {
                    Mail::to($email)->send(new ProjectTaskOverdueMail($task));
                    $sent++;
                }

                $task->update(['overdue_reminder_sent_at' => now()]);
            });

        return $sent;
    }
}
