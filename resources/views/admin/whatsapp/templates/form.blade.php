@php
  /** @var string $mode 'create'|'edit' */
  /** @var string $action */
  /** @var \App\Models\WhatsApp\WhatsAppTemplate|null $template */
  $isEdit = ($mode ?? '') === 'edit';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Internal Name</label>
            <input type="text" name="name" class="border rounded w-full px-3 py-2"
                   value="{{ old('name', $template->name ?? '') }}" required>
            <p class="text-xs text-gray-500 mt-1">
                Your internal key, e.g. <code>visit_handoff_v1</code>.
            </p>
            @error('name') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Provider Template (exact)</label>
            <input type="text" name="provider_template" class="border rounded w-full px-3 py-2"
                   value="{{ old('provider_template', $template->provider_template ?? '') }}">
            <p class="text-xs text-gray-500 mt-1">
                For Meta: the approved template name. For Twilio sandbox, you can leave blank.
            </p>
            @error('provider_template') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Language</label>
            <input type="text" name="language" class="border rounded w-full px-3 py-2"
                   value="{{ old('language', $template->language ?? 'en') }}" required>
            @error('language') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Category</label>
            <input type="text" name="category" class="border rounded w-full px-3 py-2"
                   value="{{ old('category', $template->category ?? '') }}">
            @error('category') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="border rounded w-full px-3 py-2">
                @foreach(['active','draft','archived'] as $opt)
                    <option value="{{ $opt }}" @selected(old('status', $template->status ?? 'active') === $opt)>{{ ucfirst($opt) }}</option>
                @endforeach
            </select>
            @error('status') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Header --}}
    <div>
        <label class="block text-sm font-medium mb-1">Header (optional)</label>
        <textarea name="header" rows="2" class="border rounded w-full px-3 py-2"
                  >{{ old('header', $template->header ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">
            You can reference variables like <code>@{{ lead_name }}</code>.
        </p>
        @error('header') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    {{-- Body --}}
    <div>
        <label class="block text-sm font-medium mb-1">Body</label>
        <textarea name="body" rows="6" class="border rounded w-full px-3 py-2" required>{{ old('body', $template->body ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">
            Supports variables such as <code>@{{ name }}</code>, <code>@{{ created_day }}</code>, etc.
        </p>
        @error('body') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    {{-- Footer --}}
    <div>
        <label class="block text-sm font-medium mb-1">Footer (optional)</label>
        <textarea name="footer" rows="2" class="border rounded w-full px-3 py-2"
                  >{{ old('footer', $template->footer ?? '') }}</textarea>
        <p class="text-xs text-gray-500 mt-1">
            Example: <code>@{{ footer_note }}</code>.
        </p>
        @error('footer') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
    </div>

    {{-- Variables (read-only view) --}}
    @php
      $vars = old('variables', $template->variables ?? []);
      if (!is_array($vars)) { $vars = []; }
    @endphp
    @if(!empty($vars))
      <div class="bg-gray-50 border rounded p-3">
        <div class="text-sm font-medium mb-2">Detected Variables</div>
        <div class="text-sm">
          @foreach($vars as $v)
            <span class="inline-block border rounded px-2 py-1 mr-2 mb-2 bg-white">@{{ {{ $v }} }}</span>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Buttons JSON (if your React editor writes here) --}}
    <input type="hidden" id="buttons-json" name="buttons"
           value='@json(old("buttons", $template->buttons ?? []))'>

    {{-- Optional: mount point for React editor --}}
    <div id="wa-template-editor" class="mt-4"></div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-black text-white px-4 py-2 rounded">
            {{ $isEdit ? 'Update Template' : 'Create Template' }}
        </button>

        @if($isEdit)
            <a href="{{ route('admin.whatsapp.templates.show', $template) }}" class="px-4 py-2 border rounded">Preview</a>
        @endif
    </div>
</form>
