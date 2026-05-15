{{-- resources/views/admin/clients/partials/invoices.blade.php --}}

@php
    $panelId    = 'client-invoices-' . $client->id;
    $modalId    = $panelId . '-upload-modal';
    $openBtnId  = $panelId . '-open';
    $closeBtnId = $panelId . '-close';
    $advId      = $panelId . '-adv';

    $invoices = method_exists($client, 'invoices')
        ? $client->invoices()->latest('id')->get()
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

    $sourceBadge = function ($source) {
        $source = strtolower((string) $source);

        return match ($source) {
            'upload' => 'sf-badge-blue',
            'manual' => 'sf-badge-orange',
            'system' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };
    };

    $invoiceAmount = function ($invoice) {
        $amount =
            $invoice->amount
            ?? $invoice->total_amount
            ?? $invoice->grand_total
            ?? $invoice->total
            ?? null;

        $currency = $invoice->currency ?? 'AED';

        return ! is_null($amount)
            ? number_format((float) $amount, 2) . ' ' . $currency
            : '—';
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Invoices
            </h2>

            <p class="sf-section-subtitle">
                Uploaded and generated invoices linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.invoices.index'))
                <a href="{{ route('admin.invoices.index') }}?client_id={{ $client->id }}" class="sf-btn-secondary">
                    View All
                </a>
            @endif

            @if(Route::has('admin.clients.invoices.upload'))
                <button id="{{ $openBtnId }}" type="button" class="sf-btn-primary">
                    + Upload Invoice
                </button>
            @endif
        </div>
    </div>

    {{-- Empty State --}}
    @if($invoices->isEmpty())
        <div class="sf-empty">
            <div>No invoices yet.</div>

            @if(Route::has('admin.clients.invoices.upload'))
                <div class="mt-4">
                    <button id="{{ $openBtnId }}-empty" type="button" class="sf-btn-primary">
                        Upload Invoice
                    </button>
                </div>
            @endif
        </div>
    @else

        {{-- Invoice Table --}}
        <div class="sf-table-wrap">
            <div class="sf-table-scroll">
                <table class="sf-table">
                    <thead>
                        <tr>
                            <th class="w-[24%]">Invoice</th>
                            <th class="w-[10%]">Job</th>
                            <th class="w-[14%]">Date</th>
                            <th class="w-[14%]">Amount</th>
                            <th class="w-[12%]">Status</th>
                            <th class="w-[12%]">Source</th>
                            <th class="w-[6%]">Ver.</th>
                            <th class="w-[8%] text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($invoices as $inv)
                            @php
                                $invoiceLabel = $inv->number
                                    ?? $inv->invoice_number
                                    ?? (!empty($inv->file_path) ? basename($inv->file_path) : null)
                                    ?? ('Invoice #' . $inv->id);

                                $invoiceDate = $inv->invoice_date
                                    ?? $inv->created_at
                                    ?? null;
                            @endphp

                            <tr>
                                {{-- Invoice --}}
                                <td>
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($inv->is_primary)
                                            <span class="sf-badge-green">
                                                Primary
                                            </span>
                                        @endif

                                        @if($inv->file_path && Route::has('admin.invoices.view'))
                                            <a href="{{ route('admin.invoices.view', $inv) }}"
                                               target="_blank"
                                               class="font-extrabold text-white hover:text-orange-300 hover:underline">
                                                {{ $invoiceLabel }}
                                            </a>
                                        @else
                                            <span class="font-extrabold text-white">
                                                {{ $invoiceLabel }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 text-xs font-medium text-slate-500">
                                        Invoice ID: #{{ $inv->id }}
                                    </div>
                                </td>

                                {{-- Job --}}
                                <td>
                                    @if($inv->job_id)
                                        <span class="sf-badge-slate">
                                            #{{ $inv->job_id }}
                                        </span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ $invoiceDate ? \Illuminate\Support\Carbon::parse($invoiceDate)->format('d M Y') : '—' }}
                                    </div>

                                    @if(!empty($inv->due_date))
                                        <div class="mt-1 text-xs text-slate-500">
                                            Due {{ \Illuminate\Support\Carbon::parse($inv->due_date)->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Amount --}}
                                <td>
                                    <div class="font-extrabold text-orange-300">
                                        {{ $invoiceAmount($inv) }}
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="{{ $statusBadge($inv->status ?? 'pending') }}">
                                        {{ ucfirst(str_replace('_', ' ', $inv->status ?? 'pending')) }}
                                    </span>
                                </td>

                                {{-- Source --}}
                                <td>
                                    <span class="{{ $sourceBadge($inv->source ?? 'upload') }}">
                                        {{ ucfirst(str_replace('_', ' ', $inv->source ?? 'upload')) }}
                                    </span>
                                </td>

                                {{-- Version --}}
                                <td>
                                    <span class="sf-badge-slate">
                                        v{{ $inv->version ?? 1 }}
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if($inv->file_path && Route::has('admin.invoices.download'))
                                            <a class="sf-link" href="{{ route('admin.invoices.download', $inv) }}">
                                                Download
                                            </a>
                                        @endif

                                        @if($inv->file_path && Route::has('admin.invoices.view'))
                                            <a class="sf-link" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">
                                                View
                                            </a>
                                        @endif

                                        {{-- Make Primary only if attached to a Job --}}
                                        @if(!$inv->is_primary && $inv->job_id && Route::has('admin.invoices.primary'))
                                            <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                                                @csrf
                                                <button class="sf-link" type="submit">
                                                    Make Primary
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(Route::has('admin.clients.invoices.upload'))
            <div>
                <button id="{{ $openBtnId }}-below" type="button" class="sf-btn-secondary">
                    + Upload Another
                </button>
            </div>
        @endif
    @endif

</div>

{{-- Upload Modal --}}
@if(Route::has('admin.clients.invoices.upload'))
    <div id="{{ $modalId }}"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4 backdrop-blur-sm">

        <div class="w-full max-w-2xl overflow-hidden rounded-3xl border border-white/10 bg-slate-950 shadow-2xl shadow-black/50">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-white/10 bg-gradient-to-r from-slate-900 to-orange-950/40 px-5 py-4">
                <div>
                    <h4 class="font-extrabold text-white">
                        Upload Invoice
                    </h4>

                    <p class="mt-1 text-xs font-medium text-slate-400">
                        Attach an invoice file to this client and optionally link it to a job.
                    </p>
                </div>

                <button type="button"
                        id="{{ $closeBtnId }}"
                        class="flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-red-500/10 hover:text-red-300">
                    ✕
                </button>
            </div>

            {{-- Modal Form --}}
            <form method="POST"
                  action="{{ route('admin.clients.invoices.upload', $client) }}"
                  enctype="multipart/form-data"
                  class="space-y-5 p-5">
                @csrf

                <div>
                    <label class="sf-label">
                        Invoice file <span class="text-red-300">*</span>
                    </label>

                    <input type="file"
                           name="invoice_file"
                           required
                           class="block w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                    <p class="sf-help">
                        Allowed files: PDF, JPG, JPEG, PNG, WEBP. Maximum size: 5MB.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="sf-label">
                            Attach to Job ID
                        </label>

                        <input type="number"
                               name="job_id"
                               placeholder="Optional"
                               class="sf-input">
                    </div>
                </div>

                <details id="{{ $advId }}" class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/80">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-extrabold text-slate-200 transition hover:bg-white/5">
                        Advanced metadata
                    </summary>

                    <div class="grid grid-cols-1 gap-4 border-t border-white/10 p-4 md:grid-cols-3">
                        <div>
                            <label class="sf-label">
                                Invoice #
                            </label>

                            <input type="text"
                                   name="number"
                                   placeholder="INV-001"
                                   class="sf-input">
                        </div>

                        <div>
                            <label class="sf-label">
                                Invoice Date
                            </label>

                            <input type="date"
                                   name="invoice_date"
                                   class="sf-input">
                        </div>

                        <div>
                            <label class="sf-label">
                                Due Date
                            </label>

                            <input type="date"
                                   name="due_date"
                                   class="sf-input">
                        </div>

                        <div>
                            <label class="sf-label">
                                Currency
                            </label>

                            <input type="text"
                                   name="currency"
                                   placeholder="AED"
                                   class="sf-input">
                        </div>

                        <div>
                            <label class="sf-label">
                                Amount
                            </label>

                            <input type="number"
                                   step="0.01"
                                   name="amount"
                                   placeholder="0.00"
                                   class="sf-input">
                        </div>
                    </div>
                </details>

                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-white/10 pt-4">
                    <button type="button" id="{{ $closeBtnId }}-2" class="sf-btn-secondary">
                        Cancel
                    </button>

                    <button type="submit" class="sf-btn-primary">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal JS --}}
    <script>
        (function () {
            const modal = document.getElementById('{{ $modalId }}');

            const openers = [
                document.getElementById('{{ $openBtnId }}'),
                document.getElementById('{{ $openBtnId }}-empty'),
                document.getElementById('{{ $openBtnId }}-below')
            ].filter(Boolean);

            const closers = [
                document.getElementById('{{ $closeBtnId }}'),
                document.getElementById('{{ $closeBtnId }}-2')
            ].filter(Boolean);

            function openModal() {
                if (!modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                if (!modal) return;

                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            openers.forEach(btn => btn.addEventListener('click', openModal));
            closers.forEach(btn => btn.addEventListener('click', closeModal));

            modal?.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@endif