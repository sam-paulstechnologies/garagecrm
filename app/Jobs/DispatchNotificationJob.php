<?php

namespace App\Jobs;

use Closure;
use Throwable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\GenericNotification;
use App\Services\TwilioWhatsApp;
use App\Models\Shared\CommunicationLog;

class DispatchNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'notifications';
    public $tries = 3;
    public $timeout = 120;

    public function __construct(public object $event) {}

    public function handle(TwilioWhatsApp $wa): void
    {
        $map = config('notify');
        $eventClass = $this->event::class;

        if (!isset($map[$eventClass])) {
            Log::warning('notify: no mapping for event', ['event' => $eventClass]);
            return;
        }

        $cfg = $map[$eventClass];

        // helpers to eval closures or return raw values
        $eval = function($val) {
            return $val instanceof Closure ? $val($this->event) : $val;
        };

        $to     = $eval($cfg['to'] ?? fn() => ['phone' => null, 'email' => null]);
        $waTpl  = $eval($cfg['template'] ?? null);
        $subs   = $eval($cfg['placeholders'] ?? []);
        $channels = $eval($cfg['channels'] ?? ['whatsapp', 'email']);

        // EMAIL
        if (in_array('email', $channels, true) && !empty($to['email'])) {
            $subject = (string) $eval($cfg['subject'] ?? 'Notification');
            $body    = (string) $eval($cfg['body'] ?? '');
            $cta     = $eval($cfg['cta'] ?? null); // ['label' => 'View', 'url' => '...']

            try {
                Mail::to($to['email'])->send(new GenericNotification($subject, $body, $cta));
                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject'  => $subject,
                    'body'     => $body,
                ]);
            } catch (Throwable $e) {
                Log::error('notify.email.failed', ['err' => $e->getMessage()]);
                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject'  => $subject,
                    'body'     => Str::limit($body, 500),
                    'meta'     => ['error' => $e->getMessage()],
                ]);
            }
        }

        // WHATSAPP
        if (in_array('whatsapp', $channels, true) && !empty($to['phone']) && $waTpl) {
            try {
                $res = $wa->sendTemplate($to['phone'], $waTpl, $subs);
                $ok  = (bool) ($res['ok'] ?? false);

                $this->logComm('whatsapp', [
                    'to_phone'     => $to['phone'],
                    'template'     => $waTpl,
                    'body'         => $subs, // store args
                    'provider_sid' => $res['sid'] ?? null,
                    'meta'         => $ok ? null : ['error' => $res['error'] ?? 'unknown'],
                ]);
            } catch (Throwable $e) {
                Log::error('notify.wa.failed', ['err' => $e->getMessage()]);
                $this->logComm('whatsapp', [
                    'to_phone' => $to['phone'],
                    'template' => $waTpl,
                    'meta'     => ['error' => $e->getMessage()],
                ]);
            }
        }
    }

    protected function logComm(string $channel, array $data = []): void
    {
        // Attempt to infer entity
        [$type, $id] = $this->inferEntity();

        CommunicationLog::create(array_filter([
            'entity_type'   => $type,
            'entity_id'     => $id,
            'channel'       => $channel,
            'direction'     => 'outbound',
            'template'      => $data['template'] ?? ($channel === 'email' ? 'email_generic' : null),
            'to_phone'      => $data['to_phone'] ?? null,
            'to_email'      => $data['to_email'] ?? null,
            'subject'       => $data['subject'] ?? null,
            'body'          => is_array($data['body'] ?? null) ? json_encode($data['body']) : ($data['body'] ?? null),
            'provider_sid'  => $data['provider_sid'] ?? null,
            'meta'          => $data['meta'] ?? null,
        ]));
    }

    protected function inferEntity(): array
    {
        $e = $this->event;

        foreach (['lead' => 'App\Models\Lead\Lead',
                  'opportunity' => 'App\Models\Opportunity\Opportunity',
                  'booking' => 'App\Models\Booking\Booking',
                  'job' => 'App\Models\Job\Job'] as $prop => $fqcn) {
            if (property_exists($e, $prop) && $e->{$prop}) {
                return [$fqcn, $e->{$prop}->id ?? null];
            }
        }

        // default unknown
        return [null, null];
    }
}
