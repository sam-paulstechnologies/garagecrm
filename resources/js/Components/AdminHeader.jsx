import React from 'react';
import { Link } from 'react-router-dom';

export default function Header() {
  return (
    <header className="bg-white border-b border-gray-200 shadow-sm">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between">
          {/* Logo */}
          <div className="flex items-center space-x-3">
            <img src="/logo.png" alt="Logo" className="h-8 w-8" />
            <span className="font-semibold text-lg text-gray-800">Garage CRM — Admin</span>
          </div>

          {/* Center Nav */}
          <nav className="flex-1 ml-10">
            <ul className="flex space-x-5 justify-center text-sm font-medium text-gray-700">
              <li><Link to="/admin/dashboard" className="hover:text-blue-600">Dashboard</Link></li>
              <li><Link to="/admin/clients" className="hover:text-blue-600">Clients</Link></li>
              <li><Link to="/admin/leads" className="hover:text-blue-600">Leads</Link></li>
              <li><Link to="/admin/bookings" className="hover:text-blue-600">Bookings</Link></li>
              <li><Link to="/admin/jobs" className="hover:text-blue-600">Jobs</Link></li>
              <li><Link to="/admin/invoices" className="hover:text-blue-600">Invoices</Link></li>
              <li><Link to="/admin/communications" className="hover:text-blue-600">Communication</Link></li>
              <li><Link to="/admin/users" className="hover:text-blue-600">Users</Link></li>
              <li><Link to="/admin/garages" className="hover:text-blue-600">Garages</Link></li>
              <li><Link to="/admin/company" className="hover:text-blue-600">Company</Link></li>
              <li><Link to="/admin/plans" className="hover:text-blue-600">Plans</Link></li>
              <li><Link to="/admin/templates" className="hover:text-blue-600">Templates</Link></li>
            </ul>
          </nav>

          {/* Profile */}
          <div className="flex items-center space-x-2">
            <span className="text-sm text-gray-600">Sam Abhishek Bandari</span>
            <button className="text-xs text-blue-500 hover:underline">Logout</button>
          </div>
        </div>
      </div>
    </header>
  );
}
