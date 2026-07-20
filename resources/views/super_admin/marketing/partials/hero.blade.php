<div class="sa-card mb-6 rounded-3xl p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-300">Platform Marketing</p>
            <h1 class="mt-2 text-3xl font-black">{{ $title }}</h1>
            <p class="sa-muted mt-2 max-w-3xl text-sm">{{ $subtitle }}</p>
        </div>
        @isset($action)
            <div>{{ $action }}</div>
        @endisset
    </div>
</div>
