@php($tpl = $template ?? null)
@csrf

@if ($errors->any())
  <div class="mb-4 p-3 rounded bg-red-50 text-red-800">
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm font-medium mb-1">Name</label>
    <input name="name" value="{{ old('name', $tpl->name ?? '') }}" class="w-full border rounded-md px-3 py-2" required>
  </div>

  <div>
    <label class="block text-sm font-medium mb-1">Provider Template</label>
    <input name="provider_template" value="{{ old('provider_template', $tpl->provider_template ?? '') }}" class="w-full border rounded-md px-3 py-2" required>
  </div>

  <div>
    <label class="block text-sm font-medium mb-1">Language</label>
    <input name="language" value="{{ old('language', $tpl->language ?? 'en') }}" class="w-full border rounded-md px-3 py-2" required>
  </div>

  <div>
    <label class="block text-sm font-medium mb-1">Category</label>
    <input name="category" value="{{ old('category', $tpl->category ?? '') }}" class="w-full border rounded-md px-3 py-2">
  </div>

  {{-- Header --}}
  <div class="md:col-span-2">
    <label class="block text-sm font-medium mb-1">Header (optional)</label>
    <textarea
      name="header"
      rows="2"
      class="w-full border rounded-md px-3 py-2"
      placeholder="e.g., Hi @{{name}}"
    >{{ old('header', $tpl->header ?? '') }}</textarea>
  </div>

  {{-- Body --}}
  <div class="md:col-span-2">
    <label class="block text-sm font-medium mb-1">Body *</label>
    <textarea
      name="body"
      rows="6"
      class="w-full border rounded-md px-3 py-2"
      placeholder="Use @{{var}} placeholders"
      required
    >{{ old('body', $tpl->body ?? '') }}</textarea>
  </div>

  {{-- Footer --}}
  <div class="md:col-span-2">
    <label class="block text-sm font-medium mb-1">Footer (optional)</label>
    <textarea
      name="footer"
      rows="2"
      class="w-full border rounded-md px-3 py-2"
    >{{ old('footer', $tpl->footer ?? '') }}</textarea>
  </div>

  {{-- Status --}}
  <div>
    <label class="block text-sm font-medium mb-1">Status</label>
    <select name="status" class="w-full border rounded-md px-3 py-2">
      @foreach(['active','draft','archived'] as $s)
        <option value="{{ $s }}" @selected(old('status', $tpl->status ?? 'active') === $s)>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
  </div>
</div>

{{-- Buttons builder --}}
<div class="mt-6 p-4 border rounded-md">
  <div class="font-medium mb-2">Buttons (optional)</div>
  <div id="btnList" class="space-y-2"></div>

  <div class="mt-2">
    <button type="button" onclick="addBtn('quick_reply')" class="px-3 py-2 border rounded">+ Quick Reply</button>
    <button type="button" onclick="addBtn('url')" class="px-3 py-2 border rounded ml-2">+ URL</button>
    <button type="button" onclick="addBtn('phone')" class="px-3 py-2 border rounded ml-2">+ Phone</button>
  </div>

  <input type="hidden" name="buttons" id="buttonsField" value='@json(old("buttons", $tpl->buttons ?? []))'>
  <p class="text-xs text-gray-500 mt-2">Note: Button delivery depends on your WhatsApp BSP/Twilio template support.</p>
</div>

<div class="mt-4">
  <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
  @if(isset($tpl))
    <button type="button" onclick="previewTpl()" class="ml-2 px-3 py-2 border rounded-md">Preview</button>
  @endif
</div>

<div id="preview" class="mt-4 hidden">
  <div class="p-4 border rounded-md bg-gray-50">
    <div class="text-xs text-gray-500 mb-2">Preview</div>
    <div id="previewHeader" class="font-semibold"></div>
    <div id="previewBody" class="whitespace-pre-wrap my-2"></div>
    <div id="previewFooter" class="text-gray-600 text-sm"></div>
  </div>
</div>

@push('scripts')
<script>
// --- safe helpers ---
function esc(s){ return String(s ?? '')
  .replace(/&/g,'&amp;').replace(/</g,'&lt;')
  .replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

let buttons = [];
try {
  const raw = document.getElementById('buttonsField').value;
  buttons = raw ? JSON.parse(raw) : [];
  if (!Array.isArray(buttons)) buttons = [];
} catch(e){ buttons = []; }

function addBtn(type){ buttons.push({type, text:'', url:'', phone:''}); renderBtns(); }
function removeBtn(i){ buttons.splice(i,1); renderBtns(); }
function updateBtn(i, key, val){
  buttons[i][key] = val;
  document.getElementById('buttonsField').value = JSON.stringify(buttons);
}

function renderBtns(){
  const root = document.getElementById('btnList');
  root.innerHTML = '';
  buttons.forEach((b,i)=>{
    const row = document.createElement('div');
    row.className = 'flex flex-wrap items-center gap-2';

    const typeBadge = document.createElement('span');
    typeBadge.className = 'text-xs px-2 py-1 rounded bg-gray-100';
    typeBadge.textContent = b.type;

    const textInput = document.createElement('input');
    textInput.className = 'border rounded px-2 py-1';
    textInput.placeholder = 'Button text';
    textInput.value = b.text || '';
    textInput.oninput = (e)=> updateBtn(i,'text',e.target.value);

    row.appendChild(typeBadge);
    row.appendChild(textInput);

    if (b.type === 'url') {
      const urlInput = document.createElement('input');
      urlInput.className = 'border rounded px-2 py-1 w-80';
      urlInput.placeholder = 'https://';
      urlInput.value = b.url || '';
      urlInput.oninput = (e)=> updateBtn(i,'url',e.target.value);
      row.appendChild(urlInput);
    }

    if (b.type === 'phone') {
      const phInput = document.createElement('input');
      phInput.className = 'border rounded px-2 py-1 w-56';
      phInput.placeholder = '+9715…';
      phInput.value = b.phone || '';
      phInput.oninput = (e)=> updateBtn(i,'phone',e.target.value);
      row.appendChild(phInput);
    }

    const del = document.createElement('button');
    del.type = 'button';
    del.className = 'text-red-600';
    del.textContent = 'Remove';
    del.onclick = ()=> removeBtn(i);
    row.appendChild(del);

    root.appendChild(row);
  });

  document.getElementById('buttonsField').value = JSON.stringify(buttons);
}
renderBtns();

function previewTpl(){
  const url = "{{ isset($tpl) ? route('admin.whatsapp.templates.preview', $tpl) : '' }}";
  if (!url) { alert('Save once before preview.'); return; }

  fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json'
    }
  })
  .then(r => r.ok ? r.json() : Promise.reject())
  .then(j => {
    document.getElementById('previewHeader').textContent = j.header || '';
    document.getElementById('previewBody').textContent   = j.body || '';
    document.getElementById('previewFooter').textContent = j.footer || '';
    document.getElementById('preview').classList.remove('hidden');
  })
  .catch(()=> alert('Preview failed'));
}
</script>
@endpush
