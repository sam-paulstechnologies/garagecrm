<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\MetaPage;
use App\Services\Meta\MetaLeadService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(
        private MetaLeadService $meta,
        private WhatsAppService $whatsapp
    ) {}

    /**
     * Meta webhook verification (GET)
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub.mode');
        $token     = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        if (
            $mode === 'subscribe' &&
            hash_equals(
                (string) config('services.meta.verify_token'),
                (string) $token
            )
        ) {
            return response($challenge, 200);
        }

        Log::warning('Meta webhook verification failed', [
            'mode' => $mode,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Meta Leadgen webhook handler (POST)
     */
    public function handle(Request $request)
    {
        $secret = (string) config('services.meta.app_secret', '');
        if ($secret !== '') {
            $sig = (string) $request->header('X-Hub-Signature-256', '');
            if (! $this->validSignature($sig, $request->getContent(), $secret)) {
                Log::warning('Meta webhook: invalid signature');
                return response()->noContent(Response::HTTP_UNAUTHORIZED);
            }
        }

        foreach ((array) $request->json('entry', []) as $entry) {

            $pageId = $entry['id'] ?? null;
            if (! $pageId) continue;

            $metaPage = MetaPage::where('page_id', $pageId)->first();
            if (! $metaPage) {
                Log::warning('Meta webhook: unknown page', ['page_id' => $pageId]);
                continue;
            }

            foreach ((array) ($entry['changes'] ?? []) as $change) {

                $leadgenId = $change['value']['leadgen_id'] ?? null;
                if (! $leadgenId) continue;

                $row = $this->meta->fetchLeadById(
                    $metaPage->page_access_token,
                    (string) $leadgenId
                );

                if (! is_array($row)) continue;

                $email = $row['email'] ?? null;
                $phone = $row['phone'] ?? null;

                /** 1️⃣ Client */
                $client = Client::query()
                    ->where('company_id', $metaPage->company_id)
                    ->where(function ($q) use ($email, $phone) {
                        if ($email) $q->orWhere('email', $email);
                        if ($phone) $q->orWhere('phone', $phone);
                    })
                    ->first();

                if (! $client) {
                    $client = Client::create([
                        'company_id' => $metaPage->company_id,
                        'name'       => $row['name'] ?? 'Meta Lead',
                        'email'      => $email,
                        'phone'      => $phone,
                        'source'     => 'meta',
                        'status'     => 'active',
                    ]);
                }

                /** 2️⃣ Lead (idempotent) */
                $lead = Lead::updateOrCreate(
                    [
                        'company_id'      => $metaPage->company_id,
                        'external_source' => 'meta',
                        'external_id'     => (string) $leadgenId,
                    ],
                    [
                        'client_id'            => $client->id,
                        'name'                 => $row['name'] ?? 'Meta Lead',
                        'email'                => $email,
                        'phone'                => $phone,
                        'status'               => 'new',
                        'source'               => 'meta',
                        'preferred_channel'    => 'whatsapp',
                        'external_payload'     => $row,
                        'external_received_at' => now(),
                    ]
                );

                /** 3️⃣ SLICE 1.5 — WhatsApp ACK (safe, guarded) */
                if (
                    empty($lead->wa_ack_sent) &&
                    $this->whatsapp->isActiveForCompany($lead->company_id)
                ) {
                    try {
                        $this->whatsapp->sendTemplate(
                            toE164: $lead->phone,
                            templateName: 'lead_conversation_start_v1',
                            params: [$lead->name],
                            links: [],
                            context: [
                                'company_id' => $lead->company_id,
                                'lead_id'    => $lead->id,
                                'source'     => 'meta',
                            ]
                        );

                        $lead->update(['wa_ack_sent' => true]);

                    } catch (\Throwable $e) {
                        Log::error('[WA][META][ACK_FAIL]', [
                            'lead_id' => $lead->id,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return response()->noContent();
    }

    private function validSignature(string $sigHeader, string $raw, string $secret): bool
    {
        if (! Str::startsWith($sigHeader, 'sha256=')) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $raw, $secret),
            Str::after($sigHeader, 'sha256=')
        );
    }
}
