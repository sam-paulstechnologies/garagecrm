@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-8 space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Lead Details</h1>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.edit', $lead) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
                ✏️ Edit Lead
            </a>

            <form method="POST" action="{{ route('admin.leads.toggleHot', $lead) }}">
                @csrf
                @method('PATCH')
                <button class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded shadow">
                    {{ $lead->is_hot ? '🔥 Unmark Hot' : '⭐ Mark Hot' }}
                </button>
            </form>

            <form method="POST" action="{{ route('admin.leads.touch', $lead) }}">
                @csrf
                @method('PATCH')
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded shadow">
                    ☎️ Touch Contacted
                </button>
            </form>

            <form method="POST" action="{{ route('admin.leads.convert', $lead) }}">
                @csrf
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded shadow">
                    🔁 Convert to Opportunity
                </button>
            </form>
        </div>
    </div>

    {{-- ================= DETAILS ================= --}}
    <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">

            <div><strong>Name:</strong> {{ $lead->name }}</div>
            <div><strong>Email:</strong> {{ $lead->email ?? '—' }}</div>
            <div><strong>Phone:</strong> {{ $lead->phone ?? '—' }}</div>

            <div>
                <strong>Status:</strong>
                {{ ucfirst($lead->status) }}

                @if($lead->status === 'converted')
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">
                        Auto
                    </span>
                @elseif($lead->status === 'contact_on_hold')
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">
                        Manager
                    </span>
                @endif
            </div>

            <div><strong>Source:</strong> {{ $lead->source ?? '—' }}</div>
            <div><strong>Assigned To:</strong> {{ $lead->assignee?->name ?? '—' }}</div>
            <div><strong>Preferred Channel:</strong> {{ $lead->preferred_channel ?? '—' }}</div>
            <div><strong>Last Contacted:</strong> {{ $lead->last_contacted_at?->format('d/m/Y H:i') ?? '—' }}</div>
            <div><strong>Is Hot:</strong> {{ $lead->is_hot ? 'Yes' : 'No' }}</div>
            <div><strong>Lead Score Reason:</strong> {{ $lead->lead_score_reason ?? '—' }}</div>
            <div class="md:col-span-2"><strong>Notes:</strong> {{ $lead->notes ?? '—' }}</div>
            <div><strong>Score:</strong> {{ $lead->score ?? 0 }}</div>
            <div><strong>Created:</strong> {{ $lead->created_at->format('d/m/Y H:i') }}</div>

        </div>
    </div>

    {{-- ================= COMMUNICATIONS ================= --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-semibold mb-3">Communications</h2>

        @php
            $communications = \App\Models\Shared\Communication::where('lead_id', $lead->id)
                ->latest()
                ->paginate(10);
        @endphp

        @if($communications->count())
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Date</th>
                        <th class="px-3 py-2 text-left">Type</th>
                        <th class="px-3 py-2 text-left">Content</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($communications as $c)
                        <tr>
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($c->communication_date)->format('d M Y, h:i A') }}</td>
                            <td class="px-3 py-2">{{ $c->communication_type }}</td>
                            <td class="px-3 py-2">{{ Str::limit($c->content, 120) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $communications->links() }}
            </div>
        @else
            <p class="text-sm text-gray-500">No communications yet.</p>
        @endif
    </div>

</div>
@endsection
