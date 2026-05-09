@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Leads</h1>
            <p class="text-sm text-gray-500 mt-1">
                View and track lead source, Meta form attribution, and current status.
            </p>
        </div>

        <a href="{{ route('admin.leads.create') }}"
           class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            + New Lead
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded mb-4">
            {{ session('warning') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.leads.index') }}" class="mb-4">
        <div class="flex flex-col md:flex-row gap-3">
            <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Search name, phone, email, source, form ID..."
                class="w-full md:w-96 border rounded px-3 py-2"
            >

            <button type="submit"
                    class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded">
                Search
            </button>

            @if(!empty($q))
                <a href="{{ route('admin.leads.index') }}"
                   class="inline-flex items-center justify-center border px-4 py-2 rounded text-gray-700">
                    Clear
                </a>
            @endif
        </div>
    </form>

    @if($leads->isEmpty())
        <div class="bg-white border rounded p-6 text-gray-600">
            No leads found.
        </div>
    @else
        <div class="overflow-x-auto bg-white border rounded shadow-sm">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-gray-700">
                        <th class="p-3 whitespace-nowrap">Lead</th>
                        <th class="p-3 whitespace-nowrap">Contact</th>
                        <th class="p-3 whitespace-nowrap">Vehicle</th>
                        <th class="p-3 whitespace-nowrap">Source</th>
                        <th class="p-3 whitespace-nowrap">Meta / External</th>
                        <th class="p-3 whitespace-nowrap">Status</th>
                        <th class="p-3 whitespace-nowrap">Assigned</th>
                        <th class="p-3 whitespace-nowrap">Created</th>
                        <th class="p-3 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($leads as $lead)
                        @php
                            $payload = is_array($lead->external_payload) ? $lead->external_payload : [];
                            $webhook = $payload['_webhook'] ?? [];

                            $formName = $lead->leadSource?->configValue('form_name')
                                ?? data_get($webhook, 'form_name');

                            $pageName = $lead->leadSource?->configValue('page_name')
                                ?? data_get($webhook, 'page_name');

                            $sourceLabel = $lead->leadSource?->name
                                ?? $lead->source
                                ?? 'Manual';

                            $externalSource = $lead->external_source ?: '-';
                            $externalFormId = $lead->external_form_id ?: data_get($webhook, 'form_id');
                        @endphp

                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 align-top">
                                <div class="font-semibold text-gray-900">
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="hover:underline">
                                        {{ $lead->name ?? 'Unnamed Lead' }}
                                    </a>
                                </div>

                                @if($lead->is_hot)
                                    <span class="inline-block mt-1 text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded">
                                        Hot
                                    </span>
                                @endif
                            </td>

                            <td class="p-3 align-top">
                                <div>{{ $lead->phone ?: '-' }}</div>
                                <div class="text-gray-500">{{ $lead->email ?: '-' }}</div>
                            </td>

                            <td class="p-3 align-top">
                                {{ $lead->vehicle_label ?? '-' }}
                            </td>

                            <td class="p-3 align-top">
                                <div class="font-medium text-gray-900">{{ $sourceLabel }}</div>

                                @if($lead->leadSource)
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($lead->leadSource->type) }} · {{ ucfirst($lead->leadSource->status) }}
                                    </div>
                                @endif
                            </td>

                            <td class="p-3 align-top">
                                <div class="text-gray-900">
                                    {{ strtoupper((string) $externalSource) }}
                                </div>

                                @if($pageName)
                                    <div class="text-xs text-gray-500">
                                        Page: {{ $pageName }}
                                    </div>
                                @endif

                                @if($formName)
                                    <div class="text-xs text-gray-500">
                                        Form: {{ $formName }}
                                    </div>
                                @endif

                                @if($externalFormId)
                                    <div class="text-xs text-gray-400">
                                        ID: {{ $externalFormId }}
                                    </div>
                                @endif
                            </td>

                            <td class="p-3 align-top">
                                <span class="inline-flex px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">
                                    {{ $lead->status_label }}
                                </span>
                            </td>

                            <td class="p-3 align-top">
                                {{ $lead->assignee?->name ?? 'Unassigned' }}
                            </td>

                            <td class="p-3 align-top text-gray-500 whitespace-nowrap">
                                {{ optional($lead->created_at)->format('d M Y, h:i A') }}
                            </td>

                            <td class="p-3 align-top text-right whitespace-nowrap">
                                <a href="{{ route('admin.leads.show', $lead) }}"
                                   class="text-blue-600 hover:underline">
                                    View
                                </a>

                                <span class="text-gray-300 mx-1">|</span>

                                <a href="{{ route('admin.leads.edit', $lead) }}"
                                   class="text-yellow-600 hover:underline">
                                    Edit
                                </a>

                                <span class="text-gray-300 mx-1">|</span>

                                <form action="{{ route('admin.leads.destroy', $lead) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Archive/disqualify this lead?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">
                                        Archive
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leads->links() }}
        </div>
    @endif
</div>
@endsection