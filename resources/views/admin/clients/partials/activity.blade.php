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
    if (!method_exists($model, $relation)) {
        return collect();
    }

    if (!$model->relationLoaded($relation)) {
        return collect();
    }

    return collect($model->$relation);
};

/** Notes */
$notes = $safe($client, 'notes')->take(3)->map(fn($n) => [
    'type' => 'note',
    'when' => $n->created_at,
    'who'  => $n->creator->name ?? 'Unknown',
    'line' => Str::limit((string) $n->content, 120),
    'url'  => route('admin.clients.notes.index', $client),
]);

/** Files */
$files = $safe($client, 'files')->take(3)->map(fn($f) => [
    'type' => 'file',
    'when' => $f->uploaded_at,
    'who'  => $f->uploader->name ?? 'System',
    'line' => ($f->file_name ?? 'File') . ' • ' . strtoupper($f->file_type ?? ''),
    'url'  => $f->file_path ? asset($f->file_path) : null,
]);

/** Leads */
$leads = $safe($client, 'leads')->take(3)->map(fn($l) => [
    'type' => 'lead',
    'when' => $l->created_at,
    'who'  => $l->assignee->name ?? 'Unassigned',
    'line' => 'Lead • ' . ($l->status ?? 'new'),
    'url'  => route('admin.leads.show', $l),
]);

/** Opportunities */
$ops = $safe($client, 'opportunities')->take(3)->map(fn($o) => [
    'type' => 'opportunity',
    'when' => $o->created_at,
    'who'  => $o->owner->name ?? 'Unassigned',
    'line' => ($o->title ?? 'Opportunity') . ' • ' . ($o->stage ?? 'new'),
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

<div class="space-y-3">
    @forelse ($feed as $item)
        <div class="flex items-start gap-3">
            <div class="mt-1 text-xs uppercase opacity-60">{{ $item['type'] }}</div>
            <div>
                <div class="text-sm">{{ $item['line'] }}</div>
                <div class="text-xs opacity-60">
                    {{ optional($item['when'])->format('Y-m-d H:i') }} • {{ $item['who'] }}
                    @if(!empty($item['url']))
                        • <a href="{{ $item['url'] }}" class="underline">view</a>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="text-sm opacity-60">No recent activity.</div>
    @endforelse
</div>
