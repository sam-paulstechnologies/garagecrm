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
            'class' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
        ],
        'file' => [
            'label' => 'Document',
            'icon' => '📄',
            'class' => 'bg-blue-50 text-blue-700 border-blue-100',
        ],
        'lead' => [
            'label' => 'Lead',
            'icon' => '🎯',
            'class' => 'bg-green-50 text-green-700 border-green-100',
        ],
        'opportunity' => [
            'label' => 'Opportunity',
            'icon' => '💼',
            'class' => 'bg-purple-50 text-purple-700 border-purple-100',
        ],
        default => [
            'label' => ucfirst($type),
            'icon' => '•',
            'class' => 'bg-gray-50 text-gray-700 border-gray-100',
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

<div>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Recent Activity
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Latest client-related updates.
            </p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse ($feed as $item)
            @php
                $meta = $typeMeta($item['type']);
            @endphp

            <div class="border border-gray-100 rounded-lg p-3 bg-white hover:bg-gray-50 transition">
                <div class="flex items-start gap-3">

                    {{-- Icon --}}
                    <div class="w-9 h-9 rounded-full border flex items-center justify-center text-sm shrink-0 {{ $meta['class'] }}">
                        {{ $meta['icon'] }}
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded-full border {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </div>

                                <div class="text-sm font-semibold text-gray-900 mt-1 break-words">
                                    {{ $item['title'] }}
                                </div>

                                @if(! empty($item['line']))
                                    <div class="text-sm text-gray-600 mt-0.5 break-words">
                                        {{ $item['line'] }}
                                    </div>
                                @endif
                            </div>

                            @if(! empty($item['url']))
                                <a href="{{ $item['url'] }}"
                                   class="text-xs text-blue-600 hover:underline shrink-0">
                                    View
                                </a>
                            @endif
                        </div>

                        <div class="text-xs text-gray-400 mt-2">
                            {{ optional($item['when'])->format('d M Y H:i') ?? 'No date' }}
                            · {{ $item['who'] }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="border border-dashed rounded-lg p-5 text-center">
                <div class="text-sm text-gray-500">
                    No recent activity.
                </div>
            </div>
        @endforelse
    </div>
</div>