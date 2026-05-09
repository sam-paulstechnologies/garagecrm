<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\LeadSource;
use App\Models\MetaPage;
use App\Services\Meta\MetaLeadService;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private MetaLeadService $meta,
        private WhatsAppService $whatsapp,
        private SendWhatsAppMessage $whatsappSender
    ) {}

    /**
     * Meta Lead Ads webhook verification.
     *
     * GET /api/v1/webhooks/meta/leads
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub.mode');
        $token     = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $expectedToken = (string) config('services.meta_leads.verify_token');

        if (
            $mode === 'subscribe' &&
            $expectedToken !== '' &&
            hash_equals($expectedToken, (string) $token)
        ) {
            return response($challenge, 200);
        }

        Log::warning('[META_LEADS][VERIFY_FAILED]', [
            'mode'      => $mode,
            'has_token' => ! empty($token),
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Meta Lead Ads webhook receiver.
     *
     * POST /api/v1/webhooks/meta/leads
     */
    public function handle(Request $request)
    {
        $secret = (string) config('services.meta_leads.app_secret', '');

        if ($secret !== '') {
            $signature = (string) $request->header('X-Hub-Signature-256', '');

            if (! $this->validSignature($signature, $request->getContent(), $secret)) {
                Log::warning('[META_LEADS][INVALID_SIGNATURE]', [
                    'has_signature' => $signature !== '',
                ]);

                return response()->noContent(Response::HTTP_UNAUTHORIZED);
            }
        }

        $entries = (array) $request->json('entry', []);

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;

            if (! $pageId) {
                Log::warning('[META_LEADS][MISSING_PAGE_ID]', [
                    'entry' => $entry,
                ]);

                continue;
            }

            $metaPage = MetaPage::where('page_id', (string) $pageId)->first();

            if (! $metaPage) {
                Log::warning('[META_LEADS][UNKNOWN_PAGE]', [
                    'page_id' => $pageId,
                ]);

                continue;
            }

            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = (array) ($change['value'] ?? []);

                $leadgenId = $value['leadgen_id'] ?? null;
                $formId    = $value['form_id'] ?? null;

                if (! $leadgenId) {
                    Log::warning('[META_LEADS][MISSING_LEADGEN_ID]', [
                        'company_id' => $metaPage->company_id,
                        'page_id'    => $pageId,
                        'change'     => $change,
                    ]);

                    continue;
                }

                if (! $formId) {
                    Log::warning('[META_LEADS][MISSING_FORM_ID]', [
                        'company_id' => $metaPage->company_id,
                        'page_id'    => $pageId,
                        'leadgen_id' => $leadgenId,
                        'change'     => $change,
                    ]);
                }

                $leadSource = $this->resolveLeadSource(
                    companyId: (int) $metaPage->company_id,
                    pageId: (string) $pageId,
                    formId: $formId ? (string) $formId : null
                );

                if ($formId && ! $leadSource) {
                    Log::warning('[META_LEADS][UNKNOWN_OR_INACTIVE_FORM]', [
                        'company_id' => $metaPage->company_id,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                try {
                    $row = $this->meta->fetchLeadById(
                        $metaPage->page_access_token,
                        (string) $leadgenId
                    );
                } catch (\Throwable $e) {
                    Log::error('[META_LEADS][FETCH_FAILED]', [
                        'company_id' => $metaPage->company_id,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                        'error'      => $e->getMessage(),
                    ]);

                    continue;
                }

                if (! is_array($row)) {
                    Log::warning('[META_LEADS][EMPTY_FETCH_RESPONSE]', [
                        'company_id' => $metaPage->company_id,
                        'page_id'    => $pageId,
                        'form_id'    => $formId,
                        'leadgen_id' => $leadgenId,
                    ]);

                    continue;
                }

                $email = $row['email'] ?? null;
                $phone = $row['phone'] ?? null;
                $name  = $row['name'] ?? 'Meta Lead';

                /*
                |--------------------------------------------------------------------------
                | Client Resolution
                |--------------------------------------------------------------------------
                */

                $client = null;

                if (! empty($email) || ! empty($phone)) {
                    $client = Client::query()
                        ->where('company_id', $metaPage->company_id)
                        ->where(function ($q) use ($email, $phone) {
                            if (! empty($email)) {
                                $q->orWhere('email', $email);
                            }

                            if (! empty($phone)) {
                                $q->orWhere('phone', $phone);
                            }
                        })
                        ->first();
                }

                if (! $client) {
                    $client = Client::create([
                        'company_id' => $metaPage->company_id,
                        'name'       => $name,
                        'email'      => $email,
                        'phone'      => $phone,
                        'source'     => 'meta',
                        'status'     => 'active',
                    ]);
                }

                $sourceName = $leadSource?->name ?? 'Meta Lead Ads';

                /*
                |--------------------------------------------------------------------------
                | Lead Creation / Update
                |--------------------------------------------------------------------------
                */

                $updateData = [
                    'client_id'            => $client->id,
                    'name'                 => $name,
                    'email'                => $email,
                    'phone'                => $phone,
                    'status'               => 'new',
                    'source'               => $sourceName,
                    'preferred_channel'    => 'whatsapp',
                    'external_form_id'     => $formId ? (string) $formId : ($row['form_id'] ?? null),
                    'external_payload'     => array_merge($row, [
                        '_webhook' => [
                            'page_id'        => (string) $pageId,
                            'page_name'      => (string) $metaPage->page_name,
                            'form_id'        => $formId ? (string) $formId : null,
                            'form_name'      => $leadSource?->configValue('form_name'),
                            'lead_source_id' => $leadSource?->id,
                            'leadgen_id'     => (string) $leadgenId,
                            'raw_change'     => $change,
                        ],
                    ]),
                    'external_received_at' => now(),
                ];

                /*
                |--------------------------------------------------------------------------
                | Optional column support
                |--------------------------------------------------------------------------
                */

                if ($leadSource && $this->leadHasFillable('lead_source_id')) {
                    $updateData['lead_source_id'] = $leadSource->id;
                }

                $lead = Lead::updateOrCreate(
                    [
                        'company_id'      => $metaPage->company_id,
                        'external_source' => 'meta',
                        'external_id'     => (string) $leadgenId,
                    ],
                    $updateData
                );

                if ($leadSource) {
                    $leadSource->update([
                        'last_received_at' => now(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | WhatsApp ACK
                |--------------------------------------------------------------------------
                |
                | This is a first/proactive outbound message from us.
                | So it must use an approved Meta template via DB mapping.
                |
                | Do NOT call WhatsAppService::sendTemplate() directly here.
                |
                | SendWhatsAppMessage::fireEvent() signature:
                |
                |   fireEvent(int $companyId, string $eventKey, string $toE164, array $vars = [])
                |
                */

                if (
                    ! empty($lead->phone) &&
                    empty($lead->wa_ack_sent) &&
                    $this->whatsapp->isActiveForCompany((int) $lead->company_id)
                ) {
                    try {
                        $this->whatsappSender->fireEvent(
                            (int) $lead->company_id,
                            'lead.created',
                            (string) $lead->phone,
                            [
                                /*
                                |--------------------------------------------------------------------------
                                | Template variables
                                |--------------------------------------------------------------------------
                                */

                                'name'          => $lead->name ?? 'Customer',
                                'customer_name' => $lead->name ?? 'Customer',
                                'lead_name'     => $lead->name ?? 'Customer',
                                'phone'         => $lead->phone,
                                'source'        => 'meta_leads',
                                'form_name'     => $leadSource?->configValue('form_name') ?? $sourceName,

                                /*
                                |--------------------------------------------------------------------------
                                | Context variables
                                |--------------------------------------------------------------------------
                                */

                                'company_id'      => (int) $lead->company_id,
                                'lead_id'         => (int) $lead->id,
                                'lead_source_id'  => $leadSource?->id,
                                'external_source' => 'meta',
                                'external_id'     => (string) $leadgenId,
                                'page_id'         => (string) $pageId,
                                'form_id'         => $formId ? (string) $formId : null,
                                'event_key'       => 'lead.created',
                                'action'          => 'initial',
                                'send_mode'       => 'meta_template',
                            ]
                        );

                        $lead->update(['wa_ack_sent' => true]);

                        Log::info('[WA][META_LEADS][ACK_SENT]', [
                            'company_id' => $lead->company_id,
                            'lead_id'    => $lead->id,
                            'event_key'  => 'lead.created',
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('[WA][META_LEADS][ACK_FAIL]', [
                            'company_id' => $lead->company_id,
                            'lead_id'    => $lead->id,
                            'event_key'  => 'lead.created',
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }

                Log::info('[META_LEADS][LEAD_CAPTURED]', [
                    'company_id'     => $lead->company_id,
                    'lead_id'        => $lead->id,
                    'client_id'      => $client->id,
                    'lead_source_id' => $leadSource?->id,
                    'page_id'        => $pageId,
                    'form_id'        => $formId,
                    'leadgen_id'     => $leadgenId,
                ]);
            }
        }

        return response()->noContent();
    }

    private function resolveLeadSource(int $companyId, string $pageId, ?string $formId): ?LeadSource
    {
        if (! $formId) {
            return null;
        }

        return LeadSource::query()
            ->where('company_id', $companyId)
            ->where('type', 'meta')
            ->whereIn('status', ['active', 'connected'])
            ->where(function ($query) use ($pageId, $formId) {
                $query
                    ->where('config->page_id', (string) $pageId)
                    ->where('config->form_id', (string) $formId);
            })
            ->first();
    }

    private function leadHasFillable(string $field): bool
    {
        return in_array($field, (new Lead())->getFillable(), true);
    }

    private function validSignature(string $sigHeader, string $raw, string $secret): bool
    {
        if (! Str::startsWith($sigHeader, 'sha256=')) {
            return false;
        }

        $expected = hash_hmac('sha256', $raw, $secret);
        $actual   = Str::after($sigHeader, 'sha256=');

        return hash_equals($expected, $actual);
    }
}