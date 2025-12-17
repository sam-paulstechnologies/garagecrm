export async function pollMessages(url, sinceId = 0) {
    if (!url) return [];
    const res = await fetch(`${url}?since_id=${sinceId}`, {
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });

    const j = await res.json();
    return j.ok ? j.messages : [];
}

export async function sendMessage(url, text) {
    const res = await fetch(url, {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ message: text }),
    });

    const j = await res.json();
    return j.message || null;
}

export async function fetchSmartReplies(url) {
    if (!url) return [];
    const res = await fetch(url, {
        method: "POST",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
    });

    const j = await res.json();
    return j.suggestions || [];
}
