import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth?.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const navItems = [
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

    const settingsItems = [
        { label: 'Business Profile', href: '/admin/business-profile' },
        { label: 'Launch Setup', href: '/admin/settings/launch-setup' },
        { label: 'AI Settings', href: '/admin/ai' },
        { label: 'WhatsApp Settings', href: '/admin/whatsapp/settings' },
        { label: 'Lead Sources', href: '/admin/lead-sources', divider: true },
    ];

    const isActive = (match) => window.location.pathname.startsWith(match);

    const isSettingsActive = () =>
        window.location.pathname.startsWith('/admin/settings') ||
        window.location.pathname.startsWith('/admin/ai') ||
        window.location.pathname.startsWith('/admin/whatsapp') ||
        window.location.pathname.startsWith('/admin/lead-sources') ||
        window.location.pathname.startsWith('/admin/business-profile');

    const navClass = (item) =>
        `inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ${
            isActive(item.match)
                ? 'border-indigo-400 text-gray-900'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
        }`;

    const mobileNavClass = (item) =>
        `block w-full border-l-4 py-2 pe-4 ps-3 text-start text-base font-medium transition duration-150 ease-in-out ${
            isActive(item.match)
                ? 'border-indigo-400 bg-indigo-50 text-indigo-700'
                : 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800'
        }`;

    const settingsButtonClass =
        `inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ${
            isSettingsActive()
                ? 'border-indigo-400 text-gray-900'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
        }`;

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">

                        {/* LEFT */}
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <a href="/admin/dashboard">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </a>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {navItems.map((item) => (
                                    <a
                                        key={item.label}
                                        href={item.href}
                                        className={navClass(item)}
                                    >
                                        {item.label}
                                    </a>
                                ))}

                                {/* SETTINGS DROPDOWN */}
                                <div className="relative flex items-center">
                                    <button
                                        type="button"
                                        onClick={() => setSettingsOpen((prev) => !prev)}
                                        className={settingsButtonClass}
                                    >
                                        <span>Settings</span>

                                        <svg
                                            className={`ml-1 h-4 w-4 transform transition-transform ${
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
                                                onClick={() => setSettingsOpen(false)}
                                            />

                                            <div className="absolute left-0 top-full z-50 mt-2 w-64 rounded border bg-white py-2 shadow-lg">
                                                {settingsItems.map((item) => (
                                                    <div key={item.label}>
                                                        {item.divider && (
                                                            <div className="my-2 border-t" />
                                                        )}

                                                        <a
                                                            href={item.href}
                                                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        >
                                                            {item.label}
                                                        </a>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* RIGHT */}
                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {user?.name || 'User'}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <a
                                            href="/admin/profile"
                                            className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                        >
                                            Profile
                                        </a>

                                        <form method="POST" action="/logout">
                                            <input
                                                type="hidden"
                                                name="_token"
                                                value={csrfToken}
                                            />

                                            <button
                                                type="submit"
                                                className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                            >
                                                Log Out
                                            </button>
                                        </form>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        {/* MOBILE MENU BUTTON */}
                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown((previousState) => !previousState)
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />

                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {/* MOBILE NAV */}
                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {navItems.map((item) => (
                            <a
                                key={item.label}
                                href={item.href}
                                className={mobileNavClass(item)}
                            >
                                {item.label}
                            </a>
                        ))}

                        <div className="border-t my-2" />

                        <div className="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Settings
                        </div>

                        {settingsItems.map((item) => (
                            <a
                                key={item.label}
                                href={item.href}
                                className="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                            >
                                {item.label}
                            </a>
                        ))}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user?.name || 'User'}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user?.email || ''}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <a
                                href="/admin/profile"
                                className="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                            >
                                Profile
                            </a>

                            <form method="POST" action="/logout">
                                <input
                                    type="hidden"
                                    name="_token"
                                    value={csrfToken}
                                />

                                <button
                                    type="submit"
                                    className="block w-full border-l-4 border-transparent py-2 pe-4 ps-3 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
                                >
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}