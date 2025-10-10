<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Campaign, CampaignAudience, WhatsAppMessage};
use App\Services\WhatsApp\ProviderFactory;

class CampaignDispatchCommand extends Command
{
    protected $signature = 'campaigns:dispatch {--limit=200}';
    protected $description = 'Send scheduled/queued WhatsApp campaign messages';

    public function handle(): int
    {
        $now = now();
        $limit = (int)$this->option('limit');

        $campaigns = Campaign::with(['template', 'audience'])
            ->whereIn('status', ['scheduled', 'running'])
            ->where(function($q) use ($now) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', $now);
            })->get();

        foreach ($campaigns as $c) {
            $c->update(['status' => 'running']);

            $batch = $c->audience()->where('status', 'queued')->limit($limit)->get();
            if ($batch->isEmpty()) {
                if ($c->audience()->where('status','queued')->count() === 0) {
                    $c->update(['status' => 'completed']);
                }
                continue;
            }

            $client = ProviderFactory::make();

            foreach ($batch as $row) {
                $body = $c->template->body; // (Optionally interpolate with target context later)

                try {
                    $resp = $client->send($row->to, $body);

                    $wa = WhatsAppMessage::create([
                        'company_id'          => $c->company_id,
                        'messageable_type'    => $row->target_type,
                        'messageable_id'      => $row->target_id,
                        'to'                  => $row->to,
                        'from'                => null,
                        'message_template_id' => $c->message_template_id,
                        'body'                => $body,
                        'status'              => 'sent',
                        'provider_message_id' => $resp['sid'] ?? ($resp['messages'][0]['id'] ?? $resp['message_id'] ?? null),
                        'meta'                => ['provider' => config('services.whatsapp.provider'), 'response' => $resp],
                        'sent_at'             => now(),
                    ]);

                    $row->update(['status' => 'sent', 'whatsapp_message_id' => $wa->id]);
                } catch (\Throwable $e) {
                    $row->update(['status' => 'failed']);
                }
            }
        }

        $this->info('Campaigns processed: '.$campaigns->count());
        return self::SUCCESS;
    }
}
