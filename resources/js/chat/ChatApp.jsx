import React, { useEffect, useState } from "react";
import ChatThread from "./ChatThread";
import Composer from "./Composer";
import SmartReplyBar from "./SmartReplyBar";
import { pollMessages } from "./api";

export default function ChatApp({
    conversationId,
    initialMessages,
    endpointMessages,
    endpointSend,
    endpointSmartReplies,
}) {
    const [messages, setMessages] = useState(initialMessages || []);
    const [loading, setLoading] = useState(false);

    // Poll for new messages every 4 seconds
    useEffect(() => {
        if (!conversationId) return;
        const timer = setInterval(async () => {
            const lastId = messages.length ? messages[messages.length - 1].id : 0;
            const newMessages = await pollMessages(endpointMessages, lastId);
            if (newMessages.length) {
                setMessages((prev) => [...prev, ...newMessages]);
            }
        }, 4000);

        return () => clearInterval(timer);
    }, [conversationId, messages]);

    if (!conversationId) {
        return (
            <div className="flex flex-col h-full items-center justify-center text-gray-500 text-sm">
                Select a conversation
            </div>
        );
    }

    return (
        <div className="h-full flex flex-col">
            <ChatThread messages={messages} loading={loading} />

            <SmartReplyBar
                endpoint={endpointSmartReplies}
                onSelect={(text) =>
                    setMessages((prev) => [
                        ...prev,
                        {
                            id: Math.random(),
                            direction: "out",
                            body: text,
                            created_at: new Date().toISOString(),
                            is_ai: true,
                        },
                    ])
                }
            />

            <Composer
                endpoint={endpointSend}
                onSend={(msg) =>
                    setMessages((prev) => [...prev, msg])
                }
                setLoading={setLoading}
            />
        </div>
    );
}
