{{-- Sidebar Navigation --}}
<aside id="sidebar" class="fixed lg:relative w-80 max-w-[88vw] bg-slate-900/70 backdrop-blur-2xl border-r border-white/10 flex flex-col h-full z-50 -translate-x-full lg:translate-x-0 sidebar-panel shadow-2xl shadow-sky-950/30">
    {{-- Brand --}}
    <div class="p-5 border-b border-white/10 flex-shrink-0">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-cyan-500/20 ring-1 ring-white/10">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <h1 class="font-bold text-lg tracking-tight truncate">API Magic</h1>
                        <p class="text-xs text-slate-500">Package docs workspace</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Live
                    </span>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-slate-400">
                    <span class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1">
                        <i class="fas fa-cube text-cyan-400"></i>
                        v<span id="version">1.0.0</span>
                    </span>
                    <span id="server-current" class="inline-flex min-w-0 items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-slate-300">
                        <i class="fas fa-globe text-sky-400"></i>
                        <span class="truncate">Auto</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Version Filter --}}
    <div class="p-4 border-b border-white/10 space-y-3 flex-shrink-0">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
            <input type="text" id="search" placeholder="Search endpoints or tags... (Ctrl+K)" class="w-full pl-9 pr-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
        </div>
        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Workspace</p>
                    <p class="text-sm text-slate-300">Current testing context</p>
                </div>
                <span id="sidebar-generated-at" class="text-[11px] text-slate-500">Waiting…</span>
            </div>
            <div id="server-switcher-wrapper" class="hidden">
                <label for="server-switcher" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Server</label>
                <select id="server-switcher" aria-label="Server" onchange="switchEnvironment(this.value)" class="w-full rounded-xl border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                </select>
            </div>
            <div id="version-filter-container" class="hidden">
                <label for="version-filter" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Version</label>
                <select id="version-filter" class="w-full rounded-xl border border-slate-700 bg-slate-800/90 px-3 py-2.5 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="all">All Versions</option>
                </select>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <button type="button" onclick="showOverviewPanel()" class="rounded-xl border border-white/10 bg-slate-800/70 px-2 py-2 text-[11px] font-medium text-slate-300 transition hover:border-cyan-400/30 hover:text-white">
                    <i class="fas fa-chart-line mb-1 block text-cyan-400"></i>
                    Overview
                </button>
                <button type="button" onclick="openAuthModal()" class="rounded-xl border border-white/10 bg-slate-800/70 px-2 py-2 text-[11px] font-medium text-slate-300 transition hover:border-indigo-400/30 hover:text-white">
                    <i class="fas fa-key mb-1 block text-indigo-400"></i>
                    Auth
                </button>
                <button type="button" onclick="showHistoryPanel()" class="rounded-xl border border-white/10 bg-slate-800/70 px-2 py-2 text-[11px] font-medium text-slate-300 transition hover:border-amber-400/30 hover:text-white">
                    <i class="fas fa-history mb-1 block text-amber-400"></i>
                    History
                </button>
            </div>
        </div>
    </div>

    {{-- Endpoint Navigation (scrollable) --}}
    <nav id="endpoints-list" class="flex-1 overflow-y-auto p-4 space-y-1.5 min-h-0"></nav>

    {{-- Footer: Stats + Export + History --}}
    <div class="sidebar-footer border-t border-white/10 p-4 space-y-3">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl border border-white/10 bg-slate-800/80 py-2 px-1.5">
                <div id="endpoint-count" class="text-sm font-bold text-cyan-300">0</div>
                <div class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Endpoints</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-slate-800/80 py-2 px-1.5">
                <div id="success-rate" class="text-sm font-bold text-emerald-300">100%</div>
                <div class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Success</div>
            </div>
            <div class="rounded-xl border border-white/10 bg-slate-800/80 py-2 px-1.5">
                <div id="request-count" class="text-sm font-bold text-slate-400">0</div>
                <div class="text-[10px] uppercase tracking-[0.18em] text-slate-500">Requests</div>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="grid grid-cols-3 gap-2">
            <a href="{{ route('api.docs.export') }}" target="_blank" class="flex items-center justify-center gap-1 rounded-xl border border-white/10 bg-slate-800/80 px-2 py-2 text-[10px] text-slate-300 hover:bg-slate-700 hover:text-white transition-colors truncate">
                <i class="fas fa-download text-[9px]"></i> OpenAPI
            </a>
            <a href="{{ route('api.docs.export', ['format' => 'postman']) }}" target="_blank" class="flex items-center justify-center gap-1 rounded-xl border border-orange-400/20 bg-orange-400/10 px-2 py-2 text-[10px] text-orange-300 hover:bg-orange-400/15 hover:text-orange-200 transition-colors truncate">
                <i class="fas fa-paper-plane text-[9px]"></i> Postman
            </a>
            <a href="{{ route('api.docs.export', ['format' => 'insomnia']) }}" target="_blank" class="flex items-center justify-center gap-1 rounded-xl border border-violet-400/20 bg-violet-400/10 px-2 py-2 text-[10px] text-violet-300 hover:bg-violet-400/15 hover:text-violet-200 transition-colors truncate">
                <i class="fas fa-moon text-[9px]"></i> Insomnia
            </a>
        </div>

        {{-- History Button --}}
        <button onclick="showHistoryPanel()" class="w-full flex items-center justify-center gap-1.5 rounded-xl border border-cyan-400/15 bg-cyan-400/10 px-3 py-2 text-[11px] text-cyan-300 hover:bg-cyan-400/15 hover:text-cyan-200 transition-colors">
            <i class="fas fa-history text-[10px]"></i> History
            <span id="history-count" class="bg-cyan-500/20 text-cyan-400 px-1 rounded text-[9px] leading-tight">0</span>
        </button>
    </div>
</aside>
