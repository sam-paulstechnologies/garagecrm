@php
    $lead = $lead ?? null;
    $isEdit = isset($lead) && $lead;

    $oldOr = function ($key, $default = null) use ($lead) {
        return old($key, data_get($lead, $key, $default));
    };

    $selectedStatus = \App\Models\Client\Lead::normalizeStatus($oldOr('status', 'new'));
    $selectedChannel = $oldOr('preferred_channel', 'whatsapp');
    $selectedService = old('tentative_service_type', data_get($lead, 'tentative_service_type', ''));
    $selectedSubStatus = $oldOr('status_sub_status');
    $selectedFollowUpAt = $oldOr('follow_up_at');
    $selectedCategory = $oldOr('service_category');
    $selectedTemperature = $oldOr('lead_temperature');
    $selectedPriority = $oldOr('lead_priority');
    $assignedTo = $oldOr('assigned_to');

    $leadStatusOptions = [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'contact_on_hold' => 'Contact On Hold',
        'qualified' => 'Qualified',
        'disqualified' => 'Disqualified',
    ];

    $contactOnHoldSubStatuses = [
        'call_back_requested' => 'Call back requested',
        'customer_requested_later' => 'Customer requested later',
        'waiting_for_customer_response' => 'Waiting for customer response',
        'awaiting_vehicle_details' => 'Awaiting vehicle details',
        'awaiting_service_confirmation' => 'Awaiting service confirmation',
        'awaiting_estimate_approval' => 'Awaiting estimate approval',
        'other' => 'Other',
    ];

    $disqualifiedSubStatuses = [
        'not_interested' => 'Not interested',
        'wrong_number' => 'Wrong number',
        'duplicate' => 'Duplicate',
        'unreachable_after_attempts' => 'Unreachable after multiple attempts',
        'out_of_service_area' => 'Out of service area',
        'service_not_offered' => 'Service not offered',
        'price_not_accepted' => 'Price not accepted',
        'already_serviced_elsewhere' => 'Already serviced elsewhere',
        'spam_or_test' => 'Spam / test lead',
        'other' => 'Other',
    ];

    $assignableUsers = collect($users ?? $managers ?? $assignableUsers ?? $employees ?? []);

    $fieldClass = 'sf-crm-field';
@endphp

<div class="sf-crm-form">
    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Basic Lead Details</h3>
        </div>

        <div class="sf-crm-grid">
            <div class="{{ $fieldClass }}">
                <label class="sf-label">Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ $oldOr('name') }}" required class="sf-input" placeholder="Customer name">
                @error('name')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Phone / WhatsApp Number</label>
                <input type="text" name="phone" value="{{ $oldOr('phone') }}" class="sf-input" placeholder="971586934377">
                @error('phone')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Email</label>
                <input type="email" name="email" value="{{ $oldOr('email') }}" class="sf-input" placeholder="customer@example.com">
                @error('email')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Preferred Channel</label>
                <select name="preferred_channel" class="sf-select">
                    @foreach(['whatsapp', 'phone', 'email'] as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                    @endforeach
                </select>
                @error('preferred_channel')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Source</label>
                <input type="text" name="source" value="{{ $oldOr('source', 'Manual') }}" class="sf-input" placeholder="Manual, Walk-in, Referral">
                @error('source')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Lead Temperature</label>
                <select name="lead_temperature" class="sf-select">
                    <option value="">Not set</option>
                    @foreach(['hot', 'warm', 'cold'] as $temperature)
                        <option value="{{ $temperature }}" @selected($selectedTemperature === $temperature)>{{ ucfirst($temperature) }}</option>
                    @endforeach
                </select>
                @error('lead_temperature')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Lead Priority</label>
                <select name="lead_priority" class="sf-select">
                    <option value="">Not set</option>
                    @foreach(['urgent', 'high', 'medium', 'low'] as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('lead_priority')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            @unless($isEdit)
                <div class="{{ $fieldClass }} md:col-span-2">
                    <label class="flex items-start gap-3 rounded-xl border border-orange-400/25 bg-orange-500/10 p-3 text-sm font-bold text-orange-100">
                        <input type="checkbox" name="send_whatsapp_now" value="1" @checked(old('send_whatsapp_now', true)) class="mt-1 rounded border-orange-300 text-orange-500">
                        <span>
                            <span class="block text-orange-200">Send WhatsApp welcome message now</span>
                            <span class="mt-1 block text-xs font-semibold text-orange-100/75">
                                Creates the lead and starts the booking conversation when WhatsApp is the preferred channel.
                            </span>
                        </span>
                    </label>
                    @error('send_whatsapp_now')<div class="sf-error">{{ $message }}</div>@enderror
                </div>
            @endunless
        </div>
    </section>

    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Lead Lifecycle</h3>
            <p>Hold and disqualified statuses need a reason. Qualified opens or reuses an Opportunity.</p>
        </div>

        <div class="sf-crm-grid">
            <div class="{{ $fieldClass }}">
                <label class="sf-label">Status</label>
                <select name="status" class="sf-select" data-lead-status-select>
                    @foreach($leadStatusOptions as $status => $label)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="sf-error">{{ $message }}</div>@enderror
                <div class="sf-crm-status-hint mt-2 rounded-lg border border-orange-400/25 bg-orange-500/10 px-3 py-2 text-xs font-bold" data-lead-status-hint></div>
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Status Sub-status</label>
                <select name="status_sub_status" class="sf-select" data-lead-sub-status-select>
                    <option value="">Select when required</option>
                    <optgroup label="Contact On Hold" data-status-group="contact_on_hold">
                        @foreach($contactOnHoldSubStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedSubStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Disqualified" data-status-group="disqualified">
                        @foreach($disqualifiedSubStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedSubStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @error('status_sub_status')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Status Follow-up Date / Time</label>
                <input type="datetime-local" name="follow_up_at" value="{{ $selectedFollowUpAt ? \Illuminate\Support\Carbon::parse($selectedFollowUpAt)->format('Y-m-d\TH:i') : '' }}" class="sf-input">
                @error('follow_up_at')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }} md:col-span-2">
                <label class="sf-label">Status Note / Reason</label>
                <textarea name="status_reason" rows="2" class="sf-textarea" placeholder="Required when Other is selected">{{ $oldOr('status_reason') }}</textarea>
                @error('status_reason')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Service / Vehicle Context</h3>
        </div>

        <div class="sf-crm-grid">
            <div class="{{ $fieldClass }}">
                <label class="sf-label">Service Category</label>
                <select name="service_category" class="sf-select">
                    <option value="">Not set</option>
                    @foreach(['service', 'quote', 'repair', 'complaint', 'emergency', 'enquiry'] as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
                @error('service_category')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Service Type / Detail</label>
                <input type="text" name="service_type" value="{{ $oldOr('service_type') }}" class="sf-input" placeholder="General service, detailing">
                @error('service_type')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Tentative Service Needed</label>
                <select name="tentative_service_type" class="sf-select">
                    <option value="">Not set</option>
                    @foreach(['General Service', 'Oil Change', 'AC Service', 'Brake Check', 'Battery Check', 'Tyre Service', 'Car Inspection / Renewal Prep', 'Other'] as $service)
                        <option value="{{ $service }}" @selected($selectedService === $service)>{{ $service }}</option>
                    @endforeach
                </select>
                @error('tentative_service_type')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Vehicle Make / Brand</label>
                <input type="text" name="other_make" value="{{ $oldOr('other_make', $oldOr('vehicle_make')) }}" class="sf-input" placeholder="Toyota, Peugeot, BMW">
                @error('other_make')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Vehicle Model / Line</label>
                <input type="text" name="other_model" value="{{ $oldOr('other_model', $oldOr('vehicle_model')) }}" class="sf-input" placeholder="Patrol, 408, Camry">
                @error('other_model')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Vehicle Year</label>
                <input type="number" name="vehicle_year" value="{{ $oldOr('vehicle_year') }}" class="sf-input" placeholder="2021">
                @error('vehicle_year')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Plate Number</label>
                <input type="text" name="plate_number" value="{{ $oldOr('plate_number') }}" class="sf-input" placeholder="Dubai A 12345">
                @error('plate_number')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Marketing / Source</h3>
        </div>

        <div class="sf-crm-grid">
            <div class="{{ $fieldClass }}">
                <label class="sf-label">Campaign Name</label>
                <input type="text" name="campaign_name" value="{{ $oldOr('campaign_name') }}" class="sf-input" placeholder="Meta campaign">
                @error('campaign_name')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">Retention Tag</label>
                <input type="text" name="retention_tag" value="{{ $oldOr('retention_tag') }}" class="sf-input" placeholder="service_due, quote_followup">
                @error('retention_tag')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="{{ $fieldClass }}">
                <label class="sf-label">External Source</label>
                <input type="text" name="external_source" value="{{ $oldOr('external_source') }}" class="sf-input" placeholder="Instagram, WhatsApp, Website">
                @error('external_source')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Assignment</h3>
        </div>

        <div class="sf-crm-grid">
            <div class="{{ $fieldClass }}">
                <label class="sf-label">Assigned To / Owner</label>
                <select name="assigned_to" class="sf-select">
                    <option value="">Unassigned</option>
                    @foreach($assignableUsers as $user)
                        <option value="{{ $user->id }}" @selected((string) $assignedTo === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="sf-crm-section">
        <div class="sf-crm-section-head">
            <h3>Notes</h3>
        </div>

        <div class="{{ $fieldClass }}">
            <label class="sf-label">Notes / Description</label>
            <textarea name="notes" rows="4" class="sf-textarea" placeholder="Customer requirements, quotation context, follow-up details...">{{ $oldOr('notes') }}</textarea>
            @error('notes')<div class="sf-error">{{ $message }}</div>@enderror
        </div>
    </section>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-lead-status-select]').forEach((statusSelect) => {
                const section = statusSelect.closest('.sf-crm-section');
                if (! section) return;

                const subStatusSelect = section.querySelector('[data-lead-sub-status-select]');
                const hint = section.querySelector('[data-lead-status-hint]');
                const groups = subStatusSelect ? subStatusSelect.querySelectorAll('[data-status-group]') : [];

                const messages = {
                    new: 'New is a simple lifecycle update.',
                    attempting_contact: 'Attempting Contact is a simple lifecycle update.',
                    contact_on_hold: 'Contact On Hold requires a sub-status. Callback and customer-later reasons also require follow-up date/time.',
                    qualified: 'Qualified creates or opens one Opportunity and keeps the lead status as Qualified.',
                    disqualified: 'Disqualified requires a sub-status and never creates an Opportunity.',
                };

                const syncLifecycleFields = () => {
                    const status = statusSelect.value;

                    if (hint) {
                        hint.textContent = messages[status] || '';
                        hint.hidden = ! hint.textContent;
                    }

                    groups.forEach((group) => {
                        group.disabled = ! ['contact_on_hold', 'disqualified'].includes(status)
                            || group.dataset.statusGroup !== status;
                    });

                    if (subStatusSelect && ! ['contact_on_hold', 'disqualified'].includes(status)) {
                        subStatusSelect.value = '';
                    }
                };

                statusSelect.addEventListener('change', syncLifecycleFields);
                syncLifecycleFields();
            });
        });
    </script>
@endonce
