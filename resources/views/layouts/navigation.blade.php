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

                {{-- Primary Nav --}}
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">

                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        Dashboard
                    </x-nav-link>

                    <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">
                        Clients
                    </x-nav-link>

                    <x-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
                        Leads
                    </x-nav-link>

                    <x-nav-link :href="route('admin.opportunities.index')" :active="request()->routeIs('admin.opportunities.*')">
                        Opportunities
                    </x-nav-link>

                    <x-nav-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*')">
                        Bookings
                    </x-nav-link>

                    <x-nav-link :href="route('admin.jobs.index')" :active="request()->routeIs('admin.jobs.*')">
                        Jobs
                    </x-nav-link>

                    <x-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">
                        Invoices
                    </x-nav-link>

                    <x-nav-link :href="route('admin.calendar.index')" :active="request()->routeIs('admin.calendar.*')">
                        Calendar
                    </x-nav-link>

                    {{-- SETTINGS DROPDOWN --}}
                    <div x-data="{ settingsOpen: false }" class="relative">
                        <button
                            @click="settingsOpen = !settingsOpen"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600"
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
                            {{-- Business --}}
                            @if(Route::has('admin.business-profile.edit'))
                                <a href="{{ route('admin.business-profile.edit') }}"
                                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                                    Business Profile
                                </a>
                            @endif

                            {{-- Launch Setup --}}
                            @if(Route::has('admin.settings.launch-setup.edit'))
                                <a href="{{ route('admin.settings.launch-setup.edit') }}"
                                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                                    Launch Setup
                                </a>
                            @endif

                            {{-- AI --}}
                            @if(Route::has('admin.ai.edit'))
                                <a href="{{ route('admin.ai.edit') }}"
                                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                                    AI Settings
                                </a>
                            @endif

                            {{-- WhatsApp --}}
                            @if(Route::has('admin.whatsapp.settings.edit'))
                                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                                   class="block px-4 py-2 text-sm hover:bg-gray-100">
                                    WhatsApp Settings
                                </a>
                            @endif

                            <div class="border-t my-2"></div>

                            {{-- Lead Sources --}}
                            @if(Route::has('admin.lead-sources.index'))
                                <a href="{{ route('admin.lead-sources.index') }}"
                                   class="block px-4 py-2 text-sm hover:bg-gray-100 font-medium">
                                    Lead Sources
                                </a>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- RIGHT --}}
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
                        <x-dropdown-link :href="route('admin.profile.edit')">
                            Profile
                        </x-dropdown-link>

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

        </div>
    </div>
</nav>