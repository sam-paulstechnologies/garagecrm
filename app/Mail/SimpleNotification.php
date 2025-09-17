<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SimpleNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $bodyText;

    public function __construct(string $subjectLine, string $bodyText)
    {
        $this->subjectLine = $subjectLine;
        $this->bodyText = $bodyText;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->text('emails.simple-text'); // uses a plain .blade.php below
    }
}
