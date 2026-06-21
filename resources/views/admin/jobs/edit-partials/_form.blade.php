<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="sf-card sf-crm-edit-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Job Information
            </h2>

            <p class="sf-section-subtitle">
                Update client, owner, timing, job status, and service notes.
            </p>
        </div>

        <div class="sf-card-body">
            <form id="jobEditForm"
                  method="POST"
                  action="{{ route('admin.jobs.update', $job->id) }}"
                  class="space-y-6">

                @csrf
                @method('PUT')

                <input type="hidden" name="invoice_number" id="hidden_invoice_number" value="{{ old('invoice_number', $invoiceNumber) }}">
                <input type="hidden" name="invoice_amount" id="hidden_invoice_amount" value="{{ old('invoice_amount', $invoiceAmount) }}">

                <div class="sf-crm-section">
                    <div class="sf-crm-section-head">
                        <h3>Basic Job Details</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-label">
                        Client <span class="text-red-300">*</span>
                    </label>

                    <select name="client_id"
                            class="sf-select"
                            required>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ (int) $client->id === (int) $job->client_id ? 'selected' : '' }}>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('client_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Internal Owner
                    </label>

                    <select name="assigned_to" class="sf-select">
                        <option value="">Unassigned</option>

                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (int) $user->id === (int) $job->assigned_to ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('assigned_to')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Start Time
                    </label>

                    <input type="datetime-local"
                           name="start_time"
                           value="{{ old('start_time', optional($job->start_time)->format('Y-m-d\TH:i')) }}"
                           class="sf-input">

                    @error('start_time')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Current Stage
                    </label>

                    <select name="status"
                            id="job_status"
                            class="sf-select"
                            required>
                        <option value="pending" {{ $job->status === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="in_progress" {{ $job->status === 'in_progress' ? 'selected' : '' }}>
                            In Progress
                        </option>

                        <option value="completed" {{ $job->status === 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>
                    </select>

                    <p class="sf-help">
                        Completed requires invoice number and amount.
                    </p>

                    @error('status')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
                    </div>
                </div>

                <div class="sf-crm-section">
                    <div class="sf-crm-section-head">
                        <h3>Service Notes</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="sf-label">
                        Service / Job Description <span class="text-red-300">*</span>
                    </label>

                    <textarea name="description"
                              class="sf-textarea"
                              rows="3"
                              required>{{ old('description', $job->description) }}</textarea>

                    @error('description')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="sf-label">
                        Work Summary
                    </label>

                    <textarea name="work_summary"
                              class="sf-textarea"
                              rows="3">{{ old('work_summary', $job->work_summary) }}</textarea>

                    @error('work_summary')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Issues Found
                    </label>

                    <textarea name="issues_found"
                              class="sf-textarea"
                              rows="3">{{ old('issues_found', $job->issues_found) }}</textarea>

                    @error('issues_found')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Parts Used
                    </label>

                    <textarea name="parts_used"
                              class="sf-textarea"
                              rows="3">{{ old('parts_used', $job->parts_used) }}</textarea>

                    @error('parts_used')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
                    </div>
                </div>

                @include('admin.jobs.edit-partials._service_signal')
                @include('admin.jobs.edit-partials._invoice_notice')

                <div class="sf-crm-action-bar flex flex-wrap items-center justify-end gap-3 border-t border-white/10 pt-4">
                    <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="sf-btn-primary">
                        Update Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div class="sf-card sf-job-edit-side-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">Job Snapshot</h2>
            </div>

            <div class="divide-y divide-white/10 text-sm">
                @foreach([
                    'Job' => $job->job_code ?? 'Job #' . $job->id,
                    'Client' => $job->client?->name ?? 'No client linked',
                    'Stage' => ucwords(str_replace('_', ' ', $status)),
                    'Service Bucket' => $serviceBucket,
                    'Invoice' => $invoiceNumber ?: 'Not captured',
                    'Created' => $job->created_at?->format('d M Y, h:i A') ?? 'Not set',
                ] as $label => $value)
                    <div class="px-5 py-3">
                        <div class="sf-job-field-label">{{ $label }}</div>
                        <div class="sf-job-field-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sf-job-note rounded-2xl border p-5 shadow-sm">
            <div class="sf-job-note-title font-extrabold">Completion Rule</div>
            <p class="sf-job-note-text mt-2 text-sm font-semibold leading-6">
                Completed requires invoice number and amount. The existing validation and invoice creation flow are still used.
            </p>
        </div>
    </aside>
</div>
