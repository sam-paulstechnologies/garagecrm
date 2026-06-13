<?php

namespace App\Services\Reports;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GarageSummaryReportService
{
    public function dailySummary(int $companyId, ?Carbon $date = null): array
    {
        $date = ($date ?: today())->copy();

        return $this->summary($companyId, 'eod', $date->copy()->startOfDay(), $date->copy()->endOfDay(), $date);
    }

    public function weeklySummary(int $companyId, ?Carbon $weekStart = null): array
    {
        $start = ($weekStart ?: today())->copy()->startOfWeek();

        return $this->summary($companyId, 'eow', $start->copy()->startOfDay(), $start->copy()->endOfWeek()->endOfDay(), $start);
    }

    public function monthlySummary(int $companyId, ?Carbon $month = null): array
    {
        $start = ($month ?: today())->copy()->startOfMonth();

        return $this->summary($companyId, 'eom', $start->copy()->startOfDay(), $start->copy()->endOfMonth()->endOfDay(), $start);
    }

    private function summary(int $companyId, string $period, Carbon $from, Carbon $to, Carbon $anchor): array
    {
        $sections = [
            'operations' => $this->operations($companyId, $anchor),
            'leads' => $this->leads($companyId, $from, $to),
            'opportunities' => $this->opportunities($companyId, $from, $to),
            'jobs' => $this->jobs($companyId, $from, $to),
            'invoices' => $this->invoices($companyId, $from, $to),
            'retention' => $this->retention($companyId, $from, $to),
            'whatsapp' => $this->whatsapp($companyId, $from, $to),
        ];

        $template = $this->templatePreview($companyId, $period, $sections, $from, $to);

        return [
            'period' => $period,
            'period_label' => $this->periodLabel($period, $from, $to),
            'from' => $from,
            'to' => $to,
            'generated_at' => now(),
            'sections' => $sections,
            'template' => $template,
            'notes' => $this->availabilityNotes($sections),
        ];
    }

    private function operations(int $companyId, Carbon $date): array
    {
        $today = $date->copy()->toDateString();
        $tomorrow = $date->copy()->addDay()->toDateString();

        return [
            'bookings_today' => Booking::query()->where('company_id', $companyId)->whereDate('booking_date', $today)->count(),
            'bookings_tomorrow' => Booking::query()->where('company_id', $companyId)->whereDate('booking_date', $tomorrow)->count(),
            'pending_booking_confirmations' => Booking::query()->where('company_id', $companyId)->where('status', Booking::STATUS_PENDING)->count(),
            'jobs_pending' => Job::query()->where('company_id', $companyId)->where('status', 'pending')->count(),
            'jobs_in_progress' => Job::query()->where('company_id', $companyId)->where('status', 'in_progress')->count(),
            'jobs_completed_today' => Job::query()->where('company_id', $companyId)->where('status', 'completed')->whereDate($this->jobCompletionDateColumn(), $today)->count(),
            'invoices_created_today' => Invoice::query()->where('company_id', $companyId)->whereDate('created_at', $today)->count(),
            'unpaid_invoices' => Invoice::query()->where('company_id', $companyId)->whereNull('deleted_at')->where('status', '!=', 'paid')->count(),
            'overdue_invoices' => $this->overdueInvoicesQuery($companyId)->count(),
            'inbox_attention' => $this->unreadConversations($companyId),
        ];
    }

    private function leads(int $companyId, Carbon $from, Carbon $to): array
    {
        $base = Lead::query()->where('company_id', $companyId);

        return [
            'new_leads' => (clone $base)->whereBetween('created_at', [$from, $to])->count(),
            'by_status' => (clone $base)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($count) => (int) $count)
                ->all(),
            'follow_ups_due' => Schema::hasColumn('leads', 'follow_up_date')
                ? (clone $base)->where('follow_up_required', true)->whereDate('follow_up_date', '<=', $to)->count()
                : null,
        ];
    }

    private function opportunities(int $companyId, Carbon $from, Carbon $to): array
    {
        $base = Opportunity::query()->where('company_id', $companyId)->whereNull('deleted_at');

        return [
            'open_opportunities' => (clone $base)->whereIn('stage', Opportunity::ACTIVE_STAGES)->count(),
            'closed_won' => (clone $base)->where('stage', Opportunity::STAGE_CLOSED_WON)->whereBetween('updated_at', [$from, $to])->count(),
            'closed_lost' => (clone $base)->where('stage', Opportunity::STAGE_CLOSED_LOST)->whereBetween('updated_at', [$from, $to])->count(),
            'by_stage' => (clone $base)
                ->select('stage', DB::raw('COUNT(*) as total'))
                ->groupBy('stage')
                ->pluck('total', 'stage')
                ->map(fn ($count) => (int) $count)
                ->all(),
            'follow_ups_due' => Schema::hasColumn('opportunities', 'next_follow_up')
                ? (clone $base)->whereDate('next_follow_up', '<=', $to)->whereIn('stage', Opportunity::ACTIVE_STAGES)->count()
                : null,
        ];
    }

    private function jobs(int $companyId, Carbon $from, Carbon $to): array
    {
        $base = Job::query()->where('company_id', $companyId)->whereNull('deleted_at');

        return [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'completed' => (clone $base)->where('status', 'completed')->whereBetween($this->jobCompletionDateColumn(), [$from, $to])->count(),
            'overdue' => null,
            'by_assigned_user' => Schema::hasColumn('jobs', 'assigned_to')
                ? DB::table('jobs')
                    ->leftJoin('users', 'users.id', '=', 'jobs.assigned_to')
                    ->where('jobs.company_id', $companyId)
                    ->whereNull('jobs.deleted_at')
                    ->selectRaw('COALESCE(users.name, "Unassigned") as label, COUNT(jobs.id) as total')
                    ->groupBy('label')
                    ->orderByDesc('total')
                    ->limit(8)
                    ->get()
                    ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
                    ->all()
                : [],
            'service_types' => $this->jobServiceTypes($companyId, $from, $to),
        ];
    }

    private function invoices(int $companyId, Carbon $from, Carbon $to): array
    {
        $base = Invoice::query()->where('company_id', $companyId)->whereNull('deleted_at');

        return [
            'created' => (clone $base)->whereBetween('created_at', [$from, $to])->count(),
            'paid' => (clone $base)->where('status', 'paid')->whereBetween('updated_at', [$from, $to])->count(),
            'unpaid' => (clone $base)->where('status', '!=', 'paid')->count(),
            'overdue' => $this->overdueInvoicesQuery($companyId)->count(),
            'revenue_paid' => (float) (clone $base)->where('status', 'paid')->whereBetween('updated_at', [$from, $to])->sum('amount'),
            'revenue_pending' => (float) (clone $base)->where('status', '!=', 'paid')->sum('amount'),
            'monthly_revenue' => (float) (clone $base)->where('status', 'paid')->whereBetween('updated_at', [today()->startOfMonth(), today()->endOfMonth()])->sum('amount'),
        ];
    }

    private function retention(int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('retention_actions')) {
            return ['available' => false];
        }

        $base = DB::table('retention_actions')->where('company_id', $companyId);
        $terminal = ['sent', 'skipped', 'cancelled'];

        return [
            'available' => true,
            'pending_review' => (clone $base)->where('status', 'pending_review')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'scheduled' => (clone $base)->where('status', 'scheduled')->count(),
            'sent' => (clone $base)->where('status', 'sent')->count(),
            'skipped' => (clone $base)->where('status', 'skipped')->count(),
            'overdue' => (clone $base)
                ->whereNotIn('status', $terminal)
                ->where(function ($query) {
                    $query->whereDate('scheduled_at', '<', today())
                        ->orWhere(function ($suggested) {
                            $suggested->whereNull('scheduled_at')
                                ->whereDate('suggested_follow_up_date', '<', today());
                        });
                })
                ->count(),
            'upcoming_follow_ups' => (clone $base)
                ->whereNotIn('status', $terminal)
                ->whereBetween(DB::raw('COALESCE(scheduled_at, suggested_follow_up_date)'), [$from, $to])
                ->count(),
        ];
    }

    private function whatsapp(int $companyId, Carbon $from, Carbon $to): array
    {
        $messageLogAvailable = Schema::hasTable('message_logs');
        $waMessagesAvailable = Schema::hasTable('whatsapp_messages');

        return [
            'message_logs_available' => $messageLogAvailable,
            'whatsapp_messages_available' => $waMessagesAvailable,
            'messages_sent' => $messageLogAvailable
                ? DB::table('message_logs')
                    ->where('company_id', $companyId)
                    ->where('channel', 'whatsapp')
                    ->where('direction', 'out')
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
                : null,
            'failed_messages' => $messageLogAvailable
                ? DB::table('message_logs')
                    ->where('company_id', $companyId)
                    ->where('channel', 'whatsapp')
                    ->whereIn('provider_status', ['failed', 'undelivered', 'error'])
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
                : ($waMessagesAvailable
                    ? DB::table('whatsapp_messages')->where('company_id', $companyId)->where('status', 'failed')->whereBetween('created_at', [$from, $to])->count()
                    : null),
            'inbound_replies' => $messageLogAvailable
                ? DB::table('message_logs')
                    ->where('company_id', $companyId)
                    ->where('channel', 'whatsapp')
                    ->where('direction', 'in')
                    ->whereBetween('created_at', [$from, $to])
                    ->count()
                : null,
            'unread_conversations' => $this->unreadConversations($companyId),
        ];
    }

    private function templatePreview(int $companyId, string $period, array $sections, Carbon $from, Carbon $to): array
    {
        $definition = $this->templateDefinition($period);
        $variables = $this->templateVariables($period, $sections, $from, $to);
        $body = $this->renderTemplate($definition['body'], $variables);
        $template = DB::table('whatsapp_templates')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($definition) {
                $query->where('name', $definition['key'])
                    ->orWhere('provider_template', $definition['key']);
            })
            ->first();

        $mapping = DB::table('whatsapp_template_mappings')
            ->where('company_id', $companyId)
            ->where('event_key', $definition['event_key'])
            ->first();

        return [
            'key' => $definition['key'],
            'event_key' => $definition['event_key'],
            'body' => $definition['body'],
            'preview' => $body,
            'variables' => $variables,
            'local_template_exists' => (bool) $template,
            'local_template_status' => $template->status ?? null,
            'mapped' => (bool) $mapping,
            'mapped_template_id' => $mapping->template_id ?? null,
            'active_or_approved' => $template ? in_array(strtolower((string) $template->status), ['active', 'approved', 'enabled', 'quality_pending'], true) : false,
            'meta_note' => 'Create this Meta template manually and map it locally before any future send phase.',
        ];
    }

    public function templateDefinition(string $period): array
    {
        return match ($period) {
            'eow' => [
                'key' => 'garage_weekly_summary_eow_v1',
                'event_key' => 'garage.summary.eow',
                'body' => 'Weekly summary for {{1}}: {{2}} leads, {{3}} bookings, {{4}} completed jobs, {{5}} paid revenue, and {{6}} retention follow-ups due.',
            ],
            'eom' => [
                'key' => 'garage_monthly_summary_eom_v1',
                'event_key' => 'garage.summary.eom',
                'body' => 'Monthly summary for {{1}}: {{2}} leads, {{3}} bookings, {{4}} jobs completed, {{5}} revenue collected, and {{6}} unpaid invoices.',
            ],
            default => [
                'key' => 'garage_daily_summary_eod_v1',
                'event_key' => 'garage.summary.eod',
                'body' => 'Daily summary for {{1}}: {{2}} bookings today, {{3}} jobs completed, {{4}} unpaid invoices, {{5}} leads received, and {{6}} items need attention.',
            ],
        };
    }

    private function templateVariables(string $period, array $sections, Carbon $from, Carbon $to): array
    {
        return match ($period) {
            'eow' => [
                '1' => $this->periodLabel($period, $from, $to),
                '2' => (string) ($sections['leads']['new_leads'] ?? 0),
                '3' => (string) ($sections['operations']['bookings_today'] + $sections['operations']['bookings_tomorrow']),
                '4' => (string) ($sections['jobs']['completed'] ?? 0),
                '5' => 'AED ' . number_format((float) ($sections['invoices']['revenue_paid'] ?? 0), 2),
                '6' => (string) ($sections['retention']['upcoming_follow_ups'] ?? 0),
            ],
            'eom' => [
                '1' => $this->periodLabel($period, $from, $to),
                '2' => (string) ($sections['leads']['new_leads'] ?? 0),
                '3' => (string) ($sections['operations']['bookings_today'] + $sections['operations']['bookings_tomorrow']),
                '4' => (string) ($sections['jobs']['completed'] ?? 0),
                '5' => 'AED ' . number_format((float) ($sections['invoices']['revenue_paid'] ?? 0), 2),
                '6' => (string) ($sections['invoices']['unpaid'] ?? 0),
            ],
            default => [
                '1' => $this->periodLabel($period, $from, $to),
                '2' => (string) ($sections['operations']['bookings_today'] ?? 0),
                '3' => (string) ($sections['operations']['jobs_completed_today'] ?? 0),
                '4' => (string) ($sections['operations']['unpaid_invoices'] ?? 0),
                '5' => (string) ($sections['leads']['new_leads'] ?? 0),
                '6' => (string) (($sections['operations']['pending_booking_confirmations'] ?? 0) + ($sections['operations']['inbox_attention'] ?? 0)),
            ],
        };
    }

    private function renderTemplate(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return $body;
    }

    private function periodLabel(string $period, Carbon $from, Carbon $to): string
    {
        return match ($period) {
            'eow' => $from->format('d M') . ' - ' . $to->format('d M Y'),
            'eom' => $from->format('M Y'),
            default => $from->format('d M Y'),
        };
    }

    private function overdueInvoicesQuery(int $companyId)
    {
        return Invoice::query()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('status', 'overdue')
                    ->orWhere(function ($due) {
                        $due->where('status', '!=', 'paid')
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', today());
                    });
            });
    }

    private function unreadConversations(int $companyId): int
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasColumn('conversations', 'unread_count')) {
            return 0;
        }

        return (int) DB::table('conversations')
            ->where('company_id', $companyId)
            ->where('unread_count', '>', 0)
            ->count();
    }

    private function jobCompletionDateColumn(): string
    {
        return Schema::hasColumn('jobs', 'end_time') ? 'end_time' : 'updated_at';
    }

    private function jobServiceTypes(int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasColumn('bookings', 'service_type')) {
            return [];
        }

        return DB::table('jobs')
            ->join('bookings', 'bookings.id', '=', 'jobs.booking_id')
            ->where('jobs.company_id', $companyId)
            ->whereBetween('jobs.created_at', [$from, $to])
            ->whereNotNull('bookings.service_type')
            ->select('bookings.service_type as label', DB::raw('COUNT(jobs.id) as total'))
            ->groupBy('bookings.service_type')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    private function availabilityNotes(array $sections): array
    {
        $notes = [];

        if (($sections['jobs']['overdue'] ?? null) === null) {
            $notes[] = 'Job overdue reporting is not reliable yet because jobs do not have a dedicated due date column.';
        }

        if (! ($sections['whatsapp']['message_logs_available'] ?? false)) {
            $notes[] = 'WhatsApp reporting is limited because message_logs is not available.';
        }

        $notes[] = 'Reply, booking, and revenue attribution from summary messages is intentionally unavailable until summary dispatch is enabled.';

        return $notes;
    }
}
