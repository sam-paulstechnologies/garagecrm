@extends('layouts.admin')

@section('content')

<div class="px-6 py-4 space-y-6">

    <x-admin.page-header>
        SLA Dashboard
    </x-admin.page-header>

    {{-- KPI Tiles --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <x-admin.card class="p-4 text-center bg-sky-50">
            <div class="text-xs text-gray-500">Conversations Today</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $todayCount }}</div>
        </x-admin.card>

        <x-admin.card class="p-4 text-center bg-green-50">
            <div class="text-xs text-gray-500">Open Conversations</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $openCount }}</div>
        </x-admin.card>

        <x-admin.card class="p-4 text-center bg-amber-50">
            <div class="text-xs text-gray-500">Avg First Response</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $avgFirstResponseMinutes }} mins</div>
        </x-admin.card>

        <x-admin.card class="p-4 text-center bg-red-50">
            <div class="text-xs text-gray-500">SLA Breaches</div>
            <div class="text-2xl font-semibold text-gray-800">{{ $slaBreaches }}</div>
        </x-admin.card>

    </div>

    {{-- AI vs Human --}}
    <x-admin.card class="p-6">
        <h3 class="font-semibold text-gray-700 mb-4">AI vs Human Messages</h3>
        <div class="flex gap-6">

            <div class="p-4 bg-blue-50 rounded w-40 text-center">
                <div class="text-xs text-gray-500">AI Messages</div>
                <div class="text-xl font-bold text-gray-800">{{ $aiCount }}</div>
            </div>

            <div class="p-4 bg-gray-50 rounded w-40 text-center">
                <div class="text-xs text-gray-500">Human Messages</div>
                <div class="text-xl font-bold text-gray-800">{{ $humanCount }}</div>
            </div>

        </div>
    </x-admin.card>

    {{-- Agent Leaderboard --}}
    <x-admin.card class="p-6">
        <h3 class="font-semibold text-gray-700 mb-4">Agent Performance</h3>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-500 text-xs">
                    <th class="text-left py-2">Agent</th>
                    <th>Total</th>
                    <th>AI Used</th>
                    <th>Outbound</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agentPerformance as $row)
                <tr class="border-t">
                    <td class="py-2">{{ $row->name }}</td>
                    <td class="text-center">{{ $row->total }}</td>
                    <td class="text-center">{{ $row->ai_count }}</td>
                    <td class="text-center">{{ $row->outbound }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-admin.card>

    {{-- Ageing Buckets --}}
    <x-admin.card class="p-6">
        <h3 class="font-semibold text-gray-700 mb-4">Conversation Ageing</h3>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">

            <div class="bg-slate-50 p-4 rounded text-center">
                <div class="text-xs text-gray-500">0–15 mins</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['0_15'] }}</div>
            </div>

            <div class="bg-slate-50 p-4 rounded text-center">
                <div class="text-xs text-gray-500">15–60 mins</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['15_60'] }}</div>
            </div>

            <div class="bg-slate-50 p-4 rounded text-center">
                <div class="text-xs text-gray-500">1–3 hrs</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['1_3h'] }}</div>
            </div>

            <div class="bg-slate-50 p-4 rounded text-center">
                <div class="text-xs text-gray-500">3–24 hrs</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['3_24h'] }}</div>
            </div>

            <div class="bg-slate-50 p-4 rounded text-center">
                <div class="text-xs text-gray-500">1–3 days</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['1_3d'] }}</div>
            </div>

            <div class="bg-slate-100 p-4 rounded text-center">
                <div class="text-xs text-gray-500">3+ days</div>
                <div class="text-xl font-semibold">{{ $ageBuckets['3d_plus'] }}</div>
            </div>

        </div>
    </x-admin.card>

</div>

@endsection
