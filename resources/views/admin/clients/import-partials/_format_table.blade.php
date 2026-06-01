{{-- resources/views/admin/clients/import-partials/_format_table.blade.php --}}

@php
    $rows = [
        ['name', 'Yes', 'Sam Abhishek', 'Client full name.'],
        ['phone', 'Yes', '971586934377', 'Use digits only where possible.'],
        ['email', 'Yes', 'sam@example.com', 'Used for contact and dedupe.'],
        ['whatsapp', 'No', '971586934377', 'Preferred WhatsApp number.'],
        ['dob', 'No', '02/16/1990', 'Use MM/DD/YYYY.'],
        ['gender', 'No', 'male', 'Optional profile data.'],
        ['address', 'No', 'JVC, Dubai', 'Client address.'],
        ['city', 'No', 'Dubai', 'City name.'],
        ['country', 'No', 'UAE', 'Country name.'],
        ['source', 'No', 'website', 'Lead/client source.'],
        ['status', 'No', 'active', 'Client status if supported.'],
        ['notes', 'No', 'VIP client', 'Internal notes.'],
        ['is_vip', 'No', '1', 'Use 1/yes/true if VIP.'],
        ['preferred_channel', 'No', 'whatsapp', 'whatsapp, phone, email.'],
    ];
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/70 shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="text-base font-extrabold tracking-tight text-white">
            Client Import Format
        </h2>

        <p class="mt-1 text-xs font-medium text-slate-400">
            Use these columns to keep client profiles clean and ready for CRM use.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-800">
            <thead class="bg-slate-950/70">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        Column
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        Required?
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        Example
                    </th>

                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                        Notes
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
                @foreach($rows as [$column, $required, $example, $notes])
                    <tr class="transition hover:bg-slate-950/40">
                        <td class="whitespace-nowrap px-5 py-3 text-sm font-extrabold text-white">
                            {{ $column }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-3">
                            @if($required === 'Yes')
                                <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-black text-emerald-300 ring-1 ring-emerald-400/20">
                                    Yes
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-500/10 px-2 py-1 text-xs font-black text-slate-300 ring-1 ring-slate-500/20">
                                    No
                                </span>
                            @endif
                        </td>

                        <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-300">
                            {{ $example }}
                        </td>

                        <td class="px-5 py-3 text-sm font-medium text-slate-400">
                            {{ $notes }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>