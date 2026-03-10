{{-- Main Content Area --}}
<main class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0 min-w-0">
    {{-- Desktop Header --}}
    <header class="hidden lg:flex border-b border-white/10 px-6 py-4 items-center justify-between bg-slate-900/50 backdrop-blur-md flex-shrink-0">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Interactive Console</p>
            <h2 class="font-semibold text-xl truncate text-white tracking-tight" id="schema-title">API Documentation</h2>
        </div>
        <div class="flex items-center gap-3">
            <div id="header-server-badge" class="hidden items-center gap-2 rounded-full border border-sky-400/15 bg-sky-400/10 px-3 py-1.5 text-xs text-sky-200">
                <i class="fas fa-globe text-sky-300"></i>
                <span id="header-server-name" class="max-w-44 truncate">Server</span>
            </div>
            <button onclick="showOverviewPanel()" class="px-4 py-2 bg-slate-800/90 rounded-xl text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors border border-white/10 text-slate-200">
                <i class="fas fa-chart-line text-cyan-400"></i><span>Overview</span>
            </button>
            <button onclick="openAuthModal()" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl text-sm hover:from-cyan-400 hover:to-blue-400 flex items-center gap-2 transition-colors text-white shadow-lg shadow-cyan-500/20 flex-shrink-0">
                <i class="fas fa-key"></i><span id="auth-status">Set Auth</span>
            </button>
        </div>
    </header>

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-16" id="content-area">
        {{-- Welcome Screen --}}
        <div class="max-w-6xl mx-auto w-full space-y-6" id="overview-root">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/60 p-6 lg:p-8 shadow-2xl shadow-sky-950/20">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.18),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.18),transparent_32%)]"></div>
                <div class="relative grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                    <div class="space-y-5">
                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-cyan-200">
                            <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                            API Workspace
                        </div>
                        <div class="space-y-3">
                            <h3 class="max-w-3xl text-3xl font-semibold leading-tight tracking-tight text-white lg:text-5xl">
                                Inspect, test, and export your Laravel API from one polished console.
                            </h3>
                            <p class="max-w-2xl text-sm leading-7 text-slate-300 lg:text-base">
                                Explore routes, inspect schemas, replay requests, and verify integrations across environments without leaving the browser.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button onclick="openAuthModal()" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-white/10 transition hover:bg-slate-100">
                                <i class="fas fa-key text-cyan-500"></i>
                                Configure Auth
                            </button>
                            <button onclick="showHistoryPanel()" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-slate-800/70 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/30 hover:text-white">
                                <i class="fas fa-history text-amber-400"></i>
                                Review History
                            </button>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Primary Server</p>
                            <p id="overview-server" class="mt-2 truncate text-sm font-semibold text-white">Preparing…</p>
                            <p id="overview-generated-at" class="mt-1 text-xs text-slate-400">Waiting for schema</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Capabilities</p>
                            <div id="overview-features" class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full border border-white/10 bg-slate-800/80 px-2.5 py-1 text-xs text-slate-300">Loading</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-lg shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Endpoints</span>
                        <i class="fas fa-diagram-project text-cyan-400"></i>
                    </div>
                    <p id="hero-endpoint-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Routes discovered from your application.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-lg shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Servers</span>
                        <i class="fas fa-globe text-sky-400"></i>
                    </div>
                    <p id="hero-server-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Configured environments ready for testing.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-lg shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Success Rate</span>
                        <i class="fas fa-wave-square text-emerald-400"></i>
                    </div>
                    <p id="hero-success-rate" class="mt-4 text-3xl font-semibold tracking-tight text-white">100%</p>
                    <p class="mt-2 text-sm text-slate-400">Runtime request success from this console.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-lg shadow-black/10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Requests Sent</span>
                        <i class="fas fa-paper-plane text-indigo-400"></i>
                    </div>
                    <p id="hero-request-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Interactive requests executed in-session.</p>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 lg:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">What You Can Do</p>
                            <h4 class="mt-1 text-lg font-semibold text-white">Built for exploration and verification</h4>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Docs + Console</span>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Interactive Request Runner</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Test endpoints with live headers, request bodies, query parameters, and response inspection.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Export and Client Tooling</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Generate OpenAPI, Postman, Insomnia, TypeScript, and GraphQL artifacts from the same schema.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Operational Visibility</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Surface health metrics, changelogs, and security hints alongside endpoint documentation.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Replayable Workflow</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Use request history and response chaining to validate multi-step flows faster.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 lg:p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Common Methods</p>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-get">GET</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Read resources and list collections</p>
                                <p class="text-xs text-slate-400">Great for pagination, search, and filtering flows.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-post">POST</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Create new records and trigger actions</p>
                                <p class="text-xs text-slate-400">Inspect payload examples before sending live requests.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-put">PUT</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Update and synchronize entities</p>
                                <p class="text-xs text-slate-400">Pair with Store/Update request rules for safer testing.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-delete">DELETE</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Clean up or archive resources</p>
                                <p class="text-xs text-slate-400">Quickly verify destructive actions and policy behavior.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="bg-slate-900/80 backdrop-blur-md border-t border-white/10 py-3 px-4 lg:px-6 shrink-0">
        <div class="flex flex-col gap-2 text-xs text-slate-500 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center justify-center gap-2 lg:justify-start">
                <span>Built with API Magic</span>
                <span class="h-1 w-1 rounded-full bg-slate-600"></span>
                <span id="footer-base-url" class="truncate max-w-[16rem] lg:max-w-[30rem]">Waiting for base URL…</span>
            </div>
            <div class="flex items-center justify-center gap-2">
                <span>Created by</span>
                <a href="https://github.com/Arseno25" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 font-medium transition-colors flex items-center gap-1">
                    <i class="fab fa-github"></i> Arseno25
                </a>
            </div>
        </div>
    </footer>
</main>
