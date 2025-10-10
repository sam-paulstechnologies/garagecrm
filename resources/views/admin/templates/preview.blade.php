@extends('layouts.app')

@section('title', 'Template Preview: '.$tpl->name)

@php
    // Fallback demo variables (only used if a key is missing)
    $sample = [
        'name' => 'Sam',
        'garage' => 'SayaraForce',
        'appointment_date' => 'Tue, 22 Oct · 10:30 AM',
        'otp' => '482913',
    ];

    // Generic renderer for {{placeholders}}
    $render = function (?string $text) use ($sample) {
        $text = $text ?? '';
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($sample) {
            $key = $m[1];
            return $sample[$key] ?? '‹'.$key.'›';
        }, $text);
    };

    // Normalize buttons into an array of ['text'=>..., 'type'=>...]
    $buttons = collect($tpl->buttons ?? [])->map(function ($b) {
        return is_array($b) ? $b : ['text' => (string)$b, 'type' => 'quick_reply'];
    });
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- LEFT: your plain preview block (kept) --}}
        <div class="lg:col-span-7">
            <div class="p-5 border rounded bg-white space-y-4">
                @if($tpl->header)
                    <h3 class="text-sm text-gray-500">Header</h3>
                    <div class="font-semibold">{!! nl2br(e($tpl->header)) !!}</div>
                @endif

                <h3 class="text-sm text-gray-500">Body</h3>
                <div class="whitespace-pre-wrap">{!! nl2br(e($tpl->body)) !!}</div>

                @if($tpl->footer)
                    <div class="pt-3 mt-3 text-xs text-gray-500 border-t">{!! nl2br(e($tpl->footer)) !!}</div>
                @endif

                @if($buttons->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach($buttons as $btn)
                            <button class="px-3 py-1.5 text-sm border rounded bg-gray-50 hover:bg-gray-100">
                                {{ $btn['text'] ?? 'Button' }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="text-right">
                    <a href="{{ route('admin.templates.preview', $tpl) }}" class="underline text-sm">Reload</a>
                </div>
            </div>
        </div>

        {{-- RIGHT: WhatsApp-like phone preview --}}
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-4">
                <div class="mx-auto w-[360px] rounded-[36px] border bg-[#d1d7db] shadow-md overflow-hidden">
                    {{-- Phone top bar --}}
                    <div class="h-6 bg-[#075E54]"></div>

                    {{-- Chat header --}}
                    <div class="flex items-center gap-3 px-4 py-3 bg-[#075E54] text-white">
                        <div class="w-8 h-8 rounded-full bg-white/20"></div>
                        <div>
                            <div class="text-sm font-semibold">{{ $sample['garage'] }}</div>
                            <div class="text-[11px] opacity-80">business account</div>
                        </div>
                        <div class="ml-auto text-xs opacity-80">now</div>
                    </div>

                    {{-- Chat area --}}
                    <div class="h-[520px] overflow-y-auto bg-[#ECE5DD] p-3 space-y-2">
                        {{-- Received spacer bubble (to look realistic) --}}
                        <div class="max-w-[75%] bg-white rounded-2xl rounded-tl-sm px-3 py-2 shadow">
                            Hi {{ $sample['name'] }}, how can I help you?
                            <div class="mt-1 text-[10px] text-gray-400 text-right">09:57</div>
                        </div>

                        {{-- Our template bubble (sender → green) --}}
                        <div class="ml-auto max-w-[80%] bg-[#D9FDD3] rounded-2xl rounded-tr-sm px-3 py-2 shadow">
                            @if($tpl->header)
                                <div class="font-semibold mb-2">{!! nl2br(e($render($tpl->header))) !!}</div>
                            @endif

                            <div class="whitespace-pre-wrap">{!! nl2br(e($render($tpl->body))) !!}</div>

                            @if($tpl->footer)
                                <div class="mt-2 text-[11px] text-gray-600">{!! nl2br(e($render($tpl->footer))) !!}</div>
                            @endif

                            {{-- Buttons (WhatsApp quick reply / CTA style) --}}
                            @if($buttons->isNotEmpty())
                                <div class="mt-3 border-t pt-2">
                                    <div class="flex flex-col gap-2">
                                        @foreach($buttons as $btn)
                                            @php $type = strtolower($btn['type'] ?? 'quick_reply'); @endphp
                                            @if($type === 'url' || $type === 'call' || $type === 'copy_code' || $type === 'cta' )
                                                <a class="w-full text-center text-sm py-2 rounded border bg-white hover:bg-gray-50">
                                                    {{ $btn['text'] ?? 'Open' }}
                                                </a>
                                            @else
                                                <button class="w-full text-center text-sm py-2 rounded border bg-white hover:bg-gray-50">
                                                    {{ $btn['text'] ?? 'Reply' }}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-1 text-[10px] text-gray-500 text-right">09:58 ✓✓</div>
                        </div>
                    </div>

                    {{-- Phone bottom bar --}}
                    <div class="h-10 bg-[#075E54]"></div>
                </div>
                <div class="text-center text-xs text-gray-500 mt-2">Preview only — not an actual WhatsApp client.</div>
            </div>
        </div>
    </div>
</div>
@endsection
