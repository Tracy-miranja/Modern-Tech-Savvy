<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\EmployeeContractAction;

class ReinstatementNotification extends Notification
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
        $actionLabel = $this->contractAction->action_type === 'suspension' ? 'suspension' : 'termination';

        return (new MailMessage)
            ->subject('You have been reinstated')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line("Your {$actionLabel} has been reversed and your employment status is active again.")
            ->line('Please contact HR if you have any questions.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Reinstated',
            'message' => 'Your employment status has been reinstated to active.',
            'action_id' => $this->contractAction->id,
        ];
    }
}
