<aside class="space-y-6">
    <section class="sf-growth-panel overflow-hidden rounded-3xl border">
        <div class="sf-growth-panel-header border-b px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="sf-growth-card-title text-lg font-extrabold">
                    Meta/WABA Webhook URL
                </h2>

                <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-700 ring-1 ring-blue-400/20 dark:text-blue-300">
                    Primary
                </span>
            </div>
        </div>

        <div class="px-6 py-6">
            <label class="sf-growth-subtle mb-1.5 block text-xs font-extrabold uppercase tracking-wide">
                Endpoint
            </label>

            @if($webhookUrl)
                <textarea id="webhookUrlInput"
                          readonly
                          class="sf-growth-input block min-h-[120px] w-full rounded-xl border px-4 py-3 text-xs font-semibold leading-6">{{ $webhookUrl }}</textarea>

                <button type="button"
                        onclick="copyWebhookUrl()"
                        class="sf-growth-link sf-growth-link-primary mt-4 inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-extrabold transition">
                    Copy Webhook URL
                </button>
            @else
                <div class="rounded-2xl border border-orange-300 bg-orange-50 p-4 text-sm font-semibold text-orange-800 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200">
                    Webhook route is not configured yet.
                </div>
            @endif

            @if(!empty($legacyTwilioWebhookUrl))
                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Legacy Twilio Webhook
                    </p>

                    <p class="mt-2 break-all text-xs font-semibold text-slate-700 dark:text-slate-300">
                        {{ $legacyTwilioWebhookUrl }}
                    </p>

                    <p class="mt-2 text-xs font-medium leading-5 text-slate-500 dark:text-slate-400">
                        Kept for backward compatibility. Use Meta/WABA for new WhatsApp setup.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <section class="sf-growth-panel overflow-hidden rounded-3xl border">
        <div class="sf-growth-panel-header border-b px-6 py-4">
            <h2 class="sf-growth-card-title text-lg font-extrabold">
                How this is used
            </h2>
        </div>

        <div class="px-6 py-6">
            <ul class="sf-growth-muted list-inside list-disc space-y-3 text-sm font-medium leading-6">
                <li>Receives inbound WhatsApp messages.</li>
                <li>Creates or matches leads using phone number.</li>
                <li>Routes messages to the inbox.</li>
                <li>Supports manager handoff and follow-up journeys.</li>
                <li>Feeds WhatsApp conversation history.</li>
            </ul>
        </div>
    </section>

    <section class="rounded-3xl border border-orange-300 bg-orange-50 p-6 shadow-xl shadow-black/10 dark:border-orange-400/20 dark:bg-orange-500/10">
        <h2 class="text-lg font-extrabold text-orange-700 dark:text-orange-300">
            Testing Tip
        </h2>

        <p class="mt-3 text-sm font-medium leading-6 text-orange-700 dark:text-orange-100/80">
            After webhook setup, send &ldquo;hi&rdquo; from a test phone number, check logs, then confirm if a conversation and lead are created.
        </p>
    </section>
</aside>