{{-- resources/views/admin/clients/show-partials/sections/_notes_section.blade.php --}}

@php
    $notes = collect($client->notes ?? []);

    $notesRoute = \Illuminate\Support\Facades\Route::has('admin.clients.notes.index')
        ? route('admin.clients.notes.index', $client->id)
        : null;

    $storeNoteRoute = \Illuminate\Support\Facades\Route::has('admin.clients.notes.store')
        ? route('admin.clients.notes.store', $client->id)
        : null;

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-notes-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-notes-title {
        color: #ffffff;
    }

    .sf-notes-muted {
        color: #cbd5e1;
    }

    .sf-notes-form {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.35);
    }

    .sf-notes-input {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.55);
        color: #ffffff;
    }

    .sf-notes-input::placeholder {
        color: #64748b;
    }

    .sf-notes-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-notes-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-notes-secondary-btn {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    html[data-theme="light"] .sf-notes-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-notes-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-notes-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-notes-form {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-notes-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-notes-input::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-notes-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-notes-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-notes-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
</style>

<section id="notes" class="sf-notes-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-notes-title text-lg font-extrabold tracking-tight">
                Notes
            </h2>

            <p class="sf-notes-muted mt-1 text-sm font-medium">
                Internal notes and context from the garage team.
            </p>
        </div>

        @if($notesRoute)
            <a
                href="{{ $notesRoute }}"
                class="sf-notes-secondary-btn inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
            >
                View All
            </a>
        @endif
    </div>

    <div class="sf-notes-form rounded-2xl border p-4">
        <h3 class="sf-notes-title text-base font-extrabold">
            Add Quick Note
        </h3>

        @if($storeNoteRoute)
            <form action="{{ $storeNoteRoute }}" method="POST" class="mt-3">
                @csrf

                <textarea
                    name="content"
                    rows="3"
                    class="sf-notes-input w-full rounded-xl border px-3 py-2 text-sm font-semibold outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    placeholder="Type a quick note and press Add..."
                ></textarea>

                <button
                    type="submit"
                    class="mt-3 inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    Add Note
                </button>
            </form>
        @else
            <div class="sf-notes-empty mt-3 rounded-2xl border border-dashed p-5 text-center text-sm font-semibold">
                Note route is not configured.
            </div>
        @endif
    </div>

    <div class="mt-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="sf-notes-title text-base font-extrabold">
                Recent Notes
            </h3>

            <span class="inline-flex rounded-full border border-slate-500/20 bg-slate-500/10 px-3 py-1 text-xs font-black text-slate-300">
                Latest {{ $notes->count() }}
            </span>
        </div>

        @if($notes->isNotEmpty())
            <div class="space-y-3">
                @foreach($notes->take(3) as $note)
                    <div class="sf-notes-card rounded-2xl border p-4">
                        <p class="sf-notes-title text-sm font-bold">
                            {{ $note->content ?? $note->note ?? '' }}
                        </p>

                        <p class="sf-notes-muted mt-2 text-xs font-medium">
                            {{ $formatDate($note->created_at ?? null) }}
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="sf-notes-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
                No notes available.
            </div>
        @endif
    </div>
</section>