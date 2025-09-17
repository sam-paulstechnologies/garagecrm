<?php

namespace Tests\Feature\Mail;

use App\Mail\BrandedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrandedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_and_queues_branded_notification()
    {
        Mail::fake();

        $payload = [
            'subject'  => 'Test Subject',
            'title'    => 'Hello!',
            'greeting' => 'Hi Sam,',
            'lines'    => ['Line one', 'Line two'],
            'cta_text' => 'Open Dashboard',
            'cta_url'  => 'https://example.com/dashboard',
            'outro'    => 'Thanks for choosing us.',
        ];

        Mail::to('sam@example.com')->queue(new BrandedNotification($payload));

        Mail::assertQueued(BrandedNotification::class, function ($mail) use ($payload) {
            $this->assertEquals($payload['subject'], $mail->data['subject']);
            $this->assertEquals($payload['title'], $mail->data['title']);
            $this->assertEquals($payload['greeting'], $mail->data['greeting']);
            $this->assertEquals($payload['cta_text'], $mail->data['cta_text']);
            $this->assertEquals($payload['cta_url'], $mail->data['cta_url']);
            return true;
        });
    }
}
