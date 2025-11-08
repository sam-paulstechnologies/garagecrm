import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

type Initial = {
  enabled: boolean;
  confidence_threshold: number;
  first_reply: boolean;
  intent_handle: string;
  intent_handoff: string;
  intent_forbidden: string;
  policy_text: string;
};

export default function ControlCenter({ initial }: { initial: Initial }) {
  const { props } = usePage<any>();
  const flash = props.flash || {};
  const [form, setForm] = useState<Initial>(initial);
  const [busy, setBusy] = useState(false);

  const save = (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    router.post(route('admin.ai.update'), {
      enabled: form.enabled ? 1 : 0,
      confidence_threshold: Number(form.confidence_threshold),
      first_reply: form.first_reply ? 1 : 0,
      intent_handle: form.intent_handle,
      intent_handoff: form.intent_handoff,
      intent_forbidden: form.intent_forbidden,
      policy_text: form.policy_text,
    }, {
      onFinish: () => setBusy(false)
    });
  };

  const Input = (p: any) => <input {...p} className={"w-full rounded border px-3 py-2 " + (p.className||'')} />;

  return (
    <div className="max-w-3xl mx-auto px-6 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-semibold">AI Control Center</h1>
        <a href={route('admin.dashboard')} className="text-sm underline">← Back</a>
      </div>

      {flash?.success && <div className="mb-4 bg-green-50 border border-green-100 text-green-700 p-3 rounded">{flash.success}</div>}

      <form onSubmit={save} className="space-y-8">
        <section className="bg-white rounded shadow p-5 space-y-4">
          <h2 className="font-semibold">Core</h2>
          <label className="flex items-center gap-3">
            <input type="checkbox" checked={form.enabled} onChange={e=>setForm({...form, enabled:e.target.checked})} />
            <span>Enable AI</span>
          </label>
          <div>
            <label className="block text-sm mb-1">Confidence Threshold (0–1)</label>
            <Input type="number" step="0.01" min={0} max={1}
              value={form.confidence_threshold}
              onChange={(e:any)=>setForm({...form, confidence_threshold: parseFloat(e.target.value || '0')})}/>
          </div>
          <label className="flex items-center gap-3">
            <input type="checkbox" checked={form.first_reply} onChange={e=>setForm({...form, first_reply:e.target.checked})}/>
            <span>AI first reply</span>
          </label>
        </section>

        <section className="bg-white rounded shadow p-5 space-y-3">
          <h2 className="font-semibold">Intent Matrix (CSV lists)</h2>
          <div>
            <label className="block text-sm mb-1">Handle</label>
            <Input value={form.intent_handle} onChange={(e:any)=>setForm({...form, intent_handle: e.target.value})}/>
            <p className="text-xs text-gray-500 mt-1">e.g., <code>greeting,price,service_info</code></p>
          </div>
          <div>
            <label className="block text-sm mb-1">Handoff</label>
            <Input value={form.intent_handoff} onChange={(e:any)=>setForm({...form, intent_handoff: e.target.value})}/>
          </div>
          <div>
            <label className="block text-sm mb-1">Forbidden</label>
            <Input value={form.intent_forbidden} onChange={(e:any)=>setForm({...form, intent_forbidden: e.target.value})}/>
          </div>
          <div>
            <label className="block text-sm mb-1">Policy Reply (shown for forbidden topics)</label>
            <textarea value={form.policy_text} onChange={e=>setForm({...form, policy_text: e.target.value})}
                      className="w-full rounded border px-3 py-2 min-h-[100px]" />
          </div>
        </section>

        <div>
          <button disabled={busy} className="bg-black text-white rounded px-4 py-2 disabled:opacity-50">
            {busy ? 'Saving…' : 'Save Settings'}
          </button>
        </div>
      </form>
    </div>
  );
}
