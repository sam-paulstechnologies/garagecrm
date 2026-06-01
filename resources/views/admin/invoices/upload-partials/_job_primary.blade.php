@isset($job)
    <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20">
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
                    Mark this invoice as the main invoice for this job.
                </span>
            </span>
        </label>

        @error('is_primary')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>
@endisset
