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

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
    <div class="border-b border-slate-200 p-5 dark:border-slate-800">
        <h2 class="text-base font-extrabold tracking-tight text-slate-950 dark:text-white">
            Client Import Format
        </h2>

        <p class="mt-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
            Use these columns to keep client profiles clean and ready for CRM use.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
            <thead class="bg-slate-50 dark:bg-slate-950/70">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">
                        Column
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">
                        Required?
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">
                        Example
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">
                        Notes
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($rows as [$column, $required, $example, $notes])
                    <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-extrabold text-slate-950 dark:text-white">
                            {{ $column }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3">
                            @if($required === 'Yes' || $required === 'Yes*')
                                <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-400/20 dark:text-emerald-200">
                                    {{ $required }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-600 ring-1 ring-slate-300 dark:bg-slate-500/10 dark:text-slate-200 dark:ring-slate-500/20">
                                    No
                                </span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                            {{ $example }}
                        </td>

                        <td class="px-5 py-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                            {{ $notes }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
