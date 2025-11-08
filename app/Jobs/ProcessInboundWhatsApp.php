<?php

namespace App\Jobs;

use App\Models\Client\Lead;
use App\Models\MessageLog;
use App\Notifications\ManagerLeadHandoffNotification;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\Ai\NlpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Jobs\SendWhatsAppFromTemplate;

/**
 * Inbound WhatsApp processor:
 * - Logs inbound + AI analysis (linked to a conversation)
 * - Confidence gate → manager handoff or state machine
 * - Optional AI-first reply
 * - Template fallbacks via SendWhatsAppFromTemplate job
 * - Uses company_settings.ai.policy_reply on handoff when present
 * - Manager alerts use business.manager_phone (fallback to whatsapp.manager_number)
 */
class ProcessInboundWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Retry policy */
    public $tries   = 3;
    public $backoff = [5, 20, 60];

    public function __construct(
        public string  $from,
        public string  $to,
        public string  $body,
        public ?string $sid = null,
        public int     $numMedia = 0,
        public ?string $profileName = null,
        public string  $provider = 'twilio',
        public array   $payload = [],
        public ?int    $companyId = null,
        public ?int    $leadId = null
    ) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $fromE164 = preg_replace('/^whatsapp:/', '', (string) $this->from);
        $digits   = preg_replace('/\D+/', '', $fromE164);
        $text     = trim((string) $this->body);

        // ---------- (A) AI understanding + propensity score ----------
        $nlp = null;
        $propensity = null;
        $propensityReason = null;

        try {
            /** @var NlpService $ai */
            $ai  = app(NlpService::class);
            $nlp = $ai->analyze($text, [
                'lead' => [
                    'name'        => $this->profileName,
                    'phone'       => $fromE164,
                    'last_intent' => null,
                ],
            ]);

            [$propensity, $propensityReason] = $this->computePropensity($nlp, $this->leadId, $this->companyId);
        } catch (\Throwable $e) {
            Log::warning('[WA][AI] analysis failed: '.$e->getMessage(), ['sid' => $this->sid]);
            $nlp = [
                'intent'     => 'fallback',
                'sentiment'  => 'neutral',
                'confidence' => 0.0,
                'language'   => 'en',
                'entities'   => [],
            ];
            [$propensity, $propensityReason] = [null, null];
        }

        // ---------- Runtime switches ----------
        $companyId = (int)($this->companyId ?? 1);
        $aiFirstFromDb = $this->getCompanySetting($companyId, 'ai.first_reply');
        $aiFirst = is_null($aiFirstFromDb)
            ? filter_var(env('AI_FIRST_REPLY', false), FILTER_VALIDATE_BOOLEAN)
            : filter_var($aiFirstFromDb, FILTER_VALIDATE_BOOLEAN);

        $thresholdFromDb = $this->getCompanySetting($companyId, 'ai.confidence_threshold');
        $threshold = is_null($thresholdFromDb)
            ? (float) config('ai.confidence_threshold', (float) env('AI_CONFIDENCE_THRESHOLD', 0.60))
            : (float) $thresholdFromDb;

        // NOTE: aligned to policy_reply (not policy_text)
        $policyText = trim((string) ($this->getCompanySetting($companyId, 'ai.policy_reply') ?? ''));
        $hasOpenAI = (bool) config('services.openai.api_key');

        Log::info('[AI][Gate]', [
            'aiFirst'   => $aiFirst,
            'hasOpenAI' => $hasOpenAI,
            'intent'    => $nlp['intent'] ?? null,
            'conf'      => $nlp['confidence'] ?? null,
            'threshold' => $threshold,
            'policy'    => $policyText ? 'yes' : 'no',
        ]);

        // ---------- (C) Resolve lead (earlier, so we can thread the conversation) ----------
        $lead = null;
        if ($this->leadId) {
            $lead = Lead::find($this->leadId);
        }
        if (!$lead && $digits !== '') {
            $q = Lead::query()->latest('id');
            if ($companyId) $q->where('company_id', $companyId);
            $lead = $q->where(function ($qq) use ($digits) {
                $qq->where('phone_norm', $digits)
                   ->orWhere('phone', 'like', "%{$digits}%");
            })->first();
        }

        // Create/reuse a conversation before we persist inbound
        $conversationId = $this->resolveConversation($companyId, $lead);

        // ---------- (B) Persist inbound with AI fields (now includes conversation_id) ----------
        try {
            MessageLog::in([
                'company_id'           => $companyId,
                'lead_id'              => $lead?->id ?? $this->leadId,
                'conversation_id'      => $conversationId,
                'channel'              => 'whatsapp',
                'to_number'            => $this->to,
                'from_number'          => $fromE164,
                'template'             => null,
                'body'                 => $text,
                'provider_message_id'  => $this->sid,
                'provider_status'      => 'received',
                'meta'                 => $this->payload ?: [],

                'ai_analysis'          => $nlp,
                'ai_propensity_score'  => $propensity,
                'ai_propensity_reason' => $propensityReason,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] inbound log insert failed', ['sid' => $this->sid, 'err' => $e->getMessage()]);
        }

        if (!$lead || $text === '') {
            // we still logged the message above; nothing further to do
            return;
        }

        // Ensure base conversation_data structure
        $currentCd = $lead->conversation_data;
        if (!is_array($currentCd)) $currentCd = [];
        $lead->conversation_data = array_merge(['history' => []], $currentCd);

        $leadId = (int) $lead->id;

        // ========== LOW CONFIDENCE EARLY HANDOFF ==========
        $conf = (float) ($nlp['confidence'] ?? 0.0);

        if ($conf < $threshold) {
            // If policy_reply set → send that instead of the handoff template
            if ($policyText !== '') {
                try {
                    /** @var WhatsAppService $wa */
                    $wa = app(WhatsAppService::class);
                    $wa->sendText(
                        toE164: $fromE164,
                        body:   $policyText,
                        context: ['company_id' => $companyId, 'lead_id' => $leadId],
                    );
                    $this->logOutboundPlain($companyId, $leadId, $conversationId, $fromE164, $policyText, [
                        'action' => 'manager_handoff',
                        'kind'   => 'policy_text'
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[AI][PolicyReply] send failed, fallback to template', ['err' => $e->getMessage()]);
                    $this->sendTpl($companyId, $leadId, $fromE164, 'visit_handoff_v1', [], action: 'manager_handoff');
                }
            } else {
                $this->sendTpl($companyId, $leadId, $fromE164, 'visit_handoff_v1', [], action: 'manager_handoff');
            }

            $this->notifyManager($lead, $text);
            Log::info('[AI] Low confidence handoff', ['lead_id' => $leadId, 'conf' => $conf, 'th' => $threshold]);
            return;
        }

        // ---------- (D) Update make/model on Lead/Opportunity ----------
        [$makeId, $modelId, $otherMake, $otherModel] = $this->resolveMakeModel($text);

        $changed = false;
        $lead->fill([
            'vehicle_make_id'  => $makeId    ?: $lead->vehicle_make_id,
            'vehicle_model_id' => $modelId   ?: $lead->vehicle_model_id,
            'other_make'       => $otherMake  ?: $lead->other_make,
            'other_model'      => $otherModel ?: $lead->other_model,
        ]);
        if ($lead->isDirty()) {
            $lead->save();
            $changed = true;
        }

        $op = $lead->opportunity()->first();
        if ($op) {
            $op->fill([
                'vehicle_make_id'  => $makeId  ?: $op->vehicle_make_id,
                'vehicle_model_id' => $modelId ?: $op->vehicle_model_id,
                'other_make'       => $otherMake  ?: $op->other_make,
                'other_model'      => $otherModel ?: $op->other_model,
            ]);
            if ($op->isDirty()) {
                $op->save();
            }
        }

        // ---------- (E) AI First reply (optional) ----------
        $sentOk = false;

        if ($aiFirst && $hasOpenAI) {
            try {
                /** @var NlpService $ai */
                $ai = app(NlpService::class);
                $replyText = $ai->replyText(
                    from: $fromE164,
                    to:   $this->to,
                    body: $text,
                    extra: [
                        'lead' => [
                            'name'               => $lead->name,
                            'vehicle_make_id'    => $lead->vehicle_make_id,
                            'vehicle_model_id'   => $lead->vehicle_model_id,
                            'other_make'         => $lead->other_make,
                            'other_model'        => $lead->other_model,
                            'conversation_state' => $lead->conversation_state,
                        ],
                        'nlp'  => $nlp,
                    ]
                );

                /** @var WhatsAppService $wa */
                $wa = app(WhatsAppService::class);
                $wa->sendText(
                    toE164: $fromE164,
                    body:   $replyText,
                    context: ['company_id' => (int)$companyId, 'lead_id' => (int)$lead->id],
                );

                Log::info('[AI][WA] AI-first reply sent', ['to' => $fromE164]);
                $sentOk = true;
            } catch (\Throwable $e) {
                Log::error('[AI][WA] AI-first failed, falling back to template', ['err' => $e->getMessage()]);
            }
        }

        // ---------- (E2) Conversation state machine + intent routing ----------
        $state  = $this->normalizeState($lead->conversation_state);
        $intent = $this->resolveIntent($nlp, $text);

        if (!$sentOk) {
            if ($state === 'awaiting_vehicle') {
                if ($makeId || $otherMake || $modelId || $otherModel) {
                    $this->sendTpl($companyId, $leadId, $fromE164, 'ask_preferred_time_v1', [
                        $lead->name ?: 'there',
                    ], action: 'collect_timeslot');
                    $this->updateState($lead, 'awaiting_timeslot');
                } else {
                    $this->sendTpl($companyId, $leadId, $fromE164, 'ask_make_model_v1', action: 'collect_vehicle');
                }
            } elseif ($state === 'awaiting_timeslot') {
                [$dt, $slotLabel] = $this->parsePreferredDateTime($text);
                if ($dt) {
                    $booking = $this->createPendingBooking($lead, $dt, $slotLabel);
                    $this->sendTpl(
                        $companyId, $leadId, $fromE164, 'booking_confirmed_v1',
                        [
                            $booking->reference ?? ('BK-' . $booking->id),
                            $dt->format('D, d M Y'),
                            $dt->format('H:i'),
                        ],
                        action: 'confirmed'
                    );
                    $this->updateState($lead, 'idle');
                } else {
                    $this->sendTpl($companyId, $leadId, $fromE164, 'ask_preferred_time_v1', [
                        $lead->name ?: 'there',
                    ], action: 'collect_timeslot');
                }
            } else {
                if (in_array($intent, ['booking', 'reschedule'], true)) {
                    $needsMake  = empty($lead->vehicle_make_id)  && empty($lead->other_make);
                    $needsModel = empty($lead->vehicle_model_id) && empty($lead->other_model);

                    if ($needsMake || $needsModel) {
                        $this->sendTpl($companyId, $leadId, $fromE164, 'ask_make_model_v1', action: 'collect_vehicle');
                        $this->updateState($lead, 'awaiting_vehicle');
                    } else {
                        $this->sendTpl($companyId, $leadId, $fromE164, 'ask_preferred_time_v1', [
                            $lead->name ?: 'there',
                        ], action: 'collect_timeslot');
                        $this->updateState($lead, 'awaiting_timeslot');
                    }
                } else {
                    $this->sendTpl($companyId, $leadId, $fromE164, 'lead_acknowledgment_v2', action: 'initial');
                }
            }
        }

        // ---------- (F) Notify manager (lightweight FYI) ----------
        if ($changed || mb_strlen($text) >= 2) {
            $this->notifyManager($lead, $text);
        }
    }

    /** Fetch a company setting value (string|null). */
    protected function getCompanySetting(int $companyId, string $key): ?string
    {
        try {
            $val = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');

            $val = is_string($val) ? trim($val) : null;
            return $val !== '' ? $val : null;
        } catch (\Throwable $e) {
            Log::debug('[WA] getCompanySetting failed', ['key' => $key, 'company_id' => $companyId, 'err' => $e->getMessage()]);
            return null;
        }
    }

    private function notifyManager(Lead $lead, string $reasonText): void
    {
        try {
            if ($lead->assignee && method_exists($lead->assignee, 'notify')) {
                $lead->assignee->notify(new ManagerLeadHandoffNotification(
                    companyId: $lead->company_id,
                    leadId:    $lead->id,
                    name:      $lead->name ?? 'Lead',
                    phone:     $lead->phone ?? 'N/A',
                    source:    $lead->source ?? '-',
                    reason:    mb_strimwidth($reasonText, 0, 140, '…')
                ));
            }

            /** @var WhatsAppService $wa */
            $wa = app(WhatsAppService::class);
            $manager = $this->resolveManagerNumber($lead->company_id);

            if ($manager) {
                $wa->sendTemplate(
                    toE164:       $manager,
                    templateName: 'manager_call_lead',
                    params: [
                        $lead->name ?? 'Lead',
                        $lead->phone ?? 'N/A',
                        $lead->source ?? '-',
                        'Inbound reply: ' . mb_strimwidth($reasonText, 0, 80, '…'),
                    ],
                    links:   [],
                    context: ['company_id' => $lead->company_id, 'lead_id' => $lead->id]
                );
            } else {
                Log::warning('[WA] No manager phone configured', ['company_id' => $lead->company_id]);
            }
        } catch (\Throwable $e) {
            Log::error('[WA][ManagerHandOff] '.$e->getMessage(), ['lead_id' => $lead->id]);
        }
    }

    /** Prefer business.manager_phone; fallback to whatsapp.manager_number for backward compatibility. */
    private function resolveManagerNumber(int $companyId): ?string
    {
        $n = $this->getCompanySetting($companyId, 'business.manager_phone');
        if (!$n) $n = $this->getCompanySetting($companyId, 'whatsapp.manager_number');
        return $n;
    }

    /** Log a plain text outbound when we bypass templates (policy_reply). */
    private function logOutboundPlain(int $companyId, int $leadId, ?int $conversationId, string $toE164, string $text, array $meta = []): void
    {
        try {
            if (method_exists(MessageLog::class, 'out')) {
                MessageLog::out([
                    'company_id'      => $companyId,
                    'lead_id'         => $leadId,
                    'conversation_id' => $conversationId,
                    'channel'         => 'whatsapp',
                    'to_number'       => $toE164,
                    'from_number'     => $this->to,
                    'template'        => null,
                    'body'            => $text,
                    'provider_status' => 'sent',
                    'meta'            => $meta,
                ]);
                return;
            }

            MessageLog::create([
                'company_id'      => $companyId,
                'lead_id'         => $leadId,
                'conversation_id' => $conversationId,
                'direction'       => 'out',
                'channel'         => 'whatsapp',
                'to_number'       => $toE164,
                'from_number'     => $this->to,
                'template'        => null,
                'body'            => $text,
                'provider_status' => 'sent',
                'meta'            => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[WA] outbound plain log failed: '.$e->getMessage());
        }
    }

    /** Lightweight resolver using your make/model tables. */
    private function resolveMakeModel(string $text): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text));
        if ($clean === '') return [null, null, null, null];

        $makeHit = \App\Models\Vehicle\VehicleMake::query()
            ->select('id', 'name')
            ->get()
            ->first(fn($m) => stripos($clean, $m->name) !== false);

        if ($makeHit) {
            $modelHit = \App\Models\Vehicle\VehicleModel::query()
                ->where('vehicle_make_id', $makeHit->id)
                ->select('id', 'name')
                ->get()
                ->first(fn($mm) => stripos($clean, $mm->name) !== false);

            if ($modelHit) return [$makeHit->id, $modelHit->id, null, null];

            $rest = trim(str_ireplace($makeHit->name, '', $clean));
            $tokens = preg_split('/[ ,\/\-]+/', $rest) ?: [];
            $otherModel = trim(implode(' ', array_slice($tokens, 0, 2))) ?: null;

            return [$makeHit->id, null, null, $otherModel];
        }

        $tokens = preg_split('/[ ,\/\-]+/', $clean) ?: [];
        $otherMake  = $tokens[0] ?? null;
        $otherModel = $tokens[1] ?? null;

        return [null, null, $otherMake, $otherModel];
    }

    /**
     * Compute a 0–100 likelihood the customer will book service soon.
     */
    protected function computePropensity(array $nlp, ?int $leadId, ?int $companyId): array
    {
        $hadBooking = false;
        $stage      = 'New';
        $source     = '';
        $eng        = 0.0;

        try {
            if ($leadId) {
                /** @var \App\Models\Client\Lead|null $lead */
                $lead = \App\Models\Client\Lead::find($leadId);

                if ($lead) {
                    $stage  = (string) ($lead->status ?? 'New');
                    $source = strtolower((string) ($lead->source ?? ''));

                    $in7  = MessageLog::where('lead_id', $leadId)
                        ->where('direction', 'in')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count();
                    $out7 = MessageLog::where('lead_id', $leadId)
                        ->where('direction', 'out')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->count();
                    $in7  = min(5, (int) $in7);
                    $out7 = min(5, (int) $out7);
                    $eng  = min(1.0, ($in7 * 0.08 + $out7 * 0.04));

                    $hadBooking = \App\Models\Job\Booking::where('lead_id', $leadId)
                        ->whereIn('status', ['Pending', 'Confirmed', 'Rescheduled'])
                        ->exists();
                }
            }
        } catch (\Throwable $e) {
            Log::debug('[WA][Propensity] context pull failed: '.$e->getMessage());
        }

        $intent = $nlp['intent'] ?? 'fallback';
        $e      = $nlp['entities'] ?? [];
        $sent   = $nlp['sentiment'] ?? 'neutral';
        $conf   = (float) ($nlp['confidence'] ?? 0);

        $need = ['vehicle_make', 'vehicle_model'];
        $have = 0; foreach ($need as $k) if (!empty($e[$k])) $have++;
        $entityOK = $have / max(1, count($need)); // 0..1

        $respSpeedMin = isset($this->payload['response_time_min'])
            ? (int) $this->payload['response_time_min']
            : null;

        $speedBoost = $respSpeedMin !== null
            ? ($respSpeedMin <= 10 ? 1.0 : ($respSpeedMin <= 60 ? 0.7 : 0.4))
            : 0.6;

        $sourceWeight = match (true) {
            str_contains($source, 'google') || str_contains($source, 'ads')         => 0.9,
            str_contains($source, 'website') || str_contains($source, 'webchat')    => 0.8,
            str_contains($source, 'instagram') || str_contains($source, 'facebook') => 0.7,
            str_contains($source, 'partner') || str_contains($source, 'referral')   => 0.85,
            default => 0.6,
        };

        $intentW = match ($intent) {
            'booking', 'reschedule'                      => 1.0,
            'vehicle_info', 'price_quote'                => 0.75,
            'greeting', 'thank_you', 'general_question'  => 0.55,
            'complaint'                                   => 0.45,
            default                                       => 0.4,
        };

        $sentBump  = $sent === 'positive' ? 0.08 : ($sent === 'negative' ? -0.08 : 0.0);
        $stageBump = match ($stage) {
            'Appointment' => 0.12,
            'Qualified'   => 0.08,
            'Attempting Contact' => 0.04,
            default       => 0.00,
        };
        $bookingBump = $hadBooking ? 0.10 : 0.00;

        $raw = (0.30 * $conf)
             + (0.20 * $entityOK)
             + (0.15 * $intentW)
             + (0.10 * $sourceWeight)
             + (0.10 * $speedBoost)
             + (0.07 * $eng)
             + $sentBump
             + $stageBump
             + $bookingBump;

        $score = (int) max(0, min(100, round($raw * 100)));

        $bits = [];
        if (in_array($intent, ['booking', 'reschedule'])) $bits[] = 'clear booking intent';
        if ($entityOK >= 0.99) $bits[] = 'vehicle details complete';
        if ($respSpeedMin !== null && $respSpeedMin <= 10) $bits[] = 'fast reply';
        if ($hadBooking) $bits[] = 'open booking exists';
        if ($sent === 'positive') $bits[] = 'positive tone';
        if ($stageBump > 0) $bits[] = "stage {$stage}";

        if ($bits === []) $bits[] = 'low signal';

        return [$score, implode(', ', $bits)];
    }

    private function resolveIntent(array $nlp, string $text): string
    {
        $intent = strtolower((string)($nlp['intent'] ?? ''));
        if ($intent) return $intent;

        $t = mb_strtolower($text);
        if (preg_match('/\b(service|servicing|maintenance|oil change|booking|book|appointment|schedule|reschedule)\b/i', $t)) {
            return 'booking';
        }
        if (preg_match('/\b(price|cost|quote|how much)\b/i', $t)) {
            return 'price_quote';
        }
        if (preg_match('/\b(hi|hello|hey|good\s*(morning|evening|afternoon))\b/i', $t)) {
            return 'greeting';
        }
        return 'general_question';
    }

    private function normalizeState(?string $state): string
    {
        $s = strtolower((string) $state);
        return in_array($s, ['awaiting_vehicle','awaiting_timeslot','idle'], true) ? $s : 'idle';
    }

    private function updateState(Lead $lead, string $state): void
    {
        $cd = $lead->conversation_data;
        if (!is_array($cd)) $cd = [];
        $lead->conversation_state = $state;
        $lead->conversation_data  = array_merge($cd, [
            'last_state_at' => now()->toIso8601String(),
        ]);
        $lead->save();
    }

    private function parsePreferredDateTime(string $text): array
    {
        $t = mb_strtolower($text);

        $slot = null;
        if (preg_match('/\b(morning|am|8am|9am|10am|11am)\b/i', $t)) $slot = 'Morning';
        if (preg_match('/\b(afternoon|pm|2pm|3pm|4pm|5pm|6pm)\b/i', $t)) $slot = 'Afternoon';

        if (preg_match('/\btomorrow\b/i', $t)) {
            $base = now()->addDay()->startOfDay();
        } elseif (preg_match('/\btoday\b/i', $t)) {
            $base = now()->startOfDay();
        } elseif (preg_match('/\b(mon|tue|wed|thu|fri|sat|sun)(day)?\b/i', $t, $m)) {
            $base = Carbon::parse('next ' . $m[0])->startOfDay();
        } else {
            try {
                $parsed = Carbon::parse($text);
                $base = $parsed->copy()->startOfDay();
                if (!$slot && (int)$parsed->format('H') >= 12) $slot = 'Afternoon';
                if (!$slot && (int)$parsed->format('H') > 0 && (int)$parsed->format('H') < 12) $slot = 'Morning';
            } catch (\Throwable $e) {
                $base = null;
            }
        }

        if (!$base) return [null, ''];

        $time = $slot === 'Afternoon' ? '15:00' : '10:00';
        if (preg_match('/\b([01]?\d|2[0-3]):?([0-5]\d)?\s*(am|pm)?\b/i', $t, $tm)) {
            try {
                $candidate = Carbon::parse($tm[0]);
                $time = $candidate->format('H:i');
                if (!$slot) $slot = ((int)$candidate->format('H') >= 12) ? 'Afternoon' : 'Morning';
            } catch (\Throwable $e) {}
        }

        $dt = Carbon::parse($base->format('Y-m-d') . ' ' . $time);
        if (!$slot) {
            $slot = (intval($dt->format('H')) >= 12) ? 'Afternoon' : 'Morning';
        }

        return [$dt, $slot];
    }

    private function createPendingBooking(Lead $lead, Carbon $dt, string $slotLabel)
    {
        $booking = \App\Models\Job\Booking::create([
            'company_id'   => $lead->company_id,
            'lead_id'      => $lead->id,
            'client_id'    => $lead->client_id ?? null,
            'scheduled_at' => $dt,
            'slot'         => $slotLabel,
            'status'       => 'Pending',
            'notes'        => 'Auto-created from WhatsApp',
        ]);

        return $booking;
    }

    private function sendTpl(
        int $companyId,
        int $leadId,
        string $toE164,
        string $template,
        array $placeholders = [],
        array $links = [],
        array $context = [],
        string $action = 'initial'
    ): void {
        SendWhatsAppFromTemplate::dispatch(
            companyId:    $companyId,
            leadId:       $leadId,
            toNumberE164: $toE164,
            templateName: $template,
            placeholders: $placeholders,
            links:        $links,
            context:      $context + ['company_id' => $companyId, 'lead_id' => $leadId],
            action:       $action
        );
    }

    /** Create or reuse a conversation for this lead/company */
    private function resolveConversation(int $companyId, ?\App\Models\Client\Lead $lead): ?int
    {
        try {
            $query = \App\Models\Conversation::query()->where('company_id', $companyId);

            if ($lead && $lead->client_id) {
                $query->where('client_id', $lead->client_id);
            }

            $conv = $query->orderByDesc('latest_message_at')->first();

            if (!$conv) {
                $conv = \App\Models\Conversation::create([
                    'company_id'         => $companyId,
                    'client_id'          => $lead?->client_id,
                    'subject'            => $lead?->name ? ('Chat with ' . $lead->name) : 'WhatsApp Thread',
                    'latest_message_at'  => now(),
                    'is_whatsapp_linked' => 1,
                ]);
            } else {
                $conv->update(['latest_message_at' => now(), 'is_whatsapp_linked' => 1]);
            }

            return $conv->id;
        } catch (\Throwable $e) {
            \Log::debug('[Chat] resolveConversation failed: ' . $e->getMessage());
            return null;
        }
    }
}
