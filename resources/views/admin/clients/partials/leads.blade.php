<h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center justify-between">
    <span>Leads</span>

    <span class="flex items-center gap-3">
        @if (\Illuminate\Support\Facades\Route::has('admin.leads.index'))
            <a href="{{ route('admin.leads.index', ['client_id' => $client->id]) }}"
               class="text-sm text-indigo-600 hover:underline">View all</a>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('admin.leads.create'))
            <a href="{{ route('admin.leads.create', ['client_id' => $client->id]) }}"
               class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">+ Add Lead</a>
        @endif
    </span>
</h3>

@php
    // Latest 3 leads for this client
    $leads = method_exists($client, 'leads')
        ? $client->leads()->latest()->take(3)->get()
        : collect();
@endphp

@forelse ($leads as $lead)
    <div class="py-2 text-sm">
        @if (\Illuminate\Support\Facades\Route::has('admin.leads.show'))
            <a href="{{ route('admin.leads.show', $lead->id) }}" class="hover:underline">
                • {{ $lead->name ?? 'Untitled Lead' }}
            </a>
        @else
            • {{ $lead->name ?? 'Untitled Lead' }}
        @endif
        @if(!empty($lead->status))
            <span class="text-gray-500">({{ ucfirst($lead->status) }})</span>
        @endif
    </div>
@empty
    <p class="text-sm text-gray-500">No leads yet.</p>
@endforelse
