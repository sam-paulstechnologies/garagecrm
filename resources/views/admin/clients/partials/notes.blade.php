{{-- resources/views/admin/clients/partials/notes.blade.php --}}

@php
    // Only fetch the latest 3; eager-load author
    $notes = method_exists($client, 'notes')
        ? $client->notes()->with('creator:id,name')->latest()->take(3)->get()
        : collect();
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Notes
            </h3>

            <p class="sf-section-subtitle">
                Internal notes and context from the garage team.
            </p>
        </div>

        @if (\Illuminate\Support\Facades\Route::has('admin.clients.notes.index'))
            <a href="{{ route('admin.clients.notes.index', $client->id) }}" class="sf-btn-secondary">
                View All
            </a>
        @endif
    </div>

    {{-- Inline Add Note --}}
    @if(\Illuminate\Support\Facades\Route::has('admin.clients.notes.store'))
        <form action="{{ route('admin.clients.notes.store', $client->id) }}"
              method="POST"
              class="rounded-3xl border border-white/10 bg-slate-950/60 p-4">
            @csrf

            <label for="note_content" class="sf-label">
                Add Quick Note
            </label>

            <textarea
                id="note_content"
                name="content"
                rows="3"
                placeholder="Type a quick note and press Add..."
                class="sf-textarea"
            >{{ old('content') }}</textarea>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button type="submit" class="sf-btn-primary">
                    Add Note
                </button>

                @error('content')
                    <span class="sf-error">{{ $message }}</span>
                @enderror
            </div>
        </form>
    @endif

    {{-- Recent Notes --}}
    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="text-sm font-extrabold text-white">
                Recent Notes
            </div>

            <span class="sf-badge-slate">
                Latest {{ $notes->count() }}
            </span>
        </div>

        @forelse ($notes as $note)
            <div class="mb-3 rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                <div class="whitespace-pre-line text-sm font-medium leading-6 text-slate-300">
                    {{ $note->content ?? $note->note ?? '' }}
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                    <span>
                        {{ optional($note->created_at)->format('M j, Y H:i') ?? 'No date' }}
                    </span>

                    <span>·</span>

                    <span>
                        by {{ $note->creator?->name ?? 'Unknown' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="sf-empty">
                No notes available.
            </div>
        @endforelse
    </div>

</div>