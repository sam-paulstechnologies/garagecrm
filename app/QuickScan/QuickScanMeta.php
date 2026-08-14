<?php

namespace App\QuickScan;

use App\Exceptions\WhatsAppOnboardingException;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\QuickScan\QuickScanProviderSession;
use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\System\Company;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use App\Support\Staging\StagingSafety;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class QuickScanMeta
{
    public function __construct(
        private readonly MetaEmbeddedSignupService $meta,
        private readonly StagingSafety $stagingSafety,
        private readonly QuickScanAudit $audit,
    ) {}

    /** @return array{state:string,configuration:array} */
    public function start(QuickScanWorkspace $scan): array
    {
        $this->assertEnabled();
        if (! $scan->consent_at || $scan->status !== 'connection_pending') {
            throw new RuntimeException('Consent is required before connecting WhatsApp.');
        }
        $configuration = $this->meta->signupConfiguration((string) $scan->connection_mode);
        if (! ($configuration['is_configured'] ?? false)) {
            throw new RuntimeException('Meta connection is not configured for this staging environment.');
        }

        $state = Str::random(64);
        QuickScanProviderSession::query()->create([
            'quick_scan_workspace_id' => $scan->id,
            'state_hash' => hash('sha256', $state),
            'state_encrypted' => $state,
            'connection_mode' => $configuration['connection_mode'],
            'status' => 'started',
            'expires_at' => now()->addMinutes(max(5, (int) config('services.meta.whatsapp_embedded_signup.session_ttl_minutes', 15))),
        ]);
        $scan->forceFill(['connection_started_at' => now()])->save();
        $this->audit->record($scan, 'quick_scan.meta_connection_started', context: [
            'status' => 'connection_pending', 'connection_mode' => $configuration['connection_mode'],
        ]);

        return compact('state', 'configuration');
    }

    public function complete(QuickScanWorkspace $scan, array $input): QuickScanWorkspace
    {
        $this->assertEnabled();
        $session = DB::transaction(function () use ($scan, $input): QuickScanProviderSession {
            $session = QuickScanProviderSession::query()
                ->where('quick_scan_workspace_id', $scan->id)
                ->where('state_hash', hash('sha256', (string) $input['state']))
                ->lockForUpdate()
                ->firstOrFail();
            if ($session->status === 'started') {
                $session->forceFill(['status' => 'completing'])->save();
            }

            return $session;
        }, 3);
        if ($session->status === 'completed') {
            return $scan->fresh();
        }
        if ($session->expires_at?->isPast() || $session->status !== 'completing') {
            throw new RuntimeException('The Meta connection session expired. Start again.');
        }

        try {
            $tokenPayload = $this->meta->exchangeCodeForAccessToken((string) $input['code']);
            $accessToken = (string) $tokenPayload['access_token'];
            $inspection = $this->meta->inspectAccessToken($accessToken);
            $assets = $this->meta->verifyGrantedSignupAssets(
                $input,
                $accessToken,
                $inspection,
                (string) $session->connection_mode,
            );
            $phone = (array) $assets['phone'];
            $wabaId = (string) $assets['waba_id'];
            $phoneId = (string) $phone['id'];
            $displayPhone = (string) ($phone['display_phone_number'] ?? '');
            $this->stagingSafety->assertProviderAssetsAllowed($wabaId, $phoneId);
            $this->assertClaimedNumberMatches($scan, $displayPhone);
            $this->assertAssetAvailable($scan, $wabaId, $phoneId);
            $subscription = $this->meta->subscribeAppToWaba($wabaId, $accessToken);
            if (($subscription['status'] ?? null) !== 'subscribed') {
                throw new RuntimeException('Meta webhook subscription verification is still pending.');
            }
            $this->meta->requestHistorySyncForPhone($phoneId, $accessToken);

            DB::transaction(function () use ($session, $scan, $accessToken, $assets, $wabaId, $phoneId, $displayPhone): void {
                $session->forceFill([
                    'status' => 'completed', 'access_token' => $accessToken,
                    'waba_id' => $wabaId, 'phone_number_id' => $phoneId,
                    'business_id' => $assets['business_id'], 'display_phone_number' => $displayPhone,
                    'waba_hash' => $this->assetHash($wabaId), 'phone_number_hash' => $this->assetHash($phoneId),
                    'completed_at' => now(), 'sync_requested_at' => now(),
                ])->save();
                $scan->forceFill([
                    'status' => 'history_syncing', 'connected_at' => now(),
                    'history_sync_started_at' => now(), 'failure_code' => null,
                ])->save();
            }, 3);
            $this->audit->record($scan, 'quick_scan.meta_connected', context: [
                'status' => 'history_syncing', 'connection_mode' => $session->connection_mode,
            ]);

            return $scan->fresh();
        } catch (\Throwable $exception) {
            $session->forceFill([
                'status' => 'failed',
                'error_code' => $exception instanceof WhatsAppOnboardingException ? $exception->reason : class_basename($exception),
            ])->save();
            $scan->forceFill([
                'status' => 'failed', 'failure_code' => 'meta_connection_failed',
                'failure_detail' => $exception->getMessage(),
            ])->save();
            $this->audit->record($scan, 'quick_scan.meta_connection_failed', context: [
                'status' => 'failed', 'reason_code' => 'meta_connection_failed',
            ]);
            throw $exception;
        }
    }

    private function assertClaimedNumberMatches(QuickScanWorkspace $scan, string $displayPhone): void
    {
        $claimed = $this->digits($scan->claimed_whatsapp_number);
        if ($claimed === '' || $this->digits($displayPhone) !== $claimed) {
            throw new RuntimeException('Meta returned a different WhatsApp number from the number approved for this scan.');
        }
    }

    private function assertAssetAvailable(QuickScanWorkspace $scan, string $wabaId, string $phoneId): void
    {
        if (MessagingPhoneNumber::query()->where('provider', 'meta_whatsapp')->where('phone_number_id', $phoneId)->exists()
            || Company::query()->where('meta_phone_number_id', $phoneId)->exists()) {
            throw new RuntimeException('This WhatsApp number is already connected to a SayaraForce workspace.');
        }
        $wabaHash = $this->assetHash($wabaId);
        $phoneHash = $this->assetHash($phoneId);
        $claimed = QuickScanProviderSession::query()
            ->where('quick_scan_workspace_id', '!=', $scan->id)
            ->whereIn('status', ['started', 'completed'])
            ->where(function ($query) use ($wabaHash, $phoneHash): void {
                $query->where('waba_hash', $wabaHash)->orWhere('phone_number_hash', $phoneHash);
            })->exists();
        if ($claimed) {
            throw new RuntimeException('This WhatsApp asset is already assigned to another active Quick Scan.');
        }
    }

    private function assertEnabled(): void
    {
        if (! config('quick_scan.enabled')) {
            throw new RuntimeException('Quick Scan is not enabled.');
        }
    }

    private function assetHash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }
}
