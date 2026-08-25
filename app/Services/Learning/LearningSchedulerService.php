<?php

namespace App\Services\Learning;

use App\Mail\CertificateExpiryReminderMail;
use App\Mail\CourseSessionReminderMail;
use App\Models\CourseEnrollment;
use App\Models\CourseMandate;
use Illuminate\Support\Facades\Mail;

class LearningSchedulerService
{
    public function syncMandateEnrollments(): int
    {
        $count = 0;

        CourseMandate::where('is_active', true)->each(function (CourseMandate $mandate) use (&$count) {
            $count += $mandate->autoEnroll();
        });

        return $count;
    }

    public function sendSessionReminders(): int
    {
        $sent = 0;

        CourseEnrollment::whereNotNull('course_session_id')
            ->whereNull('session_reminder_sent_at')
            ->whereIn('status', ['enrolled', 'in_progress'])
            ->with(['session', 'course', 'employee.user', 'business'])
            ->get()
            ->each(function (CourseEnrollment $enrollment) use (&$sent) {
                $session = $enrollment->session;
                $business = $enrollment->business;
                if (!$session || !$business || !$session->start_date) {
                    return;
                }

                $reminderDays = $business->learning_session_reminder_days ?? 3;
                if (!now()->addDays($reminderDays)->isSameDay($session->start_date)) {
                    return;
                }

                $email = optional($enrollment->employee->user)->email;
                if ($email) {
                    Mail::to($email)->send(new CourseSessionReminderMail($enrollment));
                    $sent++;
                }

                $enrollment->update(['session_reminder_sent_at' => now()]);
            });

        return $sent;
    }

    public function sendCertificateExpiryReminders(): int
    {
        $sent = 0;

        CourseEnrollment::where('certificate_issued', true)
            ->whereNotNull('certificate_expiry_date')
            ->whereNull('certificate_expiry_reminder_sent_at')
            ->with(['course', 'employee.user', 'business'])
            ->get()
            ->each(function (CourseEnrollment $enrollment) use (&$sent) {
                $business = $enrollment->business;
                if (!$business) {
                    return;
                }

                $reminderDays = $business->learning_certificate_expiry_reminder_days ?? 30;
                if (!now()->addDays($reminderDays)->isSameDay($enrollment->certificate_expiry_date)) {
                    return;
                }

                $email = optional($enrollment->employee->user)->email;
                if ($email) {
                    Mail::to($email)->send(new CertificateExpiryReminderMail($enrollment));
                    $sent++;
                }

                $enrollment->update(['certificate_expiry_reminder_sent_at' => now()]);
            });

        return $sent;
    }
}
