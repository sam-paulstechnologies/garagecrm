@php
$ai = $aiStatus ?? [
    'enabled' => false,
    'threshold' => 0.60,
    'first' => false,
    'label' => 'AI Off',
    'color' => '#9ca3af',
];
@endphp

<div class="mt-2 flex items-center gap-2">
    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
          style="background-color: {{ $ai['enabled'] ? '#ecfdf5' : '#f3f4f6' }}; color: {{ $ai['color'] }};">
        <span class="w-2 h-2 rounded-full mr-1.5"
              style="background-color: {{ $ai['color'] }}"></span>
        {{ $ai['label'] }}
    </span>

    <span class="text-xs text-gray-500">
        Thr: {{ number_format((float)($ai['threshold'] ?? 0.6), 2) }}
        <span class="mx-1 text-gray-300">•</span>
        First reply: {{ !empty($ai['first']) ? 'On' : 'Off' }}
        <span class="mx-1 text-gray-300">•</span>
        <a href="{{ route('admin.ai.edit') }}" class="underline text-blue-600">AI Settings</a>
        <span class="mx-1 text-gray-300">•</span>
        <a href="{{ route('admin.business.edit') }}" class="underline text-blue-600">Business Profile</a>
    </span>
</div>
