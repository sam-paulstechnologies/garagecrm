{{-- resources/views/admin/clients/import-partials/_side_notes.blade.php --}}

<style>
    .sf-note-orange {
        border-color: rgba(251, 146, 60, 0.20);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-note-orange-title,
    .sf-note-orange-text {
        color: #fdba74;
    }

    .sf-note-green {
        border-color: rgba(74, 222, 128, 0.20);
        background: rgba(34, 197, 94, 0.10);
    }

    .sf-note-green-title,
    .sf-note-green-text {
        color: #86efac;
    }

    .sf-note-blue {
        border-color: rgba(96, 165, 250, 0.20);
        background: rgba(59, 130, 246, 0.10);
    }

    .sf-note-blue-title,
    .sf-note-blue-text {
        color: #93c5fd;
    }

    html[data-theme="light"] .sf-note-orange {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-note-orange-title,
    html[data-theme="light"] .sf-note-orange-text {
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-note-green {
        border-color: #bbf7d0 !important;
        background: #ecfdf5 !important;
    }

    html[data-theme="light"] .sf-note-green-title,
    html[data-theme="light"] .sf-note-green-text {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-note-blue {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-note-blue-title,
    html[data-theme="light"] .sf-note-blue-text {
        color: #1d4ed8 !important;
    }
</style>

<div class="space-y-6">

    <div class="sf-note-orange rounded-2xl border p-5 shadow-sm">
        <h3 class="sf-note-orange-title font-extrabold">
            Required Columns
        </h3>

        <div class="mt-3 flex flex-wrap gap-2">
            <span class="inline-flex rounded-full bg-orange-500/10 px-2 py-1 text-xs font-black text-orange-300 ring-1 ring-orange-400/20">
                name
            </span>

            <span class="inline-flex rounded-full bg-orange-500/10 px-2 py-1 text-xs font-black text-orange-300 ring-1 ring-orange-400/20">
                phone
            </span>

            <span class="inline-flex rounded-full bg-orange-500/10 px-2 py-1 text-xs font-black text-orange-300 ring-1 ring-orange-400/20">
                email
            </span>
        </div>
    </div>

    <div class="sf-note-green rounded-2xl border p-5 shadow-sm">
        <h3 class="sf-note-green-title font-extrabold">
            Optional Columns
        </h3>

        <p class="sf-note-green-text mt-2 text-sm font-semibold leading-6">
            whatsapp, dob, gender, address, city, state, postal_code, country, source, status, notes, is_vip, preferred_channel.
        </p>
    </div>

    <div class="sf-note-blue rounded-2xl border p-5 shadow-sm">
        <h3 class="sf-note-blue-title font-extrabold">
            Import Tip
        </h3>

        <p class="sf-note-blue-text mt-2 text-sm font-semibold leading-6">
            Dates should be MM/DD/YYYY. Phone and WhatsApp numbers should be digits only, without plus signs or spaces.
        </p>
    </div>

</div>