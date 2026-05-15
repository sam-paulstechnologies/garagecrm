@php
    $lead = $lead ?? null;
    $isEdit = isset($lead) && $lead;

    $oldOr = function ($key, $default = null) use ($lead) {
        return old($key, data_get($lead, $key, $default));
    };

    $selectedStatus = $oldOr('status', 'new');
    $selectedChannel = $oldOr('preferred_channel', 'whatsapp');
    $selectedService = old('tentative_service_type', data_get($lead, 'tentative_service_type', ''));

    $assignableUsers = collect(
        $users
        ?? $managers
        ?? $assignableUsers
        ?? $employees
        ?? []
    );

    $assignedTo = $oldOr('assigned_to');
@endphp

<div class="space-y-8">

    {{-- Basic Lead Details --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Basic Lead Details
            </h3>
            <p class="sf-section-subtitle">
                Capture the customer details required to start the lead and WhatsApp journey.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Name --}}
            <div>
                <label class="sf-label">
                    Name <span class="text-red-300">*</span>
                </label>

                <input type="text"
                       name="name"
                       value="{{ $oldOr('name') }}"
                       required
                       class="sf-input"
                       placeholder="Customer name">

                @error('name')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Phone --}}
            <div>
                <label class="sf-label">
                    Phone / WhatsApp Number
                </label>

                <input type="text"
                       name="phone"
                       value="{{ $oldOr('phone') }}"
                       class="sf-input"
                       placeholder="971586934377">

                <p class="sf-help">
                    Use country code where possible. Phone or email is required.
                </p>

                @error('phone')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="sf-label">
                    Email
                </label>

                <input type="email"
                       name="email"
                       value="{{ $oldOr('email') }}"
                       class="sf-input"
                       placeholder="customer@example.com">

                <p class="sf-help">
                    Required only if phone number is not available.
                </p>

                @error('email')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Source --}}
            <div>
                <label class="sf-label">
                    Source
                </label>

                <input type="text"
                       name="source"
                       value="{{ $oldOr('source', 'Manual') }}"
                       class="sf-input"
                       placeholder="Manual, Walk-in, Referral, Website">

                @error('source')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Preferred Channel --}}
            <div>
                <label class="sf-label">
                    Preferred Channel
                </label>

                <select name="preferred_channel" class="sf-select">
                    @foreach(['whatsapp', 'phone', 'email'] as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>
                            {{ ucfirst($channel) }}
                        </option>
                    @endforeach
                </select>

                @error('preferred_channel')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Status --}}
            <div>
                <label class="sf-label">
                    Status
                </label>

                <select name="status" class="sf-select">
                    @foreach(['new','attempting_contact','contact_on_hold','qualified','disqualified','converted'] as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- Lead Classification --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Lead Classification
            </h3>
            <p class="sf-section-subtitle">
                Used for lead buckets, priority queues, and reporting.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Service Category --}}
            <div>
                <label class="sf-label">
                    Service Category
                </label>

                <select name="service_category" class="sf-select">
                    @php($selectedCategory = $oldOr('service_category'))

                    <option value="">— Select Category —</option>

                    @foreach(['service', 'quote', 'repair', 'complaint', 'emergency', 'enquiry'] as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>
                            {{ ucfirst($category) }}
                        </option>
                    @endforeach
                </select>

                @error('service_category')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Service Type --}}
            <div>
                <label class="sf-label">
                    Service Type / Detail
                </label>

                <input type="text"
                       name="service_type"
                       value="{{ $oldOr('service_type') }}"
                       class="sf-input"
                       placeholder="General service, detailing, callback delay">

                @error('service_type')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Lead Temperature --}}
            <div>
                <label class="sf-label">
                    Lead Temperature
                </label>

                @php($selectedTemperature = $oldOr('lead_temperature'))

                <select name="lead_temperature" class="sf-select">
                    <option value="">— Select Temperature —</option>

                    @foreach(['hot', 'warm', 'cold'] as $temperature)
                        <option value="{{ $temperature }}" @selected($selectedTemperature === $temperature)>
                            {{ ucfirst($temperature) }}
                        </option>
                    @endforeach
                </select>

                @error('lead_temperature')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Lead Priority --}}
            <div>
                <label class="sf-label">
                    Lead Priority
                </label>

                @php($selectedPriority = $oldOr('lead_priority'))

                <select name="lead_priority" class="sf-select">
                    <option value="">— Select Priority —</option>

                    @foreach(['urgent', 'high', 'medium', 'low'] as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>

                @error('lead_priority')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Campaign Name --}}
            <div>
                <label class="sf-label">
                    Campaign Name
                </label>

                <input type="text"
                       name="campaign_name"
                       value="{{ $oldOr('campaign_name') }}"
                       class="sf-input"
                       placeholder="Website import test, Meta campaign">

                @error('campaign_name')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Retention Tag --}}
            <div>
                <label class="sf-label">
                    Retention Tag
                </label>

                <input type="text"
                       name="retention_tag"
                       value="{{ $oldOr('retention_tag') }}"
                       class="sf-input"
                       placeholder="service due, quote followup, repeat customer">

                @error('retention_tag')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- WhatsApp Journey --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                WhatsApp Journey
            </h3>
            <p class="sf-section-subtitle">
                Used to send the thank-you message and push the customer into booking flow.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Tentative Service Type --}}
            <div>
                <label class="sf-label">
                    Tentative Service Needed
                </label>

                <select name="tentative_service_type" class="sf-select">
                    <option value="">— Select Service —</option>

                    @foreach([
                        'General Service',
                        'Oil Change',
                        'AC Service',
                        'Brake Check',
                        'Battery Check',
                        'Tyre Service',
                        'Car Inspection / Renewal Prep',
                        'Other',
                    ] as $service)
                        <option value="{{ $service }}" @selected($selectedService === $service)>
                            {{ $service }}
                        </option>
                    @endforeach
                </select>

                <p class="sf-help">
                    This can be used in the WhatsApp welcome message.
                </p>

                @error('tentative_service_type')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Send WhatsApp Now --}}
            @if(! $isEdit)
                <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox"
                               name="send_whatsapp_now"
                               value="1"
                               @checked(old('send_whatsapp_now', true))
                               class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

                        <span>
                            <span class="block text-sm font-extrabold text-green-300">
                                Send WhatsApp welcome message now
                            </span>

                            <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                                Sends a thank-you message and asks the customer for preferred appointment date/time.
                            </span>
                        </span>
                    </label>
                </div>
            @else
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-sm font-extrabold text-white">
                        WhatsApp Trigger
                    </div>

                    <p class="mt-1 text-xs font-medium leading-5 text-slate-400">
                        Auto WhatsApp trigger is available during new manual lead creation only.
                    </p>
                </div>
            @endif

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- Vehicle Capture --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Vehicle Details
            </h3>
            <p class="sf-section-subtitle">
                Vehicle details improve lead score, booking context, and service history.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Vehicle Make --}}
            <div>
                <label class="sf-label">
                    Vehicle Make
                </label>

                <input type="text"
                       name="other_make"
                       value="{{ $oldOr('other_make', $oldOr('vehicle_make')) }}"
                       class="sf-input"
                       placeholder="Toyota, Nissan, Jeep, BMW">

                @error('other_make')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Vehicle Model --}}
            <div>
                <label class="sf-label">
                    Vehicle Model
                </label>

                <input type="text"
                       name="other_model"
                       value="{{ $oldOr('other_model', $oldOr('vehicle_model')) }}"
                       class="sf-input"
                       placeholder="Patrol, Camry, Compass">

                @error('other_model')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Vehicle Year --}}
            <div>
                <label class="sf-label">
                    Vehicle Year
                </label>

                <input type="text"
                       name="vehicle_year"
                       value="{{ $oldOr('vehicle_year') }}"
                       class="sf-input"
                       placeholder="2021">

                @error('vehicle_year')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Plate Number --}}
            <div>
                <label class="sf-label">
                    Plate Number
                </label>

                <input type="text"
                       name="plate_number"
                       value="{{ $oldOr('plate_number') }}"
                       class="sf-input"
                       placeholder="Dubai A 12345">

                @error('plate_number')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- Follow-up --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Follow-up
            </h3>
            <p class="sf-section-subtitle">
                Helps the team track due leads and pending customer actions.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Follow-up Required --}}
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox"
                           name="follow_up_required"
                           value="1"
                           @checked((bool) old('follow_up_required', $oldOr('follow_up_required', false)))
                           class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                    <span>
                        <span class="block text-sm font-extrabold text-white">
                            Follow-up required
                        </span>

                        <span class="mt-1 block text-xs font-medium text-slate-400">
                            Mark this if the team needs to follow up with the customer.
                        </span>
                    </span>
                </label>

                @error('follow_up_required')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Follow-up Date --}}
            <div>
                <label class="sf-label">
                    Follow-up Date
                </label>

                <input type="date"
                       name="follow_up_date"
                       value="{{ $oldOr('follow_up_date') ? \Illuminate\Support\Carbon::parse($oldOr('follow_up_date'))->format('Y-m-d') : '' }}"
                       class="sf-input">

                @error('follow_up_date')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- Assignment --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Assignment
            </h3>
            <p class="sf-section-subtitle">
                Assign this lead to the right team member for follow-up and conversion.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

            {{-- Assigned To --}}
            <div>
                <label class="sf-label">
                    Assigned To
                </label>

                <select name="assigned_to" class="sf-select">
                    <option value="">Unassigned</option>

                    @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) $assignedTo === (string) $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>

                @if($assignableUsers->isEmpty())
                    <p class="sf-help">
                        No assignable users were passed to this form.
                    </p>
                @endif

                @error('assigned_to')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

        </div>
    </section>

    <div class="sf-divider"></div>

    {{-- Notes --}}
    <section class="space-y-5">
        <div>
            <h3 class="sf-section-title">
                Notes
            </h3>
            <p class="sf-section-subtitle">
                Internal notes for the garage team.
            </p>
        </div>

        <div>
            <label class="sf-label">
                Notes
            </label>

            <textarea name="notes"
                      rows="5"
                      class="sf-textarea"
                      placeholder="Add lead notes, customer requirements, quotation context, or follow-up details...">{{ $oldOr('notes') }}</textarea>

            @error('notes')
                <div class="sf-error">{{ $message }}</div>
            @enderror
        </div>
    </section>

</div>