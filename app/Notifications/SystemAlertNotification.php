<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage; // If you want to send it via email as well
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $data;

    public function __construct($message, $data)
    {
        $this->message = $message;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    /**
     * Not one of the via() channels itself - App\Events\NotificationSent
     * (the app's own real-time-alert broadcast, fired by
     * NotificationService::sendNotification() after every notification
     * regardless of channel) calls toArray() unconditionally to build its
     * payload. Without this, that fires a fatal "Call to undefined
     * method" Error (not an \Exception, so NotificationService's own
     * try/catch doesn't stop it) on every login that skips 2FA - the
     * business-employee/general-hr/restricted-hr/head-of-department/
     * chief-of-staff roles reach this call directly in store(); the
     * 2FA-required roles never do, since they're redirected to
     * verifyTwoFactorCode() instead, which doesn't send this notification
     * at all - that's why this only ever broke non-2FA logins.
     */
    public function toArray($notifiable = null)
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->line($this->message)->line('Thank you for using our application!');
    }
}
