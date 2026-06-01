<div class="sf-table-wrap shadow-none">
    <div class="sf-table-scroll">
        <table class="sf-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Ver.</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($invoices as $inv)
                    <tr>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($inv->is_primary)
                                    <span class="sf-badge-green">
                                        Primary
                                    </span>
                                @endif

                                @if($inv->file_path)
                                    <a href="{{ route('admin.invoices.view', $inv) }}"
                                       target="_blank"
                                       class="font-extrabold text-white hover:text-orange-300 hover:underline">
                                        {{ $inv->number ?? basename($inv->file_path) ?? ('Invoice #'.$inv->id) }}
                                    </a>
                                @else
                                    <span class="font-extrabold text-white">
                                        {{ $inv->number ?? ('Invoice #'.$inv->id) }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <span class="font-bold text-slate-200">
                                {{ $inv->invoice_date?->toDateString() ?? '-' }}
                            </span>
                        </td>

                        <td>
                            <span class="font-extrabold text-orange-300">
                                {{ $inv->amount ? number_format((float)$inv->amount, 2).' '.$inv->currency : '-' }}
                            </span>
                        </td>

                        <td>
                            <span class="sf-badge-slate">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>

                        <td>
                            <span class="sf-badge-blue">
                                {{ ucfirst($inv->source ?? 'upload') }}
                            </span>
                        </td>

                        <td>
                            <span class="font-bold text-slate-200">
                                v{{ $inv->version ?? 1 }}
                            </span>
                        </td>

                        <td class="text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">
                                @if($inv->file_path)
                                    <a class="sf-link" href="{{ route('admin.invoices.download', $inv) }}">
                                        Download
                                    </a>

                                    <a class="sf-link" href="{{ route('admin.invoices.view', $inv) }}" target="_blank">
                                        View
                                    </a>
                                @endif

                                @if(!$inv->is_primary)
                                    <form method="POST" action="{{ route('admin.invoices.primary', $inv) }}">
                                        @csrf

                                        <button class="sf-link">
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
