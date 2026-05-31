<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Booking Details
        </h2>

        <p class="sf-section-subtitle">
            Confirm the appointment date, slot, status, priority, and owner.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label for="booking_date" class="sf-label">
                    Booking Date <span class="text-red-300">*</span>
                </label>

                <input type="date"
                       id="booking_date"
                       name="booking_date"
                       value="{{ $bookingDateVal }}"
                       required
                       class="sf-input">

                @error('booking_date')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="slot" class="sf-label">
                    Slot <span class="text-red-300">*</span>
                </label>

                <select id="slot" name="slot" required class="sf-select">
                    @foreach($slotOptions as $value => $label)
                        <option value="{{ $value }}" @selected($slotVal === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('slot')
                    <div class="sf-error">{{ $message }}</div>
                @enderror

                <div id="slot_capacity_hint" class="sf-help"></div>
            </div>

            <div>
                <label for="status" class="sf-label">
                    Status <span class="text-red-300">*</span>
                </label>

                <select id="status" name="status" required class="sf-select">
                    @foreach($bookingStatuses as $status)
                        <option value="{{ $status }}" @selected($statusVal === $status)>
                            {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>

                @error('status')
                    <div class="sf-error">{{ $message }}</div>
                @enderror

                <div id="status_help" class="sf-help">
                    {{ $statusHelp[$statusVal] ?? 'Select the current booking status.' }}
                </div>
            </div>

            <div>
                <label for="priority" class="sf-label">
                    Priority
                </label>

                <select id="priority" name="priority" class="sf-select">
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" @selected($priorityVal === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('priority')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="assigned_to" class="sf-label">
                    Assigned To
                </label>

                <select id="assigned_to" name="assigned_to" class="sf-select">
                    <option value="">Unassigned</option>

                    @foreach(($users ?? collect()) as $user)
                        <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                            {{ $user->name }}{{ !empty($user->role) ? ' - '.ucfirst($user->role) : '' }}
                        </option>
                    @endforeach
                </select>

                @error('assigned_to')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="expected_close_date" class="sf-label">
                    Expected Close Date
                </label>

                <input type="date"
                       id="expected_close_date"
                       name="expected_close_date"
                       value="{{ $expectedCloseDateVal }}"
                       class="sf-input">

                @error('expected_close_date')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="expected_duration" class="sf-label">
                    Expected Duration (Days)
                </label>

                <input type="number"
                       id="expected_duration"
                       name="expected_duration"
                       value="{{ $oldOr('expected_duration') }}"
                       min="0"
                       class="sf-input"
                       placeholder="Example: 1">

                @error('expected_duration')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox"
                           name="allow_overbooking"
                           id="allow_overbooking"
                           value="1"
                           @checked($allowOverbookingVal === '1' || $allowOverbookingVal === 1)
                           class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                    <span>
                        <span class="block text-sm font-extrabold text-orange-300">
                            Allow Overbooking
                        </span>

                        <span class="mt-1 block text-xs font-medium leading-5 text-orange-100/80">
                            Use this only when you intentionally want to exceed slot capacity.
                        </span>
                    </span>
                </label>

                @error('allow_overbooking')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
