{{-- resources/views/admin/whatsapp/templates/show.blade.php --}}
@extends('layouts.app')

@section('title', 'WhatsApp Template: ' . $template->name)

@section('content')
@php
    $vars = is_array($template->variables) ? $template->variables : (json_decode($template->variables ?? '[]', true) ?: []);
    $buttons = is_array($template->buttons) ? $template->buttons : (json_decode($template->buttons ?? '[]', true) ?: []);
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">WhatsApp Template</h1>
            <p class="text-sm text-gray-500">Preview & details for <span class="font-medium">{{ $template->name }}</span></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.whatsapp.templates.edit', $template) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">Edit</a>
            <a href="{{ route('admin.whatsapp.templates.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Back</a>
        </div>
    </div>

    <form id="wa-template-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- LEFT: read-only details --}}
            <div class="bg-white rounded shadow p-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input name="name" type="text" class="border rounded w-full px-3 py-2 bg-gray-50" value="{{ $template->name }}" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Language</label>
                        <input name="language" type="text" class="border rounded w-full px-3 py-2 bg-gray-50" value="{{ $template->language }}" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <input name="category" type="text" class="border rounded w-full px-3 py-2 bg-gray-50" value="{{ $template->category }}" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <input name="status" type="text" class="border rounded w-full px-3 py-2 bg-gray-50" value="{{ $template->status }}" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Provider Template</label>
                    <input name="provider_template" type="text" class="border rounded w-full px-3 py-2 bg-gray-50" value="{{ $template->provider_template }}" disabled>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Header</label>
                    <textarea name="header" rows="2" class="border rounded w-full px-3 py-2 bg-gray-50" disabled>{{ $template->header }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Body</label>
                    <textarea name="body" rows="6" class="border rounded w-full px-3 py-2 bg-gray-50" disabled>{{ $template->body }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Footer</label>
                    <textarea name="footer" rows="2" class="border rounded w-full px-3 py-2 bg-gray-50" disabled>{{ $template->footer }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Variables</label>
                        <input name="variables_csv" type="text" class="border rounded w-full px-3 py-2 bg-gray-50"
                               value="{{ implode(',', $vars) }}" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Provider</label>
                        <input name="provider" type="text" class="border rounded w-full px-3 py-2 bg-gray-50"
                               value="{{ strtoupper($template->provider ?? 'twilio') }}" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Buttons (JSON)</label>
                    <textarea name="buttons" rows="6" class="border rounded w-full px-3 py-2 bg-gray-50" disabled>@if($buttons){{ json_encode($buttons, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}@endif</textarea>
                </div>
            </div>

            {{-- RIGHT: WhatsApp-like Preview (read-only render) --}}
            <div class="md:sticky md:top-4 h-fit">
                <div class="rounded overflow-hidden shadow-lg border">
                    <div class="bg-[#075E54] text-white px-4 py-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-gray-300"></div>
                            <div>
                                <div class="text-sm font-semibold">GarageCRM Support</div>
                                <div class="text-xs text-gray-200">online</div>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase" id="pv-provider">TWILIO</span>
                    </div>

                    <div class="p-4 min-h-[320px] relative overflow-y-auto"
                         style="background-color:#ECE5DD;background-image:
                            repeating-linear-gradient(45deg, rgba(0,0,0,0.02) 0, rgba(0,0,0,0.02) 2px, transparent 2px, transparent 8px),
                            repeating-linear-gradient(-45deg, rgba(0,0,0,0.02) 0, rgba(0,0,0,0.02) 2px, transparent 2px, transparent 8px);">

                        <div class="flex flex-col space-y-1">
                            <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-2 text-sm shadow" id="pv-header"></div>
                            <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-2 text-sm shadow whitespace-pre-wrap" id="pv-body"></div>

                            <div class="self-end max-w-[85%] bg-white rounded-lg px-3 py-2 text-sm shadow space-y-2 hidden" id="pv-buttons">
                                <div class="flex flex-col gap-2" id="pv-buttons-wrap"></div>
                            </div>

                            <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-1 text-xs text-gray-700 shadow" id="pv-footer"></div>
                        </div>
                    </div>

                    <div class="bg-white text-xs text-gray-600 px-3 py-2 border-t">
                        @verbatim
                        Variables render with sample values. Unset vars appear as <code>{{var}}</code>.
                        @endverbatim
                    </div>
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
        name:'John Doe', date:'2025-10-12', time:'10:00 AM',
        garage_name:'Garage Name', booking_ref:'ABC123', amount:'250.00',
        otp:'493216', service:'Periodic Service', sla_hours:'2',
        lead_ref:'L-2025-001', booking_link:'https://garagecrm.test/booking',
        contact_phone:'+971 55 123 4567', location:'Dubai', google_maps_link:'https://maps.example.com'
    };

    function render(text){
        if(!text) return '';
        let out = text.replace(/@\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g,(_,v)=> (v in samples)?samples[v]:'{{'+v+'}}');
        out = out.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g,(_,v)=> (v in samples)?samples[v]:'{{'+v+'}}');
        return out;
    }

    function parseButtons(){
        let raw = q('buttons')?.value || '';
        if(!raw.trim()) return [];
        try {
            const arr = JSON.parse(raw);
            return arr.map(b => ({
                type: String(b.type || '').toLowerCase(),
                text: render(String(b.text || '')),
                url : render(String(b.url  || ''))
            })).slice(0,3);
        } catch(e){ return []; }
    }

    function drawButtons(btns){
        pvBtnsWrap.innerHTML = '';
        if(!btns.length){ pvBtnsBox.classList.add('hidden'); return; }
        pvBtnsBox.classList.remove('hidden');
        btns.forEach(b=>{
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full text-center border rounded px-3 py-2 text-sm';
            btn.textContent = b.text || (b.type==='url' ? 'Open Link' : 'Reply');
            if(b.type==='url' && b.url){ btn.onclick = ()=>window.open(b.url,'_blank'); }
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

    update(); // one-time for read-only
})();
</script>
@endsection
