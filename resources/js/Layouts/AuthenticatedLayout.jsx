import { usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth?.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [growthOpen, setGrowthOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const currentPath = window.location.pathname;
    const userInitial = (user?.name || 'S').trim().charAt(0).toUpperCase();

    const isManagerArea = currentPath.startsWith('/manager');

    /*
    |--------------------------------------------------------------------------
    | Admin Navigation
    |--------------------------------------------------------------------------
    */
    const adminNavItems = [
        { label: 'Dashboard', href: '/admin/dashboard', match: '/admin/dashboard' },
        { label: 'Clients', href: '/admin/clients', match: '/admin/clients' },
        { label: 'Leads', href: '/admin/leads', match: '/admin/leads' },
        { label: 'Opportunities', href: '/admin/opportunities', match: '/admin/opportunities' },
        { label: 'Bookings', href: '/admin/bookings', match: '/admin/bookings' },
        { label: 'Jobs', href: '/admin/jobs', match: '/admin/jobs' },
        { label: 'Invoices', href: '/admin/invoices', match: '/admin/invoices' },
        { label: 'Calendar', href: '/admin/calendar', match: '/admin/calendar' },
        { label: 'Inbox', href: '/admin/inbox', match: '/admin/inbox' },
    ];

    const adminGrowthItems = [
        {
            label: 'Lead Sources',
            description: 'WhatsApp, website forms, and Meta lead ads',
            href: '/admin/lead-sources',
            match: '/admin/lead-sources',
        },
        {
            label: 'Audience Segmentation',
            description: 'Customer buckets and campaign groups',
            href: '/admin/settings/audience-segmentation',
            match: '/admin/settings/audience-segmentation',
        },
        {
            label: 'WhatsApp Templates',
            description: 'Approved and internal WhatsApp templates',
            href: '/admin/whatsapp/templates',
            match: '/admin/whatsapp/templates',
        },
        {
            label: 'Template Mappings',
            description: 'Map events to WhatsApp templates',
            href: '/admin/whatsapp/mappings',
            match: '/admin/whatsapp/mappings',
        },
    ];

    const adminSettingsItems = [
        {
            label: 'Launch Setup',
            description: 'Garage setup, manager handoff, working hours',
            href: '/admin/settings/launch-setup',
            match: '/admin/settings/launch-setup',
        },
        {
            label: 'Integration Settings',
            description: 'Tenant profile, Meta, Twilio, and defaults',
            href: '/admin/settings',
            match: '/admin/settings',
        },
        {
            label: 'WhatsApp Controls',
            description: 'Automation, review link, UAT reset',
            href: '/admin/whatsapp/settings',
            match: '/admin/whatsapp/settings',
        },
        {
            label: 'AI Control Center',
            description: 'AI replies, confidence, safety, and handoff',
            href: '/admin/ai',
            match: '/admin/ai',
        },
    ];

    /*
    |--------------------------------------------------------------------------
    | Manager Navigation
    |--------------------------------------------------------------------------
    | Manager should not see Admin Growth/Settings dropdowns.
    |--------------------------------------------------------------------------
    */
    const managerNavItems = [
        { label: 'Dashboard', href: '/manager/dashboard', match: '/manager/dashboard' },
        { label: 'Clients', href: '/manager/clients', match: '/manager/clients' },
        { label: 'Leads', href: '/manager/leads', match: '/manager/leads' },
        { label: 'Opportunities', href: '/manager/opportunities', match: '/manager/opportunities' },
        { label: 'Bookings', href: '/manager/bookings', match: '/manager/bookings' },
        { label: 'Jobs', href: '/manager/jobs', match: '/manager/jobs' },
        { label: 'Invoices', href: '/manager/invoices', match: '/manager/invoices' },
        { label: 'Inbox', href: '/manager/inbox', match: '/manager/inbox' },
        { label: 'Growth', href: '/manager/growth', match: '/manager/growth', safe: true },
        { label: 'Settings', href: '/manager/settings', match: '/manager/settings', safe: true },
        { label: 'Team', href: '/manager/team', match: '/manager/team' },
    ];

    const navItems = isManagerArea ? managerNavItems : adminNavItems;
    const growthItems = adminGrowthItems;
    const settingsItems = adminSettingsItems;

    const brandHref = isManagerArea ? '/manager/dashboard' : '/admin/dashboard';
    const profileHref = isManagerArea ? '/manager/dashboard' : '/admin/profile';
    const roleLabel = isManagerArea ? 'Manager' : 'Admin';

    const isActive = (match) => currentPath.startsWith(match);

    const isGrowthActive = () => {
        if (isManagerArea) {
            return currentPath.startsWith('/manager/growth');
        }

        return (
            currentPath.startsWith('/admin/lead-sources') ||
            currentPath.startsWith('/admin/settings/audience-segmentation') ||
            currentPath.startsWith('/admin/whatsapp/templates') ||
            currentPath.startsWith('/admin/whatsapp/mappings')
        );
    };

    const isSettingsActive = () => {
        if (isManagerArea) {
            return currentPath.startsWith('/manager/settings');
        }

        return (
            currentPath.startsWith('/admin/settings/launch-setup') ||
            currentPath === '/admin/settings' ||
            currentPath.startsWith('/admin/whatsapp/settings') ||
            currentPath.startsWith('/admin/ai')
        );
    };

    const closeMenus = () => {
        setGrowthOpen(false);
        setSettingsOpen(false);
        setProfileOpen(false);
    };

    const navClass = (item) =>
        `rounded-xl px-3 py-2 text-sm font-bold transition ${
            isActive(item.match)
                ? item.safe
                    ? 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/20'
                    : 'bg-white/10 text-white ring-1 ring-white/10'
                : item.safe
                    ? 'text-slate-300 hover:bg-orange-500/10 hover:text-orange-300'
                    : 'text-slate-400 hover:bg-white/5 hover:text-white'
        }`;

    const dropdownButtonClass = (active) =>
        `inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm font-bold transition ${
            active
                ? 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/20'
                : 'text-slate-400 hover:bg-white/5 hover:text-orange-300'
        }`;

    const mobileNavClass = (active, safe = false) =>
        `block rounded-2xl px-4 py-3 text-sm font-bold transition ${
            active
                ? safe
                    ? 'bg-orange-500/10 text-orange-300'
                    : 'bg-white/10 text-white'
                : safe
                    ? 'text-slate-300 hover:bg-orange-500/10 hover:text-orange-300'
                    : 'text-slate-400 hover:bg-white/5 hover:text-white'
        }`;

    return (
        <div className="min-h-screen bg-[#050914] text-slate-100">
            <nav className="sticky top-0 z-50 border-b border-white/10 bg-[#050914]/95 shadow-lg shadow-black/20 backdrop-blur">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex min-h-16 items-center justify-between gap-4">
                        {/* LEFT */}
                        <div className="flex min-w-0 items-center gap-6">
                            <a
                                href={brandHref}
                                className="flex shrink-0 items-center gap-3 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]"
                            >
                                <span className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                                    SF
                                </span>

                                <span className="hidden leading-tight sm:block">
                                    <span className="block text-sm font-extrabold tracking-tight text-white">
                                        SayaraForce
                                    </span>

                                    <span className="mt-0.5 inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-300 ring-1 ring-orange-400/20">
                                        Growth Plan
                                    </span>
                                </span>
                            </a>

                            {/* DESKTOP NAV */}
                            <div className="hidden items-center gap-1 lg:flex">
                                {navItems.map((item) => (
                                    <a key={item.label} href={item.href} className={navClass(item)}>
                                        {item.label}
                                    </a>
                                ))}

                                {/* Admin-only Growth Dropdown */}
                                {!isManagerArea && (
                                    <div className="relative">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setGrowthOpen((prev) => !prev);
                                                setSettingsOpen(false);
                                                setProfileOpen(false);
                                            }}
                                            className={dropdownButtonClass(isGrowthActive())}
                                        >
                                            <span>Growth</span>

                                            <svg
                                                className={`h-4 w-4 transition-transform ${
                                                    growthOpen ? 'rotate-180' : ''
                                                }`}
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </button>

                                        {growthOpen && (
                                            <>
                                                <button
                                                    type="button"
                                                    aria-label="Close growth menu"
                                                    className="fixed inset-0 z-40 cursor-default"
                                                    onClick={closeMenus}
                                                />

                                                <div className="absolute right-0 top-full z-50 mt-3 w-80 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40">
                                                    <div className="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                                        <p className="text-sm font-extrabold text-white">
                                                            Growth Engine
                                                        </p>
                                                        <p className="text-xs font-medium text-slate-400">
                                                            Lead capture, segments, templates, and journeys
                                                        </p>
                                                    </div>

                                                    <div className="p-2">
                                                        {growthItems.map((item) => (
                                                            <a
                                                                key={item.label}
                                                                href={item.href}
                                                                className={`block rounded-xl px-4 py-3 transition ${
                                                                    isActive(item.match)
                                                                        ? 'bg-orange-500/10 text-orange-300'
                                                                        : 'text-slate-400 hover:bg-white/5 hover:text-white'
                                                                }`}
                                                            >
                                                                <span className="block text-sm font-extrabold">
                                                                    {item.label}
                                                                </span>
                                                                <span className="mt-0.5 block text-xs font-medium text-slate-500">
                                                                    {item.description}
                                                                </span>
                                                            </a>
                                                        ))}
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                )}

                                {/* Admin-only Settings Dropdown */}
                                {!isManagerArea && (
                                    <div className="relative">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSettingsOpen((prev) => !prev);
                                                setGrowthOpen(false);
                                                setProfileOpen(false);
                                            }}
                                            className={dropdownButtonClass(isSettingsActive())}
                                        >
                                            <span>Settings</span>

                                            <svg
                                                className={`h-4 w-4 transition-transform ${
                                                    settingsOpen ? 'rotate-180' : ''
                                                }`}
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </button>

                                        {settingsOpen && (
                                            <>
                                                <button
                                                    type="button"
                                                    aria-label="Close settings menu"
                                                    className="fixed inset-0 z-40 cursor-default"
                                                    onClick={closeMenus}
                                                />

                                                <div className="absolute right-0 top-full z-50 mt-3 w-80 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40">
                                                    <div className="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                                        <p className="text-sm font-extrabold text-white">
                                                            Admin Settings
                                                        </p>
                                                        <p className="text-xs font-medium text-slate-400">
                                                            Setup, integrations, WhatsApp controls, and AI
                                                        </p>
                                                    </div>

                                                    <div className="p-2">
                                                        {settingsItems.map((item) => (
                                                            <a
                                                                key={item.label}
                                                                href={item.href}
                                                                className={`block rounded-xl px-4 py-3 transition ${
                                                                    isActive(item.match)
                                                                        ? 'bg-orange-500/10 text-orange-300'
                                                                        : 'text-slate-400 hover:bg-white/5 hover:text-white'
                                                                }`}
                                                            >
                                                                <span className="block text-sm font-extrabold">
                                                                    {item.label}
                                                                </span>
                                                                <span className="mt-0.5 block text-xs font-medium text-slate-500">
                                                                    {item.description}
                                                                </span>
                                                            </a>
                                                        ))}
                                                    </div>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* RIGHT PROFILE */}
                        <div className="hidden items-center gap-3 sm:flex">
                            <div className="relative">
                                <button
                                    type="button"
                                    onClick={() => {
                                        setProfileOpen((prev) => !prev);
                                        setGrowthOpen(false);
                                        setSettingsOpen(false);
                                    }}
                                    className="inline-flex h-10 items-center justify-center gap-1.5 rounded-2xl border border-white/10 bg-gradient-to-br from-orange-500 to-orange-700 px-3 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40 transition hover:border-orange-300/40 hover:scale-[1.03] focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]"
                                    title={user?.name || 'User'}
                                >
                                    <span>{userInitial}</span>

                                    <svg
                                        className={`h-3.5 w-3.5 text-orange-100 transition-transform ${
                                            profileOpen ? 'rotate-180' : ''
                                        }`}
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2.5"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>

                                {profileOpen && (
                                    <>
                                        <button
                                            type="button"
                                            aria-label="Close profile menu"
                                            className="fixed inset-0 z-40 cursor-default"
                                            onClick={closeMenus}
                                        />

                                        <div className="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-2xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/40">
                                            <div className="border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white">
                                                        {userInitial}
                                                    </div>

                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-extrabold text-white">
                                                            {user?.name || 'User'}
                                                        </p>

                                                        <p className="truncate text-xs font-medium text-slate-400">
                                                            {user?.email || ''}
                                                        </p>
                                                    </div>
                                                </div>

                                                <p className="mt-3">
                                                    <span className="inline-flex rounded-full bg-orange-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-orange-300 ring-1 ring-orange-400/20">
                                                        {roleLabel}
                                                    </span>
                                                </p>
                                            </div>

                                            <div className="p-2">
                                                {!isManagerArea && (
                                                    <a
                                                        href={profileHref}
                                                        className="block rounded-xl px-4 py-2 text-sm font-bold text-slate-400 transition hover:bg-white/5 hover:text-white"
                                                    >
                                                        Profile
                                                    </a>
                                                )}

                                                <form method="POST" action="/logout">
                                                    <input type="hidden" name="_token" value={csrfToken} />

                                                    <button
                                                        type="submit"
                                                        className="block w-full rounded-xl px-4 py-2 text-left text-sm font-bold text-red-300 transition hover:bg-red-500/10 hover:text-red-200"
                                                    >
                                                        Log Out
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* MOBILE MENU BUTTON */}
                        <div className="flex items-center lg:hidden">
                            <button
                                type="button"
                                onClick={() =>
                                    setShowingNavigationDropdown((previous) => !previous)
                                }
                                className="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-2 text-slate-300 shadow-sm transition hover:border-orange-400/30 hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-[#050914]"
                            >
                                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    {!showingNavigationDropdown ? (
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                    ) : (
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    )}
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* MOBILE NAV */}
                {showingNavigationDropdown && (
                    <div className="border-t border-white/10 bg-[#050914] lg:hidden">
                        <div className="space-y-1 px-4 py-4">
                            {navItems.map((item) => (
                                <a
                                    key={item.label}
                                    href={item.href}
                                    className={mobileNavClass(isActive(item.match), item.safe)}
                                >
                                    {item.label}
                                </a>
                            ))}

                            {!isManagerArea && (
                                <>
                                    <div className="my-3 border-t border-white/10"></div>

                                    <div className="px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                                        Growth
                                    </div>

                                    {growthItems.map((item) => (
                                        <a
                                            key={item.label}
                                            href={item.href}
                                            className={mobileNavClass(isActive(item.match))}
                                        >
                                            {item.label}
                                        </a>
                                    ))}

                                    <div className="my-3 border-t border-white/10"></div>

                                    <div className="px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                                        Settings
                                    </div>

                                    {settingsItems.map((item) => (
                                        <a
                                            key={item.label}
                                            href={item.href}
                                            className={mobileNavClass(isActive(item.match))}
                                        >
                                            {item.label}
                                        </a>
                                    ))}
                                </>
                            )}
                        </div>

                        <div className="border-t border-white/10 px-4 py-4">
                            <div className="rounded-2xl border border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 p-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white">
                                        {userInitial}
                                    </div>

                                    <div className="min-w-0">
                                        <div className="truncate font-extrabold text-white">
                                            {user?.name || 'User'}
                                        </div>

                                        <div className="mt-1 truncate text-sm font-medium text-slate-400">
                                            {user?.email || ''}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-3 space-y-1">
                                {!isManagerArea && (
                                    <a
                                        href={profileHref}
                                        className="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-400 transition hover:bg-white/5 hover:text-white"
                                    >
                                        Profile
                                    </a>
                                )}

                                <form method="POST" action="/logout">
                                    <input type="hidden" name="_token" value={csrfToken} />

                                    <button
                                        type="submit"
                                        className="block w-full rounded-2xl px-4 py-3 text-left text-sm font-bold text-red-300 transition hover:bg-red-500/10 hover:text-red-200"
                                    >
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                )}
            </nav>

            {header && (
                <header className="border-b border-white/10 bg-[#050914]">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}