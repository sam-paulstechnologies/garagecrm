{{-- resources/views/layouts/navigation.blade.php --}}

@php
    use Illuminate\Support\Facades\Route;

    $useAdminFullWidthShell = $useAdminFullWidthShell ?? false;
    $isSuperAdmin = auth()->check()
        && strtolower(trim((string) auth()->user()->role)) === 'super_admin';
    $isSuperAdminArea = request()->routeIs('super-admin.*');
    $isManagerArea = request()->routeIs('manager.*');
    $isAdminArea = request()->routeIs('admin.*') || (! $isManagerArea && ! $isSuperAdminArea);
    $isMediaTeam = auth()->check()
        && strtolower(trim((string) auth()->user()->role)) === 'media_team';

    $brandUrl = url('/');

    if ($isSuperAdmin && Route::has('super-admin.dashboard')) {
        $brandUrl = route('super-admin.dashboard');
    } elseif ($isManagerArea && Route::has('manager.dashboard')) {
        $brandUrl = route('manager.dashboard');
    } elseif ($isMediaTeam && Route::has('admin.lead-sources.meta')) {
        $brandUrl = route('admin.lead-sources.meta');
    } elseif (Route::has('admin.dashboard')) {
        $brandUrl = route('admin.dashboard');
    }

    $activePackageName = 'Growth Plan';

    if (auth()->check()) {
        $user = auth()->user();

        $company = null;

        try {
            $company = $user->company ?? null;
        } catch (\Throwable $e) {
            $company = null;
        }

        $possiblePackageName =
            data_get($company, 'package_name') ??
            data_get($company, 'subscription_plan') ??
            data_get($company, 'plan_name') ??
            data_get($company, 'active_package') ??
            null;

        if (! empty($possiblePackageName)) {
            $activePackageName = str($possiblePackageName)->headline()->toString();

            if (! str($activePackageName)->contains('Plan')) {
                $activePackageName .= ' Plan';
            }
        }
    }

    if ($isSuperAdmin) {
        $primaryNavItems = [
            ['label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'active' => 'super-admin.dashboard'],
            ['label' => 'Garages', 'route' => 'super-admin.garages.index', 'active' => 'super-admin.garages.*'],
            ['label' => 'Message Logs', 'route' => 'super-admin.logs.messages', 'active' => 'super-admin.logs.messages'],
            ['label' => 'Lead Logs', 'route' => 'super-admin.logs.leads', 'active' => 'super-admin.logs.leads'],
            ['label' => 'System Health', 'route' => 'super-admin.system.health', 'active' => 'super-admin.system.*'],
            ['label' => 'Audit', 'route' => 'super-admin.audit.index', 'active' => 'super-admin.audit.*'],
        ];
    } elseif ($isManagerArea) {
        $primaryNavItems = [
            ['label' => 'Dashboard', 'route' => 'manager.dashboard', 'active' => 'manager.dashboard'],
            ['label' => 'Leads', 'route' => 'manager.leads.index', 'active' => 'manager.leads.*'],
            ['label' => 'Opportunities', 'route' => 'manager.opportunities.index', 'active' => 'manager.opportunities.*'],
            ['label' => 'Bookings', 'route' => 'manager.bookings.index', 'active' => 'manager.bookings.*'],
            ['label' => 'Jobs', 'route' => 'manager.jobs.index', 'active' => 'manager.jobs.*'],
            ['label' => 'Invoices', 'route' => 'manager.invoices.index', 'active' => 'manager.invoices.*'],
            ['label' => 'Clients', 'route' => 'manager.clients.index', 'active' => 'manager.clients.*'],
            ['label' => 'Reports', 'route' => 'manager.growth.index', 'active' => 'manager.growth.*'],
            ['label' => 'Inbox', 'route' => 'manager.inbox.index', 'active' => 'manager.inbox.*'],
        ];
    } elseif ($isMediaTeam) {
        $primaryNavItems = [
            ['label' => 'Meta Forms', 'route' => 'admin.lead-sources.meta', 'active' => 'admin.lead-sources.meta*'],
        ];
    } else {
        $primaryNavItems = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
            ['label' => 'Clients', 'route' => 'admin.clients.index', 'active' => 'admin.clients.*'],
            ['label' => 'Leads', 'route' => 'admin.leads.index', 'active' => 'admin.leads.*'],
            ['label' => 'Opportunities', 'route' => 'admin.opportunities.index', 'active' => 'admin.opportunities.*'],
            ['label' => 'Bookings', 'route' => 'admin.bookings.index', 'active' => 'admin.bookings.*'],
            ['label' => 'Jobs', 'route' => 'admin.jobs.index', 'active' => 'admin.jobs.*'],
            ['label' => 'Invoices', 'route' => 'admin.invoices.index', 'active' => 'admin.invoices.*'],
            ['label' => 'Calendar', 'route' => 'admin.calendar.index', 'active' => 'admin.calendar.*'],
            ['label' => 'Inbox', 'route' => 'admin.inbox.index', 'active' => 'admin.inbox.*'],
        ];
    }

    $growthActive =
        request()->routeIs('admin.lead-sources.*') ||
        request()->routeIs('admin.growth.*') ||
        request()->routeIs('admin.audience-segmentations.*') ||
        request()->routeIs('admin.retention-actions.index') ||
        request()->routeIs('admin.whatsapp.templates.*') ||
        request()->routeIs('admin.whatsapp.mappings.*');

    $growthItems = [
        [
            'label' => 'Lead Sources',
            'description' => 'WhatsApp, website forms, and Meta lead ads',
            'route' => 'admin.lead-sources.index',
            'active' => 'admin.lead-sources.*',
        ],
        [
            'label' => 'Journey Mapping',
            'description' => 'Map campaign types to preview journeys',
            'route' => 'admin.growth.journey-mapping.index',
            'active' => 'admin.growth.journey-mapping.*',
        ],
        [
            'label' => 'Audience Segmentation',
            'description' => 'Customer buckets and campaign groups',
            'route' => 'admin.audience-segmentations.index',
            'active' => 'admin.audience-segmentations.*',
        ],
        [
            'label' => 'Retention Actions',
            'description' => 'Review imported follow-up suggestions',
            'route' => 'admin.retention-actions.index',
            'active' => 'admin.retention-actions.index',
        ],
        [
            'label' => 'WhatsApp Templates',
            'description' => 'Approved and internal WhatsApp templates',
            'route' => 'admin.whatsapp.templates.index',
            'active' => 'admin.whatsapp.templates.*',
        ],
        [
            'label' => 'Template Mappings',
            'description' => 'Map events to message templates',
            'route' => 'admin.whatsapp.mappings.index',
            'active' => 'admin.whatsapp.mappings.*',
        ],
    ];

    $reportsActive =
        request()->routeIs('admin.reports.*') ||
        request()->routeIs('admin.retention-actions.report');

    $reportsItems = [
        [
            'label' => 'Garage Summary',
            'description' => 'EOD, EOW, and EOM operating summaries',
            'route' => 'admin.reports.garage-summary',
            'active' => 'admin.reports.garage-summary',
        ],
        [
            'label' => 'Retention Report',
            'description' => 'Retention follow-up and review performance',
            'route' => 'admin.retention-actions.report',
            'active' => 'admin.retention-actions.report',
        ],
    ];

    $settingsActive =
        request()->routeIs('admin.settings.launch-setup.*') ||
        request()->routeIs('admin.settings.index') ||
        request()->routeIs('admin.ai.*') ||
        request()->routeIs('admin.whatsapp.settings.*');

    $settingsItems = [
        [
            'label' => 'Launch Setup',
            'description' => 'Garage setup, manager handoff, working hours',
            'route' => 'admin.settings.launch-setup.edit',
            'active' => 'admin.settings.launch-setup.*',
        ],
        [
            'label' => 'Integration Settings',
            'description' => 'Tenant profile, Meta, Twilio, and system defaults',
            'route' => 'admin.settings.index',
            'active' => 'admin.settings.index',
        ],
        [
            'label' => 'WhatsApp Controls',
            'description' => 'Automation, review link, UAT reset',
            'route' => 'admin.whatsapp.settings.edit',
            'active' => 'admin.whatsapp.settings.*',
        ],
        [
            'label' => 'AI Control Center',
            'description' => 'AI replies, confidence, safety, and handoff rules',
            'route' => 'admin.ai.edit',
            'active' => 'admin.ai.*',
        ],
    ];

    $profileRoute = null;

    if ($isSuperAdmin) {
        $profileRoute = null;
    } elseif ($isManagerArea && Route::has('manager.profile.edit')) {
        $profileRoute = route('manager.profile.edit');
    } elseif (Route::has('admin.profile.edit')) {
        $profileRoute = route('admin.profile.edit');
    } elseif (Route::has('profile.edit')) {
        $profileRoute = route('profile.edit');
    }

    $logoutRoute = Route::has('logout') ? route('logout') : url('/logout');

    $userName = auth()->check() ? auth()->user()->name : 'User';
    $userEmail = auth()->check() ? auth()->user()->email : '';
    $userInitial = ! empty($userName) ? strtoupper(substr($userName, 0, 1)) : 'S';
@endphp

<style>
    .sf-nav {
        border-color: rgba(255, 255, 255, 0.10) !important;
        background: rgba(5, 9, 20, 0.95) !important;
        color: #f8fafc !important;
    }

    .sf-nav-brand-name {
        color: #ffffff !important;
    }

    .sf-nav-link-idle,
    .sf-nav-dropdown-button-idle {
        color: #94a3b8 !important;
    }

    .sf-nav-link-idle:hover,
    .sf-nav-dropdown-button-idle:hover {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
    }

    .sf-nav-link-active {
        background: rgba(255, 255, 255, 0.10) !important;
        color: #ffffff !important;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.10);
    }

    .sf-nav-menu {
        background: #020617 !important;
        border-color: rgba(255, 255, 255, 0.10) !important;
    }

    .sf-nav-menu-item:hover {
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .sf-nav-menu-title {
        color: #ffffff !important;
    }

    .sf-nav-menu-desc {
        color: #94a3b8 !important;
    }

    .sf-nav-user-card,
    .sf-nav-mobile-menu {
        background: #050914 !important;
        border-color: rgba(255, 255, 255, 0.10) !important;
    }

    .sf-nav-mobile-card {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.10) !important;
    }

    .sf-nav-mobile-link-idle {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #cbd5e1 !important;
    }

    .sf-nav-mobile-link-idle:hover {
        background: rgba(255, 255, 255, 0.10) !important;
        color: #ffffff !important;
    }

    .sf-nav-muted {
        color: #94a3b8 !important;
    }

    .sf-nav-section-label {
        color: #64748b !important;
    }

    .sf-nav-burger {
        background: rgba(255, 255, 255, 0.10) !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-nav {
        background: rgba(255, 255, 255, 0.96) !important;
        border-color: #d9e1ec !important;
        color: #0f172a !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06) !important;
    }

    html[data-theme="light"] .sf-nav-brand-name {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-nav-link-idle,
    html[data-theme="light"] .sf-nav-dropdown-button-idle {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-nav-link-idle:hover,
    html[data-theme="light"] .sf-nav-dropdown-button-idle:hover {
        background: #f1f5f9 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-nav-link-active {
        background: #e2e8f0 !important;
        color: #0f172a !important;
        box-shadow: inset 0 0 0 1px #cbd5e1;
    }

    html[data-theme="light"] .sf-nav-menu {
        background: #ffffff !important;
        border-color: #d9e1ec !important;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16) !important;
    }

    html[data-theme="light"] .sf-nav-menu-item:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-nav-menu-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-nav-menu-desc {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-nav-user-card,
    html[data-theme="light"] .sf-nav-mobile-menu {
        background: #ffffff !important;
        border-color: #d9e1ec !important;
    }

    html[data-theme="light"] .sf-nav-mobile-card {
        background: #f8fafc !important;
        border-color: #d9e1ec !important;
    }

    html[data-theme="light"] .sf-nav-mobile-link-idle {
        background: #f8fafc !important;
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-nav-mobile-link-idle:hover {
        background: #e2e8f0 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-nav-muted {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-nav-section-label {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-nav-burger {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    html[data-theme="light"] .sf-nav-divider {
        border-color: #e2e8f0 !important;
    }
</style>

<nav
    x-data="{
        open: false,
        growthOpen: false,
        settingsOpen: false,
        reportsOpen: false,
        userOpen: false,

        toggleMobile() {
            this.open = !this.open;
            this.growthOpen = false;
            this.settingsOpen = false;
            this.reportsOpen = false;
            this.userOpen = false;
        },

        toggleGrowth() {
            this.growthOpen = !this.growthOpen;
            this.settingsOpen = false;
            this.reportsOpen = false;
            this.userOpen = false;
        },

        toggleSettings() {
            this.settingsOpen = !this.settingsOpen;
            this.growthOpen = false;
            this.reportsOpen = false;
            this.userOpen = false;
        },

        toggleReports() {
            this.reportsOpen = !this.reportsOpen;
            this.growthOpen = false;
            this.settingsOpen = false;
            this.userOpen = false;
        },

        toggleUser() {
            this.userOpen = !this.userOpen;
            this.growthOpen = false;
            this.settingsOpen = false;
            this.reportsOpen = false;
        },

        closeAll() {
            this.growthOpen = false;
            this.settingsOpen = false;
            this.reportsOpen = false;
            this.userOpen = false;
        }
    }"
    class="sf-nav sticky top-0 z-40 border-b shadow-lg backdrop-blur"
>
    <div class="{{ $useAdminFullWidthShell ? 'max-w-none' : 'mx-auto max-w-7xl' }} px-4 sm:px-6 lg:px-8">
        <div class="relative flex min-h-16 items-center justify-between gap-4">

            {{-- Left: Brand --}}
            <div class="flex min-w-0 items-center">
                <a
                    href="{{ $brandUrl }}"
                    class="flex shrink-0 items-center gap-3 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-400"
                >
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                        SF
                    </span>

                    <span class="hidden leading-tight sm:block">
                        <span class="sf-nav-brand-name block text-sm font-extrabold tracking-tight">
                            SayaraForce
                        </span>

                        <span class="mt-0.5 inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-500 ring-1 ring-orange-400/20">
                            {{ $activePackageName }}
                        </span>
                    </span>
                </a>
            </div>

            {{-- Center: Desktop Primary Nav --}}
            <div class="hidden items-center gap-1 {{ $useAdminFullWidthShell ? 'lg:flex' : 'xl:flex' }}">
                @foreach($primaryNavItems as $item)
                    @if(Route::has($item['route']))
                        <a
                            href="{{ route($item['route']) }}"
                            class="rounded-xl px-3 py-2 text-xs font-bold transition
                                {{ request()->routeIs($item['active'])
                                    ? 'sf-nav-link-active'
                                    : 'sf-nav-link-idle' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach

                @if($isAdminArea && ! $isMediaTeam)
                    {{-- Reports Dropdown --}}
                    <div class="relative" @click.outside="reportsOpen = false">
                        <button
                            type="button"
                            @click="toggleReports()"
                            class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-bold transition
                                {{ $reportsActive
                                    ? 'bg-orange-500/10 text-orange-400 ring-1 ring-orange-400/20'
                                    : 'sf-nav-dropdown-button-idle' }}"
                        >
                            <span>Reports</span>

                            <svg
                                class="h-4 w-4 transition-transform"
                                :class="{ 'rotate-180': reportsOpen }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="reportsOpen"
                            x-transition
                            class="sf-nav-menu absolute right-0 mt-3 w-80 rounded-2xl border p-2 shadow-2xl"
                        >
                            @foreach($reportsItems as $item)
                                @if(Route::has($item['route']))
                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="sf-nav-menu-item block rounded-xl px-4 py-3 transition
                                            {{ request()->routeIs($item['active'])
                                                ? 'bg-orange-500/10 text-orange-400'
                                                : '' }}"
                                    >
                                        <span class="sf-nav-menu-title block text-sm font-extrabold">
                                            {{ $item['label'] }}
                                        </span>

                                        <span class="sf-nav-menu-desc mt-1 block text-xs leading-5">
                                            {{ $item['description'] }}
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Growth Dropdown --}}
                    <div class="relative" @click.outside="growthOpen = false">
                        <button
                            type="button"
                            @click="toggleGrowth()"
                            class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-bold transition
                                {{ $growthActive
                                    ? 'bg-orange-500/10 text-orange-400 ring-1 ring-orange-400/20'
                                    : 'sf-nav-dropdown-button-idle' }}"
                        >
                            <span>Growth</span>

                            <svg
                                class="h-4 w-4 transition-transform"
                                :class="{ 'rotate-180': growthOpen }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="growthOpen"
                            x-transition
                            class="sf-nav-menu absolute right-0 mt-3 w-80 rounded-2xl border p-2 shadow-2xl"
                        >
                            @foreach($growthItems as $item)
                                @if(Route::has($item['route']))
                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="sf-nav-menu-item block rounded-xl px-4 py-3 transition
                                            {{ request()->routeIs($item['active'])
                                                ? 'bg-orange-500/10 text-orange-400'
                                                : '' }}"
                                    >
                                        <span class="sf-nav-menu-title block text-sm font-extrabold">
                                            {{ $item['label'] }}
                                        </span>

                                        <span class="sf-nav-menu-desc mt-1 block text-xs leading-5">
                                            {{ $item['description'] }}
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Settings Dropdown --}}
                    <div class="relative" @click.outside="settingsOpen = false">
                        <button
                            type="button"
                            @click="toggleSettings()"
                            class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-xs font-bold transition
                                {{ $settingsActive
                                    ? 'bg-orange-500/10 text-orange-400 ring-1 ring-orange-400/20'
                                    : 'sf-nav-dropdown-button-idle' }}"
                        >
                            <span>Settings</span>

                            <svg
                                class="h-4 w-4 transition-transform"
                                :class="{ 'rotate-180': settingsOpen }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="settingsOpen"
                            x-transition
                            class="sf-nav-menu absolute right-0 mt-3 w-80 rounded-2xl border p-2 shadow-2xl"
                        >
                            @foreach($settingsItems as $item)
                                @if(Route::has($item['route']))
                                    <a
                                        href="{{ route($item['route']) }}"
                                        class="sf-nav-menu-item block rounded-xl px-4 py-3 transition
                                            {{ request()->routeIs($item['active'])
                                                ? 'bg-orange-500/10 text-orange-400'
                                                : '' }}"
                                    >
                                        <span class="sf-nav-menu-title block text-sm font-extrabold">
                                            {{ $item['label'] }}
                                        </span>

                                        <span class="sf-nav-menu-desc mt-1 block text-xs leading-5">
                                            {{ $item['description'] }}
                                        </span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Desktop Profile --}}
            <div class="hidden items-center {{ $useAdminFullWidthShell ? 'lg:flex' : 'xl:flex' }}">
                <div class="relative" @click.outside="userOpen = false">
                    <button
                        type="button"
                        @click="toggleUser()"
                        class="inline-flex h-10 items-center gap-2 rounded-2xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                    >
                        <span>{{ $userInitial }}</span>

                        <svg
                            class="h-4 w-4 transition-transform"
                            :class="{ 'rotate-180': userOpen }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div
                        x-cloak
                        x-show="userOpen"
                        x-transition
                        class="sf-nav-menu absolute right-0 mt-3 w-72 rounded-2xl border p-2 shadow-2xl"
                    >
                        <div class="sf-nav-divider border-b px-4 py-3">
                            <div class="sf-nav-menu-title text-sm font-extrabold">
                                {{ $userName }}
                            </div>

                            <div class="sf-nav-muted mt-1 truncate text-xs">
                                {{ $userEmail }}
                            </div>
                        </div>

                        @if($profileRoute && ! $isMediaTeam)
                            <a
                                href="{{ $profileRoute }}"
                                class="sf-nav-menu-item mt-2 block rounded-xl px-4 py-3 text-sm font-bold transition"
                            >
                                <span class="sf-nav-menu-title">Profile</span>
                            </a>
                        @endif

                        <button
                            type="button"
                            data-sf-theme-toggle
                            aria-pressed="false"
                            class="sf-nav-menu-item mt-2 flex w-full items-center justify-between gap-3 rounded-xl px-4 py-3 text-left transition"
                        >
                            <span class="min-w-0">
                                <span class="sf-nav-menu-title block text-sm font-bold">Theme</span>
                                <span class="sf-nav-muted mt-1 block text-xs" data-sf-theme-label>Dark mode</span>
                            </span>

                            <span class="inline-flex items-center gap-2">
                                <span class="text-sm" data-sf-theme-icon>🌙</span>
                                <span class="sf-theme-switch" aria-hidden="true">
                                    <span class="sf-theme-switch-knob"></span>
                                </span>
                            </span>
                        </button>

                        <form method="POST" action="{{ $logoutRoute }}">
                            @csrf

                            <button
                                type="submit"
                                class="block w-full rounded-xl px-4 py-3 text-left text-sm font-bold text-red-500 transition hover:bg-red-500/10"
                            >
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile: Center Burger --}}
            <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 {{ $useAdminFullWidthShell ? 'lg:hidden' : 'xl:hidden' }}">
                <button
                    type="button"
                    @click="toggleMobile()"
                    class="sf-nav-burger inline-flex h-11 w-11 items-center justify-center rounded-2xl border transition"
                    aria-label="Open menu"
                >
                    <svg
                        x-show="!open"
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>

                    <svg
                        x-cloak
                        x-show="open"
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile: right spacer so theme toggle has room --}}
            <div class="h-11 w-11 {{ $useAdminFullWidthShell ? 'lg:hidden' : 'xl:hidden' }}"></div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div
        x-cloak
        x-show="open"
        x-transition
        class="sf-nav-mobile-menu border-t {{ $useAdminFullWidthShell ? 'lg:hidden' : 'xl:hidden' }}"
    >
        <div class="space-y-3 px-4 py-4">

            {{-- Mobile Profile --}}
            <div class="sf-nav-mobile-card rounded-2xl border p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-sm font-extrabold text-white">
                        {{ $userInitial }}
                    </div>

                    <div class="min-w-0">
                        <div class="sf-nav-menu-title truncate text-sm font-extrabold">
                            {{ $userName }}
                        </div>

                        <div class="sf-nav-muted truncate text-xs">
                            {{ $userEmail }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    @if($profileRoute && ! $isMediaTeam)
                        <a
                            href="{{ $profileRoute }}"
                            class="rounded-xl bg-orange-500 px-3 py-2 text-center text-xs font-bold text-white transition hover:bg-orange-600"
                        >
                            Profile
                        </a>
                    @endif

                    <button
                        type="button"
                        data-sf-theme-toggle
                        aria-pressed="false"
                        class="rounded-xl border border-orange-400/30 bg-orange-500/10 px-3 py-2 text-xs font-bold text-orange-300 transition hover:bg-orange-500/20"
                    >
                        <span class="flex items-center justify-center gap-2">
                            <span data-sf-theme-icon>🌙</span>
                            <span data-sf-theme-label>Dark mode</span>
                        </span>
                    </button>

                    <form method="POST" action="{{ $logoutRoute }}">
                        @csrf

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-red-500/15 px-3 py-2 text-center text-xs font-bold text-red-500 transition hover:bg-red-500/20"
                        >
                            Logout
                        </button>
                    </form>
                </div>
            </div>

            {{-- Mobile Primary Nav --}}
            <div class="space-y-2">
                @foreach($primaryNavItems as $item)
                    @if(Route::has($item['route']))
                        <a
                            href="{{ route($item['route']) }}"
                            class="block rounded-2xl px-4 py-3 text-sm font-extrabold transition
                                {{ request()->routeIs($item['active'])
                                    ? 'bg-orange-500 text-white shadow-lg shadow-orange-950/30'
                                    : 'sf-nav-mobile-link-idle' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            @if($isAdminArea && ! $isMediaTeam)
                {{-- Mobile Reports --}}
                <div class="pt-2">
                    <div class="sf-nav-section-label px-2 pb-2 text-xs font-extrabold uppercase tracking-wide">
                        Reports
                    </div>

                    <div class="space-y-2">
                        @foreach($reportsItems as $item)
                            @if(Route::has($item['route']))
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="block rounded-2xl px-4 py-3 transition
                                        {{ request()->routeIs($item['active'])
                                            ? 'bg-orange-500/15 text-orange-500 ring-1 ring-orange-400/20'
                                            : 'sf-nav-mobile-link-idle' }}"
                                >
                                    <span class="block text-sm font-extrabold">
                                        {{ $item['label'] }}
                                    </span>

                                    <span class="sf-nav-muted mt-1 block text-xs leading-5">
                                        {{ $item['description'] }}
                                    </span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Mobile Growth --}}
                <div class="pt-2">
                    <div class="sf-nav-section-label px-2 pb-2 text-xs font-extrabold uppercase tracking-wide">
                        Growth
                    </div>

                    <div class="space-y-2">
                        @foreach($growthItems as $item)
                            @if(Route::has($item['route']))
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="block rounded-2xl px-4 py-3 transition
                                        {{ request()->routeIs($item['active'])
                                            ? 'bg-orange-500/15 text-orange-500 ring-1 ring-orange-400/20'
                                            : 'sf-nav-mobile-link-idle' }}"
                                >
                                    <span class="block text-sm font-extrabold">
                                        {{ $item['label'] }}
                                    </span>

                                    <span class="sf-nav-muted mt-1 block text-xs leading-5">
                                        {{ $item['description'] }}
                                    </span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Mobile Settings --}}
                <div class="pt-2">
                    <div class="sf-nav-section-label px-2 pb-2 text-xs font-extrabold uppercase tracking-wide">
                        Settings
                    </div>

                    <div class="space-y-2">
                        @foreach($settingsItems as $item)
                            @if(Route::has($item['route']))
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="block rounded-2xl px-4 py-3 transition
                                        {{ request()->routeIs($item['active'])
                                            ? 'bg-orange-500/15 text-orange-500 ring-1 ring-orange-400/20'
                                            : 'sf-nav-mobile-link-idle' }}"
                                >
                                    <span class="block text-sm font-extrabold">
                                        {{ $item['label'] }}
                                    </span>

                                    <span class="sf-nav-muted mt-1 block text-xs leading-5">
                                        {{ $item['description'] }}
                                    </span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</nav>
