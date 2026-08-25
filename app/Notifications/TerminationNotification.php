<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\EmployeeContractAction;

class TerminationNotification extends Notification
{
    use Queueable;

    protected $contractAction;
    protected $pdfContent;

    public function __construct(EmployeeContractAction $contractAction, $pdfContent = null)
    {
        $this->contractAction = $contractAction;
        $this->pdfContent = $pdfContent;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Termination of Employment')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('We regret to inform you that your employment has been terminated.')
            ->line('**Reason:** ' . $this->contractAction->reason)
            ->line('**Effective Date:** ' . $this->contractAction->action_date->format('F d, Y'))
            ->line('Please find the termination letter attached for your reference.')
            ->line('Thank you for your contributions.');

        if ($this->pdfContent) {
            $mail->attachData($this->pdfContent, 'termination_letter.pdf', [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Employment Termination',
            'message' => 'Your employment has been terminated effective ' . $this->contractAction->action_date->format('F d, Y') . '. Reason: ' . $this->contractAction->reason,
            'action_id' => $this->contractAction->id,
        ];
    }
}
