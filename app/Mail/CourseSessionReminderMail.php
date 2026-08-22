<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseSessionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $enrollment;

    public function __construct(CourseEnrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    public function build()
    {
        return $this->subject('Upcoming Training: ' . $this->enrollment->course->title)
            ->view('emails.learning.session_reminder')
            ->with(['enrollment' => $this->enrollment]);
    }
}
