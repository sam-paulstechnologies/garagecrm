<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\LeadDuplicate;
use App\Models\MetaPage;
use App\Services\Meta\MetaLeadService;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWebhookController extends Controller
{
    public function __construct(private MetaLeadService $meta) {}

    /** Verification */
    public function verify(Request $r)
    {
        $verify   = (string) $r->query('hub.verify_token');
        $expected = (string) config('services.meta.verify_token');

        if ($r->query('hub.mode') === 'subscribe' && hash_equals($expected, $verify)) {
            return response($r->query('hub.challenge'), 200);
        }
        return response('Forbidden', 403);
    }

    /** Receive events */
    public function handle(Request $r)
    {
        // Optional: HMAC verify using app secret
        $secret = (string) config('services.meta.app_secret', '');
        if ($secret !== '') {
            $sig = (string) $r->header('X-Hub-Signature-256', '');
            if (! $this->validSignature($sig, $r->getContent(), $secret)) {
                Log::warning('Meta webhook: invalid signature');
                return response()->noContent(Response::HTTP_UNAUTHORIZED);
            }
        }

        $body = $r->json()->all();

        foreach ((array)($body['entry'] ?? []) as $entry) {
            $pageId = $entry['id'] ?? null; // << Page ID lives here
            if (! $pageId) continue;

            $metaPage = MetaPage::where('page_id', $pageId)->first();
            if (! $metaPage) {
                Log::warning('Meta webhook: unknown page id', ['page_id' => $pageId]);
                continue;
            }

            $companyId   = (int) $metaPage->company_id;
            $pageToken   = (string) $metaPage->page_access_token;
            $formsChosen = (array) ($metaPage->forms_json ?? []);

            $store = new SettingsStore($companyId);
            $windowDays = (int) $store->get('leads.dedupe_days', config('services.leads.dedupe_days', 30));
            $sinceDate  = now()->subDays($windowDays);

            foreach ((array)($entry['changes'] ?? []) as $change) {
                $value  = $change['value'] ?? [];
                $leadId = $value['leadgen_id'] ?? null;
                $formId = $value['form_id']    ?? null;

                // If you let admins pick specific forms, ignore others
                if (!empty($formsChosen) && $formId && !in_array($formId, $formsChosen, true)) {
                    continue;
                }

                if (! $leadId) continue;

                try {
                    $row = $this->meta->fetchLeadById($pageToken, (string)$leadId);
                    if ($row) {
                        $this->ingestRow($companyId, $formId, $row, $windowDays, $sinceDate);
                    }
                } catch (\Throwable $e) {
                    Log::error('Meta webhook ingest failed', [
                        'company_id' => $companyId,
                        'page_id'    => $pageId,
                        'leadgen_id' => $leadId,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }

        return response()->noContent();
    }

    private function validSignature(string $sigHeader, string $raw, string $secret): bool
    {
        if (! Str::startsWith($sigHeader, 'sha256=')) return false;
        $given = Str::after($sigHeader, 'sha256=');
        $calc  = hash_hmac('sha256', $raw, $secret);
        return hash_equals($calc, $given);
    }

    private function ingestRow(int $companyId, ?string $formId, array $row, int $windowDays, \Carbon\Carbon $sinceDate): void
    {
        // same ingest logic you already use (trimmed for brevity)
        $externalId  = $row['external_id']  ?? null;
        $createdTime = $row['created_time'] ?? null;
        $name        = $row['name']         ?? 'Meta Lead';
        $email       = $row['email']        ?? null;
        $phone       = $row['phone']        ?? null;
        $payload     = $row['raw']          ?? $row;

        $emailNorm = Lead::normalizeEmail($email);
        $phoneNorm = Lead::normalizePhone($phone);

        if ($externalId) {
            Lead::updateOrCreate(
                [
                    'company_id'      => $companyId,
                    'external_source' => 'meta',
                    'external_id'     => (string) $externalId,
                ],
                [
                    'name'                 => $name,
                    'email'                => $email,
                    'email_norm'           => $emailNorm,
                    'phone'                => $phone,
                    'phone_norm'           => $phoneNorm,
                    'status'               => 'new',
                    'source'               => 'meta',
                    'preferred_channel'    => 'whatsapp',
                    'external_form_id'     => (string) $formId,
                    'external_payload'     => $payload,
                    'external_received_at' => now(),
                    'created_at'           => $createdTime ?? now(),
                ]
            );
            return;
        }

        $match = Lead::query()
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $sinceDate)
            ->where(function ($q) use ($emailNorm, $phoneNorm) {
                if ($emailNorm) $q->orWhere('email_norm', $emailNorm);
                if ($phoneNorm) $q->orWhere('phone_norm', $phoneNorm);
            })
            ->orderBy('created_at', 'asc')
            ->first();

        if ($match) {
            $matchedOn = null;
            if ($emailNorm && $match->email_norm === $emailNorm) $matchedOn = 'email';
            if ($phoneNorm && $match->phone_norm === $phoneNorm) $matchedOn = $matchedOn ? 'both' : 'phone';

            LeadDuplicate::create([
                'company_id'        => $companyId,
                'primary_lead_id'   => $match->id,
                'external_source'   => 'meta',
                'external_id'       => null,
                'external_form_id'  => (string) $formId,
                'name'              => $name,
                'email'             => $email,
                'email_norm'        => $emailNorm,
                'phone'             => $phone,
                'phone_norm'        => $phoneNorm,
                'matched_on'        => $matchedOn,
                'window_days'       => $windowDays,
                'reason'            => "within {$windowDays} days of lead #{$match->id}",
                'payload'           => $payload,
                'detected_at'       => now(),
            ]);
            return;
        }

        Lead::create([
            'company_id'           => $companyId,
            'name'                 => $name,
            'email'                => $email,
            'email_norm'           => $emailNorm,
            'phone'                => $phone,
            'phone_norm'           => $phoneNorm,
            'status'               => 'new',
            'source'               => 'meta',
            'preferred_channel'    => 'whatsapp',
            'external_source'      => 'meta',
            'external_id'          => null,
            'external_form_id'     => (string) $formId,
            'external_payload'     => $payload,
            'external_received_at' => now(),
            'created_at'           => $createdTime ?? now(),
        ]);
    }
}
