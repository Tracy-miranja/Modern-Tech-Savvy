<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class P9Mail extends Mailable
{
    use Queueable, SerializesModels;

    public $employeePayroll;
    public $pdfPath;
    public $year;
    public $user;

    public function __construct($employeePayroll, $pdfPath, $year, $user)
    {
        $this->employeePayroll = $employeePayroll;
        $this->pdfPath = $pdfPath;
        $this->year = $year;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Your P9 Form for ' . $this->year)
                    ->view('emails.p9')
                    ->attach($this->pdfPath, [
                        'as' => 'P9_' . $this->employeePayroll->id . '.pdf',
                        'mime' => 'application/pdf',
                    ]);
    }
}
