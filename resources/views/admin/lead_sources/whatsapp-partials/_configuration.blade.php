<section class="sf-growth-panel overflow-hidden rounded-3xl border">
    <div class="sf-growth-panel-header border-b px-6 py-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="sf-growth-card-title text-lg font-extrabold">
                    WhatsApp Configuration
                </h2>

                <p class="sf-growth-muted mt-1 text-sm font-medium">
                    Current numbers and links used by the WhatsApp journey.
                </p>
            </div>

            <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                Intake
            </span>
        </div>
    </div>

    <div class="space-y-5 px-6 py-6">
        <div>
            <label class="sf-growth-subtle mb-1.5 block text-xs font-extrabold uppercase tracking-wide">
                Garage WhatsApp
            </label>

            <input class="sf-growth-input block w-full rounded-xl border px-4 py-3 text-sm font-semibold"
                   readonly
                   value="{{ $waFrom }}">

            <p class="sf-growth-subtle mt-2 text-xs font-medium">
                This is the configured outgoing WhatsApp number.
            </p>
        </div>

        <div>
            <label class="sf-growth-subtle mb-1.5 block text-xs font-extrabold uppercase tracking-wide">
                Manager WhatsApp
            </label>

            <input class="sf-growth-input block w-full rounded-xl border px-4 py-3 text-sm font-semibold"
                   readonly
                   value="{{ $managerWhatsapp }}">

            <p class="sf-growth-subtle mt-2 text-xs font-medium">
                Manager receives handoff and escalation alerts.
            </p>
        </div>

        <div>
            <label class="sf-growth-subtle mb-1.5 block text-xs font-extrabold uppercase tracking-wide">
                Google Review Link
            </label>

            <input class="sf-growth-input block w-full rounded-xl border px-4 py-3 text-sm font-semibold"
                   readonly
                   value="{{ $googleReviewLink }}">

            <p class="sf-growth-subtle mt-2 text-xs font-medium">
                Used after positive feedback to request reviews.
            </p>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
            <div class="sf-growth-divider border-t pt-5">
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="sf-growth-link sf-growth-link-primary inline-flex items-center justify-center rounded-xl px-5 py-3 text-sm font-extrabold transition">
                    Manage WhatsApp Settings
                </a>
            </div>
        @endif
    </div>
</section>
