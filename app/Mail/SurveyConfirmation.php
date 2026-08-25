<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Campaign;
use App\Models\Lead;

class SurveyConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $campaign;
    public $lead;

    public function __construct(Campaign $campaign, Lead $lead)
    {
        $this->campaign = $campaign;
        $this->lead = $lead;
    }

    public function build()
    {
        if (!$this->lead->email) {
            \Log::warning("No email provided for SurveyConfirmation for lead ID {$this->lead->id}");
            return $this;
        }

        return $this->subject('Thank You for Your Feedback!')
            ->view('emails.survey_confirmation')
            ->with([
                'campaign_name' => $this->campaign->name ?? 'Unknown Campaign',
                'name' => $this->lead->name ?? 'Not Asked',
                'responses' => $this->lead->survey_responses ?? [],
            ]);
    }
}