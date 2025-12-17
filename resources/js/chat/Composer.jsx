import React, { useState } from "react";
import { sendMessage } from "./api";

export default function Composer({ endpoint, onSend, setLoading }) {
    const [text, setText] = useState("");

    async function handleSend() {
        if (!text.trim()) return;

        setLoading(true);

        const msg = await sendMessage(endpoint, text.trim());
        if (msg) {
            onSend(msg);
        }

        setText("");
        setLoading(false);
    }

    return (
        <div className="border-t bg-white p-3">
            <div className="flex gap-2">
                <input
                    type="text"
                    className="flex-1 border rounded px-3 py-2 text-sm"
                    placeholder="Type a message…"
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    onKeyDown={(e) => e.key === "Enter" && handleSend()}
                />

                <button
                    onClick={handleSend}
                    className="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm"
                >
                    Send
                </button>
            </div>
        </div>
    );
}
