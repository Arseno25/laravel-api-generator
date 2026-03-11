/**
 * API Docs — Endpoint Rendering Module
 * Sidebar navigation, endpoint detail rendering, code snippets
 */

// ─── Sidebar Endpoint Rendering ───
function renderEndpoints(filter = '') {
    const container = document.getElementById('endpoints-list');
    container.innerHTML = '';
    const query = filter.toLowerCase();
    const utilitySections = [];

    const groups = {};
    for (const [path, methods] of Object.entries(schema.endpoints)) {
        for (const [method, endpoint] of Object.entries(methods)) {
            if (selectedVersion !== 'all' && endpoint.version !== selectedVersion) continue;
            const summary = endpoint.summary || path;
            if (query && !path.toLowerCase().includes(query) && !summary.toLowerCase().includes(query)) continue;
            const tag = (endpoint.tags && endpoint.tags[0]) || extractResourceFromPath(path);
            if (!groups[tag]) groups[tag] = [];
            groups[tag].push({ path, method, endpoint, summary });
        }
    }

    utilitySections.push(`
        <button onclick="showOverviewPanel()" class="w-full text-left px-3 py-3 rounded-2xl border border-cyan-400/15 bg-cyan-400/10 hover:bg-cyan-400/15 transition-colors mb-3">
            <span class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-400/15 text-cyan-300"><i class="fas fa-chart-line"></i></span>
                    <span>
                        <span class="block text-sm font-semibold text-white">Overview</span>
                        <span class="block text-xs text-slate-400">Workspace summary and quick metrics</span>
                    </span>
                </span>
                <i class="fas fa-arrow-right text-xs text-cyan-300"></i>
            </span>
        </button>
    `);

    if (schema.webhooks?.length > 0) {
        utilitySections.push(`
            <button onclick="showWebhooksPanel()" class="w-full text-left px-3 py-2.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 transition-colors mb-2">
                <span class="flex items-center justify-between gap-3">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-broadcast-tower text-amber-400"></i>
                        <span class="text-sm text-slate-200">Webhooks</span>
                    </span>
                    <span class="rounded-full bg-amber-400/10 px-2 py-1 text-[10px] text-amber-300">${schema.webhooks.length}</span>
                </span>
            </button>
        `);
    }

    const eventCount = Object.keys(schema.events || {}).length;
    if (eventCount > 0) {
        utilitySections.push(`
            <button onclick="showEventsPanel()" class="w-full text-left px-3 py-2.5 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 transition-colors mb-3">
                <span class="flex items-center justify-between gap-3">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-bolt text-yellow-400"></i>
                        <span class="text-sm text-slate-200">WebSockets</span>
                    </span>
                    <span class="rounded-full bg-yellow-400/10 px-2 py-1 text-[10px] text-yellow-300">${eventCount}</span>
                </span>
            </button>
        `);
    }

    if (utilitySections.length > 0) {
        const utilityWrapper = document.createElement('div');
        utilityWrapper.innerHTML = utilitySections.join('');
        container.appendChild(utilityWrapper);
    }

    for (const [groupName, items] of Object.entries(groups)) {
        const isCollapsed = collapsedGroups[groupName] === true;
        const groupDiv = document.createElement('div');
        groupDiv.className = 'mb-2';
        groupDiv.innerHTML = `
            <button onclick="toggleGroup('${groupName}', this)" class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider group-header">
                <span class="truncate"><i class="fas fa-folder-open mr-2 text-indigo-400/60"></i>${groupName}</span>
                <span class="text-slate-600 flex-shrink-0 ml-2">
                    <span class="text-xs font-normal normal-case mr-1">${items.length}</span>
                    <i class="fas fa-chevron-${isCollapsed ? 'right' : 'down'} text-[10px] transition-transform" id="chevron-${groupName}"></i>
                </span>
            </button>
            <div id="group-${groupName}" class="${isCollapsed ? 'hidden' : ''} space-y-0.5 mt-0.5">
                ${items.map(({ path, method, summary }) => `
                    <button onclick="selectEndpoint('${path}', '${method}')" id="sidebar-${method}-${path.replace(/[^a-z0-9]/gi, '-')}" class="w-full text-left px-3 py-2 rounded-lg border border-transparent flex items-center gap-2 text-sm sidebar-item ${selectedPath === path && selectedMethod === method ? 'active' : ''}">
                        <span class="method-badge method-${method}">${method.toUpperCase()}</span>
                        <span class="truncate text-slate-300 text-xs">${summary}</span>
                    </button>
                `).join('')}
            </div>
        `;
        container.appendChild(groupDiv);
    }

    if (Object.keys(groups).length === 0) {
        const emptyState = document.createElement('div');
        emptyState.className = 'rounded-2xl border border-dashed border-white/10 bg-white/5 px-4 py-6 text-center';
        emptyState.innerHTML = `
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-800/80 text-slate-500">
                <i class="fas fa-search"></i>
            </div>
            <p class="text-sm font-semibold text-slate-200">No endpoints match your filter</p>
            <p class="mt-1 text-xs text-slate-500">Try a different keyword or clear the search input.</p>
        `;
        container.appendChild(emptyState);
    }
}

function extractResourceFromPath(path) {
    const cleaned = path.replace(/^\/?(?:api\/)?v?\d*\/?/i, '');
    const segment = cleaned.split('/')[0] || 'General';
    return segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' ');
}

function toggleGroup(name, btn) {
    const group = document.getElementById('group-' + name);
    const chevron = document.getElementById('chevron-' + name);
    if (group.classList.contains('hidden')) {
        group.classList.remove('hidden');
        chevron.className = 'fas fa-chevron-down text-[10px] transition-transform';
        delete collapsedGroups[name];
    } else {
        group.classList.add('hidden');
        chevron.className = 'fas fa-chevron-right text-[10px] transition-transform';
        collapsedGroups[name] = true;
    }
    localStorage.setItem('api-docs-collapsed', JSON.stringify(collapsedGroups));
}

function selectEndpoint(path, method) {
    selectedPath = path;
    selectedMethod = method;
    const endpoint = schema.endpoints[path][method];
    closeMobileSidebar();
    renderEndpoints(document.getElementById('search').value);
    renderEndpointDetail(path, method, endpoint);
}

// ─── Endpoint Detail Rendering ───
function renderEndpointDetail(path, method, endpoint) {
    const container = document.getElementById('content-area');
    const availableServers = getAvailableServers();
    const activeBaseUrl = getActiveBaseUrl();

    let paramsHtml = '';
    if (endpoint.parameters?.path?.length) {
        paramsHtml += `
            <div class="mb-4">
                <h5 class="text-sm font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-route text-blue-400"></i> Path Parameters
                </h5>
                <div class="rounded-lg border border-slate-700 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Name</th>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Type</th>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase hidden sm:table-cell">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            ${endpoint.parameters.path.map(p => `
                                <tr class="bg-slate-900/50">
                                    <td class="py-2.5 px-4"><code class="text-blue-400 font-mono text-sm">${p.name}</code></td>
                                    <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-400/10 text-blue-400">${p.type}</span></td>
                                    <td class="py-2.5 px-4 text-slate-400 hidden sm:table-cell">${p.description || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    let bodyHtml = '';
    if (endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post', 'put', 'patch'].includes(method)) {
        bodyHtml += `
            <div class="mb-4">
                <h5 class="text-sm font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-code text-green-400"></i> Request Body Schema
                </h5>
                <div class="rounded-lg border border-slate-700 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Field</th>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Type</th>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Required</th>
                                <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase hidden sm:table-cell">Rules</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            ${Object.entries(endpoint.parameters.body).map(([name, field]) => `
                                <tr class="bg-slate-900/50">
                                    <td class="py-2.5 px-4"><code class="text-green-400 font-mono text-sm">${name}</code></td>
                                    <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs bg-green-400/10 text-green-400">${field.type}</span></td>
                                    <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs ${field.required ? 'bg-red-400/10 text-red-400' : 'bg-slate-600/30 text-slate-400'}">${field.required ? 'Required' : 'Optional'}</span></td>
                                    <td class="py-2.5 px-4 font-mono text-xs text-slate-400 break-all hidden sm:table-cell">${field.rules || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    const hasBody = endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post', 'put', 'patch'].includes(method);
    const responseCount = (endpoint.responses || []).length;
    const roleCount = endpoint.security
        ? endpoint.security
            .filter((item) => item.type === 'role')
            .reduce((total, item) => total + (item.roles?.length || 0), 0)
        : 0;
    const permissionCount = endpoint.security
        ? endpoint.security
            .filter((item) => item.type === 'permission')
            .reduce((total, item) => total + (item.permissions?.length || 0), 0)
        : 0;
    const isProtectedEndpoint = endpoint.security?.some((item) => item.type === 'http');
    const hasRateLimit = endpoint.security?.some((item) => item.type === 'rateLimit');
    const securitySummary = [
        isProtectedEndpoint
            ? '<span class="inline-flex items-center gap-2 rounded-full border border-red-400/15 bg-red-400/10 px-3 py-1 text-[11px] font-medium text-red-200"><i class="fas fa-lock text-red-300"></i>Protected</span>'
            : '',
        roleCount > 0
            ? `<span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-300"><i class="fas fa-user-shield text-slate-400"></i>${roleCount} role requirement${roleCount > 1 ? 's' : ''}</span>`
            : '',
        permissionCount > 0
            ? `<span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-300"><i class="fas fa-key text-slate-400"></i>${permissionCount} permission${permissionCount > 1 ? 's' : ''}</span>`
            : '',
        hasRateLimit
            ? '<span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium text-slate-300"><i class="fas fa-gauge-high text-slate-400"></i>Rate limited</span>'
            : '',
    ].filter(Boolean).join('');
    const exampleSection = hasBody ? `
        <div class="rounded-2xl border border-white/8 bg-slate-950/35 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-code text-slate-400"></i>
                    <span class="text-sm font-semibold text-slate-200">Example payload</span>
                </div>
                <button onclick="copyExample()" id="copy-example-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition-colors hover:bg-white/10">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <pre class="overflow-x-auto rounded-xl border border-white/6 bg-slate-950/80 p-3 text-sm text-green-400"><code id="example-body">${generateSampleBody(endpoint)}</code></pre>
        </div>
    ` : '';

    const bodyInputSection = hasBody ? `
        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label class="text-sm font-medium text-slate-300">Request body</label>
                <button onclick="useExample()" class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-400/20 bg-indigo-400/10 px-3 py-1.5 text-xs text-indigo-300 transition-colors hover:bg-indigo-400/15">
                    <i class="fas fa-magic"></i> Fill with Example
                </button>
            </div>
            <textarea id="request-body" rows="6" class="w-full resize-y rounded-2xl border border-white/8 bg-slate-950/70 px-4 py-3 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{}</textarea>
        </div>
    ` : '';

    const queryParamsSection = ['get', 'delete'].includes(method) ? `
        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label class="text-sm font-medium text-slate-300">Query parameters</label>
                <button onclick="addQueryParam()" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition-colors hover:bg-white/10">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
            <div id="query-params" class="space-y-2">
                ${endpoint.parameters?.query?.length ? endpoint.parameters.query.map(q => `
                    <div class="flex gap-2 query-param-row">
                        <input type="text" value="${q.name}" placeholder="key" class="query-key min-w-0 flex-1 rounded-xl border border-white/8 bg-slate-950/70 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <input type="text" value="${q.name === 'page' ? '1' : (q.name === 'per_page' ? '15' : '')}" placeholder="value" class="query-value min-w-0 flex-1 rounded-xl border border-white/8 bg-slate-950/70 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <button onclick="this.parentElement.remove()" class="px-2 text-slate-500 hover:text-red-400 flex-shrink-0"><i class="fas fa-times"></i></button>
                    </div>
                `).join('') : ''}
            </div>
        </div>
    ` : '';

    container.innerHTML = `
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-3 lg:gap-4">
            ${endpoint.deprecated ? `
            <div class="rounded-2xl border border-yellow-500/20 bg-yellow-500/10 p-4">
                <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-yellow-400 mt-0.5 flex-shrink-0"></i>
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-yellow-400">Deprecated</span>
                    ${endpoint.deprecated_info?.message ? `<p class="text-xs text-yellow-400/80 mt-1">${escapeHtml(endpoint.deprecated_info.message)}</p>` : ''}
                    ${endpoint.deprecated_info?.since ? `<p class="text-xs text-slate-400 mt-1">Since: ${escapeHtml(endpoint.deprecated_info.since)}</p>` : ''}
                    ${endpoint.deprecated_info?.alternative ? `<p class="text-xs text-slate-400 mt-1">Use instead: <code class="code-inline text-indigo-400">${escapeHtml(endpoint.deprecated_info.alternative)}</code></p>` : ''}
                </div>
                </div>
            </div>
            ` : ''}

            <div class="overflow-hidden rounded-3xl border border-white/8 bg-slate-950/35">
                <div class="flex flex-col gap-3 border-b border-white/6 px-4 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-6">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <span class="method-badge method-${method}">${method.toUpperCase()}</span>
                        <code class="text-sm text-slate-200 font-mono truncate ${endpoint.deprecated ? 'line-through opacity-60' : ''}">${path}</code>
                    </div>
                    <button onclick="loadCodeSnippets('${method}', '${path}')" class="inline-flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-medium text-slate-200 transition-colors hover:bg-white/10 sm:self-auto"><i class="fas fa-code text-slate-400"></i>Code Snippets</button>
                </div>
                <div class="space-y-4 px-4 py-5 lg:px-6">
                    <h3 class="text-lg font-semibold text-white tracking-tight">${endpoint.summary || path}</h3>
                    ${endpoint.description ? `<p class="text-slate-400 text-sm mt-2 leading-relaxed">${endpoint.description}</p>` : ''}
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] text-slate-300">
                            <i class="fas fa-layer-group mr-1.5 text-slate-500"></i>
                            Version ${endpoint.version || '1'}
                        </span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] text-slate-300">
                            <i class="fas fa-reply mr-1.5 text-slate-500"></i>
                            ${responseCount} response definition${responseCount === 1 ? '' : 's'}
                        </span>
                    </div>
                    ${securitySummary ? `
                    <div class="border-t border-white/6 pt-4">
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Access</p>
                        <div class="flex flex-wrap gap-2">${securitySummary}</div>
                    </div>
                    ` : ''}
                </div>
            </div>

            ${endpoint.responses && endpoint.responses.length > 0 ? `
            <div class="rounded-3xl border border-white/8 bg-slate-950/35 p-4 lg:p-5">
                <h4 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-200"><i class="fas fa-reply text-slate-400"></i> Response Definitions</h4>
                <div class="space-y-3">
                    ${endpoint.responses.map(r => `
                        <div class="rounded-2xl border border-white/8 bg-white/[0.03] p-3.5 lg:p-4">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="status-badge status-${Math.floor(r.status / 100)}xx">${r.status}</span>
                                <span class="text-sm text-slate-300">${escapeHtml(r.description) || 'No description'}</span>
                                ${r.resource ? `<span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-[11px] text-slate-300">${r.resource.split('\\\\').pop()}</span>` : ''}
                                ${r.is_array ? '<span class="rounded-full border border-white/10 bg-white/5 px-2.5 py-0.5 text-[11px] text-slate-300">Array</span>' : ''}
                            </div>
                            ${r.example ? `<pre class="mt-3 overflow-x-auto rounded-xl border border-white/6 bg-slate-950/80 p-3 text-xs text-green-400"><code>${JSON.stringify(r.example, null, 2)}</code></pre>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
            ` : ''}

            ${endpoint.example ? `
            <div class="rounded-3xl border border-white/8 bg-slate-950/35 p-4 lg:p-5">
                <h4 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-200"><i class="fas fa-lightbulb text-slate-400"></i> Reference Example</h4>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    ${endpoint.example.request ? `
                    <div>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Request</span>
                        <pre class="overflow-x-auto rounded-xl border border-white/6 bg-slate-950/80 p-3 text-xs text-green-400"><code>${JSON.stringify(endpoint.example.request, null, 2)}</code></pre>
                    </div>
                    ` : ''}
                    ${endpoint.example.response ? `
                    <div>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Response</span>
                        <pre class="overflow-x-auto rounded-xl border border-white/6 bg-slate-950/80 p-3 text-xs text-cyan-300"><code>${JSON.stringify(endpoint.example.response, null, 2)}</code></pre>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            <div id="code-snippets-panel" class="hidden overflow-hidden rounded-3xl border border-white/8 bg-slate-950/35">
                <div class="flex items-center justify-between border-b border-white/6 px-4 py-3 lg:px-5">
                    <h4 class="font-semibold flex items-center gap-2 text-slate-200 text-sm"><i class="fas fa-code text-violet-400"></i> Code Snippets</h4>
                    <button onclick="document.getElementById('code-snippets-panel').classList.add('hidden')" class="text-slate-400 hover:text-white p-1"><i class="fas fa-times"></i></button>
                </div>
                <div class="flex overflow-x-auto border-b border-white/6">
                    <button onclick="showSnippetTab('curl')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="curl">cURL</button>
                    <button onclick="showSnippetTab('javascript')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="javascript">JavaScript</button>
                    <button onclick="showSnippetTab('php')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="php">PHP</button>
                    <button onclick="showSnippetTab('python')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="python">Python</button>
                    <button onclick="showSnippetTab('dart')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="dart">Dart</button>
                    <button onclick="showSnippetTab('swift')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="swift">Swift</button>
                    <button onclick="showSnippetTab('go')" class="snippet-tab px-4 py-2.5 text-xs font-semibold text-slate-400 hover:text-white border-b-2 border-transparent whitespace-nowrap" data-tab="go">Go</button>
                </div>
                <div id="snippet-content" class="p-4">
                    <pre class="overflow-x-auto rounded-xl border border-white/6 bg-slate-950/80 p-4 text-xs text-green-400"><code id="snippet-code">Loading...</code></pre>
                </div>
            </div>

            ${paramsHtml || bodyHtml ? `<div class="rounded-3xl border border-white/8 bg-slate-950/35 p-4 lg:p-5">${paramsHtml}${bodyHtml}</div>` : ''}

            <div class="rounded-3xl border border-white/8 bg-slate-950/35 p-4 lg:p-5">
                <h4 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-200">
                    <i class="fas fa-play-circle text-slate-400"></i> Try It Out
                </h4>
                <div class="space-y-4 lg:space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Base URL</label>
                        ${availableServers.length > 1 ? `
                            <select id="base-url-select" onchange="switchEnvironment(this.value)" class="mb-2 w-full rounded-2xl border border-white/8 bg-slate-950/70 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                ${availableServers.map(s => `<option value="${escapeHtml(s.url)}" ${s.url === activeBaseUrl ? 'selected' : ''}>${escapeHtml(s.description)} (${escapeHtml(s.url)})</option>`).join('')}
                            </select>
                        ` : ''}
                        <input type="text" id="base-url" value="${escapeHtml(activeBaseUrl)}" class="w-full rounded-2xl border border-white/8 bg-slate-950/70 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    ${endpoint.parameters?.path?.length ? `
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-300">Path Parameters</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                ${endpoint.parameters.path.map(p => `
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1.5">${p.name}</label>
                                        <input type="text" id="param-${p.name}" placeholder="${p.type === 'integer' ? '1' : 'value'}" class="w-full rounded-xl border border-white/8 bg-slate-950/70 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    ${queryParamsSection}
                    ${exampleSection}
                    ${bodyInputSection}
                    <button onclick="sendRequest(this)" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-500 px-5 py-3 text-sm font-medium text-white transition-colors hover:bg-indigo-400 sm:w-auto">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                    <div id="response" class="mt-4 hidden overflow-hidden rounded-3xl border border-white/8 bg-slate-950/45">
                        <div class="flex items-center justify-between border-b border-white/6 px-4 py-3 lg:px-5">
                            <span class="text-sm font-semibold text-slate-200"><i class="fas fa-terminal mr-2 text-slate-400"></i>Response</span>
                            <div class="flex items-center gap-2">
                                <span id="response-status" class="status-badge"></span>
                                <span id="response-time" class="flex items-center gap-1 rounded-md border border-white/8 bg-white/5 px-2 py-1 text-xs text-slate-400">
                                    <i class="fas fa-clock"></i>
                                    <span id="response-time-val"></span>
                                </span>
                                <button onclick="copyResponse()" id="copy-response-btn" class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300 transition-colors hover:bg-white/10">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        <div id="response-body" class="max-h-96 overflow-auto"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    setActiveBaseUrl(activeBaseUrl);
}

// ─── Helpers ───
function addQueryParam() {
    const container = document.getElementById('query-params');
    const row = document.createElement('div');
    row.className = 'flex gap-2 query-param-row';
    row.innerHTML = `
        <input type="text" placeholder="key" class="query-key min-w-0 flex-1 rounded-xl border border-white/8 bg-slate-950/70 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="text" placeholder="value" class="query-value min-w-0 flex-1 rounded-xl border border-white/8 bg-slate-950/70 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button onclick="this.parentElement.remove()" class="px-2 text-slate-500 hover:text-red-400 flex-shrink-0"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}

function generateSampleBody(endpoint) {
    const sample = {};
    for (const [name, field] of Object.entries(endpoint.parameters.body || {})) {
        sample[name] = getExampleValue(name, field.type, field.rules);
    }
    return JSON.stringify(sample, null, 2);
}

function getExampleValue(name, type, rules) {
    const n = name.toLowerCase();
    if (n.includes('email') || type === 'email') return 'john.doe@example.com';
    if (n.includes('password') || n.includes('pass')) return 'Password123!';
    if (n.includes('phone') || n.includes('mobile')) return '+6281234567890';
    if (n.includes('url') || n.includes('link') || n.includes('website')) return 'https://example.com';
    if (n.includes('image') || n.includes('photo') || n.includes('avatar')) return 'https://example.com/images/photo.jpg';
    if (n.includes('date') || n.includes('_at') || n.includes('time')) return new Date().toISOString().slice(0, 19) + '.000000Z';
    if (n.includes('status')) return 'active';
    if (n.includes('title') || n.includes('subject')) return 'Example Title';
    if (n.includes('name')) return 'John Doe';
    if (n.includes('description') || n.includes('content') || n.includes('body')) return 'This is an example description.';
    if (n.includes('slug')) return 'example-slug';
    if (n.includes('price') || n.includes('cost') || n.includes('amount')) return 99.99;
    if (n.includes('quantity') || n.includes('stock') || n.includes('qty')) return 10;
    if (n.includes('_id') || n.includes('category')) return 1;
    if (type === 'boolean') return true;
    if (type === 'integer' || type === 'int' || type === 'bigint') return 1;
    if (type === 'number' || type === 'float' || type === 'double' || type === 'decimal') return 1.5;
    if (type === 'array') return ['item1', 'item2'];
    return 'example_value';
}

function copyExample() {
    const text = document.getElementById('example-body').textContent;
    copyWithFeedback('copy-example-btn', text);
}

function useExample() {
    const text = document.getElementById('example-body').textContent;
    document.getElementById('request-body').value = text;
    showToast('Example loaded!');
}

// ─── Code Snippets ───
let currentSnippets = {};

async function loadCodeSnippets(method, path) {
    const panel = document.getElementById('code-snippets-panel');
    panel.classList.remove('hidden');
    document.getElementById('snippet-code').textContent = 'Loading...';

    try {
        const baseUrl = getActiveBaseUrl();
        const response = await fetch(`${getDocsUrl('codeSnippet')}?method=${method}&path=${encodeURIComponent(path)}&base_url=${encodeURIComponent(baseUrl)}`);
        const data = await response.json();
        if (data.snippets) {
            currentSnippets = data.snippets;
            showSnippetTab('curl');
        } else {
            document.getElementById('snippet-code').textContent = 'No snippets available.';
        }
    } catch (e) {
        const endpoint = schema.endpoints[path]?.[method];
        if (endpoint) {
            currentSnippets = generateLocalSnippets(method, path, endpoint, getActiveBaseUrl());
            showSnippetTab('curl');
        } else {
            document.getElementById('snippet-code').textContent = 'Error loading snippets.';
        }
    }
}

function getSuggestedWebSocketUrl() {
    try {
        const currentBaseUrl = new URL(getActiveBaseUrl());
        const protocol = currentBaseUrl.protocol === 'https:' ? 'wss:' : 'ws:';

        return `${protocol}//${currentBaseUrl.host}`;
    } catch (e) {
        return 'ws://localhost:8080';
    }
}

function generateLocalSnippets(method, path, endpoint, baseUrl) {
    const url = baseUrl + path;
    return {
        curl: `curl -X ${method.toUpperCase()} \\\n  '${url}' \\\n  -H 'Accept: application/json'${endpoint.security?.some(s => s.type === 'http') ? " \\\n  -H 'Authorization: Bearer YOUR_TOKEN'" : ''}`,
        javascript: `const response = await fetch('${url}', {\n  method: '${method.toUpperCase()}',\n  headers: {\n    'Accept': 'application/json',${endpoint.security?.some(s => s.type === 'http') ? "\n    'Authorization': \`Bearer \${token}\`," : ''}\n  },\n});\n\nconst data = await response.json();`,
        php: `$response = Http::${endpoint.security?.some(s => s.type === 'http') ? "withToken('YOUR_TOKEN')->" : ''}${method}('${url}');\n\n$data = $response->json();`,
        python: `import requests\n\nheaders = {'Accept': 'application/json'}${endpoint.security?.some(s => s.type === 'http') ? "\nheaders['Authorization'] = 'Bearer YOUR_TOKEN'" : ''}\n\nresponse = requests.${method}('${url}', headers=headers)\nresult = response.json()`,
        dart: `import 'package:http/http.dart' as http;\nimport 'dart:convert';\n\nvar url = Uri.parse('${url}');\nvar headers = {'Accept': 'application/json'};${endpoint.security?.some(s => s.type === 'http') ? "\nheaders['Authorization'] = 'Bearer YOUR_TOKEN';" : ''}\n\nvar response = await http.${method}(url, headers: headers);\nvar data = jsonDecode(response.body);`,
        swift: `import Foundation\n\nvar request = URLRequest(url: URL(string: "${url}")!)\nrequest.httpMethod = "${method.toUpperCase()}"\nrequest.addValue("application/json", forHTTPHeaderField: "Accept")${endpoint.security?.some(s => s.type === 'http') ? "\nrequest.addValue(\"Bearer YOUR_TOKEN\", forHTTPHeaderField: \"Authorization\")" : ''}\n\nlet task = URLSession.shared.dataTask(with: request) { data, response, error in\n    guard let data = data else { return }\n    print(String(data: data, encoding: .utf8)!)\n}\ntask.resume()`,
        go: `package main\n\nimport (\n\t"fmt"\n\t"net/http"\n\t"io"\n)\n\nfunc main() {\n\treq, _ := http.NewRequest("${method.toUpperCase()}", "${url}", nil)\n\treq.Header.Add("Accept", "application/json")${endpoint.security?.some(s => s.type === 'http') ? "\n\treq.Header.Add(\"Authorization\", \"Bearer YOUR_TOKEN\")" : ''}\n\tres, _ := http.DefaultClient.Do(req)\n\tdefer res.Body.Close()\n\trespBody, _ := io.ReadAll(res.Body)\n\tfmt.Println(string(respBody))\n}`
    };
}

function showSnippetTab(lang) {
    document.querySelectorAll('.snippet-tab').forEach(tab => {
        tab.classList.remove('text-white', 'border-indigo-400');
        tab.classList.add('text-slate-400', 'border-transparent');
    });
    const activeTab = document.querySelector(`.snippet-tab[data-tab="${lang}"]`);
    if (activeTab) {
        activeTab.classList.add('text-white', 'border-indigo-400');
        activeTab.classList.remove('text-slate-400', 'border-transparent');
    }
    document.getElementById('snippet-code').textContent = currentSnippets[lang] || 'No snippet available.';
}

// ─── Webhooks ───
function renderWebhooksBadge() {
    if (schema.webhooks && schema.webhooks.length > 0) {
        const container = document.querySelector('#endpoints-list');
        if (container) {
            const webhookSection = document.createElement('div');
            webhookSection.className = 'mt-4 mb-2';
            webhookSection.innerHTML = `
                <button onclick="showWebhooksPanel()" class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider group-header hover:bg-white/5">
                    <span><i class="fas fa-broadcast-tower mr-2 text-amber-400/60"></i>Webhooks</span>
                    <span class="text-xs font-normal normal-case text-slate-600">${schema.webhooks.length}</span>
                </button>
            `;
            container.prepend(webhookSection);
        }
    }
}

function showWebhooksPanel() {
    if (!schema.webhooks || schema.webhooks.length === 0) return;
    renderEndpoints(document.getElementById('search').value);
    const container = document.getElementById('content-area');
    container.innerHTML = `
        <div class="space-y-4 max-w-4xl mx-auto w-full">
            <div class="bg-slate-900/50 backdrop-blur-xl rounded-2xl border border-white/5 overflow-hidden shadow-xl shadow-black/20">
                <div class="px-4 lg:px-6 py-4 border-b border-white/5 bg-slate-800/20 flex items-center gap-3">
                    <i class="fas fa-broadcast-tower text-amber-400"></i>
                    <h3 class="text-lg font-semibold text-white">Webhook Events</h3>
                </div>
                <div class="divide-y divide-slate-700/50">
                    ${schema.webhooks.map(w => `
                        <div class="px-4 lg:px-6 py-4 hover:bg-slate-800/20 transition-colors">
                            <div class="flex items-center gap-3 mb-2">
                                <code class="text-sm text-amber-400 font-mono bg-amber-400/10 px-2 py-0.5 rounded">${escapeHtml(w.event)}</code>
                            </div>
                            ${w.description ? `<p class="text-sm text-slate-400 mb-2">${escapeHtml(w.description)}</p>` : ''}
                            ${w.payload ? `<pre class="text-xs text-green-400 bg-slate-950 rounded-lg p-3 overflow-x-auto"><code>${JSON.stringify(w.payload, null, 2)}</code></pre>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

// ─── Realtime Events ───
function renderEventsBadge() {
    const keys = Object.keys(schema.events || {});
    if (keys.length > 0) {
        const container = document.querySelector('#endpoints-list');
        if (container) {
            const eventSection = document.createElement('div');
            eventSection.className = 'mt-4 mb-2';
            eventSection.innerHTML = `
                <button onclick="showEventsPanel()" class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider group-header hover:bg-white/5">
                    <span><i class="fas fa-bolt mr-2 text-yellow-400/60"></i>WebSockets</span>
                    <span class="text-xs font-normal normal-case text-slate-600">${keys.length}</span>
                </button>
            `;
            container.prepend(eventSection);
        }
    }
}

function showEventsPanel() {
    const keys = Object.keys(schema.events || {});
    if (keys.length === 0) return;

    selectedPath = null;
    selectedMethod = null;
    renderEndpoints(document.getElementById('search').value);

    closeActiveWebSocket();

    const container = document.getElementById('content-area');
    container.innerHTML = `
        <div class="space-y-6 max-w-4xl mx-auto w-full">
            <div class="bg-slate-900/50 backdrop-blur-xl rounded-2xl border border-white/5 overflow-hidden shadow-xl shadow-black/20">
                <div class="px-4 lg:px-6 py-4 border-b border-white/5 bg-slate-800/20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-bolt text-yellow-400"></i>
                        <h3 class="text-lg font-semibold text-white">WebSocket Events (Broadcasts)</h3>
                    </div>
                </div>
                <div class="p-4 lg:px-6 bg-yellow-500/10 border-b border-yellow-500/20 text-yellow-400 text-sm flex gap-3">
                    <i class="fas fa-info-circle mt-0.5"></i>
                    <div>
                        <strong>Realtime Events:</strong> Clients can subscribe to these channels to receive real-time updates via Laravel Reverb, Pusher, or Soketi.
                    </div>
                </div>
                <div class="divide-y divide-slate-700/50">
                    ${keys.map(key => {
                        const e = schema.events[key];
                        return `
                        <div class="px-4 lg:px-6 py-4 hover:bg-slate-800/20 transition-colors">
                            <div class="flex items-center gap-3 mb-2">
                                <code class="text-sm text-yellow-400 font-mono bg-yellow-400/10 px-2 py-0.5 rounded">${escapeHtml(e.name)}</code>
                                <span class="text-xs text-slate-500 font-mono"><i class="fas fa-tv mr-1"></i>${escapeHtml(e.channel)}</span>
                            </div>
                            ${e.description ? `<p class="text-sm text-slate-400 mb-2">${escapeHtml(e.description)}</p>` : ''}
                            ${e.payload ? `<div class="mt-3"><span class="text-xs text-slate-500 mb-1 block uppercase tracking-wider font-semibold">Payload</span><pre class="text-xs text-green-400 bg-slate-950 rounded-lg p-3 overflow-x-auto border border-white/5"><code>${JSON.stringify(e.payload, null, 2)}</code></pre></div>` : ''}
                        </div>
                        `;
                    }).join('')}
                </div>
            </div>

            <!-- WebSocket Live Tester -->
            <div class="bg-slate-900/50 backdrop-blur-xl rounded-2xl border border-white/5 overflow-hidden shadow-xl shadow-black/20 flex flex-col h-[500px]">
                <div class="px-4 py-3 border-b border-white/5 bg-slate-800/20 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-stethoscope text-cyan-400"></i>
                        <h3 class="font-semibold text-white text-sm">WebSocket Live Tester</h3>
                    </div>
                    <div id="ws-status" class="flex items-center gap-2 text-xs font-mono text-slate-500">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div> Disconnected
                    </div>
                </div>

                <div class="p-4 border-b border-white/5 flex gap-2">
                    <input type="text" id="ws-url" value="${escapeHtml(getSuggestedWebSocketUrl())}" class="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-slate-300 focus:outline-none focus:border-indigo-500" placeholder="ws:// or wss:// URL">
                    <button id="ws-connect-btn" onclick="toggleWebSocket()" class="px-4 py-1.5 bg-indigo-500 text-white rounded-lg text-sm font-medium hover:bg-indigo-600 transition-colors">
                        Connect
                    </button>
                    <button onclick="document.getElementById('ws-log').innerHTML=''" class="px-3 py-1.5 bg-slate-800 text-slate-300 rounded-lg text-sm hover:bg-slate-700 transition-colors" title="Clear Logs">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div id="ws-log" class="flex-1 overflow-y-auto p-4 space-y-2 font-mono text-xs bg-slate-950/50">
                    <div class="text-slate-500 italic">Enter a WebSocket URL and click Connect.</div>
                </div>

                <div class="p-3 border-t border-white/5 bg-slate-800/20 flex gap-2">
                    <input type="text" id="ws-message" class="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-sm text-slate-300 focus:outline-none focus:border-indigo-500 disabled:opacity-50" placeholder="Message payload (JSON or text)" disabled onkeypress="if(event.key === 'Enter') sendWsMessage()">
                    <button id="ws-send-btn" onclick="sendWsMessage()" class="px-4 py-1.5 bg-slate-700 text-white rounded-lg text-sm font-medium hover:bg-slate-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Send
                    </button>
                </div>
            </div>
        </div>
    `;

    if (window.innerWidth < 1024) {
        closeMobileSidebar();
    }
}

let activeWebSocket = null;

// ─── WebSocket Cleanup Helper ───
function closeActiveWebSocket() {
    if (activeWebSocket) {
        activeWebSocket.onclose = null; // prevent reconnect attempts or UI updates
        activeWebSocket.onerror = null;
        activeWebSocket.onmessage = null;
        activeWebSocket.onopen = null;
        activeWebSocket.close();
        activeWebSocket = null;
    }
}

// Clean up WebSocket on page unload
window.addEventListener('beforeunload', () => {
    closeActiveWebSocket();
});

function toggleWebSocket() {
    if (activeWebSocket) {
        // Manually close the WebSocket by clearing handlers and closing
        const ws = activeWebSocket;
        ws.onclose = null;
        ws.onerror = null;
        ws.onmessage = null;
        ws.onopen = null;
        ws.close();
        activeWebSocket = null;

        // Reset UI
        const btn = document.getElementById('ws-connect-btn');
        const status = document.getElementById('ws-status');
        const msgInput = document.getElementById('ws-message');
        const sendBtn = document.getElementById('ws-send-btn');

        btn.textContent = 'Connect';
        btn.classList.replace('bg-red-500', 'bg-indigo-500');
        btn.classList.replace('hover:bg-red-600', 'hover:bg-indigo-600');
        btn.disabled = false;

        status.innerHTML = `<div class="w-2 h-2 rounded-full bg-red-500"></div> Disconnected`;
        msgInput.disabled = true;
        sendBtn.disabled = true;

        return;
    }

    const url = document.getElementById('ws-url').value.trim();
    if (!url) return showToast('Please enter a WebSocket URL');

    const btn = document.getElementById('ws-connect-btn');
    const status = document.getElementById('ws-status');
    const log = document.getElementById('ws-log');
    const msgInput = document.getElementById('ws-message');
    const sendBtn = document.getElementById('ws-send-btn');

    btn.textContent = 'Connecting...';
    btn.disabled = true;

    try {
        activeWebSocket = new WebSocket(url);

        activeWebSocket.onopen = () => {
            btn.textContent = 'Disconnect';
            btn.classList.replace('bg-indigo-500', 'bg-red-500');
            btn.classList.replace('hover:bg-indigo-600', 'hover:bg-red-600');
            btn.disabled = false;

            status.innerHTML = `<div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div> Connected`;
            msgInput.disabled = false;
            sendBtn.disabled = false;

            appendWsLog('Connected to ' + url, 'text-green-400');
        };

        activeWebSocket.onclose = () => {
            btn.textContent = 'Connect';
            btn.classList.replace('bg-red-500', 'bg-indigo-500');
            btn.classList.replace('hover:bg-red-600', 'hover:bg-indigo-600');
            btn.disabled = false;

            status.innerHTML = `<div class="w-2 h-2 rounded-full bg-red-500"></div> Disconnected`;
            msgInput.disabled = true;
            sendBtn.disabled = true;
            activeWebSocket = null;

            appendWsLog('Disconnected', 'text-slate-500');
        };

        activeWebSocket.onerror = (err) => {
            console.error('WebSocket Error', err);
            appendWsLog('Error: Connection failed. Check console for details.', 'text-red-400');
            if (activeWebSocket) activeWebSocket.close();
        };

        activeWebSocket.onmessage = (event) => {
            let data = event.data;
            try {
                // Try to format nice JSON
                const parsed = JSON.parse(data);
                data = JSON.stringify(parsed, null, 2);
            } catch (e) {}
            appendWsLog('↓ ' + data, 'text-cyan-400');
        };

    } catch (err) {
        btn.textContent = 'Connect';
        btn.disabled = false;
        appendWsLog('Invalid URL: ' + err.message, 'text-red-400');
        showToast('Invalid WebSocket URL');
    }
}

function sendWsMessage() {
    if (!activeWebSocket || activeWebSocket.readyState !== WebSocket.OPEN) return;
    const input = document.getElementById('ws-message');
    const data = input.value;
    if (!data) return;

    activeWebSocket.send(data);
    appendWsLog('↑ ' + data, 'text-slate-300');
    input.value = '';
}

function appendWsLog(message, colorClass) {
    const log = document.getElementById('ws-log');
    if (!log) return;

    // Remove initial placeholder if present
    if (log.children.length === 1 && log.children[0].classList.contains('italic')) {
        log.innerHTML = '';
    }

    const time = new Date().toLocaleTimeString([], { hour12: false });
    const line = document.createElement('div');
    line.className = 'border-b border-white/5 pb-2 mb-2 last:border-0';

    // Check if multiline to wrap in pre/code
    if (message.includes('\n') || message.includes('{\n')) {
        line.innerHTML = `<span class="text-slate-600 mr-2">[${time}]</span><pre class="${colorClass} mt-1 ml-4 whitespace-pre-wrap"><code>${escapeHtml(message)}</code></pre>`;
    } else {
        line.innerHTML = `<span class="text-slate-600 mr-2">[${time}]</span><span class="${colorClass} break-all">${escapeHtml(message)}</span>`;
    }

    log.appendChild(line);
    log.scrollTop = log.scrollHeight;
}
