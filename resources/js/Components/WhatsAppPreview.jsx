import React from 'react';

/** Interpolate {{name}} or {name}; supports numeric {1}.. when variables is an array */
function interpolate(text, variables) {
  if (!text) return '';
  let out = text;

  // Object-style placeholders: {{key}} or {key}
  out = out.replace(/\{\{\s*([^}]+?)\s*\}\}|\{([^}]+?)\}/g, (_, a, b) => {
    const key = (a || b || '').trim();
    if (!key) return '';
    if (variables && !Array.isArray(variables) && typeof variables === 'object') {
      return variables[key] ?? '';
    }
    return '';
  });

  // Array-style: {1} {2}...
  if (Array.isArray(variables)) {
    out = out.replace(/\{(\d+)\}/g, (_, idx) => {
      const i = parseInt(idx, 10) - 1;
      return variables[i] ?? '';
    });
  }

  return out;
}

const Bubble = ({ children }) => (
  <div className="max-w-[85%] rounded-2xl px-3 py-2 bg-[#DCF8C6] text-black shadow-sm">
    <div className="whitespace-pre-wrap text-[15px] leading-snug">{children}</div>
  </div>
);

const Footer = ({ text }) =>
  text ? <div className="mt-2 text-[12px] text-gray-500">{text}</div> : null;

const Buttons = ({ buttons = [] }) => {
  if (!buttons?.length) return null;
  return (
    <div className="mt-3 flex flex-col gap-2">
      {buttons.map((btn, i) => (
        <button
          key={i}
          type="button"
          className="text-left w-full border rounded-xl px-3 py-2 text-[14px] hover:bg-gray-50"
          onClick={(e) => e.preventDefault()}
          title={btn.type}
        >
          {btn.text}
        </button>
      ))}
    </div>
  );
};

/**
 * props.template: { header, body, footer, buttons[] }
 * props.variables: object or array
 * props.meta: { timeString?: string }
 */
export default function WhatsAppPreview({ template, variables, meta = {} }) {
  const header = interpolate(template?.header, variables);
  const body   = interpolate(template?.body, variables);
  const footer = interpolate(template?.footer, variables);
  const timeString = meta.timeString || '10:22 AM';

  return (
    <div className="bg-[#ECE5DD] p-4 rounded-2xl border">
      {/* Top bar mock */}
      <div className="flex items-center gap-3 mb-3">
        <div className="w-8 h-8 rounded-full bg-gray-300" />
        <div className="text-sm">
          <div className="font-semibold">Customer</div>
          <div className="text-gray-500 text-xs">online</div>
        </div>
      </div>

      {/* Chat area */}
      <div className="bg-[#E5DDD5] rounded-xl p-3 min-h-[220px] flex flex-col gap-2">
        <div className="flex justify-end">
          <div>
            <Bubble>
              {header && <div className="font-semibold mb-1">{header}</div>}
              {body}
              <div className="mt-1 text-[11px] text-gray-500 text-right">{timeString}</div>
            </Bubble>
            <Footer text={footer} />
            <Buttons buttons={template?.buttons} />
          </div>
        </div>
      </div>
    </div>
  );
}
