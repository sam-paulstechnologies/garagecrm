<form method="GET" action="{{ route('admin.jobs.completed') }}" class="sf-card">
    <div class="sf-card-body">
        <div class="flex flex-col gap-3 md:flex-row">
            <input type="text"
                   name="q"
                   value="{{ $q ?? '' }}"
                   placeholder="Search job code, client, phone, service..."
                   class="sf-input md:flex-1" />

            <button class="sf-btn-primary">
                Search
            </button>

            <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
                Reset
            </a>
        </div>
    </div>
</form>
