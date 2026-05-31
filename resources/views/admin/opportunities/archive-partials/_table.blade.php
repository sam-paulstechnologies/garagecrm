<div class="sf-opportunity-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="sf-table-scroll">
        <table class="sf-table sf-opportunity-table">
            <thead>
                <tr>
                    <th class="w-[26%]">Title</th>
                    <th class="w-[18%]">Client</th>
                    <th class="w-[14%]">Stage</th>
                    <th class="w-[12%]">Priority</th>
                    <th class="w-[12%]">Value</th>
                    <th class="w-[12%]">Deleted At</th>
                    <th class="w-[6%] text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($opportunities as $opportunity)
                    <tr>
                        <td>
                            <div class="font-extrabold sf-opportunity-value">{{ $opportunity->title ?? 'Untitled Opportunity' }}</div>
                            <div class="mt-1 text-xs font-medium sf-opportunity-muted">Opportunity ID: #{{ $opportunity->id }}</div>
                        </td>

                        <td>
                            <div class="font-bold sf-opportunity-value">{{ $opportunity->client->name ?? 'N/A' }}</div>
                            @if(!empty($opportunity->client?->phone))
                                <div class="mt-1 text-xs font-medium sf-opportunity-muted">{{ $opportunity->client->phone }}</div>
                            @endif
                        </td>

                        <td><span class="{{ $stageBadge($opportunity->stage) }}">{{ ucfirst(str_replace('_', ' ', $opportunity->stage ?? '-')) }}</span></td>
                        <td><span class="{{ $priorityBadge($opportunity->priority) }}">{{ ucfirst($opportunity->priority ?? '-') }}</span></td>
                        <td><div class="font-extrabold text-orange-300">AED {{ number_format((float) ($opportunity->value ?? 0), 2) }}</div></td>
                        <td>
                            <div class="font-bold sf-opportunity-value">{{ $opportunity->deleted_at?->format('d M Y') ?? '-' }}</div>
                            <div class="text-xs sf-opportunity-muted">{{ $opportunity->deleted_at?->format('h:i A') ?? '' }}</div>
                        </td>

                        <td class="text-right">
                            <form action="{{ route('admin.opportunities.restore', $opportunity->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Restore this opportunity?');">
                                @csrf
                                @method('PUT')

                                <button type="submit" class="sf-link">Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><div class="sf-empty">No archived opportunities found.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
