import React from "react";

export default function SmartReplies({ suggestions, onSelect }) {
    if (!suggestions || suggestions.length === 0) return null;

    return (
        <div className="border-t bg-white p-3 flex flex-wrap gap-2">
            {suggestions.map((s, idx) => (
                <button
                    key={idx}
                    className="rounded-lg bg-[#294579]/10 px-3 py-1 text-sm text-[#0D1B3D] transition hover:bg-[#294579]/20"
                    onClick={() => onSelect(s.text)}
                >
                    {s.text}
                </button>
            ))}
        </div>
    );
}
