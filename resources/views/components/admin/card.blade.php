@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'card shadow-sm border-0 ' . $class]) }}>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>