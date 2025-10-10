import React, { useMemo, useState } from 'react';
import WhatsAppPreview from '@/Components/WhatsAppPreview';

export default function TemplatePreview({ template, payload }) {
  const [json, setJson] = useState(JSON.stringify(payload ?? {}, null, 2));
  const [error, setError] = useState('');

  const variables = useMemo(() => {
    try {
      setError('');
      return json.trim() ? JSON.parse(json) : {};
    } catch (e) {
      setError(e.message);
      return {};
    }
  }, [json]);

  return (
    <div className="p-6">
      <h1 className="text-xl font-semibold">WhatsApp Template Preview</h1>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-4">
        {/* Left: Config */}
        <div className="bg-white rounded-2xl shadow p-4">
          <div className="text-sm text-gray-500">Template</div>
          <div className="font-medium">
            {template.name} ({template.language})
          </div>
          <div className="text-xs text-gray-500">
            Provider: {template.provider} • Code: {template.provider_template}
          </div>

          <div className="mt-4">
            <label className="text-sm font-medium">Variables JSON</label>
            <textarea
              className="mt-1 w-full border rounded-xl p-3 font-mono text-sm h-56"
              value={json}
              onChange={(e) => setJson(e.target.value)}
              spellCheck={false}
            />
            {error && <div className="mt-1 text-xs text-red-600">{error}</div>}
            <div className="mt-2 flex gap-2">
              <button
                onClick={() => setJson(JSON.stringify(payload ?? {}, null, 2))}
                className="px-3 py-2 rounded-lg bg-gray-900 text-white text-sm"
              >
                Reset to example
              </button>
              <button
                onClick={() => {
                  const keys = (template.body || '').match(/\{\{\s*([^}]+)\s*\}\}/g)?.map(k => k.replace(/[{}]/g,'').trim()) || [];
                  const obj = Object.fromEntries(keys.map(k => [k.replace(/^\s*|\s*$/g,''), '']));
                  setJson(JSON.stringify(obj, null, 2));
                }}
                className="px-3 py-2 rounded-lg bg-gray-200 text-sm"
              >
                Extract keys
              </button>
            </div>
          </div>
        </div>

        {/* Right: Preview */}
        <div className="bg-white rounded-2xl shadow p-4">
          <WhatsAppPreview
            template={template}
            variables={variables}
            meta={{ timeString: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
          />
        </div>
      </div>
    </div>
  );
}
