<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        code, pre, .font-mono { font-family: 'JetBrains Mono', monospace !important; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }

        .method-badge {
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.2rem 0.45rem;
            border-radius: 0.375rem;
            flex-shrink: 0;
        }
        .method-get { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .method-post { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .method-put { background: rgba(249, 115, 22, 0.2); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3); }
        .method-patch { background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid rgba(234, 179, 8, 0.3); }
        .method-delete { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            letter-spacing: 0.025em;
        }
        .status-2xx { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.25); }
        .status-3xx { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.25); }
        .status-4xx { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.25); }
        .status-5xx { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); }

        .sidebar-item { transition: all 0.15s ease; }
        .sidebar-item:hover { background: rgba(99, 102, 241, 0.1); }
        .sidebar-item.active { background: rgba(99, 102, 241, 0.15); border-left: 2px solid #6366f1; }

        .error-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 1rem;
        }
        .error-icon-wrapper {
            width: 3rem; height: 3rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 0.75rem;
            background: rgba(239, 68, 68, 0.15);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }
        .success-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 1rem;
        }
        .success-icon-wrapper {
            width: 3rem; height: 3rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 0.75rem;
            background: rgba(34, 197, 94, 0.15);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
        }
        .code-inline {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }

        .toast {
            animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.2s forwards;
        }
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* Mobile sidebar overlay */
        .sidebar-overlay {
            transition: opacity 0.3s ease;
        }
        .sidebar-panel {
            transition: transform 0.3s ease;
        }

        .group-header { cursor: pointer; user-select: none; }
        .group-header:hover { background: rgba(255, 255, 255, 0.03); }
    </style>
</head>
<body class="bg-slate-950 text-white">
    <div id="app">
        <!-- Loading -->
        <div id="loading" class="fixed inset-0 flex items-center justify-center bg-slate-950 z-50">
            <div class="text-center">
                <div class="w-12 h-12 border-[3px] border-slate-700 border-t-indigo-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-slate-400">Loading documentation...</p>
            </div>
        </div>

        <!-- Main Layout -->
        <div id="main-content" class="hidden h-screen flex">
            <!-- Mobile Header -->
            <div class="lg:hidden fixed top-0 left-0 right-0 bg-slate-900 border-b border-slate-800 px-4 py-3 flex items-center justify-between z-30">
                <button onclick="toggleMobileSidebar()" class="p-2 rounded-lg hover:bg-slate-800 transition-colors">
                    <i class="fas fa-bars text-slate-300"></i>
                </button>
                <h2 class="font-semibold text-sm truncate" id="mobile-title">API Documentation</h2>
                <button onclick="openAuthModal()" class="p-2 rounded-lg hover:bg-slate-800 transition-colors">
                    <i class="fas fa-key text-slate-300"></i>
                </button>
            </div>

            <!-- Mobile Sidebar Overlay -->
            <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden sidebar-overlay" onclick="closeMobileSidebar()"></div>

            <!-- Sidebar -->
            <aside id="sidebar" class="fixed lg:relative w-72 bg-slate-900 border-r border-slate-800 flex flex-col h-full z-50 -translate-x-full lg:translate-x-0 sidebar-panel">
                <div class="p-4 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <i class="fas fa-bolt text-white"></i>
                        </div>
                        <div>
                            <h1 class="font-bold text-lg">API Magic</h1>
                            <p class="text-xs text-slate-500">v<span id="version">1.0.0</span></p>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-b border-slate-800 space-y-3">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" id="search" placeholder="Search... (Ctrl+K)" class="w-full pl-9 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <!-- Version Filter -->
                    <div id="version-filter-container" class="hidden">
                        <select id="version-filter" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Versions</option>
                        </select>
                    </div>
                </div>
                <nav id="endpoints-list" class="flex-1 overflow-y-auto p-3 space-y-1"></nav>
                <div class="p-4 border-t border-slate-800">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-slate-800 rounded-lg p-2">
                            <div id="endpoint-count" class="text-lg font-bold text-indigo-400">0</div>
                            <div class="text-xs text-slate-500">Endpoints</div>
                        </div>
                        <div class="bg-slate-800 rounded-lg p-2">
                            <div id="success-rate" class="text-lg font-bold text-green-400">100%</div>
                            <div class="text-xs text-slate-500">Success</div>
                        </div>
                        <div class="bg-slate-800 rounded-lg p-2">
                            <div id="request-count" class="text-lg font-bold text-slate-400">0</div>
                            <div class="text-xs text-slate-500">Requests</div>
                        </div>
                    </div>
                    <a href="/docs/export" target="_blank" class="mt-3 w-full flex items-center justify-center gap-2 px-3 py-2 bg-slate-800 rounded-lg text-xs text-slate-400 hover:bg-slate-700 hover:text-slate-200 transition-colors">
                        <i class="fas fa-download"></i> Export OpenAPI
                    </a>
                </div>
            </aside>

            <!-- Main -->
            <main class="flex-1 flex flex-col overflow-hidden bg-slate-900 pt-14 lg:pt-0">
                <header class="hidden lg:flex border-b border-slate-800 px-6 py-4 items-center justify-between">
                    <h2 class="font-semibold text-lg" id="schema-title">API Documentation</h2>
                    <button onclick="openAuthModal()" class="px-4 py-2 bg-slate-800 rounded-lg text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors">
                        <i class="fas fa-key"></i><span id="auth-status">Set Token</span>
                    </button>
                </header>
                <div class="flex-1 overflow-y-auto p-4 lg:p-6" id="content-area">
                    <div class="text-center py-16 lg:py-20">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center">
                            <i class="fas fa-rocket text-3xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Welcome to API Magic</h3>
                        <p class="text-slate-400 mb-6">Your advanced API documentation and testing platform</p>
                        <div class="inline-flex flex-wrap justify-center items-center gap-2 text-sm text-slate-500">
                            <span class="px-3 py-1.5 rounded-lg flex items-center gap-2" style="background: rgba(59, 130, 246, 0.1);">
                                <span class="method-badge method-get">GET</span> Retrieve
                            </span>
                            <span class="px-3 py-1.5 rounded-lg flex items-center gap-2" style="background: rgba(34, 197, 94, 0.1);">
                                <span class="method-badge method-post">POST</span> Create
                            </span>
                            <span class="px-3 py-1.5 rounded-lg flex items-center gap-2" style="background: rgba(249, 115, 22, 0.1);">
                                <span class="method-badge method-put">PUT</span> Update
                            </span>
                            <span class="px-3 py-1.5 rounded-lg flex items-center gap-2" style="background: rgba(239, 68, 68, 0.1);">
                                <span class="method-badge method-delete">DELETE</span> Delete
                            </span>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Auth Modal -->
        <div id="auth-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-700">
                <div class="p-6">
                    <h3 class="text-lg font-bold mb-4">Set Bearer Token</h3>
                    <p class="text-sm text-slate-400 mb-4">Enter your Laravel Sanctum/Passport token for authenticated requests.</p>
                    <input type="password" id="auth-token" placeholder="1|abc123xyz..." class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono mb-4 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <div class="flex gap-3">
                        <button onclick="saveToken()" class="flex-1 px-4 py-2.5 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 font-medium transition-colors">Save Token</button>
                        <button onclick="clearToken()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors">Clear</button>
                        <button onclick="closeAuthModal()" class="px-4 py-2.5 bg-slate-700 text-slate-300 rounded-lg hover:bg-slate-600 transition-colors">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let schema = { endpoints: {}, baseUrl: window.location.origin };
        let selectedPath = null;
        let selectedMethod = null;
        let token = localStorage.getItem('api-docs-token') || '';
        let requestCount = 0;
        let successCount = 0;
        let selectedVersion = 'all';
        let collapsedGroups = JSON.parse(localStorage.getItem('api-docs-collapsed') || '{}');

        // Initialize
        async function init() {
            try {
                const res = await fetch('/api/docs/json');
                schema = await res.json();
                document.getElementById('version').textContent = schema.version;
                document.getElementById('schema-title').textContent = schema.title;
                document.getElementById('mobile-title').textContent = schema.title;
                document.getElementById('endpoint-count').textContent = countEndpoints();
                updateStats();
                setupVersionFilter();
                renderEndpoints();
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('main-content').classList.remove('hidden');
            } catch (e) {
                console.error('Failed to load:', e);
                document.getElementById('loading').innerHTML = `
                    <div class="text-center px-6">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                        <p class="text-red-400 font-medium">Failed to load documentation</p>
                        <p class="text-slate-500 text-sm mt-2">${e.message}</p>
                        <button onclick="init()" class="mt-4 px-4 py-2 bg-indigo-500 rounded-lg text-white text-sm hover:bg-indigo-600 transition-colors">
                            <i class="fas fa-redo mr-2"></i>Retry
                        </button>
                    </div>
                `;
            }
        }

        function countEndpoints() {
            let count = 0;
            for (const path in schema.endpoints) {
                count += Object.keys(schema.endpoints[path]).length;
            }
            return count;
        }

        function updateStats() {
            const rate = requestCount > 0 ? Math.round((successCount / requestCount) * 100) : 100;
            document.getElementById('success-rate').textContent = rate + '%';
            document.getElementById('request-count').textContent = requestCount;
            if (token) {
                document.getElementById('auth-status').textContent = 'Authenticated';
            }
        }

        function setupVersionFilter() {
            if (!schema.versions || schema.versions.length <= 1) return;
            const container = document.getElementById('version-filter-container');
            const select = document.getElementById('version-filter');
            container.classList.remove('hidden');
            schema.versions.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v;
                opt.textContent = `Version ${v}`;
                select.appendChild(opt);
            });
            select.addEventListener('change', (e) => {
                selectedVersion = e.target.value;
                renderEndpoints();
            });
        }

        function renderEndpoints(filter = '') {
            const container = document.getElementById('endpoints-list');
            container.innerHTML = '';
            const query = filter.toLowerCase();

            // Group endpoints by resource (first URI segment after api/ and version)
            const groups = {};
            for (const [path, methods] of Object.entries(schema.endpoints)) {
                for (const [method, endpoint] of Object.entries(methods)) {
                    // Version filter
                    if (selectedVersion !== 'all' && endpoint.version !== selectedVersion) continue;

                    const summary = endpoint.summary || path;
                    if (query && !path.toLowerCase().includes(query) && !summary.toLowerCase().includes(query)) continue;

                    // Extract resource name for grouping
                    const tag = (endpoint.tags && endpoint.tags[0]) || extractResourceFromPath(path);
                    if (!groups[tag]) groups[tag] = [];
                    groups[tag].push({ path, method, endpoint, summary });
                }
            }

            // Render grouped endpoints
            for (const [groupName, items] of Object.entries(groups)) {
                const isCollapsed = collapsedGroups[groupName] === true;
                const groupDiv = document.createElement('div');
                groupDiv.className = 'mb-2';
                groupDiv.innerHTML = `
                    <button onclick="toggleGroup('${groupName}', this)" class="w-full text-left px-3 py-2 rounded-lg flex items-center justify-between text-xs font-semibold text-slate-400 uppercase tracking-wider group-header">
                        <span><i class="fas fa-folder-open mr-2 text-indigo-400/60"></i>${groupName}</span>
                        <span class="text-slate-600">
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
        }

        function extractResourceFromPath(path) {
            const cleaned = path.replace(/^\/?(api\/)?v?\d*\/?/i, '');
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

        function renderEndpointDetail(path, method, endpoint) {
            const container = document.getElementById('content-area');

            let paramsHtml = '';
            if (endpoint.parameters?.path?.length) {
                paramsHtml += `
                    <div class="mb-4">
                        <h5 class="text-sm font-semibold mb-3 flex items-center gap-2">
                            <i class="fas fa-route text-blue-400"></i> Path Parameters
                        </h5>
                        <div class="rounded-lg border border-slate-700 overflow-hidden">
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
            if (endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post','put','patch'].includes(method)) {
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

            const hasBody = endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post','put','patch'].includes(method);
            const exampleSection = hasBody ? `
                <div class="p-4 lg:p-5 bg-slate-900 rounded-xl border border-slate-700 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-code text-indigo-400"></i>
                            <span class="text-sm font-semibold text-slate-200">Example Value</span>
                        </div>
                        <button onclick="copyExample()" id="copy-example-btn" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre class="text-sm text-green-400 p-4 bg-slate-950 rounded-lg overflow-x-auto"><code id="example-body">${generateSampleBody(endpoint)}</code></pre>
                </div>
            ` : '';

            const bodyInputSection = hasBody ? `
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-slate-300">Request Body (JSON)</label>
                        <button onclick="useExample()" class="text-xs px-3 py-1.5 rounded-lg bg-indigo-500/20 text-indigo-400 hover:bg-indigo-500/30 flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-magic"></i> Fill with Example
                        </button>
                    </div>
                    <textarea id="request-body" rows="8" class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{}</textarea>
                </div>
            ` : '';

            // Query params section for GET/DELETE
            const queryParamsSection = ['get', 'delete'].includes(method) ? `
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-slate-300">Query Parameters</label>
                        <button onclick="addQueryParam()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="query-params" class="space-y-2">
                        ${method === 'get' ? `
                            <div class="flex gap-2 query-param-row">
                                <input type="text" value="page" placeholder="key" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-key">
                                <input type="text" value="1" placeholder="value" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-value">
                                <button onclick="this.parentElement.remove()" class="px-2 text-slate-500 hover:text-red-400"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="flex gap-2 query-param-row">
                                <input type="text" value="per_page" placeholder="key" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-key">
                                <input type="text" value="15" placeholder="value" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-value">
                                <button onclick="this.parentElement.remove()" class="px-2 text-slate-500 hover:text-red-400"><i class="fas fa-times"></i></button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            ` : '';

            container.innerHTML = `
                <div class="space-y-4 lg:space-y-6 max-w-4xl">
                    <!-- Endpoint Header -->
                    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-3 px-4 lg:px-5 py-3 lg:py-4 border-b border-slate-700">
                            <span class="method-badge method-${method}">${method.toUpperCase()}</span>
                            <code class="text-sm text-slate-200 font-mono truncate">${path}</code>
                            ${endpoint.security && endpoint.security.length ? `<span class="ml-auto px-2 py-1 rounded-full text-xs bg-red-400/10 text-red-400 flex items-center gap-1.5 flex-shrink-0"><i class="fas fa-lock text-xs"></i> Auth</span>` : ''}
                        </div>
                        <div class="px-4 lg:px-5 py-3 lg:py-4">
                            <h3 class="text-base lg:text-lg font-semibold text-white">${endpoint.summary || path}</h3>
                            ${endpoint.description ? `<p class="text-slate-400 text-sm mt-1">${endpoint.description}</p>` : ''}
                        </div>
                    </div>
                    ${paramsHtml || bodyHtml ? `<div class="bg-slate-800 rounded-xl p-4 lg:p-6 border border-slate-700">${paramsHtml}${bodyHtml}</div>` : ''}
                    <div class="bg-slate-800 rounded-xl p-4 lg:p-6 border border-slate-700">
                        <h4 class="font-semibold mb-5 flex items-center gap-2">
                            <i class="fas fa-play-circle text-indigo-400"></i> Try it out
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Base URL</label>
                                <input type="text" id="base-url" value="${schema.baseUrl}" class="w-full px-4 py-2.5 bg-slate-900 border border-slate-700 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            ${endpoint.parameters?.path?.length ? `
                                <div>
                                    <label class="block text-sm font-medium text-slate-300 mb-2">Path Parameters</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        ${endpoint.parameters.path.map(p => `
                                            <div>
                                                <label class="block text-xs text-slate-500 mb-1.5">${p.name}</label>
                                                <input type="text" id="param-${p.name}" placeholder="${p.type === 'integer' ? '1' : 'value'}" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            ${queryParamsSection}
                            ${exampleSection}
                            ${bodyInputSection}
                            <button onclick="sendRequest(this)" class="w-full sm:w-auto px-6 py-3 bg-indigo-500 text-white rounded-lg font-medium hover:bg-indigo-600 flex items-center justify-center gap-2 transition-colors">
                                <i class="fas fa-paper-plane"></i> Send Request
                            </button>
                            <div id="response" class="hidden rounded-xl overflow-hidden border border-slate-700">
                                <div class="px-4 lg:px-5 py-3 lg:py-4 bg-slate-900 flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-200">Response</span>
                                    <div class="flex items-center gap-3">
                                        <span id="response-status" class="status-badge"></span>
                                        <span id="response-time" class="text-xs text-slate-400 flex items-center gap-1">
                                            <i class="fas fa-clock"></i>
                                            <span id="response-time-val"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center px-4 lg:px-5 py-3 bg-slate-800 border-b border-slate-700">
                                    <span class="text-xs text-slate-400">Response Body</span>
                                    <button onclick="copyResponse()" id="copy-response-btn" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 flex items-center gap-1.5 transition-colors">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div id="response-body" class="max-h-96 overflow-auto"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function addQueryParam() {
            const container = document.getElementById('query-params');
            const row = document.createElement('div');
            row.className = 'flex gap-2 query-param-row';
            row.innerHTML = `
                <input type="text" placeholder="key" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-key">
                <input type="text" placeholder="value" class="flex-1 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 query-value">
                <button onclick="this.parentElement.remove()" class="px-2 text-slate-500 hover:text-red-400"><i class="fas fa-times"></i></button>
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

        function copyWithFeedback(btnId, text) {
            navigator.clipboard.writeText(text);
            const btn = document.getElementById(btnId);
            if (btn) {
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check text-green-400"></i> Copied!';
                setTimeout(() => { btn.innerHTML = original; }, 1500);
            }
            showToast('Copied to clipboard!');
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

        async function sendRequest(btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            btn.disabled = true;

            const baseUrl = document.getElementById('base-url').value;
            let url = selectedPath;

            const endpoint = schema.endpoints[selectedPath][selectedMethod];
            if (endpoint.parameters?.path) {
                endpoint.parameters.path.forEach(p => {
                    const val = document.getElementById('param-' + p.name)?.value || '1';
                    url = url.replace('{' + p.name + '}', encodeURIComponent(val));
                });
            }

            // Build query string
            const queryRows = document.querySelectorAll('.query-param-row');
            const params = new URLSearchParams();
            queryRows.forEach(row => {
                const key = row.querySelector('.query-key')?.value;
                const val = row.querySelector('.query-value')?.value;
                if (key && val) params.append(key, val);
            });
            const qs = params.toString();
            if (qs) url += '?' + qs;

            const options = {
                method: selectedMethod.toUpperCase(),
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
            };

            if (token) options.headers['Authorization'] = 'Bearer ' + token;

            if (endpoint.parameters?.body && ['post','put','patch'].includes(selectedMethod)) {
                options.body = document.getElementById('request-body')?.value;
            }

            const start = performance.now();
            try {
                const res = await fetch(baseUrl + url, options);
                const text = await res.text();
                const responseTime = Math.round(performance.now() - start);
                displayResponse(res.status, text, responseTime);
                if (res.ok) successCount++;
            } catch (e) {
                displayNetworkError(e.message);
            }

            requestCount++;
            updateStats();

            btn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Send Request';
            btn.disabled = false;
        }

        function displayResponse(status, text, responseTime) {
            const responseDiv = document.getElementById('response');
            const responseBody = document.getElementById('response-body');
            const responseStatus = document.getElementById('response-status');
            const responseTimeVal = document.getElementById('response-time-val');

            responseDiv.classList.remove('hidden');
            responseStatus.textContent = status;
            responseStatus.className = 'status-badge status-' + Math.floor(status / 100) + 'xx';
            responseTimeVal.textContent = responseTime + 'ms';

            let data;
            try { data = JSON.parse(text); } catch { data = { raw: text }; }

            if (!data || (typeof data === 'object' && (data.exception || (status >= 400 && data.message)))) {
                displayErrorBody(status, data);
            } else {
                displaySuccessBody(data);
            }
        }

        function displaySuccessBody(data) {
            document.getElementById('response-body').innerHTML = `
                <div class="p-4 lg:p-5">
                    <div class="flex items-center gap-3 p-4 rounded-xl success-card">
                        <div class="success-icon-wrapper flex-shrink-0">
                            <i class="fas fa-check text-green-400 text-lg"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-green-400">Success!</h4>
                            <p class="text-sm text-slate-400">Request completed successfully</p>
                        </div>
                    </div>
                    <div class="mt-4 p-4 bg-slate-950 rounded-lg overflow-x-auto">
                        <pre class="text-sm text-green-400"><code>${JSON.stringify(data, null, 2)}</code></pre>
                    </div>
                </div>
            `;
        }

        function displayErrorBody(status, data) {
            const exception = data?.exception || 'Error';
            const message = data?.message || 'An error occurred';
            const file = data?.file || '';
            const line = data?.line || '';
            const errors = data?.errors || null;

            let errorDetails = '';
            if (errors && typeof errors === 'object') {
                errorDetails = Object.entries(errors).map(([field, msgs]) => `
                    <div class="flex items-start gap-2 text-sm">
                        <span class="text-red-400 font-mono text-xs">${field}:</span>
                        <div class="flex-1 flex flex-wrap gap-1">
                            ${(Array.isArray(msgs) ? msgs : [msgs]).map(msg => `<span class="inline-block px-2 py-1 rounded bg-red-400/10 text-red-400 text-xs">${msg}</span>`).join('')}
                        </div>
                    </div>
                `).join('');
            }

            document.getElementById('response-body').innerHTML = `
                <div class="p-4 lg:p-5 space-y-4">
                    <div class="error-card p-5">
                        <div class="flex items-start gap-4">
                            <div class="error-icon-wrapper flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-bold text-red-400 mb-2">${getStatusTitle(status)}</h4>
                                <p class="text-slate-300 text-sm leading-relaxed break-words">${message}</p>
                                ${exception !== 'Error' ? `<span class="inline-flex items-center px-2 py-1 rounded text-sm bg-red-400/10 text-red-400 font-mono mt-2 break-all">${exception}</span>` : ''}
                            </div>
                        </div>
                        ${errorDetails ? `<div class="mt-4 pt-4 border-t border-red-400/20"><h5 class="text-sm font-semibold text-slate-300 mb-3"><i class="fas fa-exclamation-circle text-yellow-400 mr-2"></i>Validation Errors</h5><div class="space-y-2">${errorDetails}</div></div>` : ''}
                        ${file ? `<div class="mt-4 pt-4 border-t border-red-400/20"><div class="flex items-center gap-2 text-xs text-slate-400"><i class="fas fa-file-code"></i><span class="code-inline break-all">${file}:${line}</span></div></div>` : ''}
                    </div>
                    <div>
                        <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="w-full flex items-center justify-between p-3 rounded-lg bg-slate-800 border border-slate-700 text-sm text-slate-300 hover:bg-slate-750 transition-colors">
                            <span class="flex items-center gap-2"><i class="fas fa-code"></i><span>View Full Response</span></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="hidden mt-2">
                            <pre class="text-xs text-slate-400 p-4 bg-slate-950 rounded-lg overflow-x-auto border border-slate-800"><code>${JSON.stringify(data, null, 2)}</code></pre>
                        </div>
                    </div>
                </div>
            `;
        }

        function displayNetworkError(message) {
            const responseDiv = document.getElementById('response');
            responseDiv.classList.remove('hidden');
            document.getElementById('response-status').textContent = 'ERROR';
            document.getElementById('response-status').className = 'status-badge status-5xx';
            document.getElementById('response-time-val').textContent = '-';
            document.getElementById('response-body').innerHTML = `
                <div class="p-5">
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-900 border border-slate-700">
                        <div class="w-12 h-12 rounded-full bg-red-400/10 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-wifi text-red-400 text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-red-400 mb-2">Network Error</h4>
                            <p class="text-slate-300 text-sm">${message}</p>
                            <p class="text-xs text-slate-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Check your connection and ensure the API server is running.</p>
                        </div>
                    </div>
                </div>
            `;
        }

        function getStatusTitle(status) {
            return { 400: 'Bad Request', 401: 'Unauthorized', 403: 'Forbidden', 404: 'Not Found', 405: 'Method Not Allowed', 422: 'Validation Error', 429: 'Too Many Requests', 500: 'Internal Server Error', 503: 'Service Unavailable' }[status] || 'Error ' + status;
        }

        function copyResponse() {
            const el = document.getElementById('response-body');
            const code = el.querySelector('code') || el;
            copyWithFeedback('copy-response-btn', code.textContent);
        }

        // Sidebar mobile
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }
        function closeMobileSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }

        // Auth
        function openAuthModal() { document.getElementById('auth-modal').classList.remove('hidden'); document.getElementById('auth-token').value = token; }
        function closeAuthModal() { document.getElementById('auth-modal').classList.add('hidden'); }
        function saveToken() {
            token = document.getElementById('auth-token').value;
            localStorage.setItem('api-docs-token', token);
            document.getElementById('auth-status').textContent = token ? 'Authenticated' : 'Set Token';
            closeAuthModal();
            showToast('Token saved!');
        }
        function clearToken() {
            token = '';
            localStorage.removeItem('api-docs-token');
            document.getElementById('auth-status').textContent = 'Set Token';
            closeAuthModal();
            showToast('Token cleared!');
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast fixed bottom-6 right-6 px-4 py-3 bg-green-500 text-white rounded-lg shadow-lg text-sm z-50 flex items-center gap-2';
            toast.innerHTML = '<i class="fas fa-check-circle"></i><span>' + message + '</span>';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        // Search + Keyboard shortcuts
        document.getElementById('search').addEventListener('input', (e) => renderEndpoints(e.target.value));

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search').focus();
            }
            if (e.key === 'Escape') {
                closeAuthModal();
                closeMobileSidebar();
            }
        });

        init();
    </script>
</body>
</html>
