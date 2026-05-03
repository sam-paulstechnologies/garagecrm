import React, { useEffect, useMemo, useState } from "react";

function Badge({ type, label }) {
  const cls =
    type === "completed" ? "bg-success" :
    type === "paused"    ? "bg-secondary" :
    type === "stuck"     ? "bg-danger" :
    type === "waiting"   ? "bg-warning text-dark" :
    "bg-primary";

  return <span className={`badge ${cls}`}>{label}</span>;
}

function StatCard({ title, value, sub }) {
  return (
    <div className="card shadow-sm">
      <div className="card-body">
        <div className="text-muted small">{title}</div>
        <div className="fs-3 fw-bold">{value ?? "-"}</div>
        {sub ? <div className="text-muted small">{sub}</div> : null}
      </div>
    </div>
  );
}

function ConversationList({ endpoint, activeId, onSelect }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);

  const load = async () => {
    try {
      const res = await fetch(endpoint);
      const data = await res.json();
      if (data.ok) setItems(data.conversations || []);
    } catch (e) {
      console.error("conv list load error", e);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    const t = setInterval(load, 5000);
    return () => clearInterval(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [endpoint]);

  if (loading) return <div className="p-3 text-muted">Loading conversations…</div>;

  return (
    <div className="list-group list-group-flush">
      {items.length === 0 && (
        <div className="p-3 text-muted">No conversations yet.</div>
      )}

      {items.map((c) => (
        <button
          key={c.id}
          className={`list-group-item list-group-item-action d-flex justify-content-between align-items-start ${
            activeId === c.id ? "active" : ""
          }`}
          onClick={() => onSelect(c.id)}
          type="button"
        >
          <div className="me-2">
            <div className="fw-semibold">{c.customer_name || "Customer"}</div>
            <div className={`small ${activeId === c.id ? "text-white-50" : "text-muted"}`}>
              {c.last_message_preview || c.customer_phone}
            </div>
          </div>

          {c.unread_count > 0 && (
            <span className={`badge rounded-pill ${activeId === c.id ? "bg-light text-dark" : "bg-primary"}`}>
              {c.unread_count}
            </span>
          )}
        </button>
      ))}
    </div>
  );
}

function ChatWindowDemo({ cid, endpoints }) {
  const [messages, setMessages] = useState([]);
  const [smartReplies, setSmartReplies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [text, setText] = useState("");

  const csrf = document.querySelector(`meta[name="csrf-token"]`)?.content;

  const api = useMemo(() => {
    const rep = (u) => (u || "").replace("__CID__", String(cid));
    return {
      messages: rep(endpoints.messages),
      send: rep(endpoints.send),
      smart: rep(endpoints.smart),
      markRead: rep(endpoints.markRead),
    };
  }, [cid, endpoints]);

  const loadMessages = async (sinceId = 0) => {
    try {
      const url = sinceId ? `${api.messages}?since_id=${sinceId}` : api.messages;
      const res = await fetch(url);
      const data = await res.json();
      if (data.ok) {
        if (sinceId) {
          if (data.messages?.length) {
            setMessages((prev) => [...prev, ...data.messages]);
          }
        } else {
          setMessages(data.messages || []);
        }
      }
    } catch (e) {
      console.error("messages load error", e);
    } finally {
      setLoading(false);
    }
  };

  const markRead = async () => {
    if (!csrf) return;
    try {
      await fetch(api.markRead, { method: "POST", headers: { "X-CSRF-TOKEN": csrf } });
    } catch {}
  };

  const send = async () => {
    const msg = (text || "").trim();
    if (!msg) return;

    try {
      const res = await fetch(api.send, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(csrf ? { "X-CSRF-TOKEN": csrf } : {}),
        },
        body: JSON.stringify({ message: msg }),
      });

      const data = await res.json();
      if (data.ok && data.message) {
        setMessages((prev) => [...prev, data.message]);
        setText("");
      }
    } catch (e) {
      console.error("send error", e);
    }
  };

  const loadSmartReplies = async () => {
    if (!csrf) return;
    try {
      const res = await fetch(api.smart, { method: "POST", headers: { "X-CSRF-TOKEN": csrf } });
      const data = await res.json();
      if (data.ok) setSmartReplies(data.suggestions || []);
    } catch (e) {
      console.error("smart replies error", e);
    }
  };

  useEffect(() => {
    if (!cid) return;

    setMessages([]);
    setSmartReplies([]);
    setText("");
    setLoading(true);

    loadMessages(0).then(markRead);

    const poll = setInterval(async () => {
      const lastId = messages.length ? messages[messages.length - 1].id : 0;
      await loadMessages(lastId);
      await markRead();
    }, 2500);

    return () => clearInterval(poll);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [cid]);

  return (
    <div className="card shadow-sm h-100">
      <div className="card-header d-flex justify-content-between align-items-center">
        <div className="fw-semibold">Conversation #{cid}</div>
        <button className="btn btn-sm btn-outline-secondary" onClick={loadSmartReplies} type="button">
          Load smart replies
        </button>
      </div>

      <div className="card-body d-flex flex-column" style={{ minHeight: 520 }}>
        <div className="flex-grow-1 overflow-auto border rounded p-3 bg-light">
          {loading && <div className="text-muted">Loading…</div>}
          {!loading && messages.length === 0 && <div className="text-muted">No messages yet.</div>}

          {messages.map((m) => (
            <div
              key={m.id}
              className={`d-flex mb-2 ${m.direction === "out" ? "justify-content-end" : "justify-content-start"}`}
            >
              <div
                className={`px-3 py-2 rounded ${
                  m.direction === "out" ? "bg-primary text-white" : "bg-white border"
                }`}
                style={{ maxWidth: "80%" }}
              >
                <div className="small">{m.body}</div>
                <div className={`small mt-1 ${m.direction === "out" ? "text-white-50" : "text-muted"}`}>
                  {m.created_at ? new Date(m.created_at).toLocaleString() : ""}
                  {m.is_ai ? " • AI" : ""}
                </div>
              </div>
            </div>
          ))}
        </div>

        {smartReplies?.length > 0 && (
          <div className="mt-2 d-flex flex-wrap gap-2">
            {smartReplies.map((s, idx) => (
              <button
                key={idx}
                className="btn btn-sm btn-outline-primary"
                onClick={() => setText(s.text)}
                type="button"
              >
                {s.text}
              </button>
            ))}
          </div>
        )}

        <div className="mt-3 d-flex gap-2">
          <input
            className="form-control"
            placeholder="Type a reply…"
            value={text}
            onChange={(e) => setText(e.target.value)}
            onKeyDown={(e) => (e.key === "Enter" ? send() : null)}
          />
          <button className="btn btn-primary" onClick={send} type="button">
            Send
          </button>
        </div>
      </div>
    </div>
  );
}

export default function DemoPage({ root }) {
  const endpointMetrics = root.dataset.endpointMetrics;
  const endpointAudiences = root.dataset.endpointAudiences;
  const endpointEnrollments = root.dataset.endpointEnrollments;

  const endpointConvList = root.dataset.endpointConvList;

  const endpointsChat = {
    messages: root.dataset.chatMessages,
    send: root.dataset.chatSend,
    smart: root.dataset.chatSmart,
    markRead: root.dataset.chatMarkread,
  };

  const [metrics, setMetrics] = useState(null);
  const [audiences, setAudiences] = useState([]);
  const [enrollments, setEnrollments] = useState([]);
  const [activeCid, setActiveCid] = useState(null);

  const loadMetrics = async () => {
    try {
      const res = await fetch(endpointMetrics);
      const data = await res.json();
      if (data.ok) setMetrics(data.metrics);
    } catch (e) {
      console.error("metrics error", e);
    }
  };

  const loadAudiences = async () => {
    try {
      const res = await fetch(endpointAudiences);
      const data = await res.json();
      if (data.ok) setAudiences(data.audiences || []);
    } catch (e) {
      console.error("audiences error", e);
    }
  };

  const loadEnrollments = async () => {
    try {
      const res = await fetch(endpointEnrollments);
      const data = await res.json();
      if (data.ok) setEnrollments(data.enrollments || []);
    } catch (e) {
      console.error("enrollments error", e);
    }
  };

  useEffect(() => {
    loadMetrics();
    loadAudiences();
    loadEnrollments();

    const t = setInterval(loadMetrics, 8000);
    return () => clearInterval(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="row g-3">
      {/* Top Metrics */}
      <div className="col-12">
        <div className="row g-3">
          <div className="col-md-3">
            <StatCard title="Conversations" value={metrics?.conversations_total} />
          </div>
          <div className="col-md-3">
            <StatCard title="Unread messages" value={metrics?.unread_total} />
          </div>
          <div className="col-md-3">
            <StatCard title="Audiences active" value={metrics?.audiences_total} />
          </div>
          <div className="col-md-3">
            <StatCard title="Journeys active" value={metrics?.journeys_active} sub={`Enrollments: ${metrics?.enrollments_active ?? "-"}`} />
          </div>
        </div>
      </div>

      {/* Inbox + Chat */}
      <div className="col-lg-4">
        <div className="card shadow-sm h-100">
          <div className="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Unified Inbox</span>
            <button className="btn btn-sm btn-outline-secondary" type="button" onClick={() => window.location.reload()}>
              Refresh
            </button>
          </div>
          <ConversationList endpoint={endpointConvList} activeId={activeCid} onSelect={setActiveCid} />
        </div>
      </div>

      <div className="col-lg-8">
        {activeCid ? (
          <ChatWindowDemo cid={activeCid} endpoints={endpointsChat} />
        ) : (
          <div className="card shadow-sm h-100">
            <div className="card-body d-flex align-items-center justify-content-center text-muted" style={{ minHeight: 560 }}>
              Select a conversation to view messages.
            </div>
          </div>
        )}
      </div>

      {/* Audiences */}
      <div className="col-lg-6">
        <div className="card shadow-sm">
          <div className="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Audiences</span>
            <a className="btn btn-sm btn-outline-success" href="/admin/audiences">Open</a>
          </div>
          <div className="card-body">
            {audiences.length === 0 ? (
              <div className="text-muted">No audiences found.</div>
            ) : (
              <div className="table-responsive">
                <table className="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th className="text-end">Members</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {audiences.map((a) => (
                      <tr key={a.id}>
                        <td>
                          {a.is_system ? <span className="badge bg-dark me-2">System</span> : null}
                          {a.name}
                        </td>
                        <td className="text-end fw-semibold">{a.count}</td>
                        <td className="text-end">
                          <a className="btn btn-sm btn-outline-primary" href={a.url}>View</a>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Journeys */}
      <div className="col-lg-6">
        <div className="card shadow-sm">
          <div className="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span>Recent Journey Enrollments</span>
            <button className="btn btn-sm btn-outline-secondary" type="button" onClick={loadEnrollments}>
              Refresh
            </button>
          </div>
          <div className="card-body">
            {enrollments.length === 0 ? (
              <div className="text-muted">No enrollments yet.</div>
            ) : (
              <div className="table-responsive">
                <table className="table table-sm align-middle">
                  <thead>
                    <tr>
                      <th>Journey</th>
                      <th>Step</th>
                      <th>Status</th>
                      <th>Health</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    {enrollments.map((e) => (
                      <tr key={e.id}>
                        <td className="fw-semibold">{e.journey}</td>
                        <td>{e.step}</td>
                        <td>{e.status}</td>
                        <td>
                          <Badge type={e.health?.badge} label={e.health?.label || "On Track"} />
                        </td>
                        <td className="text-end">
                          <a className="btn btn-sm btn-outline-primary" href={e.timeline_url}>
                            Timeline
                          </a>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
            <div className="text-muted small mt-2">
              Timeline shows automation logs + WhatsApp + manual actions in one view.
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
