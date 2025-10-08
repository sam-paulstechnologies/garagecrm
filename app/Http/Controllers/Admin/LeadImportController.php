<?php
// app/Http/Controllers/Admin/LeadImportController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaLeadService;
use App\Services\Settings\SettingsStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Client\Lead;
use App\Models\LeadDuplicate;

class LeadImportController extends Controller
{
    public function __construct(private MetaLeadService $meta) {}

    public function importFromMeta(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $store     = new SettingsStore($companyId);

        $inlineToken   = trim((string) $request->input('meta_access_token', ''));
        $inlineFormId  = trim((string) $request->input('meta_form_id', ''));
        $limit         = max(1, (int) ($request->input('limit', 100)));

        $storedToken       = (string) $store->get('meta.access_token', '');
        $storedFormIdsJson = (string) $store->get('meta.form_ids', '');
        $envToken          = (string) config('services.meta.access_token', '');
        $envFormId         = (string) config('services.meta.form_id', '');

        $accessToken = $inlineToken ?: $storedToken ?: $envToken;

        $formIds = collect();
        if ($inlineFormId !== '') {
            $formIds = collect([$inlineFormId]);
        } elseif ($storedFormIdsJson !== '') {
            $decoded = json_decode($storedFormIdsJson, true);
            $formIds = is_array($decoded)
                ? collect($decoded)
                : collect(preg_split('/[,\s\[\]\"]+/', $storedFormIdsJson, -1, PREG_SPLIT_NO_EMPTY));
        } elseif ($envFormId !== '') {
            $formIds = collect([$envFormId]);
        }
        $formIds = $formIds->filter()->unique()->values();

        if (!$accessToken || $formIds->isEmpty()) {
            return back()->with('error', 'Meta import: please save Access Token and at least one Form ID in Settings.');
        }

        $inserted = 0; $upserts = 0; $skipped = 0; $dupes = 0; $console = [];

        // per-company window (fallback to config value)
        $windowDays = (int) $store->get('leads.dedupe_days', config('services.leads.dedupe_days', 30));
        $sinceDate  = now()->subDays($windowDays);

        foreach ($formIds as $formId) {
            $lock = Cache::lock("meta-import:{$companyId}:{$formId}", 55);
            if (! $lock->get()) {
                $console[] = "Form {$formId}: another import is in progress, skipped.";
                continue;
            }

            try {
                // checkpoint: last created_time we imported for this form
                $ckKey    = "meta.forms.{$formId}.last_created_time";
                $sinceIso = (string) $store->get($ckKey, null);

                $console[] = "→ Fetching leads for form {$formId} (since: ".($sinceIso ?: 'beginning').") ...";
                $rows = $this->meta->fetchLeadsSince($accessToken, (string)$formId, $sinceIso, $limit);

                $maxCreated = $sinceIso ? strtotime($sinceIso) : 0;

                foreach ($rows as $row) {
                    $externalId  = $row['external_id'] ?? null;
                    $createdTime = $row['created_time'] ?? null;
                    $ct          = $createdTime ? strtotime($createdTime) : null;
                    if ($ct && $ct > $maxCreated) $maxCreated = $ct;

                    $name    = $row['name']  ?? 'Meta Lead';
                    $email   = $row['email'] ?? null;
                    $phone   = $row['phone'] ?? null;
                    $payload = $row['raw']   ?? $row;

                    $emailNorm = Lead::normalizeEmail($email);
                    $phoneNorm = Lead::normalizePhone($phone);

                    // 1) Known Meta lead id → upsert
                    if (!empty($externalId)) {
                        $lead = Lead::updateOrCreate(
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

                        $lead->wasRecentlyCreated
                            ? ($inserted++ && $console[] = "   + Inserted lead {$externalId}")
                            : ($upserts++  && $console[] = "   ~ Updated lead {$externalId}");
                        continue;
                    }

                    // 2) No Meta id → time-window duplicate by email/phone
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
                        $dupes++; $console[] = "   - Duplicate ({$matchedOn}) for lead #{$match->id} within {$windowDays}d";
                        continue;
                    }

                    // 3) Not a duplicate → create new lead
                    DB::transaction(function () use (
                        $companyId, $formId, $name, $email, $emailNorm, $phone, $phoneNorm, $payload, $createdTime, &$inserted, &$console
                    ) {
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
                        $inserted++; $console[] = "   + Inserted lead (no external_id)";
                    });
                }

                // Save checkpoint
                if ($maxCreated > 0) {
                    $store->set($ckKey, gmdate('c', $maxCreated));
                }

                $console[] = "✔ Form {$formId}: done.";

            } catch (\Throwable $e) {
                $console[] = "❌ Meta import failed for form {$formId}: ".$e->getMessage();
                return back()->with('error', "Meta import failed for form {$formId}.")
                             ->with('meta_output', implode(PHP_EOL, $console));
            } finally {
                optional($lock)->release();
            }
        }

        $summary = "Meta import complete: +{$inserted} inserted, ~{$upserts} updated, -{$skipped} skipped, ⚠{$dupes} flagged as duplicates.";
        $console[] = $summary;

        return back()->with('success', $summary)
                     ->with('meta_output', implode(PHP_EOL, $console));
    }
}
