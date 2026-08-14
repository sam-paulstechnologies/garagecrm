<?php

namespace App\QuickScan;

use App\Jobs\ProcessQuickScanWebhook;
use App\Models\QuickScan\QuickScanProviderEvent;
use App\Models\QuickScan\QuickScanProviderSession;
use App\Support\Staging\StagingSafety;

final class QuickScanWebhookRouter
{
    public function __construct(
        private readonly StagingSafety $stagingSafety,
        private readonly QuickScanAudit $audit,
    ) {}

    public function capture(string $wabaId, string $phoneNumberId, string $field, array $value): bool
    {
        if (! config('quick_scan.enabled') || ($wabaId === '' && $phoneNumberId === '')) {
            return false;
        }
        try {
            $this->stagingSafety->assertProviderAssetsAllowed($wabaId ?: null, $phoneNumberId ?: null);
        } catch (\RuntimeException) {
            return false;
        }
        $query = QuickScanProviderSession::query()
            ->where('status', 'completed')
            ->whereHas('workspace', fn ($builder) => $builder->whereIn('status', ['history_syncing', 'analysing']));
        if ($phoneNumberId !== '') {
            $query->where('phone_number_hash', $this->assetHash($phoneNumberId));
        } else {
            $query->where('waba_hash', $this->assetHash($wabaId));
        }
        $sessions = $query->with('workspace')->limit(2)->get();
        if ($sessions->count() !== 1) {
            return false;
        }
        $session = $sessions->first();
        if ($wabaId !== '' && ! hash_equals((string) $session->waba_hash, $this->assetHash($wabaId))) {
            return false;
        }

        $payloadHash = hash('sha256', json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $providerEvent = (string) (data_get($value, 'messages.0.id') ?? data_get($value, 'statuses.0.id') ?? '');
        $eventKey = hash('sha256', implode('|', ['quick_scan', $session->id, $field, $providerEvent ?: $payloadHash]));
        $historyField = in_array($field, ['history', 'smb_app_state_sync'], true);
        $event = QuickScanProviderEvent::query()->firstOrCreate(['event_key' => $eventKey], [
            'quick_scan_workspace_id' => $session->workspace->id,
            'quick_scan_provider_session_id' => $session->id,
            'field' => $field,
            'provider_event_hash' => $providerEvent !== '' ? hash('sha256', $providerEvent) : null,
            'payload_hash' => $payloadHash,
            'payload' => $historyField ? $value : null,
            'status' => $historyField ? 'pending' : 'ignored',
            'error_code' => $historyField ? null : 'quick_scan_observational_only',
            'occurred_at' => now(),
            'processed_at' => $historyField ? null : now(),
        ]);
        $session->forceFill(['last_webhook_at' => now()])->save();
        if ($event->wasRecentlyCreated) {
            $this->audit->record($session->workspace, 'quick_scan.webhook_received', context: [
                'field' => $field, 'status' => $event->status,
            ]);
            if ($historyField) {
                ProcessQuickScanWebhook::dispatch($event->id);
            }
        }

        return true;
    }

    private function assetHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
