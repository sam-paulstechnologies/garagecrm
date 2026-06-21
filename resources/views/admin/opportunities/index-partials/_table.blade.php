{{-- resources/views/admin/opportunities/index-partials/_table.blade.php --}}

@php
    $phoneService = app(\App\Services\PhoneNumberService::class);
@endphp

<div class="sf-opportunity-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="sf-table-scroll">
        <table class="sf-table sf-opportunity-table min-w-full table-fixed divide-y divide-slate-800 text-sm">
            <thead>
                <tr>
                    <th class="w-[28%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Opportunity</th>
                    <th class="w-[18%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Client / Vehicle</th>
                    <th class="w-[14%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Stage</th>
                    <th class="w-[9%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Priority</th>
                    <th class="w-[10%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Value</th>
                    <th class="w-[9%] px-4 py-3 text-left text-xs font-black uppercase tracking-wide">Date</th>
                    <th class="w-[12%] px-4 py-3 text-right text-xs font-black uppercase tracking-wide">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
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

                        $opportunityPhone = $opportunity->client?->phone
                            ?? $opportunity->lead?->phone
                            ?? null;
                        $opportunityPhoneDisplay = $opportunityPhone
                            ? $phoneService->formatForDisplay($opportunityPhone)
                            : null;
                        $opportunityTelUrl = $opportunityPhone
                            ? $phoneService->buildTelUrl($opportunityPhone)
                            : null;
                    @endphp

                    <tr class="transition hover:bg-slate-800/30">
                        <td class="px-4 py-4 align-top" data-label="Opportunity">
                            <div class="min-w-0">
                                @if(Route::has('admin.opportunities.show'))
                                    <a href="{{ route('admin.opportunities.show', $opportunity) }}" class="sf-opportunity-name-link font-extrabold hover:text-orange-400">
                                        {{ $opportunity->title ?? 'Untitled Opportunity' }}
                                    </a>
                                @else
                                    <div class="font-extrabold sf-opportunity-value">
                                        {{ $opportunity->title ?? 'Untitled Opportunity' }}
                                    </div>
                                @endif

                                <div class="mt-1 text-sm font-bold sf-opportunity-value">
                                    @if($opportunityTelUrl)
                                        <a href="{{ $opportunityTelUrl }}" class="sf-link break-all">
                                            {{ $opportunityPhoneDisplay }}
                                        </a>
                                    @else
                                        <span class="sf-opportunity-muted">No phone</span>
                                    @endif
                                </div>

                                <div class="mt-1 text-xs font-medium sf-opportunity-muted">
                                    #{{ $opportunity->id }}

                                    @if($opportunity->source)
                                        &middot; {{ $opportunity->source }}
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top" data-label="Client / Vehicle">
                            <div class="font-bold sf-opportunity-value">
                                {{ $opportunity->client?->name ?? 'No client' }}
                            </div>

                            <div class="mt-1 text-xs font-medium sf-opportunity-muted">
                                {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top" data-label="Stage">
                            <span class="{{ $stageBadge($opportunity->stage) }}">
                                {{ $stageLabel($opportunity->stage) }}
                            </span>
                        </td>

                        <td class="px-4 py-4 align-top" data-label="Priority">
                            <span class="{{ $priorityBadge($opportunity->priority ?? 'medium') }}">
                                {{ ucfirst($opportunity->priority ?? 'Medium') }}
                            </span>
                        </td>

                        <td class="px-4 py-4 align-top" data-label="Value">
                            <div class="font-extrabold sf-opportunity-money">
                                AED {{ number_format((float) $value, 2) }}
                            </div>
                        </td>

                        <td class="px-4 py-4 align-top" data-label="Date">
                            <div class="font-bold sf-opportunity-value">
                                {{ optional($opportunity->expected_close_date)->format('d M Y') ?? optional($opportunity->created_at)->format('d M Y') ?? '-' }}
                            </div>

                            <div class="text-xs sf-opportunity-muted">
                                {{ optional($opportunity->created_at)->format('h:i A') ?? '' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 text-right align-top" data-label="Actions">
                            <div class="sf-opportunity-action-group">
                                @if(Route::has('admin.opportunities.show'))
                                    <a href="{{ route('admin.opportunities.show', $opportunity) }}" class="sf-opportunity-action-pill">
                                        View
                                    </a>
                                @endif

                                @if(Route::has('admin.opportunities.edit'))
                                    <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="sf-opportunity-action-pill">
                                        Edit
                                    </a>
                                @endif

                                @if(Route::has('admin.opportunities.destroy') && ! ($opportunity->is_archived ?? false))
                                    <form method="POST" action="{{ route('admin.opportunities.destroy', $opportunity) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sf-opportunity-action-pill sf-opportunity-action-pill-danger">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10">
                            <div class="sf-opportunity-soft-panel rounded-2xl border p-8 text-center">
                                No opportunities found.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
