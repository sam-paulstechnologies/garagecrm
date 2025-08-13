// resources/js/components/AdminLayout.jsx
import React from 'react';
import AdminHeader from './AdminHeader';

const AdminLayout = ({ children }) => {
  return (
    <div className="min-h-screen bg-gray-100">
      <AdminHeader />
      <main className="p-6">{children}</main>
    </div>
  );
};

export default AdminLayout;
