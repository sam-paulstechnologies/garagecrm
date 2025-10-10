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

    public string $queue = 'notifications';
    public int    $tries = 3;
    public int    $timeout = 120;

    public function __construct(public object $event) {}

    public function handle(TwilioWhatsApp $wa): void
    {
        $map = (array) config('notify', []);
        $eventClass = $this->event::class;

        if (!isset($map[$eventClass])) {
            Log::warning('notify.mapping.missing', ['event' => $eventClass]);
            return;
        }

        $cfg = (array) $map[$eventClass];

        // Helper to evaluate closures with the event or return raw value
        $eval = function ($val, $default = null) {
            if ($val instanceof Closure) {
                try {
                    return $val($this->event);
                } catch (Throwable $e) {
                    Log::error('notify.eval.failed', ['err' => $e->getMessage()]);
                    return $default;
                }
            }
            return $val ?? $default;
        };

        // Resolve recipients & content
        $to = (array) $eval(Arr::get($cfg, 'to'), ['phone' => null, 'email' => null]);
        $channels = array_map(
            fn ($c) => strtolower((string) $c),
            (array) $eval(Arr::get($cfg, 'channels'), ['whatsapp', 'email'])
        );

        $waTemplate   = $eval(Arr::get($cfg, 'template'));
        $placeholders = (array) $eval(Arr::get($cfg, 'placeholders'), []);
        $subject      = (string) $eval(Arr::get($cfg, 'subject'), 'Notification');
        $body         = (string) $eval(Arr::get($cfg, 'body'), '');
        $cta          = $eval(Arr::get($cfg, 'cta')); // ['label' => 'View', 'url' => '...']

        // EMAIL
        if (in_array('email', $channels, true) && !empty($to['email'])) {
            try {
                Mail::to($to['email'])->send(new GenericNotification($subject, $body, $cta));

                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject'  => $subject,
                    'body'     => $this->truncateBody($body),
                ]);
            } catch (Throwable $e) {
                Log::error('notify.email.failed', ['err' => $e->getMessage()]);

                $this->logComm('email', [
                    'to_email' => $to['email'],
                    'subject'  => $subject,
                    'body'     => $this->truncateBody($body),
                    'meta'     => ['error' => $e->getMessage()],
                ]);
            }
        }

        // WHATSAPP (Twilio)
        if (in_array('whatsapp', $channels, true) && !empty($to['phone']) && $waTemplate) {
            // Clean DB value to E.164 (no prefix); Twilio wants "whatsapp:+E164"
            $e164 = $this->normalizeE164($to['phone']);
            if (!$e164) {
                Log::warning('notify.wa.invalid_phone', ['raw' => $to['phone']]);
            } else {
                $twilioRecipient = 'whatsapp:' . $e164;

                try {
                    $res = (array) $wa->sendTemplate($twilioRecipient, $waTemplate, $placeholders);
                    $ok  = (bool) Arr::get($res, 'ok', false);

                    $this->logComm('whatsapp', [
                        'to_phone'     => $e164,                 // store clean value
                        'template'     => $waTemplate,
                        'body'         => $placeholders,         // store args as JSON
                        'provider_sid' => Arr::get($res, 'sid'),
                        'meta'         => $ok ? null : ['error' => Arr::get($res, 'error', 'unknown')],
                    ]);
                } catch (Throwable $e) {
                    Log::error('notify.wa.failed', ['err' => $e->getMessage()]);
                    $this->logComm('whatsapp', [
                        'to_phone' => $e164,
                        'template' => $waTemplate,
                        'meta'     => ['error' => $e->getMessage()],
                    ]);
                }
            }
        }
    }

    /**
     * Persist a communication log (defensive about lengths/types).
     */
    protected function logComm(string $channel, array $data = []): void
    {
        [$entityType, $entityId] = $this->inferEntity();

        // Normalize meta to array; truncate giant arrays/strings
        $meta = $data['meta'] ?? null;
        if (is_string($meta)) {
            $meta = ['message' => Str::limit($meta, 500)];
        } elseif (is_array($meta)) {
            // Trim deeply if needed
            $meta = $this->shallowLimitArray($meta, 25, 500);
        } elseif ($meta !== null) {
            $meta = ['value' => (string) $meta];
        }

        $body = $data['body'] ?? null;
        if (is_array($body)) {
            // keep small, stringify
            $body = json_encode($this->shallowLimitArray($body, 25, 300));
        } elseif (is_string($body)) {
            $body = $this->truncateBody($body);
        }

        CommunicationLog::create(array_filter([
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'channel'       => $channel,
            'direction'     => 'outbound',
            'template'      => $data['template'] ?? ($channel === 'email' ? 'email_generic' : null),
            'to_phone'      => $data['to_phone'] ?? null,   // E.164 only (e.g., +9715…)
            'to_email'      => $data['to_email'] ?? null,
            'subject'       => $data['subject'] ?? null,
            'body'          => $body,
            'provider_sid'  => $data['provider_sid'] ?? null,
            'meta'          => $meta ? json_encode($meta) : null,
        ]));
    }

    /**
     * Try to infer entity_type/entity_id from common event properties.
     * Saves the actual model class for safer morph use.
     */
    protected function inferEntity(): array
    {
        $e = $this->event;

        foreach (['lead', 'opportunity', 'booking', 'job'] as $prop) {
            if (property_exists($e, $prop) && $e->{$prop}) {
                $model = $e->{$prop};
                $id    = $model->id ?? null;
                $type  = is_object($model) ? get_class($model) : null;
                return [$type, $id];
            }
        }

        return [null, null];
    }

    /**
     * Strip "whatsapp:" if present; return +E164 or null.
     */
    protected function normalizeE164(?string $phone): ?string
    {
        if (!$phone) return null;

        // Remove leading "whatsapp:" if user accidentally saved it
        $phone = preg_replace('#^whatsapp:\s*#i', '', trim($phone));

        // Keep + and digits only
        $phone = preg_replace('/[^+\d]/', '', $phone ?? '');

        // Must start with + and have at least country code + a few digits
        if (!Str::startsWith($phone, '+')) {
            // If it starts with country code without +, we can optionally add +.
            // Safer to require + from upstream, but we’ll add it if everything is digits.
            if (preg_match('/^\d+$/', $phone ?? '')) {
                $phone = '+' . $phone;
            }
        }

        // Basic sanity: + and 8–20 digits total length
        if (!preg_match('/^\+\d{8,20}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    protected function truncateBody(string $body, int $limit = 1000): string
    {
        return Str::limit($body, $limit, ' …');
    }

    /**
     * Limit array depth/size for safe logging.
     */
    protected function shallowLimitArray(array $arr, int $maxItems = 25, int $stringLimit = 500): array
    {
        $out = [];
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($i++ >= $maxItems) {
                $out['__truncated__'] = true;
                break;
            }
            if (is_scalar($v) || $v === null) {
                $out[$k] = is_string($v) ? Str::limit($v, $stringLimit) : $v;
            } elseif (is_array($v)) {
                // one level shallow
                $out[$k] = array_map(function ($vv) use ($stringLimit) {
                    if (is_scalar($vv) || $vv === null) {
                        return is_string($vv) ? Str::limit($vv, $stringLimit) : $vv;
                    }
                    return is_array($vv) ? ['__array__' => 'nested'] : ['__type__' => gettype($vv)];
                }, array_slice($v, 0, 10, true));
                if (count($v) > 10) {
                    $out[$k]['__truncated__'] = true;
                }
            } else {
                $out[$k] = ['__type__' => gettype($v)];
            }
        }
        return $out;
    }
}
