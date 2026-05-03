@php
$invoices = method_exists($client, 'invoices')
    ? $client->invoices()->latest()->get()
    : collect();
@endphp

<div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-semibold">Invoices</h2>

    <a href="{{ route('admin.invoices.create', ['client_id' => $client->id]) }}"
       class="text-sm text-blue-600 underline">
        Create Invoice
    </a>
</div>

@if($invoices->isEmpty())
    <p class="text-sm text-gray-500">No invoices yet.</p>
@else
    <ul class="space-y-2 text-sm">
        @foreach($invoices as $inv)
            <li class="border rounded p-3 flex justify-between">
                <span>{{ $inv->number ?? 'Invoice #'.$inv->id }}</span>
                <span class="text-gray-500">
                    {{ optional($inv->created_at)->format('d M Y') }}
                </span>
            </li>
        @endforeach
    </ul>
@endif
