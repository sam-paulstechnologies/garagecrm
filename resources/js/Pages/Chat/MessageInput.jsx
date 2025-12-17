import React, { useState } from "react";

export default function MessageInput({ onSend }) {
    const [text, setText] = useState("");

    const send = () => {
        const msg = text.trim();
        if (!msg) return;
        onSend(msg);
        setText("");
    };

    return (
        <div className="p-3 bg-white border-t flex items-center space-x-2">
            <textarea
                className="flex-1 border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring
                           resize-none h-12"
                placeholder="Type a message…"
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={(e) => {
                    if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        send();
                    }
                }}
            ></textarea>

            <button
                onClick={send}
                disabled={!text.trim()}
                className="bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
            >
                Send
            </button>
        </div>
    );
}
