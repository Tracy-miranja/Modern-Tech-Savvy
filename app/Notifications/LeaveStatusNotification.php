<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LeaveStatusNotification extends Notification implements ShouldQueue
{
    // use Queueable;

    public $leave;

    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $status = $this->leave->status;
        $subject = $this->getEmailSubject($status);
        $greeting = "Hello {$notifiable->name},";

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        // Build email content based on status
        switch ($status) {
            case 'approved':
                $mailMessage = $this->buildApprovedEmail($mailMessage);
                break;
            case 'rejected':
                $mailMessage = $this->buildRejectedEmail($mailMessage);
                break;
            case 'pending':
                $mailMessage = $this->buildPendingEmail($mailMessage);
                break;
            default:
                $mailMessage = $this->buildGenericEmail($mailMessage);
        }

        return $mailMessage->line('Thank you for using our leave management system.');
    }

    public function toDatabase($notifiable)
    {
        $status = $this->leave->status;

        return [
            'leave_id'           => $this->leave->id,
            'reference_number'   => $this->leave->reference_number,
            'status'             => $status,
            'start_date'         => $this->leave->start_date->format('Y-m-d'),
            'end_date'           => $this->leave->end_date->format('Y-m-d'),
            'total_days'         => $this->leave->total_days,
            'leave_type'         => optional($this->leave->leaveType)->name,
            'reason'             => $this->leave->reason,
            'rejection_reason'   => $this->leave->rejection_reason,
            'current_level'      => $this->leave->current_approval_level,
            'required_levels'    => optional($this->leave->leaveType)->approval_levels,
            'message'            => $this->getStatusMessage($status),
            'action_required'    => $this->getActionRequired($status, $notifiable),
        ];
    }

    protected function getEmailSubject($status)
    {
        switch ($status) {
            case 'approved':
                return 'Leave Request Approved - ' . $this->leave->reference_number;
            case 'rejected':
                return 'Leave Request Rejected - ' . $this->leave->reference_number;
            case 'pending':
                return 'Leave Request Status Update - ' . $this->leave->reference_number;
            default:
                return 'Leave Request Update - ' . $this->leave->reference_number;
        }
    }

    protected function buildApprovedEmail($mailMessage)
    {
        return $mailMessage
            ->line("Great news! Your leave request has been **approved**.")
            ->line("**Leave Details:**")
            ->line("• Reference: {$this->leave->reference_number}")
            ->line("• Leave Type: " . optional($this->leave->leaveType)->name)
            ->line("• Duration: {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')}")
            ->line("• Total Days: {$this->leave->total_days}")
            ->line("• Approved By: " . optional($this->leave->approvedBy)->name)
            ->line("• Approved On: " . optional($this->leave->approved_at)->format('M d, Y H:i'))
            ->when($this->leave->reason, function($mail) {
                return $mail->line("• Reason: {$this->leave->reason}");
            })
            ->line("Please ensure proper handover of your responsibilities before your leave begins.")
            ->action('View Leave Request', url("/leave/show/{$this->leave->reference_number}"));
    }

    protected function buildRejectedEmail($mailMessage)
    {
        return $mailMessage
            ->line("We regret to inform you that your leave request has been **rejected**.")
            ->line("**Leave Details:**")
            ->line("• Reference: {$this->leave->reference_number}")
            ->line("• Leave Type: " . optional($this->leave->leaveType)->name)
            ->line("• Duration: {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')}")
            ->line("• Total Days: {$this->leave->total_days}")
            ->when($this->leave->rejection_reason, function($mail) {
                return $mail->line("• **Rejection Reason:** {$this->leave->rejection_reason}");
            })
            ->line("If you have any questions or would like to discuss this decision, please contact your supervisor or HR department.")
            ->action('View Leave Request', url("/leave/show/{$this->leave->reference_number}"));
    }

    protected function buildPendingEmail($mailMessage)
    {
        $currentLevel = $this->leave->current_approval_level;
        $requiredLevels = optional($this->leave->leaveType)->approval_levels ?? 1;

        if ($currentLevel > 0 && $currentLevel < $requiredLevels) {
            // Partial approval
            $mailMessage = $mailMessage
                ->line("Your leave request has received partial approval and is progressing through the approval process.")
                ->line("**Current Status:** Level {$currentLevel} of {$requiredLevels} approvals completed")
                ->line("**Next Step:** Waiting for final approval from HR");
        } else {
            // Initial submission or document upload
            $mailMessage = $mailMessage
                ->line("Your leave request status has been updated.")
                ->line("**Current Status:** Under review");
        }

        return $mailMessage
            ->line("**Leave Details:**")
            ->line("• Reference: {$this->leave->reference_number}")
            ->line("• Leave Type: " . optional($this->leave->leaveType)->name)
            ->line("• Duration: {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')}")
            ->line("• Total Days: {$this->leave->total_days}")
            ->when($this->leave->requires_documentation && !$this->leave->attachment, function($mail) {
                return $mail->line("**Action Required:** Please upload the required documentation to complete your request.");
            })
            ->line("We will notify you once a decision has been made.")
            ->action('View Leave Request', url("/leave/show/{$this->leave->reference_number}"));
    }

    protected function buildGenericEmail($mailMessage)
    {
        return $mailMessage
            ->line("Your leave request has been updated.")
            ->line("**Leave Details:**")
            ->line("• Reference: {$this->leave->reference_number}")
            ->line("• Leave Type: " . optional($this->leave->leaveType)->name)
            ->line("• Duration: {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')}")
            ->line("• Status: " . ucfirst($this->leave->status))
            ->action('View Leave Request', url("/leave/show/{$this->leave->reference_number}"));
    }

    protected function getStatusMessage($status)
    {
        switch ($status) {
            case 'approved':
                return "Your leave request from {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')} has been approved.";
            case 'rejected':
                return "Your leave request from {$this->leave->start_date->format('M d, Y')} to {$this->leave->end_date->format('M d, Y')} has been rejected.";
            case 'pending':
                $currentLevel = $this->leave->current_approval_level;
                $requiredLevels = optional($this->leave->leaveType)->approval_levels ?? 1;

                if ($currentLevel > 0 && $currentLevel < $requiredLevels) {
                    return "Your leave request has received level {$currentLevel} approval and is awaiting final approval.";
                }
                return "Your leave request is under review.";
            default:
                return "Your leave request status has been updated.";
        }
    }

    protected function getActionRequired($status, $notifiable)
    {
        // Check if this is the employee and documentation is required
        if ($status === 'pending' &&
            $this->leave->requires_documentation &&
            !$this->leave->attachment &&
            optional($notifiable->employee)->id === $this->leave->employee_id) {
            return 'upload_document';
        }

        return null;
    }
}
