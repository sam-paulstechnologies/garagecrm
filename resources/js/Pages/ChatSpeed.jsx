import React, { useEffect, useRef, useState } from 'react';

export default function ChatSpeed() {
  const [text, setText] = useState('');
  const [lines, setLines] = useState([]);
  const inputRef = useRef(null);

  useEffect(() => { inputRef.current?.focus(); }, []);

  const send = () => {
    if (!text.trim()) return;
    setLines(prev => [...prev, { t: Date.now(), text }]);
    setText('');
    inputRef.current?.focus();
  };

  return (
    <div className="min-h-screen p-4 bg-gray-50">
      <div className="max-w-2xl mx-auto bg-white rounded-2xl shadow p-4">
        <h1 className="text-xl font-semibold">⚡ Chat Speed</h1>
        <div className="mt-4 h-72 border rounded-xl p-3 overflow-auto">
          {lines.length === 0
            ? <div className="h-full grid place-items-center text-gray-400 text-sm">
                Start typing and press Enter…
              </div>
            : <ul className="space-y-2">
                {lines.map(l => (
                  <li key={l.t} className="bg-gray-100 rounded-lg px-3 py-2">{l.text}</li>
                ))}
              </ul>}
        </div>
        <div className="mt-4 flex gap-2">
          <input
            ref={inputRef}
            value={text}
            onChange={(e) => setText(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && send()}
            placeholder="Type fast and hit Enter…"
            className="flex-1 border rounded-xl px-3 py-2 outline-none focus:ring"
          />
          <button onClick={send} className="px-4 py-2 rounded-xl bg-black text-white">Send</button>
        </div>
      </div>
    </div>
  );
}
