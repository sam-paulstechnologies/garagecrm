@php
    use Illuminate\Support\Facades\Route;

    $isManagerArea = request()->routeIs('manager.*');
    $isAdminArea = request()->routeIs('admin.*') || ! $isManagerArea;

    $brandUrl = url('/');

    if ($isManagerArea && Route::has('manager.dashboard')) {
        $brandUrl = route('manager.dashboard');
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

    $primaryNavItems = $isManagerArea
        ? [
            ['label' => 'Dashboard', 'route' => 'manager.dashboard', 'active' => 'manager.dashboard'],
            ['label' => 'Clients', 'route' => 'manager.clients.index', 'active' => 'manager.clients.*'],
            ['label' => 'Leads', 'route' => 'manager.leads.index', 'active' => 'manager.leads.*'],
            ['label' => 'Opportunities', 'route' => 'manager.opportunities.index', 'active' => 'manager.opportunities.*'],
            ['label' => 'Bookings', 'route' => 'manager.bookings.index', 'active' => 'manager.bookings.*'],
            ['label' => 'Jobs', 'route' => 'manager.jobs.index', 'active' => 'manager.jobs.*'],
            ['label' => 'Invoices', 'route' => 'manager.invoices.index', 'active' => 'manager.invoices.*'],
            ['label' => 'Inbox', 'route' => 'manager.inbox.index', 'active' => 'manager.inbox.*'],
            ['label' => 'Team', 'route' => 'manager.team.index', 'active' => 'manager.team.*'],
        ]
        : [
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

    /*
    |--------------------------------------------------------------------------
    | Growth menu
    |--------------------------------------------------------------------------
    | Lead capture, audience building, templates, and event/template mapping.
    |--------------------------------------------------------------------------
    */
    $growthActive =
        request()->routeIs('admin.lead-sources.*') ||
        request()->routeIs('admin.audience-segmentations.*') ||
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
            'label' => 'Audience Segmentation',
            'description' => 'Customer buckets and campaign groups',
            'route' => 'admin.audience-segmentations.index',
            'active' => 'admin.audience-segmentations.*',
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

    /*
    |--------------------------------------------------------------------------
    | Settings menu
    |--------------------------------------------------------------------------
    | Actual setup/configuration pages only.
    |--------------------------------------------------------------------------
    */
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

    if ($isManagerArea && Route::has('manager.profile.edit')) {
        $profileRoute = route('manager.profile.edit');
    } elseif (Route::has('admin.profile.edit')) {
        $profileRoute = route('admin.profile.edit');
    }

    $userInitial = 'S';

    if (auth()->check() && ! empty(auth()->user()->name)) {
        $userInitial = strtoupper(substr(auth()->user()->name, 0, 1));
    }
@endphp

<nav
    x-data="{
        open: false,
        growthOpen: false,
        settingsOpen: false,
        userOpen: false,
        toggleGrowth() {
            this.growthOpen = !this.growthOpen;
            this.settingsOpen = false;
            this.userOpen = false;
        },
        toggleSettings() {
            this.settingsOpen = !this.settingsOpen;
            this.growthOpen = false;
            this.userOpen = false;
        },
        toggleUser() {
            this.userOpen = !this.userOpen;
            this.growthOpen = false;
            this.settingsOpen = false;
        },
        closeAll() {
            this.growthOpen = false;
            this.settingsOpen = false;
            this.userOpen = false;
        }
    }"
    class="sticky top-0 z-40 border-b border-white/10 bg-[#050914]/95 shadow-lg shadow-black/20 backdrop-blur"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        <div class="flex min-h-16 items-center justify-between gap-4">

            {{-- Left: Brand + Desktop Nav --}}
            <div class="flex min-w-0 items-center gap-6">

                {{-- Brand --}}
                <a href="{{ $brandUrl }}"
                   class="flex shrink-0 items-center gap-3 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]">

                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                        SF
                    </span>

                    <span class="hidden leading-tight sm:block">
                        <span class="block text-sm font-extrabold tracking-tight text-white">
                            SayaraForce
                        </span>

                        <span class="mt-0.5 inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-300 ring-1 ring-orange-400/20">
                            {{ $activePackageName }}
                        </span>
                    </span>
                </a>

                {{-- Desktop Primary Nav --}}
                <div class="hidden items-center gap-1 lg:flex">
                    @foreach($primaryNavItems as $item)
                        @if(Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               class="rounded-xl px-3 py-2 text-sm font-bold transition
                                      {{ request()->routeIs($item['active'])
                                            ? 'bg-white/10 text-white ring-1 ring-white/10'
                                            : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach

                    @if($isAdminArea)

                        {{-- Growth Dropdown --}}
                        <div class="relative">
                            <button
                                type="button"
                                @click="toggleGrowth()"
                                class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm font-bold transition
                                       {{ $growthActive
                                            ? 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/20'
                                            : 'text-slate-400 hover:bg-white/5 hover:text-orange-300' }}"
                            >
                                <span>Growth</span>

                                <svg class="h-4 w-4 transition-transform"
                                     :class="{ 'rotate-180': growthOpen }"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="growthOpen"
                                x-transition.origin.top.right
                                @click.outside="growthOpen = false"
                                class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40"
                            >
                                <div class="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                    <p class="text-sm font-extrabold text-white">Growth Engine</p>
                                    <p class="text-xs font-medium text-slate-400">
                                        Lead capture, segments, templates, and journeys
                                    </p>
                                </div>

                                <div class="p-2">
                                    @foreach($growthItems as $item)
                                        @if(Route::has($item['route']))
                                            <a href="{{ route($item['route']) }}"
                                               class="block rounded-xl px-4 py-3 transition
                                                      {{ request()->routeIs($item['active'])
                                                            ? 'bg-orange-500/10 text-orange-300'
                                                            : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                                <span class="block text-sm font-extrabold">
                                                    {{ $item['label'] }}
                                                </span>

                                                <span class="mt-0.5 block text-xs font-medium text-slate-500">
                                                    {{ $item['description'] }}
                                                </span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Settings Dropdown --}}
                        <div class="relative">
                            <button
                                type="button"
                                @click="toggleSettings()"
                                class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm font-bold transition
                                       {{ $settingsActive
                                            ? 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/20'
                                            : 'text-slate-400 hover:bg-white/5 hover:text-orange-300' }}"
                            >
                                <span>Settings</span>

                                <svg class="h-4 w-4 transition-transform"
                                     :class="{ 'rotate-180': settingsOpen }"
                                     fill="none"
                                     viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="settingsOpen"
                                x-transition.origin.top.right
                                @click.outside="settingsOpen = false"
                                class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40"
                            >
                                <div class="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                    <p class="text-sm font-extrabold text-white">Admin Settings</p>
                                    <p class="text-xs font-medium text-slate-400">
                                        Setup, integrations, WhatsApp controls, and AI
                                    </p>
                                </div>

                                <div class="p-2">
                                    @foreach($settingsItems as $item)
                                        @if(Route::has($item['route']))
                                            <a href="{{ route($item['route']) }}"
                                               class="block rounded-xl px-4 py-3 transition
                                                      {{ request()->routeIs($item['active'])
                                                            ? 'bg-orange-500/10 text-orange-300'
                                                            : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                                <span class="block text-sm font-extrabold">
                                                    {{ $item['label'] }}
                                                </span>

                                                <span class="mt-0.5 block text-xs font-medium text-slate-500">
                                                    {{ $item['description'] }}
                                                </span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: User Initial Only --}}
            <div class="hidden items-center gap-3 sm:flex">
                @auth
                    <div class="relative">
                        <button
                            type="button"
                            @click="toggleUser()"
                            class="inline-flex h-10 items-center justify-center gap-1.5 rounded-2xl border border-white/10 bg-gradient-to-br from-orange-500 to-orange-700 px-3 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40 transition hover:border-orange-300/40 hover:scale-[1.03] focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]"
                            title="{{ auth()->user()->name }}"
                        >
                            <span>{{ $userInitial }}</span>

                            <svg class="h-3.5 w-3.5 text-orange-100 transition-transform"
                                 :class="{ 'rotate-180': userOpen }"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2.5"
                                      d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="userOpen"
                            x-transition.origin.top.right
                            @click.outside="userOpen = false"
                            class="absolute right-0 z-50 mt-3 w-64 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40"
                        >
                            <div class="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white">
                                        {{ $userInitial }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-extrabold text-white">
                                            {{ auth()->user()->name }}
                                        </p>

                                        <p class="truncate text-xs font-medium text-slate-400">
                                            {{ auth()->user()->email }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-3">
                                    <span class="inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-300 ring-1 ring-orange-400/20">
                                        {{ $activePackageName }}
                                    </span>
                                </p>
                            </div>

                            <div class="p-2">
                                @if($profileRoute)
                                    <a href="{{ $profileRoute }}"
                                       class="block rounded-xl px-4 py-2 text-sm font-bold text-slate-400 transition hover:bg-white/5 hover:text-white">
                                        Profile
                                    </a>
                                @endif

                                @if(Route::has('logout'))
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                                class="block w-full rounded-xl px-4 py-2 text-left text-sm font-bold text-red-300 transition hover:bg-red-500/10 hover:text-red-200">
                                            Log Out
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endauth
            </div>

            {{-- Mobile Hamburger --}}
            <div class="flex items-center lg:hidden">
                <button
                    type="button"
                    @click="open = !open; growthOpen = false; settingsOpen = false; userOpen = false;"
                    class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-2 text-slate-300 shadow-sm transition hover:border-orange-400/30 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path
                            :class="{ 'hidden': open, 'inline-flex': !open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{ 'hidden': !open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-cloak
         x-show="open"
         x-transition
         class="border-t border-white/10 bg-[#050914] lg:hidden">

        <div class="space-y-1 px-4 py-4">
            @foreach($primaryNavItems as $item)
                @if(Route::has($item['route']))
                    <a href="{{ route($item['route']) }}"
                       class="block rounded-2xl px-4 py-3 text-sm font-bold transition
                              {{ request()->routeIs($item['active'])
                                    ? 'bg-white/10 text-white'
                                    : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach

            @if($isAdminArea)
                <div class="my-3 border-t border-white/10"></div>

                <div class="px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                    Growth
                </div>

                @foreach($growthItems as $item)
                    @if(Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-2xl px-4 py-3 text-sm font-bold transition
                                  {{ request()->routeIs($item['active'])
                                        ? 'bg-orange-500/10 text-orange-300'
                                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach

                <div class="my-3 border-t border-white/10"></div>

                <div class="px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                    Settings
                </div>

                @foreach($settingsItems as $item)
                    @if(Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                           class="block rounded-2xl px-4 py-3 text-sm font-bold transition
                                  {{ request()->routeIs($item['active'])
                                        ? 'bg-orange-500/10 text-orange-300'
                                        : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            @endif
        </div>

        @auth
            <div class="border-t border-white/10 px-4 py-4">
                <div class="rounded-2xl border border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white">
                            {{ $userInitial }}
                        </div>

                        <div class="min-w-0">
                            <div class="truncate font-extrabold text-white">
                                {{ auth()->user()->name }}
                            </div>

                            <div class="mt-1 truncate text-sm font-medium text-slate-400">
                                {{ auth()->user()->email }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-300 ring-1 ring-orange-400/20">
                            {{ $activePackageName }}
                        </span>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    @if($profileRoute)
                        <a href="{{ $profileRoute }}"
                           class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-400 transition hover:bg-white/5 hover:text-white">
                            Profile
                        </a>
                    @endif

                    @if(Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="block w-full rounded-2xl px-4 py-3 text-left text-sm font-bold text-red-300 transition hover:bg-red-500/10 hover:text-red-200">
                                Log Out
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endauth
    </div>
</nav>