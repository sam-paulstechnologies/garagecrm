{{-- resources/views/admin/clients/show-partials/sections/_invoices_section.blade.php --}}

@php
    $invoices = collect($client->invoices ?? []);

    $invoiceCreateRoute = \Illuminate\Support\Facades\Route::has('admin.invoices.create')
        ? route('admin.invoices.create', ['client_id' => $client->id])
        : null;

    $invoiceShowRoute = function ($invoice) {
        return \Illuminate\Support\Facades\Route::has('admin.invoices.show')
            ? route('admin.invoices.show', $invoice->id)
            : null;
    };

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-invoices-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-invoices-title {
        color: #ffffff;
    }

    .sf-invoices-muted {
        color: #cbd5e1;
    }

    .sf-invoices-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-invoices-value {
        color: #ffffff;
    }

    .sf-invoices-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    html[data-theme="light"] .sf-invoices-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-invoices-title,
    html[data-theme="light"] .sf-invoices-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-invoices-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-invoices-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-invoices-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
</style>

<section id="invoices" class="sf-invoices-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-invoices-title text-lg font-extrabold tracking-tight">
                Invoices
            </h2>

            <p class="sf-invoices-muted mt-1 text-sm font-medium">
                Client invoice history and billing records.
            </p>
        </div>

        @if($invoiceCreateRoute)
            <a
                href="{{ $invoiceCreateRoute }}"
                class="inline-flex h-8 items-center justify-center whitespace-nowrap rounded-lg bg-orange-500 px-3 text-xs font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
            >
                Create Invoice
            </a>
        @endif
    </div>

    @if($invoices->isNotEmpty())
        <div class="space-y-3">
            @foreach($invoices->take(5) as $invoice)
                @php
                    $showRoute = $invoiceShowRoute($invoice);
                @endphp

                <div class="sf-invoices-card rounded-2xl border p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="sf-invoices-value text-sm font-black">
                                {{ $invoice->number ?? $invoice->invoice_number ?? 'Invoice #' . $invoice->id }}
                            </p>

                            <p class="sf-invoices-muted mt-1 text-xs font-medium">
                                AED {{ number_format((float) ($invoice->amount ?? $invoice->total ?? 0), 2) }}
                                · {{ $formatDate($invoice->created_at ?? null) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-300">
                                {{ $invoice->status ?? 'pending' }}
                            </span>

                            @if($showRoute)
                                <a href="{{ $showRoute }}" class="text-xs font-black text-orange-300 hover:text-orange-200">
                                    View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sf-invoices-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No invoices yet.
        </div>
    @endif
</section>
