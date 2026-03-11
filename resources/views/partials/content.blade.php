{{-- Main Content Area --}}
<main class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0 min-w-0">
    {{-- Desktop Header --}}
    <header class="hidden lg:flex border-b border-white/10 px-6 py-4 items-center justify-between bg-slate-900/50 backdrop-blur-md flex-shrink-0">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Interactive API Docs</p>
            <h2 class="font-semibold text-xl truncate text-white tracking-tight" id="schema-title">API Documentation</h2>
            <div class="mt-2 flex items-center gap-3 text-xs text-slate-500">
                <span id="sidebar-generated-at">Waiting…</span>
                <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                <span id="header-server-badge" class="hidden items-center gap-2 text-slate-400">
                    <i class="fas fa-globe text-sky-300"></i>
                    <span id="header-server-name" class="max-w-44 truncate">Server</span>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden xl:flex items-center gap-2">
                <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300">
                    <span class="text-slate-500">Endpoints</span>
                    <span id="endpoint-count" class="ml-2 font-semibold text-white">0</span>
                </div>
                <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300">
                    <span class="text-slate-500">Success</span>
                    <span id="success-rate" class="ml-2 font-semibold text-white">100%</span>
                </div>
                <div class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-slate-300">
                    <span class="text-slate-500">Requests</span>
                    <span id="request-count" class="ml-2 font-semibold text-white">0</span>
                </div>
            </div>
            <button onclick="showHistoryPanel()" class="px-4 py-2 bg-slate-800/70 rounded-xl text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors border border-white/10 text-slate-200">
                <i class="fas fa-history text-cyan-400"></i>
                <span>History</span>
                <span id="history-count" class="rounded bg-cyan-500/20 px-1.5 py-0.5 text-[10px] leading-tight text-cyan-300">0</span>
            </button>
            <details class="group relative">
                <summary class="list-none px-4 py-2 bg-slate-800/70 rounded-xl text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors border border-white/10 text-slate-200 cursor-pointer">
                    <i class="fas fa-download text-sky-400"></i>
                    <span>Export</span>
                    <i class="fas fa-chevron-down text-[10px] text-slate-500 transition group-open:rotate-180"></i>
                </summary>
                <div class="absolute right-0 mt-2 w-52 rounded-2xl border border-white/10 bg-slate-900/95 p-2 shadow-2xl shadow-black/30">
                    <a href="{{ route('api.docs.export') }}" target="_blank" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <i class="fas fa-file-code text-sky-400"></i>
                        <span>OpenAPI</span>
                    </a>
                    <a href="{{ route('api.docs.export', ['format' => 'postman']) }}" target="_blank" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <i class="fas fa-paper-plane text-orange-400"></i>
                        <span>Postman</span>
                    </a>
                    <a href="{{ route('api.docs.export', ['format' => 'insomnia']) }}" target="_blank" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white">
                        <i class="fas fa-moon text-violet-400"></i>
                        <span>Insomnia</span>
                    </a>
                </div>
            </details>
            <button onclick="showOverviewPanel()" class="px-4 py-2 bg-slate-800/70 rounded-xl text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors border border-white/10 text-slate-200">
                <i class="fas fa-chart-line text-cyan-400"></i><span>Overview</span>
            </button>
            <button onclick="openAuthModal()" class="px-4 py-2 bg-slate-100 rounded-xl text-sm hover:bg-white flex items-center gap-2 transition-colors text-slate-950 flex-shrink-0">
                <i class="fas fa-key"></i><span id="auth-status">Set Auth</span>
            </button>
        </div>
    </header>

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-16" id="content-area">
        {{-- Welcome Screen --}}
        <div class="max-w-6xl mx-auto w-full space-y-6" id="overview-root">
            <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-slate-900/55 p-6 lg:p-8">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.10),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.08),transparent_28%)]"></div>
                <div class="relative grid gap-8 lg:grid-cols-[1.7fr_0.9fr]">
                    <div class="space-y-5">
                        <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-cyan-200">
                            <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                            API Workspace
                        </div>
                        <div class="space-y-3">
                            <h3 class="max-w-3xl text-3xl font-semibold leading-tight tracking-tight text-white lg:text-[3.25rem]">
                                Clean, focused API documentation for exploring and testing your Laravel routes.
                            </h3>
                            <p class="max-w-2xl text-sm leading-7 text-slate-300 lg:text-base">
                                Review available routes, inspect request and response contracts, and test the API from one focused workspace. New users can follow a simple flow, while experienced users can jump directly into the routes they need.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button onclick="openFirstEndpoint()" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                                <i class="fas fa-play-circle"></i>
                                Start With An Endpoint
                            </button>
                            <button onclick="focusSearchBox()" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-slate-800/60 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-sky-400/30 hover:text-white">
                                <i class="fas fa-search text-sky-400"></i>
                                Search Routes
                            </button>
                            <button onclick="openAuthModal()" class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-slate-800/60 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:border-cyan-400/30 hover:text-white">
                                <i class="fas fa-key text-cyan-400"></i>
                                Configure Auth
                            </button>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 lg:p-6">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Current Workspace</p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-xs text-slate-500">Server</p>
                                <p id="overview-server" class="mt-1 truncate text-sm font-semibold text-white">Preparing…</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Generated</p>
                                <p id="overview-generated-at" class="mt-1 text-sm text-slate-300">Waiting for schema</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Enabled Features</p>
                                <div id="overview-features" class="mt-2 flex flex-wrap gap-2">
                                    <span class="rounded-full border border-white/10 bg-slate-800/80 px-2.5 py-1 text-xs text-slate-300">Loading</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Endpoints</span>
                        <i class="fas fa-diagram-project text-cyan-400"></i>
                    </div>
                    <p id="hero-endpoint-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Routes discovered from your application.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Servers</span>
                        <i class="fas fa-globe text-sky-400"></i>
                    </div>
                    <p id="hero-server-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Configured environments ready for testing.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Success Rate</span>
                        <i class="fas fa-wave-square text-emerald-400"></i>
                    </div>
                    <p id="hero-success-rate" class="mt-4 text-3xl font-semibold tracking-tight text-white">100%</p>
                    <p class="mt-2 text-sm text-slate-400">Runtime request success from this console.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Requests Sent</span>
                        <i class="fas fa-paper-plane text-indigo-400"></i>
                    </div>
                    <p id="hero-request-count" class="mt-4 text-3xl font-semibold tracking-tight text-white">0</p>
                    <p class="mt-2 text-sm text-slate-400">Interactive requests executed in-session.</p>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5 lg:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Start Here</p>
                            <h4 class="mt-1 text-lg font-semibold text-white">A simple path through the documentation</h4>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Guided</span>
                    </div>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-start gap-3">
                                <span class="overview-step-index">1</span>
                                <div>
                                    <p class="text-sm font-semibold text-white">Choose the right environment</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Use the server switcher to point requests to the correct API before you start exploring endpoints.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-start gap-3">
                                <span class="overview-step-index">2</span>
                                <div>
                                    <p class="text-sm font-semibold text-white">Open an endpoint</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Browse the sidebar or use search to jump directly to a route, then read the request shape and expected responses.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex items-start gap-3">
                                <span class="overview-step-index">3</span>
                                <div>
                                    <p class="text-sm font-semibold text-white">Run the request and keep the result</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-400">Test the endpoint, inspect the live response, then use snippets or exports when you want to move the same contract into another tool.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5 lg:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Power Tools</p>
                            <h4 class="mt-1 text-lg font-semibold text-white">Core capabilities for daily API work</h4>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Advanced</span>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Interactive Request Runner</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Send requests with live headers, query parameters, and request bodies directly from the docs.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Export and Client Tooling</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Generate OpenAPI and client-facing artifacts from the same schema used in the interface.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Operational Visibility</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Keep health metrics, changelogs, and security context close to the endpoints they affect.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Replayable Workflow</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Reuse request history and chained responses when you need to verify multi-step flows.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5 lg:p-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Request Patterns</p>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-get">GET</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Inspect data safely</p>
                                <p class="text-xs text-slate-400">Useful for collections, detail views, and understanding the shape of the API.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-post">POST</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Create records or trigger actions</p>
                                <p class="text-xs text-slate-400">Review the payload contract before sending write operations.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-put">PUT</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Update existing resources</p>
                                <p class="text-xs text-slate-400">Compare request rules and response payloads before changing live data.</p>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 flex items-center gap-3">
                            <span class="method-badge method-delete">DELETE</span>
                            <div>
                                <p class="text-sm font-semibold text-white">Remove or archive resources</p>
                                <p class="text-xs text-slate-400">Check permissions and confirm destructive behavior before running the request.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/45 p-5 lg:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Working Notes</p>
                            <h4 class="mt-1 text-lg font-semibold text-white">A few practical reminders while you explore</h4>
                        </div>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300">Shortcuts</span>
                    </div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Auth is optional until the route needs it</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Open the auth dialog only when a protected endpoint requires a token, session, or another credential.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Use <span class="code-inline">Ctrl+K</span> when the route list gets long</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Jump straight to a path, tag, or version without manually scanning the sidebar.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">History helps when you repeat the same checks</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Replay a successful request instead of rebuilding the same payload every time.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-semibold text-white">Exports are best after you validate the live response</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Once the request behaves as expected, move the same contract into Postman, Insomnia, or generated clients.</p>
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
