import React, { useMemo, useState } from "react";
import { useForm, usePage, router } from "@inertiajs/react";

export default function AiSettings({ initial }) {
  const { props } = usePage();
  const flash = props?.flash || {};
  const [tab, setTab] = useState("policy");

  const form = useForm({
    // policy
    enabled: initial.enabled ?? false,
    confidence_threshold: initial.confidence_threshold ?? 0.6,
    first_reply: initial.first_reply ?? false,
    intent_handle: initial.intent_handle ?? "",
    intent_handoff: initial.intent_handoff ?? "",
    intent_forbidden: initial.intent_forbidden ?? "",
    forbidden_topics: initial.forbidden_topics ?? "",
    policy_reply: initial.policy_reply ?? "",
    // business
    manager_phone: initial.manager_phone ?? "",
    work_hours: initial.work_hours ?? "",
    holidays: initial.holidays ?? "[]",
    location: initial.location ?? "",
    location_coords: initial.location_coords ?? "",
    // escalations
    esc_low_confidence: initial.esc_low_confidence ?? true,
    esc_sentiment: initial.esc_sentiment ?? true,
    esc_timeout_minutes: initial.esc_timeout_minutes ?? 120,
  });

  const onSubmit = (e) => {
    e.preventDefault();
    form.post(route("admin.ai.update"), { preserveScroll: true });
  };

  const badge = useMemo(() => {
    return form.data.enabled ? (
      <span className="ml-2 rounded-full bg-green-100 text-green-700 px-2 py-0.5 text-xs">AI On</span>
    ) : (
      <span className="ml-2 rounded-full bg-gray-200 text-gray-700 px-2 py-0.5 text-xs">AI Off</span>
    );
  }, [form.data.enabled]);

  return (
    <div className="min-h-screen px-6 py-8">
      <div className="flex items-center justify-between border-b pb-4 mb-6">
        <h1 className="text-2xl font-semibold">AI Control Center {badge}</h1>
        <a href={route("admin.dashboard")} className="text-sm underline opacity-70 hover:opacity-100">
          ← Back to Dashboard
        </a>
      </div>

      {flash?.success && (
        <div className="mb-4 rounded-md bg-green-50 p-3 text-green-700 text-sm">{flash.success}</div>
      )}
      {Object.keys(form.errors).length > 0 && (
        <div className="mb-4 rounded-md bg-red-50 p-3 text-red-700 text-sm">
          {Object.values(form.errors).join(", ")}
        </div>
      )}

      <form onSubmit={onSubmit} className="space-y-8">
        {/* tabs */}
        <div className="border-b border-gray-200 -mb-px">
          <nav className="flex space-x-6">
            {[
              ["policy", "Policy & Permissions"],
              ["business", "Business Profile & Escalations"],
            ].map(([key, label]) => (
              <button
                key={key}
                type="button"
                onClick={() => setTab(key)}
                className={`whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium ${
                  tab === key ? "border-indigo-500 text-indigo-600" : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                }`}
              >
                {label}
              </button>
            ))}
          </nav>
        </div>

        {tab === "policy" ? <PolicyTab form={form} /> : <BusinessTab form={form} />}

        <div className="pt-2">
          <button type="submit" disabled={form.processing} className="rounded-lg px-4 py-2 bg-black text-white disabled:opacity-50">
            {form.processing ? "Saving…" : "Save Settings"}
          </button>
        </div>
      </form>

      <div className="mt-10 text-xs opacity-60">
        <p><strong>Tip:</strong> You can still override defaults via <code>.env</code>:</p>
        <pre className="mt-2 rounded bg-gray-100 p-3 overflow-x-auto">
AI_CONFIDENCE_THRESHOLD=0.60
AI_FIRST_REPLY=true
        </pre>
        <p className="mt-2">Database values (per company) take priority over env.</p>
      </div>
    </div>
  );
}

function Section({ title, subtitle, children }) {
  return (
    <section className="rounded-xl border border-gray-200 p-5">
      <h2 className="text-lg font-semibold">{title}</h2>
      {subtitle && <p className="text-sm text-gray-500 mt-1">{subtitle}</p>}
      <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">{children}</div>
    </section>
  );
}

function Field({ label, help, children }) {
  return (
    <div className="space-y-1">
      <label className="block text-sm font-medium">{label}</label>
      {children}
      {help && <p className="text-xs text-gray-500">{help}</p>}
    </div>
  );
}

function PolicyTab({ form }) {
  return (
    <div className="space-y-6">
      <Section title="Core Policy" subtitle="Toggle AI, set confidence, and first reply permission.">
        <Field label="Enable AI">
          <div className="flex items-center gap-2">
            <input type="checkbox" checked={!!form.data.enabled} onChange={(e)=>form.setData("enabled", e.target.checked)} />
            <span className="text-sm text-gray-700">Turn on AI responses</span>
          </div>
        </Field>

        <Field label={`Confidence Threshold (${form.data.confidence_threshold})`} help="0.0 risky — 1.0 safe. 0.60–0.75 is typical.">
          <input type="range" min="0" max="1" step="0.01" value={form.data.confidence_threshold}
                 onChange={(e)=>form.setData("confidence_threshold", parseFloat(e.target.value))}
                 className="w-full" />
        </Field>

        <Field label="AI Can Send First Reply">
          <div className="flex items-center gap-2">
            <input type="checkbox" checked={!!form.data.first_reply} onChange={(e)=>form.setData("first_reply", e.target.checked)} />
            <span className="text-sm text-gray-700">Allow AI to greet/respond first when safe</span>
          </div>
        </Field>

        <Field label="Policy Reply (used for forbidden topics)">
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.policy_reply} onChange={(e)=>form.setData("policy_reply", e.target.value)} />
        </Field>
      </Section>

      <Section title="Intent Matrix" subtitle="What AI should handle, hand off, or forbid.">
        <Field label="Handle (comma/newline)" help="e.g., greeting, price, service_info">
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.intent_handle} onChange={(e)=>form.setData("intent_handle", e.target.value)} />
        </Field>
        <Field label="Handoff to Manager (comma/newline)" help="e.g., booking_change, complex_quote">
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.intent_handoff} onChange={(e)=>form.setData("intent_handoff", e.target.value)} />
        </Field>
        <Field label="Forbidden Intents (comma/newline)" help="e.g., payments, personal_data">
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.intent_forbidden} onChange={(e)=>form.setData("intent_forbidden", e.target.value)} />
        </Field>
        <Field label="Forbidden Topics (comma/newline)" help='Examples: "Card details, PIN, OTP"'>
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.forbidden_topics} onChange={(e)=>form.setData("forbidden_topics", e.target.value)} />
        </Field>
      </Section>
    </div>
  );
}

function BusinessTab({ form }) {
  return (
    <div className="space-y-6">
      <Section title="Business Profile" subtitle="Context the AI can use in replies.">
        <Field label="Manager Phone (WhatsApp)">
          <input className="w-full rounded-md border px-3 py-2"
                 value={form.data.manager_phone} onChange={(e)=>form.setData("manager_phone", e.target.value)}
                 placeholder="+9715XXXXXXXX" />
        </Field>
        <Field label="Work Hours">
          <input className="w-full rounded-md border px-3 py-2"
                 value={form.data.work_hours} onChange={(e)=>form.setData("work_hours", e.target.value)}
                 placeholder="Mon–Sat 09:00–18:00" />
        </Field>
        <Field label='Holidays (JSON array of "YYYY-MM-DD")' help='Example: ["2025-12-25","2026-01-01"]'>
          <textarea className="w-full rounded-md border-gray-300" rows={3}
                    value={form.data.holidays} onChange={(e)=>form.setData("holidays", e.target.value)} />
        </Field>
        <Field label="Location (address)">
          <input className="w-full rounded-md border px-3 py-2"
                 value={form.data.location} onChange={(e)=>form.setData("location", e.target.value)}
                 placeholder="Street, Area, City" />
        </Field>
        <Field label="Location Coords (lat,lng)">
          <input className="w-full rounded-md border px-3 py-2"
                 value={form.data.location_coords} onChange={(e)=>form.setData("location_coords", e.target.value)}
                 placeholder="25.2048,55.2708" />
        </Field>
      </Section>

      <Section title="Escalation Rules" subtitle="Define when to alert/hand off.">
        <Field label="Escalate on Low Confidence">
          <div className="flex items-center gap-2">
            <input type="checkbox" checked={!!form.data.esc_low_confidence}
                   onChange={(e)=>form.setData("esc_low_confidence", e.target.checked)} />
            <span className="text-sm text-gray-700">Notify manager if AI confidence < threshold</span>
          </div>
        </Field>
        <Field label="Escalate on Negative Sentiment">
          <div className="flex items-center gap-2">
            <input type="checkbox" checked={!!form.data.esc_sentiment}
                   onChange={(e)=>form.setData("esc_sentiment", e.target.checked)} />
            <span className="text-sm text-gray-700">Alert manager on negative sentiment</span>
          </div>
        </Field>
        <Field label={`Escalation Timeout Minutes (${form.data.esc_timeout_minutes})`} help="If no reply within this time, alert manager.">
          <input type="range" min="5" max="480" step="5"
                 value={form.data.esc_timeout_minutes}
                 onChange={(e)=>form.setData("esc_timeout_minutes", parseInt(e.target.value, 10))}
                 className="w-full" />
        </Field>
      </Section>
    </div>
  );
}
