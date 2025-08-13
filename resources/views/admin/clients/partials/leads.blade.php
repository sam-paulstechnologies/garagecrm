<h3 class="text-xl font-semibold text-gray-800 mb-2">Leads</h3>
@forelse ($client->leads ?? [] as $lead)
    <p class="text-gray-700">• {{ $lead->title ?? 'Untitled Lead' }} <span class="text-sm text-gray-500">({{ ucfirst($lead->status) }})</span></p>
@empty
    <p class="text-gray-500">No leads found.</p>
@endforelse
