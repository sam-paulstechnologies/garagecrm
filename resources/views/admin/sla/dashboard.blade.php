@extends('layouts.app')

@section('title', 'SLA Dashboard')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">

<h1 class="text-2xl font-bold">SLA Dashboard</h1>

{{-- KPI Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Today Conversations</div>
        <div class="text-2xl font-bold">{{ $todayCount }}</div>
    </div>

    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Open Conversations</div>
        <div class="text-2xl font-bold">{{ $openCount }}</div>
    </div>

    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Avg First Response (mins)</div>
        <div class="text-2xl font-bold">{{ $avgFirstResponseMinutes }}</div>
    </div>

    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">SLA Breaches</div>
        <div class="text-2xl font-bold text-red-600">{{ $slaBreaches }}</div>
    </div>
</div>

{{-- AI vs Human --}}
<div class="grid grid-cols-2 gap-4">
    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">AI Replies</div>
        <div class="text-xl font-bold">{{ $aiCount }}</div>
    </div>
    <div class="p-4 bg-white rounded shadow">
        <div class="text-sm text-gray-500">Human Replies</div>
        <div class="text-xl font-bold">{{ $humanCount }}</div>
    </div>
</div>

{{-- Agent Performance --}}
<div class="bg-white rounded shadow p-4">
    <h2 class="font-semibold mb-3">Top Agents</h2>
    <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-3 py-2 text-left">Agent</th>
                <th class="px-3 py-2">Total</th>
                <th class="px-3 py-2">AI</th>
                <th class="px-3 py-2">Outbound</th>
            </tr>
        </thead>
        <tbody>
        @foreach($agentPerformance as $agent)
            <tr class="border-t">
                <td class="px-3 py-2">{{ $agent->name }}</td>
                <td class="px-3 py-2 text-center">{{ $agent->total }}</td>
                <td class="px-3 py-2 text-center">{{ $agent->ai_count }}</td>
                <td class="px-3 py-2 text-center">{{ $agent->outbound }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Ageing --}}
<div class="bg-white rounded shadow p-4">
    <h2 class="font-semibold mb-3">Conversation Ageing</h2>
    <ul class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
        @foreach($ageBuckets as $label => $count)
            <li class="border rounded p-3">
                <div class="text-gray-500">{{ strtoupper(str_replace('_','–',$label)) }} mins</div>
                <div class="text-xl font-bold">{{ $count }}</div>
            </li>
        @endforeach
    </ul>
</div>

</div>
@endsection
