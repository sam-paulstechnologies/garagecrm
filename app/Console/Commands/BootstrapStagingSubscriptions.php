<?php

namespace App\Console\Commands;

use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\System\Company;
use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;
use RuntimeException;

class BootstrapStagingSubscriptions extends Command
{
    protected $signature = 'commercial:bootstrap-staging-subscriptions {--confirm}';

    protected $description = 'Assign explicit canonical subscriptions to unassigned synthetic staging companies.';

    public function handle(StagingSafety $safety, SubscriptionManager $subscriptions): int
    {
        if (! $this->option('confirm')) {
            $this->error('Refused: --confirm is required.');

            return self::FAILURE;
        }

        $safety->assertRuntimeIsolated();
        $assignments = collect((array) config('commercial.staging_assignments'))
            ->mapWithKeys(fn (string $plan, string $email): array => [strtolower(trim($email)) => $plan]);
        $counts = array_fill_keys(Plans::codes(), 0);

        Company::query()->doesntHave('subscription')->orderBy('id')->chunkById(100, function ($companies) use ($subscriptions, $assignments, &$counts): void {
            foreach ($companies as $company) {
                $plan = $assignments->get(strtolower(trim((string) $company->email)), Plans::FREE);
                if (! in_array($plan, Plans::codes(), true)) {
                    throw new RuntimeException('Staging subscription map contains an unknown plan code.');
                }

                $subscriptions->assignPlan($company, $plan);
                $counts[$plan]++;
            }
        });

        $total = array_sum($counts);
        $summary = collect($counts)->filter()->map(fn (int $count, string $plan): string => "{$plan}={$count}")->implode(', ');
        $this->info("Explicit staging subscriptions assigned: {$total}.".($summary !== '' ? " {$summary}." : ''));

        return self::SUCCESS;
    }
}
