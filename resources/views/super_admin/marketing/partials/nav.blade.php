<div class="mb-5 flex flex-wrap gap-2">
    @foreach([
        'dashboard' => ['Dashboard', route('super-admin.marketing.dashboard')],
        'prospects.*' => ['Prospects', route('super-admin.marketing.prospects.index')],
        'segments.*' => ['Segments', route('super-admin.marketing.segments.index')],
        'campaigns.*' => ['Campaigns', route('super-admin.marketing.campaigns.index')],
        'conversations.*' => ['Conversations', route('super-admin.marketing.conversations.index')],
        'appointments.*' => ['Demo Appointments', route('super-admin.marketing.appointments.index')],
        'templates.*' => ['Templates', route('super-admin.marketing.templates.index')],
        'reports.*' => ['Reports', route('super-admin.marketing.reports.index')],
        'channel.*' => ['Channel Settings', route('super-admin.marketing.channel.index')],
    ] as $pattern => [$label, $url])
        <a href="{{ $url }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.marketing.'.$pattern) ? 'bg-emerald-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">{{ $label }}</a>
    @endforeach
</div>
