// resources/js/components/WhatsAppTemplateEditor.jsx
import React, { useState } from 'react';

export default function WhatsAppTemplateEditor({ initial = {}, onSave, onPreview }) {
  const [form, setForm] = useState({
    name: initial.name || '',
    provider_template: initial.provider_template || '',
    language: initial.language || 'en',
    category: initial.category || '',
    header: initial.header || '',
    body: initial.body || '',
    footer: initial.footer || '',
    status: initial.status || 'active',
    buttons: initial.buttons || [],
  });

  const update = (k, v) => setForm(prev => ({ ...prev, [k]: v }));

  const addButton = (type) =>
    setForm(prev => ({ ...prev, buttons: [...(prev.buttons || []), { type, text: '', url: '', phone: '' }] }));

  const updateButton = (i, k, v) => {
    const next = [...form.buttons]; next[i][k] = v; update('buttons', next);
  };

  const removeButton = (i) => {
    const next = [...form.buttons]; next.splice(i, 1); update('buttons', next);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
      {/* Left: Form */}
      <div className="space-y-4">
        <div className="grid grid-cols-2 gap-3">
          <Field label="Name">
            <input className="w-full border rounded px-3 py-2"
                   value={form.name} onChange={e => update('name', e.target.value)} />
          </Field>
          <Field label="Provider Template">
            <input className="w-full border rounded px-3 py-2"
                   value={form.provider_template} onChange={e => update('provider_template', e.target.value)} />
          </Field>
          <Field label="Language">
            <input className="w-full border rounded px-3 py-2"
                   value={form.language} onChange={e => update('language', e.target.value)} />
          </Field>
          <Field label="Category">
            <input className="w-full border rounded px-3 py-2"
                   value={form.category} onChange={e => update('category', e.target.value)} />
          </Field>
        </div>

        <Field label="Header (optional)">
          <textarea rows={2} className="w-full border rounded px-3 py-2"
                    value={form.header} onChange={e => update('header', e.target.value)} />
        </Field>

        <Field label="Body *">
          <textarea rows={6} className="w-full border rounded px-3 py-2"
                    value={form.body} onChange={e => update('body', e.target.value)} />
        </Field>

        <Field label="Footer (optional)">
          <textarea rows={2} className="w-full border rounded px-3 py-2"
                    value={form.footer} onChange={e => update('footer', e.target.value)} />
        </Field>

        <Field label="Status">
          <select className="w-full border rounded px-3 py-2"
                  value={form.status} onChange={e => update('status', e.target.value)}>
            {['active', 'draft', 'archived'].map(s => <option key={s} value={s}>{s}</option>)}
          </select>
        </Field>

        <div className="p-3 border rounded">
          <div className="flex items-center justify-between mb-2">
            <div className="font-medium">Buttons</div>
            <div className="space-x-2">
              <Btn onClick={() => addButton('quick_reply')}>+ Quick Reply</Btn>
              <Btn onClick={() => addButton('url')}>+ URL</Btn>
              <Btn onClick={() => addButton('phone')}>+ Phone</Btn>
            </div>
          </div>

          <div className="space-y-2">
            {(form.buttons || []).map((b, i) => (
              <div key={i} className="flex flex-wrap items-center gap-2">
                <span className="text-xs px-2 py-1 rounded bg-gray-100">{b.type}</span>
                <input className="border rounded px-2 py-1" placeholder="Button text"
                       value={b.text || ''} onChange={e => updateButton(i, 'text', e.target.value)} />
                {b.type === 'url' && (
                  <input className="border rounded px-2 py-1 w-64" placeholder="https://"
                         value={b.url || ''} onChange={e => updateButton(i, 'url', e.target.value)} />
                )}
                {b.type === 'phone' && (
                  <input className="border rounded px-2 py-1 w-48" placeholder="+9715…"
                         value={b.phone || ''} onChange={e => updateButton(i, 'phone', e.target.value)} />
                )}
                <button type="button" className="text-red-600" onClick={() => removeButton(i)}>Remove</button>
              </div>
            ))}
          </div>
        </div>

        <div className="flex gap-2">
          <button type="button" className="px-4 py-2 rounded bg-indigo-600 text-white"
                  onClick={() => onSave?.(form)}>Save</button>
          <button type="button" className="px-3 py-2 rounded border"
                  onClick={() => onPreview?.(form)}>Preview via API</button>
        </div>
      </div>

      {/* Right: WhatsApp-like Preview */}
      <div className="flex justify-center">
        <PhoneFrame>
          <ChatHeader title="WhatsApp" subtitle="Preview" />
          <div className="flex-1 overflow-y-auto p-3 bg-[rgb(230,244,234)]">
            <BubbleIncoming>
              {form.header && <div className="font-semibold mb-1">{form.header}</div>}
              <div className="whitespace-pre-wrap">{form.body || 'Your message will appear here…'}</div>
              {form.footer && <div className="text-xs text-gray-600 mt-2">{form.footer}</div>}
            </BubbleIncoming>

            {form.buttons?.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {form.buttons.map((b, idx) => (
                  <button key={idx} className="px-3 py-1 rounded-full border bg-white text-sm">
                    {b.text || (b.type === 'url' ? 'Open link' : b.type === 'phone' ? 'Call' : 'Reply')}
                  </button>
                ))}
              </div>
            )}
          </div>
          <ChatInput placeholder="Type a message" />
        </PhoneFrame>
      </div>
    </div>
  );
}

function Field({ label, children }) {
  return (
    <label className="block">
      <span className="block text-sm font-medium mb-1">{label}</span>
      {children}
    </label>
  );
}
function Btn({ children, onClick }) {
  return <button type="button" onClick={onClick} className="px-2 py-1 border rounded">{children}</button>;
}

function PhoneFrame({ children }) {
  return (
    <div className="w-[320px] h-[640px] bg-black rounded-[36px] p-2 shadow-xl">
      <div className="bg-white rounded-[30px] h-full flex flex-col overflow-hidden">
        {children}
      </div>
    </div>
  );
}
function ChatHeader({ title, subtitle }) {
  return (
    <div className="px-3 py-2 bg-emerald-600 text-white">
      <div className="text-sm font-semibold">{title}</div>
      <div className="text-[11px] opacity-90">{subtitle}</div>
    </div>
  );
}
function BubbleIncoming({ children }) {
  return (
    <div className="max-w-[85%] bg-white rounded-2xl px-3 py-2 shadow mb-2">
      {children}
    </div>
  );
}
function ChatInput({ placeholder }) {
  return (
    <div className="p-2 bg-white border-t flex items-center gap-2">
      <input className="flex-1 border rounded-full px-3 py-2 text-sm" placeholder={placeholder} readOnly />
      <div className="w-8 h-8 rounded-full bg-emerald-600" />
    </div>
  );
}
