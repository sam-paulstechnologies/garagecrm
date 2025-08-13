<h3 class="text-xl font-semibold text-gray-800 mb-2">Opportunities</h3>
@forelse ($client->opportunities ?? [] as $opportunity)
    <p class="text-gray-700">• {{ $opportunity->title ?? 'Untitled Opportunity' }} <span class="text-sm text-gray-500">({{ ucfirst($opportunity->stage) }})</span></p>
@empty
    <p class="text-gray-500">No opportunities found.</p>
@endforelse
