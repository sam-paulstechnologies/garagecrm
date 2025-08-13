@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Lead Details</h1>
        <a href="{{ route('admin.leads.edit', $lead) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
            ✏️ Edit Lead
        </a>
    </div>

    <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
            <div><strong>Name:</strong> {{ $lead->name }}</div>
            <div><strong>Email:</strong> {{ $lead->email }}</div>
            <div><strong>Phone:</strong> {{ $lead->phone ?? '—' }}</div>
            <div><strong>Status:</strong> {{ ucfirst($lead->status) }}</div>
            <div><strong>Source:</strong> {{ $lead->source ?? '—' }}</div>
            <div><strong>Assigned To:</strong> {{ $lead->assigned_to ?? '—' }}</div>
            <div><strong>Preferred Channel:</strong> {{ ucfirst($lead->preferred_channel ?? '—') }}</div>
            <div><strong>Last Contacted:</strong> {{ $lead->last_contacted_at?->format('d/m/Y, H:i') ?? '—' }}</div>
            <div><strong>Is Hot:</strong> {{ $lead->is_hot ? 'Yes' : 'No' }}</div>
            <div><strong>Lead Score Reason:</strong> {{ $lead->lead_score_reason ?? '—' }}</div>
            <div><strong>Notes:</strong> {{ $lead->notes ?? '—' }}</div>
            <div><strong>Score:</strong> {{ $lead->score ?? 0 }}</div>
        </div>
        <div class="p-4 text-sm text-gray-500">
            Created on: {{ $lead->created_at->format('d/m/Y, H:i') }}
        </div>
    </div>
</div>
@endsection
