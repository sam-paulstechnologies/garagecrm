@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Opportunity Details</h1>
        <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
            ✏️ Edit Opportunity
        </a>
    </div>

    <div class="bg-white shadow rounded-lg divide-y divide-gray-200">
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
            <div><strong>Opportunity ID:</strong> {{ $opportunity->id }}</div>
            <div><strong>Company ID:</strong> {{ $opportunity->company_id }}</div>
            <div><strong>Client:</strong> {{ $opportunity->client->name ?? 'N/A' }}</div>
            <div><strong>Lead:</strong> {{ $opportunity->lead->name ?? '—' }}</div>
            <div><strong>Title:</strong> {{ $opportunity->title }}</div>
            <div><strong>Stage:</strong> {{ ucfirst($opportunity->stage) ?? '—' }}</div>
            <div><strong>Service Type(s):</strong>
                @if($opportunity->service_type)
                    <div class="mt-1 flex flex-wrap gap-2">
                        @foreach(explode(',', $opportunity->service_type) as $service)
                            <span class="inline-block bg-gray-200 text-xs rounded px-2 py-1">{{ $service }}</span>
                        @endforeach
                    </div>
                @else
                    —
                @endif
            </div>
            <div><strong>Value:</strong> {{ number_format($opportunity->value ?? 0, 2) }} AED</div>
            <div><strong>Expected Close Date:</strong> {{ $opportunity->expected_close_date ?? '—' }}</div>
            <div><strong>Score:</strong> {{ $opportunity->score ?? '—' }}</div>
            <div><strong>Priority:</strong> {{ ucfirst($opportunity->priority ?? '—') }}</div>
            <div><strong>Is Converted:</strong> {{ $opportunity->is_converted ? 'Yes' : 'No' }}</div>
            <div><strong>Close Reason:</strong> {{ $opportunity->close_reason ?? '—' }}</div>
            <div><strong>Next Follow-up:</strong> {{ $opportunity->next_follow_up ?? '—' }}</div>
            <div><strong>Expected Duration (days):</strong> {{ $opportunity->expected_duration ?? '—' }}</div>
            <div><strong>Assigned To:</strong> {{ $opportunity->assignedUser->name ?? '—' }}</div>
            <div><strong>Source:</strong> {{ $opportunity->source ?? '—' }}</div>

            <!-- ✅ Vehicle Make & Model with support for manual entry -->
            <div><strong>Vehicle Make:</strong>
                @if($opportunity->vehicle_make_id)
                    {{ $opportunity->vehicleMake->name ?? '—' }}
                @elseif($opportunity->other_make)
                    <span class="text-blue-600">Other:</span> {{ $opportunity->other_make }}
                @else
                    —
                @endif
            </div>

            <div><strong>Vehicle Model:</strong>
                @if($opportunity->vehicle_model_id)
                    {{ $opportunity->vehicleModel->name ?? '—' }}
                @elseif($opportunity->other_model)
                    <span class="text-blue-600">Other:</span> {{ $opportunity->other_model }}
                @else
                    —
                @endif
            </div>

            <div class="md:col-span-2"><strong>Notes:</strong> {{ $opportunity->notes ?? '—' }}</div>
            <div><strong>Created At:</strong> {{ $opportunity->created_at?->format('d M Y, h:i A') ?? '—' }}</div>
            <div><strong>Last Updated:</strong> {{ $opportunity->updated_at?->format('d M Y, h:i A') ?? '—' }}</div>
        </div>

        <div class="p-4">
            <a href="{{ route('admin.opportunities.index') }}"
               class="inline-block px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                ← Back to List
            </a>
        </div>
    </div>
</div>
@endsection
