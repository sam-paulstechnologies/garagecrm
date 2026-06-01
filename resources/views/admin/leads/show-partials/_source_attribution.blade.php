{{-- resources/views/admin/leads/show-partials/_source_attribution.blade.php --}}

<div class="sf-leads-show-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-show-title text-lg font-extrabold tracking-tight">Source & Attribution</h2>
        <p class="sf-leads-show-muted mt-1 text-sm font-medium">
            Useful for verifying Meta Lead Ads, website forms, WhatsApp, and campaign attribution.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-5 p-5 text-sm md:grid-cols-2">
        @foreach([
            'Displayed Source' => $sourceLabel,
            'Lead Source Type' => $leadSourceType ? ucfirst($leadSourceType) : '-',
            'Lead Source Status' => $leadSourceStatus ? ucfirst($leadSourceStatus) : '-',
            'External Source' => $lead->external_source ?? '-',
            'Received At' => $lead->external_received_at?->format('d M Y, h:i A') ?? '-',
            'Meta Page' => $pageName ?? '-',
            'Meta Form' => $formName ?? '-',
        ] as $label => $value)
            <div>
                <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                <div class="sf-leads-show-value mt-1 font-bold">{{ $value }}</div>
            </div>
        @endforeach

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Lead Source Record</div>
            <div class="sf-leads-show-value mt-1 font-bold">
                @if($lead->leadSource)
                    #{{ $lead->leadSource->id }} - {{ $lead->leadSource->name }}
                @else
                    -
                @endif
            </div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">External Lead ID</div>
            <div class="sf-leads-show-value mt-1 break-all font-mono text-xs">{{ $leadgenId ?? '-' }}</div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">External Form ID</div>
            <div class="sf-leads-show-value mt-1 break-all font-mono text-xs">{{ $formId ?? '-' }}</div>
        </div>

        @if($pageId)
            <div>
                <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Meta Page ID</div>
                <div class="sf-leads-show-value mt-1 break-all font-mono text-xs">{{ $pageId }}</div>
            </div>
        @endif

        @if($formId)
            <div>
                <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Meta Form ID</div>
                <div class="sf-leads-show-value mt-1 break-all font-mono text-xs">{{ $formId }}</div>
            </div>
        @endif

        @if(!empty($webhook))
            <div class="md:col-span-2">
                <details class="sf-leads-show-soft rounded-2xl border">
                    <summary class="sf-leads-show-title cursor-pointer px-4 py-3 font-bold">
                        View Webhook Metadata
                    </summary>
                    <pre class="sf-leads-show-value overflow-x-auto whitespace-pre-wrap p-4 text-xs">{{ json_encode($webhook, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
            </div>
        @endif
    </div>
</div>
