import{r as i,a as p,j as e}from"./app-CaJ51NEH.js";import{A as H}from"./AuthenticatedLayout-CmFIJnZB.js";function q(){const[w,R]=i.useState([]),[n,y]=i.useState([]),[a,N]=i.useState(null),[t,k]=i.useState(null),[d,r]=i.useState(""),[x,E]=i.useState(""),[z,D]=i.useState("professional"),[P,_]=i.useState(!1),[h,S]=i.useState(!1),[g,C]=i.useState(!1),[o,b]=i.useState("ai"),A=i.useRef(null);i.useEffect(()=>{m()},[]),i.useEffect(()=>{const s=setTimeout(()=>{m()},300);return()=>clearTimeout(s)},[x]),i.useEffect(()=>{a&&L(a.id)},[a]),i.useEffect(()=>{var s;(s=A.current)==null||s.scrollIntoView({behavior:"smooth"})},[n]);const m=async()=>{try{const s=await p.get("/admin/inbox/list",{params:{search:x}});R(s.data.conversations||[])}catch(s){console.error("Failed to load conversations",s)}},L=async s=>{_(!0);try{const l=await p.get(`/admin/inbox/messages/${s}`);y(l.data.messages||[]),k(l.data.context||null),m()}catch(l){console.error("Failed to load messages",l)}finally{_(!1)}},T=async()=>{if(!(!d.trim()||!a||h)){S(!0);try{await p.post("/admin/inbox/send",{conversation_id:a.id,message:d.trim()}),r(""),await L(a.id)}catch(s){console.error("Failed to send message",s),alert("Message failed to send. Please check WhatsApp settings/logs.")}finally{S(!1)}}},G=async()=>{if(!(!a||g)){C(!0);try{const s=await p.post("/admin/inbox/suggest-reply",{conversation_id:a.id,tone:z});r(s.data.suggestion||"")}catch(s){console.error("Failed to generate reply",s),alert("Could not generate reply.")}finally{C(!1)}}},I=s=>{if(!s)return"";try{return new Date(s).toLocaleTimeString([],{hour:"2-digit",minute:"2-digit"})}catch{return s}},F=s=>{if(!s)return"";try{return new Date(s).toLocaleDateString([],{day:"2-digit",month:"short"})}catch{return s}},u=(s,l)=>{const v=(s||l||"?").trim();if(!v)return"?";const j=v.split(" ").filter(Boolean);return j.length>=2?`${j[0][0]}${j[1][0]}`.toUpperCase():v[0].toUpperCase()},M=s=>s.is_ai?"AI":s.source==="human"?"Human":s.provider_status||"",c=(t==null?void 0:t.name)||(t==null?void 0:t.lead_name)||(a==null?void 0:a.customer_name)||(a==null?void 0:a.customer_phone)||"Customer",f=(t==null?void 0:t.phone)||(a==null?void 0:a.customer_phone)||"",$=t!=null&&t.lead_id?`L-${String(t.lead_id).padStart(4,"0")}`:"Not linked";return e.jsxs(H,{children:[e.jsx("style",{children:`
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

                .sf-input-icons {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    color: #94a3b8;
                }

                .sf-icon-btn {
                    width: 34px;
                    height: 34px;
                    border: 0;
                    border-radius: 11px;
                    background: transparent;
                    color: #94a3b8;
                    font-size: 17px;
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
                    width: 42px;
                    height: 42px;
                    border: 1px solid rgba(255, 255, 255, 0.10);
                    border-radius: 14px;
                    background: rgba(2, 6, 23, 0.72);
                    color: #fdba74;
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
                }
            `}),e.jsx("div",{className:"sf-inbox-page",children:e.jsx("div",{className:"sf-inbox-shell",children:e.jsxs("div",{className:"sf-inbox-frame",children:[e.jsxs("aside",{className:`sf-panel sf-left-panel ${a?"mobile-hidden":""}`,children:[e.jsxs("div",{className:"sf-left-header",children:[e.jsxs("div",{children:[e.jsx("div",{className:"sf-title",children:"WhatsApp Inbox"}),e.jsx("div",{className:"sf-subtitle",children:"Manage customer conversations"})]}),e.jsx("button",{type:"button",className:"sf-plus-btn",children:"+"})]}),e.jsx("div",{className:"sf-search-area",children:e.jsxs("div",{className:"sf-search-box",children:[e.jsx("span",{children:"⌕"}),e.jsx("input",{value:x,onChange:s=>E(s.target.value),placeholder:"Search or start new chat",autoComplete:"off"})]})}),e.jsxs("div",{className:"sf-conversation-list",children:[w.length===0&&e.jsx("div",{className:"sf-empty-list",children:"No conversations found."}),w.map(s=>e.jsxs("button",{type:"button",onClick:()=>N(s),className:`sf-conversation ${(a==null?void 0:a.id)===s.id?"active":""}`,children:[e.jsx("div",{className:"sf-conv-avatar",children:u(s.customer_name,s.customer_phone)}),e.jsxs("div",{className:"sf-conv-body",children:[e.jsxs("div",{className:"sf-conv-top",children:[e.jsx("div",{className:"sf-conv-name",children:s.customer_name||s.customer_phone||"Unknown"}),e.jsx("div",{className:"sf-conv-time",children:I(s.last_message_at)})]}),e.jsxs("div",{className:"sf-conv-bottom",children:[e.jsx("div",{className:"sf-conv-preview",children:s.last_message_preview||s.customer_phone||"No message preview"}),s.unread_count>0&&e.jsx("span",{className:"sf-unread",children:s.unread_count})]})]})]},s.id))]})]}),e.jsx("main",{className:`sf-panel sf-chat-panel ${a?"":"mobile-hidden"}`,children:a?e.jsxs(e.Fragment,{children:[e.jsxs("header",{className:"sf-chat-header",children:[e.jsxs("div",{className:"flex min-w-0 items-center gap-3",children:[e.jsx("button",{type:"button",onClick:()=>{N(null),y([]),k(null)},className:"sf-mobile-back",children:"←"}),e.jsx("div",{className:"sf-chat-avatar",children:u(c,f)}),e.jsxs("div",{className:"min-w-0",children:[e.jsx("div",{className:"sf-chat-name truncate",children:c}),e.jsx("div",{className:"sf-chat-phone truncate",children:f})]})]}),e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsxs("div",{className:"sf-lead-chip",children:["Lead: ",$]}),e.jsx("button",{type:"button",className:"sf-menu-dots",children:"⋮"})]})]}),e.jsxs("section",{className:"sf-messages",children:[P?e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"⏳"}),e.jsx("h2",{children:"Loading messages"}),e.jsx("p",{children:"Please wait while we fetch this conversation."})]})}):n.length===0?e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"💬"}),e.jsx("h2",{children:"No messages yet"}),e.jsx("p",{children:"Start the conversation by typing a reply below."})]})}):e.jsxs(e.Fragment,{children:[e.jsx("div",{className:"sf-date-pill",children:"Today"}),n.map(s=>e.jsx("div",{className:`sf-message-row ${s.direction==="out"?"out":"in"}`,children:e.jsxs("div",{className:"sf-bubble",children:[e.jsx("div",{children:s.body||"[Non-text message received]"}),e.jsxs("div",{className:"sf-message-meta",children:[s.is_ai&&e.jsx("span",{className:"sf-ai-chip",children:"AI"}),e.jsx("span",{children:I(s.created_at)}),M(s)&&e.jsxs("span",{children:["· ",M(s)]}),s.direction==="out"&&e.jsx("span",{children:s.provider_status==="read"?"✓✓":"✓"})]})]})},s.id))]}),e.jsx("div",{ref:A})]}),e.jsxs("footer",{className:"sf-composer",children:[e.jsxs("div",{className:"sf-composer-tabs",children:[e.jsx("button",{type:"button",onClick:()=>b("ai"),className:`sf-composer-tab ${o==="ai"?"active":""}`,children:"✨ AI Reply"}),e.jsx("button",{type:"button",onClick:()=>b("quick"),className:`sf-composer-tab ${o==="quick"?"active":""}`,children:"⚡ Quick Replies"}),e.jsx("button",{type:"button",onClick:()=>b("template"),className:`sf-composer-tab ${o==="template"?"active":""}`,children:"▣ Templates"})]}),o==="ai"&&e.jsxs("div",{className:"sf-ai-tools",children:[e.jsxs("select",{value:z,onChange:s=>D(s.target.value),className:"sf-tone-select",children:[e.jsx("option",{value:"professional",children:"Professional"}),e.jsx("option",{value:"friendly",children:"Friendly"}),e.jsx("option",{value:"short",children:"Short"}),e.jsx("option",{value:"urgent",children:"Urgent"})]}),e.jsx("button",{type:"button",onClick:G,disabled:g,className:"sf-ai-btn",children:g?"Generating...":"Generate AI Reply"}),e.jsx("div",{className:"sf-ai-note",children:"AI only drafts. Admin must review before send."})]}),o==="quick"&&e.jsxs("div",{className:"sf-ai-tools",children:[e.jsx("button",{type:"button",onClick:()=>r("Hi, thanks for contacting us. How can we help you today?"),className:"sf-ai-btn",children:"Greeting"}),e.jsx("button",{type:"button",onClick:()=>r("Please share your vehicle make, model, and preferred date/time."),className:"sf-ai-btn",children:"Ask Vehicle Details"}),e.jsx("button",{type:"button",onClick:()=>r("Our service manager will contact you shortly."),className:"sf-ai-btn",children:"Manager Follow-up"})]}),o==="template"&&e.jsx("div",{className:"sf-ai-tools",children:e.jsx("div",{className:"sf-ai-note",children:"Template picker can be connected here later. Current flow keeps manual reply logic unchanged."})}),e.jsxs("div",{className:"sf-input-box",children:[e.jsx("textarea",{value:d,onChange:s=>r(s.target.value),onKeyDown:s=>{s.key==="Enter"&&!s.shiftKey&&(s.preventDefault(),T())},placeholder:"Type a message... Press Enter to send, Shift+Enter for new line"}),e.jsxs("div",{className:"sf-input-actions",children:[e.jsxs("div",{className:"sf-input-icons",children:[e.jsx("button",{type:"button",className:"sf-icon-btn",children:"🙂"}),e.jsx("button",{type:"button",className:"sf-icon-btn",children:"📎"}),e.jsx("button",{type:"button",className:"sf-icon-btn",children:"🖼"})]}),e.jsxs("div",{className:"sf-send-group",children:[e.jsxs("button",{type:"button",onClick:T,disabled:h||!d.trim(),className:"sf-send-btn",children:[e.jsx("span",{children:"➤"}),e.jsx("span",{children:h?"Sending...":"Send"})]}),e.jsx("button",{type:"button",className:"sf-send-extra",children:"⌄"})]})]})]})]})]}):e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"💬"}),e.jsx("h2",{children:"WhatsApp Inbox"}),e.jsx("p",{children:"Select a conversation from the left to view messages and reply from SayaraForce."})]})})}),e.jsxs("aside",{className:"sf-panel sf-right-panel",children:[e.jsxs("div",{className:"sf-right-section",children:[e.jsxs("div",{className:"sf-right-header",children:[e.jsx("div",{className:"sf-right-title",children:"Customer Info"}),e.jsx("div",{className:"sf-right-subtitle",children:"Conversation context"})]}),a?e.jsxs(e.Fragment,{children:[e.jsxs("div",{className:"sf-profile-top",children:[e.jsx("div",{className:"sf-profile-avatar",children:u(c,f)}),e.jsxs("div",{className:"min-w-0",children:[e.jsx("div",{className:"sf-profile-name truncate",children:c}),e.jsx("div",{className:"sf-profile-phone truncate",children:f||"No phone"}),e.jsx("div",{className:"sf-status-pill",children:t!=null&&t.lead_id?"Active Lead":"Not Linked"})]})]}),e.jsxs("div",{className:"sf-info-list",children:[e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Lead ID"}),e.jsx("div",{className:"sf-info-value",children:$})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Lead Status"}),e.jsx("div",{className:"sf-info-value",children:(t==null?void 0:t.lead_status)||"Not linked"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Source"}),e.jsx("div",{className:"sf-info-value",children:"Website"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Last Contact"}),e.jsx("div",{className:"sf-info-value",children:F(a==null?void 0:a.last_message_at)||"Today"})]})]}),e.jsx("button",{type:"button",className:"sf-outline-btn",children:"View Full Lead Profile ↗"})]}):e.jsx("div",{className:"p-6 text-center text-sm font-semibold text-slate-400",children:"Select a conversation to view customer details."})]}),e.jsxs("div",{className:"sf-right-section",children:[e.jsx("div",{className:"sf-right-header",children:e.jsx("div",{className:"sf-right-title",children:"Conversation Summary"})}),e.jsxs("div",{className:"sf-summary-list",children:[e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"First Contact"}),e.jsx("div",{className:"sf-info-value",children:a!=null&&a.last_message_at?F(a.last_message_at):"—"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Total Messages"}),e.jsx("div",{className:"sf-info-value",children:n.length||0})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"AI Responses"}),e.jsx("div",{className:"sf-info-value",children:n.filter(s=>s.is_ai).length})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Human Responses"}),e.jsx("div",{className:"sf-info-value",children:n.filter(s=>s.source==="human").length})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Status"}),e.jsx("div",{className:"sf-info-value",children:(t==null?void 0:t.conversation_state)||"Open"})]})]})]}),e.jsxs("div",{className:"sf-right-section",children:[e.jsx("div",{className:"sf-right-header",children:e.jsx("div",{className:"sf-right-title",children:"Actions"})}),e.jsxs("div",{className:"sf-action-list",children:[e.jsx("button",{type:"button",className:"sf-action-btn",children:"✓ Mark as Resolved"}),e.jsx("button",{type:"button",className:"sf-action-btn",children:"👥 Assign to Team Member"}),e.jsx("button",{type:"button",className:"sf-action-btn danger",children:"⊘ Block Contact"})]})]})]})]})})})]})}export{q as default};
