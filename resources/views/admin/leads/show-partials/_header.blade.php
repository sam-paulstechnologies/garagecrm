{{-- resources/views/admin/leads/show-partials/_header.blade.php --}}

<div class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">Lead Profile</div>

                @if((bool) ($lead->is_hot ?? false))
                    <span class="{{ $badgeBase }} bg-red-500/10 text-red-300 ring-red-400/20">Hot Lead</span>
                @endif

                <span class="{{ $badgeBase }} {{ $scoreBadgeClass }}">Score: {{ $score }}/100</span>
            </div>

            <h1 class="sf-leads-show-title mt-3 text-3xl font-extrabold tracking-tight">
                Lead Details
            </h1>

            <p class="sf-leads-show-muted mt-2 max-w-3xl text-sm font-medium">
                View lead profile, source attribution, communications, and message history.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">Back</a>

            <a href="{{ route('admin.leads.edit', $lead) }}" class="sf-btn-primary">Edit Lead</a>

            <form method="POST" action="{{ route('admin.leads.toggleHot', $lead) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="sf-btn-danger">
                    {{ $lead->is_hot ? 'Unmark Hot' : 'Mark Hot' }}
                </button>
            </form>
        </div>
    </div>
</div>
