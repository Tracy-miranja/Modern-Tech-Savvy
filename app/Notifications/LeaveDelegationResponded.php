<?php

namespace App\Notifications;

use App\Models\LeaveDelegation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when their chosen reliever accepts or declines
 * covering their duties while they're on leave.
 */
class LeaveDelegationResponded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveDelegation $delegation, public string $outcome)
    {
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $delegateName = optional($this->delegation->delegate->user)->name ?? 'Your reliever';
        $leave = $this->delegation->leaveRequest;

        $mail = (new MailMessage)
            ->subject($this->outcome === 'accepted'
                ? "{$delegateName} accepted covering your leave"
                : "{$delegateName} declined covering your leave")
            ->greeting("Hello {$notifiable->name},");

        if ($this->outcome === 'accepted') {
            $mail->line("{$delegateName} has accepted covering your duties while you're on leave.");
        } else {
            $mail->line("{$delegateName} has declined to cover your duties while you're on leave. You may want to arrange another reliever.");
        }

        return $mail->action('View Request', url("/leave/show/{$leave?->reference_number}"));
    }

    public function toDatabase($notifiable)
    {
        $delegateName = optional($this->delegation->delegate->user)->name ?? 'Your reliever';
        $leave = $this->delegation->leaveRequest;

        return [
            'delegation_id' => $this->delegation->id,
            'leave_request_id' => $this->delegation->leave_request_id,
            'reference_number' => $leave?->reference_number,
            'outcome' => $this->outcome,
            'message' => $this->outcome === 'accepted'
                ? "{$delegateName} accepted covering your leave."
                : "{$delegateName} declined to cover your leave - you may want to arrange another reliever.",
        ];
    }
}
