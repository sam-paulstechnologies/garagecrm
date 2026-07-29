@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-[#0D1B3D]/20 shadow-sm focus:border-[#FF6A00] focus:ring-[#FF6A00]']) }}>
