import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

type Initial = {
  manager_phone: string;
  location: string;
  work_hours: string;
  holidays: string;
  esc_low_confidence: boolean;
  esc_negative_sentiment: boolean;
  esc_timeout_enabled: boolean;
  esc_timeout_minutes: number;
};

export default function BusinessEdit({ initial }: { initial: Initial }) {
  const { props } = usePage<any>();
  const flash = props.flash || {};
  const [f, setF] = useState<Initial>(initial);
  const [busy, setBusy] = useState(false);

  const save = (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    router.post(route('admin.business.update'), {
      ...f,
      esc_low_confidence: f.esc_low_confidence ? 1 : 0,
      esc_negative_sentiment: f.esc_negative_sentiment ? 1 : 0,
      esc_timeout_enabled: f.esc_timeout_enabled ? 1 : 0,
      esc_timeout_minutes: Number(f.esc_timeout_minutes)
    }, { onFinish: () => setBusy(false) });
  };

  const Input = (p:any) => <input {...p} className={"w-full rounded border px-3 py-2 " + (p.className||'')} />;

  return (
    <div className="max-w-3xl mx-auto px-6 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-semibold">Business Profile & Escalation</h1>
        <a href={route('admin.dashboard')} className="text-sm underline">← Back</a>
      </div>

      {flash?.success && <div className="mb-4 bg-green-50 border border-green-100 text-green-700 p-3 rounded">{flash.success}</div>}

      <form onSubmit={save} className="space-y-8">
        <section className="bg-white rounded shadow p-5 space-y-3">
          <h2 className="font-semibold">Profile</h2>
          <div>
            <label className="block text-sm mb-1">Manager Phone (E.164)</label>
            <Input value={f.manager_phone} onChange={(e:any)=>setF({...f, manager_phone:e.target.value})}/>
          </div>
          <div>
            <label className="block text-sm mb-1">Location</label>
            <Input value={f.location} onChange={(e:any)=>setF({...f, location:e.target.value})}/>
          </div>
          <div>
            <label className="block text-sm mb-1">Work Hours</label>
            <Input value={f.work_hours} onChange={(e:any)=>setF({...f, work_hours:e.target.value})}/>
          </div>
          <div>
            <label className="block text-sm mb-1">Holidays (comma separated)</label>
            <textarea className="w-full rounded border px-3 py-2 min-h-[80px]"
              value={f.holidays} onChange={e=>setF({...f, holidays:e.target.value})}/>
          </div>
        </section>

        <section className="bg-white rounded shadow p-5 space-y-3">
          <h2 className="font-semibold">Escalation Rules</h2>
          <label className="flex items-center gap-3">
            <input type="checkbox" checked={f.esc_low_confidence} onChange={e=>setF({...f, esc_low_confidence:e.target.checked})}/>
            <span>Handoff on low confidence</span>
          </label>
          <label className="flex items-center gap-3">
            <input type="checkbox" checked={f.esc_negative_sentiment} onChange={e=>setF({...f, esc_negative_sentiment:e.target.checked})}/>
            <span>Handoff on negative sentiment</span>
          </label>
          <label className="flex items-center gap-3">
            <input type="checkbox" checked={f.esc_timeout_enabled} onChange={e=>setF({...f, esc_timeout_enabled:e.target.checked})}/>
            <span>Handoff on no-reply timeout</span>
          </label>
          <div>
            <label className="block text-sm mb-1">Timeout minutes</label>
            <Input type="number" min={10} max={1440}
              value={f.esc_timeout_minutes}
              onChange={(e:any)=>setF({...f, esc_timeout_minutes:Number(e.target.value||'0')})}/>
          </div>
        </section>

        <div>
          <button disabled={busy} className="bg-black text-white rounded px-4 py-2 disabled:opacity-50">
            {busy ? 'Saving…' : 'Save'}
          </button>
        </div>
      </form>
    </div>
  );
}
