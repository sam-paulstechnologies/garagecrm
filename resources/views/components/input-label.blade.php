@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-[#344563]']) }}>
    {{ $value ?? $slot }}
</label>
