{{-- resources/views/admin/clients/create-partials/_form.blade.php --}}

<style>
    .sf-create-card {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-create-card-border {
        border-color: rgba(30, 41, 59, 1);
    }

    .sf-create-title {
        color: #ffffff;
    }

    .sf-create-subtitle {
        color: #94a3b8;
    }

    .sf-create-label {
        color: #cbd5e1;
    }

    .sf-create-help {
        color: #64748b;
    }

    .sf-create-input {
        border-color: #334155;
        background: rgba(2, 6, 23, 0.70);
        color: #e2e8f0;
    }

    .sf-create-input::placeholder {
        color: #64748b;
    }

    html[data-theme="light"] .sf-create-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-create-card-border {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-create-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-create-subtitle,
    html[data-theme="light"] .sf-create-label,
    html[data-theme="light"] .sf-create-help {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-create-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-create-input::placeholder {
        color: #94a3b8 !important;
    }
</style>

<div class="lg:col-span-2">
    <form
        method="POST"
        action="{{ route('admin.clients.store') }}"
        class="sf-create-card overflow-hidden rounded-2xl border shadow-sm"
    >
        @csrf

        <div class="sf-create-card-border border-b p-5">
            <h2 class="sf-create-title text-base font-extrabold tracking-tight">
                Client Information
            </h2>

            <p class="sf-create-subtitle mt-1 text-xs font-medium">
                Enter customer details. Phone and email help with follow-ups, reminders, and communication history.
            </p>
        </div>

        <div class="space-y-5 p-5">

            {{-- Name --}}
            <div>
                <label for="name" class="sf-create-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Name <span class="text-red-400">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    class="sf-create-input h-11 w-full rounded-xl border px-3 text-sm font-semibold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    placeholder="Customer name"
                    required
                >

                @error('name')
                    <div class="mt-2 text-xs font-bold text-red-300">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="sf-create-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Email <span class="text-red-400">*</span>
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    class="sf-create-input h-11 w-full rounded-xl border px-3 text-sm font-semibold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    placeholder="customer@example.com"
                    required
                >

                @error('email')
                    <div class="mt-2 text-xs font-bold text-red-300">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="sf-create-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Phone <span class="text-red-400">*</span>
                </label>

                <input
                    type="text"
                    name="phone"
                    id="phone"
                    value="{{ old('phone') }}"
                    class="sf-create-input h-11 w-full rounded-xl border px-3 text-sm font-semibold outline-none transition focus:border-orange-400 focus:ring-2 focus:ring-orange-500/20"
                    placeholder="971586934377"
                    required
                >

                <p class="sf-create-help mt-2 text-xs font-medium">
                    Use country code where possible.
                </p>

                @error('phone')
                    <div class="mt-2 text-xs font-bold text-red-300">
                        {{ $message }}
                    </div>
                @enderror
            </div>

        </div>

        <div class="sf-create-card-border border-t p-5">
            <div class="flex flex-wrap justify-end gap-2">
                @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                    <a
                        href="{{ route('admin.clients.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                    >
                        Cancel
                    </a>
                @endif

                <button
                    type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Submit
                </button>
            </div>
        </div>
    </form>
</div>