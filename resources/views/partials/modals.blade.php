{{-- Modal Dialogs --}}

{{-- Auth Modal --}}
<div id="auth-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-slate-800/95 rounded-[1.75rem] shadow-2xl w-full max-w-lg border border-white/10 overflow-hidden">
        <div class="border-b border-white/10 bg-white/5 px-5 py-4 lg:px-6">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500/20 to-indigo-500/20 text-cyan-300">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Authentication Settings</h3>
                    <p class="mt-1 text-sm text-slate-400">Choose the auth mode used when sending requests from this console.</p>
                </div>
            </div>
        </div>
        <div class="p-5 lg:p-6">
            <div class="mb-4 flex flex-wrap gap-2">
                <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-cyan-200">Bearer</span>
                <span class="rounded-full border border-indigo-400/20 bg-indigo-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-indigo-200">Sanctum</span>
                <span class="rounded-full border border-violet-400/20 bg-violet-400/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-violet-200">OAuth2</span>
            </div>

            <div class="mb-5 bg-slate-900/80 border border-white/10 rounded-2xl p-4 flex items-center gap-3">
                <input type="checkbox" id="auth-sanctum-cookie" class="w-5 h-5 rounded border-slate-600 text-indigo-500 focus:ring-indigo-500 bg-slate-800">
                <div>
                    <span class="block text-sm font-medium text-slate-200">Use Sanctum SPA Cookie Auth</span>
                    <span class="block text-xs text-slate-400">Fetch CSRF and include credentials</span>
                </div>
            </div>

            <div id="oauth2-section" class="mb-5">
                <button onclick="startOAuthLogin()" type="button" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-900/80 border border-white/10 rounded-2xl text-sm font-medium hover:bg-slate-700 transition-colors text-slate-200">
                    <i class="fab fa-openid text-indigo-400 text-lg"></i>
                    <span>Login with OAuth 2.0</span>
                </button>
            </div>

            <div class="mb-4 relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-700"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-slate-800 px-2 text-slate-500">OR BEARER TOKEN</span>
                </div>
            </div>

            <input type="password" id="auth-token" placeholder="1|abc123xyz..." class="w-full px-4 py-3 bg-slate-900 border border-white/10 rounded-2xl text-sm font-mono mb-4 focus:outline-none focus:ring-2 focus:ring-cyan-500 text-slate-200">

            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <button onclick="saveToken()" class="flex-1 px-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 text-white rounded-2xl hover:from-cyan-400 hover:to-blue-400 font-medium transition-colors text-sm shadow-lg shadow-cyan-500/20">Save Settings</button>
                <button onclick="clearToken()" class="px-4 py-3 bg-slate-700 text-slate-300 rounded-2xl hover:bg-slate-600 transition-colors text-sm">Clear</button>
                <button onclick="closeAuthModal()" class="px-4 py-3 bg-slate-700 text-slate-300 rounded-2xl hover:bg-slate-600 transition-colors text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>
