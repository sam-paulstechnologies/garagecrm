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

    const submit = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Login — SayaraForce" />

            <main className="sf-auth-shell grid min-h-screen bg-[#07112A] text-white lg:grid-cols-[1.08fr_0.92fr]">
                <section className="relative hidden overflow-hidden border-r border-white/10 bg-[#0D1B3D] lg:flex lg:flex-col">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,106,0,0.2),transparent_33%),radial-gradient(circle_at_bottom_right,rgba(41,69,121,0.46),transparent_38%)]" />

                    <div className="relative flex h-full flex-col justify-between p-10 xl:p-12">
                        <Link href="/" className="inline-flex w-fit items-center">
                            <img
                                src="/images/brand/sayaraforce-logo-horizontal.png"
                                alt="SayaraForce"
                                width="1153"
                                height="326"
                                className="h-12 w-auto object-contain"
                            />
                        </Link>

                        <div className="max-w-2xl py-12">
                            <p className="mb-5 inline-flex rounded-full border border-[#FF6A00]/35 bg-[#FF6A00]/10 px-4 py-2 text-sm font-semibold text-[#FF9A52]">
                                Growth Engine for UAE Garages
                            </p>

                            <h1 className="max-w-2xl text-4xl leading-[1.08] tracking-tight text-white xl:text-5xl">
                                Manage leads, bookings, jobs and WhatsApp follow-ups in one place.
                            </h1>

                            <p className="mt-6 max-w-xl text-base leading-7 text-[#D2DAEA] xl:text-lg xl:leading-8">
                                A clear operational workspace for capturing enquiries, coordinating follow-up and keeping garage teams aligned.
                            </p>
                        </div>

                        <div className="grid grid-cols-3 gap-3 xl:gap-4">
                            {[
                                ['Lead capture', 'Enquiries organised'],
                                ['Follow-up', 'WhatsApp-enabled'],
                                ['Workshop flow', 'Bookings to jobs'],
                            ].map(([label, description]) => (
                                <div key={label} className="rounded-2xl border border-white/10 bg-white/[0.055] p-4 xl:p-5">
                                    <p className="text-sm font-semibold text-white xl:text-base">{label}</p>
                                    <p className="mt-1 text-xs leading-5 text-[#AEBBD0]">{description}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                <section className="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-8 sm:px-8">
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,106,0,0.12),transparent_32%)] lg:hidden" />

                    <div className="relative w-full max-w-md">
                        <div className="mb-8 flex justify-center lg:hidden">
                            <Link href="/">
                                <img
                                    src="/images/brand/sayaraforce-logo-horizontal.png"
                                    alt="SayaraForce"
                                    width="1153"
                                    height="326"
                                    className="h-11 w-auto max-w-[240px] object-contain"
                                />
                            </Link>
                        </div>

                        <div className="rounded-[1.75rem] border border-white/10 bg-[#0D1B3D]/90 p-6 shadow-2xl shadow-black/25 backdrop-blur sm:p-8">
                            <div className="mb-7">
                                <h2 className="text-3xl leading-tight tracking-tight text-white">Welcome back</h2>
                                <p className="mt-2 text-sm leading-6 text-[#AEBBD0]">Sign in to your SayaraForce workspace.</p>
                            </div>

                            {status && (
                                <div className="mb-5 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                                    {status}
                                </div>
                            )}

                            {(errors.email || errors.password) && (
                                <div className="mb-5 rounded-2xl border border-red-400/25 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">
                                    {errors.email || errors.password}
                                </div>
                            )}

                            <form onSubmit={submit} className="space-y-5">
                                <div>
                                    <label htmlFor="email" className="mb-2 block text-sm font-semibold text-[#D2DAEA]">
                                        Email address
                                    </label>
                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]"
                                        autoComplete="username"
                                        isFocused
                                        placeholder="you@company.com"
                                        onChange={(event) => setData('email', event.target.value)}
                                    />
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <div className="mb-2 flex items-center justify-between gap-4">
                                        <label htmlFor="password" className="block text-sm font-semibold text-[#D2DAEA]">
                                            Password
                                        </label>

                                        {canResetPassword && (
                                            <Link href={route('password.request')} className="rounded text-sm font-semibold text-[#FF8A38] transition hover:text-[#FFB079]">
                                                Forgot password?
                                            </Link>
                                        )}
                                    </div>

                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]"
                                        autoComplete="current-password"
                                        placeholder="Enter your password"
                                        onChange={(event) => setData('password', event.target.value)}
                                    />
                                    <InputError message={errors.password} className="mt-2" />
                                </div>

                                <label className="flex w-fit items-center gap-2 text-sm text-[#AEBBD0]">
                                    <Checkbox
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(event) => setData('remember', event.target.checked)}
                                    />
                                    <span>Remember me</span>
                                </label>

                                <PrimaryButton className="flex w-full justify-center py-3.5 text-base" disabled={processing}>
                                    Log in
                                </PrimaryButton>
                            </form>

                            <div className="mt-6 border-t border-white/10 pt-6 text-center text-sm leading-6 text-[#AEBBD0]">
                                New to SayaraForce?{' '}
                                <Link href="/#audit" className="font-semibold text-[#FF8A38] transition hover:text-[#FFB079]">
                                    Book a free audit
                                </Link>
                            </div>
                        </div>

                        <p className="mt-6 text-center text-xs text-[#75849D]">
                            © {new Date().getFullYear()} SayaraForce. Built for UAE garages.
                        </p>
                    </div>
                </section>
            </main>
        </>
    );
}
