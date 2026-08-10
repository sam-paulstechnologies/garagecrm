<?php

namespace App\Console\Commands;

use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnforceBillingGracePeriods extends Command
{
    protected $signature = 'billing:enforce-grace-periods {--dry-run}';

    protected $description = 'Suspend subscriptions whose configured failed-payment grace period has expired.';

    public function handle(): int
    {
        $query = Subscription::query()
            ->where('status', 'grace')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<=', now());
        $count = (clone $query)->count();
        if ($this->option('dry-run')) {
            $this->info("Expired billing grace periods: {$count}.");

            return self::SUCCESS;
        }

        $query->orderBy('id')->chunkById(100, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                DB::transaction(function () use ($subscription): void {
                    $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
                    if ($locked->status !== 'grace' || ! $locked->grace_ends_at?->isPast()) {
                        return;
                    }
                    $locked->update(['status' => 'suspended', 'suspended_at' => now()]);
                    EntitlementAuditLog::query()->create([
                        'company_id' => $locked->company_id,
                        'subscription_id' => $locked->id,
                        'event' => 'billing.grace_expired',
                        'source' => 'command',
                        'context' => ['result' => 'suspended'],
                        'created_at' => now(),
                    ]);
                });
            }
        });
        $this->info("Suspended {$count} expired subscription(s).");

        return self::SUCCESS;
    }
}
