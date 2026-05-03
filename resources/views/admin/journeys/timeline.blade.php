@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1">Journey Timeline</h3>
            <div class="text-muted">
                <div><strong>Journey:</strong> {{ $journey->name ?? ('#'.$journey->id) }}</div>
                <div><strong>Trigger:</strong> {{ $journey->trigger_key ?? '-' }}</div>
                <div><strong>Status:</strong> {{ $enrollment->status ?? 'active' }}</div>
                <div>
                    <strong>Entity:</strong>
                    {{ class_basename($enrollment->enrollable_type) }}
                    #{{ $enrollment->enrollable_id }}
                </div>
            </div>
        </div>

        <div class="text-end d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="toggleDebug()">Debug</button>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-primary" onclick="filterTimeline('all')">All</button>
        <button class="btn btn-sm btn-outline-success" onclick="filterTimeline('step')">Steps</button>
        <button class="btn btn-sm btn-outline-info" onclick="filterTimeline('whatsapp')">WhatsApp</button>
        <button class="btn btn-sm btn-outline-purple" onclick="filterTimeline('automation')">Automation</button>
        <button class="btn btn-sm btn-outline-warning" onclick="filterTimeline('communication')">Communication</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            @if(empty($timeline))
                <div class="text-muted">No timeline events found.</div>
            @else
                <div class="sl-timeline">
                    @foreach($timeline as $item)
                        <x-timeline-item :item="$item" />
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</div>

<style>
.sl-timeline { display:flex; flex-direction:column; gap:14px; }
.btn-outline-purple { border-color:#6610f2; color:#6610f2; }
.btn-outline-purple:hover { background:#6610f2; color:#fff; }
</style>

<script>
function filterTimeline(type) {
    document.querySelectorAll('.sl-ti').forEach(el => {
        if (type === 'all') {
            el.style.display = 'flex';
            return;
        }
        el.style.display = el.dataset.type.startsWith(type) ? 'flex' : 'none';
    });
}

function toggleDebug() {
    document.querySelectorAll('.sl-ti-debug').forEach(el => {
        el.classList.toggle('d-none');
    });
}
</script>
@endsection
