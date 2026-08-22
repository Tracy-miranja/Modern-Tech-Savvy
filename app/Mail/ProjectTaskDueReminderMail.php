<?php

namespace App\Mail;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectTaskDueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;

    public function __construct(ProjectTask $task)
    {
        $this->task = $task;
    }

    public function build()
    {
        return $this->subject('Task Due Soon: ' . $this->task->title)
            ->view('emails.projects.task_due_reminder')
            ->with(['task' => $this->task]);
    }
}
