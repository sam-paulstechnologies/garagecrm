@extends('layouts.app')

@section('title', 'WhatsApp Campaigns')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">WhatsApp Campaigns</h1>
            <p class="text-sm text-gray-500 mt-1">
                Create, schedule, pause, resume, and track WhatsApp broadcast campaigns.
            </p>
        </div>

        <a href="{{ route('admin.whatsapp.campaigns.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white">
            + New Campaign
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded bg-red-50 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.whatsapp.campaigns.index') }}" class="mb-4">
        <div class="flex flex-col md:flex-row gap-3">
            <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                placeholder="Search campaign or template..."
                class="w-full md:w-96 border rounded px-3 py-2"
            >

            <button type="submit"
                    class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded">
                Search
            </button>

            @if(!empty($q))
                <a href="{{ route('admin.whatsapp.campaigns.index') }}"
                   class="inline-flex items-center justify-center border px-4 py-2 rounded text-gray-700">
                    Clear
                </a>
            @endif
        </div>
    </form>

    @if($campaigns->isEmpty())
        <div class="border rounded bg-white p-6">
            <p class="text-gray-600">No campaigns yet. Click “New Campaign”.</p>
        </div>
    @else
        <div class="overflow-x-auto border rounded bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-left text-gray-700">
                        <th class="p-3 whitespace-nowrap">Campaign</th>
                        <th class="p-3 whitespace-nowrap">Template</th>
                        <th class="p-3 whitespace-nowrap">Status</th>
                        <th class="p-3 whitespace-nowrap">Scheduled</th>
                        <th class="p-3 whitespace-nowrap">Audience</th>
                        <th class="p-3 whitespace-nowrap">Sent</th>
                        <th class="p-3 whitespace-nowrap">Failed</th>
                        <th class="p-3 whitespace-nowrap">Created</th>
                        <th class="p-3 whitespace-nowrap text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($campaigns as $campaign)
                        @php
                            $statusClass = match($campaign->status) {
                                'draft' => 'bg-gray-100 text-gray-700',
                                'scheduled' => 'bg-blue-100 text-blue-700',
                                'running' => 'bg-green-100 text-green-700',
                                'paused' => 'bg-yellow-100 text-yellow-700',
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp

                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3 align-top">
                                <div class="font-semibold text-gray-900">
                                    {{ $campaign->name }}
                                </div>

                                @if($campaign->description)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ \Illuminate\Support\Str::limit($campaign->description, 80) }}
                                    </div>
                                @endif

                                <div class="text-xs text-gray-400 mt-1">
                                    {{ ucfirst($campaign->type) }} · {{ ucfirst($campaign->channel) }}
                                </div>
                            </td>

                            <td class="p-3 align-top">
                                @if($campaign->template)
                                    <div class="font-medium text-gray-900">
                                        {{ $campaign->template->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $campaign->template->provider_template }}
                                    </div>
                                @else
                                    <span class="text-gray-400">No template</span>
                                @endif
                            </td>

                            <td class="p-3 align-top">
                                <span class="inline-flex px-2 py-1 rounded text-xs {{ $statusClass }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </td>

                            <td class="p-3 align-top whitespace-nowrap">
                                {{ $campaign->scheduled_at?->format('d M Y, h:i A') ?? '—' }}
                            </td>

                            <td class="p-3 align-top">
                                <div class="font-medium">{{ $campaign->audience_count ?? 0 }}</div>
                                <div class="text-xs text-gray-500">
                                    Queued: {{ $campaign->queued_count ?? 0 }}
                                </div>
                            </td>

                            <td class="p-3 align-top">
                                {{ $campaign->sent_count ?? 0 }}
                            </td>

                            <td class="p-3 align-top">
                                {{ $campaign->failed_count ?? 0 }}
                            </td>

                            <td class="p-3 align-top whitespace-nowrap text-gray-500">
                                {{ $campaign->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </td>

                            <td class="p-3 align-top text-right whitespace-nowrap">
                                <a href="{{ route('admin.whatsapp.campaigns.edit', $campaign) }}"
                                   class="text-yellow-600 hover:underline">
                                    Edit
                                </a>

                                <span class="text-gray-300 mx-1">|</span>

                                <form action="{{ route('admin.whatsapp.campaigns.send_now', $campaign) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Queue this campaign to send now?')">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline">
                                        Send Now
                                    </button>
                                </form>

                                @if(in_array($campaign->status, ['scheduled', 'running'], true))
                                    <span class="text-gray-300 mx-1">|</span>

                                    <form action="{{ route('admin.whatsapp.campaigns.pause', $campaign) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 hover:underline">
                                            Pause
                                        </button>
                                    </form>
                                @endif

                                @if($campaign->status === 'paused')
                                    <span class="text-gray-300 mx-1">|</span>

                                    <form action="{{ route('admin.whatsapp.campaigns.resume', $campaign) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:underline">
                                            Resume
                                        </button>
                                    </form>
                                @endif

                                <span class="text-gray-300 mx-1">|</span>

                                <form action="{{ route('admin.whatsapp.campaigns.destroy', $campaign) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Delete this campaign?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection