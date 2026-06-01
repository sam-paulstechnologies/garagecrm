@if($invoiceNumber || $invoiceAmount)
    <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-green-300">
            Invoice details captured
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
            Invoice No:
            <strong class="text-white">{{ $invoiceNumber ?: '-' }}</strong>,
            Amount:
            <strong class="text-white">{{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : '-' }}</strong>
        </p>
    </div>
@endif
