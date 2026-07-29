import React from 'react';
import { Link } from 'react-router-dom';

const navigation = [
    ['Dashboard', '/admin/dashboard'],
    ['Clients', '/admin/clients'],
    ['Leads', '/admin/leads'],
    ['Bookings', '/admin/bookings'],
    ['Jobs', '/admin/jobs'],
    ['Invoices', '/admin/invoices'],
    ['Communication', '/admin/communications'],
    ['Users', '/admin/users'],
    ['Garages', '/admin/garages'],
    ['Company', '/admin/company'],
    ['Plans', '/admin/plans'],
    ['Templates', '/admin/templates'],
];

export default function Header({ userName = 'Account' }) {
    return (
        <header className="border-b border-[#0D1B3D]/10 bg-white shadow-sm">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-5">
                    <img
                        src="/images/brand/sayaraforce-logo-horizontal.png"
                        alt="SayaraForce"
                        width="1153"
                        height="326"
                        className="h-9 w-auto object-contain"
                    />

                    <nav className="ml-6 min-w-0 flex-1 overflow-x-auto" aria-label="Administration">
                        <ul className="flex justify-center gap-5 whitespace-nowrap text-sm font-medium text-[#344563]">
                            {navigation.map(([label, href]) => (
                                <li key={href}>
                                    <Link to={href} className="transition hover:text-[#FF6A00]">
                                        {label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    <div className="flex items-center gap-2 whitespace-nowrap">
                        <span className="text-sm text-[#5C6B85]">{userName}</span>
                        <button className="text-xs font-semibold text-[#FF6A00] hover:underline">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </header>
    );
}
