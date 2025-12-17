import React from "react";

export default function SmartReplies({ suggestions, onSelect }) {
    if (!suggestions || suggestions.length === 0) return null;

    return (
        <div className="border-t bg-white p-3 flex flex-wrap gap-2">
            {suggestions.map((s, idx) => (
                <button
                    key={idx}
                    className="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-md hover:bg-blue-200 transition"
                    onClick={() => onSelect(s.text)}
                >
                    {s.text}
                </button>
            ))}
        </div>
    );
}
