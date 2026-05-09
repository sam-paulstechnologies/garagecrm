@extends('layouts.app')

@section('title', 'WhatsApp Template Mappings')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                WhatsApp Template Mappings
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Control which WhatsApp template is used for each SayaraForce journey trigger.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.templates.index'))
                <a href="{{ route('admin.whatsapp.templates.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Templates
                </a>
            @endif
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl bg-green-50 border border-green-100 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Error --}}
    @if($errors->any())
        <div class="mb-5 rounded-xl bg-red-50 border border-red-100 p-4 text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $journeyMeta = [
            'lead.created' => [
                'group' => 'Lead Journey',
                'audience' => 'Customer',
                'label' => 'New lead acknowledgement',
                'description' => 'Sent when a new lead is created and WhatsApp number is valid.',
            ],
            'lead.whatsapp_failed.manager_alert' => [
                'group' => 'Lead Journey',
                'audience' => 'Manager',
                'label' => 'Invalid lead WhatsApp alert',
                'description' => 'Sent to manager when lead WhatsApp is missing, invalid, or failed.',
            ],
            'booking.confirmed' => [
                'group' => 'Booking Journey',
                'audience' => 'Customer',
                'label' => 'Booking confirmed',
                'description' => 'Sent after manager confirms the booking.',
            ],
            'booking.rescheduled' => [
                'group' => 'Booking Journey',
                'audience' => 'Customer',
                'label' => 'Booking rescheduled',
                'description' => 'Sent when booking date or slot changes.',
            ],
            'booking.cancelled' => [
                'group' => 'Booking Journey',
                'audience' => 'Customer',
                'label' => 'Booking cancelled',
                'description' => 'Sent when booking is cancelled.',
            ],
            'job.started' => [
                'group' => 'Job Journey',
                'audience' => 'Customer',
                'label' => 'Vehicle received / job started',
                'description' => 'Sent when vehicle is received or job moves to in progress.',
            ],
            'job.progress' => [
                'group' => 'Job Journey',
                'audience' => 'Customer',
                'label' => 'Job progress update',
                'description' => 'Optional update while work is in progress.',
            ],
            'job.done.feedback' => [
                'group' => 'Job Journey',
                'audience' => 'Customer',
                'label' => 'Job completed feedback request',
                'description' => 'Sent after job is completed with invoice number and amount.',
            ],
            'feedback.positive.review' => [
                'group' => 'Feedback Journey',
                'audience' => 'Customer',
                'label' => 'Positive feedback Google review',
                'description' => 'Sent after positive feedback asking customer to leave Google review.',
            ],
            'feedback.negative.manager_alert' => [
                'group' => 'Feedback Journey',
                'audience' => 'Manager',
                'label' => 'Negative feedback escalation',
                'description' => 'Sent to manager when feedback is negative or complaint-like.',
            ],
            'retention.general_service' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'General service reminder',
                'description' => 'Future reminder for regular service.',
            ],
            'retention.oil_service' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'Oil service reminder',
                'description' => 'Future reminder for oil service.',
            ],
            'retention.battery' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'Battery check reminder',
                'description' => 'Future reminder for battery check or replacement.',
            ],
            'retention.ac' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'AC service reminder',
                'description' => 'Future reminder for AC service.',
            ],
            'retention.tyres' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'Tyre reminder',
                'description' => 'Future reminder for tyre inspection or replacement.',
            ],
            'retention.brakes' => [
                'group' => 'Retention Journey',
                'audience' => 'Customer',
                'label' => 'Brake safety reminder',
                'description' => 'Future reminder for brake check.',
            ],
        ];

        $mappingByEvent = $mappings->keyBy('event_key');

        $canonicalEvents = collect($journeyMeta)->keys();

        $eventsToShow = collect($eventKeys ?? [])
            ->merge($canonicalEvents)
            ->unique()
            ->values();

        $groupedEvents = $eventsToShow->groupBy(function ($eventKey) use ($journeyMeta) {
            return $journeyMeta[$eventKey]['group'] ?? 'Other Events';
        });
    @endphp

    {{-- Info Note --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-blue-900">
            WhatsApp Journey Control Center
        </p>
        <p class="text-sm text-blue-800 mt-1">
            Each event key should be mapped to an approved WhatsApp template. Missing or inactive mappings will prevent that journey message from sending automatically.
        </p>
    </div>

    {{-- Quick Add / Update --}}
    <div class="bg-white border rounded-xl shadow-sm p-5 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="font-semibold text-gray-900">
                    Map Trigger to Template
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Select an event and assign the WhatsApp template that should be used.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.whatsapp.mappings.store') }}">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

                <div class="lg:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Event
                    </label>

                    <select name="event_key"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            required>
                        @foreach($eventsToShow as $eventKey)
                            @php
                                $meta = $journeyMeta[$eventKey] ?? null;
                            @endphp

                            <option value="{{ $eventKey }}">
                                {{ $eventKey }}
                                @if($meta)
                                    — {{ $meta['label'] }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Template
                    </label>

                    <select name="template_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">No template</option>

                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">
                                {{ $template->name }}
                                @if($template->status)
                                    — {{ ucwords($template->status) }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-end">
                    <button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                        Save Mapping
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- Journey Groups --}}
    <div class="space-y-6">

        @foreach($groupedEvents as $groupName => $events)

            <div class="bg-white border rounded-xl shadow-sm overflow-hidden">

                <div class="px-5 py-4 border-b bg-gray-50">
                    <h3 class="font-semibold text-gray-900">
                        {{ $groupName }}
                    </h3>

                    <p class="text-xs text-gray-500 mt-1">
                        WhatsApp triggers used in this part of the customer journey.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">

                        <thead class="bg-white border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Journey Step</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Event Key</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Audience</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Mapped Template</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Template Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Mapping</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($events as $eventKey)

                                @php
                                    $mapping = $mappingByEvent->get($eventKey);
                                    $template = $mapping?->template;
                                    $meta = $journeyMeta[$eventKey] ?? [
                                        'label' => $eventKey,
                                        'description' => 'Custom or legacy event key.',
                                        'audience' => '—',
                                    ];

                                    $templateStatus = strtolower((string) ($template?->status ?? ''));

                                    $templateBadge = match($templateStatus) {
                                        'approved', 'active' => 'bg-green-50 text-green-800 border-green-100',
                                        'pending', 'pending approval' => 'bg-yellow-50 text-yellow-800 border-yellow-100',
                                        'rejected', 'failed' => 'bg-red-50 text-red-800 border-red-100',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };

                                    $mappingBadge = ! $mapping
                                        ? 'bg-red-50 text-red-800 border-red-100'
                                        : ($mapping->is_active
                                            ? 'bg-green-50 text-green-800 border-green-100'
                                            : 'bg-gray-50 text-gray-700 border-gray-200');
                                @endphp

                                <tr class="border-t hover:bg-gray-50 align-top">

                                    {{-- Journey Step --}}
                                    <td class="px-4 py-4 min-w-[260px]">
                                        <div class="font-medium text-gray-900">
                                            {{ $meta['label'] }}
                                        </div>

                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $meta['description'] }}
                                        </div>
                                    </td>

                                    {{-- Event --}}
                                    <td class="px-4 py-4">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                            {{ $eventKey }}
                                        </code>
                                    </td>

                                    {{-- Audience --}}
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-800 border border-blue-100">
                                            {{ $meta['audience'] }}
                                        </span>
                                    </td>

                                    {{-- Template --}}
                                    <td class="px-4 py-4 min-w-[240px]">
                                        @if($template)
                                            <div class="font-medium text-gray-900">
                                                {{ $template->name }}
                                            </div>

                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $template->provider_template ?: 'No provider template' }}
                                            </div>

                                            @if($template->body)
                                                <div class="text-xs text-gray-500 mt-2 max-w-[320px]">
                                                    <span class="block truncate" title="{{ $template->body }}">
                                                        {{ $template->body }}
                                                    </span>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-red-600 font-medium">
                                                Missing template
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Map a template before using this trigger.
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Template Status --}}
                                    <td class="px-4 py-4">
                                        @if($template)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $templateBadge }}">
                                                {{ $template->status ? ucwords($template->status) : 'Unknown' }}
                                            </span>

                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $template->provider ?: 'No provider' }}
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-800 border border-red-100">
                                                Missing
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Mapping Status --}}
                                    <td class="px-4 py-4">
                                        @if($mapping)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $mappingBadge }}">
                                                {{ $mapping->is_active ? 'Active' : 'Inactive' }}
                                            </span>

                                            <div class="text-xs text-gray-500 mt-1">
                                                Updated {{ $mapping->updated_at?->diffForHumans() ?? '—' }}
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $mappingBadge }}">
                                                Not mapped
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-3 whitespace-nowrap">

                                            @if($template && \Illuminate\Support\Facades\Route::has('admin.whatsapp.templates.show'))
                                                <a href="{{ route('admin.whatsapp.templates.show', $template) }}"
                                                   class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                                    Preview
                                                </a>
                                            @endif

                                            @if($mapping)
                                                <form action="{{ route('admin.whatsapp.mappings.toggle', $mapping) }}"
                                                      method="POST"
                                                      class="inline">
                                                    @csrf

                                                    <button type="submit"
                                                            class="text-yellow-700 hover:text-yellow-900 hover:underline font-medium">
                                                        {{ $mapping->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400">
                                                    Use form above
                                                </span>
                                            @endif

                                        </div>
                                    </td>

                                </tr>

                            @endforeach
                        </tbody>

                    </table>
                </div>

            </div>

        @endforeach

    </div>

</div>
@endsection