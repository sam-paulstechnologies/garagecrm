{{-- resources/views/admin/clients/partials/activity.blade.php --}}

@php
use Illuminate\Support\Str;

$feed = collect();

/**
 * Helper: safely get a collection for a relation that may be null/unloaded.
 * We prefer getRelation() so we don't trigger extra queries if not eager-loaded.
 */
$safe = function ($model, string $relation) {
    return collect($model->getRelation($relation) ?? []);
};

/** Notes (recent 3) */
$notes = $safe($client, 'notes')
    ->take(3)
    ->map(fn($n) => [
        'type' => 'note',
        'when' => $n->created_at ?? now(),
        'who'  => optional($n->creator)->name ?? 'Unknown',
        'line' => Str::limit((string)($n->content ?? ''), 120),
        'url'  => route('admin.clients.notes.index', $client),
    ]);

/** Files (recent 3) */
$files = $safe($client, 'files')
    ->take(3)
    ->map(fn($f) => [
        'type' => 'file',
        'when' => $f->created_at ?? now(),
        'who'  => optional($f->uploader)->name ?? 'Unknown',
        'line' => ($f->file_name ?? 'File') . ' • ' . strtoupper((string)($f->file_type ?? '')),
        'url'  => route('admin.files.show', $f->id ?? 0), // adjust route if different
    ]);

/** Leads (recent 3) */
$leads = $safe($client, 'leads')
    ->take(3)
    ->map(fn($l) => [
        'type' => 'lead',
        'when' => $l->created_at ?? now(),
        'who'  => optional($l->assignee)->name ?? 'Unassigned',
        'line' => 'Lead • ' . ($l->status ?? 'new') . ' • ' . ($l->source ?? 'unknown'),
        'url'  => route('admin.leads.show', $l->id ?? 0),
    ]);

/** Opportunities (recent 3) */
$ops = $safe($client, 'opportunities')
    ->take(3)
    ->map(fn($o) => [
        'type' => 'opportunity',
        'when' => $o->created_at ?? now(),
        'who'  => optional($o->owner)->name ?? optional($o->assignee)->name ?? 'Unassigned',
        'line' => ($o->title ?? 'Opportunity') . ' • ' . ($o->stage ?? 'new'),
        'url'  => route('admin.opportunities.show', $o->id ?? 0),
    ]);

$feed = $feed->merge($notes)->merge($files)->merge($leads)->merge($ops)
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
