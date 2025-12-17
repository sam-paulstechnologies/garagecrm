import React, { useEffect, useState, useRef } from "react";
import MessageList from "./MessageList";
import MessageInput from "./MessageInput";
import SmartReplyBar from "./SmartReplyBar";

export default function ChatWindow({ conversationId }) {
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [smartReplies, setSmartReplies] = useState([]);

    const lastMessageId = useRef(0);
    const pollingRef = useRef(null);

    const el = document.getElementById("chat-window");

    const endpointMessages = el.dataset.endpointMessages;
    const endpointSend = el.dataset.endpointSend;
    const endpointSmartReplies = el.dataset.endpointSmartReplies;
    const endpointMarkRead = `/admin/chat/${conversationId}/mark-read`;
    const endpointListJson = `/admin/chat/json/list`;

    const csrf = document.querySelector(`meta[name="csrf-token"]`)?.content;

    /** Load full message history */
    const loadMessages = async () => {
        if (!endpointMessages) return;

        try {
            const res = await fetch(endpointMessages);
            const data = await res.json();

            if (data.ok) {
                setMessages(data.messages || []);
                if (data.messages && data.messages.length > 0) {
                    lastMessageId.current = data.messages.at(-1).id;
                }
            }
        } catch (e) {
            console.error("Failed to load messages", e);
        } finally {
            setLoading(false);
        }

        // Mark all as read on first load
        if (csrf) {
            await fetch(endpointMarkRead, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrf },
            }).catch(() => {});
        }
    };

    /** Poll incoming messages */
    const startPolling = () => {
        if (!endpointMessages) return;

        pollingRef.current = setInterval(async () => {
            try {
                const res = await fetch(
                    `${endpointMessages}?since_id=${lastMessageId.current}`
                );
                const data = await res.json();

                if (data.ok && data.messages && data.messages.length > 0) {
                    setMessages((prev) => [...prev, ...data.messages]);
                    lastMessageId.current = data.messages.at(-1).id;

                    // Auto mark read when receiving new inbound
                    if (csrf) {
                        await fetch(endpointMarkRead, {
                            method: "POST",
                            headers: { "X-CSRF-TOKEN": csrf },
                        }).catch(() => {});
                    }
                }
            } catch (e) {
                console.error("Polling error", e);
            }
        }, 2500);
    };

    const stopPolling = () => {
        if (pollingRef.current) clearInterval(pollingRef.current);
    };

    /** Send a message */
    const handleSend = async (text) => {
        if (!text || !endpointSend) return;

        try {
            const res = await fetch(endpointSend, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
                },
                body: JSON.stringify({ message: text }),
            });

            const data = await res.json();

            if (data.ok && data.message) {
                setMessages((prev) => [...prev, data.message]);
                lastMessageId.current = data.message.id;
            }
        } catch (e) {
            console.error("Send error", e);
        }
    };

    /** Load smart replies */
    const loadSmartReplies = async () => {
        if (!endpointSmartReplies || !csrf) return;

        try {
            const res = await fetch(endpointSmartReplies, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrf },
            });

            const data = await res.json();
            if (data.ok) setSmartReplies(data.suggestions || []);
        } catch (e) {
            console.error("Smart replies error", e);
        }
    };

    /** Auto-refresh conversation list */
    const pollConversationList = async () => {
        try {
            const res = await fetch(endpointListJson);
            const data = await res.json();

            if (data.ok && data.conversations) {
                window.dispatchEvent(
                    new CustomEvent("conv-list-update", {
                        detail: data.conversations,
                    })
                );
            }
        } catch (e) {
            console.error("conv list poll error", e);
        }
    };

    useEffect(() => {
        if (!conversationId) return;

        loadMessages();
        startPolling();
        const listInterval = setInterval(pollConversationList, 5000);

        return () => {
            stopPolling();
            clearInterval(listInterval);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [conversationId]);

    return (
        <div className="flex flex-col h-full">
            <div className="p-4 bg-white border-b text-gray-700 font-semibold">
                Conversation #{conversationId}
            </div>

            <MessageList messages={messages} loading={loading} />

            <SmartReplyBar
                replies={smartReplies}
                onSelect={handleSend}
                onLoad={loadSmartReplies}
            />

            <MessageInput onSend={handleSend} />
        </div>
    );
}
