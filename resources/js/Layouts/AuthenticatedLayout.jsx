export default function AuthenticatedLayout({ header, children }) {
    return (
        <>
            {header && (
                <header className="border-b border-white/10 bg-[#050914]">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </>
    );
}
