{{-- resources/views/admin/whatsapp/templates/show.blade.php --}}
@extends('layouts.app')

@section('title', 'WhatsApp Template: ' . $template->name)

@section('content')
@php
    $vars = is_array($template->variables) ? $template->variables : (json_decode($template->variables ?? '[]', true) ?: []);
    $buttons = is_array($template->buttons) ? $template->buttons : (json_decode($template->buttons ?? '[]', true) ?: []);

    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none';
    $textareaClass = $inputClass . ' min-h-[110px]';
@endphp

<style>
    html[data-theme="light"] .sf-whatsapp-template-show .bg-slate-900\/80,
    html[data-theme="light"] .sf-whatsapp-template-show .bg-slate-900,
    html[data-theme="light"] .sf-whatsapp-template-show .bg-slate-950\/35,
    html[data-theme="light"] .sf-whatsapp-template-show .bg-slate-950\/70 {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .border-white\/10 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show :where(h1, h2, h3, p, div, td, th, label, span, a, button).text-white,
    html[data-theme="light"] .sf-whatsapp-template-show input.text-white,
    html[data-theme="light"] .sf-whatsapp-template-show textarea.text-white {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .bg-\[\#075E54\],
    html[data-theme="light"] .sf-whatsapp-template-show .bg-\[\#075E54\] :where(div, span).text-white {
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .bg-\[\#075E54\] .text-white\/70 {
        color: rgba(255, 255, 255, 0.78) !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .text-slate-200,
    html[data-theme="light"] .sf-whatsapp-template-show .text-slate-300,
    html[data-theme="light"] .sf-whatsapp-template-show .text-slate-400 {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show :where(.bg-orange-500, .bg-orange-600).text-white,
    html[data-theme="light"] .sf-whatsapp-template-show :where(.bg-orange-500, .bg-orange-600) .text-white {
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show input,
    html[data-theme="light"] .sf-whatsapp-template-show textarea {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show input:disabled,
    html[data-theme="light"] .sf-whatsapp-template-show textarea:disabled {
        background: #f8fafc !important;
        color: #0f172a !important;
        opacity: 1 !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-show input::placeholder,
    html[data-theme="light"] .sf-whatsapp-template-show textarea::placeholder {
        color: #64748b !important;
    }
</style>

<div class="sf-whatsapp-template-show mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Template Preview
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                WhatsApp Template
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Preview and details for
                <span class="font-extrabold text-slate-200">{{ $template->name }}</span>.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.whatsapp.templates.edit', $template) }}"
               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                Edit Template
            </a>

            <a href="{{ route('admin.whatsapp.templates.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back
            </a>
        </div>
    </div>

    <form id="wa-template-form">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- LEFT: read-only details --}}
            <div class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Template Details
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Read-only template metadata and message content.
                            </p>
                        </div>

                        <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                            {{ strtoupper($template->language) }}
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }} space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Name</label>
                            <input name="name" type="text" class="{{ $inputClass }}" value="{{ $template->name }}" disabled>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Language</label>
                            <input name="language" type="text" class="{{ $inputClass }}" value="{{ $template->language }}" disabled>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Category</label>
                            <input name="category" type="text" class="{{ $inputClass }}" value="{{ $template->category }}" disabled>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Status</label>
                            <input name="status" type="text" class="{{ $inputClass }}" value="{{ $template->status }}" disabled>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Provider Template</label>
                        <input name="provider_template" type="text" class="{{ $inputClass }}" value="{{ $template->provider_template }}" disabled>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Header</label>
                        <textarea name="header" rows="2" class="{{ $textareaClass }}" disabled>{{ $template->header }}</textarea>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Body</label>
                        <textarea name="body" rows="6" class="{{ $textareaClass }}" disabled>{{ $template->body }}</textarea>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Footer</label>
                        <textarea name="footer" rows="2" class="{{ $textareaClass }}" disabled>{{ $template->footer }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Variables</label>
                            <input name="variables_csv" type="text" class="{{ $inputClass }}" value="{{ implode(',', $vars) }}" disabled>
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">Provider</label>
                            <input name="provider" type="text" class="{{ $inputClass }}" value="{{ strtoupper($template->provider ?? 'twilio') }}" disabled>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">Buttons JSON</label>
                        <textarea name="buttons" rows="6" class="{{ $textareaClass }}" disabled>@if($buttons){{ json_encode($buttons, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}@endif</textarea>
                    </div>
                </div>
            </div>

            {{-- RIGHT: WhatsApp-like Preview --}}
            <div class="lg:sticky lg:top-6 h-fit">
                <div class="{{ $cardClass }}">
                    <div class="{{ $cardHeaderClass }}">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-extrabold text-white">
                                    WhatsApp Preview
                                </h2>

                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Sample rendering with placeholder variables.
                                </p>
                            </div>

                            <span id="pv-provider" class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                TWILIO
                            </span>
                        </div>
                    </div>

                    <div class="overflow-hidden">
                        {{-- Phone Header --}}
                        <div class="bg-[#075E54] px-4 py-3 text-white">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-sm font-extrabold">
                                        SF
                                    </div>

                                    <div>
                                        <div class="text-sm font-extrabold">
                                            SayaraForce Support
                                        </div>
                                        <div class="text-xs text-white/70">
                                            online
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs text-white/70">
                                    Template
                                </div>
                            </div>
                        </div>

                        {{-- WhatsApp Body --}}
                        <div class="relative min-h-[420px] overflow-y-auto p-5"
                             style="background-color:#0f172a;background-image:
                                radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0);
                                background-size: 22px 22px;">

                            <div class="flex flex-col space-y-2">
                                <div class="self-end max-w-[85%] rounded-2xl rounded-br-sm bg-[#dcf8c6] px-4 py-2 text-sm font-semibold text-slate-900 shadow" id="pv-header"></div>

                                <div class="self-end max-w-[85%] whitespace-pre-wrap rounded-2xl rounded-br-sm bg-[#dcf8c6] px-4 py-3 text-sm font-semibold leading-6 text-slate-900 shadow" id="pv-body"></div>

                                <div class="hidden self-end max-w-[85%] rounded-2xl bg-white px-4 py-3 text-sm shadow" id="pv-buttons">
                                    <div class="flex flex-col gap-2" id="pv-buttons-wrap"></div>
                                </div>

                                <div class="self-end max-w-[85%] rounded-2xl rounded-br-sm bg-[#dcf8c6] px-4 py-2 text-xs font-semibold text-slate-700 shadow" id="pv-footer"></div>

                                <div class="self-end text-[11px] font-semibold text-slate-500">
                                    Just now ✓✓
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-white/10 bg-slate-950/70 px-4 py-3 text-xs font-medium text-slate-500">
                            @verbatim
                            Variables render with sample values. Unset vars appear as {{var}}.
                            @endverbatim
                        </div>
                    </div>
                </div>

                {{-- Quick summary --}}
                <div class="mt-6 rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-xl shadow-black/20">
                    <h3 class="text-lg font-extrabold text-white">
                        Usage Note
                    </h3>

                    <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                        This template should be mapped under Template Mappings before it is used by automated WhatsApp journeys.
                    </p>

                    @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                        <a href="{{ route('admin.whatsapp.mappings.index') }}"
                           class="mt-4 inline-flex w-full justify-center rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Open Template Mappings
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function(){
    const f = document.getElementById('wa-template-form');
    if(!f) return;

    const q = n => f.querySelector('[name="'+n+'"]');

    const pvHeader  = document.getElementById('pv-header');
    const pvBody    = document.getElementById('pv-body');
    const pvFooter  = document.getElementById('pv-footer');
    const pvProv    = document.getElementById('pv-provider');
    const pvBtnsBox = document.getElementById('pv-buttons');
    const pvBtnsWrap= document.getElementById('pv-buttons-wrap');

    const samples = {
        name:'John Doe',
        date:'2025-10-12',
        time:'10:00 AM',
        garage_name:'Garage Name',
        booking_ref:'ABC123',
        amount:'250.00',
        otp:'493216',
        service:'Periodic Service',
        sla_hours:'2',
        lead_ref:'L-2025-001',
        booking_link:'https://garagecrm.test/booking',
        contact_phone:'+971 55 123 4567',
        location:'Dubai',
        google_maps_link:'https://maps.example.com'
    };

    function render(text){
        if(!text) return '';

        let out = text.replace(/@\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function(_, v) {
            return (v in samples) ? samples[v] : '{{' + v + '}}';
        });

        out = out.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, function(_, v) {
            return (v in samples) ? samples[v] : '{{' + v + '}}';
        });

        return out;
    }

    function parseButtons(){
        let raw = q('buttons')?.value || '';

        if(!raw.trim()) {
            return [];
        }

        try {
            const arr = JSON.parse(raw);

            return arr.map(function(b) {
                return {
                    type: String(b.type || '').toLowerCase(),
                    text: render(String(b.text || '')),
                    url : render(String(b.url  || ''))
                };
            }).slice(0, 3);
        } catch(e) {
            return [];
        }
    }

    function drawButtons(btns){
        pvBtnsWrap.innerHTML = '';

        if(!btns.length) {
            pvBtnsBox.classList.add('hidden');
            return;
        }

        pvBtnsBox.classList.remove('hidden');

        btns.forEach(function(b) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-sm font-extrabold text-blue-700';
            btn.textContent = b.text || (b.type === 'url' ? 'Open Link' : 'Reply');

            if(b.type === 'url' && b.url) {
                btn.onclick = function() {
                    window.open(b.url, '_blank');
                };
            }

            pvBtnsWrap.appendChild(btn);
        });
    }

    function update(){
        pvHeader.textContent = render(q('header')?.value || '');
        pvBody.textContent   = render(q('body')?.value || '');
        pvFooter.textContent = render(q('footer')?.value || '');
        pvProv.textContent   = (q('provider')?.value || 'TWILIO').toString().toUpperCase();

        drawButtons(parseButtons());
    }

    update();
})();
</script>
@endsection
