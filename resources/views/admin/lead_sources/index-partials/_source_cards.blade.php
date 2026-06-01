<div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
    <div class="sf-growth-source-card overflow-hidden rounded-3xl border">
        <div class="sf-growth-source-header border-b px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex rounded-full bg-green-500/10 px-3 py-1 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                    WhatsApp &bull; Connected
                </span>

                <span class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">WA</span>
            </div>
        </div>

        <div class="px-6 py-6">
            <h2 class="sf-growth-card-title text-xl font-black">
                WhatsApp Conversations
            </h2>

            <p class="sf-growth-muted mt-3 min-h-[72px] text-sm font-medium leading-6">
                Automatically capture and manage leads directly from customer WhatsApp chats.
            </p>

            <div class="sf-growth-soft-panel mt-5 rounded-2xl border p-4">
                <p class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">
                    Status
                </p>

                <p class="mt-2 text-sm font-extrabold text-green-300">
                    Auto-capture enabled
                </p>
            </div>

            <a href="{{ route('admin.lead-sources.whatsapp') }}"
               class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-green-500/20 transition hover:bg-green-700">
                Configure WhatsApp Flow
            </a>
        </div>
    </div>

    <div class="sf-growth-source-card overflow-hidden rounded-3xl border">
        <div class="sf-growth-source-header border-b px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex rounded-full bg-blue-500/10 px-3 py-1 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                    Website &bull; Ready
                </span>

                <span class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">WEB</span>
            </div>
        </div>

        <div class="px-6 py-6">
            <h2 class="sf-growth-card-title text-xl font-black">
                Website Forms
            </h2>

            <p class="sf-growth-muted mt-3 min-h-[72px] text-sm font-medium leading-6">
                Capture leads from contact forms and landing pages in real time.
            </p>

            <div class="sf-growth-soft-panel mt-5 rounded-2xl border p-4">
                <p class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">
                    Status
                </p>

                <p class="mt-2 text-sm font-extrabold text-blue-300">
                    Forms active
                </p>
            </div>

            <a href="{{ route('admin.lead-sources.website.index') }}"
               class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700">
                Manage Forms & Webhooks
            </a>
        </div>
    </div>

    <div class="sf-growth-source-card overflow-hidden rounded-3xl border">
        <div class="sf-growth-source-header border-b px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-extrabold text-cyan-300 ring-1 ring-cyan-400/20">
                    Meta &bull; Ready
                </span>

                <span class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">META</span>
            </div>
        </div>

        <div class="px-6 py-6">
            <h2 class="sf-growth-card-title text-xl font-black">
                Meta Lead Ads
            </h2>

            <p class="sf-growth-muted mt-3 min-h-[72px] text-sm font-medium leading-6">
                Sync Facebook and Instagram Lead Ads automatically into SayaraForce.
            </p>

            <div class="sf-growth-soft-panel mt-5 rounded-2xl border p-4">
                <p class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">
                    Status
                </p>

                <p class="mt-2 text-sm font-extrabold text-cyan-300">
                    Facebook lead capture enabled
                </p>
            </div>

            <a href="{{ route('admin.lead-sources.meta') }}"
               class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-cyan-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-700">
                Manage Meta Account
            </a>
        </div>
    </div>

    <div class="sf-growth-source-card overflow-hidden rounded-3xl border">
        <div class="sf-growth-source-header border-b px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex rounded-full bg-orange-500/10 px-3 py-1 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                    Google Ads &bull; Ready
                </span>

                <span class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">ADS</span>
            </div>
        </div>

        <div class="px-6 py-6">
            <h2 class="sf-growth-card-title text-xl font-black">
                Google Lead Forms
            </h2>

            <p class="sf-growth-muted mt-3 min-h-[72px] text-sm font-medium leading-6">
                Capture Google Ads Lead Form submissions using webhook URL and webhook key.
            </p>

            <div class="sf-growth-soft-panel mt-5 rounded-2xl border p-4">
                <p class="sf-growth-subtle text-xs font-extrabold uppercase tracking-wide">
                    Status
                </p>

                <p class="mt-2 text-sm font-extrabold text-orange-300">
                    Webhook setup available
                </p>
            </div>

            <a href="{{ route('admin.lead-sources.google') }}"
               class="sf-growth-link-primary mt-5 inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-extrabold transition">
                Open Google Setup
            </a>
        </div>
    </div>
</div>
