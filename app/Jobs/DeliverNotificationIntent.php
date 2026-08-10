<?php

namespace App\Jobs;

use App\Commercial\CompanyAccessService;
use App\Models\Notifications\NotificationIntent;
use App\Models\Notifications\PushDevice;
use App\Notifications\Push\PushProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class DeliverNotificationIntent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $intentId)
    {
        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function handle(CompanyAccessService $access, PushProviderManager $providers): void
    {
        $intent = NotificationIntent::query()->with(['company', 'user'])->find($this->intentId);
        if (! $intent || $intent->state !== 'pending') {
            return;
        }
        if (! $intent->company || ! $access->companyCanAccess($intent->company) || ! $intent->user
            || (int) $intent->user->company_id !== (int) $intent->company_id) {
            $intent->update(['state' => 'suppressed', 'failure_reason' => 'tenant_not_operational']);
            return;
        }

        $devices = PushDevice::query()
            ->where('company_id', $intent->company_id)
            ->where('user_id', $intent->user_id)
            ->where('status', 'active')
            ->get();
        if ($devices->isEmpty()) {
            $intent->update(['state' => 'suppressed', 'failure_reason' => 'no_active_device']);
            return;
        }

        try {
            $provider = $providers->driver();
            $delivered = $devices->contains(fn ($device) => $provider->deliver($device, $intent)->delivered);
            $intent->update([
                'state' => $delivered ? 'delivered' : 'failed',
                'dispatched_at' => now(),
                'delivered_at' => $delivered ? now() : null,
                'failure_reason' => $delivered ? null : 'provider_rejected',
            ]);
        } catch (Throwable $error) {
            $intent->update(['state' => 'failed', 'dispatched_at' => now(), 'failure_reason' => 'provider_unavailable']);
            report($error);
        }
    }
}
