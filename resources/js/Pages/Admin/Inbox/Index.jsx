import { useEffect, useState, useRef } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

export default function Index() {
    const [conversations, setConversations] = useState([]);
    const [messages, setMessages] = useState([]);
    const [selected, setSelected] = useState(null);
    const [context, setContext] = useState(null);
    const [message, setMessage] = useState("");
    const [search, setSearch] = useState("");
    const [tone, setTone] = useState("professional");
    const [loadingMessages, setLoadingMessages] = useState(false);
    const [sending, setSending] = useState(false);
    const [generating, setGenerating] = useState(false);
    const bottomRef = useRef(null);

    useEffect(() => {
        loadConversations();
    }, []);

    useEffect(() => {
        const timer = setTimeout(() => {
            loadConversations();
        }, 300);

        return () => clearTimeout(timer);
    }, [search]);

    useEffect(() => {
        if (selected) {
            loadMessages(selected.id);
        }
    }, [selected]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    }, [messages]);

    const loadConversations = async () => {
        try {
            const res = await axios.get("/admin/inbox/list", {
                params: { search },
            });

            setConversations(res.data.conversations || []);
        } catch (error) {
            console.error("Failed to load conversations", error);
        }
    };

    const loadMessages = async (id) => {
        setLoadingMessages(true);

        try {
            const res = await axios.get(`/admin/inbox/messages/${id}`);
            setMessages(res.data.messages || []);
            setContext(res.data.context || null);
            loadConversations();
        } catch (error) {
            console.error("Failed to load messages", error);
        } finally {
            setLoadingMessages(false);
        }
    };

    const sendMessage = async () => {
        if (!message.trim() || !selected || sending) return;

        setSending(true);

        try {
            await axios.post("/admin/inbox/send", {
                conversation_id: selected.id,
                message: message.trim(),
            });

            setMessage("");
            await loadMessages(selected.id);
        } catch (error) {
            console.error("Failed to send message", error);
            alert("Message failed to send. Please check WhatsApp settings/logs.");
        } finally {
            setSending(false);
        }
    };

    const generateReply = async () => {
        if (!selected || generating) return;

        setGenerating(true);

        try {
            const res = await axios.post("/admin/inbox/suggest-reply", {
                conversation_id: selected.id,
                tone,
            });

            setMessage(res.data.suggestion || "");
        } catch (error) {
            console.error("Failed to generate reply", error);
            alert("Could not generate reply.");
        } finally {
            setGenerating(false);
        }
    };

    const formatTime = (value) => {
        if (!value) return "";

        try {
            return new Date(value).toLocaleString();
        } catch {
            return value;
        }
    };

    const statusLabel = (msg) => {
        if (msg.is_ai) return "AI";
        if (msg.source === "human") return "Human";
        return msg.provider_status || "";
    };

    return (
        <AuthenticatedLayout>
            <div className="h-[calc(100vh-4rem)] bg-gray-100 flex overflow-hidden">

                {/* LEFT PANEL */}
                <div
                    className={`w-full md:w-1/3 lg:w-1/4 bg-white border-r flex flex-col ${
                        selected ? "hidden md:flex" : "flex"
                    }`}
                >
                    <div className="p-4 border-b">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <div className="font-bold text-lg text-gray-900">
                                    WhatsApp Inbox
                                </div>
                                <div className="text-xs text-gray-500 mt-1">
                                    Manage customer conversations
                                </div>
                            </div>

                            <a
                                href="/admin/dashboard"
                                className="text-xs text-blue-600 hover:underline whitespace-nowrap"
                            >
                                Back
                            </a>
                        </div>

                        <input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="mt-3 w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            placeholder="Search name, phone, message..."
                        />
                    </div>

                    <div className="overflow-y-auto flex-1">
                        {conversations.length === 0 && (
                            <div className="p-4 text-sm text-gray-400">
                                No conversations found.
                            </div>
                        )}

                        {conversations.map((c) => (
                            <button
                                type="button"
                                key={c.id}
                                onClick={() => setSelected(c)}
                                className={`w-full text-left p-4 border-b hover:bg-gray-50 transition ${
                                    selected?.id === c.id ? "bg-green-50" : ""
                                }`}
                            >
                                <div className="flex justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="font-semibold text-gray-900 truncate">
                                            {c.customer_name || c.customer_phone}
                                        </div>

                                        <div className="text-xs text-gray-500 truncate mt-1">
                                            {c.customer_phone}
                                        </div>
                                    </div>

                                    {c.unread_count > 0 && (
                                        <span className="bg-green-600 text-white text-xs rounded-full px-2 py-0.5 h-fit">
                                            {c.unread_count}
                                        </span>
                                    )}
                                </div>

                                <div className="text-sm text-gray-500 truncate mt-2">
                                    {c.last_message_preview || "No message preview"}
                                </div>

                                <div className="text-xs text-gray-400 mt-1">
                                    {formatTime(c.last_message_at)}
                                </div>
                            </button>
                        ))}
                    </div>
                </div>

                {/* RIGHT PANEL */}
                <div
                    className={`flex-1 flex-col ${
                        selected ? "flex" : "hidden md:flex"
                    }`}
                >
                    {selected ? (
                        <>
                            {/* Chat Header */}
                            <div className="p-4 border-b bg-white flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <button
                                        onClick={() => {
                                            setSelected(null);
                                            setMessages([]);
                                            setContext(null);
                                        }}
                                        className="md:hidden text-sm text-blue-600"
                                    >
                                        ← Back
                                    </button>

                                    <div>
                                        <div className="font-semibold text-gray-900">
                                            {selected.customer_name || selected.customer_phone}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {selected.customer_phone}
                                        </div>
                                    </div>
                                </div>

                                <div className="text-xs text-gray-500">
                                    Lead: {context?.lead_id || "Not linked"}
                                </div>
                            </div>

                            <div className="flex-1 flex overflow-hidden">
                                {/* Chat Messages */}
                                <div className="flex-1 flex flex-col">
                                    <div className="flex-1 overflow-y-auto p-4 space-y-3">
                                        {loadingMessages ? (
                                            <div className="text-sm text-gray-400">
                                                Loading messages...
                                            </div>
                                        ) : (
                                            messages.map((m) => (
                                                <div
                                                    key={m.id}
                                                    className={`max-w-lg ${
                                                        m.direction === "out"
                                                            ? "ml-auto"
                                                            : "mr-auto"
                                                    }`}
                                                >
                                                    <div
                                                        className={`p-3 rounded-2xl text-sm shadow-sm ${
                                                            m.direction === "out"
                                                                ? "bg-green-600 text-white rounded-br-sm"
                                                                : "bg-white border text-gray-900 rounded-bl-sm"
                                                        }`}
                                                    >
                                                        <div className="whitespace-pre-wrap">
                                                            {m.body}
                                                        </div>
                                                    </div>

                                                    <div
                                                        className={`text-[11px] text-gray-400 mt-1 ${
                                                            m.direction === "out"
                                                                ? "text-right"
                                                                : "text-left"
                                                        }`}
                                                    >
                                                        {formatTime(m.created_at)}
                                                        {statusLabel(m) && (
                                                            <span> · {statusLabel(m)}</span>
                                                        )}
                                                    </div>
                                                </div>
                                            ))
                                        )}

                                        <div ref={bottomRef}></div>
                                    </div>

                                    {/* AI Reply Box */}
                                    <div className="bg-white border-t px-4 py-3">
                                        <div className="flex flex-col lg:flex-row gap-2 mb-3">
                                            <select
                                                value={tone}
                                                onChange={(e) => setTone(e.target.value)}
                                                className="border rounded-lg px-3 py-2 text-sm"
                                            >
                                                <option value="professional">Professional</option>
                                                <option value="friendly">Friendly</option>
                                                <option value="short">Short</option>
                                                <option value="urgent">Urgent</option>
                                            </select>

                                            <button
                                                onClick={generateReply}
                                                disabled={generating}
                                                className="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-100 disabled:opacity-60"
                                            >
                                                {generating ? "Generating..." : "Generate AI Reply"}
                                            </button>

                                            <div className="text-xs text-gray-400 flex items-center">
                                                AI only drafts. Admin must review before send.
                                            </div>
                                        </div>

                                        <div className="flex gap-2">
                                            <textarea
                                                value={message}
                                                onChange={(e) => setMessage(e.target.value)}
                                                onKeyDown={(e) => {
                                                    if (e.key === "Enter" && !e.shiftKey) {
                                                        e.preventDefault();
                                                        sendMessage();
                                                    }
                                                }}
                                                className="flex-1 border rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-green-500"
                                                rows="2"
                                                placeholder="Type a message... Press Enter to send, Shift+Enter for new line"
                                            />

                                            <button
                                                onClick={sendMessage}
                                                disabled={sending || !message.trim()}
                                                className="bg-green-600 text-white px-5 rounded-lg font-medium hover:bg-green-700 disabled:opacity-60"
                                            >
                                                {sending ? "Sending..." : "Send"}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {/* Context Panel */}
                                <div className="hidden xl:block w-80 bg-white border-l p-4 overflow-y-auto">
                                    <div className="font-semibold text-gray-900">
                                        Customer Context
                                    </div>

                                    <div className="mt-4 space-y-3 text-sm">
                                        <div>
                                            <div className="text-xs text-gray-500">Name</div>
                                            <div className="font-medium text-gray-900">
                                                {context?.name ||
                                                    context?.lead_name ||
                                                    selected.customer_name ||
                                                    "Unknown"}
                                            </div>
                                        </div>

                                        <div>
                                            <div className="text-xs text-gray-500">Phone</div>
                                            <div className="font-medium text-gray-900">
                                                {context?.phone || selected.customer_phone}
                                            </div>
                                        </div>

                                        <div>
                                            <div className="text-xs text-gray-500">
                                                Lead Status
                                            </div>
                                            <div className="font-medium text-gray-900">
                                                {context?.lead_status || "Not linked"}
                                            </div>
                                        </div>

                                        <div>
                                            <div className="text-xs text-gray-500">
                                                Conversation State
                                            </div>
                                            <div className="font-medium text-gray-900">
                                                {context?.conversation_state || "Not available"}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-6 p-3 rounded-lg bg-yellow-50 border border-yellow-100">
                                        <div className="text-sm font-semibold text-yellow-900">
                                            Human Takeover
                                        </div>
                                        <div className="text-xs text-yellow-800 mt-1">
                                            Sending a manual reply moves this lead into human mode and stops bot replies.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="flex items-center justify-center h-full text-gray-400">
                            Select a conversation
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}