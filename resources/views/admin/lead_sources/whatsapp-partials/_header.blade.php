<div class="mb-6 rounded-3xl border sf-growth-panel p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-growth-kicker inline-flex rounded-full border px-3 py-1 text-xs font-extrabold uppercase tracking-wide">
                WhatsApp Lead Source
            </div>

            <h1 class="sf-growth-title mt-4 text-3xl font-black tracking-tight md:text-4xl">
                WhatsApp Lead Intake
            </h1>

            <p class="sf-growth-muted mt-2 max-w-3xl text-sm font-medium leading-6">
                Review WhatsApp numbers, webhook URL, manager handoff, review link, and lead intake configuration.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="sf-growth-link sf-growth-link-secondary inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-extrabold transition">
                Back to Lead Sources
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="sf-growth-link sf-growth-link-primary inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-extrabold transition">
                    Manage WhatsApp Settings
                </a>
            @endif
        </div>
    </div>
</div>
