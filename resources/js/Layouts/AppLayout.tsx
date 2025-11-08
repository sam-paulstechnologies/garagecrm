import React from 'react';
import { useMe } from '../stores/authStore';

export default function AppLayout({ children }: { children: React.ReactNode }) {
  const { user, loading, error } = useMe();

  if (loading) return <div className="p-6">Loading...</div>;
  if (error) return <div className="p-6 text-red-600">Auth error: {error}</div>;

  return (
    <div className="min-h-screen">
      <header className="p-4 border-b flex items-center justify-between">
        <div className="font-semibold">GarageCRM</div>
        <div className="text-sm">
          {user ? (
            <>
              <span className="mr-2">{user.name}</span>
              {user.company?.name && (
                <span className="opacity-70">{user.company.name}</span>
              )}
            </>
          ) : (
            <span className="opacity-70">Not signed in</span>
          )}
        </div>
      </header>

      <main className="p-6">{children}</main>
    </div>
  );
}
