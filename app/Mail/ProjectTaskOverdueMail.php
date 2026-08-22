<?php

namespace App\Mail;

use App\Models\ProjectTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectTaskOverdueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $task;

    public function __construct(ProjectTask $task)
    {
        $this->task = $task;
    }

    public function build()
    {
        return $this->subject('Task Overdue: ' . $this->task->title)
            ->view('emails.projects.task_overdue')
            ->with(['task' => $this->task]);
    }
}
