<?php

namespace App\Notifications;

use App\Models\LeaveDelegation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the reliever (delegate) when someone assigns them to cover their
 * duties while on leave - mail + portal, so they hear about it either way.
 */
class LeaveDelegationAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveDelegation $delegation)
    {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $employeeName = optional($this->delegation->employee->user)->name ?? 'A colleague';
        $leave = $this->delegation->leaveRequest;

        return (new MailMessage)
            ->subject("You've been asked to cover for {$employeeName} while they're on leave")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$employeeName} has asked you to cover their duties while they're on leave from "
                . optional($leave?->start_date)->format('M d, Y') . ' to ' . optional($leave?->end_date)->format('M d, Y') . '.')
            ->when($this->delegation->duties_delegated, fn ($mail) => $mail->line('Handover notes: ' . $this->delegation->duties_delegated))
            ->action('View Request', url("/leave/show/{$leave?->reference_number}"))
            ->line('Please accept or decline this from your portal so they know whether to make other arrangements.');
    }

    public function toDatabase($notifiable)
    {
        $employeeName = optional($this->delegation->employee->user)->name ?? 'A colleague';
        $leave = $this->delegation->leaveRequest;

        return [
            'delegation_id' => $this->delegation->id,
            'leave_request_id' => $this->delegation->leave_request_id,
            'reference_number' => $leave?->reference_number,
            'message' => "{$employeeName} asked you to cover their duties from "
                . optional($leave?->start_date)->format('M d, Y') . ' to ' . optional($leave?->end_date)->format('M d, Y') . '.',
        ];
    }
}
