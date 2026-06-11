{{-- resources/views/admin/clients/show-partials/sections/_next_retention_follow_up_section.blade.php --}}

@php
    $followUp = $nextRetentionFollowUp ?? ['state' => 'empty'];
    $state = $followUp['state'] ?? 'empty';
    $statusCode = $followUp['status_code'] ?? $state;

    $badgeClass = match ($statusCode) {
        'overdue' => 'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-400/30 dark:bg-rose-500/10 dark:text-rose-200',
        'due_soon' => 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-400/30 dark:bg-orange-500/10 dark:text-orange-200',
        'upcoming' => 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200',
        'scheduled' => 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-400/30 dark:bg-blue-500/10 dark:text-blue-200',
        'sent' => 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200',
        'pending_review', 'approved', 'pending' => 'border-orange-300 bg-orange-50 text-orange-700 dark:border-orange-400/30 dark:bg-orange-500/10 dark:text-orange-200',
        'suggested' => 'border-purple-300 bg-purple-50 text-purple-700 dark:border-purple-400/30 dark:bg-purple-500/10 dark:text-purple-200',
        default => 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200',
    };

    $formatDate = function ($value) {
        if (! $value) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    };
@endphp

<section id="next-retention-follow-up" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/75">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                Retention
            </p>

            <h2 class="mt-1 text-lg font-extrabold tracking-tight text-slate-950 dark:text-white">
                Next Retention Follow-up
            </h2>

            <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                Suggested next customer message based on retention actions, import rows, or service history.
            </p>
        </div>

        <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-black {{ $badgeClass }}">
            {{ $followUp['status_label'] ?? 'No suggestion' }}
        </span>
    </div>

    @if($state === 'empty')
        <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-950/45">
            <p class="text-sm font-extrabold text-slate-900 dark:text-white">
                No retention follow-up suggested yet.
            </p>

            <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-400">
                Add service history or import retention data to generate reminders.
            </p>

            <p class="mt-3 text-xs font-bold text-slate-500 dark:text-slate-500">
                {{ $followUp['safety_note'] ?? 'No message is sent from this card.' }}
            </p>
        </div>
    @else
        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-400/20 dark:bg-orange-500/10">
                <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-200">
                    Retention Type
                </p>

                <p class="mt-3 text-base font-black text-slate-950 dark:text-white">
                    {{ $followUp['segment_label'] ?? 'Retention Follow-up' }}
                </p>

                @if(!empty($followUp['segment_code']))
                    <p class="mt-2 text-xs font-bold text-orange-700 dark:text-orange-200">
                        {{ $followUp['segment_code'] }}
                    </p>
                @endif
            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-400/20 dark:bg-blue-500/10">
                <p class="text-xs font-black uppercase tracking-wide text-blue-700 dark:text-blue-200">
                    Suggested Message Date
                </p>

                <p class="mt-3 text-base font-black text-slate-950 dark:text-white">
                    {{ $formatDate($followUp['follow_up_date'] ?? null) }}
                </p>

                <p class="mt-2 text-xs font-bold text-blue-700 dark:text-blue-200">
                    Channel: {{ $followUp['channel'] ?? 'Not set' }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/45">
                <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Source
                </p>

                <p class="mt-3 break-words text-sm font-bold leading-6 text-slate-800 dark:text-slate-200">
                    {{ $followUp['source_label'] ?? 'Retention Rule' }}
                </p>
            </div>
        </div>

        @if(!empty($followUp['vehicle_label']) || !empty($followUp['expiry_date']))
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/45">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Vehicle
                    </p>

                    <p class="mt-3 break-words text-sm font-bold leading-6 text-slate-800 dark:text-slate-200">
                        {{ $followUp['vehicle_label'] ?? 'Vehicle' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/45">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Expiry Date
                    </p>

                    <p class="mt-3 text-sm font-bold leading-6 text-slate-800 dark:text-slate-200">
                        {{ $formatDate($followUp['expiry_date'] ?? null) }}
                    </p>
                </div>
            </div>
        @endif

        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/45">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                Message Preview
            </p>

            <p class="mt-3 break-words text-sm font-semibold leading-6 text-slate-800 dark:text-slate-200">
                {{ $followUp['message'] ?? 'No message preview available yet.' }}
            </p>
        </div>

        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200">
            {{ $followUp['safety_note'] ?? 'No message is sent from this card.' }}
        </div>
    @endif
</section>
