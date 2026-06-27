import { useEffect, useRef, useState } from "react";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

export default function Index({ selectedConversationId = null }) {
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
    const [composerTab, setComposerTab] = useState("ai");
    const [sendError, setSendError] = useState("");

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
        if (!selected && selectedConversationId && conversations.length > 0) {
            const conversation = conversations.find(
                (item) => Number(item.id) === Number(selectedConversationId)
            );

            if (conversation) {
                setSelected(conversation);
            }
        }
    }, [conversations, selected, selectedConversationId]);

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
            const res = await axios.get("/manager/inbox/list", {
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
            const res = await axios.get(`/manager/inbox/messages/${id}`);
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
        setSendError("");

        try {
            await axios.post("/manager/inbox/send", {
                conversation_id: selected.id,
                message: message.trim(),
            });

            setMessage("");
            await loadMessages(selected.id);
        } catch (error) {
            console.error("Failed to send message", error);
            setSendError("Message was not sent. Check WhatsApp settings/logs before retrying.");
        } finally {
            setSending(false);
        }
    };

    const generateReply = async () => {
        if (!selected || generating) return;

        setGenerating(true);

        try {
            const res = await axios.post("/manager/inbox/suggest-reply", {
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

    const markRead = async () => {
        if (!selected) return;

        try {
            await axios.post("/manager/inbox/mark-read", {
                conversation_id: selected.id,
            });

            await loadConversations();
        } catch (error) {
            console.error("Failed to mark read", error);
        }
    };

    const formatTime = (value) => {
        if (!value) return "";

        try {
            return new Date(value).toLocaleTimeString([], {
                hour: "2-digit",
                minute: "2-digit",
            });
        } catch {
            return value;
        }
    };

    const formatDate = (value) => {
        if (!value) return "";

        try {
            return new Date(value).toLocaleDateString([], {
                day: "2-digit",
                month: "short",
            });
        } catch {
            return value;
        }
    };

    const initials = (name, phone) => {
        const value = (name || phone || "?").trim();

        if (!value) return "?";

        const words = value.split(" ").filter(Boolean);

        if (words.length >= 2) {
            return `${words[0][0]}${words[1][0]}`.toUpperCase();
        }

        return value[0].toUpperCase();
    };

    const statusLabel = (msg) => {
        if (msg.is_ai) return "AI";
        if (msg.source === "human") return "Human";
        return msg.provider_status || "";
    };

    const selectedName =
        context?.name ||
        context?.lead_name ||
        selected?.customer_name ||
        selected?.customer_phone ||
        "Customer";

    const selectedPhone = context?.phone || selected?.customer_phone || "";

    const selectedLeadId = context?.lead_id
        ? `L-${String(context.lead_id).padStart(4, "0")}`
        : "Not linked";

    const leadProfileUrl = context?.lead_id
        ? `/manager/leads?lead=${context.lead_id}`
        : "/manager/leads";

    return (
        <AuthenticatedLayout>
            <style>{`
                body {
                    overflow: hidden;
                    background: #070b16;
                }

                .sf-inbox-page {
                    height: calc(100vh - 64px);
                    overflow: hidden;
                    background:
                        radial-gradient(circle at top right, rgba(249, 115, 22, 0.10), transparent 30%),
                        radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 32%),
                        #070b16;
                    color: #e5e7eb;
                }

                .sf-inbox-shell {
                    height: 100%;
                    padding: 28px 24px;
                }

                .sf-inbox-frame {
                    height: 100%;
                    display: grid;
                    grid-template-columns: 380px minmax(0, 1fr) 360px;
                    gap: 16px;
                    max-width: 1780px;
                    margin: 0 auto;
                }

                .sf-panel {
                    min-width: 0;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(15, 23, 42, 0.92);
                    box-shadow: 0 24px 45px rgba(0, 0, 0, 0.28);
                    overflow: hidden;
                }

                .sf-left-panel,
                .sf-chat-panel,
                .sf-right-panel {
                    border-radius: 24px;
                }

                .sf-left-panel {
                    display: flex;
                    flex-direction: column;
                }

                .sf-left-header {
                    padding: 18px 18px 14px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 14px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(2, 6, 23, 0.40);
                }

                .sf-title {
                    font-size: 18px;
                    font-weight: 900;
                    color: #ffffff;
                    letter-spacing: -0.02em;
                }

                .sf-subtitle {
                    margin-top: 4px;
                    color: #94a3b8;
                    font-size: 12px;
                    font-weight: 600;
                }

                .sf-plus-btn {
                    width: 44px;
                    height: 44px;
                    border: 0;
                    border-radius: 16px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    color: #ffffff;
                    font-size: 24px;
                    line-height: 1;
                    font-weight: 800;
                    box-shadow: 0 14px 26px rgba(249, 115, 22, 0.28);
                }

                .sf-search-area {
                    padding: 12px 16px 14px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(2, 6, 23, 0.20);
                }

                .sf-search-box {
                    height: 44px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 0 14px;
                    border-radius: 14px;
                    background: rgba(2, 6, 23, 0.72);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    color: #94a3b8;
                }

                .sf-search-box input {
                    width: 100%;
                    border: 0;
                    outline: 0;
                    background: transparent;
                    color: #ffffff;
                    font-size: 14px;
                    font-weight: 600;
                }

                .sf-search-box input::placeholder {
                    color: #64748b;
                    font-weight: 600;
                }

                .sf-conversation-list {
                    flex: 1;
                    overflow-y: auto;
                    padding: 8px;
                }

                .sf-conversation {
                    width: 100%;
                    min-height: 78px;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 12px 10px;
                    border: 0;
                    border-radius: 18px;
                    background: transparent;
                    text-align: left;
                    transition: all 0.18s ease;
                }

                .sf-conversation:hover {
                    background: rgba(255, 255, 255, 0.04);
                }

                .sf-conversation.active {
                    background: linear-gradient(135deg, rgba(249, 115, 22, 0.16), rgba(37, 99, 235, 0.10));
                    box-shadow: inset 3px 0 0 #f97316;
                }

                .sf-conv-avatar,
                .sf-chat-avatar,
                .sf-profile-avatar {
                    border-radius: 999px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    font-weight: 900;
                }

                .sf-conv-avatar {
                    width: 48px;
                    height: 48px;
                    background: rgba(148, 163, 184, 0.16);
                    color: #cbd5e1;
                    font-size: 14px;
                }

                .sf-conversation.active .sf-conv-avatar {
                    background: rgba(249, 115, 22, 0.18);
                    color: #fdba74;
                }

                .sf-conv-body {
                    min-width: 0;
                    flex: 1;
                }

                .sf-conv-top,
                .sf-conv-bottom {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                }

                .sf-conv-name {
                    min-width: 0;
                    color: #ffffff;
                    font-size: 15px;
                    font-weight: 900;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .sf-conv-time {
                    color: #94a3b8;
                    font-size: 11px;
                    font-weight: 700;
                    white-space: nowrap;
                }

                .sf-conv-preview {
                    min-width: 0;
                    margin-top: 5px;
                    color: #94a3b8;
                    font-size: 13px;
                    font-weight: 600;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .sf-unread {
                    min-width: 22px;
                    height: 22px;
                    padding: 0 7px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 999px;
                    background: #f97316;
                    color: #ffffff;
                    font-size: 11px;
                    font-weight: 900;
                    box-shadow: 0 8px 14px rgba(249, 115, 22, 0.28);
                }

                .sf-empty-list {
                    padding: 24px;
                    color: #94a3b8;
                    text-align: center;
                    font-size: 14px;
                    font-weight: 600;
                }

                .sf-chat-panel {
                    min-width: 0;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }

                .sf-chat-header {
                    min-height: 78px;
                    padding: 16px 18px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 16px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(2, 6, 23, 0.40);
                    flex-shrink: 0;
                }

                .sf-chat-avatar {
                    width: 52px;
                    height: 52px;
                    background: rgba(148, 163, 184, 0.16);
                    color: #cbd5e1;
                    font-size: 15px;
                }

                .sf-chat-name {
                    color: #ffffff;
                    font-size: 18px;
                    font-weight: 900;
                    letter-spacing: -0.02em;
                }

                .sf-chat-phone {
                    margin-top: 3px;
                    color: #94a3b8;
                    font-size: 13px;
                    font-weight: 700;
                }

                .sf-lead-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 7px;
                    padding: 8px 12px;
                    border-radius: 12px;
                    background: rgba(37, 99, 235, 0.14);
                    color: #93c5fd;
                    border: 1px solid rgba(96, 165, 250, 0.20);
                    font-size: 12px;
                    font-weight: 900;
                    white-space: nowrap;
                }

                .sf-menu-dots {
                    width: 38px;
                    height: 38px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 12px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(2, 6, 23, 0.55);
                    color: #94a3b8;
                    font-weight: 900;
                    letter-spacing: 2px;
                }

                .sf-messages {
                    position: relative;
                    flex: 1;
                    overflow-y: auto;
                    padding: 28px;
                    background-color: #0b1220;
                    background-image:
                        radial-gradient(circle at 16px 16px, rgba(249, 115, 22, 0.055) 1.2px, transparent 1.8px),
                        radial-gradient(circle at 42px 38px, rgba(59, 130, 246, 0.045) 1.2px, transparent 1.8px),
                        linear-gradient(45deg, rgba(255, 255, 255, 0.018) 25%, transparent 25%),
                        linear-gradient(-45deg, rgba(255, 255, 255, 0.014) 25%, transparent 25%);
                    background-size: 58px 58px, 58px 58px, 92px 92px, 92px 92px;
                }

                .sf-date-pill {
                    width: fit-content;
                    margin: 0 auto 22px;
                    padding: 7px 13px;
                    border-radius: 999px;
                    background: rgba(15, 23, 42, 0.94);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    color: #94a3b8;
                    font-size: 12px;
                    font-weight: 900;
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.20);
                }

                .sf-empty-state {
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 24px;
                    text-align: center;
                }

                .sf-empty-card {
                    max-width: 450px;
                    padding: 34px;
                    border-radius: 24px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(15, 23, 42, 0.88);
                    box-shadow: 0 24px 40px rgba(0, 0, 0, 0.24);
                    backdrop-filter: blur(16px);
                }

                .sf-empty-icon {
                    margin: 0 auto 16px;
                    width: 58px;
                    height: 58px;
                    border-radius: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(249, 115, 22, 0.14);
                    color: #fdba74;
                    font-size: 28px;
                }

                .sf-empty-card h2 {
                    color: #ffffff;
                    font-size: 20px;
                    font-weight: 900;
                    letter-spacing: -0.02em;
                }

                .sf-empty-card p {
                    margin-top: 10px;
                    color: #94a3b8;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 1.7;
                }

                .sf-message-row {
                    display: flex;
                    margin-bottom: 14px;
                }

                .sf-message-row.in {
                    justify-content: flex-start;
                }

                .sf-message-row.out {
                    justify-content: flex-end;
                }

                .sf-bubble {
                    max-width: min(560px, 72%);
                    padding: 12px 14px 8px;
                    border-radius: 18px;
                    color: #e5e7eb;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 1.55;
                    white-space: pre-wrap;
                    overflow-wrap: anywhere;
                    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22);
                }

                .sf-message-row.in .sf-bubble {
                    background: rgba(15, 23, 42, 0.96);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-bottom-left-radius: 6px;
                }

                .sf-message-row.out .sf-bubble {
                    background: linear-gradient(135deg, rgba(249, 115, 22, 0.24), rgba(37, 99, 235, 0.16));
                    border: 1px solid rgba(249, 115, 22, 0.22);
                    border-bottom-right-radius: 6px;
                }

                .sf-message-meta {
                    margin-top: 7px;
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 5px;
                    color: #94a3b8;
                    font-size: 10px;
                    font-weight: 800;
                }

                .sf-ai-chip {
                    margin-right: auto;
                    padding: 2px 6px;
                    border-radius: 999px;
                    background: rgba(37, 99, 235, 0.16);
                    color: #93c5fd;
                    font-size: 10px;
                    font-weight: 900;
                }

                .sf-composer {
                    flex-shrink: 0;
                    padding: 14px 18px 16px;
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(2, 6, 23, 0.40);
                }

                .sf-composer-tabs {
                    display: flex;
                    align-items: center;
                    gap: 22px;
                    margin-bottom: 12px;
                    padding: 0 4px;
                }

                .sf-composer-tab {
                    position: relative;
                    border: 0;
                    background: transparent;
                    color: #94a3b8;
                    font-size: 13px;
                    font-weight: 900;
                    padding: 7px 0;
                }

                .sf-composer-tab.active {
                    color: #fdba74;
                }

                .sf-composer-tab.active::after {
                    content: "";
                    position: absolute;
                    left: 0;
                    right: 0;
                    bottom: -2px;
                    height: 3px;
                    border-radius: 999px;
                    background: #f97316;
                }

                .sf-ai-tools {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 12px;
                }

                .sf-tone-select {
                    height: 36px;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                    border-radius: 12px;
                    background: #020617;
                    color: #e5e7eb;
                    padding: 0 12px;
                    font-size: 12px;
                    font-weight: 800;
                    outline: 0;
                }

                .sf-ai-btn {
                    height: 36px;
                    border: 1px solid rgba(249, 115, 22, 0.22);
                    border-radius: 12px;
                    padding: 0 14px;
                    background: rgba(249, 115, 22, 0.12);
                    color: #fdba74;
                    font-size: 12px;
                    font-weight: 900;
                }

                .sf-ai-btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }

                .sf-ai-note {
                    color: #94a3b8;
                    font-size: 11px;
                    font-weight: 700;
                }

                .sf-input-box {
                    border: 1px solid rgba(255, 255, 255, 0.10);
                    border-radius: 18px;
                    background: rgba(2, 6, 23, 0.72);
                    overflow: hidden;
                    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.22);
                }

                .sf-input-box textarea {
                    width: 100%;
                    min-height: 72px;
                    max-height: 140px;
                    resize: none;
                    border: 0;
                    outline: 0;
                    padding: 16px;
                    color: #ffffff;
                    background: transparent;
                    font-size: 14px;
                    font-weight: 600;
                    line-height: 1.5;
                }

                .sf-input-box textarea::placeholder {
                    color: #64748b;
                }

                .sf-input-actions {
                    padding: 10px 12px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    border-top: 1px solid rgba(255, 255, 255, 0.08);
                }

                .sf-send-error {
                    margin: 10px 12px 0;
                    border: 1px solid rgba(248, 113, 113, 0.24);
                    border-radius: 12px;
                    background: rgba(127, 29, 29, 0.28);
                    color: #fecaca;
                    padding: 10px 12px;
                    font-size: 12px;
                    font-weight: 800;
                }

                .sf-input-icons {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    color: #94a3b8;
                }

                .sf-icon-btn {
                    min-width: 34px;
                    height: 34px;
                    border: 0;
                    border-radius: 11px;
                    background: transparent;
                    color: #94a3b8;
                    font-size: 11px;
                    font-weight: 900;
                    padding: 0 9px;
                }

                .sf-icon-btn:hover {
                    background: rgba(255, 255, 255, 0.06);
                    color: #fdba74;
                }

                .sf-send-group {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .sf-send-btn {
                    height: 42px;
                    border: 0;
                    border-radius: 14px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    padding: 0 18px;
                    background: linear-gradient(135deg, #f97316, #ea580c);
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: 900;
                    box-shadow: 0 12px 20px rgba(249, 115, 22, 0.22);
                }

                .sf-send-btn:hover:not(:disabled) {
                    filter: brightness(0.96);
                }

                .sf-send-btn:disabled {
                    opacity: 0.45;
                    cursor: not-allowed;
                }

                .sf-send-extra {
                    min-width: 58px;
                    height: 42px;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                    border-radius: 14px;
                    background: rgba(2, 6, 23, 0.72);
                    color: #fdba74;
                    padding: 0 12px;
                    font-weight: 900;
                }

                .sf-right-panel {
                    display: flex;
                    flex-direction: column;
                    overflow-y: auto;
                    background: rgba(15, 23, 42, 0.72);
                }

                .sf-right-section {
                    margin: 14px;
                    margin-bottom: 0;
                    border-radius: 20px;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    background: rgba(2, 6, 23, 0.45);
                    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.16);
                    overflow: hidden;
                }

                .sf-right-header {
                    padding: 18px 18px 14px;
                    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                }

                .sf-right-title {
                    color: #ffffff;
                    font-size: 17px;
                    font-weight: 900;
                    letter-spacing: -0.02em;
                }

                .sf-right-subtitle {
                    margin-top: 4px;
                    color: #94a3b8;
                    font-size: 12px;
                    font-weight: 600;
                }

                .sf-profile-top {
                    padding: 20px 18px;
                    display: flex;
                    align-items: center;
                    gap: 14px;
                }

                .sf-profile-avatar {
                    width: 58px;
                    height: 58px;
                    background: rgba(148, 163, 184, 0.16);
                    color: #cbd5e1;
                    font-size: 16px;
                }

                .sf-profile-name {
                    color: #ffffff;
                    font-size: 18px;
                    font-weight: 900;
                }

                .sf-profile-phone {
                    margin-top: 4px;
                    color: #94a3b8;
                    font-size: 13px;
                    font-weight: 700;
                }

                .sf-status-pill {
                    width: fit-content;
                    margin-top: 8px;
                    padding: 5px 9px;
                    border-radius: 999px;
                    background: rgba(34, 197, 94, 0.14);
                    color: #86efac;
                    border: 1px solid rgba(34, 197, 94, 0.18);
                    font-size: 11px;
                    font-weight: 900;
                }

                .sf-info-list {
                    padding: 4px 18px 18px;
                    display: grid;
                    gap: 14px;
                }

                .sf-info-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                }

                .sf-info-label {
                    color: #94a3b8;
                    font-size: 13px;
                    font-weight: 700;
                }

                .sf-info-value {
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: 900;
                    text-align: right;
                }

                .sf-outline-btn {
                    width: calc(100% - 36px);
                    margin: 0 18px 18px;
                    height: 44px;
                    border: 1px solid rgba(249, 115, 22, 0.34);
                    border-radius: 12px;
                    background: rgba(249, 115, 22, 0.10);
                    color: #fdba74;
                    font-size: 13px;
                    font-weight: 900;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    text-decoration: none;
                }

                .sf-summary-list,
                .sf-action-list {
                    padding: 16px 18px 18px;
                    display: grid;
                    gap: 14px;
                }

                .sf-action-btn {
                    width: 100%;
                    height: 42px;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                    border-radius: 12px;
                    background: rgba(15, 23, 42, 0.72);
                    color: #cbd5e1;
                    font-size: 13px;
                    font-weight: 800;
                    text-align: left;
                    padding: 0 14px;
                }

                .sf-action-btn:hover {
                    background: rgba(255, 255, 255, 0.05);
                    border-color: rgba(249, 115, 22, 0.28);
                    color: #fdba74;
                }

                .sf-action-btn.danger {
                    color: #fca5a5;
                }

                .sf-action-btn.danger:hover {
                    border-color: rgba(248, 113, 113, 0.25);
                    background: rgba(239, 68, 68, 0.10);
                    color: #fecaca;
                }

                .sf-mobile-back {
                    display: none;
                }

                @media (max-width: 1400px) {
                    .sf-inbox-frame {
                        grid-template-columns: 360px minmax(0, 1fr);
                    }

                    .sf-right-panel {
                        display: none;
                    }
                }

                @media (max-width: 900px) {
                    .sf-inbox-shell {
                        padding: 0;
                    }

                    .sf-inbox-frame {
                        height: 100%;
                        grid-template-columns: 1fr;
                        gap: 0;
                    }

                    .sf-left-panel,
                    .sf-chat-panel {
                        border-radius: 0;
                    }

                    .sf-left-panel.mobile-hidden,
                    .sf-chat-panel.mobile-hidden {
                        display: none;
                    }

                    .sf-mobile-back {
                        display: inline-flex;
                        border: 0;
                        background: transparent;
                        color: #fdba74;
                        font-size: 18px;
                        font-weight: 900;
                    }

                    .sf-messages {
                        padding: 18px 14px;
                    }

                    .sf-bubble {
                        max-width: 88%;
                    }

                    .sf-ai-tools {
                        flex-wrap: wrap;
                    }

                    .sf-ai-note {
                        width: 100%;
                    }

                    .sf-composer-tabs {
                        overflow-x: auto;
                    }

                    .sf-composer-tab {
                        white-space: nowrap;
                    }

                    .sf-input-actions {
                        align-items: stretch;
                        flex-direction: column;
                        gap: 10px;
                    }

                    .sf-send-group,
                    .sf-send-btn {
                        width: 100%;
                    }
                }
            `}</style>

            <div className="sf-inbox-page">
                <div className="sf-inbox-shell">
                    <div className="sf-inbox-frame">
                        <aside className={`sf-panel sf-left-panel ${selected ? "mobile-hidden" : ""}`}>
                            <div className="sf-left-header">
                                <div>
                                    <div className="sf-title">WhatsApp Inbox</div>
                                    <div className="sf-subtitle">
                                        Manage customer conversations
                                    </div>
                                </div>

                                <button type="button" className="sf-plus-btn">
                                    +
                                </button>
                            </div>

                            <div className="sf-search-area">
                                <div className="sf-search-box">
                                    <span>Search</span>
                                    <input
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Search or start new chat"
                                        autoComplete="off"
                                    />
                                </div>
                            </div>

                            <div className="sf-conversation-list">
                                {conversations.length === 0 && (
                                    <div className="sf-empty-list">
                                        No conversations found.
                                    </div>
                                )}

                                {conversations.map((c) => (
                                    <button
                                        type="button"
                                        key={c.id}
                                        onClick={() => setSelected(c)}
                                        className={`sf-conversation ${
                                            selected?.id === c.id ? "active" : ""
                                        }`}
                                    >
                                        <div className="sf-conv-avatar">
                                            {initials(c.customer_name, c.customer_phone)}
                                        </div>

                                        <div className="sf-conv-body">
                                            <div className="sf-conv-top">
                                                <div className="sf-conv-name">
                                                    {c.customer_name || c.customer_phone || "Unknown"}
                                                </div>

                                                <div className="sf-conv-time">
                                                    {formatTime(c.last_message_at)}
                                                </div>
                                            </div>

                                            <div className="sf-conv-bottom">
                                                <div className="sf-conv-preview">
                                                    {c.last_message_preview || c.customer_phone || "No message preview"}
                                                </div>

                                                {c.unread_count > 0 && (
                                                    <span className="sf-unread">
                                                        {c.unread_count}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </aside>

                        <main className={`sf-panel sf-chat-panel ${!selected ? "mobile-hidden" : ""}`}>
                            {selected ? (
                                <>
                                    <header className="sf-chat-header">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setSelected(null);
                                                    setMessages([]);
                                                    setContext(null);
                                                }}
                                                className="sf-mobile-back"
                                            >
                                                ←
                                            </button>

                                            <div className="sf-chat-avatar">
                                                {initials(selectedName, selectedPhone)}
                                            </div>

                                            <div className="min-w-0">
                                                <div className="sf-chat-name truncate">
                                                    {selectedName}
                                                </div>

                                                <div className="sf-chat-phone truncate">
                                                    {selectedPhone}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3">
                                            <div className="sf-lead-chip">
                                                Lead: {selectedLeadId}
                                            </div>

                                            <button
                                                type="button"
                                                onClick={markRead}
                                                className="sf-menu-dots"
                                                title="Mark read"
                                            >
                                                ✓
                                            </button>
                                        </div>
                                    </header>

                                    <section className="sf-messages">
                                        {loadingMessages ? (
                                            <div className="sf-empty-state">
                                                <div className="sf-empty-card">
                                                    <div className="sf-empty-icon">⏳</div>
                                                    <h2>Loading messages</h2>
                                                    <p>Please wait while we fetch this conversation.</p>
                                                </div>
                                            </div>
                                        ) : messages.length === 0 ? (
                                            <div className="sf-empty-state">
                                                <div className="sf-empty-card">
                                                    <div className="sf-empty-icon">💬</div>
                                                    <h2>No messages yet</h2>
                                                    <p>Start the conversation by typing a reply below.</p>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="sf-date-pill">Today</div>

                                                {messages.map((m) => (
                                                    <div
                                                        key={m.id}
                                                        className={`sf-message-row ${
                                                            m.direction === "out" ? "out" : "in"
                                                        }`}
                                                    >
                                                        <div className="sf-bubble">
                                                            <div>
                                                                {m.body || "[Non-text message received]"}
                                                            </div>

                                                            <div className="sf-message-meta">
                                                                {m.is_ai && (
                                                                    <span className="sf-ai-chip">
                                                                        AI
                                                                    </span>
                                                                )}

                                                                <span>
                                                                    {formatTime(m.created_at)}
                                                                </span>

                                                                {statusLabel(m) && (
                                                                    <span>
                                                                        {" | "}{statusLabel(m)}
                                                                    </span>
                                                                )}

                                                                {m.direction === "out" && (
                                                                    <span>
                                                                        {m.provider_status === "read" ? "Read" : "Sent"}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </>
                                        )}

                                        <div ref={bottomRef}></div>
                                    </section>

                                    <footer className="sf-composer">
                                        <div className="sf-composer-tabs">
                                            <button
                                                type="button"
                                                onClick={() => setComposerTab("ai")}
                                                className={`sf-composer-tab ${composerTab === "ai" ? "active" : ""}`}
                                            >
                                                AI Reply
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => setComposerTab("quick")}
                                                className={`sf-composer-tab ${composerTab === "quick" ? "active" : ""}`}
                                            >
                                                Quick Replies
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() => setComposerTab("template")}
                                                className={`sf-composer-tab ${composerTab === "template" ? "active" : ""}`}
                                            >
                                                Templates
                                            </button>
                                        </div>

                                        {composerTab === "ai" && (
                                            <div className="sf-ai-tools">
                                                <select
                                                    value={tone}
                                                    onChange={(e) => setTone(e.target.value)}
                                                    className="sf-tone-select"
                                                >
                                                    <option value="professional">Professional</option>
                                                    <option value="friendly">Friendly</option>
                                                    <option value="short">Short</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>

                                                <button
                                                    type="button"
                                                    onClick={generateReply}
                                                    disabled={generating}
                                                    className="sf-ai-btn"
                                                >
                                                    {generating ? "Generating..." : "Generate AI Reply"}
                                                </button>

                                                <div className="sf-ai-note">
                                                    AI only drafts. Manager must review before send.
                                                </div>
                                            </div>
                                        )}

                                        {composerTab === "quick" && (
                                            <div className="sf-ai-tools">
                                                <button
                                                    type="button"
                                                    onClick={() => setMessage("Hi, thanks for contacting us. How can we help you today?")}
                                                    className="sf-ai-btn"
                                                >
                                                    Greeting
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => setMessage("Please share your vehicle make, model, and preferred date/time.")}
                                                    className="sf-ai-btn"
                                                >
                                                    Ask Vehicle Details
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => setMessage("Our service manager will contact you shortly.")}
                                                    className="sf-ai-btn"
                                                >
                                                    Manager Follow-up
                                                </button>
                                            </div>
                                        )}

                                        {composerTab === "template" && (
                                            <div className="sf-ai-tools">
                                                <div className="sf-ai-note">
                                                    Template picker can be connected here later. Current flow keeps manual reply logic unchanged.
                                                </div>
                                            </div>
                                        )}

                                        <div className="sf-input-box">
                                            <textarea
                                                value={message}
                                                onChange={(e) => setMessage(e.target.value)}
                                                onKeyDown={(e) => {
                                                    if (e.key === "Enter" && !e.shiftKey) {
                                                        e.preventDefault();
                                                        sendMessage();
                                                    }
                                                }}
                                                placeholder="Type a message... Press Enter to send, Shift+Enter for new line"
                                            />

                                            {sendError && (
                                                <div className="sf-send-error">
                                                    {sendError}
                                                </div>
                                            )}

                                            <div className="sf-input-actions">
                                                <div className="sf-input-icons">
                                                    <button type="button" className="sf-icon-btn">
                                                        Emoji
                                                    </button>

                                                    <button type="button" className="sf-icon-btn">
                                                        Attach
                                                    </button>

                                                    <button type="button" className="sf-icon-btn">
                                                        Image
                                                    </button>
                                                </div>

                                                <div className="sf-send-group">
                                                    <button
                                                        type="button"
                                                        onClick={sendMessage}
                                                        disabled={sending || !message.trim()}
                                                        className="sf-send-btn"
                                                    >
                                                        <span>{sending ? "Sending..." : "Send"}</span>
                                                    </button>

                                                    <button type="button" className="sf-send-extra">
                                                        More
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </footer>
                                </>
                            ) : (
                                <div className="sf-empty-state">
                                    <div className="sf-empty-card">
                                        <div className="sf-empty-icon">MSG</div>
                                        <h2>WhatsApp Inbox</h2>
                                        <p>Select a conversation from the left to view messages and reply from SayaraForce.</p>
                                    </div>
                                </div>
                            )}
                        </main>

                        <aside className="sf-panel sf-right-panel">
                            <div className="sf-right-section">
                                <div className="sf-right-header">
                                    <div className="sf-right-title">Customer Info</div>
                                    <div className="sf-right-subtitle">Conversation context</div>
                                </div>

                                {selected ? (
                                    <>
                                        <div className="sf-profile-top">
                                            <div className="sf-profile-avatar">
                                                {initials(selectedName, selectedPhone)}
                                            </div>

                                            <div className="min-w-0">
                                                <div className="sf-profile-name truncate">
                                                    {selectedName}
                                                </div>

                                                <div className="sf-profile-phone truncate">
                                                    {selectedPhone || "No phone"}
                                                </div>

                                                <div className="sf-status-pill">
                                                    {context?.lead_id ? "Active Lead" : "Not Linked"}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="sf-info-list">
                                            <div className="sf-info-row">
                                                <div className="sf-info-label">Lead ID</div>
                                                <div className="sf-info-value">{selectedLeadId}</div>
                                            </div>

                                            <div className="sf-info-row">
                                                <div className="sf-info-label">Lead Status</div>
                                                <div className="sf-info-value">
                                                    {context?.lead_status || "Not linked"}
                                                </div>
                                            </div>

                                            <div className="sf-info-row">
                                                <div className="sf-info-label">Source</div>
                                                <div className="sf-info-value">Website</div>
                                            </div>

                                            <div className="sf-info-row">
                                                <div className="sf-info-label">Last Contact</div>
                                                <div className="sf-info-value">
                                                    {formatDate(selected?.last_message_at) || "Today"}
                                                </div>
                                            </div>
                                        </div>

                                        <a href={leadProfileUrl} className="sf-outline-btn">
                                            View Full Lead Profile
                                        </a>
                                    </>
                                ) : (
                                    <div className="p-6 text-center text-sm font-semibold text-slate-400">
                                        Select a conversation to view customer details.
                                    </div>
                                )}
                            </div>

                            <div className="sf-right-section">
                                <div className="sf-right-header">
                                    <div className="sf-right-title">Conversation Summary</div>
                                </div>

                                <div className="sf-summary-list">
                                    <div className="sf-info-row">
                                        <div className="sf-info-label">First Contact</div>
                                        <div className="sf-info-value">
                                            {selected?.last_message_at ? formatDate(selected.last_message_at) : "No date"}
                                        </div>
                                    </div>

                                    <div className="sf-info-row">
                                        <div className="sf-info-label">Total Messages</div>
                                        <div className="sf-info-value">{messages.length || 0}</div>
                                    </div>

                                    <div className="sf-info-row">
                                        <div className="sf-info-label">AI Responses</div>
                                        <div className="sf-info-value">
                                            {messages.filter((m) => m.is_ai).length}
                                        </div>
                                    </div>

                                    <div className="sf-info-row">
                                        <div className="sf-info-label">Human Responses</div>
                                        <div className="sf-info-value">
                                            {messages.filter((m) => m.source === "human").length}
                                        </div>
                                    </div>

                                    <div className="sf-info-row">
                                        <div className="sf-info-label">Status</div>
                                        <div className="sf-info-value">
                                            {context?.conversation_state || "Open"}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="sf-right-section">
                                <div className="sf-right-header">
                                    <div className="sf-right-title">Actions</div>
                                </div>

                                <div className="sf-action-list">
                                    <button type="button" onClick={markRead} className="sf-action-btn">
                                        Mark as Read
                                    </button>

                                    <button type="button" className="sf-action-btn">
                                        Assign to Team Member
                                    </button>

                                    <button type="button" className="sf-action-btn danger">
                                        Block Contact
                                    </button>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
