<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('admin.dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex items-center">
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.clients.index')" :active="request()->routeIs('admin.clients.*')">
                        {{ __('Clients') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.leads.index')" :active="request()->routeIs('admin.leads.*')">
                        {{ __('Leads') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.opportunities.index')" :active="request()->routeIs('admin.opportunities.*')">
                        {{ __('Opportunities') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*')">
                        {{ __('Bookings') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.jobs.index')" :active="request()->routeIs('admin.jobs.*')">
                        {{ __('Jobs') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')">
                        {{ __('Invoices') }}
                    </x-nav-link>

                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        {{ __('Users') }}
                    </x-nav-link>

                    <!-- Communication Dropdown -->
                    <div x-data="{ commOpen: false }" class="relative">
                        <button
                            type="button"
                            @click="commOpen = !commOpen"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600 focus:outline-none"
                        >
                            <span>Communication</span>
                            <svg class="ml-1 w-4 h-4 transform transition-transform duration-200"
                                 :class="{ 'rotate-180': commOpen }"
                                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="commOpen"
                            @click.outside="commOpen = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute left-0 mt-2 w-64 bg-white border rounded shadow-lg z-50 py-2"
                            role="menu" aria-orientation="vertical" tabindex="-1"
                        >
                            <!-- WhatsApp Section -->
                            <div class="px-4 py-2 text-xs text-gray-400">WhatsApp Automation</div>

                            <a href="{{ route('admin.whatsapp.templates.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.whatsapp.templates.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Templates
                            </a>

                            <a href="{{ route('admin.marketing.campaigns.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.marketing.campaigns.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Campaigns
                            </a>

                            <a href="{{ route('admin.marketing.triggers.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.marketing.triggers.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Triggers
                            </a>

                            <!-- 🔁 Updated to use MessageLogController / message_logs -->
                            <a href="{{ route('admin.whatsapp.logs.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.whatsapp.logs.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Message Logs
                            </a>

                            <a href="{{ route('admin.whatsapp.performance.index') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.whatsapp.performance.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Performance Dashboard
                            </a>

                            <a href="{{ route('admin.whatsapp.settings.edit') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.whatsapp.settings.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Settings
                            </a>

                            <div class="my-2 border-t"></div>

                            <div class="px-4 py-2 text-xs text-gray-400">Email & Others</div>
                            <a href="{{ route('admin.communication.logs') }}"
                               class="block px-4 py-2 text-sm hover:bg-gray-100 {{ request()->routeIs('admin.communication.logs') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}"
                               role="menuitem">
                                Email / SMS Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('admin.profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('admin.plans.index')">
                            {{ __('Subscription Plan') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
