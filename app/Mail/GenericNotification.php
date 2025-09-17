<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public ?array $cta = null // ['label'=>'View', 'url'=>'...']
    ) {}

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.notifications.generic')
            ->with([
                'bodyText' => $this->bodyText,
                'cta'      => $this->cta,
            ]);
    }
}
