import { useEffect, useState, useRef } from "react";
import axios from "axios";

export default function Index() {
    const [conversations, setConversations] = useState([]);
    const [messages, setMessages] = useState([]);
    const [selected, setSelected] = useState(null);
    const [message, setMessage] = useState("");
    const bottomRef = useRef(null);

    useEffect(() => {
        loadConversations();
    }, []);

    useEffect(() => {
        if (selected) loadMessages(selected.id);
    }, [selected]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    const loadConversations = async () => {
        const res = await axios.get("/admin/inbox/list");
        setConversations(res.data.conversations);
    };

    const loadMessages = async (id) => {
        const res = await axios.get(`/admin/inbox/messages/${id}`);
        setMessages(res.data.messages);
        loadConversations();
    };

    const sendMessage = async () => {
        if (!message.trim()) return;

        await axios.post("/admin/inbox/send", {
            conversation_id: selected.id,
            message: message,
        });

        setMessage("");
        loadMessages(selected.id);
    };

    return (
        <div className="flex h-screen bg-gray-100">
            {/* LEFT PANEL */}
            <div className="w-1/3 bg-white border-r overflow-y-auto">
                <div className="p-4 font-bold text-lg border-b">
                    WhatsApp Inbox
                </div>

                {conversations.map((c) => (
                    <div
                        key={c.id}
                        onClick={() => setSelected(c)}
                        className={`p-4 border-b cursor-pointer hover:bg-gray-50 ${
                            selected?.id === c.id ? "bg-gray-100" : ""
                        }`}
                    >
                        <div className="font-semibold">
                            {c.customer_name || c.customer_phone}
                        </div>
                        <div className="text-sm text-gray-500 truncate">
                            {c.last_message_preview}
                        </div>
                        {c.unread_count > 0 && (
                            <div className="text-xs text-green-600">
                                {c.unread_count} new
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* RIGHT PANEL */}
            <div className="flex-1 flex flex-col">
                {selected ? (
                    <>
                        <div className="p-4 border-b bg-white font-semibold">
                            {selected.customer_name || selected.customer_phone}
                        </div>

                        <div className="flex-1 overflow-y-auto p-4 space-y-3">
                            {messages.map((m) => (
                                <div
                                    key={m.id}
                                    className={`max-w-xs p-3 rounded-lg text-sm ${
                                        m.direction === "out"
                                            ? "ml-auto bg-green-500 text-white"
                                            : "bg-white border"
                                    }`}
                                >
                                    {m.body}
                                </div>
                            ))}
                            <div ref={bottomRef}></div>
                        </div>

                        <div className="p-4 bg-white border-t flex gap-2">
                            <input
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                className="flex-1 border rounded-lg px-3 py-2"
                                placeholder="Type a message..."
                            />
                            <button
                                onClick={sendMessage}
                                className="bg-green-600 text-white px-4 rounded-lg"
                            >
                                Send
                            </button>
                        </div>
                    </>
                ) : (
                    <div className="flex items-center justify-center h-full text-gray-400">
                        Select a conversation
                    </div>
                )}
            </div>
        </div>
    );
}