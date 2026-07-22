<script data-ops-graph-renderer="shared">
(() => {
    const root = document.getElementById('ops-root');
    if (!root) return;

    const canvas = document.getElementById('ops-canvas');
    const frame = document.querySelector('.ops-graph-frame');
    const edgesSvg = document.getElementById('ops-edges');
    const detail = document.getElementById('ops-detail');
    const detailPanel = document.getElementById('ops-detail-panel');
    const minimap = document.getElementById('ops-minimap');
    const metrics = document.getElementById('ops-metrics');
    const search = document.getElementById('ops-search');
    const groupFilter = document.getElementById('ops-group-filter');
    const referenceMode = document.getElementById('ops-reference-mode');
    const breadcrumbs = document.getElementById('ops-breadcrumbs');
    const workspace = document.getElementById('ops-workspace');
    const fullscreenButton = document.getElementById('ops-fullscreen');
    const detailToggle = document.getElementById('ops-detail-toggle');
    const fitButton = document.getElementById('ops-fit');
    const resetButton = document.getElementById('ops-reset');
    const storageKey = `ops-tree-state-${root.dataset.view}`;
    const selectedStorageKey = `ops-graph-selected-${root.dataset.view}`;

    const state = {
        nodes: new Map(),
        edges: new Map(),
        references: [],
        initialNodeIds: new Set(),
        expanded: new Set(),
        selected: null,
        searchMatches: new Set(),
        ancestorPath: new Set(),
        positions: {},
        scale: 1,
        pan: { x: 0, y: 0 },
        fullscreen: false,
        detailsCollapsed: false,
        referenceMode: 'off',
        loading: new Set(),
        rootId: null,
        currentPositions: {},
        layoutMode: root.dataset.layoutMode || 'flow-tree'
    };

    function saveState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                expanded: [...state.expanded],
                pan: state.pan,
                scale: state.scale,
                positions: state.positions
            }));
            if (state.selected) localStorage.setItem(selectedStorageKey, state.selected);
        } catch (_) {}
    }

    function restoreState() {
        try {
            const raw = localStorage.getItem(storageKey);
            const saved = JSON.parse(raw || '{}');
            state.expanded = new Set(saved.expanded || []);
            state.pan = saved.pan || state.pan;
            state.scale = saved.scale || state.scale;
            state.positions = saved.positions || {};
            state.selected = localStorage.getItem(selectedStorageKey);
            return !!raw;
        } catch (_) {}
        return false;
    }

    function addNodes(nodes, edges = []) {
        nodes.forEach(node => state.nodes.set(node.id, node));
        edges.forEach(edge => state.edges.set(edge.id, edge));
    }

    function visibleNodes() {
        const all = [...state.nodes.values()];
        return all.filter(node => {
            if (state.searchMatches.size > 0 && !state.searchMatches.has(node.id) && !state.ancestorPath.has(node.id)) {
                return false;
            }
            if (!node.parent_id) return true;
            if (state.initialNodeIds.has(node.id)) return true;
            let parent = state.nodes.get(node.parent_id);
            while (parent) {
                if (!state.expanded.has(parent.id) && !state.ancestorPath.has(parent.id) && !state.initialNodeIds.has(parent.id)) return false;
                parent = parent.parent_id ? state.nodes.get(parent.parent_id) : null;
            }
            return true;
        });
    }

    function visibleEdges(nodes) {
        const ids = new Set(nodes.map(node => node.id));
        const parentEdges = [...state.edges.values()].filter(edge => ids.has(edge.source) && ids.has(edge.target));
        const refs = state.referenceMode === 'off' ? [] : state.references.filter(edge => {
            if (!ids.has(edge.source) || !ids.has(edge.target)) return false;
            return state.referenceMode === 'all' || edge.source === state.selected || edge.target === state.selected;
        });
        return [...parentEdges, ...refs];
    }

    function childrenOf(id) {
        return [...state.nodes.values()].filter(node => node.parent_id === id);
    }

    function layout(nodes) {
        const byParent = nodes.reduce((memo, node) => {
            const key = node.parent_id || '__root__';
            (memo[key] ||= []).push(node);
            return memo;
        }, {});

        const positions = {};
        const mode = state.layoutMode;
        const nodeW = 210;
        const row = 116;
        const col = 285;

        if (mode === 'flow-tree') {
            const ordered = flowOrder(nodes);
            ordered.forEach((node, index) => {
                const column = index % 5;
                const band = Math.floor(index / 5);
                positions[node.id] = {
                    x: 60 + column * 238,
                    y: 96 + band * 150 + (band % 2 && column % 2 ? 22 : 0)
                };
            });
        } else if (mode === 'radial-tree') {
            const rootNode = nodes.find(node => !node.parent_id) || nodes[0];
            positions[rootNode.id] = { x: 470, y: 310 };
            const branches = childrenOf(rootNode.id).filter(node => nodes.some(visible => visible.id === node.id));
            const left = branches.filter(node => node.side === 'left');
            const right = branches.filter(node => node.side !== 'left');
            placeRadial(left, 130, 64, 104, positions);
            placeRadial(right, 800, 64, 104, positions);
            branches.forEach(branch => placeChildren(branch, branch.side === 'left' ? 28 : 1035, positions[branch.id].y - 24, 104, positions, nodes));
        } else {
            const roots = byParent.__root__ || [];
            const rootNode = roots[0] || nodes[0];
            if (mode === 'layered-tree') {
                if (rootNode?.id?.startsWith('trace-')) {
                    placeFocusedTrace(rootNode, positions, nodes);
                } else {
                    positions[rootNode.id] = { x: 470, y: 68 };
                    childrenOf(rootNode.id)
                        .filter(node => nodes.some(visible => visible.id === node.id))
                        .forEach((child, index) => {
                            positions[child.id] = {
                                x: 70 + (index % 3) * 330,
                                y: 220 + Math.floor(index / 3) * 130
                            };
                            placeChildren(child, positions[child.id].x + 260, positions[child.id].y - 20, 88, positions, nodes);
                        });
                }
            } else {
                const startY = 80;
                const rootX = 50;
                positions[rootNode.id] = { x: rootX, y: startY + Math.max(0, (nodes.length - 1) * 16) };
                placeTree(rootNode, rootX + col, startY, row, col, positions, nodes);
            }
        }

        Object.entries(state.positions).forEach(([id, pos]) => {
            if (positions[id]) positions[id] = pos;
        });

        return positions;

        function placeRadial(items, x, y, step, output) {
            items.forEach((node, index) => output[node.id] = { x, y: y + index * step });
        }

        function placeChildren(parent, x, y, step, output, visible) {
            childrenOf(parent.id).filter(node => visible.some(item => item.id === node.id)).forEach((child, index) => {
                output[child.id] = { x, y: y + index * step };
                placeChildren(child, x + (parent.side === 'left' ? -260 : 260), output[child.id].y - 20, step, output, visible);
            });
        }

        function flowOrder(visible) {
            const ordered = [];
            const seen = new Set();
            const roots = visible.filter(node => !node.parent_id);
            const visit = node => {
                if (!node || seen.has(node.id)) return;
                seen.add(node.id);
                ordered.push(node);
                childrenOf(node.id)
                    .filter(child => visible.some(item => item.id === child.id))
                    .forEach(visit);
            };
            roots.forEach(visit);
            visible.forEach(node => visit(node));
            return ordered;
        }

        function placeFocusedTrace(rootNode, output, visible) {
            const visibleIds = new Set(visible.map(node => node.id));
            const direct = id => childrenOf(id).filter(child => visibleIds.has(child.id));
            const route = direct(rootNode.id).find(node => node.group === 'route') || direct(rootNode.id)[0];
            const routeChildren = route ? direct(route.id) : [];
            const controller = routeChildren.find(node => node.group === 'controller');
            const policy = routeChildren.find(node => node.group === 'policy');
            const service = controller ? direct(controller.id).find(node => node.group === 'service') : null;
            const test = policy ? direct(policy.id).find(node => node.group === 'test') : null;
            const model = service ? direct(service.id).find(node => node.group === 'model') : null;
            const table = model ? direct(model.id).find(node => node.group === 'table') : null;
            [
                [rootNode, 390, 70],
                [route, 60, 220],
                [controller, 300, 220],
                [policy, 300, 350],
                [service, 540, 220],
                [test, 540, 350],
                [model, 780, 220],
                [table, 780, 350],
            ].forEach(([node, x, y]) => {
                if (node) output[node.id] = { x, y };
            });
            visible.forEach((node, index) => {
                if (!output[node.id]) output[node.id] = { x: 60 + (index % 4) * 240, y: 500 + Math.floor(index / 4) * 120 };
            });
        }

        function placeTree(parent, x, y, step, xStep, output, visible) {
            const kids = childrenOf(parent.id).filter(node => visible.some(item => item.id === node.id));
            if (!kids.length) return 1;
            let cursor = y;
            kids.forEach(child => {
                const span = countVisibleLeaves(child, visible);
                output[child.id] = { x, y: cursor + Math.max(0, span - 1) * step / 2 };
                placeTree(child, x + xStep, cursor, step, xStep, output, visible);
                cursor += Math.max(1, span) * step;
            });
        }

        function countVisibleLeaves(node, visible) {
            const kids = childrenOf(node.id).filter(child => visible.some(item => item.id === child.id));
            if (!kids.length) return 1;
            return kids.reduce((sum, child) => sum + countVisibleLeaves(child, visible), 0);
        }
    }

    function render() {
        const nodes = visibleNodes();
        const positions = layout(nodes);
        const edges = visibleEdges(nodes);
        state.currentPositions = positions;

        canvas.querySelectorAll('.ops-node').forEach(node => node.remove());
        nodes.forEach(node => renderNode(node, positions[node.id]));
        drawEdges(edges, positions);
        drawMinimap(nodes, positions);
        renderMetrics(nodes, edges);
        renderBreadcrumbs();
        applyTransform();
        applyFilters();
    }

    function renderNode(node, pos) {
        const nodeEl = document.createElement('div');
        nodeEl.className = 'ops-node';
        nodeEl.dataset.id = node.id;
        nodeEl.dataset.group = node.group;
        nodeEl.style.left = `${pos.x}px`;
        nodeEl.style.top = `${pos.y}px`;
        nodeEl.setAttribute('role', 'button');
        nodeEl.setAttribute('tabindex', '0');
        nodeEl.setAttribute('aria-pressed', node.id === state.selected ? 'true' : 'false');
        nodeEl.classList.toggle('is-selected', node.id === state.selected);
        nodeEl.classList.toggle('is-search-match', state.searchMatches.has(node.id));
        nodeEl.classList.toggle('is-dimmed', state.searchMatches.size > 0 && !state.searchMatches.has(node.id) && !state.ancestorPath.has(node.id));
        const expanded = state.expanded.has(node.id);
        const loading = state.loading.has(node.id);
        const chevron = node.has_children ? `<button type="button" class="ops-expand" data-expand-id="${escapeHtml(node.id)}" aria-label="${expanded ? 'Collapse' : 'Expand'} ${escapeHtml(node.label)}">${loading ? '...' : (expanded ? '-' : '+')}</button>` : '';
        nodeEl.innerHTML = `${chevron}<small>${escapeHtml(node.group)}</small><strong>${escapeHtml(node.label)}</strong><span>${escapeHtml(node.status_badge || 'Ready')}</span>${node.child_count ? `<span class="ops-child-count">${node.child_count}</span>` : ''}`;
        nodeEl.addEventListener('click', event => {
            if (event.target.closest('.ops-expand')) return;
            selectNode(node.id);
        });
        nodeEl.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                selectNode(node.id);
            }
        });
        nodeEl.addEventListener('dblclick', () => toggleNode(node.id));
        makeDraggable(nodeEl);
        canvas.appendChild(nodeEl);
    }

    function drawEdges(edges, positions) {
        if (window.matchMedia('(max-width: 900px)').matches) {
            edgesSvg.innerHTML = '';
            canvas.style.width = 'auto';
            canvas.style.height = 'auto';
            return;
        }
        const width = Math.max(1180, ...Object.values(positions).map(pos => pos.x + 260));
        const height = Math.max(620, ...Object.values(positions).map(pos => pos.y + 130));
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        edgesSvg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        edgesSvg.innerHTML = '';
        edges.forEach(edge => {
            const source = positions[edge.source];
            const target = positions[edge.target];
            if (!source || !target) return;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.classList.add('ops-edge');
            const sx = source.x + 210;
            const sy = source.y + 36;
            const tx = target.x;
            const ty = target.y + 36;
            const isRadial = state.layoutMode === 'radial-tree';
            const d = isRadial
                ? `M ${source.x + 105} ${sy} C ${(source.x + target.x) / 2} ${sy}, ${(source.x + target.x) / 2} ${ty}, ${target.x + 105} ${ty}`
                : `M ${sx} ${sy} C ${sx + 70} ${sy}, ${tx - 70} ${ty}, ${tx} ${ty}`;
            path.setAttribute('d', d);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', edge.type === 'reference' ? 'rgba(251, 191, 36, .75)' : 'rgba(148, 163, 184, .50)');
            path.setAttribute('stroke-width', edge.source === state.selected || edge.target === state.selected ? '3' : '2');
            path.setAttribute('stroke-dasharray', edge.type === 'reference' ? '6 5' : '');
            edgesSvg.appendChild(path);
        });
    }

    function drawMinimap(nodes, positions) {
        minimap.innerHTML = '';
        nodes.forEach(node => {
            const pos = positions[node.id];
            if (!pos) return;
            const dot = document.createElement('span');
            dot.className = 'ops-mini-dot';
            dot.style.left = `${Math.max(4, Math.min(180, pos.x / 8))}px`;
            dot.style.top = `${Math.max(4, Math.min(110, pos.y / 6))}px`;
            minimap.appendChild(dot);
        });
    }

    function renderMetrics(nodes, edges) {
        metrics.innerHTML = [
            ['Visible Nodes', nodes.length],
            ['Visible Edges', edges.length],
            ['Mode', state.layoutMode],
            ['References', state.referenceMode]
        ].map(([k,v]) => `<div class="sa-soft rounded-2xl px-3 py-2"><span class="sa-label">${escapeHtml(k)}</span><strong class="ml-2">${escapeHtml(v)}</strong></div>`).join('');
    }

    function renderBreadcrumbs() {
        if (!state.selected) {
            breadcrumbs.textContent = '';
            return;
        }
        const names = ancestorPath(state.selected).concat(state.selected).map(id => state.nodes.get(id)?.label).filter(Boolean);
        breadcrumbs.textContent = names.join(' / ');
    }

    async function toggleNode(id) {
        if (state.expanded.has(id)) {
            collapseBranch(id);
            return;
        }
        await expandNode(id);
    }

    async function expandNode(id) {
        const node = state.nodes.get(id);
        if (!node?.has_children) return;
        state.loading.add(id);
        render();
        if (!childrenOf(id).some(child => !state.initialNodeIds.has(child.id))) {
            const response = await fetch(`${root.dataset.branchUrl}?view=${encodeURIComponent(root.dataset.view)}&parent_id=${encodeURIComponent(id)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            addNodes(data.nodes || [], data.edges || []);
        }
        state.loading.delete(id);
        state.expanded.add(id);
        saveState();
        render();
    }

    function collapseBranch(id) {
        const ids = descendants(id);
        state.expanded.delete(id);
        ids.forEach(childId => state.expanded.delete(childId));
        saveState();
        render();
    }

    async function selectNode(id) {
        state.selected = id;
        saveState();
        render();
        detail.innerHTML = 'Loading node details...';
        const response = await fetch(`${root.dataset.nodeUrl}/${encodeURIComponent(id)}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        showDetails(data);
    }

    function showDetails(data) {
        const node = data.node;
        const technicalDetails = root.dataset.view === 'technical_map';
        const page = node.url ? `<a class="mt-3 inline-flex rounded-2xl bg-emerald-500 px-4 py-2 text-xs font-black text-white" href="${node.url}">Open Page</a>` : '<p class="sa-label mt-3 text-xs font-bold">No safe direct page link for this node.</p>';
        const internalRows = root.dataset.detailLevel === 'manager' || !technicalDetails ? '' : `
            ${row('Route', node.route_name || 'n/a')}
            ${row('Controller', node.controller || 'n/a')}
            ${row('Service', node.service || 'n/a')}
            ${row('Model / Table', [node.model, node.table].filter(Boolean).join(' / ') || 'n/a')}
            ${row('Source', node.file || 'n/a')}`;
        const detailRows = `
            ${row('Purpose', node.summary || 'n/a')}
            ${row('Responsible Role', node.responsible_role || 'n/a')}
            ${row('Entry Criteria', node.details?.entry_criteria || 'n/a')}
            ${row('Exit Criteria', node.details?.exit_criteria || 'n/a')}
            ${row('Next Stage', node.next_stage || 'n/a')}
            ${row('Child Nodes', node.child_count || 0)}
            ${internalRows}
            ${row('Expanded Payload', `${data.payload_bytes} bytes`)}
            ${row('Expanded Queries', data.query_count)}`;
        const sourceBlock = root.dataset.detailLevel === 'manager'
            ? `<div class="sa-soft rounded-2xl p-3 text-xs font-bold">${escapeHtml(data.access_note || 'Manager view hides platform internals.')}</div>`
            : (technicalDetails ? `<pre class="sa-soft overflow-auto rounded-2xl p-3 text-xs">${escapeHtml((data.source_excerpt || []).join('\\n'))}</pre>` : '');
        detail.innerHTML = `<div class="space-y-4"><div><p class="text-xs font-black uppercase text-emerald-300">${escapeHtml(node.group)}</p><h3 class="mt-1 text-xl font-black text-white">${escapeHtml(node.label)}</h3>${page}</div><dl class="grid gap-2 text-xs">${detailRows}</dl>${sourceBlock}</div>`;
    }

    function row(label, value) {
        return `<div class="sa-soft rounded-2xl p-3"><dt class="sa-label font-black uppercase">${escapeHtml(label)}</dt><dd class="mt-1 font-bold text-white">${escapeHtml(String(value ?? 'n/a'))}</dd></div>`;
    }

    function makeDraggable(el) {
        let start = null;
        el.addEventListener('pointerdown', event => {
            if (event.target.closest('.ops-expand') || window.matchMedia('(max-width: 900px)').matches) return;
            start = { x: event.clientX, y: event.clientY, left: parseFloat(el.style.left), top: parseFloat(el.style.top) };
            el.setPointerCapture(event.pointerId);
        });
        el.addEventListener('pointermove', event => {
            if (!start) return;
            const x = start.left + (event.clientX - start.x) / state.scale;
            const y = start.top + (event.clientY - start.y) / state.scale;
            el.style.left = `${x}px`;
            el.style.top = `${y}px`;
            state.positions[el.dataset.id] = { x, y };
        });
        el.addEventListener('pointerup', () => { if (start) { saveState(); render(); } start = null; });
    }

    function applyFilters() {
        const group = groupFilter.value;
        document.querySelectorAll('.ops-node').forEach(el => {
            const node = state.nodes.get(el.dataset.id);
            el.classList.toggle('is-hidden', !!group && node.group !== group);
        });
    }

    async function performSearch() {
        const term = search.value.trim();
        if (!term) {
            state.searchMatches.clear();
            state.ancestorPath.clear();
            render();
            return;
        }
        const response = await fetch(`${root.dataset.searchUrl}?view=${encodeURIComponent(root.dataset.view)}&q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        addNodes(data.nodes || [], data.edges || []);
        state.searchMatches = new Set(data.matched_node_ids || []);
        state.ancestorPath = new Set(data.ancestor_node_ids || []);
        state.ancestorPath.forEach(id => state.expanded.add(id));
        const first = [...state.searchMatches][0];
        if (first) await selectNode(first);
        render();
    }

    async function loadTrace() {
        if (!root.dataset.traceUrl) return;
        const response = await fetch(`${root.dataset.traceUrl}?target=opportunity-details`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        state.nodes.clear();
        state.edges.clear();
        state.references = data.references || [];
        addNodes(data.nodes || [], data.edges || []);
        state.expanded = new Set((data.nodes || []).map(node => node.id));
        state.selected = data.nodes?.[0]?.id || null;
        state.layoutMode = data.layout_mode || 'layered-tree';
        state.positions = {};
        render();
        if (state.selected) await selectNode(state.selected);
    }

    function fit() {
        const positions = Object.values(state.currentPositions || {});
        if (!positions.length) return;
        const nodeW = 230;
        const nodeH = 98;
        const bounds = positions.reduce((box, pos) => ({
            minX: Math.min(box.minX, pos.x),
            minY: Math.min(box.minY, pos.y),
            maxX: Math.max(box.maxX, pos.x + nodeW),
            maxY: Math.max(box.maxY, pos.y + nodeH)
        }), { minX: Infinity, minY: Infinity, maxX: 0, maxY: 0 });
        const frameRect = frame.getBoundingClientRect();
        const scaleX = (frameRect.width - 48) / Math.max(1, bounds.maxX - bounds.minX);
        const scaleY = (frameRect.height - 48) / Math.max(1, bounds.maxY - bounds.minY);
        state.scale = Math.max(0.32, Math.min(1, Math.min(scaleX, scaleY)));
        state.pan = {
            x: 24 - bounds.minX * state.scale + Math.max(0, (frameRect.width - (bounds.maxX - bounds.minX) * state.scale) / 2 - 24),
            y: 24 - bounds.minY * state.scale + Math.max(0, (frameRect.height - (bounds.maxY - bounds.minY) * state.scale) / 2 - 24)
        };
        saveState();
        applyTransform();
    }

    function reset() {
        state.scale = 1;
        state.pan = { x: 0, y: 0 };
        state.positions = {};
        state.searchMatches.clear();
        state.ancestorPath.clear();
        saveState();
        render();
    }

    function applyTransform() {
        canvas.style.transform = `translate(${state.pan.x}px, ${state.pan.y}px) scale(${state.scale})`;
    }

    function setFullscreen(enabled) {
        state.fullscreen = enabled;
        workspace.classList.toggle('ops-fullscreen', enabled);
        document.body.classList.toggle('ops-scroll-lock', enabled);
        fullscreenButton.textContent = enabled ? 'Exit Fullscreen' : 'Fullscreen';
        requestAnimationFrame(render);
    }

    function toggleDetails() {
        state.detailsCollapsed = !state.detailsCollapsed;
        detailPanel.hidden = state.detailsCollapsed;
        detailToggle.textContent = state.detailsCollapsed ? 'Expand Details' : 'Collapse Details';
    }

    function descendants(id) {
        const found = [];
        const walk = parentId => childrenOf(parentId).forEach(child => {
            found.push(child.id);
            walk(child.id);
        });
        walk(id);
        return found;
    }

    function ancestorPath(id) {
        const path = [];
        let current = state.nodes.get(id);
        while (current?.parent_id) {
            path.unshift(current.parent_id);
            current = state.nodes.get(current.parent_id);
        }
        return path;
    }

    function renderFilters(filters) {
        groupFilter.innerHTML = '<option value="">All groups</option>';
        (filters.groups || []).forEach(group => {
            const option = document.createElement('option');
            option.value = group;
            option.textContent = group.replace(/-/g, ' ');
            groupFilter.appendChild(option);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    canvas.addEventListener('click', event => {
        const expand = event.target.closest('[data-expand-id]');
        if (expand) toggleNode(expand.dataset.expandId);
    });
    let searchTimer = null;
    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(performSearch, 300);
    });
    search.addEventListener('change', performSearch);
    search.addEventListener('keydown', event => { if (event.key === 'Enter') performSearch(); });
    groupFilter.addEventListener('change', applyFilters);
    referenceMode.addEventListener('change', () => { state.referenceMode = referenceMode.value; render(); });
    fitButton.addEventListener('click', fit);
    resetButton.addEventListener('click', reset);
    fullscreenButton.addEventListener('click', () => setFullscreen(!state.fullscreen));
    detailToggle.addEventListener('click', toggleDetails);
    document.getElementById('ops-collapse-branch')?.addEventListener('click', () => state.selected && collapseBranch(state.selected));
    document.getElementById('ops-expand-one')?.addEventListener('click', () => state.selected && expandNode(state.selected));
    document.getElementById('ops-collapse-all')?.addEventListener('click', () => { state.expanded.clear(); render(); saveState(); });
    document.getElementById('ops-expand-path')?.addEventListener('click', () => { if (state.selected) ancestorPath(state.selected).forEach(id => state.expanded.add(id)); render(); saveState(); });
    document.getElementById('ops-trace-page')?.addEventListener('click', loadTrace);
    document.getElementById('ops-return-overview')?.addEventListener('click', () => location.reload());
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && state.fullscreen) setFullscreen(false); });

    let panStart = null;
    frame.addEventListener('pointerdown', event => {
        if (event.target.closest('.ops-node')) return;
        panStart = { x: event.clientX, y: event.clientY, pan: { ...state.pan } };
    });
    frame.addEventListener('pointermove', event => {
        if (!panStart) return;
        state.pan = { x: panStart.pan.x + event.clientX - panStart.x, y: panStart.pan.y + event.clientY - panStart.y };
        applyTransform();
    });
    frame.addEventListener('pointerup', () => { if (panStart) saveState(); panStart = null; });
    frame.addEventListener('wheel', event => {
        if (!event.ctrlKey && !event.metaKey) return;
        event.preventDefault();
        state.scale = Math.max(0.32, Math.min(1.4, state.scale + (event.deltaY < 0 ? .06 : -.06)));
        saveState();
        applyTransform();
    }, { passive: false });

    const restored = restoreState();
    fetch(`${root.dataset.dataUrl}?view=${root.dataset.view}`, { headers: { Accept: 'application/json' } })
        .then(response => response.json())
        .then(data => {
            state.layoutMode = data.layout_mode || state.layoutMode;
            state.rootId = data.root_id;
            state.references = data.references || [];
            addNodes(data.nodes || [], data.edges || []);
            state.initialNodeIds = new Set((data.nodes || []).map(node => node.id));
            renderFilters(data.filters || {});
            render();
            if (!restored) requestAnimationFrame(fit);
            if (state.selected && state.nodes.has(state.selected)) selectNode(state.selected);
        })
        .catch(() => { detail.textContent = 'Could not load Operations Center tree data.'; });
})();
</script>
