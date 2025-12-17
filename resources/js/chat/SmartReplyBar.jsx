import React, { useState } from "react";
import { fetchSmartReplies } from "./api";

export default function SmartReplyBar({ endpoint, onSelect }) {
    const [suggestions, setSuggestions] = useState([]);

    async function load() {
        const list = await fetchSmartReplies(endpoint);
        setSuggestions(list);
    }

    return (
        <div className="border-t bg-gray-100 p-2 flex flex-wrap gap-2">
            <button
                onClick={load}
                className="text-xs text-blue-600 underline"
            >
                Suggest replies
            </button>

            {suggestions.map((s, i) => (
                <button
                    key={i}
                    onClick={() => onSelect(s.text)}
                    className="px-2 py-1 text-xs bg-white border rounded hover:bg-gray-50"
                >
                    {s.text}
                </button>
            ))}
        </div>
    );
}
