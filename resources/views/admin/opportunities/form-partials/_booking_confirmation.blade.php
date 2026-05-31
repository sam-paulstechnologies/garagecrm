<div id="booking_confirmation_wrap" class="hidden sf-card border-green-400/20">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="sf-section-title">Booking Confirmation</h2>
            <p class="sf-section-subtitle">Required only when stage is Booking Confirmed. This creates or updates the booking record.</p>
        </div>

        <span class="sf-badge-green">Customer Agreed</span>
    </div>

    <div class="sf-card-body space-y-5">
        <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5">
            <div class="font-extrabold text-green-300">Confirm actual booking details</div>
            <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">Do not rely only on tentative appointment date. Confirm the real booking date and slot here.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="sf-label">Confirmed Booking Date <span class="text-red-300">*</span></label>
                <input type="date" name="booking_date" id="booking_date" value="{{ $bookingDateVal }}" class="sf-input">
                @error('booking_date')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Confirmed Slot <span class="text-red-300">*</span></label>
                <select name="booking_slot" id="booking_slot" class="sf-select">
                    <option value="">-- Select Slot --</option>
                    <option value="morning" @selected($bookingSlotVal === 'morning')>Morning</option>
                    <option value="afternoon" @selected($bookingSlotVal === 'afternoon')>Afternoon</option>
                    <option value="evening" @selected($bookingSlotVal === 'evening')>Evening</option>
                </select>
                @error('booking_slot')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="sf-label">Booking Notes</label>
                <textarea name="booking_notes" id="booking_notes" rows="3" placeholder="Example: Customer requested pickup from office basement parking." class="sf-textarea">{{ $bookingNotesVal }}</textarea>
                @error('booking_notes')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
