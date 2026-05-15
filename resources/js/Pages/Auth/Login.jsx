import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Login — SayaraForce" />

            <div className="min-h-screen bg-slate-950 text-white">
                <div className="grid min-h-screen lg:grid-cols-2">
                    {/* Left Branding Panel */}
                    <div className="relative hidden overflow-hidden border-r border-white/10 bg-slate-900 lg:block">
                        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.28),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.18),transparent_35%)]" />

                        <div className="relative flex h-full flex-col justify-between p-12">
                            <Link href="/" className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-lg font-black text-white shadow-lg shadow-orange-500/20">
                                    SF
                                </div>

                                <div>
                                    <div className="text-xl font-black tracking-tight text-white">
                                        SayaraForce
                                    </div>
                                    <div className="text-sm text-slate-400">
                                        Garage Growth CRM
                                    </div>
                                </div>
                            </Link>

                            <div>
                                <div className="mb-5 inline-flex rounded-full border border-orange-400/30 bg-orange-500/10 px-4 py-2 text-sm font-semibold text-orange-300">
                                    Lead recovery system for garages
                                </div>

                                <h1 className="max-w-xl text-5xl font-black leading-tight tracking-tight text-white">
                                    Manage leads, bookings, jobs and WhatsApp follow-ups in one place.
                                </h1>

                                <p className="mt-6 max-w-lg text-lg leading-8 text-slate-300">
                                    Built for garages that want to stop losing enquiries and convert more customers into confirmed jobs.
                                </p>
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <div className="text-2xl font-black text-white">
                                        Leads
                                    </div>
                                    <div className="mt-1 text-sm text-slate-400">
                                        Capture
                                    </div>
                                </div>

                                <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <div className="text-2xl font-black text-white">
                                        WA
                                    </div>
                                    <div className="mt-1 text-sm text-slate-400">
                                        Follow-up
                                    </div>
                                </div>

                                <div className="rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <div className="text-2xl font-black text-white">
                                        Jobs
                                    </div>
                                    <div className="mt-1 text-sm text-slate-400">
                                        Track
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Login Panel */}
                    <div className="flex min-h-screen items-center justify-center px-6 py-10">
                        <div className="w-full max-w-md">
                            {/* Mobile Logo */}
                            <div className="mb-8 text-center lg:hidden">
                                <Link href="/" className="inline-flex items-center justify-center gap-3">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-lg font-black text-white shadow-lg shadow-orange-500/20">
                                        SF
                                    </div>

                                    <div className="text-left">
                                        <div className="text-xl font-black tracking-tight text-white">
                                            SayaraForce
                                        </div>
                                        <div className="text-sm text-slate-400">
                                            Garage Growth CRM
                                        </div>
                                    </div>
                                </Link>
                            </div>

                            <div className="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur">
                                <div className="mb-8">
                                    <h2 className="text-3xl font-black tracking-tight text-white">
                                        Welcome back
                                    </h2>
                                    <p className="mt-2 text-sm text-slate-400">
                                        Login to your garage workspace.
                                    </p>
                                </div>

                                {status && (
                                    <div className="mb-5 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                                        {status}
                                    </div>
                                )}

                                {(errors.email || errors.password) && (
                                    <div className="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                                        {errors.email || errors.password}
                                    </div>
                                )}

                                <form onSubmit={submit} className="space-y-5">
                                    <div>
                                        <label
                                            htmlFor="email"
                                            className="mb-2 block text-sm font-semibold text-slate-300"
                                        >
                                            Email Address
                                        </label>

                                        <TextInput
                                            id="email"
                                            type="email"
                                            name="email"
                                            value={data.email}
                                            className="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2"
                                            autoComplete="username"
                                            isFocused={true}
                                            placeholder="admin@garage.com"
                                            onChange={(e) => setData('email', e.target.value)}
                                        />

                                        <InputError message={errors.email} className="mt-2" />
                                    </div>

                                    <div>
                                        <div className="mb-2 flex items-center justify-between">
                                            <label
                                                htmlFor="password"
                                                className="block text-sm font-semibold text-slate-300"
                                            >
                                                Password
                                            </label>

                                            {canResetPassword && (
                                                <Link
                                                    href={route('password.request')}
                                                    className="text-sm font-semibold text-orange-400 hover:text-orange-300"
                                                >
                                                    Forgot?
                                                </Link>
                                            )}
                                        </div>

                                        <TextInput
                                            id="password"
                                            type="password"
                                            name="password"
                                            value={data.password}
                                            className="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2"
                                            autoComplete="current-password"
                                            placeholder="Enter your password"
                                            onChange={(e) => setData('password', e.target.value)}
                                        />

                                        <InputError message={errors.password} className="mt-2" />
                                    </div>

                                    <div className="flex items-center justify-between">
                                        <label className="flex items-center gap-2 text-sm text-slate-400">
                                            <Checkbox
                                                name="remember"
                                                checked={data.remember}
                                                onChange={(e) =>
                                                    setData('remember', e.target.checked)
                                                }
                                            />

                                            <span>Remember me</span>
                                        </label>
                                    </div>

                                    <PrimaryButton
                                        className="flex w-full justify-center rounded-2xl bg-orange-500 px-6 py-4 text-base font-black text-white shadow-xl shadow-orange-500/20 transition hover:bg-orange-600 disabled:opacity-60"
                                        disabled={processing}
                                    >
                                        Login
                                    </PrimaryButton>
                                </form>

                                <div className="mt-6 border-t border-white/10 pt-6 text-center text-sm text-slate-400">
                                    New to SayaraForce?{' '}
                                    <Link
                                        href="/#audit"
                                        className="font-bold text-orange-400 hover:text-orange-300"
                                    >
                                        Request a free lead recovery audit
                                    </Link>
                                </div>
                            </div>

                            <div className="mt-6 text-center text-xs text-slate-600">
                                © {new Date().getFullYear()} SayaraForce. Built for UAE garages.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}