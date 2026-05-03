/**
 * Main JS entry for GarageCRM / SayaraForce
 */

import "./bootstrap";
import { createRoot } from "react-dom/client";

/* ============================
   Chat Window (Existing)
   ============================ */
import ChatWindow from "./Pages/Chat/ChatWindow";

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

/* ============================
   Admin Demo Page
   ============================ */
import DemoPage from "./components/Demo/DemoPage";

function mountAdminDemo() {
    const el = document.getElementById("admin-demo");
    if (!el) return;

    const root = createRoot(el);
    root.render(<DemoPage root={el} />);
}

/* ============================
   Auto mount
   ============================ */
function onReady(fn) {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", fn);
    } else {
        fn();
    }
}

onReady(mountChatWindow);
onReady(mountAdminDemo);
