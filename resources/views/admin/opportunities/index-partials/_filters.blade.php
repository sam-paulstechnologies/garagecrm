<form method="GET" action="{{ route('admin.opportunities.index') }}" class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="md:col-span-1">
            <label class="sf-label">Search</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="Search title, client, vehicle..." class="sf-opportunity-input h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
        </div>

        <div>
            <label class="sf-label">Stage</label>
            <select name="stage" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                <option value="">All stages</option>
                @foreach(['new', 'attempting_contact', 'manager_confirmation_pending', 'appointment', 'closed_won', 'closed_lost'] as $stageOption)
                    <option value="{{ $stageOption }}" @selected($stage === $stageOption)>{{ $stageLabel($stageOption) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="sf-label">Priority</label>
            <select name="priority" class="sf-opportunity-select h-11 w-full rounded-xl border px-3 text-sm font-bold transition">
                <option value="">All priorities</option>
                @foreach(['urgent', 'high', 'medium', 'low'] as $priorityOption)
                    <option value="{{ $priorityOption }}" @selected($priority === $priorityOption)>{{ ucfirst($priorityOption) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            @if($bucket)
                <input type="hidden" name="bucket" value="{{ $bucket }}">
            @endif

            <button type="submit" class="sf-btn-primary w-full">Filter</button>

            @if($bucket || $stage || $priority || $q)
                <a href="{{ $clearUrl }}" class="sf-btn-secondary">Reset</a>
            @endif
        </div>
    </div>
</form>
