<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\EmployeeContractAction;

class SuspensionNotification extends Notification
{
    use Queueable;

    protected $contractAction;

    public function __construct(EmployeeContractAction $contractAction)
    {
        $this->contractAction = $contractAction;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Notice of Suspension')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('You have been placed on suspension effective ' . $this->contractAction->action_date->format('F d, Y') . '.')
            ->line('**Reason:** ' . $this->contractAction->reason)
            ->when($this->contractAction->description, fn ($mail) => $mail->line($this->contractAction->description))
            ->line('Please contact HR if you have any questions.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Employee Suspended',
            'message' => 'You have been suspended effective ' . $this->contractAction->action_date->format('F d, Y') . '. Reason: ' . $this->contractAction->reason,
            'action_id' => $this->contractAction->id,
        ];
    }
}
