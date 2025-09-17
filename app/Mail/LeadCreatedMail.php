<?php 

namespace App\Mail;

use App\Models\Client\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadCreatedMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Lead $lead) {}
    public function build() {
        return $this->subject('We received your request')
            ->view('emails.leads.created');
    }
}
