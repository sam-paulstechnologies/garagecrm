import { useState, useEffect, useRef } from "react";
import axios from "axios";
import MessageBubble from "./Components/MessageBubble";
import AiSuggestionCard from "./Components/AiSuggestionCard";
import TypingIndicator from "./Components/TypingIndicator";

export default function ChatWindow({ conversationId }) {
    const [messages, setMessages] = useState([]);
    const [suggestions, setSuggestions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [sending, setSending] = useState(false);
    const [input, setInput] = useState("");
    const [isTyping, setIsTyping] = useState(false);
    const poller = useRef(null);
    const bottomRef = useRef(null);

    /** Auto scroll to bottom */
    const scrollToBottom = () => {
        bottomRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    /** Fetch messages */
    const loadMessages = async () => {
        try {
            const res = await axios.get(`/admin/chat/${conversationId}/messages`);
            setMessages(res.data.messages || []);
            scrollToBottom();
        } catch (err) {
            console.error("Error fetching messages", err);
        }
    };

    /** Fetch AI suggestions */
    const loadSuggestions = async () => {
        try {
            const res = await axios.get(`/admin/inbox/${conversationId}/ai-suggest`);
            setSuggestions(res.data.suggestions || []);
        } catch (err) {
            console.error("AI Suggestion Error", err);
        }
    };

    /** Send message */
    const sendMessage = async () => {
        if (!input.trim()) return;
        setSending(true);

        try {
            await axios.post(`/admin/chat/${conversationId}/send`, {
                message: input,
            });
            setInput("");
            await loadMessages();
        } catch (err) {
            console.error("Send error", err);
        } finally {
            setSending(false);
        }
    };

    /** Apply AI suggestion */
    const applySuggestion = async (text) => {
        setInput(text);
        scrollToBottom();
    };

    /** Setup polling */
    useEffect(() => {
        loadMessages().then(() => setLoading(false));
        loadSuggestions();

        poller.current = setInterval(() => {
            loadMessages();
        }, 4000);

        return () => clearInterval(poller.current);
    }, [conversationId]);

    /** Auto-scroll on new messages */
    useEffect(scrollToBottom, [messages]);

    return (
        <div className="flex flex-col h-full text-gray-800">
            
            {/* Chat messages */}
            <div className="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-gray-50">
                {loading ? (
                    <div className="text-center py-6 text-gray-500">Loading chat…</div>
                ) : (
                    messages.map((msg) => (
                        <MessageBubble key={msg.id} message={msg} />
                    ))
                )}

                {isTyping && <TypingIndicator />}

                <div ref={bottomRef}></div>
            </div>

            {/* AI Suggestions */}
            {suggestions.length > 0 && (
                <div className="p-3 bg-white border-t border-gray-200 space-y-2">
                    <div className="text-xs font-semibold text-gray-500 uppercase">
                        AI Suggested Replies
                    </div>
                    <div className="flex gap-2 overflow-x-auto">
                        {suggestions.map((s, i) => (
                            <AiSuggestionCard
                                key={i}
                                text={s.text}
                                confidence={s.confidence}
                                onSelect={() => applySuggestion(s.text)}
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* Input bar */}
            <div className="p-3 bg-white border-t border-gray-200 flex items-center gap-2">
                <input
                    type="text"
                    className="flex-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-blue-400"
                    placeholder="Type a message…"
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                />

                <button
                    onClick={sendMessage}
                    disabled={sending}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    {sending ? "Sending…" : "Send"}
                </button>
            </div>
        </div>
    );
}
