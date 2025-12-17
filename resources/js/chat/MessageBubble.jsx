import React from "react";

export default function MessageBubble({ message }) {
    const isOut = message.direction === "out";

    return (
        <div className={`flex ${isOut ? "justify-end" : "justify-start"}`}>
            <div
                className={`max-w-[70%] rounded-lg px-3 py-2 text-sm shadow ${
                    isOut
                        ? "bg-green-600 text-white"
                        : "bg-white border border-gray-200 text-gray-800"
                }`}
            >
                <div className="whitespace-pre-wrap">{message.body}</div>

                <div className="mt-1 text-[11px] opacity-70">
                    {new Date(message.created_at).toLocaleString()}
                    {isOut && message.provider_status
                        ? ` · ${message.provider_status}`
                        : ""}
                </div>
            </div>
        </div>
    );
}
