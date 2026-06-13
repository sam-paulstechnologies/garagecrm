{{-- resources/views/admin/clients/import-partials/_format_table.blade.php --}}

@php
    $rows = [
        ['name', 'Yes', 'Sam Abhishek', 'Client full name.'],
        ['phone', 'Yes*', '971586934377', 'Required if WhatsApp is blank. Use digits only where possible.'],
        ['whatsapp', 'Yes*', '971586934377', 'Required if phone is blank. Preferred WhatsApp number.'],
        ['email', 'No', 'sam@example.com', 'Optional contact and fallback duplicate check.'],
        ['vehicle_make', 'No', 'Mercedes-Benz', 'Vehicle brand.'],
        ['vehicle_model', 'No', 'GLE', 'Vehicle model or line.'],
        ['plate_number', 'No', 'D12345', 'Optional vehicle plate.'],
        ['vehicle_year', 'No', '2021', 'Optional model year.'],
        ['last_service_date', 'No', now()->subMonths(7)->toDateString(), 'Used to suggest service retention timing.'],
        ['last_service_type', 'No', 'General Service', 'Used to suggest retention segment.'],
        ['last_invoice_amount', 'No', '850.00', 'Optional historical spend.'],
        ['last_mileage', 'No', '72000', 'Optional mileage at last service.'],
        ['insurance_expiry_date', 'No', now()->addDays(24)->toDateString(), 'Used for insurance renewal reminders.'],
        ['mulkia_expiry_date', 'No', now()->addDays(28)->toDateString(), 'Used for registration renewal reminders.'],
        ['source', 'No', 'website', 'Lead/client source.'],
        ['status', 'No', 'active', 'Client status if supported.'],
        ['is_vip', 'No', '1', 'Use 1/yes/true if VIP.'],
        ['preferred_channel', 'No', 'whatsapp', 'whatsapp, phone, email.'],
        ['notes', 'No', 'Previous garage customer', 'Internal notes.'],
    ];
@endphp

<section
    class="sf-client-import-panel overflow-hidden rounded-2xl border shadow-sm"
    data-client-import-collapsible
    data-storage-key="sayara.clientImport.formatGuideCollapsed"
    data-default-collapsed="true"
>
    <div class="flex flex-col gap-3 border-b border-slate-800 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="sf-client-import-title text-base font-extrabold tracking-tight">
                    File Format Guide
                </h2>

                <span class="sf-client-import-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                    {{ count($rows) }} columns
                </span>
            </div>

            <p class="sf-client-import-muted mt-1 text-xs font-semibold leading-5">
                Keep this collapsed while uploading; open it when preparing a new sheet.
            </p>
        </div>

        <button
            type="button"
            class="sf-btn-secondary"
            data-client-import-collapsible-toggle
            aria-expanded="false"
        >
            Show format guide
        </button>
    </div>

    <div class="hidden" data-client-import-collapsible-body>
        <div class="overflow-x-auto">
            <table class="sf-client-import-table min-w-full divide-y divide-slate-800 text-sm">
                <thead>
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Column</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Required?</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Example</th>
                        <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Notes</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-800">
                    @foreach($rows as [$column, $required, $example, $notes])
                        <tr class="transition hover:bg-slate-800/30">
                            <td class="whitespace-nowrap px-5 py-3 text-sm font-extrabold">
                                {{ $column }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3">
                                @if($required === 'Yes' || $required === 'Yes*')
                                    <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-black text-emerald-200 ring-1 ring-emerald-400/20">
                                        {{ $required }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-1 text-xs font-black text-slate-300 ring-1 ring-slate-500/20">
                                        No
                                    </span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-sm font-semibold">
                                {{ $example }}
                            </td>

                            <td class="min-w-[260px] px-5 py-3 text-sm font-semibold">
                                {{ $notes }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
