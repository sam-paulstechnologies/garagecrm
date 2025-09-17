<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrandedNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Public data available to the Blade view.
     */
    public array $data;

    /**
     * Create a new message instance.
     *
     * @param  array{
     *   subject: string,
     *   title?: string,
     *   greeting?: string,
     *   lines?: array<string>,
     *   cta_text?: string|null,
     *   cta_url?: string|null,
     *   outro?: string|null,
     *   footer_note?: string|null,
     *   hero_url?: string|null
     * }  $payload
     */
    public function __construct(array $payload)
    {
        $this->data = [
            'subject'     => $payload['subject'] ?? config('app.name') . ' Notification',
            'title'       => $payload['title'] ?? null,
            'greeting'    => $payload['greeting'] ?? __('emails.greeting_default'),
            'lines'       => $payload['lines'] ?? [],
            'cta_text'    => $payload['cta_text'] ?? null,
            'cta_url'     => $payload['cta_url'] ?? null,
            'outro'       => $payload['outro'] ?? null,
            'footer_note' => $payload['footer_note'] ?? null,
            'hero_url'    => $payload['hero_url'] ?? null,
        ];
    }

    public function build()
    {
        $subject = $this->data['subject'] ?? (config('app.name') . ' Notification');

        return $this->subject($subject)
            ->from(
                config('mail.from.address'),
                config('mail.from.name')
            )
            ->view('emails.notification')
            ->with($this->data);
    }
}
