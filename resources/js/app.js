/**
 * Main JS entry for GarageCRM / SayaraForce
 */

import "./bootstrap";
import { createRoot } from "react-dom/client";
import ChatWindow from "./Pages/Chat/ChatWindow";

/**
 * Mount React Chat Window
 */
function mountChatWindow() {
    const el = document.getElementById("chat-window");
    if (!el) return;

    const root = createRoot(el);

    root.render(
        <ChatWindow
            conversationId={el.dataset.conversation || ""}
            endpointMessages={el.dataset.endpointMessages || ""}
            endpointSend={el.dataset.endpointSend || ""}
            endpointSmartReplies={el.dataset.endpointSmartReplies || ""}
        />
    );
}

/**
 * Auto-mount when ready
 */
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mountChatWindow);
} else {
    mountChatWindow();
}
