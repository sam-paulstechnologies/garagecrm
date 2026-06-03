{{-- resources/views/admin/invoices/index-partials/_hero.blade.php --}}

<div class="sf-invoices-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="sf-invoice-title text-3xl font-extrabold tracking-tight">
                Invoices
            </h1>

            <p class="sf-invoice-muted mt-2 max-w-3xl text-sm font-medium">
                Track invoice revenue, payment status, job attribution, and ROI readiness.
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
</div>