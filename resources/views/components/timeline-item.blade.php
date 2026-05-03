@props(['item'])

@php
    $type = strtolower($item['type'] ?? 'unknown');

    $color = match ($type) {
        'enrollment'    => '#0d6efd',
        'step_done'     => '#198754',
        'step_pending'  => '#6c757d',
        'automation'    => '#6610f2',
        'whatsapp'      => '#20c997',
        'communication' => '#fd7e14',
        'action'        => '#0dcaf0',
        default         => '#6c757d',
    };

    $at = $item['at'] ?? null;

    $timestamp = $at instanceof \Carbon\Carbon
        ? $at->format('Y-m-d H:i')
        : '—';

    $relative = $at instanceof \Carbon\Carbon
        ? $at->diffForHumans()
        : '';

    $meta = $item['meta'] ?? [];

    $hasError =
        ($type === 'whatsapp' && in_array(($meta['status'] ?? ''), ['failed', 'error'], true)) ||
        !empty($meta['error']);
@endphp

<div class="sl-ti" data-type="{{ $type }}">
    <div class="sl-ti-dot" style="background: {{ $color }};"></div>

    <div class="sl-ti-body {{ $hasError ? 'border-start border-3 border-danger ps-2' : '' }}">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="sl-ti-title">
                {{ $item['title'] ?? 'Event' }}

                @if($hasError)
                    <span class="badge bg-danger ms-1">Issue</span>
                @endif
            </div>

            <div class="sl-ti-meta">
                {{ $timestamp }}
                @if($relative)
                    • {{ $relative }}
                @endif
            </div>
        </div>

        @if(!empty($item['body']))
            <div class="sl-ti-text sl-collapse">
                {{ $item['body'] }}
            </div>

            <button
                type="button"
                class="btn btn-link btn-sm p-0 mt-1"
                onclick="this.previousElementSibling.classList.toggle('sl-collapse')"
            >
                Toggle details
            </button>
        @endif

        {{-- Debug Meta (toggled from parent view) --}}
        <div class="sl-ti-debug d-none mt-2 small text-muted">
            <pre class="mb-0">{{ json_encode($meta, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
</div>

<style>
.sl-ti {
    display: flex;
    gap: 12px;
}

.sl-ti-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-top: 6px;
    flex: 0 0 12px;
}

.sl-ti-body {
    flex: 1;
}

.sl-ti-title {
    font-weight: 600;
}

.sl-ti-meta {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.sl-ti-text {
    margin-top: 6px;
    white-space: pre-wrap;
    font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Arial;
}

.sl-collapse {
    max-height: 4.5em;
    overflow: hidden;
}
</style>
