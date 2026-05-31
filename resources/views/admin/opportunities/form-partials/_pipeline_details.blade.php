<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">Pipeline Details</h2>
        <p class="sf-section-subtitle">Control the sales stage, priority, value, owner, and expected appointment date.</p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="sf-label">Stage <span class="text-red-300">*</span></label>
                <select name="stage" id="stage_select" required class="sf-select">
                    @foreach($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStage === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="sf-help">Select the current stage of this opportunity.</p>
                @error('stage')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Priority</label>
                <select name="priority" class="sf-select">
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedPriority === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Tentative Appointment / Planning Date</label>
                <input type="date" name="expected_close_date" id="expected_close_date" value="{{ old('expected_close_date', $fmtDate($opp?->expected_close_date ?? null)) }}" class="sf-input">
                <p class="sf-help">This is only a tentative or planning date. Confirmed booking date is captured when stage is Booking Confirmed.</p>
                @error('expected_close_date')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Estimated Value (AED)</label>
                <input type="number" step="0.01" min="0" name="value" value="{{ $oldOr('value') }}" placeholder="0.00" class="sf-input">
                @error('value')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Owner / Manager</label>
                <select name="assigned_to" class="sf-select">
                    <option value="">Unassigned</option>
                    @foreach($usersCollection as $user)
                        <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                            {{ $user->name }}{{ !empty($user->role) ? ' - '.ucfirst($user->role) : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="sf-help">Only admin and manager users should be shown here.</p>
                @error('assigned_to')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div id="close_reason_wrap" class="hidden">
                <label class="sf-label">Close Reason <span class="text-red-300">*</span></label>
                <select name="close_reason" id="close_reason" class="sf-select">
                    <option value="">-- Select reason --</option>
                    @foreach($closeReasonOptions as $reason)
                        <option value="{{ $reason }}" @selected(old('close_reason', $opp?->close_reason ?? '') === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
                <p class="sf-help">Used later for retention and marketing analysis.</p>
                @error('close_reason')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
