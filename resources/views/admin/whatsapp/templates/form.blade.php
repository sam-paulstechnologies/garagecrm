@php
    /** @var string $mode 'create'|'edit' */
    /** @var string $action */
    /** @var \App\Models\WhatsApp\WhatsAppTemplate|null $template */

    $isEdit = ($mode ?? '') === 'edit';

    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
    $textareaClass = $inputClass . ' min-h-[120px]';
    $selectClass = $inputClass;

    $vars = old('variables', $template->variables ?? []);
    if (!is_array($vars)) {
        $vars = [];
    }
@endphp

<style>
    html[data-theme="light"] .sf-whatsapp-template-form .bg-slate-900\/80,
    html[data-theme="light"] .sf-whatsapp-template-form .bg-slate-900,
    html[data-theme="light"] .sf-whatsapp-template-form .bg-slate-950\/35,
    html[data-theme="light"] .sf-whatsapp-template-form .bg-slate-950\/55,
    html[data-theme="light"] .sf-whatsapp-template-form .bg-slate-950\/70 {
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .border-white\/10 {
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form :where(h1, h2, h3, p, div, td, th, label, span, a, button, code).text-white,
    html[data-theme="light"] .sf-whatsapp-template-form input.text-white,
    html[data-theme="light"] .sf-whatsapp-template-form textarea.text-white,
    html[data-theme="light"] .sf-whatsapp-template-form select.text-white {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-slate-200,
    html[data-theme="light"] .sf-whatsapp-template-form .text-slate-300,
    html[data-theme="light"] .sf-whatsapp-template-form .text-slate-400 {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-slate-500 {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-blue-300 {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-green-300 {
        color: #15803d !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-purple-300 {
        color: #7e22ce !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form .text-red-200,
    html[data-theme="light"] .sf-whatsapp-template-form .text-red-300,
    html[data-theme="light"] .sf-whatsapp-template-form .text-red-400 {
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form :where(.bg-orange-500, .bg-orange-600).text-white,
    html[data-theme="light"] .sf-whatsapp-template-form :where(.bg-orange-500, .bg-orange-600) .text-white {
        color: #ffffff !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form input,
    html[data-theme="light"] .sf-whatsapp-template-form select,
    html[data-theme="light"] .sf-whatsapp-template-form textarea {
        background: #ffffff !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-whatsapp-template-form input::placeholder,
    html[data-theme="light"] .sf-whatsapp-template-form textarea::placeholder {
        color: #64748b !important;
    }
</style>

<div class="sf-whatsapp-template-form">
{{-- Errors --}}
@if($errors->any())
    <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        <div class="font-extrabold text-red-200">Please fix the following:</div>

        <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Core Template Details --}}
    <section class="{{ $cardClass }}">
        <div class="{{ $cardHeaderClass }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        Template Details
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Internal name, provider template name, language, category, and status.
                    </p>
                </div>

                <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                    {{ $isEdit ? 'Edit' : 'Create' }}
                </span>
            </div>
        </div>

        <div class="{{ $cardBodyClass }}">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                {{-- Name --}}
                <div>
                    <label class="{{ $labelClass }}">
                        Internal Name <span class="text-red-400">*</span>
                    </label>

                    <input type="text"
                           name="name"
                           class="{{ $inputClass }}"
                           value="{{ old('name', $template->name ?? '') }}"
                           placeholder="visit_handoff_v1"
                           required>

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        Your internal key, e.g. <code class="text-slate-300">visit_handoff_v1</code>.
                    </p>

                    @error('name')
                        <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Provider Template --}}
                <div>
                    <label class="{{ $labelClass }}">
                        Provider Template
                    </label>

                    <input type="text"
                           name="provider_template"
                           class="{{ $inputClass }}"
                           value="{{ old('provider_template', $template->provider_template ?? '') }}"
                           placeholder="approved_meta_template_name">

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        For Meta: approved template name. For Twilio sandbox, this can be blank.
                    </p>

                    @error('provider_template')
                        <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Language --}}
                <div>
                    <label class="{{ $labelClass }}">
                        Language <span class="text-red-400">*</span>
                    </label>

                    <input type="text"
                           name="language"
                           class="{{ $inputClass }}"
                           value="{{ old('language', $template->language ?? 'en') }}"
                           placeholder="en"
                           required>

                    @error('language')
                        <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label class="{{ $labelClass }}">
                        Category
                    </label>

                    <input type="text"
                           name="category"
                           class="{{ $inputClass }}"
                           value="{{ old('category', $template->category ?? '') }}"
                           placeholder="booking / feedback / escalation">

                    @error('category')
                        <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label class="{{ $labelClass }}">
                        Status
                    </label>

                    <select name="status" class="{{ $selectClass }}">
                        @foreach(['active','draft','archived'] as $opt)
                            <option value="{{ $opt }}" @selected(old('status', $template->status ?? 'active') === $opt)>
                                {{ ucfirst($opt) }}
                            </option>
                        @endforeach
                    </select>

                    @error('status')
                        <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </section>

    {{-- Message Content --}}
    <section class="{{ $cardClass }}">
        <div class="{{ $cardHeaderClass }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        Message Content
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Header, body, and footer shown in the WhatsApp message.
                    </p>
                </div>

                <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                    Content
                </span>
            </div>
        </div>

        <div class="{{ $cardBodyClass }} space-y-5">

            {{-- Header --}}
            <div>
                <label class="{{ $labelClass }}">
                    Header
                </label>

                <textarea name="header"
                          rows="2"
                          class="{{ $textareaClass }}"
                          placeholder="Optional header">{{ old('header', $template->header ?? '') }}</textarea>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    You can reference variables like <code class="text-slate-300">@{{ lead_name }}</code>.
                </p>

                @error('header')
                    <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                @enderror
            </div>

            {{-- Body --}}
            <div>
                <label class="{{ $labelClass }}">
                    Body <span class="text-red-400">*</span>
                </label>

                <textarea name="body"
                          rows="7"
                          class="{{ $textareaClass }}"
                          placeholder="Hi @{{ name }}, thank you for contacting us..."
                          required>{{ old('body', $template->body ?? '') }}</textarea>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Supports variables such as <code class="text-slate-300">@{{ name }}</code>, <code class="text-slate-300">@{{ created_day }}</code>, etc.
                </p>

                @error('body')
                    <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                @enderror
            </div>

            {{-- Footer --}}
            <div>
                <label class="{{ $labelClass }}">
                    Footer
                </label>

                <textarea name="footer"
                          rows="2"
                          class="{{ $textareaClass }}"
                          placeholder="Optional footer note">{{ old('footer', $template->footer ?? '') }}</textarea>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Example: <code class="text-slate-300">@{{ footer_note }}</code>.
                </p>

                @error('footer')
                    <div class="mt-2 text-sm font-semibold text-red-300">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    {{-- Detected Variables --}}
    @if(!empty($vars))
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <h2 class="text-lg font-extrabold text-white">
                    Detected Variables
                </h2>

                <p class="mt-1 text-sm font-medium text-slate-500">
                    Variables detected in the current template content.
                </p>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="flex flex-wrap gap-2">
                    @foreach($vars as $v)
                        <span class="inline-flex rounded-full border border-white/10 bg-slate-950/70 px-3 py-1.5 text-xs font-extrabold text-slate-300">
                            @{{ {{ $v }} }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- React Editor / Buttons JSON --}}
    <section class="{{ $cardClass }}">
        <div class="{{ $cardHeaderClass }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        Buttons & Advanced Editor
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Optional buttons are written to the hidden JSON field by the React editor.
                    </p>
                </div>

                <span class="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-extrabold text-purple-300 ring-1 ring-purple-400/20">
                    Advanced
                </span>
            </div>
        </div>

        <div class="{{ $cardBodyClass }}">
            <input type="hidden"
                   id="buttons-json"
                   name="buttons"
                   value='@json(old("buttons", $template->buttons ?? []))'>

            <div id="wa-template-editor" class="mt-1"></div>

            <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/55 p-4 text-sm font-medium leading-6 text-slate-500">
                If the advanced editor does not load, the template can still be saved without buttons.
            </div>
        </div>
    </section>

    {{-- Actions --}}
    <div class="flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('admin.whatsapp.templates.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
            Cancel
        </a>

        @if($isEdit)
            <a href="{{ route('admin.whatsapp.templates.show', $template) }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-green-400/30 hover:text-green-300">
                Preview
            </a>
        @endif

        <button type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-6 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
            {{ $isEdit ? 'Update Template' : 'Create Template' }}
        </button>
    </div>
</form>
</div>
