<?php

namespace App\Mail;

use App\Models\Sales\Opportunity;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class OpportunityConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Opportunity $opp) {}
    public function build() {
        return $this->subject('Your booking is confirmed')
            ->view('emails.opportunities.confirmed');
    }
}
