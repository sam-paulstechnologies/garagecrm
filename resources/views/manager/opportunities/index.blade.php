@extends('layouts.manager')

@section('title', 'Manager Opportunities')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $stageLabels = $stageLabels ?? [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'appointment' => 'Appointment',
        'offer' => 'Offer',
        'follow_up' => 'Follow Up',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
    ];

    $opportunityStages = $opportunityStages ?? array_keys($stageLabels);

    $stageLabel = function ($value) use ($stageLabels) {
        return $stageLabels[$value] ?? match ($value) {
            'New' => 'New',
            'Attempting Contact' => 'Attempting Contact',
            'Manager Confirmation Pending' => 'Manager Confirmation Pending',
            'Appointment' => 'Appointment',
            'Offer' => 'Offer',
            'Follow Up' => 'Follow Up',
            'Closed Won' => 'Closed Won',
            'Closed Lost' => 'Closed Lost',
            default => $value ? ucwords(str_replace('_', ' ', $value)) : 'New',
        };
    };

    $opportunityName = function ($opportunity) {
        return $opportunity->title
            ?? $opportunity->name
            ?? $opportunity->customer_name
            ?? $opportunity->client_name
            ?? 'Opportunity #' . $opportunity->id;
    };

    $opportunityPhone = function ($opportunity) {
        return $opportunity->phone
            ?? $opportunity->mobile
            ?? $opportunity->phone_number
            ?? $opportunity->whatsapp_number
            ?? '-';
    };

    $opportunityVehicle = function ($opportunity) {
        $make = $opportunity->vehicle_make ?? $opportunity->make ?? null;
        $model = $opportunity->vehicle_model ?? $opportunity->model ?? null;

        return trim(($make ?? '') . ' ' . ($model ?? '')) ?: '-';
    };

    $assignedValue = function ($opportunity) {
        return $opportunity->assigned_to
            ?? $opportunity->assigned_to_id
            ?? $opportunity->assigned_user_id
            ?? $opportunity->manager_id
            ?? $opportunity->owner_id
            ?? $opportunity->user_id
            ?? null;
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Opportunities
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage follow-ups, appointments, offers, and open sales opportunities.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}"
           class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please check the form below.</p>
            <ul class="mt-2 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
        <div class="p-4">
            <form method="GET" action="{{ route('manager.opportunities.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Search
                    </label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Name, phone, email, vehicle, notes"
                    >
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Stage
                    </label>
                    <select
                        name="stage"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Stages</option>
                        @foreach($opportunityStages as $value)
                            <option value="{{ $value }}" @selected(($stage ?? request('stage')) === $value)>
                                {{ $stageLabel($value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <select
                        name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All</option>
                        <option value="active" @selected(($status ?? request('status')) === 'active')>Active</option>
                        <option value="open" @selected(($status ?? request('status')) === 'open')>Open</option>
                        <option value="won" @selected(($status ?? request('status')) === 'won')>Won</option>
                        <option value="lost" @selected(($status ?? request('status')) === 'lost')>Lost</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="w-full inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold text-gray-900">
                    Open Opportunities
                </h2>
                <p class="text-sm text-gray-500">
                    Active sales pipeline for manager follow-up.
                </p>
            </div>

            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                {{ method_exists($opportunities, 'total') ? $opportunities->total() : $opportunities->count() }} opportunity(s)
            </span>
        </div>

        @if($opportunities->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Opportunity</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Contact</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Vehicle</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Stage</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Follow-up</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Assign</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($opportunities as $opportunity)
                            @php
                                $statusClass = match($opportunity->status ?? '') {
                                    'won' => 'bg-green-100 text-green-700',
                                    'lost' => 'bg-red-100 text-red-700',
                                    'open' => 'bg-indigo-100 text-indigo-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-gray-900">
                                        {{ $opportunityName($opportunity) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        #{{ $opportunity->id }}
                                        @if(!empty($opportunity->email))
                                            · {{ $opportunity->email }}
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top text-gray-700">
                                    {{ $opportunityPhone($opportunity) }}
                                </td>

                                <td class="px-4 py-3 align-top text-gray-700">
                                    {{ $opportunityVehicle($opportunity) }}
                                </td>

                                <td class="px-4 py-3 align-top min-w-[230px]">
                                    @if(Route::has('manager.opportunities.stage'))
                                        <form method="POST" action="{{ route('manager.opportunities.stage', $opportunity) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="stage"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach($opportunityStages as $value)
                                                    <option value="{{ $value }}" @selected(($opportunity->stage ?? '') === $value)>
                                                        {{ $stageLabel($value) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ $stageLabel($opportunity->stage ?? 'new') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $statusClass }}">
                                        {{ ucfirst($opportunity->status ?? 'active') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top min-w-[210px]">
                                    @if(Route::has('manager.opportunities.follow-up'))
                                        <form method="POST" action="{{ route('manager.opportunities.follow-up', $opportunity) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="date"
                                                name="follow_up_date"
                                                value="{{ !empty($opportunity->follow_up_date) ? \Carbon\Carbon::parse($opportunity->follow_up_date)->format('Y-m-d') : '' }}"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >

                                            <input type="hidden" name="follow_up_required" value="1">

                                            <button class="rounded-md border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                                Save
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-500">
                                            {{ !empty($opportunity->follow_up_date) ? \Carbon\Carbon::parse($opportunity->follow_up_date)->format('d M Y') : '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top min-w-[190px]">
                                    @if(Route::has('manager.opportunities.assign') && ($managers ?? collect())->count())
                                        <form method="POST" action="{{ route('manager.opportunities.assign', $opportunity) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="assigned_to"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onchange="this.form.submit()"
                                            >
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" @selected((string) $assignedValue($opportunity) === (string) $manager->id)>
                                                        {{ $manager->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        @if(Route::has('manager.opportunities.edit'))
                                            <a href="{{ route('manager.opportunities.edit', $opportunity) }}"
                                               class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Manage
                                            </a>
                                        @endif

                                        @if(Route::has('manager.opportunities.show'))
                                            <a href="{{ route('manager.opportunities.show', $opportunity) }}"
                                               class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                View
                                            </a>
                                        @endif

                                        @if(Route::has('manager.opportunities.mark-won'))
                                            <form method="POST" action="{{ route('manager.opportunities.mark-won', $opportunity) }}" class="inline">
                                                @csrf
                                                @method('PATCH')

                                                <button class="rounded-md border border-green-200 px-3 py-2 text-xs font-semibold text-green-700 hover:bg-green-50">
                                                    Won
                                                </button>
                                            </form>
                                        @endif

                                        @if(Route::has('manager.opportunities.mark-lost'))
                                            <form method="POST" action="{{ route('manager.opportunities.mark-lost', $opportunity) }}" class="inline">
                                                @csrf
                                                @method('PATCH')

                                                <button class="rounded-md border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                    Lost
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            @if(!empty($opportunity->notes) || !empty($opportunity->manager_notes) || !empty($opportunity->internal_notes))
                                <tr class="bg-gray-50">
                                    <td colspan="8" class="px-4 py-3">
                                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Latest Notes
                                        </div>
                                        <div class="mt-1 text-sm text-gray-700">
                                            {{ \Illuminate\Support\Str::limit($opportunity->manager_notes ?? $opportunity->internal_notes ?? $opportunity->notes, 220) }}
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <h3 class="text-base font-semibold text-gray-900">
                    No open opportunities found
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Opportunities needing manager follow-up will appear here.
                </p>
            </div>
        @endif

        @if(method_exists($opportunities, 'links'))
            <div class="px-4 py-4 border-t border-gray-200 bg-white">
                {{ $opportunities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection