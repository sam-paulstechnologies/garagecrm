@php
    use Illuminate\Support\Facades\Route;
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- LEFT --}}
            <div class="flex">

                {{-- Logo --}}
                <div class="shrink-0 flex items-center">
                    @if(Route::has('admin.dashboard'))
                        <a href="{{ route('admin.dashboard') }}">
                            <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                        </a>
                    @endif
                </div>

                {{-- Desktop Primary Nav --}}
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">

                    @if(Route::has('admin.dashboard'))
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            Dashboard
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.clients.index'))
                        <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">
                            Clients
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.leads.index'))
                        <x-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
                            Leads
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.opportunities.index'))
                        <x-nav-link :href="route('admin.opportunities.index')" :active="request()->routeIs('admin.opportunities.*')">
                            Opportunities
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.bookings.index'))
                        <x-nav-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*')">
                            Bookings
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.jobs.index'))
                        <x-nav-link :href="route('admin.jobs.index')" :active="request()->routeIs('admin.jobs.*')">
                            Jobs
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.invoices.index'))
                        <x-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">
                            Invoices
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.calendar.index'))
                        <x-nav-link :href="route('admin.calendar.index')" :active="request()->routeIs('admin.calendar.*')">
                            Calendar
                        </x-nav-link>
                    @endif

                    @if(Route::has('admin.inbox.index'))
                        <x-nav-link :href="route('admin.inbox.index')" :active="request()->routeIs('admin.inbox.*')">
                            Inbox
                        </x-nav-link>
                    @endif

                    {{-- SETTINGS DROPDOWN --}}
                    <div x-data="{ settingsOpen: false }" class="relative">
                        <button
                            @click="settingsOpen = !settingsOpen"
                            type="button"
                            class="inline-flex items-center text-sm font-medium {{ request()->routeIs('admin.business-profile.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.ai.*') || request()->routeIs('admin.whatsapp.*') || request()->routeIs('admin.lead-sources.*') || request()->routeIs('admin.audience-segmentations.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}"
                        >
                            <span>Settings</span>
                            <svg class="ml-1 w-4 h-4 transform transition-transform"
                                 :class="{ 'rotate-180': settingsOpen }"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="settingsOpen"
                            @click.outside="settingsOpen = false"
                            class="absolute left-0 mt-2 w-64 bg-white border rounded shadow-lg z-50 py-2"
                        >
                            @if(Route::has('admin.business-profile.edit'))
                                <a href="{{ route('admin.business-profile.edit') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.business-profile.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }}">
                                    Business Profile
                                </a>
                            @endif

                            @if(Route::has('admin.settings.launch-setup.edit'))
                                <a href="{{ route('admin.settings.launch-setup.edit') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.settings.launch-setup.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }}">
                                    Launch Setup
                                </a>
                            @endif

                            @if(Route::has('admin.ai.edit'))
                                <a href="{{ route('admin.ai.edit') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.ai.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }}">
                                    AI Settings
                                </a>
                            @endif

                            @if(Route::has('admin.whatsapp.settings.edit'))
                                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.whatsapp.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }}">
                                    WhatsApp Settings
                                </a>
                            @endif

                            @if(Route::has('admin.audience-segmentations.index'))
                                <a href="{{ route('admin.audience-segmentations.index') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.audience-segmentations.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }}">
                                    Audience Segmentation
                                </a>
                            @endif

                            <div class="border-t my-2"></div>

                            @if(Route::has('admin.lead-sources.index'))
                                <a href="{{ route('admin.lead-sources.index') }}"
                                   class="block px-4 py-2 text-sm {{ request()->routeIs('admin.lead-sources.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-gray-100 text-gray-700' }} font-medium">
                                    Lead Sources
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Desktop Right --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                            {{ auth()->user()->name }}
                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if(Route::has('admin.profile.edit'))
                            <x-dropdown-link :href="route('admin.profile.edit')">
                                Profile
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Mobile Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = !open"
                    type="button"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none"
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
    <div x-show="open" x-cloak class="sm:hidden border-t border-gray-100 bg-white">

        <div class="pt-2 pb-3 space-y-1">
            @if(Route::has('admin.dashboard'))
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    Dashboard
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.clients.index'))
                <x-responsive-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">
                    Clients
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.leads.index'))
                <x-responsive-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
                    Leads
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.opportunities.index'))
                <x-responsive-nav-link :href="route('admin.opportunities.index')" :active="request()->routeIs('admin.opportunities.*')">
                    Opportunities
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.bookings.index'))
                <x-responsive-nav-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*')">
                    Bookings
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.jobs.index'))
                <x-responsive-nav-link :href="route('admin.jobs.index')" :active="request()->routeIs('admin.jobs.*')">
                    Jobs
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.invoices.index'))
                <x-responsive-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">
                    Invoices
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.calendar.index'))
                <x-responsive-nav-link :href="route('admin.calendar.index')" :active="request()->routeIs('admin.calendar.*')">
                    Calendar
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.inbox.index'))
                <x-responsive-nav-link :href="route('admin.inbox.index')" :active="request()->routeIs('admin.inbox.*')">
                    Inbox
                </x-responsive-nav-link>
            @endif

            <div class="border-t my-2"></div>

            <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                Settings
            </div>

            @if(Route::has('admin.business-profile.edit'))
                <x-responsive-nav-link :href="route('admin.business-profile.edit')" :active="request()->routeIs('admin.business-profile.*')">
                    Business Profile
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.settings.launch-setup.edit'))
                <x-responsive-nav-link :href="route('admin.settings.launch-setup.edit')" :active="request()->routeIs('admin.settings.launch-setup.*')">
                    Launch Setup
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.ai.edit'))
                <x-responsive-nav-link :href="route('admin.ai.edit')" :active="request()->routeIs('admin.ai.*')">
                    AI Settings
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.whatsapp.settings.edit'))
                <x-responsive-nav-link :href="route('admin.whatsapp.settings.edit')" :active="request()->routeIs('admin.whatsapp.*')">
                    WhatsApp Settings
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.audience-segmentations.index'))
                <x-responsive-nav-link :href="route('admin.audience-segmentations.index')" :active="request()->routeIs('admin.audience-segmentations.*')">
                    Audience Segmentation
                </x-responsive-nav-link>
            @endif

            @if(Route::has('admin.lead-sources.index'))
                <x-responsive-nav-link :href="route('admin.lead-sources.index')" :active="request()->routeIs('admin.lead-sources.*')">
                    Lead Sources
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">
                    {{ auth()->user()->name }}
                </div>
                <div class="font-medium text-sm text-gray-500">
                    {{ auth()->user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                @if(Route::has('admin.profile.edit'))
                    <x-responsive-nav-link :href="route('admin.profile.edit')">
                        Profile
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>