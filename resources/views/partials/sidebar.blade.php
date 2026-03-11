{{-- Sidebar Navigation --}}
<aside id="sidebar" class="fixed lg:relative w-80 max-w-[88vw] bg-slate-900/70 backdrop-blur-2xl border-r border-white/10 flex flex-col h-full z-50 -translate-x-full lg:translate-x-0 sidebar-panel shadow-2xl shadow-sky-950/30">
    {{-- Brand --}}
    <div class="p-5 border-b border-white/10 flex-shrink-0">
        <div class="flex items-center gap-3">
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
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="p-4 border-b border-white/10 space-y-3 flex-shrink-0">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
            <label for="search" class="sr-only">Search endpoints or tags</label>
            <input type="text" id="search" placeholder="Search paths, tags, or versions... (Ctrl+K)" class="w-full pl-9 pr-4 py-2.5 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
        </div>
        <div class="grid gap-3">
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
            <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-[11px] text-slate-400">
                <span class="font-semibold uppercase tracking-[0.22em] text-slate-500">Routes</span>
                <span class="truncate">Use search or browse</span>
            </div>
        </div>
    </div>

    {{-- Endpoint Navigation (scrollable) --}}
    <nav id="endpoints-list" class="flex-1 overflow-y-auto p-4 space-y-1.5 min-h-0"></nav>
</aside>
