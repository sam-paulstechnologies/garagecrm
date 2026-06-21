{{-- resources/views/admin/opportunities/show.blade.php --}}
@extends('layouts.app')

@section('title', $opportunity->title ?? 'Opportunity Details')

@section('content')
@include('admin.opportunities.show-partials._styles')

@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $stageValues = $stages ?? \App\Models\Client\Opportunity::STAGES;
    $displayStage = \App\Models\Client\Opportunity::normalizeStage($opportunity->stage);
    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset';
    $stageLabels = collect($stageValues)
        ->mapWithKeys(fn ($stage) => [$stage => \App\Models\Client\Opportunity::stageLabel($stage)])
        ->all();
    $closedLostSubStatuses = $closedLostSubStatuses ?? [
        'not_interested' => 'Not interested',
        'price_not_accepted' => 'Price not accepted',
        'customer_cancelled' => 'Customer cancelled',
        'unreachable_after_follow_up' => 'Unreachable after follow-up',
        'service_not_required' => 'Service no longer required',
        'service_not_offered' => 'Service not offered',
        'duplicate' => 'Duplicate opportunity',
        'booked_elsewhere' => 'Booked elsewhere',
        'spam_or_test' => 'Spam / test',
        'other' => 'Other',
    ];

    $stageBadge = match ($displayStage) {
        'booking_confirmed' => 'sf-opportunity-badge-success',
        'closed_lost' => 'sf-opportunity-badge-danger',
        'manager_confirmation_pending', 'appointment', 'offer' => 'sf-opportunity-badge-warning',
        default => 'sf-opportunity-badge-neutral',
    };
    $priorityBadge = match (strtolower((string) ($opportunity->priority ?? 'medium'))) {
        'urgent' => 'sf-opportunity-badge-danger',
        'high' => 'sf-opportunity-badge-warning',
        default => 'sf-opportunity-badge-neutral',
    };

    $vehicleLabel = trim(($opportunity->vehicleMake?->name ?? $opportunity->other_make ?? '') . ' ' . ($opportunity->vehicleModel?->name ?? $opportunity->other_model ?? ''));
    $vehicleLabel = $vehicleLabel !== '' ? $vehicleLabel : ($opportunity->vehicle?->vehicle_label ?? $opportunity->vehicle?->plate_number ?? null);
    $phone = $opportunity->client?->phone ?? $opportunity->lead?->phone ?? null;
    $phoneService = app(\App\Services\PhoneNumberService::class);
    $phoneDisplay = $phone ? $phoneService->formatForDisplay($phone) : null;
    $telUrl = $phone ? $phoneService->buildTelUrl($phone) : null;
    $contactEmail = trim((string) ($opportunity->client?->email ?? $opportunity->lead?->email ?? ''));
    $contactMailtoUrl = $contactEmail !== '' ? 'mailto:' . $contactEmail : null;
    $opportunityWhatsappLookup = $phone ? $phoneService->buildWhatsappLookupKey($phone) : null;
    $opportunityWhatsappInboxUrl = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index', $opportunityWhatsappLookup ? ['search' => $opportunityWhatsappLookup] : [])
        : '#';
    $whatsappFloatingUrl = $opportunityWhatsappInboxUrl;
    $resolveWhatsappVerification = function (array $models): array {
        foreach ($models as $model) {
            if (! $model || ! method_exists($model, 'getAttributes')) {
                continue;
            }

            $attributes = $model->getAttributes();

            foreach (['whatsapp_verified', 'is_whatsapp_verified', 'verified_on_whatsapp'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    return [
                        'state' => (bool) $attributes[$field] ? 'verified' : 'not_verified',
                        'source' => get_class($model) . '::' . $field,
                    ];
                }
            }

            foreach (['whatsapp_verified_at', 'phone_verified_at'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    return [
                        'state' => filled($attributes[$field]) ? 'verified' : 'not_verified',
                        'source' => get_class($model) . '::' . $field,
                    ];
                }
            }
        }

        return ['state' => 'unknown', 'source' => null];
    };
    $opportunityLatestWhatsappLog = $opportunity->lead
        ? \App\Models\MessageLog::query()
            ->where('company_id', (int) ($opportunity->company_id ?? auth()->user()?->company_id))
            ->where('lead_id', $opportunity->lead->id)
            ->where('channel', 'whatsapp')
            ->latest()
            ->first()
        : null;
    $latestWhatsappStatus = strtolower((string) ($opportunityLatestWhatsappLog?->provider_status ?? ''));
    $whatsappHasFailed = in_array($latestWhatsappStatus, ['failed', 'undelivered', 'error'], true);
    $whatsappHasUsableLink = filled($opportunityWhatsappLookup) && \Illuminate\Support\Facades\Route::has('admin.inbox.index');
    $whatsappVerification = $resolveWhatsappVerification([$opportunity->client, $opportunity->lead]);
    $whatsappIndicatorState = $whatsappHasFailed || ! $whatsappHasUsableLink
        ? 'failed'
        : ($whatsappVerification['state'] === 'verified' ? 'verified' : 'unverified');
    $whatsappTagMeta = match ($whatsappIndicatorState) {
        'verified' => [
            'class' => 'sf-wa-verified',
            'label' => 'WhatsApp verified',
            'title' => 'This phone number is confirmed as reachable on WhatsApp.',
        ],
        'failed' => [
            'class' => 'sf-wa-failed',
            'label' => 'WhatsApp unavailable or failed',
            'title' => $whatsappHasFailed
                ? 'The latest WhatsApp send for this related lead failed.'
                : 'No usable WhatsApp inbox link is available for this phone number.',
        ],
        default => [
            'class' => 'sf-wa-unverified',
            'label' => 'WhatsApp not verified',
            'title' => $whatsappVerification['state'] === 'not_verified'
                ? 'This phone number has not been verified for WhatsApp.'
                : 'No WhatsApp verification status is available for this phone number.',
        ],
    };
    $opportunityStageExplainers = [
        'new' => 'Opportunity has been created and is ready for follow-up.',
        'attempting_contact' => 'Team is trying to reach or confirm with the customer.',
        'appointment' => 'Appointment timing or visit details are being arranged.',
        'offer' => 'Estimate, quote, or offer is being discussed.',
        'manager_confirmation_pending' => 'Waiting for manager approval or confirmation.',
        'booking_confirmed' => 'Customer confirmed; booking is created or linked.',
        'closed_lost' => 'Opportunity was not won and requires a reason.',
    ];
    $opportunityPriorityExplainers = [
        'urgent' => 'Needs immediate action.',
        'high' => 'High business priority.',
        'medium' => 'Normal priority opportunity.',
        'low' => 'Lower urgency opportunity.',
    ];
    $value = $opportunity->value ?? $opportunity->estimated_value ?? $opportunity->amount ?? 0;
    $services = collect(explode(',', (string) $opportunity->service_type))->map(fn ($item) => trim($item))->filter()->values();
    $quickUpdateUrl = \Illuminate\Support\Facades\Route::has('admin.opportunities.quick-update')
        ? route('admin.opportunities.quick-update', $opportunity)
        : null;
    $priorityOptions = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];
    $assignableUserOptions = collect($users ?? [])
        ->mapWithKeys(fn ($user) => [(string) $user->id => $user->name . (! empty($user->role) ? ' - ' . ucfirst($user->role) : '')])
        ->all();
    $currentStageIndex = array_search($displayStage, array_keys($stageLabels), true);
    $currentStageIndex = $currentStageIndex === false ? 0 : $currentStageIndex;
    $booking = $opportunity->bookings->sortByDesc('created_at')->first();
    $job = $opportunity->jobs->sortByDesc('created_at')->first();
    $invoice = $opportunity->invoices->sortByDesc('created_at')->first();
    $formatRelatedDate = function ($value, string $fallback = 'Date not set', bool $withTime = false): string {
        if (blank($value)) {
            return $fallback;
        }

        try {
            $date = $value instanceof \Carbon\CarbonInterface
                ? $value
                : \Illuminate\Support\Carbon::parse($value);

            return $date->format($withTime ? 'd M Y, h:i A' : 'd M Y');
        } catch (\Throwable $exception) {
            return $fallback;
        }
    };
    $formatRelatedLabel = fn ($value, string $fallback = 'Not set') => filled($value)
        ? \Illuminate\Support\Str::headline((string) $value)
        : $fallback;
    $leadCreatedLabel = $opportunity->lead
        ? $formatRelatedDate($opportunity->lead->created_at, 'Created date not set')
        : null;
    $leadStatusLabel = $opportunity->lead?->status_label ?? 'Status not set';
    $bookingDateLabel = $booking
        ? $formatRelatedDate($booking->booking_date ?? $booking->scheduled_at ?? $booking->created_at, 'Booking date not set')
        : null;
    $bookingStatusLabel = $booking ? $formatRelatedLabel($booking->status, 'Status not set') : null;
    $bookingSlotLabel = $booking ? $formatRelatedLabel($booking->slot, 'Slot not set') : null;
    $jobDateLabel = $job
        ? $formatRelatedDate($job->start_time ?? $job->created_at, 'Job date not set')
        : null;
    $jobStatusLabel = $job ? $formatRelatedLabel($job->status, 'Status not set') : null;
    $invoiceDateLabel = $invoice
        ? $formatRelatedDate($invoice->issue_date ?? $invoice->created_at, 'Invoice date not set')
        : null;
    $invoiceStatusLabel = $invoice ? $formatRelatedLabel($invoice->status, 'Status not set') : null;
    $nextAction = match ($displayStage) {
        'new' => 'Assign owner and start contact.',
        'attempting_contact' => 'Continue follow-up and collect customer intent.',
        'appointment' => 'Confirm appointment details with the customer.',
        'offer' => 'Review offer and close the next step.',
        'manager_confirmation_pending' => 'Manager confirmation is required.',
        'booking_confirmed' => 'Review the linked booking and operational handoff.',
        'closed_lost' => 'Review close reason and recovery opportunities.',
        default => 'Review opportunity details.',
    };
    $priorityValue = strtolower((string) ($opportunity->priority ?? 'medium'));
    $stageExplainer = $opportunityStageExplainers[$displayStage] ?? 'Current opportunity pipeline stage.';
    $priorityExplainer = $opportunityPriorityExplainers[$priorityValue] ?? 'Opportunity priority.';
    $heroChips = [
        ['value' => $opportunity->client?->name, 'title' => 'Customer linked to this opportunity.'],
        ['value' => $vehicleLabel, 'title' => 'Vehicle linked to this opportunity.'],
        ['value' => 'AED ' . number_format((float) $value, 2), 'title' => 'Estimated opportunity value.'],
        ['value' => $opportunity->source, 'title' => 'Where this opportunity came from.'],
    ];

    $detailSections = [
        'Opportunity Information' => [
            ['label' => 'Opportunity ID', 'value' => '#' . $opportunity->id],
            ['label' => 'Opportunity Name', 'value' => $opportunity->title, 'editable' => true, 'field' => 'title', 'type' => 'text', 'raw' => $opportunity->title],
            ['label' => 'Stage', 'value' => $stageLabels[$displayStage] ?? $opportunity->stage_label],
            ['label' => 'Priority', 'value' => ucfirst((string) ($opportunity->priority ?? 'Medium')), 'editable' => true, 'field' => 'priority', 'type' => 'select', 'options' => $priorityOptions, 'raw' => $opportunity->priority ?? 'medium'],
            ['label' => 'Estimated Value', 'value' => 'AED ' . number_format((float) $value, 2), 'editable' => true, 'field' => 'value', 'type' => 'number', 'raw' => $opportunity->value],
            ['label' => 'Source', 'value' => $opportunity->source, 'editable' => true, 'field' => 'source', 'type' => 'text', 'raw' => $opportunity->source],
            ['label' => 'Assigned To', 'value' => $opportunity->assignee?->name ?? 'Unassigned', 'editable' => true, 'field' => 'assigned_to', 'type' => 'select', 'options' => ['' => 'Unassigned'] + $assignableUserOptions, 'raw' => $opportunity->assigned_to],
            ['label' => 'Expected Close / Appointment', 'value' => $opportunity->expected_close_date?->format('d M Y'), 'editable' => true, 'field' => 'expected_close_date', 'type' => 'date', 'raw' => $opportunity->expected_close_date?->format('Y-m-d')],
            ['label' => 'Converted', 'value' => $opportunity->is_converted ? 'Yes' : 'No'],
        ],
        'Client / Vehicle Context' => [
            ['label' => 'Client', 'value' => $opportunity->client?->name],
            ['label' => 'Phone', 'value' => $phoneDisplay, 'link' => $telUrl],
            ['label' => 'Email', 'value' => $opportunity->client?->email ?? $opportunity->lead?->email],
            ['label' => 'Vehicle', 'value' => $vehicleLabel],
            ['label' => 'Linked Lead', 'value' => $opportunity->lead?->name, 'link' => $opportunity->lead && \Illuminate\Support\Facades\Route::has('admin.leads.show') ? route('admin.leads.show', $opportunity->lead) : null],
        ],
        'Service / Request Details' => [
            ['label' => 'Service Type', 'value' => $services->implode(', '), 'editable' => true, 'field' => 'service_type', 'type' => 'text', 'raw' => $opportunity->service_type],
            ['label' => 'Notes / Description', 'value' => $opportunity->notes, 'editable' => true, 'field' => 'notes', 'type' => 'textarea', 'raw' => $opportunity->notes],
            ['label' => 'Close Reason', 'value' => $opportunity->close_reason],
        ],
        'Commercial / Pipeline Details' => [
            ['label' => 'Value', 'value' => 'AED ' . number_format((float) $value, 2)],
            ['label' => 'Current Stage', 'value' => $stageLabels[$displayStage] ?? $opportunity->stage_label],
            ['label' => 'Next Action', 'value' => $nextAction],
            ['label' => 'Follow-up Date', 'value' => $opportunity->next_follow_up?->format('d M Y'), 'editable' => true, 'field' => 'next_follow_up', 'type' => 'date', 'raw' => $opportunity->next_follow_up?->format('Y-m-d')],
            ['label' => 'Booking', 'value' => $booking ? 'Booking #' . $booking->id : null, 'link' => $booking && \Illuminate\Support\Facades\Route::has('admin.bookings.show') ? route('admin.bookings.show', $booking) : null],
        ],
        'System Information' => [
            ['label' => 'Created At', 'value' => $opportunity->created_at?->format('d M Y, h:i A')],
            ['label' => 'Last Updated', 'value' => $opportunity->updated_at?->format('d M Y, h:i A')],
            ['label' => 'Archived Status', 'value' => $opportunity->is_archived ? 'Archived' : 'Not archived'],
        ],
    ];

    $activityTimeline = collect([
        ['time' => $opportunity->created_at, 'title' => 'Opportunity created', 'body' => $opportunity->title, 'source' => 'System'],
        ['time' => $opportunity->updated_at, 'title' => 'Opportunity updated', 'body' => $stageLabels[$displayStage] ?? $opportunity->stage_label, 'source' => 'System'],
        ['time' => $opportunity->lead?->updated_at, 'title' => 'Linked lead available', 'body' => $opportunity->lead?->name, 'source' => 'Lead'],
        ['time' => $booking?->created_at, 'title' => 'Booking linked', 'body' => $booking ? 'Booking #' . $booking->id : null, 'source' => 'Booking'],
        ['time' => $job?->created_at, 'title' => 'Job linked', 'body' => $job ? 'Job #' . $job->id : null, 'source' => 'Job'],
        ['time' => $invoice?->created_at, 'title' => 'Invoice linked', 'body' => $invoice ? 'Invoice #' . $invoice->id : null, 'source' => 'Invoice'],
    ])->filter(fn ($item) => filled($item['time']))->sortByDesc('time')->values();
@endphp

<div class="sf-opportunity-show min-h-screen px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-5">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <a href="{{ route('admin.opportunities.index') }}" class="sf-opportunity-back-link inline-flex text-sm font-bold">Back to Opportunities</a>

        <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="{{ $badgeBase }} sf-opportunity-badge-neutral sf-has-explainer" title="Opportunity record in the SayaraForce CRM." aria-label="Opportunity record in the SayaraForce CRM.">Opportunity</span>
                        <span class="{{ $badgeBase }} {{ $stageBadge }} sf-has-explainer" title="{{ $stageExplainer }}" aria-label="{{ ($stageLabels[$displayStage] ?? $opportunity->stage_label) }}: {{ $stageExplainer }}">{{ $stageLabels[$displayStage] ?? $opportunity->stage_label }}</span>
                        <span class="{{ $badgeBase }} {{ $priorityBadge }} sf-has-explainer" title="{{ $priorityExplainer }}" aria-label="{{ ucfirst((string) ($opportunity->priority ?? 'Medium')) }}: {{ $priorityExplainer }}">{{ ucfirst((string) ($opportunity->priority ?? 'Medium')) }}</span>
                    </div>

                    <div>
                        <h1 class="sf-opportunity-show-title text-2xl font-black tracking-tight sm:text-3xl">{{ $opportunity->title ?? 'Untitled Opportunity' }}</h1>
                        <div class="mt-3 flex flex-wrap gap-2 text-sm font-semibold">
                            @if($phoneDisplay && $telUrl)
                                <a href="{{ $telUrl }}" class="sf-opportunity-chip sf-has-explainer rounded-full border px-3 py-1.5 hover:border-orange-400" title="Click to call this customer." aria-label="Click to call this customer.">{{ $phoneDisplay }}</a>
                            @elseif($phoneDisplay)
                                <span class="sf-opportunity-chip sf-has-explainer rounded-full border px-3 py-1.5" title="Customer phone number." aria-label="Customer phone number.">{{ $phoneDisplay }}</span>
                            @endif

                            @if($phoneDisplay)
                                <a href="{{ $opportunityWhatsappInboxUrl }}" class="sf-wa-tag {{ $whatsappTagMeta['class'] }}" title="{{ $whatsappTagMeta['title'] }}" aria-label="{{ $whatsappTagMeta['label'] }}">
                                    <svg class="sf-wa-tag-icon" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                                        <path d="M16.01 3C8.83 3 3 8.83 3 16.01c0 2.29.6 4.53 1.74 6.5L3 29l6.67-1.7A12.92 12.92 0 0 0 16.01 29C23.18 29 29 23.18 29 16.01 29 8.83 23.18 3 16.01 3Zm0 23.75c-2.01 0-3.97-.54-5.69-1.57l-.41-.24-3.96 1.01 1.06-3.86-.27-.43a10.63 10.63 0 0 1-1.5-5.65c0-5.94 4.83-10.77 10.77-10.77s10.76 4.83 10.76 10.77-4.83 10.74-10.76 10.74Zm5.9-8.06c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.51-.16-.72.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.86-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.39-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.48 4.84.77.33 1.37.53 1.84.68.77.24 1.47.21 2.03.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
                                    </svg>
                                </a>
                            @endif

                            @foreach($heroChips as $chip)
                                @if(filled($chip['value']))
                                    <span class="sf-opportunity-chip sf-has-explainer rounded-full border px-3 py-1.5" title="{{ $chip['title'] }}" aria-label="{{ $chip['title'] }}">{{ $chip['value'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    <a href="#opportunity-activity-timeline" class="sf-btn-secondary rounded-full px-4 py-2 text-sm font-bold">View All Activity</a>
                    @if(Route::has('admin.opportunities.edit'))
                        <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="sf-btn-primary rounded-full px-4 py-2 text-sm font-bold">Edit Opportunity</a>
                    @endif
                    @if($opportunity->client && Route::has('admin.clients.show'))
                        <a href="{{ route('admin.clients.show', $opportunity->client) }}" class="sf-btn-secondary rounded-full px-4 py-2 text-sm font-bold">View Client</a>
                    @endif
                    @if(Route::has('admin.opportunities.destroy') && ! $opportunity->is_archived)
                        <form method="POST" action="{{ route('admin.opportunities.destroy', $opportunity) }}" onsubmit="return confirm('Archive this opportunity? This will not hard-delete it.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sf-btn-danger rounded-full px-4 py-2 text-sm font-bold">Archive</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        <section class="sf-opportunity-show-panel rounded-2xl border p-4 shadow-sm">
            <div class="sf-opportunity-stage-grid grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
                @foreach($stageLabels as $stage => $label)
                    @php
                        $index = array_search($stage, array_keys($stageLabels), true);
                        $isCurrent = $stage === $displayStage;
                        $isDone = $index !== false && $index < $currentStageIndex;
                        $stepClass = $isCurrent ? 'is-current' : ($isDone ? 'is-complete' : 'is-pending');
                        $needsExtra = in_array($stage, ['booking_confirmed', 'closed_lost'], true);
                        $buttonExplainer = $opportunityStageExplainers[$stage] ?? 'Update the opportunity pipeline stage.';
                    @endphp

                    <div class="min-w-0">
                        @if($needsExtra)
                            <details class="sf-opportunity-stage-details">
                                <summary class="sf-opportunity-stage-step sf-has-explainer {{ $stepClass }}" title="{{ $buttonExplainer }}" aria-label="{{ $label }}: {{ $buttonExplainer }}">{{ $label }}</summary>
                                <form method="POST" action="{{ route('admin.opportunities.stage', $opportunity) }}" class="mt-3 space-y-3 rounded-2xl border border-orange-200 bg-orange-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="stage" value="{{ $stage }}">

                                    @if($stage === 'booking_confirmed')
                                        <label class="block text-xs font-black uppercase text-slate-700">Booking Date</label>
                                        <input type="date" name="booking_date" value="{{ old('booking_date') }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                                        <label class="block text-xs font-black uppercase text-slate-700">Booking Slot</label>
                                        <select name="booking_slot" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                                            <option value="">Select slot</option>
                                            <option value="morning" @selected(old('booking_slot') === 'morning')>Morning</option>
                                            <option value="afternoon" @selected(old('booking_slot') === 'afternoon')>Afternoon</option>
                                            <option value="evening" @selected(old('booking_slot') === 'evening')>Evening</option>
                                        </select>
                                        <textarea name="booking_notes" rows="2" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950" placeholder="Booking notes">{{ old('booking_notes') }}</textarea>
                                    @else
                                        <label class="block text-xs font-black uppercase text-slate-700">Closed Lost Reason</label>
                                        <select name="stage_sub_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                                            <option value="">Select reason</option>
                                            @foreach($closedLostSubStatuses as $value => $reasonLabel)
                                                <option value="{{ $value }}" @selected(old('stage_sub_status') === $value)>{{ $reasonLabel }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="stage_reason" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950" placeholder="Required when reason is Other">{{ old('stage_reason') }}</textarea>
                                    @endif

                                    <button type="submit" class="sf-btn-primary rounded-full px-4 py-2 text-xs font-black">Update Stage</button>
                                </form>
                            </details>
                        @else
                            <form method="POST" action="{{ route('admin.opportunities.stage', $opportunity) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="stage" value="{{ $stage }}">
                                <button type="submit" class="sf-opportunity-stage-step sf-has-explainer {{ $stepClass }}" title="{{ $buttonExplainer }}" aria-label="{{ $label }}: {{ $buttonExplainer }}">{{ $label }}</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <main class="space-y-5">
                @foreach($detailSections as $sectionTitle => $fields)
                    @php $visibleFields = collect($fields)->filter(fn ($field) => filled($field['value'] ?? null) || (bool) ($field['editable'] ?? false)); @endphp
                    @if($visibleFields->isNotEmpty())
                        <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
                            <div class="mb-3">
                                <h2 class="sf-opportunity-section-title text-base font-black">{{ $sectionTitle }}</h2>
                            </div>
                            <div class="sf-opportunity-field-grid grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach($visibleFields as $field)
                                    @php
                                        $editable = (bool) ($field['editable'] ?? false);
                                        $quickField = $field['field'] ?? null;
                                        $inputType = $field['type'] ?? 'text';
                                        $options = $field['options'] ?? [];
                                        $rawValue = $field['raw'] ?? '';
                                        $fieldId = $quickField ? 'opportunity-field-' . str_replace('_', '-', $quickField) : null;
                                        $hasFieldError = old('field') === $quickField && $errors->has('value');
                                    @endphp
                                    <div class="sf-opportunity-field-card rounded-xl border p-4" @if($fieldId) id="{{ $fieldId }}" @endif>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="sf-opportunity-field-label text-xs font-black uppercase tracking-wide">{{ $field['label'] }}</div>
                                            @if($editable && $quickField && $quickUpdateUrl)
                                                <button type="button" class="sf-opportunity-edit-link text-xs font-bold" onclick="this.closest('.sf-opportunity-field-card').querySelector('details').open = true">Edit</button>
                                            @endif
                                        </div>
                                        <div class="sf-opportunity-field-value mt-2 break-words text-sm font-bold">
                                            @if(! empty($field['link']))
                                                <a href="{{ $field['link'] }}" class="sf-opportunity-link">{{ $field['value'] }}</a>
                                            @elseif(filled($field['value'] ?? null))
                                                {{ $field['value'] }}
                                            @else
                                                <span class="sf-opportunity-not-set">Not set</span>
                                            @endif
                                        </div>

                                        @if($editable && $quickField && $quickUpdateUrl)
                                            <details class="sf-opportunity-row-edit mt-3" @if($hasFieldError) open @endif>
                                                <summary class="sr-only">Quick edit {{ $field['label'] }}</summary>
                                                <form method="POST" action="{{ $quickUpdateUrl }}" class="grid gap-3 rounded-xl border border-orange-200 bg-orange-50 p-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="field" value="{{ $quickField }}">

                                                    <div class="space-y-2">
                                                        @if($inputType === 'select')
                                                            <select name="value" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">
                                                                @foreach($options as $optionValue => $optionLabel)
                                                                    <option value="{{ $optionValue }}" @selected((string) old('value', $rawValue) === (string) $optionValue)>
                                                                        {{ $optionLabel }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($inputType === 'textarea')
                                                            <textarea name="value" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">{{ old('value', $rawValue) }}</textarea>
                                                        @else
                                                            <input type="{{ $inputType }}" name="value" value="{{ old('value', $rawValue) }}" @if($inputType === 'number') step="0.01" min="0" @endif class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">
                                                        @endif

                                                        @if($hasFieldError)
                                                            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700">
                                                                {{ $errors->first('value') }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex flex-wrap gap-2 md:justify-end">
                                                        <button type="submit" class="sf-btn-primary rounded-full px-4 py-2 text-xs font-black">Save</button>
                                                        <button type="button" class="sf-btn-secondary rounded-full px-4 py-2 text-xs font-black" onclick="this.closest('details').open = false">Cancel</button>
                                                    </div>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </main>

            <aside class="space-y-5">
                <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
                    <h2 class="sf-opportunity-section-title text-base font-black">Client / Contact</h2>
                    <div class="mt-3 sf-opportunity-value text-sm font-black">{{ $opportunity->client?->name ?? $opportunity->lead?->name ?? 'No client linked' }}</div>
                    <div class="sf-contact-list mt-4 space-y-3">
                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">Call</div>
                            <div class="sf-contact-value mt-1 text-sm font-bold">
                                @if($telUrl)
                                    <a href="{{ $telUrl }}" class="sf-contact-link break-all" title="Click to call this customer." aria-label="Click to call this customer.">{{ $phoneDisplay }}</a>
                                @else
                                    <span class="sf-opportunity-not-set">Phone not set</span>
                                @endif
                            </div>
                        </div>

                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">WhatsApp</div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <a href="{{ $opportunityWhatsappInboxUrl }}" class="sf-wa-tag {{ $whatsappTagMeta['class'] }}" title="{{ $whatsappTagMeta['title'] }}" aria-label="{{ $whatsappTagMeta['label'] }}">
                                    <svg class="sf-wa-tag-icon" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                                        <path d="M16.01 3C8.83 3 3 8.83 3 16.01c0 2.29.6 4.53 1.74 6.5L3 29l6.67-1.7A12.92 12.92 0 0 0 16.01 29C23.18 29 29 23.18 29 16.01 29 8.83 23.18 3 16.01 3Zm0 23.75c-2.01 0-3.97-.54-5.69-1.57l-.41-.24-3.96 1.01 1.06-3.86-.27-.43a10.63 10.63 0 0 1-1.5-5.65c0-5.94 4.83-10.77 10.77-10.77s10.76 4.83 10.76 10.77-4.83 10.74-10.76 10.74Zm5.9-8.06c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.51-.16-.72.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.86-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.39-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.48 4.84.77.33 1.37.53 1.84.68.77.24 1.47.21 2.03.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
                                    </svg>
                                </a>
                                <span class="sf-contact-muted text-xs font-semibold">Internal Inbox</span>
                            </div>
                        </div>

                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">Email</div>
                            <div class="sf-contact-value mt-1 text-sm font-bold">
                                @if($contactMailtoUrl)
                                    <a href="{{ $contactMailtoUrl }}" class="sf-contact-link break-all">{{ $contactEmail }}</a>
                                @else
                                    <span class="sf-opportunity-not-set">Email not set</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
                    <h2 class="sf-opportunity-section-title text-base font-black">Vehicle</h2>
                    <div class="mt-3 text-sm font-bold sf-opportunity-value">{{ $vehicleLabel ?: 'No vehicle linked' }}</div>
                </section>

                <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
                    <h2 class="sf-opportunity-section-title text-base font-black">Next Action</h2>
                    <p class="mt-3 text-sm font-bold sf-opportunity-value">{{ $nextAction }}</p>
                </section>

                <section id="opportunity-activity-timeline" class="sf-opportunity-show-panel rounded-2xl border shadow-sm">
                    <div class="sf-opportunity-section-header border-b p-5">
                        <h2 class="sf-opportunity-section-title text-base font-black">Activity Timeline</h2>
                    </div>
                    <div class="max-h-[680px] space-y-3 overflow-y-auto p-5">
                        @forelse($activityTimeline as $item)
                            <div class="sf-opportunity-activity-item rounded-xl border p-4">
                                <div class="sf-opportunity-activity-summary flex items-start justify-between gap-3">
                                    <div>
                                        <div class="sf-opportunity-field-value text-sm font-black">{{ $item['title'] }}</div>
                                        <div class="mt-1 text-xs font-semibold sf-opportunity-muted">{{ $item['source'] }}</div>
                                    </div>
                                    <div class="whitespace-nowrap text-xs font-semibold sf-opportunity-muted">{{ $item['time']?->format('d M, h:i A') }}</div>
                                </div>
                                <div class="text-xs font-black uppercase sf-opportunity-muted">{{ $item['time']?->format('d M Y, h:i A') }} · {{ $item['source'] }}</div>
                                <div class="mt-1 text-sm font-black sf-opportunity-value">{{ $item['title'] }}</div>
                                @if(filled($item['body']))
                                    <div class="mt-1 text-sm font-semibold sf-opportunity-muted">{{ $item['body'] }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="sf-opportunity-activity-item rounded-xl border p-6 text-center text-sm font-bold">No activity recorded yet.</div>
                        @endforelse
                    </div>
                </section>

                <section class="sf-opportunity-show-panel rounded-2xl border p-5 shadow-sm">
                    <h2 class="sf-opportunity-section-title text-base font-black">Related Records</h2>
                    <div class="mt-4 space-y-3">
                        @if($opportunity->lead && Route::has('admin.leads.show'))
                            <a href="{{ route('admin.leads.show', $opportunity->lead) }}" class="sf-related-record-row group flex items-center justify-between gap-3 rounded-xl border p-3">
                                <span class="min-w-0">
                                    <span class="sf-related-record-label block text-xs font-black uppercase tracking-wide">Lead</span>
                                    <span class="sf-related-record-value mt-1 block text-sm font-black">#{{ $opportunity->lead->id }}</span>
                                    <span class="sf-related-record-meta mt-2 flex flex-wrap gap-1.5">
                                        <span class="sf-related-record-chip">Created {{ $leadCreatedLabel }}</span>
                                        <span class="sf-related-record-chip">Status {{ $leadStatusLabel }}</span>
                                    </span>
                                </span>
                                <span class="sf-related-record-arrow" aria-hidden="true">View</span>
                            </a>
                        @endif

                        @if($booking && Route::has('admin.bookings.show'))
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-related-record-row group flex items-center justify-between gap-3 rounded-xl border p-3">
                                <span class="min-w-0">
                                    <span class="sf-related-record-label block text-xs font-black uppercase tracking-wide">Booking</span>
                                    <span class="sf-related-record-value mt-1 block text-sm font-black">#{{ $booking->id }}</span>
                                    <span class="sf-related-record-meta mt-2 flex flex-wrap gap-1.5">
                                        <span class="sf-related-record-chip">Date {{ $bookingDateLabel }}</span>
                                        <span class="sf-related-record-chip">Status {{ $bookingStatusLabel }}</span>
                                        @if($bookingSlotLabel !== 'Slot not set')
                                            <span class="sf-related-record-chip">Slot {{ $bookingSlotLabel }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="sf-related-record-arrow" aria-hidden="true">View</span>
                            </a>
                        @endif

                        @if($job && Route::has('admin.jobs.show'))
                            <a href="{{ route('admin.jobs.show', $job) }}" class="sf-related-record-row group flex items-center justify-between gap-3 rounded-xl border p-3">
                                <span class="min-w-0">
                                    <span class="sf-related-record-label block text-xs font-black uppercase tracking-wide">Job</span>
                                    <span class="sf-related-record-value mt-1 block text-sm font-black">#{{ $job->id }}</span>
                                    <span class="sf-related-record-meta mt-2 flex flex-wrap gap-1.5">
                                        <span class="sf-related-record-chip">Date {{ $jobDateLabel }}</span>
                                        <span class="sf-related-record-chip">Status {{ $jobStatusLabel }}</span>
                                    </span>
                                </span>
                                <span class="sf-related-record-arrow" aria-hidden="true">View</span>
                            </a>
                        @endif

                        @if($invoice && Route::has('admin.invoices.show'))
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-related-record-row group flex items-center justify-between gap-3 rounded-xl border p-3">
                                <span class="min-w-0">
                                    <span class="sf-related-record-label block text-xs font-black uppercase tracking-wide">Invoice</span>
                                    <span class="sf-related-record-value mt-1 block text-sm font-black">#{{ $invoice->id }}</span>
                                    <span class="sf-related-record-meta mt-2 flex flex-wrap gap-1.5">
                                        <span class="sf-related-record-chip">Date {{ $invoiceDateLabel }}</span>
                                        <span class="sf-related-record-chip">Status {{ $invoiceStatusLabel }}</span>
                                    </span>
                                </span>
                                <span class="sf-related-record-arrow" aria-hidden="true">View</span>
                            </a>
                        @endif

                        @if(! $opportunity->lead && ! $booking && ! $job && ! $invoice)
                            <div class="sf-related-record-row rounded-xl border p-4 text-center text-sm font-bold">No related records yet.</div>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
