<?php

namespace App\Commercial;

use Illuminate\Support\Str;

final class RouteCapabilityMap
{
    /**
     * Resolve tenant web surfaces to their canonical commercial capability.
     * Specific write/action routes must appear before broader feature groups.
     */
    public function capabilityFor(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach ($this->rules() as [$patterns, $capability]) {
            if (Str::is($patterns, $routeName)) {
                return $capability;
            }
        }

        return null;
    }

    /** @return list<array{0:list<string>,1:string}> */
    private function rules(): array
    {
        return [
            [['admin.clients.import.batches.retention-actions'], 'retention_insights'],
            [['manager.bookings.convert-to-job'], 'jobs'],
            [['manager.inbox.send', 'manager.inbox.reply', 'manager.conversation.reply'], 'whatsapp_manual_reply'],
            [['manager.inbox.suggest-reply'], 'ai_recommendations'],
            [['manager.inbox.resume', 'manager.conversation.resume'], 'ai_action_execution'],
            [['admin.inbox.send', 'admin.inbox.reply'], 'whatsapp_manual_reply'],
            [['admin.whatsapp.campaigns.*'], 'whatsapp_marketing'],
            [['admin.whatsapp.templates.*'], 'whatsapp_marketing'],
            [['admin.whatsapp.mappings.*'], 'whatsapp_transactional'],
            [['admin.whatsapp.performance.*'], 'campaign_intelligence'],
            [['admin.whatsapp.messages.*', 'admin.whatsapp.logs.*'], 'inbox'],
            [['admin.whatsapp.settings.*', 'admin.whatsapp.connect*'], 'whatsapp_connect'],
            [['admin.ai.suggestions.approve', 'admin.ai.update', 'admin.ai.policy.update'], 'ai_action_execution'],
            [['admin.ai.suggestions.*', 'admin.ai.policy.edit'], 'ai_recommendations'],
            [['admin.ai.insights.*', 'admin.ai.edit'], 'ai_observational'],

            [['admin.users.*', 'manager.team.*'], 'limit.users'],
            [['admin.clients.*', 'manager.clients.*'], 'clients'],
            [['admin.vehicles.*'], 'vehicles'],
            [['admin.leads.*', 'manager.leads.*'], 'leads'],
            [['admin.opportunities.*', 'manager.opportunities.*'], 'opportunities'],
            [['admin.bookings.*', 'manager.bookings.*', 'manager.booking.*'], 'bookings'],
            [['admin.calendar.*'], 'calendar'],
            [['admin.inbox.*', 'manager.inbox.*', 'manager.escalations', 'manager.conversation'], 'inbox'],
            [['admin.jobs.*', 'manager.jobs.*', 'admin.documents.*'], 'jobs'],
            [['admin.invoices.*', 'manager.invoices.*'], 'invoices'],
            [['admin.communications.*', 'admin.communication-logs.*'], 'follow_up_reminders'],
            [['admin.retention-actions.*'], 'retention_insights'],
            [['admin.lead-sources.*', 'admin.growth.*', 'manager.growth.*'], 'source_attribution'],
            [['admin.audience-segmentations.*', 'admin.marketing.*'], 'campaign_intelligence'],
            [['admin.reports.*'], 'advanced_reports'],
            [['admin.sla_dashboard', 'manager.operations.*'], 'staff_performance'],
            [['admin.messaging.whatsapp.*'], 'whatsapp_connect'],
        ];
    }
}
