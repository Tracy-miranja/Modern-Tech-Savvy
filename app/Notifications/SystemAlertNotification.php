<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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

    public function toArray($notifiable = null)
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)->line($this->message)->line('Thank you for using our application!');
    }
}
