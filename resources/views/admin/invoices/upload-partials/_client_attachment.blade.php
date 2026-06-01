@isset($client)
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Job Attachment
            </h2>

            <p class="sf-section-subtitle">
                Optionally attach this invoice to a job and mark it as primary.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-label">
                        Attach to Job ID
                    </label>

                    <input type="number"
                           name="job_id"
                           placeholder="Attach to Job ID (optional)"
                           value="{{ old('job_id', $jobId ?? '') }}"
                           class="sf-input">

                    @error('job_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox"
                               name="is_primary"
                               value="1"
                               @checked(old('is_primary'))
                               class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

                        <span>
                            <span class="block text-sm font-extrabold text-green-300">
                                Set as Primary
                            </span>

                            <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                                Applies if a Job ID is selected.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
@endisset
