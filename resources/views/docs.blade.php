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
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
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
        .sidebar-item.active { background: rgba(99, 102, 241, 0.15); border-color: #6366f1; }

        /* Error Card Styles */
        .error-card {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 1rem;
        }

        .error-icon-wrapper {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            background: rgba(239, 68, 68, 0.15);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .code-inline {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }

        /* Toggle Button */
        .toggle-btn {
            transition: all 0.2s ease;
        }
        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Success Response */
        .success-card {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(22, 163, 74, 0.1) 100%);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 1rem;
        }

        .success-icon-wrapper {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            background: rgba(34, 197, 94, 0.15);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
        }
    </style>
</head>
<body class="bg-slate-950 text-white">
    <div id="app">
        <!-- Loading -->
        <div id="loading" class="fixed inset-0 flex items-center justify-center bg-slate-950 z-50">
            <div class="text-center">
                <div class="w-12 h-12 border-3 border-slate-700 border-t-indigo-500 rounded-full animate-spin mx-auto mb-4"></div>
                <p class="text-slate-400">Loading...</p>
            </div>
        </div>

        <!-- Main Content -->
        <div id="main-content" class="hidden h-screen flex">
            <!-- Sidebar -->
            <aside class="w-72 bg-slate-900 border-r border-slate-800 flex flex-col">
                <div class="p-4 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-bolt text-white"></i>
                        </div>
                        <div>
                            <h1 class="font-bold text-lg">API Magic</h1>
                            <p class="text-xs text-slate-500">v<span id="version">1.0.0</span></p>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-b border-slate-800">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                        <input type="text" id="search" placeholder="Search endpoints..." class="w-full pl-9 pr-4 py-2.5 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <nav id="endpoints-list" class="flex-1 overflow-y-auto p-4 space-y-1"></nav>
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
                </div>
            </aside>

            <!-- Main -->
            <main class="flex-1 flex flex-col overflow-hidden bg-slate-900">
                <header class="border-b border-slate-800 px-6 py-4 flex items-center justify-between">
                    <h2 class="font-semibold text-lg" id="schema-title">API Documentation</h2>
                    <button onclick="openAuthModal()" class="px-4 py-2 bg-slate-800 rounded-lg text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors">
                        <i class="fas fa-key"></i><span id="auth-status">Set Token</span>
                    </button>
                </header>
                <div class="flex-1 overflow-y-auto p-6" id="content-area">
                    <div class="text-center py-20">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center">
                            <i class="fas fa-rocket text-3xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2">Welcome to API Magic</h3>
                        <p class="text-slate-400 mb-6">Your advanced API documentation and testing platform</p>
                        <div class="inline-flex items-center gap-2 text-sm text-slate-500">
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
        let requestCount = parseInt(localStorage.getItem('api-docs-requestCount') || '0');
        let successCount = parseInt(localStorage.getItem('api-docs-successCount') || '0');

        // Initialize
        async function init() {
            try {
                const res = await fetch('/api/docs/json');
                schema = await res.json();
                document.getElementById('version').textContent = schema.version;
                document.getElementById('schema-title').textContent = schema.title;
                document.getElementById('endpoint-count').textContent = countEndpoints();
                updateStats();
                renderEndpoints();
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('main-content').classList.remove('hidden');
            } catch (e) {
                console.error('Failed to load:', e);
                document.getElementById('loading').innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                        <p class="text-red-400 font-medium">Failed to load documentation</p>
                        <p class="text-slate-500 text-sm mt-2">${e.message}</p>
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

        function renderEndpoints(filter = '') {
            const container = document.getElementById('endpoints-list');
            container.innerHTML = '';
            const query = filter.toLowerCase();

            for (const [path, methods] of Object.entries(schema.endpoints)) {
                for (const [method, endpoint] of Object.entries(methods)) {
                    const summary = endpoint.summary || path;
                    if (path.toLowerCase().includes(query) || summary.toLowerCase().includes(query)) {
                        const div = document.createElement('div');
                        div.className = 'mb-2';
                        div.innerHTML = `
                            <button onclick="selectEndpoint('${path}', '${method}')" class="w-full text-left px-3 py-2.5 rounded-lg border border-transparent hover:bg-slate-800 flex items-center gap-2 text-sm sidebar-item transition-all">
                                <span class="method-badge method-${method}">${method.toUpperCase()}</span>
                                <span class="truncate text-slate-300">${summary}</span>
                                <i class="fas fa-lock text-slate-600 text-xs opacity-0 group-hover:opacity-100"></i>
                            </button>
                        `;
                        container.appendChild(div);
                    }
                }
            }
        }

        function selectEndpoint(path, method) {
            selectedPath = path;
            selectedMethod = method;
            const endpoint = schema.endpoints[path][method];
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
                                        <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Description</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                    ${endpoint.parameters.path.map(p => `
                                        <tr class="bg-slate-900/50">
                                            <td class="py-2.5 px-4"><code class="text-blue-400 font-mono text-sm">${p.name}</code></td>
                                            <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-400/10 text-blue-400">${p.type}</span></td>
                                            <td class="py-2.5 px-4 text-slate-400">${p.description || '-'}</td>
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
                        <div class="rounded-lg border border-slate-700 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-800">
                                    <tr>
                                        <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Field</th>
                                        <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Type</th>
                                        <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Required</th>
                                        <th class="py-2.5 px-4 text-left text-xs font-semibold text-slate-400 uppercase">Rules</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-700">
                                    ${Object.entries(endpoint.parameters.body).map(([name, field]) => `
                                        <tr class="bg-slate-900/50">
                                            <td class="py-2.5 px-4"><code class="text-green-400 font-mono text-sm">${name}</code></td>
                                            <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs bg-green-400/10 text-green-400">${field.type}</span></td>
                                            <td class="py-2.5 px-4"><span class="inline-flex items-center px-2 py-1 rounded text-xs ${field.required ? 'bg-red-400/10 text-red-400' : 'bg-slate-600/30 text-slate-400'}">${field.required ? 'Required' : 'Optional'}</span></td>
                                            <td class="py-2.5 px-4 font-mono text-xs text-slate-400 break-all">${field.rules || '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            const exampleSection = (endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post','put','patch'].includes(method)) ? `
                <!-- Example Request Body -->
                <div class="p-5 bg-slate-900 rounded-xl border border-slate-700 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-code text-indigo-400"></i>
                            <span class="text-sm font-semibold text-slate-200">Example Value</span>
                        </div>
                        <button onclick="copyExample()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre class="text-sm text-green-400 p-4 bg-slate-950 rounded-lg overflow-x-auto"><code id="example-body">${generateSampleBody(endpoint)}</code></pre>
                </div>
            ` : '';

            const bodyInputSection = (endpoint.parameters?.body && Object.keys(endpoint.parameters.body).length > 0 && ['post','put','patch'].includes(method)) ? `
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

            container.innerHTML = `
                <div class="space-y-6">
                    <!-- Endpoint Header -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span class="method-badge text-base px-4 py-2 method-${method}">${method.toUpperCase()}</span>
                            <code class="text-xl text-slate-200">${path}</code>
                            ${endpoint.security && endpoint.security.length ? `<span class="px-3 py-1 rounded-full text-xs bg-red-400/10 text-red-400 flex items-center gap-1.5"><i class="fas fa-lock text-xs"></i> Auth Required</span>` : ''}
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">${endpoint.summary || ''}</h3>
                        <p class="text-slate-400">${endpoint.description || ''}</p>
                    </div>
                    ${paramsHtml || bodyHtml ? `<div class="bg-slate-800 rounded-xl p-6 border border-slate-700">${paramsHtml}${bodyHtml}</div>` : ''}
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
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
                                    <div class="grid grid-cols-2 gap-3">
                                        ${endpoint.parameters.path.map(p => `
                                            <div>
                                                <label class="block text-xs text-slate-500 mb-1.5">${p.name}</label>
                                                <input type="text" id="param-${p.name}" placeholder="${p.type === 'integer' ? '1' : 'value'}" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}
                            ${exampleSection}
                            ${bodyInputSection}
                            <button onclick="sendRequest()" class="px-6 py-3 bg-indigo-500 text-white rounded-lg font-medium hover:bg-indigo-600 flex items-center gap-2 transition-colors">
                                <i class="fas fa-paper-plane"></i> Send Request
                            </button>
                            <div id="response" class="hidden rounded-xl overflow-hidden border border-slate-700">
                                <div class="px-5 py-4 bg-slate-900 flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-200">Response</span>
                                    <div class="flex items-center gap-3">
                                        <span id="response-status" class="status-badge"></span>
                                        <span id="response-time" class="text-xs text-slate-400 flex items-center gap-1">
                                            <i class="fas fa-clock"></i>
                                            <span id="response-time-val"></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center px-5 py-3 bg-slate-800 border-b border-slate-700">
                                    <span class="text-xs text-slate-400">Response Body</span>
                                    <button onclick="copyResponse()" class="text-xs px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 flex items-center gap-1.5 transition-colors">
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

        function generateSampleBody(endpoint) {
            const sample = {};
            for (const [name, field] of Object.entries(endpoint.parameters.body || {})) {
                sample[name] = getExampleValue(name, field.type, field.rules);
            }
            return JSON.stringify(sample, null, 2);
        }

        function getExampleValue(name, type, rules) {
            const lowerName = name.toLowerCase();
            if (lowerName.includes('email') || type === 'email') return 'john.doe@example.com';
            if (lowerName.includes('password') || lowerName.includes('pass')) return 'Password123!';
            if (lowerName.includes('phone') || lowerName.includes('mobile')) return '+6281234567890';
            if (lowerName.includes('url') || lowerName.includes('link') || lowerName.includes('website')) return 'https://example.com';
            if (lowerName.includes('image') || lowerName.includes('photo') || lowerName.includes('avatar') || lowerName.includes('thumbnail')) return 'https://example.com/images/product.jpg';
            if (lowerName.includes('date') || lowerName.includes('at') || lowerName.includes('time')) {
                if (lowerName.includes('published_at') || lowerName.includes('ended_at') || lowerName.includes('expired_at')) return '2024-12-31T23:59:59.000000Z';
                return new Date().toISOString().slice(0, 19) + '.000000Z';
            }
            if (lowerName.includes('status')) return 'active';
            if (lowerName.includes('title') || lowerName.includes('subject')) return 'Example Title';
            if (lowerName.includes('name')) return 'John Doe';
            if (lowerName.includes('description') || lowerName.includes('content') || lowerName.includes('body')) return 'This is an example description that provides details about the content.';
            if (lowerName.includes('slug')) return 'example-slug-here';
            if (lowerName.includes('price') || lowerName.includes('cost') || lowerName.includes('amount')) return 99.99;
            if (lowerName.includes('quantity') || lowerName.includes('stock') || lowerName.includes('qty')) return 10;
            if (lowerName.includes('category') || lowerName.includes('category_id')) return 1;
            if (type === 'boolean') return true;
            if (type === 'integer' || type === 'int' || type === 'bigint') return 1;
            if (type === 'number' || type === 'float' || type === 'double' || type === 'decimal') return 1.5;
            if (type === 'array') return ['item1', 'item2', 'item3'];
            return 'example_value';
        }

        function copyExample() {
            const example = document.getElementById('example-body').textContent;
            navigator.clipboard.writeText(example);
            showToast('Example copied!');
        }

        function useExample() {
            const example = document.getElementById('example-body').textContent;
            document.getElementById('request-body').value = example;
            showToast('Example loaded!');
        }

        async function sendRequest() {
            const btn = event.target;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            btn.disabled = true;

            const baseUrl = document.getElementById('base-url').value;
            let url = selectedPath;

            // Replace path params
            const endpoint = schema.endpoints[selectedPath][selectedMethod];
            if (endpoint.parameters?.path) {
                endpoint.parameters.path.forEach(p => {
                    const val = document.getElementById('param-' + p.name)?.value || '1';
                    url = url.replace('{' + p.name + '}', encodeURIComponent(val));
                });
            }

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

                // Parse and display response
                displayResponse(res.status, text, responseTime);

                if (res.ok) successCount++;
            } catch (e) {
                displayNetworkError(e.message);
            }

            requestCount++;
            localStorage.setItem('api-docs-requestCount', requestCount);
            localStorage.setItem('api-docs-successCount', successCount);
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
            responseTimeVal.textContent = responseTime;

            let data;
            try {
                data = JSON.parse(text);
            } catch {
                data = { raw: text };
            }

            // Check if error response
            if (!data || (typeof data === 'object' && (data.exception || data.message))) {
                displayErrorBody(status, data);
            } else {
                // Success response
                displaySuccessBody(data);
            }
        }

        function displaySuccessBody(data) {
            const responseBody = document.getElementById('response-body');
            responseBody.innerHTML = `
                <div class="p-5">
                    <div class="flex items-center gap-3 p-4 rounded-xl success-card">
                        <div class="success-icon-wrapper">
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
            const responseBody = document.getElementById('response-body');

            const exception = data.exception || 'Error';
            const message = data.message || 'An error occurred';
            const file = data.file || '';
            const line = data.line || '';
            const errors = data.errors || null;

            let errorDetails = '';
            if (errors && typeof errors === 'object') {
                errorDetails = Object.entries(errors).map(([field, msgs]) => `
                    <div class="flex items-start gap-2 text-sm">
                        <span class="text-red-400 font-mono text-xs">${field}:</span>
                        <div class="flex-1">
                            ${msgs.map(msg => `<span class="inline-block px-2 py-1 rounded bg-red-400/10 text-red-400 text-xs mr-1">${msg}</span>`).join('')}
                        </div>
                    </div>
                `).join('');
            }

            responseBody.innerHTML = `
                <div class="p-5 space-y-4">
                    <!-- Main Error Card -->
                    <div class="error-card p-5">
                        <div class="flex items-start gap-4">
                            <div class="error-icon-wrapper flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-red-400 mb-2">${getStatusTitle(status)}</h4>
                                <p class="text-slate-300 text-sm leading-relaxed">${message}</p>
                                ${exception !== 'Error' ? `<span class="inline-flex items-center px-2 py-1 rounded text-sm bg-red-400/10 text-red-400 font-mono mt-2">${exception}</span>` : ''}
                            </div>
                        </div>

                        ${errorDetails ? `
                            <!-- Validation Errors -->
                            <div class="mt-4 pt-4 border-t border-red-400/20">
                                <h5 class="text-sm font-semibold text-slate-300 mb-3">
                                    <i class="fas fa-exclamation-circle text-yellow-400 mr-2"></i>Validation Errors
                                </h5>
                                <div class="space-y-2">${errorDetails}</div>
                            </div>
                        ` : ''}

                        ${file ? `
                            <!-- File Location -->
                            <div class="mt-4 pt-4 border-t border-red-400/20">
                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                    <i class="fas fa-file-code"></i>
                                    <span class="code-inline">${file}:${line}</span>
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Toggle Full Response -->
                    <div>
                        <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="toggle-btn w-full flex items-center justify-between p-3 rounded-lg bg-slate-800 border border-slate-700 text-sm text-slate-300">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-code"></i>
                                <span>View Full Response</span>
                            </span>
                            <i class="fas fa-chevron-down transition-transform" id="chevron-icon"></i>
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
            const responseBody = document.getElementById('response-body');
            const responseStatus = document.getElementById('response-status');
            const responseTimeVal = document.getElementById('response-time-val');

            responseDiv.classList.remove('hidden');
            responseStatus.textContent = 'ERROR';
            responseStatus.className = 'status-badge status-5xx';
            responseTimeVal.textContent = '-';

            responseBody.innerHTML = `
                <div class="p-5">
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-slate-900 border border-slate-700">
                        <div class="w-12 h-12 rounded-full bg-red-400/10 flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-wifi-slash text-red-400 text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-red-400 mb-2">Network Error</h4>
                            <p class="text-slate-300 text-sm">${message}</p>
                            <p class="text-xs text-slate-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Check your connection and ensure the API server is running.
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }

        function getStatusTitle(status) {
            const titles = {
                400: 'Bad Request',
                401: 'Unauthorized',
                403: 'Forbidden',
                404: 'Not Found',
                405: 'Method Not Allowed',
                422: 'Validation Error',
                429: 'Too Many Requests',
                500: 'Internal Server Error',
                503: 'Service Unavailable',
                504: 'Gateway Timeout'
            };
            return titles[status] || 'Error ' + status;
        }

        function copyResponse() {
            const responseBody = document.getElementById('response-body');
            const codeBlock = responseBody.querySelector('code') || responseBody;
            const text = codeBlock.textContent || responseBody.textContent;
            navigator.clipboard.writeText(text);
            showToast('Response copied!');
        }

        function openAuthModal() {
            document.getElementById('auth-modal').classList.remove('hidden');
            document.getElementById('auth-token').value = token;
        }

        function closeAuthModal() {
            document.getElementById('auth-modal').classList.add('hidden');
        }

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
            toast.className = 'fixed bottom-6 right-6 px-4 py-3 bg-green-500 text-white rounded-lg shadow-lg text-sm z-50 flex items-center gap-2';
            toast.innerHTML = '<i class="fas fa-check-circle"></i><span>' + message + '</span>';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2500);
        }

        // Search
        document.getElementById('search').addEventListener('input', (e) => {
            renderEndpoints(e.target.value);
        });

        // Start
        init();
    </script>
</body>
</html>
