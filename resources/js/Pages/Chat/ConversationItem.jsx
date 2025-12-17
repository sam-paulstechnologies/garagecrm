import React from "react";

export default function ConversationItem({ conv, active }) {
    return (
        <a
            href={`/admin/chat/${conv.id}`}
            className={`block p-4 cursor-pointer ${
                active ? "bg-blue-50" : "hover:bg-gray-100"
            }`}
        >
            <div className="flex justify-between">
                <div className="font-medium text-gray-800">
                    {conv.customer_name || "Unknown"}
                </div>

                {conv.unread_count > 0 && (
                    <span className="bg-red-600 text-white text-xs rounded-full px-2">
                        {conv.unread_count}
                    </span>
                )}
            </div>

            <div className="text-xs text-gray-500">
                {conv.customer_phone}
            </div>

            <div className="text-sm text-gray-600 truncate mt-1">
                {conv.last_message_preview || "No messages yet"}
            </div>

            <div className="text-[10px] text-gray-400">
                {conv.last_message_at
                    ? new Date(conv.last_message_at).toLocaleString()
                    : ""}
            </div>
        </a>
    );
}
