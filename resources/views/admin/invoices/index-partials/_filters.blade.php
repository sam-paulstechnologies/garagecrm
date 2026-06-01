<form method="GET" action="{{ route('admin.invoices.index') }}" class="sf-card">
    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-7">
                <label class="sf-label">
                    Search
                </label>

                <input type="text"
                       name="q"
                       value="{{ $currentSearch }}"
                       placeholder="Search invoice no, client, phone, job code, amount..."
                       class="sf-input" />
            </div>

            <div class="lg:col-span-3">
                <label class="sf-label">
                    Status
                </label>

                @php
                    $statuses = [
                        '' => 'All Invoices',
                        'paid' => 'Paid',
                        'pending' => 'Pending',
                        'overdue' => 'Overdue',
                    ];
                @endphp

                <select name="status" class="sf-select">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-2">
                <button class="sf-btn-primary w-full">
                    Apply
                </button>

                <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                    Reset
                </a>
            </div>
        </div>
    </div>
</form>
