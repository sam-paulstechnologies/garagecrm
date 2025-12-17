import React, { useEffect, useRef } from "react";
import MessageBubble from "./MessageBubble";

export default function MessageList({ messages, loading, typing }) {
    const bottomRef = useRef(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages, typing]);

    if (loading) {
        return (
            <div className="p-4 text-center text-gray-500">
                Loading messages…
            </div>
        );
    }

    return (
        <div className="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-2">

            {messages.length === 0 && (
                <div className="text-center text-gray-400 mt-10">
                    No messages yet.
                </div>
            )}

            {messages.map((msg) => (
                <MessageBubble key={msg.id} msg={msg} />
            ))}

            {typing && (
                <div className="text-xs text-gray-400 italic px-2">
                    User is typing…
                </div>
            )}

            <div ref={bottomRef}></div>
        </div>
    );
}
