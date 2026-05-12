<?php

namespace App\Jobs;

use App\Mail\GenericNotification;
use App\Models\Shared\CommunicationLog;
use App\Services\TwilioWhatsApp;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public object $event)
    {
        /*
        |--------------------------------------------------------------------------
        | Queue Assignment
        |--------------------------------------------------------------------------
        | Do NOT declare public string $queue here.
        | The Queueable trait already has a $queue property.
        | Use onQueue() instead to avoid PHP fatal error.
        */

        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function handle(TwilioWhatsApp $wa): void
    {
        $map = (array) config('notify', []);
        $eventClass = $this->event::class;

        if (! isset($map[$eventClass])) {
            Log::warning('notify.mapping.missing', [
                'event' => $eventClass,
            ]);

            return;
        }

        $cfg = (array) $map[$eventClass];

        /*
        |--------------------------------------------------------------------------
        | Helper to evaluate closures with the event or return raw value
        |--------------------------------------------------------------------------
        */

        $eval = function ($val, $default = null) {
            if ($val instanceof Closure) {
                try {
                    return $val($this->event);
                } catch (Throwable $e) {
                    Log::error('notify.eval.failed', [
                        'err' => $e->getMessage(),
                    ]);

                    return $default;
                }
            }

            return $val ?? $default;
        };

        /*
        |--------------------------------------------------------------------------
        | Resolve recipients and content
        |--------------------------------------------------------------------------
        */

        $to = (array) $eval(Arr::get($cfg, 'to'), [
            'phone' => null,
            'email' => null,
        ]);

        $channels = array_map(
            fn ($c) => strtolower((string) $c),
            (array) $eval(Arr::get($cfg, 'channels'), ['whatsapp', 'email'])
        );

        $waTemplate = $eval(Arr::get($cfg, 'template'));
        $placeholders = (array) $eval(Arr::get($cfg, 'placeholders'), []);
        $subject = (string) $eval(Arr::get($cfg, 'subject'), 'Notification');
        $body = (string) $eval(Arr::get($cfg, 'body'), '');
        $cta = $eval(Arr::get($cfg, 'cta'));

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        if (in_array('email', $channels, true) && ! empty($to['email'])) {
            try {
                Mail::to($to['email'])->send(
                    new GenericNotification($subject, $body, $cta)
                );

                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject' => $subject,
                    'body' => $this->truncateBody($body),
                ]);
            } catch (Throwable $e) {
                Log::error('notify.email.failed', [
                    'err' => $e->getMessage(),
                ]);

                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject' => $subject,
                    'body' => $this->truncateBody($body),
                    'meta' => [
                        'error' => $e->getMessage(),
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | WhatsApp - Twilio Legacy Notification Path
        |--------------------------------------------------------------------------
        | This is only for the old unified notification system.
        | Main SayaraForce WhatsApp journey now uses Meta path separately.
        |--------------------------------------------------------------------------
        */

        if (in_array('whatsapp', $channels, true) && ! empty($to['phone']) && $waTemplate) {
            $e164 = $this->normalizeE164($to['phone']);

            if (! $e164) {
                Log::warning('notify.wa.invalid_phone', [
                    'raw' => $to['phone'],
                ]);

                return;
            }

            $twilioRecipient = 'whatsapp:' . $e164;

            try {
                $res = (array) $wa->sendTemplate(
                    $twilioRecipient,
                    $waTemplate,
                    $placeholders
                );

                $ok = (bool) Arr::get($res, 'ok', false);

                $this->logComm('whatsapp', [
                    'to_phone' => $e164,
                    'template' => $waTemplate,
                    'body' => $placeholders,
                    'provider_sid' => Arr::get($res, 'sid'),
                    'meta' => $ok ? null : [
                        'error' => Arr::get($res, 'error', 'unknown'),
                    ],
                ]);
            } catch (Throwable $e) {
                Log::error('notify.wa.failed', [
                    'err' => $e->getMessage(),
                ]);

                $this->logComm('whatsapp', [
                    'to_phone' => $e164,
                    'template' => $waTemplate,
                    'meta' => [
                        'error' => $e->getMessage(),
                    ],
                ]);
            }
        }
    }

    protected function logComm(string $channel, array $data = []): void
    {
        try {
            [$entityType, $entityId] = $this->inferEntity();

            $meta = $data['meta'] ?? null;

            if (is_string($meta)) {
                $meta = [
                    'message' => Str::limit($meta, 500),
                ];
            } elseif (is_array($meta)) {
                $meta = $this->shallowLimitArray($meta, 25, 500);
            } elseif ($meta !== null) {
                $meta = [
                    'value' => (string) $meta,
                ];
            }

            $body = $data['body'] ?? null;

            if (is_array($body)) {
                $body = json_encode($this->shallowLimitArray($body, 25, 300));
            } elseif (is_string($body)) {
                $body = $this->truncateBody($body);
            }

            CommunicationLog::create(array_filter([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'channel' => $channel,
                'direction' => 'outbound',
                'template' => $data['template'] ?? ($channel === 'email' ? 'email_generic' : null),
                'to_phone' => $data['to_phone'] ?? null,
                'to_email' => $data['to_email'] ?? null,
                'subject' => $data['subject'] ?? null,
                'body' => $body,
                'provider_sid' => $data['provider_sid'] ?? null,
                'meta' => $meta ? json_encode($meta) : null,
            ], fn ($value) => $value !== null));
        } catch (Throwable $e) {
            Log::warning('notify.communication_log.failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function inferEntity(): array
    {
        $event = $this->event;

        foreach (['lead', 'opportunity', 'booking', 'job'] as $property) {
            if (property_exists($event, $property) && $event->{$property}) {
                $model = $event->{$property};
                $id = $model->id ?? null;
                $type = is_object($model) ? get_class($model) : null;

                return [$type, $id];
            }
        }

        return [null, null];
    }

    protected function normalizeE164(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = preg_replace('#^whatsapp:\s*#i', '', trim($phone));
        $phone = preg_replace('/[^+\d]/', '', $phone ?? '');

        if (! Str::startsWith($phone, '+')) {
            if (preg_match('/^\d+$/', $phone ?? '')) {
                $phone = '+' . $phone;
            }
        }

        if (! preg_match('/^\+\d{8,20}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    protected function truncateBody(string $body, int $limit = 1000): string
    {
        return Str::limit($body, $limit, ' …');
    }

    protected function shallowLimitArray(array $arr, int $maxItems = 25, int $stringLimit = 500): array
    {
        $out = [];
        $i = 0;

        foreach ($arr as $key => $value) {
            if ($i++ >= $maxItems) {
                $out['__truncated__'] = true;
                break;
            }

            if (is_scalar($value) || $value === null) {
                $out[$key] = is_string($value)
                    ? Str::limit($value, $stringLimit)
                    : $value;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = array_map(function ($nestedValue) use ($stringLimit) {
                    if (is_scalar($nestedValue) || $nestedValue === null) {
                        return is_string($nestedValue)
                            ? Str::limit($nestedValue, $stringLimit)
                            : $nestedValue;
                    }

                    return is_array($nestedValue)
                        ? ['__array__' => 'nested']
                        : ['__type__' => gettype($nestedValue)];
                }, array_slice($value, 0, 10, true));

                if (count($value) > 10) {
                    $out[$key]['__truncated__'] = true;
                }

                continue;
            }

            $out[$key] = [
                '__type__' => gettype($value),
            ];
        }

        return $out;
    }
}