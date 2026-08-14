<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanProviderEvent;

final class QuickScanWebhookProcessor
{
    public function __construct(
        private readonly QuickScanIngestion $ingestion,
        private readonly QuickScanAnalysis $analysis,
    ) {}

    public function process(int $eventId): void
    {
        $event = QuickScanProviderEvent::query()->with('workspace')->find($eventId);
        if (! $event || $event->status === 'processed') {
            return;
        }
        $event->forceFill(['status' => 'processing', 'error_code' => null])->save();
        try {
            $payload = (array) ($event->payload ?? []);
            if ($event->field === 'history') {
                $completed = $this->syncIsComplete($payload);
                $scan = $this->ingestion->ingest($event->workspace, $payload, $completed);
                if ($completed) {
                    $this->analysis->run($scan);
                }
            }
            $event->forceFill(['status' => 'processed', 'processed_at' => now(), 'error_code' => null])->save();
        } catch (\Throwable $exception) {
            $event->forceFill(['status' => 'retrying', 'error_code' => class_basename($exception)])->save();
            throw $exception;
        }
    }

    private function syncIsComplete(array $payload): bool
    {
        $status = strtolower((string) ($payload['status'] ?? data_get($payload, 'metadata.status') ?? data_get($payload, 'history.0.status') ?? ''));
        $progress = $payload['progress'] ?? data_get($payload, 'metadata.progress') ?? data_get($payload, 'history.0.progress');

        return in_array($status, ['complete', 'completed', 'finished'], true)
            || filter_var($payload['is_complete'] ?? false, FILTER_VALIDATE_BOOL)
            || (is_numeric($progress) && (float) $progress >= 100);
    }
}
