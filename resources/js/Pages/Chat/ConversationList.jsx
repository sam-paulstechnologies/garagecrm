import React from "react";
import ConversationItem from "./ConversationItem";

export default function ConversationList({ conversations, activeId }) {
    if (!conversations || conversations.length === 0) {
        return (
            <div className="p-6 text-center text-gray-400">
                No conversations yet.
            </div>
        );
    }

    return (
        <div className="overflow-y-auto h-full divide-y divide-gray-200">
            {conversations.map(conv => (
                <ConversationItem
                    key={conv.id}
                    conv={conv}
                    active={activeId === conv.id}
                />
            ))}
        </div>
    );
}
