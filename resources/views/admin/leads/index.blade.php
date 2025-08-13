@extends('layouts.app')

@section('content')
<div class="px-6 py-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Lead Management</h2>
        <a href="{{ route('admin.leads.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
            + Add Lead
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white shadow rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Phone</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Source</th>
                    <th class="px-4 py-2">Preferred Channel</th>
                    <th class="px-4 py-2">Is Hot</th>
                    <th class="px-4 py-2">Score</th>
                    <th class="px-4 py-2">Score Reason</th>
                    <th class="px-4 py-2">Assigned To</th>
                    <th class="px-4 py-2">Last Contact</th>
                    <th class="px-4 py-2">Created At</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($leads as $index => $lead)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $index + 1 }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.leads.show', $lead) }}" class="text-blue-600 hover:underline">
                                {{ $lead->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2">{{ $lead->email ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $lead->phone ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($lead->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $lead->source ?? '—' }}</td>
                        <td class="px-4 py-2">{{ ucfirst($lead->preferred_channel) ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $lead->is_hot ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2">{{ $lead->score ?? 0 }}</td>
                        <td class="px-4 py-2">{{ $lead->lead_score_reason ?? '—' }}</td>
                        <td class="px-4 py-2">{{ optional($lead->assignedUser)->name ?? '—' }}</td>
                        <td class="px-4 py-2">
                            {{ $lead->last_contacted_at ? $lead->last_contacted_at->format('d/m/Y, H:i') : '—' }}
                        </td>
                        <td class="px-4 py-2">
                            {{ $lead->created_at ? $lead->created_at->format('d/m/Y, H:i') : '—' }}
                        </td>
                        <td class="px-4 py-2">
                            <a href="{{ route('admin.leads.edit', $lead->id) }}" class="text-yellow-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="px-4 py-6 text-center text-gray-400">No leads available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
