{{-- resources/views/admin/opportunities/partials/notes.blade.php --}}

<div class="space-y-6">

    {{-- Notes --}}
    <div>
        <label class="sf-label">
            Notes
        </label>

        <textarea name="notes"
                  rows="4"
                  class="sf-textarea"
                  placeholder="Add internal notes, quote details, customer preference, or follow-up context...">{{ old('notes', $opportunity->notes ?? '') }}</textarea>

        @error('notes')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Close Reason --}}
    <div>
        <label class="sf-label">
            Close Reason
        </label>

        <textarea name="close_reason"
                  rows="2"
                  class="sf-textarea"
                  placeholder="Use this when the opportunity is closed lost...">{{ old('close_reason', $opportunity->close_reason ?? '') }}</textarea>

        @error('close_reason')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Score --}}
    <div>
        <label class="sf-label">
            Opportunity Score
        </label>

        <input type="number"
               name="score"
               value="{{ old('score', $opportunity->score ?? '') }}"
               class="sf-input"
               placeholder="0 - 100">

        @error('score')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is Converted --}}
    <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
        <label for="is_converted" class="flex items-start gap-3">
            <input type="checkbox"
                   name="is_converted"
                   id="is_converted"
                   value="1"
                   @checked(old('is_converted', $opportunity->is_converted ?? false))
                   class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

            <span>
                <span class="block text-sm font-extrabold text-green-300">
                    Converted to Job/Booking
                </span>

                <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                    Mark this only when the opportunity has been confirmed and moved forward.
                </span>
            </span>
        </label>

        @error('is_converted')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

</div>