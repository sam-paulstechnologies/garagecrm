<div class="mt-6 space-y-4">
    <!-- Notes -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" rows="4"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes', $opportunity->notes ?? '') }}</textarea>
    </div>

    <!-- Close Reason (if closed_lost) -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Close Reason</label>
        <textarea name="close_reason" rows="2"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('close_reason', $opportunity->close_reason ?? '') }}</textarea>
    </div>

    <!-- Score -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Opportunity Score</label>
        <input type="number" name="score"
               value="{{ old('score', $opportunity->score ?? '') }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Is Converted -->
    <div class="flex items-center">
        <input type="checkbox" name="is_converted" id="is_converted" value="1"
               @checked(old('is_converted', $opportunity->is_converted ?? false))
               class="mr-2 border-gray-300 rounded shadow-sm">
        <label for="is_converted" class="text-sm text-gray-700">Converted to Job/Booking</label>
    </div>
</div>
