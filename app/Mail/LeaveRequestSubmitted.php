<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $leaveRequest;
    public $showUrl;

    public function __construct(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest->loadMissing(['employee.user','leaveType','business']);

        $this->subject('New Leave Request: '.$leaveRequest->reference_number);

        $this->showUrl = route('business.leave.show', [
            'business' => $this->leaveRequest->business->slug,
            'leave'    => $this->leaveRequest->reference_number,
        ]);
    }

    public function build()
    {
        return $this
            ->view('emails.leave.request_submitted')
            ->with([
                'leaveRequest' => $this->leaveRequest,
                'showUrl'      => $this->showUrl,
            ]);
    }
}
