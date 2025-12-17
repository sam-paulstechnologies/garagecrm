import { createRoot } from "react-dom/client";
import ChatApp from "./ChatApp";

document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById("chat-root");
    if (!el) return;

    const conversationId = el.dataset.activeConversationId || null;
    const initialMessages = JSON.parse(el.dataset.initialMessages || "[]");
    const endpointMessages = el.dataset.endpointMessages || "";
    const endpointSend = el.dataset.endpointSend || "";
    const endpointSmartReplies = el.dataset.endpointSmartReplies || "";

    const root = createRoot(el);
    root.render(
        <ChatApp
            conversationId={conversationId}
            initialMessages={initialMessages}
            endpointMessages={endpointMessages}
            endpointSend={endpointSend}
            endpointSmartReplies={endpointSmartReplies}
        />
    );
});
