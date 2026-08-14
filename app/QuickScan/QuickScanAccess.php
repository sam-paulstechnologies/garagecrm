<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuickScanAccess
{
    public function __construct(private readonly QuickScanAudit $audit) {}

    /** @return array{scan:QuickScanWorkspace,token:string} */
    public function create(array $attributes, User $creator): array
    {
        $token = Str::random(64);
        $identity = mb_strtolower(trim((string) ($attributes['garage_email'] ?? ''))).'|'.preg_replace('/\D+/', '', (string) ($attributes['garage_phone'] ?? ''));
        $scan = QuickScanWorkspace::query()->create(array_merge($attributes, [
            'created_by' => $creator->id,
            'owner_user_id' => $attributes['owner_user_id'] ?? $creator->id,
            'garage_identity_hash' => trim($identity, '|') !== ''
                ? hash_hmac('sha256', $identity, (string) config('app.key'))
                : null,
            'status' => 'consent_pending',
            'access_token_hash' => hash('sha256', $token),
            'access_token_encrypted' => $token,
            'link_expires_at' => now()->addHours(max(1, (int) config('quick_scan.link_ttl_hours', 24))),
            'analysis_limit' => max(1, (int) config('quick_scan.analysis_contact_limit', 500)),
        ]));
        $this->audit->record($scan, 'quick_scan.created', $creator, ['status' => $scan->status]);

        return compact('scan', 'token');
    }

    public function resolve(string $token, bool $allowExpiredReport = false): QuickScanWorkspace
    {
        $scan = QuickScanWorkspace::query()->where('access_token_hash', hash('sha256', $token))->first();
        if (! $scan || $scan->revoked_at || $scan->status === 'purged') {
            throw new NotFoundHttpException;
        }

        if (! $scan->consent_at && $scan->link_expires_at?->isPast()) {
            if ($scan->status !== 'expired') {
                $scan->forceFill(['status' => 'expired'])->save();
                $this->audit->record($scan, 'quick_scan.expired', context: ['status' => 'expired']);
            }
            throw new NotFoundHttpException;
        }
        if (! $allowExpiredReport && $scan->report_expires_at?->isPast()) {
            throw new NotFoundHttpException;
        }

        return $scan;
    }

    public function token(QuickScanWorkspace $scan): string
    {
        return (string) $scan->access_token_encrypted;
    }

    public function url(QuickScanWorkspace $scan): string
    {
        return route('quick-scan.show', ['token' => $this->token($scan)]);
    }

    public function revoke(QuickScanWorkspace $scan, User $actor): void
    {
        $scan->forceFill(['status' => 'expired', 'revoked_at' => now()])->save();
        $this->audit->record($scan, 'quick_scan.revoked', $actor, ['status' => 'expired']);
    }

    public function regenerate(QuickScanWorkspace $scan, User $actor): string
    {
        $token = Str::random(64);
        $scan->forceFill([
            'access_token_hash' => hash('sha256', $token),
            'access_token_encrypted' => $token,
            'link_expires_at' => now()->addHours(max(1, (int) config('quick_scan.link_ttl_hours', 24))),
            'revoked_at' => null,
            'status' => $scan->consent_at ? $scan->status : 'consent_pending',
        ])->save();
        $this->audit->record($scan, 'quick_scan.link_regenerated', $actor, ['status' => $scan->status]);

        return $token;
    }
}
