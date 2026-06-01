<div class="sf-page-header">
    <div>
        <div class="sf-kicker">
            Revenue Tracking
        </div>

        <h1 class="sf-page-title mt-3">
            Invoices
        </h1>

        <p class="sf-page-subtitle">
            Lightweight invoice tracking for revenue capture and future ROI reporting.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
            Open Jobs
        </a>

        @if(\Illuminate\Support\Facades\Route::has('admin.jobs.completed'))
            <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
                Completed Jobs
            </a>
        @endif

        <a href="{{ route('admin.invoices.create') }}" class="sf-btn-primary">
            + Create Invoice
        </a>
    </div>
</div>
