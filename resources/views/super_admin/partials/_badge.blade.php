@php
    $tone = $tone ?? 'slate';
    $classes = [
        'green' => 'bg-emerald-500/10 text-emerald-300 ring-emerald-400/25',
        'red' => 'bg-red-500/10 text-red-300 ring-red-400/25',
        'orange' => 'bg-orange-500/10 text-orange-300 ring-orange-400/25',
        'blue' => 'bg-blue-500/10 text-blue-300 ring-blue-400/25',
        'slate' => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
    ][$tone] ?? 'bg-slate-500/10 text-slate-300 ring-slate-400/20';
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $classes }}">
    {{ $label ?? '' }}
</span>
