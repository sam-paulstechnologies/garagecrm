<script data-ops-graph-renderer="shared">
(() => {
    const root = document.getElementById('ops-root');
    if (!root) return;

    const state = { nodes: [], edges: [], positions: {}, selected: null, scale: 1, hidden: new Set() };
    const canvas = document.getElementById('ops-canvas');
    const edgesSvg = document.getElementById('ops-edges');
    const detail = document.getElementById('ops-detail');
    const minimap = document.getElementById('ops-minimap');
    const metrics = document.getElementById('ops-metrics');
    const search = document.getElementById('ops-search');
    const groupFilter = document.getElementById('ops-group-filter');
    const storageKey = `ops-graph-layout-${root.dataset.view}`;

    function savedPositions() {
        try { return JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (_) { return {}; }
    }

    function persistPositions() {
        localStorage.setItem(storageKey, JSON.stringify(state.positions));
    }

    function initialPosition(node, index, total) {
        const saved = state.positions[node.id];
        if (saved) return saved;
        const cols = root.dataset.view === 'technical_map' ? 6 : 5;
        const x = 70 + (index % cols) * 245;
        const y = 55 + Math.floor(index / cols) * 150;
        if (node.group === 'domain') return { x: 80 + index * 220, y: 40 };
        if (root.dataset.view === 'mind_map') {
            const angle = (index / Math.max(total, 1)) * Math.PI * 2;
            return { x: 560 + Math.cos(angle) * 430, y: 310 + Math.sin(angle) * 250 };
        }
        return { x, y };
    }

    function renderMetrics(data) {
        const m = data.metrics || {};
        metrics.innerHTML = [
            ['Nodes', m.node_count],
            ['Relationships', m.edge_count],
            ['Payload', `${m.payload_bytes || 0} bytes`],
            ['Queries', m.query_count]
        ].map(([k,v]) => `<div class="sa-soft rounded-2xl px-3 py-2"><span class="sa-label">${k}</span><strong class="ml-2">${v}</strong></div>`).join('');
    }

    function renderFilters(filters) {
        (filters.groups || []).forEach(group => {
            const option = document.createElement('option');
            option.value = group;
            option.textContent = group.replace(/-/g, ' ');
            groupFilter.appendChild(option);
        });
    }

    function renderNodes() {
        canvas.querySelectorAll('.ops-node').forEach(node => node.remove());
        state.nodes.forEach((node, index) => {
            const pos = initialPosition(node, index, state.nodes.length);
            state.positions[node.id] = pos;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'ops-node';
            button.dataset.id = node.id;
            button.dataset.group = node.group;
            button.style.left = `${pos.x}px`;
            button.style.top = `${pos.y}px`;
            button.innerHTML = `<small>${escapeHtml(node.group)} · ${escapeHtml(node.section || 'core')}</small><strong>${escapeHtml(node.label)}</strong><span>${escapeHtml(node.summary || '')}</span>`;
            button.addEventListener('click', () => selectNode(node.id));
            makeDraggable(button);
            canvas.appendChild(button);
        });
        drawEdges();
        drawMinimap();
    }

    function drawEdges() {
        const width = Math.max(1280, ...state.nodes.map(n => (state.positions[n.id]?.x || 0) + 260));
        const height = Math.max(720, ...state.nodes.map(n => (state.positions[n.id]?.y || 0) + 150));
        edgesSvg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        edgesSvg.innerHTML = '';
        state.edges.forEach(edge => {
            const source = state.positions[edge.source];
            const target = state.positions[edge.target];
            if (!source || !target) return;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            const sx = source.x + 95, sy = source.y + 44, tx = target.x + 95, ty = target.y + 44;
            const mid = (sx + tx) / 2;
            path.setAttribute('d', `M ${sx} ${sy} C ${mid} ${sy}, ${mid} ${ty}, ${tx} ${ty}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', 'rgba(148, 163, 184, .45)');
            path.setAttribute('stroke-width', '2');
            edgesSvg.appendChild(path);
        });
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
    }

    function drawMinimap() {
        minimap.innerHTML = '';
        state.nodes.forEach(node => {
            const pos = state.positions[node.id];
            if (!pos) return;
            const dot = document.createElement('span');
            dot.className = 'ops-mini-dot';
            dot.style.left = `${Math.max(4, Math.min(180, pos.x / 8))}px`;
            dot.style.top = `${Math.max(4, Math.min(110, pos.y / 6))}px`;
            minimap.appendChild(dot);
        });
    }

    function makeDraggable(el) {
        let start = null;
        el.addEventListener('pointerdown', (event) => {
            if (window.matchMedia('(max-width: 900px)').matches) return;
            start = { x: event.clientX, y: event.clientY, left: parseFloat(el.style.left), top: parseFloat(el.style.top) };
            el.setPointerCapture(event.pointerId);
        });
        el.addEventListener('pointermove', (event) => {
            if (!start) return;
            const x = start.left + (event.clientX - start.x) / state.scale;
            const y = start.top + (event.clientY - start.y) / state.scale;
            el.style.left = `${x}px`;
            el.style.top = `${y}px`;
            state.positions[el.dataset.id] = { x, y };
            drawEdges();
            drawMinimap();
        });
        el.addEventListener('pointerup', () => { if (start) persistPositions(); start = null; });
    }

    async function selectNode(id) {
        state.selected = id;
        document.querySelectorAll('.ops-node').forEach(node => node.classList.toggle('is-selected', node.dataset.id === id));
        detail.innerHTML = 'Loading node details...';
        const response = await fetch(`${root.dataset.nodeUrl}/${encodeURIComponent(id)}`, { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        const node = data.node;
        const page = node.url ? `<a class="mt-3 inline-flex rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-black text-white" href="${node.url}">Open Page</a>` : '<p class="sa-label mt-3 text-xs font-bold">No direct page link for this node.</p>';
        detail.innerHTML = `
            <div class="space-y-4">
                <div><p class="text-xs font-black uppercase text-emerald-300">${escapeHtml(node.group)}</p><h3 class="mt-1 text-xl font-black text-white">${escapeHtml(node.label)}</h3><p class="sa-muted mt-2">${escapeHtml(node.summary || '')}</p>${page}</div>
                <dl class="grid gap-2 text-xs">
                    ${row('Route', node.route_name || node.uri || 'n/a')}
                    ${row('Permissions', node.permissions || 'n/a')}
                    ${row('Controller', node.controller || 'n/a')}
                    ${row('Source', node.file || 'n/a')}
                    ${row('Relationships', data.relationships.length)}
                    ${row('Expanded payload', `${data.payload_bytes} bytes`)}
                    ${row('Expanded queries', data.query_count)}
                </dl>
                <pre class="sa-soft overflow-auto rounded-2xl p-3 text-xs">${escapeHtml((data.source_excerpt || []).join('\\n'))}</pre>
            </div>`;
    }

    function row(label, value) {
        return `<div class="sa-soft rounded-2xl p-3"><dt class="sa-label font-black uppercase">${escapeHtml(label)}</dt><dd class="mt-1 font-bold text-white">${escapeHtml(String(value))}</dd></div>`;
    }

    function applyFilters() {
        const term = search.value.trim().toLowerCase();
        const group = groupFilter.value;
        document.querySelectorAll('.ops-node').forEach(el => {
            const node = state.nodes.find(item => item.id === el.dataset.id);
            const matchTerm = !term || `${node.label} ${node.summary} ${node.controller} ${node.uri}`.toLowerCase().includes(term);
            const matchGroup = !group || node.group === group;
            el.classList.toggle('is-hidden', !(matchTerm && matchGroup));
        });
    }

    function fit() {
        state.scale = state.scale === 1 ? 0.78 : 1;
        canvas.style.transform = `scale(${state.scale})`;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    search.addEventListener('input', applyFilters);
    groupFilter.addEventListener('change', applyFilters);
    document.getElementById('ops-fit').addEventListener('click', fit);
    document.getElementById('ops-fullscreen').addEventListener('click', () => document.getElementById('ops-workspace').classList.toggle('ops-fullscreen'));

    state.positions = savedPositions();
    fetch(`${root.dataset.dataUrl}?view=${root.dataset.view}`, { headers: { 'Accept': 'application/json' } })
        .then(response => response.json())
        .then(data => {
            state.nodes = data.nodes || [];
            state.edges = data.edges || [];
            renderMetrics(data);
            renderFilters(data.filters || {});
            renderNodes();
        })
        .catch(() => { detail.textContent = 'Could not load Operations Center graph data.'; });
})();
</script>
