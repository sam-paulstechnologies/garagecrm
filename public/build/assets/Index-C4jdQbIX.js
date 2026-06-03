import{r as t,a as p,j as e}from"./app-DT1bVV2L.js";import{A as H}from"./AuthenticatedLayout-C5slzAvg.js";function q(){const[j,R]=t.useState([]),[n,y]=t.useState([]),[s,N]=t.useState(null),[o,k]=t.useState(null),[l,i]=t.useState(""),[b,E]=t.useState(""),[z,D]=t.useState("professional"),[P,_]=t.useState(!1),[x,S]=t.useState(!1),[g,C]=t.useState(!1),[r,h]=t.useState("ai"),A=t.useRef(null);t.useEffect(()=>{m()},[]),t.useEffect(()=>{const a=setTimeout(()=>{m()},300);return()=>clearTimeout(a)},[b]),t.useEffect(()=>{s&&L(s.id)},[s]),t.useEffect(()=>{var a;(a=A.current)==null||a.scrollIntoView({behavior:"smooth"})},[n]);const m=async()=>{try{const a=await p.get("/admin/inbox/list",{params:{search:b}});R(a.data.conversations||[])}catch(a){console.error("Failed to load conversations",a)}},L=async a=>{_(!0);try{const f=await p.get(`/admin/inbox/messages/${a}`);y(f.data.messages||[]),k(f.data.context||null),m()}catch(f){console.error("Failed to load messages",f)}finally{_(!1)}},T=async()=>{if(!(!l.trim()||!s||x)){S(!0);try{await p.post("/admin/inbox/send",{conversation_id:s.id,message:l.trim()}),i(""),await L(s.id)}catch(a){console.error("Failed to send message",a),alert("Message failed to send. Please check WhatsApp settings/logs.")}finally{S(!1)}}},G=async()=>{if(!(!s||g)){C(!0);try{const a=await p.post("/admin/inbox/suggest-reply",{conversation_id:s.id,tone:z});i(a.data.suggestion||"")}catch(a){console.error("Failed to generate reply",a),alert("Could not generate reply.")}finally{C(!1)}}},I=a=>{if(!a)return"";try{return new Date(a).toLocaleTimeString([],{hour:"2-digit",minute:"2-digit"})}catch{return a}},F=a=>{if(!a)return"";try{return new Date(a).toLocaleDateString([],{day:"2-digit",month:"short"})}catch{return a}},u=(a,f)=>{const v=(a||f||"?").trim();if(!v)return"?";const w=v.split(" ").filter(Boolean);return w.length>=2?`${w[0][0]}${w[1][0]}`.toUpperCase():v[0].toUpperCase()},M=a=>a.is_ai?"AI":a.source==="human"?"Human":a.provider_status||"",d=(o==null?void 0:o.name)||(o==null?void 0:o.lead_name)||(s==null?void 0:s.customer_name)||(s==null?void 0:s.customer_phone)||"Customer",c=(o==null?void 0:o.phone)||(s==null?void 0:s.customer_phone)||"",$=o!=null&&o.lead_id?`L-${String(o.lead_id).padStart(4,"0")}`:"Not linked";return e.jsxs(H,{children:[e.jsx("style",{children:`
                .sf-inbox-page {
                    --sf-inbox-page-bg:
                        radial-gradient(circle at top right, rgba(255, 122, 26, 0.10), transparent 30%),
                        radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 32%),
                        #050914;
                    --sf-inbox-panel: rgba(11, 18, 32, 0.96);
                    --sf-inbox-panel-soft: rgba(8, 17, 31, 0.78);
                    --sf-inbox-panel-deep: #08111f;
                    --sf-inbox-border: #1e293b;
                    --sf-inbox-border-soft: rgba(148, 163, 184, 0.14);
                    --sf-inbox-text: #f1f5f9;
                    --sf-inbox-heading: #ffffff;
                    --sf-inbox-muted: #94a3b8;
                    --sf-inbox-muted-strong: #cbd5e1;
                    --sf-inbox-orange: #ff7a1a;
                    --sf-inbox-orange-hover: #ea6508;
                    --sf-inbox-blue-soft: rgba(59, 130, 246, 0.16);
                    --sf-inbox-green-soft: rgba(34, 197, 94, 0.14);
                    --sf-inbox-danger-soft: rgba(239, 68, 68, 0.10);
                    --sf-inbox-shadow: 0 24px 45px rgba(0, 0, 0, 0.28);
                    height: calc(100dvh - 64px);
                    min-height: 0;
                    overflow: hidden;
                    background: var(--sf-inbox-page-bg);
                    color: var(--sf-inbox-text);
                }

                html[data-theme="light"] .sf-inbox-page {
                    --sf-inbox-page-bg:
                        radial-gradient(circle at top right, rgba(255, 122, 26, 0.11), transparent 30%),
                        radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 34%),
                        #f3f6fb;
                    --sf-inbox-panel: #ffffff;
                    --sf-inbox-panel-soft: #f8fafc;
                    --sf-inbox-panel-deep: #eef4fb;
                    --sf-inbox-border: #dbe3ef;
                    --sf-inbox-border-soft: #e5edf7;
                    --sf-inbox-text: #0f172a;
                    --sf-inbox-heading: #020617;
                    --sf-inbox-muted: #64748b;
                    --sf-inbox-muted-strong: #334155;
                    --sf-inbox-blue-soft: #eff6ff;
                    --sf-inbox-green-soft: #ecfdf5;
                    --sf-inbox-danger-soft: #fef2f2;
                    --sf-inbox-shadow: 0 20px 45px rgba(15, 23, 42, 0.10);
                }

                .sf-inbox-shell {
                    height: 100%;
                    padding: 16px 24px 20px;
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
                    border: 1px solid var(--sf-inbox-border);
                    background: var(--sf-inbox-panel);
                    box-shadow: var(--sf-inbox-shadow);
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
                    border-bottom: 1px solid var(--sf-inbox-border);
                    background: var(--sf-inbox-panel-soft);
                }

                .sf-title {
                    font-size: 18px;
                    font-weight: 900;
                    color: var(--sf-inbox-heading);
                    letter-spacing: -0.02em;
                }

                .sf-subtitle {
                    margin-top: 4px;
                    color: var(--sf-inbox-muted);
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
                    background: linear-gradient(135deg, var(--sf-inbox-orange), var(--sf-inbox-orange-hover));
                    color: #ffffff;
                    font-size: 24px;
                    line-height: 1;
                    font-weight: 800;
                    box-shadow: 0 14px 26px rgba(255, 122, 26, 0.28);
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

                .sf-inbox-page .sf-search-area,
                .sf-inbox-page .sf-chat-header,
                .sf-inbox-page .sf-composer,
                .sf-inbox-page .sf-right-panel,
                .sf-inbox-page .sf-right-section {
                    border-color: var(--sf-inbox-border);
                    background: var(--sf-inbox-panel-soft);
                }

                .sf-inbox-page .sf-search-box,
                .sf-inbox-page .sf-menu-dots,
                .sf-inbox-page .sf-date-pill,
                .sf-inbox-page .sf-input-box,
                .sf-inbox-page .sf-send-extra,
                .sf-inbox-page .sf-action-btn {
                    border-color: var(--sf-inbox-border);
                    background: var(--sf-inbox-panel-deep);
                    color: var(--sf-inbox-muted-strong);
                }

                .sf-inbox-page .sf-search-box input,
                .sf-inbox-page .sf-input-box textarea {
                    color: var(--sf-inbox-heading);
                }

                .sf-inbox-page .sf-search-box input::placeholder,
                .sf-inbox-page .sf-input-box textarea::placeholder {
                    color: var(--sf-inbox-muted);
                }

                .sf-inbox-page .sf-conv-name,
                .sf-inbox-page .sf-chat-name,
                .sf-inbox-page .sf-empty-card h2,
                .sf-inbox-page .sf-right-title,
                .sf-inbox-page .sf-profile-name,
                .sf-inbox-page .sf-info-value {
                    color: var(--sf-inbox-heading);
                }

                .sf-inbox-page .sf-conv-time,
                .sf-inbox-page .sf-conv-preview,
                .sf-inbox-page .sf-empty-list,
                .sf-inbox-page .sf-chat-phone,
                .sf-inbox-page .sf-empty-card p,
                .sf-inbox-page .sf-message-meta,
                .sf-inbox-page .sf-right-subtitle,
                .sf-inbox-page .sf-profile-phone,
                .sf-inbox-page .sf-info-label,
                .sf-inbox-page .sf-input-icons,
                .sf-inbox-page .sf-ai-note {
                    color: var(--sf-inbox-muted);
                }

                .sf-inbox-page .sf-conversation:hover {
                    background: rgba(148, 163, 184, 0.10);
                }

                .sf-inbox-page .sf-conversation.active {
                    background: linear-gradient(135deg, rgba(255, 122, 26, 0.16), rgba(37, 99, 235, 0.10));
                    box-shadow: inset 3px 0 0 var(--sf-inbox-orange);
                }

                .sf-inbox-page .sf-conv-avatar,
                .sf-inbox-page .sf-chat-avatar,
                .sf-inbox-page .sf-profile-avatar {
                    background: rgba(148, 163, 184, 0.16);
                    color: var(--sf-inbox-muted-strong);
                }

                .sf-inbox-page .sf-conversation.active .sf-conv-avatar,
                .sf-inbox-page .sf-empty-icon,
                .sf-inbox-page .sf-send-extra,
                .sf-inbox-page .sf-mobile-back {
                    color: var(--sf-inbox-orange);
                }

                .sf-inbox-page .sf-unread,
                .sf-inbox-page .sf-send-btn {
                    background: linear-gradient(135deg, var(--sf-inbox-orange), var(--sf-inbox-orange-hover));
                    color: #ffffff;
                }

                .sf-inbox-page .sf-lead-chip,
                .sf-inbox-page .sf-ai-chip {
                    background: var(--sf-inbox-blue-soft);
                    color: #93c5fd;
                    border-color: rgba(96, 165, 250, 0.22);
                }

                .sf-inbox-page .sf-status-pill {
                    background: var(--sf-inbox-green-soft);
                    color: #86efac;
                    border-color: rgba(34, 197, 94, 0.20);
                }

                .sf-inbox-page .sf-outline-btn {
                    background: rgba(255, 122, 26, 0.10);
                    border-color: rgba(255, 122, 26, 0.36);
                    color: #fdba74;
                }

                .sf-inbox-page .sf-action-btn:hover,
                .sf-inbox-page .sf-icon-btn:hover {
                    background: rgba(255, 122, 26, 0.10);
                    border-color: rgba(255, 122, 26, 0.32);
                    color: var(--sf-inbox-orange);
                }

                .sf-inbox-page .sf-action-btn.danger {
                    color: #fca5a5;
                }

                .sf-inbox-page .sf-action-btn.danger:hover {
                    background: var(--sf-inbox-danger-soft);
                    color: #fecaca;
                }

                .sf-inbox-page .sf-messages {
                    background-color: #0b1220;
                    border-color: var(--sf-inbox-border);
                }

                .sf-inbox-page .sf-empty-card {
                    border-color: var(--sf-inbox-border);
                    background: var(--sf-inbox-panel);
                    box-shadow: var(--sf-inbox-shadow);
                }

                .sf-inbox-page .sf-message-row.in .sf-bubble {
                    background: var(--sf-inbox-panel);
                    border-color: var(--sf-inbox-border);
                    color: var(--sf-inbox-text);
                }

                .sf-inbox-page .sf-message-row.out .sf-bubble {
                    background: linear-gradient(135deg, rgba(255, 122, 26, 0.24), rgba(37, 99, 235, 0.16));
                    border-color: rgba(255, 122, 26, 0.24);
                    color: var(--sf-inbox-text);
                }

                .sf-inbox-page .sf-composer-tabs {
                    border-color: var(--sf-inbox-border);
                    background: var(--sf-inbox-panel-deep);
                }

                .sf-inbox-page .sf-composer-tab {
                    color: var(--sf-inbox-muted);
                }

                .sf-inbox-page .sf-composer-tab.active {
                    color: var(--sf-inbox-heading);
                }

                .sf-inbox-page .sf-tone-select,
                .sf-inbox-page .sf-ai-btn {
                    border-color: var(--sf-inbox-border);
                    background: var(--sf-inbox-panel-deep);
                    color: var(--sf-inbox-heading);
                }

                .sf-inbox-page .sf-ai-btn:hover {
                    border-color: rgba(255, 122, 26, 0.36);
                    color: var(--sf-inbox-orange);
                }

                html[data-theme="light"] .sf-inbox-page .sf-panel {
                    background: #ffffff;
                }

                html[data-theme="light"] .sf-inbox-page .sf-search-area,
                html[data-theme="light"] .sf-inbox-page .sf-chat-header,
                html[data-theme="light"] .sf-inbox-page .sf-composer,
                html[data-theme="light"] .sf-inbox-page .sf-right-panel,
                html[data-theme="light"] .sf-inbox-page .sf-right-section {
                    background: #ffffff;
                }

                html[data-theme="light"] .sf-inbox-page .sf-left-header,
                html[data-theme="light"] .sf-inbox-page .sf-search-area {
                    background: #f8fafc;
                }

                html[data-theme="light"] .sf-inbox-page .sf-search-box,
                html[data-theme="light"] .sf-inbox-page .sf-menu-dots,
                html[data-theme="light"] .sf-inbox-page .sf-date-pill,
                html[data-theme="light"] .sf-inbox-page .sf-input-box,
                html[data-theme="light"] .sf-inbox-page .sf-send-extra,
                html[data-theme="light"] .sf-inbox-page .sf-action-btn,
                html[data-theme="light"] .sf-inbox-page .sf-tone-select,
                html[data-theme="light"] .sf-inbox-page .sf-ai-btn {
                    background: #f8fafc;
                    border-color: #dbe3ef;
                    color: #334155;
                    box-shadow: none;
                }

                html[data-theme="light"] .sf-inbox-page .sf-conversation:hover {
                    background: #f1f5f9;
                }

                html[data-theme="light"] .sf-inbox-page .sf-conversation.active {
                    background: linear-gradient(135deg, rgba(255, 122, 26, 0.14), rgba(59, 130, 246, 0.10));
                }

                html[data-theme="light"] .sf-inbox-page .sf-conv-avatar,
                html[data-theme="light"] .sf-inbox-page .sf-chat-avatar,
                html[data-theme="light"] .sf-inbox-page .sf-profile-avatar {
                    background: #eaf1f8;
                    color: #334155;
                }

                html[data-theme="light"] .sf-inbox-page .sf-conversation.active .sf-conv-avatar,
                html[data-theme="light"] .sf-inbox-page .sf-empty-icon {
                    background: #fff7ed;
                    color: #ea6508;
                }

                html[data-theme="light"] .sf-inbox-page .sf-messages {
                    background-color: #eef4fb;
                    background-image:
                        radial-gradient(circle at 16px 16px, rgba(255, 122, 26, 0.11) 1.1px, transparent 1.8px),
                        radial-gradient(circle at 42px 38px, rgba(59, 130, 246, 0.10) 1.1px, transparent 1.8px);
                    background-size: 58px 58px, 58px 58px;
                }

                html[data-theme="light"] .sf-inbox-page .sf-empty-card,
                html[data-theme="light"] .sf-inbox-page .sf-message-row.in .sf-bubble {
                    background: #ffffff;
                    border-color: #dbe3ef;
                    color: #0f172a;
                    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
                }

                html[data-theme="light"] .sf-inbox-page .sf-message-row.out .sf-bubble {
                    background: #fff7ed;
                    border-color: rgba(255, 122, 26, 0.30);
                    color: #0f172a;
                    box-shadow: 0 14px 28px rgba(249, 115, 22, 0.10);
                }

                html[data-theme="light"] .sf-inbox-page .sf-message-meta {
                    color: #64748b;
                }

                html[data-theme="light"] .sf-inbox-page .sf-lead-chip,
                html[data-theme="light"] .sf-inbox-page .sf-ai-chip {
                    background: #eff6ff;
                    border-color: #bfdbfe;
                    color: #1d4ed8;
                }

                html[data-theme="light"] .sf-inbox-page .sf-status-pill {
                    background: #ecfdf5;
                    border-color: #bbf7d0;
                    color: #047857;
                }

                html[data-theme="light"] .sf-inbox-page .sf-outline-btn {
                    background: #fff7ed;
                    border-color: rgba(255, 122, 26, 0.34);
                    color: #c2410c;
                }

                html[data-theme="light"] .sf-inbox-page .sf-action-btn:hover,
                html[data-theme="light"] .sf-inbox-page .sf-icon-btn:hover,
                html[data-theme="light"] .sf-inbox-page .sf-ai-btn:hover {
                    background: #fff7ed;
                    color: #c2410c;
                }

                html[data-theme="light"] .sf-inbox-page .sf-action-btn.danger {
                    color: #b91c1c;
                }

                html[data-theme="light"] .sf-inbox-page .sf-action-btn.danger:hover {
                    background: #fef2f2;
                    border-color: #fecaca;
                    color: #991b1b;
                }

                html[data-theme="light"] .sf-inbox-page .sf-send-extra,
                html[data-theme="light"] .sf-inbox-page .sf-mobile-back {
                    color: #c2410c;
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
            `}),e.jsx("div",{className:"sf-inbox-page",children:e.jsx("div",{className:"sf-inbox-shell",children:e.jsxs("div",{className:"sf-inbox-frame",children:[e.jsxs("aside",{className:`sf-panel sf-left-panel ${s?"mobile-hidden":""}`,children:[e.jsxs("div",{className:"sf-left-header",children:[e.jsxs("div",{children:[e.jsx("div",{className:"sf-title",children:"WhatsApp Inbox"}),e.jsx("div",{className:"sf-subtitle",children:"Manage customer conversations"})]}),e.jsx("button",{type:"button",className:"sf-plus-btn",children:"+"})]}),e.jsx("div",{className:"sf-search-area",children:e.jsxs("div",{className:"sf-search-box",children:[e.jsx("span",{children:"⌕"}),e.jsx("input",{value:b,onChange:a=>E(a.target.value),placeholder:"Search or start new chat",autoComplete:"off"})]})}),e.jsxs("div",{className:"sf-conversation-list",children:[j.length===0&&e.jsx("div",{className:"sf-empty-list",children:"No conversations found."}),j.map(a=>e.jsxs("button",{type:"button",onClick:()=>N(a),className:`sf-conversation ${(s==null?void 0:s.id)===a.id?"active":""}`,children:[e.jsx("div",{className:"sf-conv-avatar",children:u(a.customer_name,a.customer_phone)}),e.jsxs("div",{className:"sf-conv-body",children:[e.jsxs("div",{className:"sf-conv-top",children:[e.jsx("div",{className:"sf-conv-name",children:a.customer_name||a.customer_phone||"Unknown"}),e.jsx("div",{className:"sf-conv-time",children:I(a.last_message_at)})]}),e.jsxs("div",{className:"sf-conv-bottom",children:[e.jsx("div",{className:"sf-conv-preview",children:a.last_message_preview||a.customer_phone||"No message preview"}),a.unread_count>0&&e.jsx("span",{className:"sf-unread",children:a.unread_count})]})]})]},a.id))]})]}),e.jsx("main",{className:`sf-panel sf-chat-panel ${s?"":"mobile-hidden"}`,children:s?e.jsxs(e.Fragment,{children:[e.jsxs("header",{className:"sf-chat-header",children:[e.jsxs("div",{className:"flex min-w-0 items-center gap-3",children:[e.jsx("button",{type:"button",onClick:()=>{N(null),y([]),k(null)},className:"sf-mobile-back",children:"←"}),e.jsx("div",{className:"sf-chat-avatar",children:u(d,c)}),e.jsxs("div",{className:"min-w-0",children:[e.jsx("div",{className:"sf-chat-name truncate",children:d}),e.jsx("div",{className:"sf-chat-phone truncate",children:c})]})]}),e.jsxs("div",{className:"flex items-center gap-3",children:[e.jsxs("div",{className:"sf-lead-chip",children:["Lead: ",$]}),e.jsx("button",{type:"button",className:"sf-menu-dots",children:"⋮"})]})]}),e.jsxs("section",{className:"sf-messages",children:[P?e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"⏳"}),e.jsx("h2",{children:"Loading messages"}),e.jsx("p",{children:"Please wait while we fetch this conversation."})]})}):n.length===0?e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"💬"}),e.jsx("h2",{children:"No messages yet"}),e.jsx("p",{children:"Start the conversation by typing a reply below."})]})}):e.jsxs(e.Fragment,{children:[e.jsx("div",{className:"sf-date-pill",children:"Today"}),n.map(a=>e.jsx("div",{className:`sf-message-row ${a.direction==="out"?"out":"in"}`,children:e.jsxs("div",{className:"sf-bubble",children:[e.jsx("div",{children:a.body||"[Non-text message received]"}),e.jsxs("div",{className:"sf-message-meta",children:[a.is_ai&&e.jsx("span",{className:"sf-ai-chip",children:"AI"}),e.jsx("span",{children:I(a.created_at)}),M(a)&&e.jsxs("span",{children:["· ",M(a)]}),a.direction==="out"&&e.jsx("span",{children:a.provider_status==="read"?"✓✓":"✓"})]})]})},a.id))]}),e.jsx("div",{ref:A})]}),e.jsxs("footer",{className:"sf-composer",children:[e.jsxs("div",{className:"sf-composer-tabs",children:[e.jsx("button",{type:"button",onClick:()=>h("ai"),className:`sf-composer-tab ${r==="ai"?"active":""}`,children:"✨ AI Reply"}),e.jsx("button",{type:"button",onClick:()=>h("quick"),className:`sf-composer-tab ${r==="quick"?"active":""}`,children:"⚡ Quick Replies"}),e.jsx("button",{type:"button",onClick:()=>h("template"),className:`sf-composer-tab ${r==="template"?"active":""}`,children:"▣ Templates"})]}),r==="ai"&&e.jsxs("div",{className:"sf-ai-tools",children:[e.jsxs("select",{value:z,onChange:a=>D(a.target.value),className:"sf-tone-select",children:[e.jsx("option",{value:"professional",children:"Professional"}),e.jsx("option",{value:"friendly",children:"Friendly"}),e.jsx("option",{value:"short",children:"Short"}),e.jsx("option",{value:"urgent",children:"Urgent"})]}),e.jsx("button",{type:"button",onClick:G,disabled:g,className:"sf-ai-btn",children:g?"Generating...":"Generate AI Reply"}),e.jsx("div",{className:"sf-ai-note",children:"AI only drafts. Admin must review before send."})]}),r==="quick"&&e.jsxs("div",{className:"sf-ai-tools",children:[e.jsx("button",{type:"button",onClick:()=>i("Hi, thanks for contacting us. How can we help you today?"),className:"sf-ai-btn",children:"Greeting"}),e.jsx("button",{type:"button",onClick:()=>i("Please share your vehicle make, model, and preferred date/time."),className:"sf-ai-btn",children:"Ask Vehicle Details"}),e.jsx("button",{type:"button",onClick:()=>i("Our service manager will contact you shortly."),className:"sf-ai-btn",children:"Manager Follow-up"})]}),r==="template"&&e.jsx("div",{className:"sf-ai-tools",children:e.jsx("div",{className:"sf-ai-note",children:"Template picker can be connected here later. Current flow keeps manual reply logic unchanged."})}),e.jsxs("div",{className:"sf-input-box",children:[e.jsx("textarea",{value:l,onChange:a=>i(a.target.value),onKeyDown:a=>{a.key==="Enter"&&!a.shiftKey&&(a.preventDefault(),T())},placeholder:"Type a message... Press Enter to send, Shift+Enter for new line"}),e.jsxs("div",{className:"sf-input-actions",children:[e.jsxs("div",{className:"sf-input-icons",children:[e.jsx("button",{type:"button",className:"sf-icon-btn",children:"🙂"}),e.jsx("button",{type:"button",className:"sf-icon-btn",children:"📎"}),e.jsx("button",{type:"button",className:"sf-icon-btn",children:"🖼"})]}),e.jsxs("div",{className:"sf-send-group",children:[e.jsxs("button",{type:"button",onClick:T,disabled:x||!l.trim(),className:"sf-send-btn",children:[e.jsx("span",{children:"➤"}),e.jsx("span",{children:x?"Sending...":"Send"})]}),e.jsx("button",{type:"button",className:"sf-send-extra",children:"⌄"})]})]})]})]})]}):e.jsx("div",{className:"sf-empty-state",children:e.jsxs("div",{className:"sf-empty-card",children:[e.jsx("div",{className:"sf-empty-icon",children:"💬"}),e.jsx("h2",{children:"WhatsApp Inbox"}),e.jsx("p",{children:"Select a conversation from the left to view messages and reply from SayaraForce."})]})})}),e.jsxs("aside",{className:"sf-panel sf-right-panel",children:[e.jsxs("div",{className:"sf-right-section",children:[e.jsxs("div",{className:"sf-right-header",children:[e.jsx("div",{className:"sf-right-title",children:"Customer Info"}),e.jsx("div",{className:"sf-right-subtitle",children:"Conversation context"})]}),s?e.jsxs(e.Fragment,{children:[e.jsxs("div",{className:"sf-profile-top",children:[e.jsx("div",{className:"sf-profile-avatar",children:u(d,c)}),e.jsxs("div",{className:"min-w-0",children:[e.jsx("div",{className:"sf-profile-name truncate",children:d}),e.jsx("div",{className:"sf-profile-phone truncate",children:c||"No phone"}),e.jsx("div",{className:"sf-status-pill",children:o!=null&&o.lead_id?"Active Lead":"Not Linked"})]})]}),e.jsxs("div",{className:"sf-info-list",children:[e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Lead ID"}),e.jsx("div",{className:"sf-info-value",children:$})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Lead Status"}),e.jsx("div",{className:"sf-info-value",children:(o==null?void 0:o.lead_status)||"Not linked"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Source"}),e.jsx("div",{className:"sf-info-value",children:"Website"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Last Contact"}),e.jsx("div",{className:"sf-info-value",children:F(s==null?void 0:s.last_message_at)||"Today"})]})]}),e.jsx("button",{type:"button",className:"sf-outline-btn",children:"View Full Lead Profile ↗"})]}):e.jsx("div",{className:"p-6 text-center text-sm font-semibold text-slate-400",children:"Select a conversation to view customer details."})]}),e.jsxs("div",{className:"sf-right-section",children:[e.jsx("div",{className:"sf-right-header",children:e.jsx("div",{className:"sf-right-title",children:"Conversation Summary"})}),e.jsxs("div",{className:"sf-summary-list",children:[e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"First Contact"}),e.jsx("div",{className:"sf-info-value",children:s!=null&&s.last_message_at?F(s.last_message_at):"—"})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Total Messages"}),e.jsx("div",{className:"sf-info-value",children:n.length||0})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"AI Responses"}),e.jsx("div",{className:"sf-info-value",children:n.filter(a=>a.is_ai).length})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Human Responses"}),e.jsx("div",{className:"sf-info-value",children:n.filter(a=>a.source==="human").length})]}),e.jsxs("div",{className:"sf-info-row",children:[e.jsx("div",{className:"sf-info-label",children:"Status"}),e.jsx("div",{className:"sf-info-value",children:(o==null?void 0:o.conversation_state)||"Open"})]})]})]}),e.jsxs("div",{className:"sf-right-section",children:[e.jsx("div",{className:"sf-right-header",children:e.jsx("div",{className:"sf-right-title",children:"Actions"})}),e.jsxs("div",{className:"sf-action-list",children:[e.jsx("button",{type:"button",className:"sf-action-btn",children:"✓ Mark as Resolved"}),e.jsx("button",{type:"button",className:"sf-action-btn",children:"👥 Assign to Team Member"}),e.jsx("button",{type:"button",className:"sf-action-btn danger",children:"⊘ Block Contact"})]})]})]})]})})})]})}export{q as default};
