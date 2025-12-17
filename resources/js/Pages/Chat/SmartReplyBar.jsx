import React from "react";

export default function SmartReplyBar({ replies, onSelect, onLoad }) {
    // No suggestions yet → show a small prompt to load
    if (!replies || replies.length === 0) {
        return (
            <div className="border-t bg-slate-50 px-3 py-2 text-xs text-slate-500 flex items-center justify-between">
                <span>Need suggestions based on last message?</span>
                <button
                    type="button"
                    onClick={onLoad}
                    className="px-2 py-1 text-xs rounded border border-slate-300 bg-white hover:bg-slate-100"
                >
                    Load smart replies
                </button>
            </div>
        );
    }

    // Show suggestion chips
    return (
        <div className="border-t bg-white p-3 flex flex-wrap gap-2">
            {replies.map((s, idx) => (
                <button
                    key={idx}
                    type="button"
                    className="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-md hover:bg-blue-200 transition"
                    onClick={() => onSelect(s.text)}
                >
                    {s.text}
                </button>
            ))}
            <button
                type="button"
                onClick={onLoad}
                className="px-2 py-1 text-xs rounded border border-slate-300 bg-slate-50 hover:bg-slate-100 ml-auto"
            >
                Refresh
            </button>
        </div>
    );
}
