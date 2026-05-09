<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CampaignDispatchCommand extends Command
{
    protected $signature = 'campaigns:dispatch {--limit=200}';

    protected $description = 'Send scheduled/queued WhatsApp campaign messages';

    public function handle(): int
    {
        $now = now();
        $limit = (int) $this->option('limit');

        $campaigns = Campaign::with(['template', 'audience'])
            ->whereIn('status', ['scheduled', 'running'])
            ->where(function ($q) use ($now) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', $now);
            })
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'running']);

            $batch = $campaign->audience()
                ->where('status', 'queued')
                ->limit($limit)
                ->get();

            if ($batch->isEmpty()) {
                if ($campaign->audience()->where('status', 'queued')->count() === 0) {
                    $campaign->update(['status' => 'completed']);
                }

                continue;
            }

            foreach ($batch as $row) {
                $template = $campaign->template;

                if (! $template) {
                    Log::warning('[CampaignDispatchCommand] Template missing', [
                        'campaign_id' => $campaign->id,
                        'company_id'  => $campaign->company_id,
                        'audience_id' => $row->id ?? null,
                    ]);

                    $row->update(['status' => 'failed']);

                    continue;
                }

                $to = trim((string) $row->to);

                if ($to === '') {
                    Log::warning('[CampaignDispatchCommand] Recipient phone missing', [
                        'campaign_id' => $campaign->id,
                        'company_id'  => $campaign->company_id,
                        'audience_id' => $row->id ?? null,
                    ]);

                    $row->update(['status' => 'failed']);

                    continue;
                }

                $eventKey = $this->resolveEventKey($campaign, $template);

                if (! $eventKey) {
                    Log::warning('[CampaignDispatchCommand] WhatsApp event key missing', [
                        'campaign_id'   => $campaign->id,
                        'company_id'    => $campaign->company_id,
                        'audience_id'   => $row->id ?? null,
                        'template_id'   => $template->id ?? null,
                        'template_name' => $template->name ?? null,
                    ]);

                    $row->update(['status' => 'failed']);

                    continue;
                }

                try {
                    /*
                    |--------------------------------------------------------------------------
                    | Campaign WhatsApp Send
                    |--------------------------------------------------------------------------
                    |
                    | Campaign messages are proactive/automated.
                    | They must use approved Meta template mappings via fireEvent().
                    |
                    | SendWhatsAppMessage::fireEvent signature:
                    | fireEvent(int $companyId, string $eventKey, string $toE164, array $vars = [])
                    |
                    */

                    $message = app(SendWhatsAppMessage::class)->fireEvent(
                        (int) $campaign->company_id,
                        (string) $eventKey,
                        (string) $to,
                        $this->resolveVars($campaign, $row, $template, $eventKey)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Optional compatibility log
                    |--------------------------------------------------------------------------
                    |
                    | SendWhatsAppMessage::fireEvent() should create its own WhatsAppMessage
                    | record. This block keeps the old audience link behavior if a message
                    | object is returned.
                    |
                    */

                    if ($message instanceof WhatsAppMessage) {
                        $row->update([
                            'status'              => 'sent',
                            'whatsapp_message_id' => $message->id,
                        ]);
                    } else {
                        $row->update([
                            'status' => 'sent',
                        ]);
                    }

                    Log::info('[CampaignDispatchCommand] WhatsApp campaign event fired', [
                        'campaign_id'   => $campaign->id,
                        'company_id'    => $campaign->company_id,
                        'audience_id'   => $row->id ?? null,
                        'event_key'     => $eventKey,
                        'template_id'   => $template->id ?? null,
                        'template_name' => $template->name ?? null,
                    ]);
                } catch (\Throwable $e) {
                    $row->update(['status' => 'failed']);

                    Log::error('[CampaignDispatchCommand] WhatsApp campaign event failed', [
                        'campaign_id'   => $campaign->id,
                        'company_id'    => $campaign->company_id,
                        'audience_id'   => $row->id ?? null,
                        'event_key'     => $eventKey,
                        'template_id'   => $template->id ?? null,
                        'template_name' => $template->name ?? null,
                        'error'         => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info('Campaigns processed: ' . $campaigns->count());

        return self::SUCCESS;
    }

    protected function resolveEventKey(Campaign $campaign, object $template): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | Preferred: campaign/template event key
        |--------------------------------------------------------------------------
        */

        foreach ([
            $campaign->event_key ?? null,
            $campaign->whatsapp_event_key ?? null,
            $campaign->mapping_key ?? null,
            $template->event_key ?? null,
            $template->mapping_key ?? null,
        ] as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: template name
        |--------------------------------------------------------------------------
        |
        | This works only if the DB mapping uses the template name as event key.
        | If not mapped, SendWhatsAppMessage::fireEvent() should safely log failure.
        |
        */

        $templateName = trim((string) ($template->name ?? ''));

        return $templateName !== '' ? $templateName : null;
    }

    protected function resolveVars(Campaign $campaign, object $row, object $template, string $eventKey): array
    {
        $name = $row->name
            ?? $row->customer_name
            ?? $row->lead_name
            ?? 'there';

        $to = $row->to ?? null;

        return [
            /*
            |--------------------------------------------------------------------------
            | Numeric placeholders
            |--------------------------------------------------------------------------
            */

            0 => $name,
            1 => config('app.name', 'GarageCRM'),

            /*
            |--------------------------------------------------------------------------
            | Named placeholders
            |--------------------------------------------------------------------------
            */

            'name'          => $name,
            'customer_name' => $name,
            'lead_name'     => $name,
            'phone'         => $to,
            'app_name'      => config('app.name', 'GarageCRM'),

            /*
            |--------------------------------------------------------------------------
            | Context variables
            |--------------------------------------------------------------------------
            */

            'company_id'           => (int) $campaign->company_id,
            'campaign_id'          => (int) $campaign->id,
            'audience_id'          => $row->id ?? null,
            'target_type'          => $row->target_type ?? null,
            'target_id'            => $row->target_id ?? null,
            'message_template_id'  => $campaign->message_template_id ?? null,
            'template_id'          => $template->id ?? null,
            'template_name'        => $template->name ?? null,
            'event_key'            => $eventKey,
            'source'               => 'campaign_dispatch_command',
            'action'               => 'campaign_dispatch',
            'send_mode'            => 'meta_template',
        ];
    }
}