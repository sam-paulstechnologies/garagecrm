{{-- resources/views/admin/clients/create-partials/_side_notes.blade.php --}}

<style>
    .sf-create-note-card {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-create-note-border {
        border-color: rgba(30, 41, 59, 1);
    }

    .sf-create-note-title {
        color: #ffffff;
    }

    .sf-create-note-text {
        color: #cbd5e1;
    }

    .sf-create-info-blue {
        border-color: rgba(96, 165, 250, 0.20);
        background: rgba(59, 130, 246, 0.10);
    }

    .sf-create-info-blue-title,
    .sf-create-info-blue-text {
        color: #93c5fd;
    }

    .sf-create-info-orange {
        border-color: rgba(251, 146, 60, 0.20);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-create-info-orange-title,
    .sf-create-info-orange-text {
        color: #fdba74;
    }

    html[data-theme="light"] .sf-create-note-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-create-note-border {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-create-note-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-create-note-text {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-create-info-blue {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-create-info-blue-title,
    html[data-theme="light"] .sf-create-info-blue-text {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-create-info-orange {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-create-info-orange-title,
    html[data-theme="light"] .sf-create-info-orange-text {
        color: #c2410c !important;
    }
</style>

<div class="space-y-6">

    {{-- Setup Notes --}}
    <div class="sf-create-note-card overflow-hidden rounded-2xl border shadow-sm">
        <div class="sf-create-note-border border-b p-5">
            <h2 class="sf-create-note-title text-base font-extrabold tracking-tight">
                Client Setup Notes
            </h2>
        </div>

        <div class="p-5">
            <ul class="space-y-3 text-sm">
                <li class="sf-create-note-text flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                        1
                    </span>

                    <span>Create the client first.</span>
                </li>

                <li class="sf-create-note-text flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                        2
                    </span>

                    <span>Add vehicles from the client profile page.</span>
                </li>

                <li class="sf-create-note-text flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                        3
                    </span>

                    <span>Bookings, jobs, invoices, and notes will connect to this profile.</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Phone Format --}}
    <div class="sf-create-info-blue rounded-2xl border p-5 shadow-sm">
        <h3 class="sf-create-info-blue-title font-extrabold">
            Phone Format
        </h3>

        <p class="sf-create-info-blue-text mt-2 text-sm font-semibold leading-6">
            Recommended UAE format: 9715XXXXXXXX. This keeps WhatsApp and SMS workflows clean.
        </p>
    </div>

    {{-- Next Step --}}
    <div class="sf-create-info-orange rounded-2xl border p-5 shadow-sm">
        <h3 class="sf-create-info-orange-title font-extrabold">
            Next Step
        </h3>

        <p class="sf-create-info-orange-text mt-2 text-sm font-semibold leading-6">
            After creating the client, open the profile and add their first vehicle.
        </p>
    </div>

</div>