{{-- Sidebar Navigation --}}
<aside id="sidebar" class="fixed lg:relative w-72 bg-slate-900/60 backdrop-blur-2xl border-r border-white/5 flex flex-col h-full z-50 -translate-x-full lg:translate-x-0 sidebar-panel shadow-2xl shadow-indigo-500/5">
    {{-- Brand --}}
    <div class="p-4 border-b border-slate-800 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <div class="min-w-0">
                <h1 class="font-bold text-lg truncate">API Magic</h1>
                <p class="text-xs text-slate-500">v<span id="version">1.0.0</span></p>
            </div>
        </div>
    </div>

    {{-- Search & Version Filter --}}
    <div class="p-3 border-b border-slate-800 space-y-2 flex-shrink-0">
        <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
            <input type="text" id="search" placeholder="Search... (Ctrl+K)" class="w-full pl-9 pr-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div class="flex gap-2">
            <div id="version-filter-container" class="hidden flex-1">
                <select id="version-filter" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Versions</option>
                </select>
            </div>
            <div class="flex-1">
                <select id="env-switcher" onchange="switchEnvironment(this.value)" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="local">Local</option>
                    <option value="staging">Staging</option>
                    <option value="production">Production</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Endpoint Navigation (scrollable) --}}
    <nav id="endpoints-list" class="flex-1 overflow-y-auto p-3 space-y-1 min-h-0"></nav>

    {{-- Footer: Stats + Export + History --}}
    <div class="sidebar-footer border-t border-slate-800 p-3 space-y-2">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-1.5 text-center">
            <div class="bg-slate-800/80 rounded-lg py-1.5 px-1">
                <div id="endpoint-count" class="text-sm font-bold text-indigo-400">0</div>
                <div class="text-[10px] text-slate-500">Endpoints</div>
            </div>
            <div class="bg-slate-800/80 rounded-lg py-1.5 px-1">
                <div id="success-rate" class="text-sm font-bold text-green-400">100%</div>
                <div class="text-[10px] text-slate-500">Success</div>
            </div>
            <div class="bg-slate-800/80 rounded-lg py-1.5 px-1">
                <div id="request-count" class="text-sm font-bold text-slate-400">0</div>
                <div class="text-[10px] text-slate-500">Requests</div>
            </div>
        </div>

        {{-- Export Buttons --}}
        <div class="grid grid-cols-3 gap-1.5">
            <a href="/api/docs/export" target="_blank" class="flex items-center justify-center gap-1 px-2 py-1.5 bg-slate-800/80 rounded-lg text-[10px] text-slate-400 hover:bg-slate-700 hover:text-slate-200 transition-colors truncate">
                <i class="fas fa-download text-[9px]"></i> OpenAPI
            </a>
            <a href="/api/docs/export?format=postman" target="_blank" class="flex items-center justify-center gap-1 px-2 py-1.5 bg-slate-800/80 rounded-lg text-[10px] text-orange-400 hover:bg-slate-700 hover:text-orange-300 transition-colors truncate">
                <i class="fas fa-paper-plane text-[9px]"></i> Postman
            </a>
            <a href="/api/docs/export?format=insomnia" target="_blank" class="flex items-center justify-center gap-1 px-2 py-1.5 bg-slate-800/80 rounded-lg text-[10px] text-purple-400 hover:bg-slate-700 hover:text-purple-300 transition-colors truncate">
                <i class="fas fa-moon text-[9px]"></i> Insomnia
            </a>
        </div>

        {{-- History Button --}}
        <button onclick="showHistoryPanel()" class="w-full flex items-center justify-center gap-1.5 px-2 py-1.5 bg-slate-800/60 rounded-lg text-[11px] text-cyan-400 hover:bg-slate-700 hover:text-cyan-300 transition-colors">
            <i class="fas fa-history text-[10px]"></i> History
            <span id="history-count" class="bg-cyan-500/20 text-cyan-400 px-1 rounded text-[9px] leading-tight">0</span>
        </button>
    </div>
</aside>
