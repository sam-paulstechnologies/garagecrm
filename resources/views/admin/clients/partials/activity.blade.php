{{-- resources/views/admin/clients/partials/activity.blade.php --}}

@php
use Illuminate\Support\Str;

$feed = collect();

/**
 * Safe helper:
 * - Only read relation if it exists AND is loaded
 * - Never triggers queries
 * - Never crashes
 */
$safe = function ($model, string $relation) {
    if (! method_exists($model, $relation)) {
        return collect();
    }

    if (! $model->relationLoaded($relation)) {
        return collect();
    }

    return collect($model->$relation);
};

$typeMeta = function (string $type) {
    return match ($type) {
        'note' => [
            'label' => 'Note',
            'icon' => '📝',
            'class' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'border' => 'border-yellow-400/20',
        ],
        'file' => [
            'label' => 'Document',
            'icon' => '📄',
            'class' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'border' => 'border-blue-400/20',
        ],
        'lead' => [
            'label' => 'Lead',
            'icon' => '🎯',
            'class' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            'border' => 'border-green-400/20',
        ],
        'opportunity' => [
            'label' => 'Opportunity',
            'icon' => '💼',
            'class' => 'bg-purple-500/10 text-purple-300 ring-purple-400/20',
            'border' => 'border-purple-400/20',
        ],
        default => [
            'label' => ucfirst($type),
            'icon' => '•',
            'class' => 'bg-white/5 text-slate-300 ring-white/10',
            'border' => 'border-white/10',
        ],
    };
};

/** Notes */
$notes = $safe($client, 'notes')->take(3)->map(fn ($n) => [
    'type' => 'note',
    'when' => $n->created_at,
    'who'  => $n->creator->name ?? 'Unknown',
    'title' => 'Note added',
    'line' => Str::limit((string) $n->content, 120),
    'url'  => route('admin.clients.notes.index', $client),
]);

/** Files */
$files = $safe($client, 'files')->take(3)->map(fn ($f) => [
    'type' => 'file',
    'when' => $f->uploaded_at ?? $f->created_at,
    'who'  => $f->uploader->name ?? 'System',
    'title' => $f->file_name ?? $f->document_name ?? 'Document uploaded',
    'line' => ucfirst(str_replace('_', ' ', $f->file_type ?? $f->type ?? 'document')),
    'url'  => ! empty($f->file_path)
        ? asset($f->file_path)
        : (! empty($f->document_path) ? asset('storage/' . $f->document_path) : null),
]);

/** Leads */
$leads = $safe($client, 'leads')->take(3)->map(fn ($l) => [
    'type' => 'lead',
    'when' => $l->created_at,
    'who'  => $l->assignee->name ?? 'Unassigned',
    'title' => $l->name ?? 'Lead',
    'line' => ucfirst(str_replace('_', ' ', $l->status ?? 'new')),
    'url'  => route('admin.leads.show', $l),
]);

/** Opportunities */
$ops = $safe($client, 'opportunities')->take(3)->map(fn ($o) => [
    'type' => 'opportunity',
    'when' => $o->created_at,
    'who'  => $o->owner->name ?? $o->assignee->name ?? 'Unassigned',
    'title' => $o->title ?? 'Opportunity',
    'line' => ucfirst(str_replace('_', ' ', $o->stage ?? 'new')),
    'url'  => route('admin.opportunities.show', $o),
]);

$feed = $feed
    ->merge($notes)
    ->merge($files)
    ->merge($leads)
    ->merge($ops)
    ->sortByDesc('when')
    ->values();
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-section-title">
                Recent Activity
            </h2>

            <p class="sf-section-subtitle">
                Latest client-related updates from notes, documents, leads, and opportunities.
            </p>
        </div>

        <span class="sf-badge-slate">
            {{ $feed->count() }} item(s)
        </span>
    </div>

    {{-- Feed --}}
    <div class="space-y-3">
        @forelse ($feed as $item)
            @php
                $meta = $typeMeta($item['type']);
            @endphp

            <div class="rounded-2xl border {{ $meta['border'] }} bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                <div class="flex items-start gap-3">

                    {{-- Icon --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm ring-1 {{ $meta['class'] }}">
                        {{ $meta['icon'] }}
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide ring-1 {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>

                                <div class="mt-2 break-words text-sm font-extrabold text-white">
                                    {{ $item['title'] }}
                                </div>

                                @if(! empty($item['line']))
                                    <div class="mt-1 break-words text-sm font-medium leading-6 text-slate-400">
                                        {{ $item['line'] }}
                                    </div>
                                @endif
                            </div>

                            @if(! empty($item['url']))
                                <a href="{{ $item['url'] }}" class="sf-link shrink-0">
                                    View
                                </a>
                            @endif
                        </div>

                        <div class="mt-3 text-xs font-medium text-slate-500">
                            {{ optional($item['when'])->format('d M Y H:i') ?? 'No date' }}
                            · {{ $item['who'] }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="sf-empty">
                No recent activity.
            </div>
        @endforelse
    </div>
</div>