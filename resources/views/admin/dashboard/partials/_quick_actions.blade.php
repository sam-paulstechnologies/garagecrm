{{-- resources/views/admin/dashboard/partials/_quick_actions.blade.php --}}

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="mb-5">
        <h3 class="text-base font-semibold text-white">Quick Actions</h3>
        <p class="mt-1 text-xs text-slate-400">
            Create records quickly without leaving the dashboard flow.
        </p>
    </div>

    @php
        $actions = [
            [
                'label' => 'Add Lead',
                'route' => 'admin.leads.create',
                'classes' => 'border-blue-500/30 bg-blue-600/15 text-blue-200 hover:bg-blue-600/25',
            ],
            [
                'label' => 'Add Client',
                'route' => 'admin.clients.create',
                'classes' => 'border-slate-700 bg-slate-800 text-slate-100 hover:bg-slate-700',
            ],
            [
                'label' => 'New Booking',
                'route' => 'admin.bookings.create',
                'classes' => 'border-orange-400 bg-orange-500 text-white hover:bg-orange-600',
            ],
            [
                'label' => 'New Opportunity',
                'route' => 'admin.opportunities.create',
                'classes' => 'border-orange-400 bg-orange-500 text-white hover:bg-orange-600',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($actions as $action)
            @if (\Illuminate\Support\Facades\Route::has($action['route']))
                <a
                    href="{{ route($action['route']) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition {{ $action['classes'] }}"
                >
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/10">
                        +
                    </span>
                    {{ $action['label'] }}
                </a>
            @else
                <button
                    type="button"
                    disabled
                    class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-slate-800 bg-slate-800/40 px-4 py-3 text-sm font-semibold text-slate-500"
                >
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/5">
                        +
                    </span>
                    {{ $action['label'] }}
                </button>
            @endif
        @endforeach
    </div>
</div>