<?php

namespace App\Mail;

use App\Models\Jobs\Job;
use Illuminate\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class JobCompletedMail extends Mailable
{
    use Queueable, SerializesModels;
    public function __construct(public Job $job) {}
    public function build() {
        return $this->subject('Your car is ready')
            ->view('emails.jobs.completed');
    }
}
