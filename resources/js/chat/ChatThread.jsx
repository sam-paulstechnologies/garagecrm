import React, { useEffect, useRef } from "react";
import MessageBubble from "./MessageBubble";

export default function ChatThread({ messages, loading }) {
    const boxRef = useRef(null);

    useEffect(() => {
        const box = boxRef.current;
        if (box) {
            box.scrollTop = box.scrollHeight;
        }
    }, [messages]);

    return (
        <div
            ref={boxRef}
            className="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50"
        >
            {messages.map((m) => (
                <MessageBubble key={m.id} message={m} />
            ))}

            {loading && (
                <div className="text-xs text-gray-400 text-center">Sending…</div>
            )}
        </div>
    );
}
