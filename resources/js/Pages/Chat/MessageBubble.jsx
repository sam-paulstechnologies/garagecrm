import React from "react";

export default function MessageBubble({ msg }) {
    const isOut = msg.direction === "out";
    const isAi = msg.is_ai;

    const deliveryIcon = (() => {
        if (!isOut) return null;

        switch (msg.provider_status) {
            case "sent":
                return "✓";
            case "delivered":
                return "✓✓";
            case "read":
                return <span className="text-blue-400">✓✓</span>;
            default:
                return null;
        }
    })();

    return (
        <div className={`flex ${isOut ? "justify-end" : "justify-start"} mb-2`}>
            <div
                className={`max-w-[75%] px-3 py-2 rounded-lg shadow-sm animate-fade-in 
                ${isOut ? "bg-[#0D1B3D] text-white" : "bg-white border border-[#0D1B3D]/15 text-[#0D1B3D]"}`}
            >
                <div className="whitespace-pre-wrap text-sm">{msg.body}</div>

                <div className="mt-1 text-[10px] opacity-80 flex items-center gap-1">
                    {new Date(msg.created_at).toLocaleString()}

                    {deliveryIcon && (
                        <span className="ml-1 opacity-90">{deliveryIcon}</span>
                    )}

                    {isAi && (
                        <span className="italic text-[10px] opacity-80">(AI)</span>
                    )}
                </div>
            </div>
        </div>
    );
}
