{{-- resources/views/admin/clients/partials/invoices.blade.php --}}

@php
    $invoices = method_exists($client, 'invoices')
        ? $client->invoices()->latest()->get()
        : collect();

    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid' => 'sf-badge-green',
            'unpaid', 'pending' => 'sf-badge-orange',
            'overdue' => 'sf-badge-red',
            'cancelled', 'canceled', 'void' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };

    $invoiceAmount = function ($invoice) {
        $amount =
            $invoice->total_amount
            ?? $invoice->amount
            ?? $invoice->grand_total
            ?? $invoice->total
            ?? 0;

        return 'AED ' . number_format((float) $amount, 2);
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Invoices
            </h2>

            <p class="sf-section-subtitle">
                Client invoice history and billing records.
            </p>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.invoices.create'))
            <a href="{{ route('admin.invoices.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                Create Invoice
            </a>
        @endif
    </div>

    {{-- Invoice List --}}
    @if($invoices->isEmpty())
        <div class="sf-empty">
            No invoices yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach($invoices as $inv)
                @php
                    $number = $inv->number
                        ?? $inv->invoice_number
                        ?? ('Invoice #' . $inv->id);

                    $status = $inv->status ?? 'pending';
                @endphp

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-extrabold text-white">
                                    {{ $number }}
                                </div>

                                <span class="{{ $statusBadge($status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </span>
                            </div>

                            <div class="mt-2 text-sm font-bold text-orange-300">
                                {{ $invoiceAmount($inv) }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ optional($inv->created_at)->format('d M Y') ?? 'No date' }}
                            </div>
                        </div>

                        <div class="shrink-0 text-left sm:text-right">
                            @if(\Illuminate\Support\Facades\Route::has('admin.invoices.show'))
                                <a href="{{ route('admin.invoices.show', $inv) }}" class="sf-link">
                                    View
                                </a>
                            @else
                                <span class="text-xs font-bold text-slate-600">
                                    #{{ $inv->id }}
                                </span>
                            @endif
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>