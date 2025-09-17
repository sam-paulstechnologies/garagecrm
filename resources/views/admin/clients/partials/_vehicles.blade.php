<h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center justify-between">
    <span>Vehicles</span>

    @if (\Illuminate\Support\Facades\Route::has('admin.vehicles.create'))
        <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
           class="inline-flex items-center px-3 py-2 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
            + Add Vehicle
        </a>
    @endif
</h3>

@php
    $vehicles = $client->vehicles ?? collect();
    $hasVehicles = $vehicles->isNotEmpty();

    // latest opportunity make/model (only relevant if no vehicles yet)
    $latestOpp = $client->opportunities->first();
    $oppMake   = optional($latestOpp?->vehicleMake)->name ?? null;
    $oppModel  = optional($latestOpp?->vehicleModel)->name ?? null;
@endphp

{{-- Show “Latest from Opportunity” only if there are no vehicles --}}
@if(!$hasVehicles && ($oppMake || $oppModel))
    <div class="border rounded p-3 mb-3 bg-gray-50">
        <div class="text-xs text-gray-500 font-medium mb-1">Latest from Opportunity</div>
        <div class="text-sm text-gray-800">{{ trim(($oppMake ?? '') . ' ' . ($oppModel ?? '')) }}</div>
    </div>
@endif

@if($hasVehicles)
    @foreach ($vehicles as $v)
        <div class="border rounded p-3 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-medium">
                        {{ $v->year }} {{ optional($v->make)->name }} {{ optional($v->model)->name }}
                    </div>
                    <div class="text-sm text-gray-700">
                        Plate: {{ $v->plate_number ?? '—' }}
                        @if(!empty($v->color)) • Color: {{ $v->color }} @endif
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.vehicles.show'))
                    <a href="{{ route('admin.vehicles.show', $v->id) }}"
                       class="text-sm text-indigo-600 hover:underline ml-4 shrink-0">
                        View
                    </a>
                @endif
            </div>

            {{-- Inline renewal date editor --}}
            @if (\Illuminate\Support\Facades\Route::has('admin.vehicles.renewals.update'))
                <form action="{{ route('admin.vehicles.renewals.update', $v->id) }}" method="POST"
                      class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Car Renewal (Registration)</label>
                        <input type="date" name="registration_expiry_date"
                            value="{{ old('registration_expiry_date', $v->registration_expiry_date ? \Illuminate\Support\Carbon::parse($v->registration_expiry_date)->format('Y-m-d') : '') }}"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Insurance Renewal</label>
                        <input type="date" name="insurance_expiry_date"
                            value="{{ old('insurance_expiry_date', $v->insurance_expiry_date ? \Illuminate\Support\Carbon::parse($v->insurance_expiry_date)->format('Y-m-d') : '') }}"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
                            Save
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endforeach
@else
    <p class="text-sm text-gray-500">No vehicles added yet.</p>
@endif
