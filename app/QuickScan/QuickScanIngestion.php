<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanCandidate;
use App\Models\QuickScan\QuickScanMessage;
use App\Models\QuickScan\QuickScanWorkspace;
use Illuminate\Support\Carbon;
use RuntimeException;

final class QuickScanIngestion
{
    public function __construct(private readonly QuickScanAudit $audit) {}

    public function ingest(QuickScanWorkspace $scan, array $payload, bool $completed): QuickScanWorkspace
    {
        if (in_array($scan->status, ['declined', 'purging', 'purged', 'expired'], true)) {
            throw new RuntimeException('Quick Scan history ingestion is no longer available.');
        }

        // Fable M2: record when customer history begins flowing in. This is the
        // canonical retention anchor — the reconciliation sweep derives a
        // deterministic purge deadline (anchor + retention window) from it, so a
        // scan that stalls or fails before report_ready can never retain customer
        // data indefinitely.
        $scan->forceFill([
            'status' => 'history_syncing',
            'history_sync_started_at' => $scan->history_sync_started_at ?? now(),
            'failure_code' => null,
        ])->save();

        $history = (array) ($payload['history'] ?? []);
        $batches = $history === [] ? [$payload] : (array_is_list($history) ? $history : [$history]);
        foreach ($batches as $batch) {
            foreach ((array) data_get($batch, 'threads', $payload['threads'] ?? []) as $thread) {
                if (is_array($thread)) {
                    $this->ingestThread($scan, $thread);
                }
            }
        }

        $discovered = $scan->candidates()->count();
        $scan->forceFill([
            'contacts_discovered' => $discovered,
            'status' => $completed ? 'analysing' : 'history_syncing',
            'history_sync_completed_at' => $completed ? now() : null,
        ])->save();
        $this->audit->record($scan, $completed ? 'quick_scan.history_sync_completed' : 'quick_scan.history_sync_progress', context: [
            'status' => $scan->status,
            'contacts_discovered' => $discovered,
        ]);

        return $scan->fresh();
    }

    private function ingestThread(QuickScanWorkspace $scan, array $thread): void
    {
        $customer = $this->digits($thread['wa_id'] ?? $thread['id'] ?? $thread['phone_number'] ?? null);
        if ($customer === '') {
            return;
        }
        $hash = $this->identityHash($scan, $customer);
        $staffHashes = (array) ($scan->staff_number_hashes ?? []);
        $candidate = QuickScanCandidate::query()->firstOrCreate([
            'quick_scan_workspace_id' => $scan->id,
            'external_identity_hash' => $hash,
        ], [
            'customer_identifier' => $customer,
            'display_name' => data_get($thread, 'profile.name'),
            'deterministic_excluded' => in_array($hash, $staffHashes, true),
            'intelligence_status' => in_array($hash, $staffHashes, true) ? 'deterministic' : 'discovered',
            'classification' => in_array($hash, $staffHashes, true) ? 'possible_colleague' : 'unknown',
        ]);

        foreach ((array) ($thread['messages'] ?? []) as $message) {
            if (is_array($message)) {
                $this->ingestMessage($scan, $candidate, $customer, $message);
            }
        }

        $stats = QuickScanMessage::query()->where('quick_scan_candidate_id', $candidate->id)->whereNull('purged_at');
        $candidate->forceFill([
            'message_count' => (clone $stats)->count(),
            'inbound_count' => (clone $stats)->where('direction', 'in')->count(),
            'outbound_count' => (clone $stats)->where('direction', 'out')->count(),
            'first_message_at' => (clone $stats)->min('message_timestamp'),
            'last_message_at' => (clone $stats)->max('message_timestamp'),
        ])->save();
    }

    private function ingestMessage(QuickScanWorkspace $scan, QuickScanCandidate $candidate, string $customer, array $message): void
    {
        $providerId = (string) ($message['id'] ?? '');
        $type = (string) ($message['type'] ?? 'unknown');
        $body = trim((string) ($message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? $message['caption']
            ?? ''));
        $timestamp = is_numeric($message['timestamp'] ?? null)
            ? Carbon::createFromTimestampUTC((int) $message['timestamp'])
            : null;
        $direction = ($message['from_me'] ?? false) || ($message['direction'] ?? null) === 'outbound' ? 'out' : 'in';
        $fingerprint = hash('sha256', implode('|', [
            $scan->public_id, $customer, $providerId, $timestamp?->timestamp,
            $direction, $type, hash('sha256', $body),
        ]));

        QuickScanMessage::query()->firstOrCreate(['source_fingerprint' => $fingerprint], [
            'quick_scan_workspace_id' => $scan->id,
            'quick_scan_candidate_id' => $candidate->id,
            'provider_message_hash' => $providerId !== '' ? hash('sha256', $providerId) : null,
            'direction' => $direction,
            'message_type' => $type,
            'body' => $body !== '' ? $body : null,
            'metadata' => [
                'has_media' => filled(data_get($message, $type.'.id')),
                'mime_type' => data_get($message, $type.'.mime_type'),
            ],
            'message_timestamp' => $timestamp,
        ]);
    }

    public function identityHash(QuickScanWorkspace $scan, string $number): string
    {
        $key = (string) config('messaging.history.hmac_key');
        if ($key === '') {
            throw new RuntimeException('Quick Scan identity protection is unavailable.');
        }

        return hash_hmac('sha256', $scan->public_id.'|'.$this->digits($number), $key);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }
}
