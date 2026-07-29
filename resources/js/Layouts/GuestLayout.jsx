import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="sf-auth-shell flex min-h-screen flex-col items-center bg-[#07112A] px-5 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-auto w-56" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden rounded-3xl border border-white/10 bg-white px-6 py-6 text-[#0D1B3D] shadow-2xl sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
