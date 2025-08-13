import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const AdminSidebar = () => {
  console.log("AdminSidebar loaded"); // ✅ Debug log

  const location = useLocation();
  const isActive = (path) => location.pathname.startsWith(path);

  const links = [
    { name: 'Dashboard', path: '/admin/dashboard' },
    { name: 'Clients', path: '/admin/clients' },
    { name: 'Leads', path: '/admin/leads' },
    { name: 'Bookings', path: '/admin/bookings' },
    { name: 'Jobs', path: '/admin/jobs' },
    { name: 'Invoices', path: '/admin/invoices' },
    { name: 'Communications', path: '/admin/communications' },
    { name: 'Users', path: '/admin/users' },
    { name: 'Garages', path: '/admin/garages' },
    { name: 'Company', path: '/admin/company' },
    { name: 'Plans', path: '/admin/plans' },
    { name: 'Journey Templates', path: '/admin/journey_templates' },
    { name: 'Journeys', path: '/admin/journeys' },
    { name: 'Templates', path: '/admin/templates' }
  ];

  return (
    <div className="w-64 bg-white border-r h-screen p-4">
      <h2 className="text-xl font-bold mb-4">Admin Menu</h2>
      <ul className="space-y-2">
        {links.map((link) => (
          <li key={link.name}>
            <Link
              to={link.path}
              className={`block px-4 py-2 rounded hover:bg-gray-100 ${
                isActive(link.path) ? 'bg-gray-200 font-semibold' : ''
              }`}
            >
              {link.name}
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default AdminSidebar;
