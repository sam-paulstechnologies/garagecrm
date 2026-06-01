{{-- resources/views/admin/clients/edit-partials/_form.blade.php --}}

@php
    $showRoute = \Illuminate\Support\Facades\Route::has('admin.clients.show')
        ? route('admin.clients.show', $client->id)
        : route('admin.clients.index');

    $dobValue = old('dob');

    if (!$dobValue && !empty($client->dob)) {
        try {
            $dobValue = \Illuminate\Support\Carbon::parse($client->dob)->format('Y-m-d');
        } catch (\Throwable $e) {
            $dobValue = null;
        }
    }
@endphp

<form action="{{ route('admin.clients.update', $client->id) }}" method="POST" class="sf-edit-panel rounded-2xl border shadow-sm">
    @csrf
    @method('PUT')

    <div class="border-b border-white/10 p-5">
        <h2 class="sf-edit-title text-lg font-extrabold tracking-tight">
            Client Information
        </h2>

        <p class="sf-edit-muted mt-1 text-sm font-medium">
            Keep this profile clean so bookings, invoices, reminders, and service history stay accurate.
        </p>
    </div>

    <div class="space-y-8 p-5">

        {{-- Basic Details --}}
        <section id="client-contact" class="sf-edit-target space-y-5">
            <div>
                <h3 class="sf-edit-section-title text-base font-extrabold">
                    Basic Details
                </h3>

                <p class="sf-edit-muted mt-1 text-sm font-medium">
                    Primary customer contact information.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Name <span class="text-red-400">*</span>
                    </label>

                    <input
                        name="name"
                        type="text"
                        value="{{ old('name', $client->name) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        required
                    >

                    @error('name')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Email
                    </label>

                    <input
                        name="email"
                        type="email"
                        value="{{ old('email', $client->email) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('email')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Phone
                    </label>

                    <input
                        name="phone"
                        type="text"
                        value="{{ old('phone', $client->phone) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        placeholder="971586934377"
                    >

                    @error('phone')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        WhatsApp
                    </label>

                    <input
                        name="whatsapp"
                        type="text"
                        value="{{ old('whatsapp', $client->whatsapp) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        placeholder="971586934377"
                    >

                    @error('whatsapp')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="sf-edit-divider border-t"></div>

        {{-- Identity --}}
        <section class="space-y-5">
            <div>
                <h3 class="sf-edit-section-title text-base font-extrabold">
                    Identity & Preference
                </h3>

                <p class="sf-edit-muted mt-1 text-sm font-medium">
                    Optional profile fields for better segmentation and personalization.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Gender
                    </label>

                    <select name="gender" class="sf-edit-select h-11 w-full rounded-xl border px-3 text-sm font-bold">
                        <option value="">—</option>

                        @foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('gender', $client->gender) == $val)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('gender')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Date of Birth
                    </label>

                    <input
                        name="dob"
                        type="date"
                        value="{{ $dobValue }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('dob')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                {{-- VIP --}}
                <div class="sf-edit-vip-card rounded-2xl border p-4">
                    <label for="is_vip" class="flex cursor-pointer items-start gap-3">
                        <input type="hidden" name="is_vip" value="0">

                        <input
                            id="is_vip"
                            name="is_vip"
                            type="checkbox"
                            value="1"
                            @checked((bool) old('is_vip', $client->is_vip))
                            class="sf-edit-checkbox mt-1 h-5 w-5 shrink-0 cursor-pointer rounded"
                        >

                        <span>
                            <span class="sf-edit-vip-title block text-sm font-extrabold">
                                VIP Client
                            </span>

                            <span class="sf-edit-vip-text mt-1 block text-xs font-medium leading-5">
                                Mark this client for priority service and reporting.
                            </span>
                        </span>
                    </label>

                    @error('is_vip')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="sf-edit-divider border-t"></div>

        {{-- Address --}}
        <section id="client-address" class="sf-edit-target space-y-5">
            <div>
                <h3 class="sf-edit-section-title text-base font-extrabold">
                    Address & Location
                </h3>

                <p class="sf-edit-muted mt-1 text-sm font-medium">
                    Useful for pickup, drop-off, service area, and customer segmentation.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Address
                    </label>

                    <input
                        name="address"
                        type="text"
                        value="{{ old('address', $client->address) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        placeholder="Street, building, area"
                    >

                    @error('address')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Preferred Channel
                    </label>

                    <select name="preferred_channel" class="sf-edit-select h-11 w-full rounded-xl border px-3 text-sm font-bold">
                        <option value="">—</option>

                        @foreach(['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('preferred_channel', $client->preferred_channel) == $val)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('preferred_channel')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        City
                    </label>

                    <input
                        name="city"
                        type="text"
                        value="{{ old('city', $client->city) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('city')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        State
                    </label>

                    <input
                        name="state"
                        type="text"
                        value="{{ old('state', $client->state) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('state')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Postal Code
                    </label>

                    <input
                        name="postal_code"
                        type="text"
                        value="{{ old('postal_code', $client->postal_code) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('postal_code')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Country
                    </label>

                    <input
                        name="country"
                        type="text"
                        value="{{ old('country', $client->country) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                    >

                    @error('country')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="sf-edit-divider border-t"></div>

        {{-- CRM --}}
        <section class="space-y-5">
            <div>
                <h3 class="sf-edit-section-title text-base font-extrabold">
                    CRM Fields
                </h3>

                <p class="sf-edit-muted mt-1 text-sm font-medium">
                    Source and status help reporting, filtering, and segmentation.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Source
                    </label>

                    <input
                        name="source"
                        type="text"
                        value="{{ old('source', $client->source) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        placeholder="website, whatsapp, walk-in, referral"
                    >

                    @error('source')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                        Status
                    </label>

                    <input
                        name="status"
                        type="text"
                        value="{{ old('status', $client->status) }}"
                        class="sf-edit-input h-11 w-full rounded-xl border px-3 text-sm font-bold"
                        placeholder="active"
                    >

                    @error('status')
                        <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </section>

        <div class="sf-edit-divider border-t"></div>

        {{-- Notes --}}
        <section class="space-y-5">
            <div>
                <h3 class="sf-edit-section-title text-base font-extrabold">
                    Internal Notes
                </h3>

                <p class="sf-edit-muted mt-1 text-sm font-medium">
                    Private notes visible to the garage team.
                </p>
            </div>

            <div>
                <label class="sf-edit-label mb-2 block text-xs font-black uppercase tracking-wide">
                    Notes
                </label>

                <textarea
                    name="notes"
                    rows="4"
                    class="sf-edit-textarea w-full rounded-xl border px-3 py-3 text-sm font-bold"
                    placeholder="Add internal notes about this client..."
                >{{ old('notes', $client->notes) }}</textarea>

                @error('notes')
                    <div class="mt-2 text-xs font-bold text-red-400">{{ $message }}</div>
                @enderror
            </div>
        </section>
    </div>

    <div class="border-t border-white/10 p-5">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="submit"
                class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
            >
                Update Client
            </button>

            <a
                href="{{ $showRoute }}"
                class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
            >
                Cancel
            </a>
        </div>
    </div>
</form>