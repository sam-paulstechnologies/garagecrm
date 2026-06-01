<div class="mb-6 rounded-3xl border sf-growth-panel p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-growth-kicker inline-flex rounded-full border px-3 py-1 text-xs font-extrabold uppercase tracking-wide">
                Lead Capture
            </div>

            <h1 class="sf-growth-title mt-4 text-3xl font-black tracking-tight md:text-4xl">
                Lead Sources
            </h1>

            <p class="sf-growth-muted mt-2 max-w-3xl text-sm font-medium leading-6">
                Configure how leads enter SayaraForce from WhatsApp, website forms, Meta lead ads, and Google Ads lead forms.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="sf-growth-link sf-growth-link-secondary inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-extrabold transition">
                    Integration Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.settings.launch-setup.edit'))
                <a href="{{ route('admin.settings.launch-setup.edit') }}"
                   class="sf-growth-link sf-growth-link-secondary inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-extrabold transition">
                    Launch Setup
                </a>
            @endif
        </div>
    </div>
</div>
