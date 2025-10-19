@php
    $mode = $mode ?? ($template?->id ? 'edit' : 'create');
    $action = $mode === 'edit'
        ? route('admin.whatsapp.templates.update', $template)
        : route('admin.whatsapp.templates.store');
    $vars = is_array($variables ?? null) ? $variables : [];
@endphp

@if ($errors->any())
    <div class="mb-4 p-3 rounded bg-red-50 text-red-700">
        <ul class="list-disc ml-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" id="wa-template-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- LEFT: fields --}}
        <div class="space-y-4">
            <div class="bg-white rounded shadow p-4 space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Name</label>
                        <input type="text" name="name" class="border rounded w-full px-3 py-2" required
                               value="{{ old('name', $template->name ?? '') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Language</label>
                        <input type="text" name="language" class="border rounded w-full px-3 py-2"
                               value="{{ old('language', $template->language ?? 'en') }}" placeholder="en or en_GB">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Category</label>
                        <input type="text" name="category" class="border rounded w-full px-3 py-2"
                               value="{{ old('category', $template->category ?? '') }}" placeholder="utility, marketing">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="border rounded w-full px-3 py-2">
                            @foreach(['draft','active','archived'] as $s)
                                <option value="{{ $s }}" @selected(old('status', $template->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Provider Template ID (optional)</label>
                    <input type="text" name="provider_template" class="border rounded w-full px-3 py-2"
                           value="{{ old('provider_template', $template->provider_template ?? '') }}">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Header (optional)</label>
                    <textarea name="header" rows="2" class="border rounded w-full px-3 py-2"
                              placeholder="Header">{{ old('header', $template->header ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Body</label>
                    <textarea name="body" rows="6" class="border rounded w-full px-3 py-2" required
                              placeholder="Use @{{name}} @{{date}} etc.">{{ old('body', $template->body ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Footer (optional)</label>
                    <textarea name="footer" rows="2" class="border rounded w-full px-3 py-2"
                              placeholder="Footer">{{ old('footer', $template->footer ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Variables (comma separated)</label>
                    <input type="text" name="variables_csv" class="border rounded w-full px-3 py-2"
                           value="{{ old('variables_csv', implode(',', $vars)) }}"
                           placeholder="name,date,time,garage_name,booking_ref">
                    <div class="mt-1 text-xs text-gray-600">
                        Reference variables in text as <code>@{{name}}</code>, <code>@{{date}}</code> …
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Buttons (JSON, optional)</label>
                    <textarea name="buttons" rows="3" class="border rounded w-full px-3 py-2"
                        placeholder='[{"type":"url","text":"Book Now","url":"@{{booking_link}}"},{"type":"quick_reply","text":"Call me"}]'>{{ old('buttons', isset($template->buttons) ? json_encode($template->buttons, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Provider</label>
                        <select name="provider" class="border rounded w-full px-3 py-2">
                            @foreach(['twilio','meta'] as $p)
                                <option value="{{ $p }}" @selected(old('provider', $template->provider ?? 'twilio') === $p)>{{ strtoupper($p) }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($mode === 'edit' && !empty($template->last_synced_at))
                        <div>
                            <label class="block text-sm font-medium mb-1">Last synced</label>
                            <input type="text" class="border rounded w-full px-3 py-2 bg-gray-50" readonly
                                   value="{{ $template->last_synced_at }}">
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="bg-gray-900 text-white rounded px-4 py-2">
                        {{ $mode === 'edit' ? 'Save Changes' : 'Save' }}
                    </button>

                    @if($mode === 'edit')
                        <form method="POST" action="{{ route('admin.whatsapp.templates.preview', $template) }}">
                            @csrf
                            <button type="submit" class="border rounded px-4 py-2">Live Preview</button>
                        </form>
                        <form method="POST" action="{{ route('admin.whatsapp.templates.test_send', $template) }}">
                            @csrf
                            <button type="submit" class="border rounded px-4 py-2">Test Send</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- RIGHT: WhatsApp-like Live Preview --}}
        <div class="md:sticky md:top-4 h-fit">
            <div class="rounded overflow-hidden shadow-lg border">
                {{-- WhatsApp Header --}}
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

                {{-- Chat area (subtle wallpaper) --}}
                <div class="p-4 min-h-[320px] relative overflow-y-auto"
                     style="background-color:#ECE5DD;background-image:
                        repeating-linear-gradient(45deg, rgba(0,0,0,0.02) 0, rgba(0,0,0,0.02) 2px, transparent 2px, transparent 8px),
                        repeating-linear-gradient(-45deg, rgba(0,0,0,0.02) 0, rgba(0,0,0,0.02) 2px, transparent 2px, transparent 8px);">

                    <div class="flex flex-col space-y-1">
                        {{-- header bubble --}}
                        <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-2 text-sm shadow" id="pv-header"></div>

                        {{-- body bubble --}}
                        <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-2 text-sm shadow whitespace-pre-wrap" id="pv-body"></div>

                        {{-- buttons bubble --}}
                        <div class="self-end max-w-[85%] bg-white rounded-lg px-3 py-2 text-sm shadow space-y-2 hidden" id="pv-buttons">
                            <div class="flex flex-col gap-2" id="pv-buttons-wrap"></div>
                        </div>

                        {{-- footer bubble --}}
                        <div class="self-end max-w-[85%] bg-[#DCF8C6] rounded-lg px-3 py-1 text-xs text-gray-700 shadow" id="pv-footer"></div>
                    </div>
                </div>

                {{-- Footer info (escaped with verbatim to avoid Blade parsing) --}}
                <div class="bg-white text-xs text-gray-600 px-3 py-2 border-t">
                    @verbatim
                    Variables render with sample values. Unset vars appear as <code>{{var}}</code>.
                    @endverbatim
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Live preview script --}}
<script>
(function(){
    const f = document.getElementById('wa-template-form');
    if(!f) return;

    const el = n => f.querySelector('[name="'+n+'"]');

    const pvHeader  = document.getElementById('pv-header');
    const pvBody    = document.getElementById('pv-body');
    const pvFooter  = document.getElementById('pv-footer');
    const pvProv    = document.getElementById('pv-provider');
    const pvBtnsBox = document.getElementById('pv-buttons');
    const pvBtnsWrap= document.getElementById('pv-buttons-wrap');

    // Sample values used to replace @{{var}}
    const samples = {
        name:'John Doe', date:'2025-10-12', time:'10:00 AM',
        garage_name:'GarageCRM', booking_ref:'ABC123', amount:'250.00',
        otp:'493216', service:'Periodic Service', sla_hours:'2',
        lead_ref:'L-2025-001', booking_link:'https://garagecrm.test/booking',
        contact_phone:'+971 55 123 4567', location:'Dubai'
    };

    function render(text){
        if(!text) return '';
        return text.replace(/@\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (_,v)=>{
            return (v in samples) ? samples[v] : '{{' + v + '}}';
        });
    }

    function parseButtons(){
        let raw = el('buttons')?.value || '';
        if(!raw.trim()) return [];
        try {
            const arr = JSON.parse(raw);
            return arr.map(b => ({
                type: String(b.type || '').toLowerCase(),
                text: render(String(b.text || '')),
                url : render(String(b.url  || ''))
            })).slice(0, 3);
        } catch(e){
            return [];
        }
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
            if(b.type === 'url' && b.url){
                btn.onclick = ()=>window.open(b.url, '_blank');
            }
            pvBtnsWrap.appendChild(btn);
        });
    }

    function update(){
        pvHeader.textContent = render(el('header')?.value || '');
        pvBody.textContent   = render(el('body')?.value || '');
        pvFooter.textContent = render(el('footer')?.value || '');
        pvProv.textContent   = (el('provider')?.value || 'twilio').toUpperCase();
        drawButtons(parseButtons());
    }

    ['input','change','keyup'].forEach(evt=> f.addEventListener(evt, update, true));
    update();
})();
</script>
