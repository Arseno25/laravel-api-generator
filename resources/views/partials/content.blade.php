{{-- Main Content Area --}}
<main class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0 min-w-0">
    {{-- Desktop Header --}}
    <header class="hidden lg:flex border-b border-white/5 px-6 py-4 items-center justify-between bg-slate-900/40 backdrop-blur-md flex-shrink-0">
        <h2 class="font-semibold text-lg truncate" id="schema-title">API Documentation</h2>
        <button onclick="openAuthModal()" class="px-4 py-2 bg-slate-800 rounded-lg text-sm hover:bg-slate-700 flex items-center gap-2 transition-colors flex-shrink-0">
            <i class="fas fa-key"></i><span id="auth-status">Set Token</span>
        </button>
    </header>

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto p-4 lg:p-6 pb-16" id="content-area">
        {{-- Welcome Screen --}}
        <div class="text-center py-12 lg:py-20 max-w-4xl mx-auto px-4">
            <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-5 rounded-2xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center">
                <i class="fas fa-rocket text-2xl lg:text-3xl text-indigo-400"></i>
            </div>
            <h3 class="text-xl lg:text-2xl font-bold mb-2">Welcome to API Magic</h3>
            <p class="text-slate-400 mb-6 text-sm lg:text-base">Your advanced API documentation and testing platform</p>
            <div class="flex flex-wrap justify-center items-center gap-2 text-xs lg:text-sm text-slate-500">
                <span class="px-2.5 py-1 lg:px-3 lg:py-1.5 rounded-lg flex items-center gap-1.5 bg-slate-800/50 border border-white/5 shadow-sm">
                    <span class="method-badge method-get">GET</span> Retrieve
                </span>
                <span class="px-2.5 py-1 lg:px-3 lg:py-1.5 rounded-lg flex items-center gap-1.5 bg-slate-800/50 border border-white/5 shadow-sm">
                    <span class="method-badge method-post">POST</span> Create
                </span>
                <span class="px-2.5 py-1 lg:px-3 lg:py-1.5 rounded-lg flex items-center gap-1.5 bg-slate-800/50 border border-white/5 shadow-sm">
                    <span class="method-badge method-put">PUT</span> Update
                </span>
                <span class="px-2.5 py-1 lg:px-3 lg:py-1.5 rounded-lg flex items-center gap-1.5 bg-slate-800/50 border border-white/5 shadow-sm">
                    <span class="method-badge method-delete">DELETE</span> Delete
                </span>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="bg-slate-900/80 backdrop-blur-md border-t border-slate-800 py-2.5 px-4 lg:px-6 shrink-0">
        <div class="flex items-center justify-center gap-2 text-xs lg:text-sm text-slate-500">
            <span>Made with</span>
            <i class="fas fa-heart text-red-500"></i>
            <span>by</span>
            <a href="https://github.com/Arseno25" target="_blank" rel="noopener noreferrer" class="text-indigo-400 hover:text-indigo-300 font-medium transition-colors flex items-center gap-1">
                <i class="fab fa-github"></i> Arseno25
            </a>
        </div>
    </footer>
</main>
