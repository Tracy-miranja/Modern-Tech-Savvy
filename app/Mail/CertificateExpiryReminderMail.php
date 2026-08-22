<?php

namespace App\Mail;

use App\Models\CourseEnrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificateExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $enrollment;

    public function __construct(CourseEnrollment $enrollment)
    {
        $this->enrollment = $enrollment;
    }

    public function build()
    {
        return $this->subject('Certificate Expiring Soon: ' . $this->enrollment->course->title)
            ->view('emails.learning.certificate_expiry_reminder')
            ->with(['enrollment' => $this->enrollment]);
    }
}
