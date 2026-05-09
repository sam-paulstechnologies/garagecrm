@php
    $lead = $lead ?? null;
    $isEdit = isset($lead) && $lead;

    $oldOr = function ($key, $default = null) use ($lead) {
        return old($key, $lead->$key ?? $default);
    };

    $selectedStatus = $oldOr('status', 'new');
    $selectedChannel = $oldOr('preferred_channel', 'whatsapp');
@endphp

<div class="space-y-6">

    {{-- Basic Lead Details --}}
    <div>
        <h3 class="text-base font-semibold text-gray-900">Basic Lead Details</h3>
        <p class="text-sm text-gray-500 mt-1">
            Capture the customer details required to start the WhatsApp journey.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Name --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Name <span class="text-red-500">*</span>
            </label>

            <input type="text"
                   name="name"
                   value="{{ $oldOr('name') }}"
                   required
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Customer name">
        </div>

        {{-- Phone --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Phone / WhatsApp Number
            </label>

            <input type="text"
                   name="phone"
                   value="{{ $oldOr('phone') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="971586934377">

            <p class="text-xs text-gray-500 mt-1">
                Use country code where possible. Phone or email is required.
            </p>
        </div>

        {{-- Email --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Email
            </label>

            <input type="email"
                   name="email"
                   value="{{ $oldOr('email') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="customer@example.com">

            <p class="text-xs text-gray-500 mt-1">
                Required only if phone number is not available.
            </p>
        </div>

        {{-- Source --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Source
            </label>

            <input type="text"
                   name="source"
                   value="{{ $oldOr('source', 'Manual') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Manual, Walk-in, Referral, Website">
        </div>

        {{-- Preferred Channel --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Preferred Channel
            </label>

            <select name="preferred_channel"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @foreach(['whatsapp', 'phone', 'email'] as $channel)
                    <option value="{{ $channel }}" @selected($selectedChannel === $channel)>
                        {{ ucfirst($channel) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Status
            </label>

            <select name="status"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @foreach(['new','attempting_contact','contact_on_hold','qualified','disqualified','converted'] as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- WhatsApp Journey --}}
    <div class="border-t pt-6">
        <h3 class="text-base font-semibold text-gray-900">WhatsApp Journey</h3>
        <p class="text-sm text-gray-500 mt-1">
            Used to send the thank-you message and push the customer into booking flow.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Tentative Service Type --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Tentative Service Needed
            </label>

            <select name="tentative_service_type"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @php
                    $selectedService = old('tentative_service_type', $lead->tentative_service_type ?? '');
                @endphp

                <option value="">— Select Service —</option>
                <option value="General Service" @selected($selectedService === 'General Service')>General Service</option>
                <option value="Oil Change" @selected($selectedService === 'Oil Change')>Oil Change</option>
                <option value="AC Service" @selected($selectedService === 'AC Service')>AC Service</option>
                <option value="Brake Check" @selected($selectedService === 'Brake Check')>Brake Check</option>
                <option value="Battery Check" @selected($selectedService === 'Battery Check')>Battery Check</option>
                <option value="Tyre Service" @selected($selectedService === 'Tyre Service')>Tyre Service</option>
                <option value="Car Inspection / Renewal Prep" @selected($selectedService === 'Car Inspection / Renewal Prep')>Car Inspection / Renewal Prep</option>
                <option value="Other" @selected($selectedService === 'Other')>Other</option>
            </select>

            <p class="text-xs text-gray-500 mt-1">
                This will be used in the WhatsApp welcome message.
            </p>
        </div>

        {{-- Send WhatsApp Now --}}
        @if(! $isEdit)
            <div class="rounded-lg border border-green-100 bg-green-50 p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox"
                           name="send_whatsapp_now"
                           value="1"
                           @checked(old('send_whatsapp_now', true))
                           class="mt-1 rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">

                    <span>
                        <span class="block text-sm font-semibold text-green-900">
                            Send WhatsApp welcome message now
                        </span>

                        <span class="block text-xs text-green-800 mt-1">
                            Sends a thank-you message and asks the customer for preferred appointment date/time.
                        </span>
                    </span>
                </label>
            </div>
        @else
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="text-sm font-semibold text-gray-900">
                    WhatsApp trigger
                </div>
                <p class="text-xs text-gray-600 mt-1">
                    Auto WhatsApp trigger is available during new manual lead creation only.
                </p>
            </div>
        @endif
    </div>

    {{-- Vehicle Capture --}}
    <div class="border-t pt-6">
        <h3 class="text-base font-semibold text-gray-900">Vehicle Details</h3>
        <p class="text-sm text-gray-500 mt-1">
            Optional for now. These details improve lead score and booking context.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Vehicle Make --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Vehicle Make
            </label>

            <input type="text"
                   name="other_make"
                   value="{{ $oldOr('other_make') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Toyota, Nissan, Jeep, BMW">
        </div>

        {{-- Vehicle Model --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Vehicle Model
            </label>

            <input type="text"
                   name="other_model"
                   value="{{ $oldOr('other_model') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Patrol, Camry, Compass">
        </div>
    </div>

    {{-- Assignment --}}
    <div class="border-t pt-6">
        <h3 class="text-base font-semibold text-gray-900">Assignment</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Assigned To --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Assigned To
            </label>

            <select name="assigned_to"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— Unassigned —</option>

                @foreach($managers ?? [] as $manager)
                    <option value="{{ $manager->id }}" @selected($oldOr('assigned_to') == $manager->id)>
                        {{ $manager->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Linked Client --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Linked Client
            </label>

            <select name="client_id"
                    class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="">— None —</option>

                @foreach($clients ?? [] as $client)
                    <option value="{{ $client->id }}" @selected($oldOr('client_id') == $client->id)>
                        {{ $client->name }} {{ $client->phone ? '— ' . $client->phone : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Flags --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        {{-- Is Hot --}}
        <div class="rounded-lg border border-red-100 bg-red-50 p-4">
            <label class="flex items-start gap-3">
                <input type="checkbox"
                       name="is_hot"
                       id="is_hot"
                       value="1"
                       @checked(old('is_hot', $lead->is_hot ?? false))
                       class="mt-1 rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">

                <span>
                    <span class="block text-sm font-semibold text-red-900">
                        Mark as Hot Lead
                    </span>

                    <span class="block text-xs text-red-800 mt-1">
                        Hot leads get higher priority and higher lead score.
                    </span>
                </span>
            </label>
        </div>

        {{-- Last Contacted --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Last Contacted At
            </label>

            <input type="date"
                   name="last_contacted_at"
                   value="{{ old('last_contacted_at', isset($lead) && $lead->last_contacted_at ? $lead->last_contacted_at->format('Y-m-d') : '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
    </div>

    {{-- Notes --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">
            Notes
        </label>

        <textarea name="notes"
                  rows="4"
                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Customer requirement, issue, preferred time, service concern...">{{ old('notes', $lead->notes ?? '') }}</textarea>
    </div>

    {{-- Lead Score Reason --}}
    <div>
        <label class="block text-sm font-medium text-gray-700">
            Lead Score Reason
        </label>

        <textarea name="lead_score_reason"
                  rows="2"
                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Why this lead is important or should be prioritized...">{{ old('lead_score_reason', $lead->lead_score_reason ?? '') }}</textarea>
    </div>

</div>