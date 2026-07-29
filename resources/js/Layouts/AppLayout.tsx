import React from 'react';
import { useMe } from '../stores/authStore';

export default function AppLayout({ children }: { children: React.ReactNode }) {
  const { user, loading, error } = useMe();

  if (loading) return <div className="sf-theme-body min-h-screen p-6">Loading...</div>;
  if (error) return <div className="sf-theme-body min-h-screen p-6 text-red-600">Auth error: {error}</div>;

  return (
    <div className="sf-theme-body min-h-screen bg-[var(--sf-bg)] text-[var(--sf-text)]">
      <header className="flex items-center justify-between border-b border-[var(--sf-border)] bg-[var(--sf-surface)] px-4 py-3 shadow-sm">
        <img
          src="/images/brand/sayaraforce-logo-horizontal.png"
          alt="SayaraForce"
          width="1153"
          height="326"
          className="h-9 w-auto object-contain object-left sm:h-10"
        />
        <div className="text-sm">
          {user ? (
            <>
              <span className="mr-2 font-semibold">{user.name}</span>
              {user.company?.name && (
                <span className="text-[var(--sf-muted)]">{user.company.name}</span>
              )}
            </>
          ) : (
            <span className="text-[var(--sf-muted)]">Not signed in</span>
          )}
        </div>
      </header>

      <main className="p-4 sm:p-6">{children}</main>
    </div>
  );
}
