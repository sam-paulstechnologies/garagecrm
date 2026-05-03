<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Meta\MetaLeadService;
use App\Services\Settings\SettingsStore;
use App\Services\Leads\LeadFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Client\Lead;

class LeadImportController extends Controller
{
    public function __construct(
        private MetaLeadService $meta,
        private LeadFactory $factory
    ) {}

    public function showMetaForm(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $store     = new SettingsStore($companyId);

        $prefill = [
            'meta_access_token' => (string) $store->get('meta.access_token', config('services.meta.access_token', '')),
            'meta_form_id'      => (string) $store->get('meta.form_id',     config('services.meta.form_id', '')),
            'limit'             => 100,
        ];

        return view('admin.leads.import-meta', compact('prefill'));
    }

    public function importFromMeta(Request $request)
    {
        $companyId = (int) $request->user()->company_id;
        $store     = new SettingsStore($companyId);

        $accessToken = trim(
            (string) $request->input('meta_access_token')
            ?: $store->get('meta.access_token')
            ?: config('services.meta.access_token')
        );

        $formIds = collect(
            array_filter(array_map('trim', explode(',', 
                (string) $request->input('meta_form_id')
                ?: (string) $store->get('meta.form_ids')
                ?: config('services.meta.form_id')
            )))
        )->unique();

        if (!$accessToken || $formIds->isEmpty()) {
            return back()->with('error', 'Meta import: Access Token and Form ID required.');
        }

        $inserted = 0;
        $updated  = 0;
        $dupes    = 0;
        $console  = [];

        $windowDays = (int) $store->get('leads.dedupe_days', 30);

        foreach ($formIds as $formId) {

            $lock = Cache::lock("meta-import:{$companyId}:{$formId}", 60);
            if (!$lock->get()) {
                $console[] = "⚠ Import already running for form {$formId}";
                continue;
            }

            try {
                $ckKey    = "meta.forms.{$formId}.last_created_time";
                $sinceIso = $store->get($ckKey);

                $rows = $this->meta->fetchLeadsSince(
                    $accessToken,
                    (string) $formId,
                    $sinceIso,
                    (int) $request->input('limit', 100)
                );

                $maxCreated = $sinceIso ? strtotime($sinceIso) : 0;

                foreach ($rows as $row) {
                    $createdTime = $row['created_time'] ?? null;
                    if ($createdTime && strtotime($createdTime) > $maxCreated) {
                        $maxCreated = strtotime($createdTime);
                    }

                    $payload = $row['raw'] ?? $row;

                    // Known Meta ID → safe upsert
                    if (!empty($row['external_id'])) {
                        $lead = Lead::updateOrCreate(
                            [
                                'company_id'      => $companyId,
                                'external_source' => 'meta',
                                'external_id'     => (string) $row['external_id'],
                            ],
                            [
                                'name'                 => $row['name'] ?? 'Meta Lead',
                                'email'                => $row['email'] ?? null,
                                'phone'                => $row['phone'] ?? null,
                                'status'               => 'new',
                                'source'               => 'meta',
                                'preferred_channel'    => 'whatsapp',
                                'external_form_id'     => (string) $formId,
                                'external_payload'     => $payload,
                                'external_received_at' => now(),
                                'created_at'           => $createdTime ?? now(),
                            ]
                        );

                        $lead->wasRecentlyCreated ? $inserted++ : $updated++;
                        continue;
                    }

                    // Unknown ID → go through LeadFactory (DEDUP)
                    $result = $this->factory->createOrDetectDuplicate([
                        'company_id'           => $companyId,
                        'name'                 => $row['name'] ?? 'Meta Lead',
                        'email'                => $row['email'] ?? null,
                        'phone'                => $row['phone'] ?? null,
                        'status'               => 'new',
                        'source'               => 'meta',
                        'preferred_channel'    => 'whatsapp',
                        'external_source'      => 'meta',
                        'external_form_id'     => (string) $formId,
                        'external_payload'     => $payload,
                        'external_received_at' => now(),
                        'window_days'          => $windowDays,
                    ]);

                    $result instanceof Lead ? $inserted++ : $dupes++;
                }

                if ($maxCreated > 0) {
                    $store->set($ckKey, gmdate('c', $maxCreated));
                }

                $console[] = "✔ Form {$formId} processed";

            } catch (\Throwable $e) {
                $console[] = "❌ {$e->getMessage()}";
            } finally {
                optional($lock)->release();
            }
        }

        $summary = "Meta import done: +{$inserted} new, ~{$updated} updated, ⚠{$dupes} duplicates";

        return back()
            ->with('success', $summary)
            ->with('meta_output', implode(PHP_EOL, $console));
    }
}
