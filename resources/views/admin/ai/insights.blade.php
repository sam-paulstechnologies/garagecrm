@extends('layouts.app')

@section('title', 'AI Monitoring & Insights')

@section('content')
<div class="container mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">AI Monitoring & Insights</h1>
        <div class="space-x-2">
            <a href="{{ route('admin.ai.insights',['days'=>7]) }}" class="px-3 py-1 border rounded {{ $days==7 ? 'bg-gray-200' : '' }}">7d</a>
            <a href="{{ route('admin.ai.insights',['days'=>30]) }}" class="px-3 py-1 border rounded {{ $days==30 ? 'bg-gray-200' : '' }}">30d</a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4">
        <div class="p-4 bg-white rounded shadow">
            <div class="text-sm text-gray-500">AI vs Template (Out %)</div>
            <div class="mt-2 text-3xl font-bold">{{ $pctAIText }}% / {{ $pctTemplate }}%</div>
            <div class="text-xs text-gray-500">AI Text / Template</div>
        </div>
        <div class="p-4 bg-white rounded shadow">
            <div class="text-sm text-gray-500">Human Outbound (%)</div>
            <div class="mt-2 text-3xl font-bold">{{ $pctHuman }}%</div>
            <div class="text-xs text-gray-500">of {{ $totalOut }} outs</div>
        </div>
        <div class="p-4 bg-white rounded shadow">
            <div class="text-sm text-gray-500">Avg AI Confidence</div>
            <div class="mt-2 text-3xl font-bold">{{ $avgConfidence }}</div>
            <div class="text-xs text-gray-500">since {{ $since->format('d M') }}</div>
        </div>
        <div class="p-4 bg-white rounded shadow">
            <div class="text-sm text-gray-500">Outbound breakdown</div>
            <div class="mt-2 text-sm">
                <div>Template: <strong>{{ $templateOut }}</strong></div>
                <div>AI Text:  <strong>{{ $aiTextOut }}</strong></div>
                <div>Human:    <strong>{{ $humanTextOut }}</strong></div>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded shadow">
        <div class="border-b px-4 py-3 font-semibold">Manager Alerts (latest 100)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 text-left text-sm">
                    <tr>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">To</th>
                        <th class="px-4 py-2">Lead</th>
                        <th class="px-4 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($alerts as $a)
                    <tr>
                        <td class="px-4 py-2">{{ $a->created_at->format('d M H:i') }}</td>
                        <td class="px-4 py-2">{{ $a->to_number }}</td>
                        <td class="px-4 py-2">#{{ $a->lead_id ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $a->provider_status ?? 'sent' }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-3 text-gray-500" colspan="4">No alerts in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
