@php
/**
 * ------------------------------------------------------------
 * Communications – Client scoped
 * ------------------------------------------------------------
 */

$communications = \App\Models\Shared\Communication::where('company_id', company_id())
    ->where('client_id', $client->id)
    ->orderByDesc('communication_date')
    ->orderByDesc('id')
    ->limit(10)
    ->get();
@endphp

<div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-semibold">Communications</h2>

    <div class="flex gap-3 text-sm">
        <a href="{{ route('admin.communications.create', ['client_id' => $client->id]) }}"
           class="text-blue-600 underline">
            Add Communication
        </a>

        <a href="{{ route('admin.communications.index', ['client_id' => $client->id]) }}"
           class="text-blue-600 underline">
            Open Log
        </a>
    </div>
</div>

@if($communications->isEmpty())
    <p class="text-sm text-gray-500">No communications yet.</p>
@else
    <div class="space-y-2">
        @foreach($communications as $comm)
            <div class="border rounded p-3 text-sm">
                <div class="flex justify-between">
                    <strong>{{ ucfirst($comm->communication_type) }}</strong>
                    <span class="text-xs text-gray-500">
                        {{ optional($comm->communication_date)->format('d M Y H:i') ?? '—' }}
                    </span>
                </div>

                <div class="text-gray-700 mt-1">
                    {{ Str::limit($comm->content, 140) }}
                </div>

                @if($comm->follow_up_required)
                    <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-800">
                        Follow-up required
                    </span>
                @endif
            </div>
        @endforeach
    </div>
@endif
