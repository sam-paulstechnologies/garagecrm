export async function fetchMessages(url, sinceId = 0) {
    const res = await fetch(url + '?since_id=' + sinceId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const json = await res.json();
    if (!json.ok) return [];

    return json.messages || [];
}

export async function sendMessage(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: body })
    });

    const json = await res.json();
    return json.message || null;
}
