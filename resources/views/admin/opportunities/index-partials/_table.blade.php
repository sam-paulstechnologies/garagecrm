{{-- resources/views/admin/opportunities/index-partials/_table.blade.php --}}

<div class="sf-opportunity-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="sf-table-scroll">
        <table class="sf-table sf-opportunity-table">
            <thead>
                <tr>
                    <th class="w-[26%]">Opportunity</th>
                    <th class="w-[20%]">Client / Vehicle</th>
                    <th class="w-[14%]">Stage</th>
                    <th class="w-[12%]">Priority</th>
                    <th class="w-[12%]">Value</th>
                    <th class="w-[10%]">Date</th>
                    <th class="w-[6%] text-right">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($opportunities as $opportunity)
                    @php
                        $vehicleLabel = trim(
                            ($opportunity->vehicleMake?->name ?? $opportunity->other_make ?? '') . ' ' .
                            ($opportunity->vehicleModel?->name ?? $opportunity->other_model ?? '')
                        );

                        $value = $opportunity->value
                            ?? $opportunity->estimated_value
                            ?? $opportunity->amount
                            ?? 0;
                    @endphp

                    <tr>
                        <td>
                            <div class="font-extrabold sf-opportunity-value">
                                {{ $opportunity->title ?? 'Untitled Opportunity' }}
                            </div>

                            <div class="mt-1 text-xs font-medium sf-opportunity-muted">
                                #{{ $opportunity->id }}

                                @if($opportunity->source)
                                    &middot; {{ $opportunity->source }}
                                @endif
                            </div>
                        </td>

                        <td>
                            <div class="font-bold sf-opportunity-value">
                                {{ $opportunity->client?->name ?? 'No client' }}
                            </div>

                            <div class="mt-1 text-xs font-medium sf-opportunity-muted">
                                {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}
                            </div>
                        </td>

                        <td>
                            <span class="{{ $stageBadge($opportunity->stage) }}">
                                {{ $stageLabel($opportunity->stage) }}
                            </span>
                        </td>

                        <td>
                            <span class="{{ $priorityBadge($opportunity->priority ?? 'medium') }}">
                                {{ ucfirst($opportunity->priority ?? 'Medium') }}
                            </span>
                        </td>

                        <td>
                            <div class="font-extrabold text-orange-300">
                                AED {{ number_format((float) $value, 2) }}
                            </div>
                        </td>

                        <td>
                            <div class="font-bold sf-opportunity-value">
                                {{ optional($opportunity->expected_close_date)->format('d M Y') ?? optional($opportunity->created_at)->format('d M Y') ?? '-' }}
                            </div>

                            <div class="text-xs sf-opportunity-muted">
                                {{ optional($opportunity->created_at)->format('h:i A') ?? '' }}
                            </div>
                        </td>

                        <td class="text-right">
                            @if(Route::has('admin.opportunities.show'))
                                <a href="{{ route('admin.opportunities.show', $opportunity) }}" class="sf-link">
                                    View
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="sf-empty">
                                No opportunities found.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>