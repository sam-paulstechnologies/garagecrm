@extends('layouts.manager')

@section('title', 'Manager Leads')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $leadStatuses = [
        'New',
        'Attempting Contact',
        'Contacted',
        'Qualified',
        'Disqualified',
        'On Hold',
        'Assigned',
    ];

    $leadName = function ($lead) {
        return $lead->name
            ?? $lead->full_name
            ?? $lead->customer_name
            ?? $lead->client_name
            ?? 'Lead #' . $lead->id;
    };

    $leadPhone = function ($lead) {
        return $lead->phone
            ?? $lead->mobile
            ?? $lead->phone_number
            ?? $lead->whatsapp_number
            ?? '-';
    };

    $leadVehicle = function ($lead) {
        $make = $lead->vehicle_make ?? $lead->make ?? null;
        $model = $lead->vehicle_model ?? $lead->model ?? null;

        return trim(($make ?? '') . ' ' . ($model ?? '')) ?: '-';
    };

    $assignedValue = function ($lead) {
        return $lead->assigned_to
            ?? $lead->assigned_to_id
            ?? $lead->assigned_user_id
            ?? $lead->manager_id
            ?? $lead->user_id
            ?? null;
    };
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                Leads
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Manager action queue for open leads, follow-ups, and qualification.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}"
           class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Back to Dashboard
        </a>
    </div>

    {{-- Alerts --}}
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

    {{-- Filters --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
        <div class="p-4">
            <form method="GET" action="{{ route('manager.leads.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
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
                        Status
                    </label>
                    <select
                        name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        @foreach($leadStatuses as $item)
                            <option value="{{ $item }}" @selected(($status ?? request('status')) === $item)>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Source
                    </label>
                    <select
                        name="source"
                        class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Sources</option>
                        @foreach(($sources ?? collect()) as $item)
                            <option value="{{ $item }}" @selected(($source ?? request('source')) === $item)>
                                {{ ucfirst($item) }}
                            </option>
                        @endforeach
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

    {{-- Leads Table --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold text-gray-900">
                    Open Leads
                </h2>
                <p class="text-sm text-gray-500">
                    Showing manager-actionable leads only.
                </p>
            </div>

            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                {{ method_exists($leads, 'total') ? $leads->total() : $leads->count() }} lead(s)
            </span>
        </div>

        @if($leads->count())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Lead</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Contact</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Vehicle</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Source</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Follow-up</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Assign</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($leads as $lead)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-gray-900">
                                        {{ $leadName($lead) }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        #{{ $lead->id }}
                                        @if(!empty($lead->email))
                                            · {{ $lead->email }}
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="text-gray-900">
                                        {{ $leadPhone($lead) }}
                                    </div>
                                    @if(!empty($lead->preferred_channel))
                                        <div class="text-xs text-gray-500 mt-1">
                                            Preferred: {{ $lead->preferred_channel }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top text-gray-700">
                                    {{ $leadVehicle($lead) }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                        {{ $lead->source ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top min-w-[180px]">
                                    @if(Route::has('manager.leads.status'))
                                        <form method="POST" action="{{ route('manager.leads.status', $lead) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="status"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach($leadStatuses as $item)
                                                    <option value="{{ $item }}" @selected(($lead->status ?? '') === $item)>
                                                        {{ $item }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                            {{ $lead->status ?? 'New' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top min-w-[210px]">
                                    @if(Route::has('manager.leads.follow-up'))
                                        <form method="POST" action="{{ route('manager.leads.follow-up', $lead) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="date"
                                                name="follow_up_date"
                                                value="{{ !empty($lead->follow_up_date) ? \Carbon\Carbon::parse($lead->follow_up_date)->format('Y-m-d') : '' }}"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >

                                            <input type="hidden" name="follow_up_required" value="1">

                                            <button class="rounded-md border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                                Save
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-gray-500">
                                            {{ !empty($lead->follow_up_date) ? \Carbon\Carbon::parse($lead->follow_up_date)->format('d M Y') : '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 align-top min-w-[190px]">
                                    @if(Route::has('manager.leads.assign') && ($managers ?? collect())->count())
                                        <form method="POST" action="{{ route('manager.leads.assign', $lead) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="assigned_to"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onchange="this.form.submit()"
                                            >
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" @selected((string) $assignedValue($lead) === (string) $manager->id)>
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
                                        @if(Route::has('manager.leads.show'))
                                            <a href="{{ route('manager.leads.show', $lead) }}"
                                               class="rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                View
                                            </a>
                                        @endif

                                        @if(Route::has('manager.conversation'))
                                            <a href="{{ route('manager.conversation', $lead) }}"
                                               class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Conversation
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            @if(!empty($lead->notes) || !empty($lead->manager_notes) || !empty($lead->internal_notes))
                                <tr class="bg-gray-50">
                                    <td colspan="8" class="px-4 py-3">
                                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                            Latest Notes
                                        </div>
                                        <div class="mt-1 text-sm text-gray-700">
                                            {{ \Illuminate\Support\Str::limit($lead->manager_notes ?? $lead->internal_notes ?? $lead->notes, 220) }}
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
                    No open leads found
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Leads needing manager action will appear here.
                </p>
            </div>
        @endif

        @if(method_exists($leads, 'links'))
            <div class="px-4 py-4 border-t border-gray-200 bg-white">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection